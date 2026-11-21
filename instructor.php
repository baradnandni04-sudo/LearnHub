<?php
require_once __DIR__ . '/includes/functions.php';
$instructor = require_role('instructor');
$pdo = db();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'create_course') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $short = trim((string) ($_POST['short_description'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?: 0;
        $level = (string) ($_POST['level'] ?? 'beginner');
        $category = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
        $category->execute([$categoryId]);
        if (mb_strlen($title) < 4 || mb_strlen($title) > 180 || mb_strlen($short) < 10 || mb_strlen($short) > 300 || $description === '') {
            $error = 'Add a title, a short summary (10–300 characters), and a full description.';
        } elseif (!in_array($level, ['beginner', 'intermediate', 'advanced'], true) || !$category->fetch()) {
            $error = 'Choose a valid course level and category.';
        } else {
            $slug = trim((string) preg_replace('/[^a-z0-9]+/i', '-', strtolower($title)), '-') . '-' . bin2hex(random_bytes(3));
            $create = $pdo->prepare(
                'INSERT INTO courses (instructor_id, category_id, title, slug, short_description, description, price, level, status)
                 VALUES (?, ?, ?, ?, ?, ?, 0, ?, "draft")'
            );
            $create->execute([$instructor['id'], $categoryId, $title, $slug, $short, $description, $level]);
            flash('success', 'Course draft created. Add your first lesson below.');
            redirect('instructor.php');
        }
    } elseif ($action === 'add_lesson') {
        $courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT) ?: 0;
        $title = trim((string) ($_POST['lesson_title'] ?? ''));
        $description = trim((string) ($_POST['lesson_description'] ?? ''));
        $videoUrl = trim((string) ($_POST['video_url'] ?? ''));
        $duration = filter_input(INPUT_POST, 'duration_minutes', FILTER_VALIDATE_INT);
        $owned = $pdo->prepare('SELECT id FROM courses WHERE id = ? AND instructor_id = ?');
        $owned->execute([$courseId, $instructor['id']]);
        $urlValid = $videoUrl === '' || (filter_var($videoUrl, FILTER_VALIDATE_URL) && parse_url($videoUrl, PHP_URL_SCHEME) === 'https');
        if (!$owned->fetch() || mb_strlen($title) < 2 || mb_strlen($title) > 180 || $duration === false || $duration < 1 || $duration > 600 || !$urlValid) {
            $error = 'Check the course, lesson title, duration (1–600 minutes), and HTTPS video URL.';
        } else {
            $order = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM lessons WHERE course_id = ?');
            $order->execute([$courseId]);
            $insert = $pdo->prepare(
                'INSERT INTO lessons (course_id, title, description, video_url, duration_minutes, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $insert->execute([$courseId, $title, $description, $videoUrl ?: null, $duration, $order->fetchColumn()]);
            flash('success', 'Lesson added to your course.');
            redirect('instructor.php');
        }
    } elseif ($action === 'course_status') {
        $courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT) ?: 0;
        $status = (string) ($_POST['status'] ?? '');
        if (!in_array($status, ['published', 'draft'], true)) {
            $error = 'Choose a valid course status.';
        } else {
            $update = $pdo->prepare('UPDATE courses SET status = ? WHERE id = ? AND instructor_id = ?');
            $update->execute([$status, $courseId, $instructor['id']]);
            flash('success', 'Course status updated.');
            redirect('instructor.php');
        }
    } else {
        $error = 'That action is not available.';
    }
}
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$courseQuery = $pdo->prepare(
    'SELECT co.id, co.title, co.status, co.short_description,
            (SELECT COUNT(*) FROM lessons l WHERE l.course_id = co.id) AS lesson_count,
            (SELECT COUNT(*) FROM enrollments en WHERE en.course_id = co.id) AS student_count
     FROM courses co WHERE co.instructor_id = ? ORDER BY co.created_at DESC'
);
$courseQuery->execute([$instructor['id']]);
$courses = $courseQuery->fetchAll();
$pageTitle = 'Instructor workspace';
require __DIR__ . '/includes/header.php';
?>
<section class="dashboard-section"><div class="container"><div class="dashboard-welcome"><div><span class="section-kicker">YOUR TEACHING SPACE</span><h1>Instructor workspace</h1><p>Share what you know. Help someone take their next step.</p></div><span class="admin-chip"><i class="bi bi-person-workspace"></i> Instructor</span></div><?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><div class="row g-4"><div class="col-lg-4"><div class="admin-panel"><div class="admin-panel-heading"><div><span class="section-kicker">START SOMETHING</span><h2>Create a course</h2></div></div><form method="post" class="admin-create-form"><?= csrf_field() ?><input type="hidden" name="action" value="create_course"><div class="mb-3"><label class="form-label" for="title">Course title</label><input class="form-control" id="title" name="title" required maxlength="180"></div><div class="mb-3"><label class="form-label" for="category">Category</label><select class="form-select" id="category" name="category_id" required><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option><?php endforeach; ?></select></div><div class="mb-3"><label class="form-label" for="level">Level</label><select class="form-select" id="level" name="level"><option value="beginner">Beginner</option><option value="intermediate">Intermediate</option><option value="advanced">Advanced</option></select></div><div class="mb-3"><label class="form-label" for="short">Short summary</label><input class="form-control" id="short" name="short_description" required maxlength="300"></div><div class="mb-3"><label class="form-label" for="description">Course description</label><textarea class="form-control" id="description" name="description" rows="4" required></textarea></div><button class="btn btn-primary w-100 rounded-pill py-2" type="submit">Create draft <i class="bi bi-arrow-right ms-2"></i></button></form></div></div><div class="col-lg-8"><div class="dashboard-section-heading"><div><span class="section-kicker">YOUR COURSES</span><h2>Course library</h2></div></div><?php if ($courses): ?><div class="instructor-course-list"><?php foreach ($courses as $course): ?><article class="instructor-course-card"><div class="instructor-course-top"><div><span class="role-badge <?= e($course['status']) ?>"><?= e(ucfirst($course['status'])) ?></span><h3><?= e($course['title']) ?></h3><p><?= e($course['short_description']) ?></p></div><div class="instructor-course-metrics"><span><i class="bi bi-people"></i> <?= (int) $course['student_count'] ?> learners</span><span><i class="bi bi-play-circle"></i> <?= (int) $course['lesson_count'] ?> lessons</span></div></div><form method="post" class="course-status-form"><?= csrf_field() ?><input type="hidden" name="action" value="course_status"><input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>"><select class="form-select form-select-sm" name="status"><option value="draft" <?= $course['status'] === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= $course['status'] === 'published' ? 'selected' : '' ?>>Published</option></select><button class="btn btn-sm btn-outline-primary" type="submit">Update status</button></form><details class="lesson-editor"><summary><i class="bi bi-plus-circle me-2"></i>Add a lesson</summary><form method="post" class="admin-create-form mt-3"><?= csrf_field() ?><input type="hidden" name="action" value="add_lesson"><input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>"><div class="row g-3"><div class="col-md-8"><label class="form-label">Lesson title</label><input class="form-control" name="lesson_title" required maxlength="180"></div><div class="col-md-4"><label class="form-label">Duration (minutes)</label><input class="form-control" name="duration_minutes" type="number" min="1" max="600" value="10" required></div><div class="col-12"><label class="form-label">Lesson description</label><textarea class="form-control" name="lesson_description" rows="2"></textarea></div><div class="col-12"><label class="form-label">Video URL (HTTPS, optional)</label><input class="form-control" name="video_url" type="url" placeholder="https://www.youtube-nocookie.com/embed/..."></div><div class="col-12"><button class="btn btn-primary btn-sm rounded-pill px-3" type="submit">Save lesson</button></div></div></form></details><a class="text-link instructor-preview-link" href="<?= e(url('course.php?id=' . (int) $course['id'])) ?>">Preview course <i class="bi bi-arrow-up-right"></i></a></article><?php endforeach; ?></div><?php else: ?><div class="empty-state dashboard-empty"><i class="bi bi-journal-plus"></i><h3>Your first course starts here</h3><p>Give your knowledge a home and help learners grow.</p></div><?php endif; ?></div></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
