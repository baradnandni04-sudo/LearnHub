<?php
require_once __DIR__ . '/includes/functions.php';
if (current_user()) {
    redirect('dashboard.php');
}

$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $query = db()->prepare('SELECT id, name, email, password_hash, role, is_active FROM users WHERE email = ? LIMIT 1');
    $query->execute([$email]);
    $user = $query->fetch();
    if ($user && (int) $user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $update = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $update->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }
        session_regenerate_id(true);
        unset($user['password_hash'], $user['is_active']);
        $_SESSION['user'] = $user;
        redirect('dashboard.php');
    }
    $error = 'That email and password combination didn’t match. Please try again.';
}
$pageTitle = 'Sign in';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-section"><div class="container"><div class="auth-layout"><div class="auth-aside"><span class="section-kicker">WELCOME BACK</span><h1>Your next<br>step is waiting.</h1><p>Pick up right where you left off. Your learning journey is yours to shape.</p><div class="auth-quote"><i class="bi bi-quote"></i><p>“A little progress each day adds up to big results.”</p><span>Keep going. You’ve got this.</span></div><div class="auth-doodle"><i class="bi bi-book-half"></i></div></div><div class="auth-card"><div class="auth-card-heading"><span class="section-kicker">GOOD TO SEE YOU</span><h2>Welcome back</h2><p>Sign in to pick up where you left off.</p></div>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" novalidate><?= csrf_field() ?><div class="mb-3"><label class="form-label" for="email">Email address</label><input class="form-control" id="email" name="email" type="email" value="<?= e($email) ?>" autocomplete="email" required placeholder="you@example.com"></div><div class="mb-2"><div class="d-flex justify-content-between align-items-center"><label class="form-label" for="password">Password</label><a class="form-small-link" href="<?= e(url('forgot-password.php')) ?>">Forgot password?</a></div><div class="input-group"><input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required placeholder="Your password"><button class="btn btn-outline-secondary password-toggle" type="button" aria-label="Show password"><i class="bi bi-eye"></i></button></div></div><button class="btn btn-primary w-100 py-3 rounded-pill mt-4" type="submit">Sign in <i class="bi bi-arrow-right ms-2"></i></button><p class="auth-switch mt-4">New to LearnHub? <a href="<?= e(url('register.php')) ?>">Create an account</a></p></form></div></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
