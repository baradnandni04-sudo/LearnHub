<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_role('student');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('courses.php');
}
verify_csrf();
$courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT) ?: 0;
$pdo = db();
$course = $pdo->prepare('SELECT id, price FROM courses WHERE id = ? AND status = "published"');
$course->execute([$courseId]);
$courseData = $course->fetch();
if (!$courseData) {
    flash('danger', 'That course is not available for enrollment.');
    redirect('courses.php');
}
if ((float) $courseData['price'] > 0) {
    flash('warning', 'Paid checkout is not configured yet. Contact us to ask about this course.');
    redirect('course.php?id=' . $courseId);
}
$enroll = $pdo->prepare('INSERT IGNORE INTO enrollments (student_id, course_id) VALUES (?, ?)');
$enroll->execute([$user['id'], $courseId]);
flash('success', $enroll->rowCount() ? 'You’re enrolled! Let’s get learning.' : 'You’re already enrolled in this course.');
redirect('learn.php?course_id=' . $courseId);
