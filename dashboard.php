<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
if ($user['role'] === 'admin') {
    redirect('admin.php');
}
if ($user['role'] === 'instructor') {
    redirect('instructor.php');
}
$pdo = db();
$coursesQuery = $pdo->prepare(
    'SELECT co.id, co.title, co.image_url, co.level, co.rating, en.progress_percent,
            u.name AS instructor_name,
            (SELECT COUNT(*) FROM lessons l WHERE l.course_id = co.id) AS lesson_count
     FROM enrollments en INNER JOIN courses co ON co.id = en.course_id
     INNER JOIN users u ON u.id = co.instructor_id
     WHERE en.student_id = ? ORDER BY en.updated_at DESC'
);
$coursesQuery->execute([$user['id']]);
$enrolledCourses = $coursesQuery->fetchAll();
$statsQuery = $pdo->prepare(
    'SELECT COUNT(*) AS course_count, COALESCE(SUM(progress_percent = 100), 0) AS completed_count
     FROM enrollments WHERE student_id = ?'
);
$statsQuery->execute([$user['id']]);
$stats = $statsQuery->fetch();
$profileError = '';
$profileName = $user['name'];
$profileMobile = $user['mobile'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'profile') {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $mobile = trim((string) ($_POST['mobile'] ?? ''));
    $profileName = $name;
    $profileMobile = $mobile;
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120 || !preg_match('/^[+0-9().\-\s]{7,24}$/', $mobile)) {
        $profileError = 'Enter a name between 2 and 120 characters and a valid mobile number.';
    } else {
        $update = $pdo->prepare('UPDATE users SET name = ?, mobile = ? WHERE id = ?');
        $update->execute([$name, $mobile, $user['id']]);
        $_SESSION['user']['name'] = $name;
        flash('success', 'Your profile has been updated.');
        redirect('dashboard.php');
    }
}
$pageTitle = 'My learning';
require __DIR__ . '/includes/header.php';
?>
<section class="dashboard-section"><div class="container"><div class="dashboard-welcome"><div><span class="section-kicker">YOUR LEARNING SPACE</span><h1>Hey, <?= e(explode(' ', $user['name'])[0]) ?> <span>👋</span></h1><p>Every lesson is a step forward. Here’s where you’re headed.</p></div><a class="btn btn-primary rounded-pill px-4 py-3" href="<?= e(url('courses.php')) ?>">Find a new course <i class="bi bi-arrow-right ms-2"></i></a></div><div class="row g-3 dashboard-stats"><div class="col-6 col-lg-3"><div class="stat-card"><span class="stat-icon stat-violet"><i class="bi bi-journal-bookmark"></i></span><strong><?= (int) $stats['course_count'] ?></strong><small>Courses enrolled</small></div></div><div class="col-6 col-lg-3"><div class="stat-card"><span class="stat-icon stat-peach"><i class="bi bi-award"></i></span><strong><?= (int) $stats['completed_count'] ?></strong><small>Courses completed</small></div></div><div class="col-6 col-lg-3"><div class="stat-card"><span class="stat-icon stat-green"><i class="bi bi-lightning-charge"></i></span><strong><?= count($enrolledCourses) ? max(array_map(fn($course) => (int) $course['progress_percent'], $enrolledCourses)) : 0 ?>%</strong><small>Best progress</small></div></div><div class="col-6 col-lg-3"><div class="stat-card"><span class="stat-icon stat-blue"><i class="bi bi-stars"></i></span><strong><?= count($enrolledCourses) ?></strong><small>Active journeys</small></div></div></div>
    <?php if ($profileError): ?><div class="alert alert-danger"><?= e($profileError) ?></div><?php endif; ?>
    <div class="dashboard-main"><div class="dashboard-courses"><div class="dashboard-section-heading"><div><span class="section-kicker">PICK UP WHERE YOU LEFT OFF</span><h2>My courses</h2></div><a class="text-link" href="<?= e(url('certificates.php')) ?>">My certificates <i class="bi bi-arrow-up-right"></i></a></div>
        <?php if ($enrolledCourses): ?><div class="enrolled-course-list"><?php foreach ($enrolledCourses as $course): ?><article class="enrolled-course"><a class="enrolled-thumb" href="<?= e(url('learn.php?course_id=' . (int) $course['id'])) ?>"><img src="<?= e(course_image($course['image_url'])) ?>" alt="<?= e($course['title']) ?>"></a><div class="enrolled-info"><span class="mini-tag"><?= e(ucfirst($course['level'])) ?> COURSE</span><h3><a href="<?= e(url('learn.php?course_id=' . (int) $course['id'])) ?>"><?= e($course['title']) ?></a></h3><p>with <?= e($course['instructor_name']) ?> · <?= (int) $course['lesson_count'] ?> lessons</p><div class="progress-label"><span>Your progress</span><strong><?= (int) $course['progress_percent'] ?>%</strong></div><div class="progress" role="progressbar" aria-label="Course progress" aria-valuenow="<?= (int) $course['progress_percent'] ?>" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: <?= (int) $course['progress_percent'] ?>%"></div></div></div><a class="btn btn-outline-primary rounded-pill enrolled-action" href="<?= e(url('learn.php?course_id=' . (int) $course['id'])) ?>"><?= (int) $course['progress_percent'] === 100 ? 'Review course' : 'Continue' ?> <i class="bi bi-arrow-right ms-1"></i></a></article><?php endforeach; ?></div>
        <?php else: ?><div class="empty-state dashboard-empty"><i class="bi bi-journal-plus"></i><h3>Your next favorite course is out there</h3><p>Find a course that sparks your curiosity and start learning today.</p><a class="btn btn-primary rounded-pill px-4" href="<?= e(url('courses.php')) ?>">Explore courses</a></div><?php endif; ?>
    </div><aside class="profile-card"><span class="section-kicker">YOUR PROFILE</span><div class="profile-avatar"><?= e(initials($user['name'])) ?></div><h3><?= e($user['name']) ?></h3><p><?= e($user['email']) ?></p><button class="btn btn-outline-primary rounded-pill w-100" type="button" data-bs-toggle="collapse" data-bs-target="#profileForm" aria-expanded="false">Edit profile</button><div class="collapse mt-3" id="profileForm"><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="profile"><div class="mb-2"><label class="form-label" for="profileName">Name</label><input id="profileName" class="form-control" name="name" value="<?= e($profileName) ?>" required minlength="2" maxlength="120"></div><div class="mb-3"><label class="form-label" for="profileMobile">Mobile</label><input id="profileMobile" class="form-control" name="mobile" value="<?= e($profileMobile) ?>" required></div><button class="btn btn-primary w-100 rounded-pill" type="submit">Save changes</button></form></div><div class="profile-tip"><i class="bi bi-lightbulb"></i><p>Consistency beats intensity. A little learning each day adds up.</p></div></aside></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
