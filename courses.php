<?php
require_once __DIR__ . '/includes/functions.php';

$pdo = db();
$search = trim((string) ($_GET['q'] ?? ''));
$categoryId = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) ?: 0;
$level = (string) ($_GET['level'] ?? '');
$allowedLevels = ['beginner', 'intermediate', 'advanced', 'all'];
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
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
if (in_array($level, $allowedLevels, true) && $level !== 'all') {
    $sql .= ' AND co.level = ?';
    $params[] = $level;
}
$sql .= ' ORDER BY co.rating DESC, co.created_at DESC';
$query = $pdo->prepare($sql);
$query->execute($params);
$courses = $query->fetchAll();
$pageTitle = 'Explore courses';
$activePage = 'courses';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container"><span class="section-kicker">A WORLD OF POSSIBILITY</span><h1>Find your next <span>favorite thing.</span></h1><p>Explore practical, inspiring courses built to help you keep growing.</p><form class="catalog-search" method="get" action="<?= e(url('courses.php')) ?>"><i class="bi bi-search"></i><input type="search" name="q" value="<?= e($search) ?>" placeholder="Search courses, skills, or instructors" aria-label="Search courses"><button type="submit" class="btn btn-primary rounded-pill px-4">Search</button></form></div></section>
<section class="section-space catalog-section"><div class="container"><form class="filter-bar" method="get" action="<?= e(url('courses.php')) ?>"><input type="hidden" name="q" value="<?= e($search) ?>"><div><label class="form-label" for="category">Category</label><select class="form-select" id="category" name="category"><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></div><div><label class="form-label" for="level">Course level</label><select class="form-select" id="level" name="level"><option value="">All levels</option><?php foreach (['beginner', 'intermediate', 'advanced'] as $option): ?><option value="<?= e($option) ?>" <?= $level === $option ? 'selected' : '' ?>><?= e(ucfirst($option)) ?></option><?php endforeach; ?></select></div><button class="btn btn-outline-primary rounded-pill align-self-end px-4" type="submit"><i class="bi bi-sliders me-2"></i>Apply filters</button><a class="text-link align-self-center" href="<?= e(url('courses.php')) ?>">Clear</a></form>
    <div class="catalog-result-heading"><p><strong><?= count($courses) ?></strong> course<?= count($courses) === 1 ? '' : 's' ?> to explore</p></div>
    <?php if ($courses): ?><div class="row g-4"><?php foreach ($courses as $course): ?><div class="col-md-6 col-lg-4"><?php require __DIR__ . '/includes/course-card.php'; ?></div><?php endforeach; ?></div>
    <?php else: ?><div class="empty-state"><i class="bi bi-search"></i><h3>No courses found</h3><p>Try changing your search or clearing the filters.</p><a class="btn btn-outline-primary rounded-pill px-4" href="<?= e(url('courses.php')) ?>">Clear filters</a></div><?php endif; ?>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
