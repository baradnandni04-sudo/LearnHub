<article class="course-card">
    <a class="course-card-image" href="<?= e(url('course.php?id=' . (int) $course['id'])) ?>">
        <img src="<?= e(course_image($course['image_url'] ?? null)) ?>" alt="<?= e($course['title']) ?>" loading="lazy">
        <span class="course-category-pill"><?= e($course['category_name'] ?? 'Featured') ?></span>
        <span class="course-price"><?= e(format_price($course['price'] ?? 0)) ?></span>
    </a>
    <div class="course-card-body">
        <div class="course-meta"><span><i class="bi bi-star-fill"></i> <?= number_format((float) ($course['rating'] ?? 0), 1) ?></span><span><?= number_format((int) ($course['student_count'] ?? 0)) ?> learners</span></div>
        <h3><a href="<?= e(url('course.php?id=' . (int) $course['id'])) ?>"><?= e($course['title']) ?></a></h3>
        <p><?= e($course['short_description']) ?></p>
        <div class="course-card-footer"><span class="card-instructor"><span class="instructor-avatar"><?= e(initials($course['instructor_name'] ?? 'LearnHub')) ?></span><?= e($course['instructor_name'] ?? 'LearnHub instructor') ?></span><span><?= e(ucfirst($course['level'] ?? 'All levels')) ?></span></div>
    </div>
</article>
