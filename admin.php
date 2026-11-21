<?php
require_once __DIR__ . '/includes/functions.php';
$admin = require_role('admin');
$pdo = db();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'user') {
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT) ?: 0;
        $role = (string) ($_POST['role'] ?? '');
        $active = isset($_POST['is_active']) ? 1 : 0;
        if (!in_array($role, ['student', 'instructor'], true)) {
            $error = 'Choose a student or instructor role.';
        } else {
            $target = $pdo->prepare('SELECT id FROM users WHERE id = ? AND role <> "admin"');
            $target->execute([$userId]);
            if ($target->fetch()) {
                $update = $pdo->prepare('UPDATE users SET role = ?, is_active = ? WHERE id = ? AND role <> "admin"');
                $update->execute([$role, $active, $userId]);
                flash('success', 'User access has been updated.');
                redirect('admin.php#people');
            }
            $error = 'That user could not be updated. Admin accounts cannot be changed here.';
        }
    } elseif ($action === 'course_status') {
        $courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT) ?: 0;
        $status = (string) ($_POST['status'] ?? '');
        if (!in_array($status, ['published', 'draft'], true)) {
            $error = 'Choose a valid course status.';
        } else {
            $update = $pdo->prepare('UPDATE courses SET status = ? WHERE id = ?');
            $update->execute([$status, $courseId]);
            flash('success', 'Course status updated.');
            redirect('admin.php#courses');
        }
    } elseif ($action === 'create_course') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $shortDescription = trim((string) ($_POST['short_description'] ?? ''));
        $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?: 0;
        $instructorId = filter_input(INPUT_POST, 'instructor_id', FILTER_VALIDATE_INT) ?: 0;
        $price = filter_var($_POST['price'] ?? '', FILTER_VALIDATE_FLOAT);
        $level = (string) ($_POST['level'] ?? 'beginner');
        $image = trim((string) ($_POST['image_url'] ?? ''));
        if (mb_strlen($title) < 4 || mb_strlen($title) > 180 || mb_strlen($shortDescription) > 300 || $description === '') {
            $error = 'Enter a course title (4–180 characters), a short summary (up to 300), and a description.';
        } elseif ($price === false || $price < 0 || !in_array($level, ['beginner', 'intermediate', 'advanced'], true)) {
            $error = 'Enter a valid non-negative price and course level.';
        } elseif ($image !== '' && (!filter_var($image, FILTER_VALIDATE_URL) || parse_url($image, PHP_URL_SCHEME) !== 'https')) {
            $error = 'Course image must be a valid HTTPS URL.';
        } else {
            $owner = $pdo->prepare('SELECT id FROM users WHERE id = ? AND role = "instructor" AND is_active = 1');
            $owner->execute([$instructorId]);
            $category = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
            $category->execute([$categoryId]);
            if (!$owner->fetch() || !$category->fetch()) {
                $error = 'Choose an active instructor and a valid category.';
            } else {
                $slug = trim((string) preg_replace('/[^a-z0-9]+/i', '-', strtolower($title)), '-') . '-' . bin2hex(random_bytes(3));
                $insert = $pdo->prepare(
                    'INSERT INTO courses (instructor_id, category_id, title, slug, short_description, description, image_url, price, level, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "published")'
                );
                $insert->execute([$instructorId, $categoryId, $title, $slug, $shortDescription, $description, $image ?: null, $price, $level]);
                flash('success', 'Your course is published and ready for lessons.');
                redirect('admin.php#courses');
            }
        }
    } else {
        $error = 'That action is not available.';
    }
}
$stats = $pdo->query(
    'SELECT (SELECT COUNT(*) FROM users WHERE role = "student") AS students,
            (SELECT COUNT(*) FROM users WHERE role = "instructor") AS instructors,
            (SELECT COUNT(*) FROM courses) AS courses,
            (SELECT COUNT(*) FROM enrollments) AS enrollments'
)->fetch();
$users = $pdo->query('SELECT id, name, email, role, is_active, created_at FROM users ORDER BY created_at DESC LIMIT 100')->fetchAll();
$courses = $pdo->query(
    'SELECT co.id, co.title, co.status, co.price, u.name AS instructor_name,
            (SELECT COUNT(*) FROM enrollments en WHERE en.course_id = co.id) AS student_count
     FROM courses co INNER JOIN users u ON u.id = co.instructor_id ORDER BY co.created_at DESC LIMIT 100'
)->fetchAll();
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$instructors = $pdo->query('SELECT id, name FROM users WHERE role = "instructor" AND is_active = 1 ORDER BY name')->fetchAll();
$pageTitle = 'Admin dashboard';
require __DIR__ . '/includes/header.php';
?>
<section class="dashboard-section admin-section"><div class="container"><div class="dashboard-welcome"><div><span class="section-kicker">LEARNHUB CONTROL ROOM</span><h1>Admin dashboard</h1><p>Manage your learning community and course catalog.</p></div><span class="admin-chip"><i class="bi bi-shield-check"></i> Administrator</span></div>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
    <div class="row g-3 dashboard-stats"><div class="col-6 col-lg-3"><div class="stat-card"><span class="stat-icon stat-violet"><i class="bi bi-people"></i></span><strong><?= (int) $stats['students'] ?></strong><small>Students</small></div></div><div class="col-6 col-lg-3"><div class="stat-card"><span class="stat-icon stat-peach"><i class="bi bi-person-workspace"></i></span><strong><?= (int) $stats['instructors'] ?></strong><small>Instructors</small></div></div><div class="col-6 col-lg-3"><div class="stat-card"><span class="stat-icon stat-blue"><i class="bi bi-journals"></i></span><strong><?= (int) $stats['courses'] ?></strong><small>Courses</small></div></div><div class="col-6 col-lg-3"><div class="stat-card"><span class="stat-icon stat-green"><i class="bi bi-mortarboard"></i></span><strong><?= (int) $stats['enrollments'] ?></strong><small>Enrollments</small></div></div></div>
    <div class="admin-content-grid"><div class="admin-panel" id="people"><div class="admin-panel-heading"><div><span class="section-kicker">YOUR COMMUNITY</span><h2>Students & instructors</h2></div><span class="admin-count"><?= count($users) ?> shown</span></div><div class="table-responsive"><table class="table admin-table align-middle"><thead><tr><th>Member</th><th>Joined</th><th>Access</th></tr></thead><tbody><?php foreach ($users as $member): ?><tr><td><strong><?= e($member['name']) ?></strong><small><?= e($member['email']) ?></small></td><td><?= e(date('M j, Y', strtotime($member['created_at']))) ?></td><td><?php if ($member['role'] === 'admin'): ?><span class="role-badge admin">Admin</span><?php else: ?><form method="post" class="user-role-form"><?= csrf_field() ?><input type="hidden" name="action" value="user"><input type="hidden" name="user_id" value="<?= (int) $member['id'] ?>"><select class="form-select form-select-sm" name="role" aria-label="Role for <?= e($member['name']) ?>"><option value="student" <?= $member['role'] === 'student' ? 'selected' : '' ?>>Student</option><option value="instructor" <?= $member['role'] === 'instructor' ? 'selected' : '' ?>>Instructor</option></select><label class="active-toggle"><input type="checkbox" name="is_active" value="1" <?= (int) $member['is_active'] ? 'checked' : '' ?>> Active</label><button class="btn btn-sm btn-outline-primary" type="submit">Save</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></div>
    <div class="admin-panel" id="courses"><div class="admin-panel-heading"><div><span class="section-kicker">THE LEARNING LIBRARY</span><h2>Manage courses</h2></div><button class="btn btn-primary btn-sm rounded-pill px-3" type="button" data-bs-toggle="collapse" data-bs-target="#createCourse"><i class="bi bi-plus-lg me-1"></i>New course</button></div><div class="collapse mb-4" id="createCourse"><form method="post" class="admin-create-form"><?= csrf_field() ?><input type="hidden" name="action" value="create_course"><div class="row g-3"><div class="col-md-8"><label class="form-label" for="title">Course title</label><input id="title" class="form-control" name="title" required maxlength="180"></div><div class="col-md-4"><label class="form-label" for="level">Level</label><select class="form-select" name="level" id="level"><option value="beginner">Beginner</option><option value="intermediate">Intermediate</option><option value="advanced">Advanced</option></select></div><div class="col-md-6"><label class="form-label" for="category">Category</label><select class="form-select" id="category" name="category_id" required><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option><?php endforeach; ?></select></div><div class="col-md-6"><label class="form-label" for="instructor">Instructor</label><select class="form-select" id="instructor" name="instructor_id" required><?php foreach ($instructors as $instructor): ?><option value="<?= (int) $instructor['id'] ?>"><?= e($instructor['name']) ?></option><?php endforeach; ?></select></div><div class="col-md-8"><label class="form-label" for="shortDescription">Short summary</label><input id="shortDescription" class="form-control" name="short_description" maxlength="300" required></div><div class="col-md-4"><label class="form-label" for="price">Price (USD)</label><input id="price" class="form-control" name="price" type="number" min="0" step="0.01" value="0" required></div><div class="col-12"><label class="form-label" for="description">Course description</label><textarea id="description" class="form-control" name="description" rows="3" required></textarea></div><div class="col-12"><label class="form-label" for="imageUrl">Image URL (optional, HTTPS)</label><input id="imageUrl" class="form-control" name="image_url" type="url"></div><div class="col-12"><button class="btn btn-primary rounded-pill px-4" type="submit">Create course</button></div></div></form></div><div class="table-responsive"><table class="table admin-table align-middle"><thead><tr><th>Course</th><th>Instructor</th><th>Learners</th><th>Status</th></tr></thead><tbody><?php foreach ($courses as $course): ?><tr><td><a class="admin-course-link" href="<?= e(url('course.php?id=' . (int) $course['id'])) ?>"><?= e($course['title']) ?></a><small><?= e(format_price($course['price'])) ?></small></td><td><?= e($course['instructor_name']) ?></td><td><?= (int) $course['student_count'] ?></td><td><form method="post" class="course-status-form"><?= csrf_field() ?><input type="hidden" name="action" value="course_status"><input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>"><select name="status" class="form-select form-select-sm" aria-label="Status for <?= e($course['title']) ?>"><option value="published" <?= $course['status'] === 'published' ? 'selected' : '' ?>>Published</option><option value="draft" <?= $course['status'] === 'draft' ? 'selected' : '' ?>>Draft</option></select><button class="btn btn-sm btn-outline-primary" type="submit">Save</button></form></td></tr><?php endforeach; ?></tbody></table></div></div></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
