<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_role('student');
$pdo = db();
$completedQuery = $pdo->prepare(
    'SELECT co.id, co.title, en.updated_at AS completed_at, ce.certificate_code
     FROM enrollments en INNER JOIN courses co ON co.id = en.course_id
     LEFT JOIN certificates ce ON ce.course_id = co.id AND ce.student_id = en.student_id
     WHERE en.student_id = ? AND en.progress_percent = 100 ORDER BY en.updated_at DESC'
);
$completedQuery->execute([$user['id']]);
$completed = $completedQuery->fetchAll();
foreach ($completed as $item) {
    if (!$item['certificate_code']) {
        $code = strtoupper(bin2hex(random_bytes(6)));
        $create = $pdo->prepare(
            'INSERT IGNORE INTO certificates (student_id, course_id, certificate_code)
             VALUES (?, ?, ?)'
        );
        $create->execute([$user['id'], $item['id'], $code]);
    }
}
$completedQuery->execute([$user['id']]);
$completed = $completedQuery->fetchAll();
$pageTitle = 'My certificates';
require __DIR__ . '/includes/header.php';
?>
<section class="dashboard-section"><div class="container"><div class="dashboard-welcome"><div><span class="section-kicker">MILESTONES WORTH CELEBRATING</span><h1>Your certificates</h1><p>You put in the work. Here’s something to show for it.</p></div><a class="btn btn-outline-primary rounded-pill px-4 py-3" href="<?= e(url('dashboard.php')) ?>"><i class="bi bi-arrow-left me-2"></i>My learning</a></div>
    <?php if ($completed): ?><div class="row g-4"><?php foreach ($completed as $certificate): ?><div class="col-md-6 col-lg-4"><div class="certificate-preview"><span class="certificate-star"><i class="bi bi-award-fill"></i></span><span class="section-kicker">CERTIFICATE OF COMPLETION</span><h2><?= e($certificate['title']) ?></h2><p>Presented to</p><strong><?= e($user['name']) ?></strong><span class="certificate-code">ID <?= e($certificate['certificate_code']) ?></span><a class="btn btn-primary rounded-pill px-4 mt-3" href="<?= e(url('certificate.php?code=' . rawurlencode($certificate['certificate_code']))) ?>">View certificate <i class="bi bi-arrow-up-right ms-1"></i></a></div></div><?php endforeach; ?></div>
    <?php else: ?><div class="empty-state"><i class="bi bi-award"></i><h2>Your next milestone is waiting</h2><p>Complete every lesson in a course to earn a certificate.</p><a class="btn btn-primary rounded-pill px-4" href="<?= e(url('dashboard.php')) ?>">Continue learning</a></div><?php endif; ?>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
