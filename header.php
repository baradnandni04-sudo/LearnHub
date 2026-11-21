<?php
$pageTitle = $pageTitle ?? 'Learn without limits';
$activePage = $activePage ?? '';
$navUser = current_user();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f8ff">
    <title><?= e($pageTitle) ?> · LearnHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light site-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand brand-mark" href="<?= e(url('index.php')) ?>"><span class="brand-icon"><i class="bi bi-layers-fill"></i></span>learn<span>hub</span></a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto ms-lg-4">
                <li class="nav-item"><a class="nav-link <?= $activePage === 'courses' ? 'active' : '' ?>" href="<?= e(url('courses.php')) ?>">Explore</a></li>
                <li class="nav-item"><a class="nav-link <?= $activePage === 'about' ? 'active' : '' ?>" href="<?= e(url('about.php')) ?>">About</a></li>
                <li class="nav-item"><a class="nav-link <?= $activePage === 'contact' ? 'active' : '' ?>" href="<?= e(url('contact.php')) ?>">Contact</a></li>
            </ul>
            <div class="d-flex align-items-center gap-2 nav-actions">
                <?php if ($navUser): ?>
                    <?php if ($navUser['role'] === 'admin'): ?><a class="btn btn-link text-decoration-none nav-admin-link" href="<?= e(url('admin.php')) ?>">Admin</a><?php endif; ?>
                    <?php if ($navUser['role'] === 'instructor'): ?><a class="btn btn-link text-decoration-none nav-admin-link" href="<?= e(url('instructor.php')) ?>">Teach</a><a class="btn btn-link text-decoration-none nav-admin-link" href="<?= e(url('instructor-quiz.php')) ?>">Quiz builder</a><?php endif; ?>
                    <a class="btn btn-link text-decoration-none nav-admin-link" href="<?= e(url('dashboard.php')) ?>">My learning</a>
                    <form action="<?= e(url('logout.php')) ?>" method="post" class="m-0"><?= csrf_field() ?><button class="btn btn-outline-primary btn-sm rounded-pill px-3" type="submit">Log out</button></form>
                <?php else: ?>
                    <a class="btn btn-link text-decoration-none nav-admin-link" href="<?= e(url('login.php')) ?>">Log in</a>
                    <a class="btn btn-primary rounded-pill px-4" href="<?= e(url('register.php')) ?>">Join for free <i class="bi bi-arrow-right ms-1"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<main>
    <?php foreach (take_flashes() as $notice): ?>
        <div class="container pt-3"><div class="alert alert-<?= e($notice['type']) ?> alert-dismissible fade show mb-0" role="alert">
            <?= e($notice['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div></div>
    <?php endforeach; ?>
