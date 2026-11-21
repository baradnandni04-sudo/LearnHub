<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(400);
        exit('Your session expired. Please go back, refresh the page, and try again.');
    }
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('info', 'Please sign in to continue.');
        redirect('login.php');
    }

    $query = db()->prepare('SELECT id, name, email, mobile, role, is_active FROM users WHERE id = ? LIMIT 1');
    $query->execute([$user['id']]);
    $freshUser = $query->fetch();
    if (!$freshUser || (int) $freshUser['is_active'] !== 1) {
        unset($_SESSION['user']);
        session_regenerate_id(true);
        flash('warning', 'Your account is unavailable. Please contact the site administrator.');
        redirect('login.php');
    }

    unset($freshUser['is_active']);
    $freshUser['id'] = (int) $freshUser['id'];
    $_SESSION['user'] = $freshUser;
    return $freshUser;
}

function require_role(string ...$roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('You do not have permission to view this page.');
    }
    return $user;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function format_price(int|float|string|null $price): string
{
    $amount = (float) ($price ?? 0);
    return $amount <= 0 ? 'Free' : '$' . number_format($amount, 2);
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= mb_substr($part, 0, 1);
    }
    return strtoupper($letters ?: 'LH');
}

function course_image(?string $image): string
{
    return $image ?: 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=85';
}

function can_access_course(PDO $pdo, int $courseId, array $user): bool
{
    if (in_array($user['role'], ['admin', 'instructor'], true)) {
        $query = $pdo->prepare(
            'SELECT id FROM courses WHERE id = ? AND (instructor_id = ? OR ? = "admin")'
        );
        $query->execute([$courseId, $user['id'], $user['role']]);
        return (bool) $query->fetch();
    }
    $query = $pdo->prepare('SELECT id FROM enrollments WHERE course_id = ? AND student_id = ?');
    $query->execute([$courseId, $user['id']]);
    return (bool) $query->fetch();
}

function update_course_progress(PDO $pdo, int $courseId, int $studentId): int
{
    $count = $pdo->prepare('SELECT COUNT(*) FROM lessons WHERE course_id = ?');
    $count->execute([$courseId]);
    $total = (int) $count->fetchColumn();
    if ($total === 0) {
        return 0;
    }

    $completed = $pdo->prepare(
        'SELECT COUNT(*) FROM lesson_progress lp
         INNER JOIN lessons l ON l.id = lp.lesson_id
         WHERE l.course_id = ? AND lp.student_id = ? AND lp.completed_at IS NOT NULL'
    );
    $completed->execute([$courseId, $studentId]);
    $progress = min(100, (int) round(((int) $completed->fetchColumn() / $total) * 100));
    $save = $pdo->prepare(
        'UPDATE enrollments SET progress_percent = ?, updated_at = CURRENT_TIMESTAMP
         WHERE course_id = ? AND student_id = ?'
    );
    $save->execute([$progress, $courseId, $studentId]);
    return $progress;
}
