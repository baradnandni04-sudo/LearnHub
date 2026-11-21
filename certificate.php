<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_role('student');
$code = strtoupper(trim((string) ($_GET['code'] ?? '')));
$query = db()->prepare(
    'SELECT ce.certificate_code, ce.issued_at, co.title, u.name AS student_name
     FROM certificates ce INNER JOIN courses co ON co.id = ce.course_id
     INNER JOIN users u ON u.id = ce.student_id
     INNER JOIN enrollments en ON en.course_id = ce.course_id AND en.student_id = ce.student_id
     WHERE ce.certificate_code = ? AND ce.student_id = ? AND en.progress_percent = 100'
);
$query->execute([$code, $user['id']]);
$certificate = $query->fetch();
if (!$certificate) {
    http_response_code(404);
    $pageTitle = 'Certificate not found';
    require __DIR__ . '/includes/header.php';
    echo '<section class="section-space"><div class="container empty-state"><i class="bi bi-award"></i><h1>Certificate not found</h1><a class="btn btn-primary rounded-pill" href="' . e(url('certificates.php')) . '">Back to certificates</a></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
$pageTitle = 'Certificate · ' . $certificate['title'];
require __DIR__ . '/includes/header.php';
?>
<section class="certificate-page"><div class="container"><div class="certificate-actions"><a class="back-link" href="<?= e(url('certificates.php')) ?>"><i class="bi bi-arrow-left"></i> My certificates</a><button class="btn btn-outline-primary rounded-pill px-4" type="button" onclick="window.print()"><i class="bi bi-printer me-2"></i>Print certificate</button></div><article class="certificate-paper"><div class="certificate-inner"><span class="certificate-brand"><i class="bi bi-layers-fill"></i> learnhub</span><span class="certificate-decor">✦</span><span class="section-kicker">CERTIFICATE OF COMPLETION</span><h1>Knowledge in action.</h1><p class="certificate-presented">This certificate is proudly presented to</p><strong class="certificate-recipient"><?= e($certificate['student_name']) ?></strong><p class="certificate-description">for successfully completing the course</p><h2><?= e($certificate['title']) ?></h2><div class="certificate-footer"><div><strong><?= e($certificate['student_name']) ?></strong><span>LearnHub learner</span></div><div class="certificate-seal"><i class="bi bi-award-fill"></i></div><div><strong><?= e(date('F j, Y', strtotime($certificate['issued_at']))) ?></strong><span>Date of completion</span></div></div><span class="certificate-verify">Certificate ID: <?= e($certificate['certificate_code']) ?></span></div></article></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
