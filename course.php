<?php
require_once __DIR__ . '/includes/functions.php';
$courseId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$viewer = current_user();
if ($viewer) {
    $viewer = require_login();
}
$query = db()->prepare(
    'SELECT co.*, u.name AS instructor_name, u.bio AS instructor_bio, u.avatar_url,
            c.name AS category_name,
            (SELECT COUNT(*) FROM enrollments en WHERE en.course_id = co.id) AS student_count,
            (SELECT COUNT(*) FROM lessons l WHERE l.course_id = co.id) AS lesson_count
     FROM courses co INNER JOIN users u ON u.id = co.instructor_id
     INNER JOIN categories c ON c.id = co.category_id
     WHERE co.id = ? AND (
        co.status = "published" OR ? = "admin"
        OR (? = "instructor" AND co.instructor_id = ?)
     )'
);
$query->execute([
    $courseId,
    $viewer['role'] ?? 'guest',
    $viewer['role'] ?? 'guest',
    $viewer['id'] ?? 0,
]);
$course = $query->fetch();
if (!$course) {
    http_response_code(404);
    $pageTitle = 'Course not found';
    require __DIR__ . '/includes/header.php';
    echo '<section class="section-space"><div class="container empty-state"><i class="bi bi-compass"></i><h1>We couldn’t find that course</h1><a class="btn btn-primary rounded-pill" href="' . e(url('courses.php')) . '">Explore courses</a></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
$user = $viewer;
$isEnrolled = false;
if ($user && $user['role'] === 'student') {
    $enrollment = db()->prepare('SELECT id, progress_percent FROM enrollments WHERE student_id = ? AND course_id = ?');
    $enrollment->execute([$user['id'], $courseId]);
    $enrollmentData = $enrollment->fetch();
    $isEnrolled = (bool) $enrollmentData;
}
$lessonQuery = db()->prepare('SELECT title, duration_minutes FROM lessons WHERE course_id = ? ORDER BY sort_order, id');
$lessonQuery->execute([$courseId]);
$lessons = $lessonQuery->fetchAll();
$pageTitle = $course['title'];
require __DIR__ . '/includes/header.php';
?>
<section class="course-detail-hero"><div class="container"><div class="row g-5 align-items-center"><div class="col-lg-7"><a class="back-link" href="<?= e(url('courses.php')) ?>"><i class="bi bi-arrow-left"></i> Explore courses</a><div class="mt-4"><span class="course-category-pill detail-category"><?= e($course['category_name']) ?></span><span class="level-pill"><?= e(ucfirst($course['level'])) ?></span></div><h1><?= e($course['title']) ?></h1><p class="course-detail-summary"><?= e($course['short_description']) ?></p><div class="detail-stats"><span><i class="bi bi-star-fill"></i> <?= number_format((float) $course['rating'], 1) ?> rating</span><span><i class="bi bi-people"></i> <?= number_format((int) $course['student_count']) ?> learners</span><span><i class="bi bi-play-circle"></i> <?= count($lessons) ?> lessons</span></div><div class="detail-instructor"><span class="instructor-avatar large"><?= e(initials($course['instructor_name'])) ?></span><div><small>YOUR INSTRUCTOR</small><strong><?= e($course['instructor_name']) ?></strong></div></div></div><div class="col-lg-5"><div class="course-enroll-card"><div class="course-preview-image"><img src="<?= e(course_image($course['image_url'])) ?>" alt="<?= e($course['title']) ?>"></div><div class="course-enroll-body"><div class="course-price-large"><?= e(format_price($course['price'])) ?></div><p>Full lifetime access to this course.</p>
    <?php if ($isEnrolled): ?><a href="<?= e(url('learn.php?course_id=' . $courseId)) ?>" class="btn btn-primary w-100 py-3 rounded-pill">Continue learning <i class="bi bi-arrow-right ms-2"></i></a>
    <?php elseif (!$user): ?><a href="<?= e(url('login.php')) ?>" class="btn btn-primary w-100 py-3 rounded-pill">Sign in to enroll <i class="bi bi-arrow-right ms-2"></i></a><p class="enroll-footnote">New here? <a href="<?= e(url('register.php')) ?>">Create a free account</a>.</p>
    <?php elseif ($user['role'] !== 'student'): ?><a href="<?= e(url($user['role'] === 'admin' ? 'admin.php' : 'instructor.php')) ?>" class="btn btn-outline-primary w-100 py-3 rounded-pill">Go to your workspace</a>
    <?php elseif ((float) $course['price'] > 0): ?><button class="btn btn-outline-primary w-100 py-3 rounded-pill" type="button" disabled>Paid enrollment unavailable</button><p class="enroll-footnote">Checkout isn’t set up for this demo. Contact us for enrollment.</p>
    <?php else: ?><form action="<?= e(url('enroll.php')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="course_id" value="<?= $courseId ?>"><button class="btn btn-primary w-100 py-3 rounded-pill" type="submit">Enroll for free <i class="bi bi-arrow-right ms-2"></i></button></form><?php endif; ?>
    <div class="course-includes"><span><i class="bi bi-infinity"></i> Learn at your own pace</span><span><i class="bi bi-phone"></i> Learn on any device</span><span><i class="bi bi-award"></i> Certificate of completion</span></div></div></div></div></div></div></section>
<section class="section-space course-content-section"><div class="container"><div class="row g-5"><div class="col-lg-7"><span class="section-kicker">WHAT YOU’LL LEARN</span><h2 class="content-heading">A practical path from curious to confident.</h2><p class="course-long-description"><?= nl2br(e($course['description'])) ?></p><h3 class="curriculum-heading">Course content <span><?= count($lessons) ?> lessons</span></h3><div class="curriculum-list"><?php foreach ($lessons as $index => $lesson): ?><div class="curriculum-item"><span class="curriculum-index"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><span class="curriculum-title"><?= e($lesson['title']) ?></span><span class="curriculum-time"><?= (int) $lesson['duration_minutes'] ?> min</span></div><?php endforeach; ?><?php if (!$lessons): ?><p class="text-secondary">Lessons will be added soon.</p><?php endif; ?></div></div><div class="col-lg-4 offset-lg-1"><div class="instructor-profile"><span class="section-kicker">MEET YOUR INSTRUCTOR</span><div class="detail-instructor mt-3"><span class="instructor-avatar large"><?= e(initials($course['instructor_name'])) ?></span><div><strong><?= e($course['instructor_name']) ?></strong><small>Course instructor</small></div></div><p><?= e($course['instructor_bio'] ?: 'A passionate educator sharing practical skills and fresh ideas with learners everywhere.') ?></p></div></div></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
