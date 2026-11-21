<?php
require_once __DIR__ . '/includes/functions.php';

$pdo = db();
$search = trim((string) ($_GET['q'] ?? ''));
$categoryId = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) ?: 0;
$categories = $pdo->query(
    'SELECT c.id, c.name, c.icon, COUNT(co.id) AS course_count
     FROM categories c LEFT JOIN courses co ON co.category_id = c.id AND co.status = "published"
     GROUP BY c.id ORDER BY c.name LIMIT 8'
)->fetchAll();
$sql = 'SELECT co.*, u.name AS instructor_name, c.name AS category_name,
        (SELECT COUNT(*) FROM enrollments en WHERE en.course_id = co.id) AS student_count
        FROM courses co INNER JOIN users u ON u.id = co.instructor_id
        INNER JOIN categories c ON c.id = co.category_id WHERE co.status = "published"';
$params = [];
if ($search !== '') {
    $sql .= ' AND (co.title LIKE ? OR co.short_description LIKE ? OR u.name LIKE ?)';
    $term = '%' . $search . '%';
    array_push($params, $term, $term, $term);
}
if ($categoryId > 0) {
    $sql .= ' AND co.category_id = ?';
    $params[] = $categoryId;
}
$sql .= ' ORDER BY co.rating DESC, co.created_at DESC LIMIT 6';
$query = $pdo->prepare($sql);
$query->execute($params);
$courses = $query->fetchAll();
$pageTitle = 'Learn without limits';
$activePage = 'home';
require __DIR__ . '/includes/header.php';
?>
<section class="hero-section">
    <div class="container hero-wrap">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 hero-copy">
                <div class="eyebrow"><span class="eyebrow-dot"></span> Your future is a skill away</div>
                <h1>Make room for<br>your <span>next big thing.</span></h1>
                <p>Learn from people who do the work. Find practical courses that help you turn what-if into what’s next.</p>
                <form action="<?= e(url('courses.php')) ?>" method="get" class="hero-search">
                    <i class="bi bi-search"></i>
                    <input type="search" name="q" value="<?= e($search) ?>" placeholder="What do you want to learn?" aria-label="Search courses">
                    <button class="btn btn-primary rounded-pill px-4" type="submit">Explore</button>
                </form>
                <div class="hero-social-proof"><div class="avatar-stack"><span>J</span><span>M</span><span>A</span><span><i class="bi bi-plus"></i></span></div><div><strong>Join 25,000+ learners</strong><small>Growing their skills every day</small></div></div>
            </div>
            <div class="col-lg-6 hero-art-wrap">
                <div class="hero-art">
                    <div class="hero-orbit orbit-one"></div><div class="hero-orbit orbit-two"></div>
                    <div class="floating-note note-top"><span class="note-icon lavender"><i class="bi bi-lightning-charge-fill"></i></span><span><strong>Keep it up!</strong><small>You're on a 5-day streak</small></span></div>
                    <div class="learning-card">
                        <div class="learning-card-top"><span class="course-label">TODAY'S PICK</span><span class="bookmark-icon"><i class="bi bi-bookmark"></i></span></div>
                        <div class="learning-illustration"><div class="illustration-window"><span></span><span></span><span></span><div class="code-line long"></div><div class="code-line"></div><div class="code-line short"></div><i class="bi bi-braces-asterisk"></i></div></div>
                        <div class="learning-card-content"><span class="mini-tag">DESIGN & TECH</span><h3>Creative coding<br>for curious minds</h3><div class="card-instructor"><span class="instructor-avatar">AL</span><span>Alex Morgan <small>Product designer</small></span><i class="bi bi-arrow-up-right ms-auto"></i></div></div>
                    </div>
                    <div class="floating-note note-bottom"><span class="note-icon peach"><i class="bi bi-award-fill"></i></span><span><strong>Skill unlocked</strong><small>Creative problem solving</small></span></div>
                    <div class="hero-sparkle sparkle-one">✳</div><div class="hero-sparkle sparkle-two">✦</div>
                </div>
            </div>
        </div>
        <div class="hero-trust"><span>Learn at your own pace</span><span>•</span><span>Courses built for real life</span><span>•</span><span>Certificates that count</span></div>
    </div>
</section>
<section class="section-space category-section">
    <div class="container">
        <div class="section-heading"><div><span class="section-kicker">FIND YOUR THING</span><h2>Explore by category</h2></div><a class="text-link" href="<?= e(url('courses.php')) ?>">All categories <i class="bi bi-arrow-up-right"></i></a></div>
        <div class="row g-3">
            <?php foreach ($categories as $index => $category): ?>
                <div class="col-6 col-md-4 col-lg-3"><a class="category-tile" href="<?= e(url('courses.php?category=' . (int) $category['id'])) ?>"><span class="category-icon category-tone-<?= $index % 4 ?>"><i class="bi <?= e($category['icon'] ?: 'bi-book') ?>"></i></span><span><strong><?= e($category['name']) ?></strong><small><?= (int) $category['course_count'] ?> courses</small></span><i class="bi bi-arrow-up-right category-arrow"></i></a></div>
            <?php endforeach; ?>
            <?php if (!$categories): ?><div class="col-12"><p class="text-secondary">Categories are being added. Check back soon.</p></div><?php endif; ?>
        </div>
    </div>
</section>
<section class="section-space courses-section">
    <div class="container">
        <div class="section-heading"><div><span class="section-kicker">MADE FOR YOUR MOMENT</span><h2><?= $search !== '' ? 'Search results' : 'Popular right now' ?></h2><p>Thoughtful courses, taught by people who love what they do.</p></div><a class="text-link" href="<?= e(url('courses.php')) ?>">Browse all courses <i class="bi bi-arrow-up-right"></i></a></div>
        <?php if ($courses): ?><div class="row g-4"><?php foreach ($courses as $course): ?>
            <div class="col-md-6 col-lg-4"><?php require __DIR__ . '/includes/course-card.php'; ?></div>
        <?php endforeach; ?></div>
        <?php else: ?><div class="empty-state"><i class="bi bi-compass"></i><h3>No courses just yet</h3><p>Try a different search, or explore all available courses.</p><a class="btn btn-primary rounded-pill px-4" href="<?= e(url('courses.php')) ?>">Browse courses</a></div><?php endif; ?>
    </div>
</section>
<section class="section-space how-section"><div class="container"><div class="row align-items-center g-5"><div class="col-lg-5"><span class="section-kicker">LEARNING, REIMAGINED</span><h2 class="display-heading">A little progress<br>goes a <span>long way.</span></h2><p class="muted-copy">Learning should fit into your life, not the other way around. Pick a course, set your pace, and celebrate every milestone.</p><a class="btn btn-primary rounded-pill px-4 py-3 mt-2" href="<?= e(url(current_user() ? 'dashboard.php' : 'register.php')) ?>">Start learning today <i class="bi bi-arrow-right ms-2"></i></a></div><div class="col-lg-7"><div class="journey-card"><div class="journey-header"><div><span class="section-kicker">YOUR LEARNING JOURNEY</span><h3>A new skill, one step at a time</h3></div><span class="journey-badge"><i class="bi bi-stars"></i> Your pace, always</span></div><div class="journey-steps"><div class="journey-step"><span class="journey-number">01</span><div><h4>Find your next thing</h4><p>Choose a course that sparks your curiosity.</p></div><span class="journey-check"><i class="bi bi-check-lg"></i></span></div><div class="journey-step"><span class="journey-number">02</span><div><h4>Learn by doing</h4><p>Build useful skills with lessons made for real life.</p></div><span class="journey-check"><i class="bi bi-play-fill"></i></span></div><div class="journey-step"><span class="journey-number">03</span><div><h4>Celebrate your progress</h4><p>Show off what you’ve learned with a certificate.</p></div><span class="journey-check"><i class="bi bi-award"></i></span></div></div></div></div></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
