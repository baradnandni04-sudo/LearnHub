<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_role('student');
$courseId = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT) ?: 0;
$pdo = db();
$courseQuery = $pdo->prepare(
    'SELECT co.id, co.title, co.image_url, co.instructor_id, en.progress_percent
     FROM courses co INNER JOIN enrollments en ON en.course_id = co.id
     WHERE co.id = ? AND en.student_id = ?'
);
$courseQuery->execute([$courseId, $user['id']]);
$course = $courseQuery->fetch();
if (!$course) {
    flash('warning', 'Enroll in a course before opening its lessons.');
    redirect('courses.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $lessonId = filter_input(INPUT_POST, 'lesson_id', FILTER_VALIDATE_INT) ?: 0;
    $validLesson = $pdo->prepare('SELECT id FROM lessons WHERE id = ? AND course_id = ?');
    $validLesson->execute([$lessonId, $courseId]);
    if (!$validLesson->fetch()) {
        http_response_code(400);
        exit('The selected lesson does not belong to this course.');
    }
    $mark = $pdo->prepare(
        'INSERT INTO lesson_progress (student_id, lesson_id, completed_at)
         VALUES (?, ?, CURRENT_TIMESTAMP)
         ON DUPLICATE KEY UPDATE completed_at = CURRENT_TIMESTAMP'
    );
    $mark->execute([$user['id'], $lessonId]);
    $progress = update_course_progress($pdo, $courseId, (int) $user['id']);
    if ($progress === 100) {
        flash('success', 'Course complete! Your certificate is ready.');
        redirect('certificates.php');
    }
    flash('success', 'Lesson complete — nice work!');
    redirect('learn.php?course_id=' . $courseId . '&lesson_id=' . $lessonId);
}
$lessonsQuery = $pdo->prepare(
    'SELECT l.id, l.title, l.description, l.video_url, l.duration_minutes,
            lp.completed_at
     FROM lessons l LEFT JOIN lesson_progress lp
        ON lp.lesson_id = l.id AND lp.student_id = ?
     WHERE l.course_id = ? ORDER BY l.sort_order, l.id'
);
$lessonsQuery->execute([$user['id'], $courseId]);
$lessons = $lessonsQuery->fetchAll();
$selectedId = filter_input(INPUT_GET, 'lesson_id', FILTER_VALIDATE_INT) ?: 0;
$selected = null;
foreach ($lessons as $lesson) {
    if ((int) $lesson['id'] === $selectedId) {
        $selected = $lesson;
        break;
    }
}
if (!$selected) {
    foreach ($lessons as $lesson) {
        if (!$lesson['completed_at']) {
            $selected = $lesson;
            break;
        }
    }
}
$selected ??= $lessons[0] ?? null;
$progress = update_course_progress($pdo, $courseId, (int) $user['id']);
$pageTitle = 'Learning · ' . $course['title'];
require __DIR__ . '/includes/header.php';
?>
<section class="lesson-page"><div class="container-fluid lesson-container"><div class="lesson-topbar"><a href="<?= e(url('dashboard.php')) ?>" class="back-link"><i class="bi bi-arrow-left"></i> My learning</a><div class="lesson-course-title"><span>NOW LEARNING</span><strong><?= e($course['title']) ?></strong></div><div class="lesson-progress-summary"><span><?= $progress ?>% complete</span><div class="progress"><div class="progress-bar" style="width: <?= $progress ?>%"></div></div></div></div><div class="row g-0 lesson-workspace"><aside class="col-lg-4 lesson-sidebar"><div class="lesson-sidebar-heading"><span class="section-kicker">COURSE CONTENT</span><h2><?= e($course['title']) ?></h2><p><?= count(array_filter($lessons, fn($item) => $item['completed_at'])) ?> of <?= count($lessons) ?> lessons complete</p></div><div class="lesson-list"><?php foreach ($lessons as $index => $lesson): ?><a class="lesson-list-item <?= $selected && (int) $selected['id'] === (int) $lesson['id'] ? 'active' : '' ?>" href="<?= e(url('learn.php?course_id=' . $courseId . '&lesson_id=' . (int) $lesson['id'])) ?>"><span class="lesson-status"><?= $lesson['completed_at'] ? '<i class="bi bi-check-circle-fill"></i>' : '<i class="bi bi-play-circle"></i>' ?></span><span class="lesson-list-copy"><small>LESSON <?= $index + 1 ?></small><strong><?= e($lesson['title']) ?></strong></span><span class="lesson-duration"><?= (int) $lesson['duration_minutes'] ?>m</span></a><?php endforeach; ?><?php if (!$lessons): ?><p class="p-3 text-secondary">No lessons have been added to this course yet.</p><?php endif; ?></div></aside><div class="col-lg-8 lesson-main">
    <?php if ($selected): ?><div class="lesson-player">
        <?php
        $videoUrl = (string) ($selected['video_url'] ?? '');
        $videoHost = strtolower((string) parse_url($videoUrl, PHP_URL_HOST));
        $isYoutubeEmbed = in_array($videoHost, ['youtube.com', 'www.youtube.com', 'www.youtube-nocookie.com'], true)
            && str_starts_with((string) parse_url($videoUrl, PHP_URL_PATH), '/embed/');
        $isVimeo = in_array($videoHost, ['player.vimeo.com'], true)
            && preg_match('#^/video/[0-9]+$#', (string) parse_url($videoUrl, PHP_URL_PATH));
        $isDirectVideo = filter_var($videoUrl, FILTER_VALIDATE_URL)
            && parse_url($videoUrl, PHP_URL_SCHEME) === 'https'
            && preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', $videoUrl);
        ?>
        <?php if (($isYoutubeEmbed || $isVimeo) && parse_url($videoUrl, PHP_URL_SCHEME) === 'https'): ?><div class="video-frame"><iframe src="<?= e($videoUrl) ?>" title="<?= e($selected['title']) ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>
        <?php elseif ($isDirectVideo): ?><video class="direct-video" controls preload="metadata"><source src="<?= e($videoUrl) ?>">Your browser does not support HTML video.</video>
        <?php else: ?><div class="video-placeholder"><div class="play-graphic"><i class="bi bi-play-fill"></i></div><span>YOUR NEXT LESSON</span><h2><?= e($selected['title']) ?></h2><p>Lesson video coming soon</p></div><?php endif; ?>
    </div><div class="lesson-detail"><div class="lesson-detail-heading"><div><span class="section-kicker">LESSON</span><h1><?= e($selected['title']) ?></h1></div><span class="lesson-duration-badge"><i class="bi bi-clock"></i> <?= (int) $selected['duration_minutes'] ?> min</span></div><p><?= nl2br(e($selected['description'] ?: 'Take your time with this lesson. Progress is saved as you go.')) ?></p><div class="lesson-actions"><form method="post"><?= csrf_field() ?><input type="hidden" name="lesson_id" value="<?= (int) $selected['id'] ?>"><button class="btn <?= $selected['completed_at'] ? 'btn-outline-primary' : 'btn-primary' ?> rounded-pill px-4 py-3" type="submit"><?= $selected['completed_at'] ? 'Mark complete again' : 'Mark lesson complete' ?> <i class="bi bi-check2 ms-2"></i></button></form><a class="text-link" href="<?= e(url('quiz.php?course_id=' . $courseId)) ?>">Take course quiz <i class="bi bi-arrow-right"></i></a></div></div>
    <?php else: ?><div class="empty-state"><i class="bi bi-play-circle"></i><h2>No lessons yet</h2><p>Your instructor is preparing course content.</p></div><?php endif; ?>
</div></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
