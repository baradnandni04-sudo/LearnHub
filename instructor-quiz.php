<?php
require_once __DIR__ . '/includes/functions.php';
$instructor = require_role('instructor');
$pdo = db();
$courseId = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT)
    ?: (filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT) ?: 0);
$error = '';

if ($courseId > 0) {
    $ownedCourse = $pdo->prepare('SELECT id, title FROM courses WHERE id = ? AND instructor_id = ?');
    $ownedCourse->execute([$courseId, $instructor['id']]);
    $course = $ownedCourse->fetch();
    if (!$course) {
        http_response_code(404);
        $pageTitle = 'Course not found';
        require __DIR__ . '/includes/header.php';
        echo '<section class="section-space"><div class="container empty-state"><i class="bi bi-patch-question"></i><h1>Course not found</h1><a class="btn btn-primary rounded-pill" href="' . e(url('instructor-quiz.php')) . '">Back to quiz builder</a></div></section>';
        require __DIR__ . '/includes/footer.php';
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'delete_question') {
            $questionId = filter_input(INPUT_POST, 'question_id', FILTER_VALIDATE_INT) ?: 0;
            $delete = $pdo->prepare('DELETE FROM quiz_questions WHERE id = ? AND course_id = ?');
            $delete->execute([$questionId, $courseId]);
            if ($delete->rowCount() > 0) {
                flash('success', 'Quiz question removed.');
                redirect('instructor-quiz.php?course_id=' . $courseId);
            }
            $error = 'That question could not be found in this course.';
        } elseif ($action === 'add_question') {
            $question = trim((string) ($_POST['question_text'] ?? ''));
            $options = [];
            foreach (['a', 'b', 'c', 'd'] as $option) {
                $options[$option] = trim((string) ($_POST['option_' . $option] ?? ''));
            }
            $correctOption = (string) ($_POST['correct_option'] ?? '');
            $optionsValid = true;
            foreach ($options as $optionText) {
                if ($optionText === '' || mb_strlen($optionText) > 500) {
                    $optionsValid = false;
                    break;
                }
            }
            if (mb_strlen($question) < 10 || mb_strlen($question) > 1000
                || !$optionsValid || !in_array($correctOption, ['a', 'b', 'c', 'd'], true)) {
                $error = 'Enter a question, four answers, and select the correct answer.';
            } else {
                $order = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM quiz_questions WHERE course_id = ?');
                $order->execute([$courseId]);
                $insert = $pdo->prepare(
                    'INSERT INTO quiz_questions
                        (course_id, question_text, option_a, option_b, option_c, option_d, correct_option, sort_order)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $insert->execute([
                    $courseId,
                    $question,
                    $options['a'],
                    $options['b'],
                    $options['c'],
                    $options['d'],
                    $correctOption,
                    $order->fetchColumn(),
                ]);
                flash('success', 'Quiz question added to your course.');
                redirect('instructor-quiz.php?course_id=' . $courseId);
            }
        } else {
            $error = 'That action is not available.';
        }
    }

    $questionQuery = $pdo->prepare(
        'SELECT id, question_text, option_a, option_b, option_c, option_d, correct_option
         FROM quiz_questions WHERE course_id = ? ORDER BY sort_order, id'
    );
    $questionQuery->execute([$courseId]);
    $questions = $questionQuery->fetchAll();
} else {
    $courseQuery = $pdo->prepare(
        'SELECT co.id, co.title, co.status,
                (SELECT COUNT(*) FROM quiz_questions q WHERE q.course_id = co.id) AS question_count
         FROM courses co WHERE co.instructor_id = ? ORDER BY co.created_at DESC'
    );
    $courseQuery->execute([$instructor['id']]);
    $courses = $courseQuery->fetchAll();
}

$pageTitle = $courseId > 0 ? 'Quiz builder · ' . $course['title'] : 'Quiz builder';
require __DIR__ . '/includes/header.php';
?>
<section class="dashboard-section"><div class="container">
    <?php if ($courseId === 0): ?>
        <div class="dashboard-welcome"><div><span class="section-kicker">ASSESSMENT BUILDER</span><h1>Your course quizzes</h1><p>Create multiple-choice quizzes to help learners check their progress.</p></div><a class="btn btn-outline-primary rounded-pill px-4 py-3" href="<?= e(url('instructor.php')) ?>"><i class="bi bi-arrow-left me-2"></i>Teaching space</a></div>
        <?php if ($courses): ?><div class="row g-3"><?php foreach ($courses as $item): ?><div class="col-md-6 col-lg-4"><article class="instructor-course-card h-100"><span class="role-badge <?= e($item['status']) ?>"><?= e(ucfirst($item['status'])) ?></span><h3 class="mt-3"><?= e($item['title']) ?></h3><p class="muted-copy"><?= (int) $item['question_count'] ?> quiz questions</p><a class="btn btn-outline-primary rounded-pill btn-sm" href="<?= e(url('instructor-quiz.php?course_id=' . (int) $item['id'])) ?>">Manage quiz <i class="bi bi-arrow-right ms-1"></i></a></article></div><?php endforeach; ?></div><?php else: ?><div class="empty-state"><i class="bi bi-journal-plus"></i><h2>Create a course to get started</h2><a class="btn btn-primary rounded-pill" href="<?= e(url('instructor.php')) ?>">Create course</a></div><?php endif; ?>
    <?php else: ?>
        <div class="dashboard-welcome"><div><a class="back-link" href="<?= e(url('instructor-quiz.php')) ?>"><i class="bi bi-arrow-left"></i> All course quizzes</a><h1 class="mt-2"><?= e($course['title']) ?> quiz</h1><p>Questions are scored automatically; learners need 70% to pass.</p></div><a class="btn btn-outline-primary rounded-pill px-4 py-3" href="<?= e(url('instructor.php')) ?>">Teaching space</a></div>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <div class="row g-4 align-items-start"><div class="col-lg-5"><div class="admin-panel"><div class="admin-panel-heading"><div><span class="section-kicker">NEW QUESTION</span><h2>Add to this quiz</h2></div></div><form method="post" class="admin-create-form"><?= csrf_field() ?><input type="hidden" name="course_id" value="<?= $courseId ?>"><input type="hidden" name="action" value="add_question"><div class="mb-3"><label class="form-label" for="question">Question</label><textarea class="form-control" id="question" name="question_text" rows="3" required minlength="10" maxlength="1000"></textarea></div><div class="row g-2"><?php foreach (['a', 'b', 'c', 'd'] as $option): ?><div class="col-12"><label class="form-label" for="option<?= strtoupper($option) ?>">Answer <?= strtoupper($option) ?></label><input class="form-control" id="option<?= strtoupper($option) ?>" name="option_<?= $option ?>" required maxlength="500"></div><?php endforeach; ?></div><div class="mt-3"><label class="form-label" for="correctOption">Correct answer</label><select class="form-select" id="correctOption" name="correct_option"><?php foreach (['a', 'b', 'c', 'd'] as $option): ?><option value="<?= $option ?>">Answer <?= strtoupper($option) ?></option><?php endforeach; ?></select></div><button class="btn btn-primary rounded-pill px-4 mt-3" type="submit">Save question</button></form></div></div><div class="col-lg-7"><div class="admin-panel"><div class="admin-panel-heading"><div><span class="section-kicker">QUESTION BANK</span><h2><?= count($questions) ?> questions</h2></div></div><?php if ($questions): ?><div class="quiz-question-bank"><?php foreach ($questions as $index => $question): ?><article class="quiz-bank-item"><div><span class="section-kicker">QUESTION <?= $index + 1 ?></span><h3><?= e($question['question_text']) ?></h3><ol type="A"><?php foreach (['a', 'b', 'c', 'd'] as $option): ?><li class="<?= $question['correct_option'] === $option ? 'correct-answer' : '' ?>"><?= e($question['option_' . $option]) ?><?= $question['correct_option'] === $option ? ' · Correct answer' : '' ?></li><?php endforeach; ?></ol></div><form method="post" onsubmit="return confirm('Remove this question?');"><?= csrf_field() ?><input type="hidden" name="course_id" value="<?= $courseId ?>"><input type="hidden" name="question_id" value="<?= (int) $question['id'] ?>"><input type="hidden" name="action" value="delete_question"><button class="btn btn-outline-danger btn-sm rounded-pill" type="submit" aria-label="Remove question"><i class="bi bi-trash"></i></button></form></article><?php endforeach; ?></div><?php else: ?><div class="empty-state"><i class="bi bi-patch-question"></i><h3>Your quiz starts with a good question</h3><p>Add your first multiple-choice question to this course.</p></div><?php endif; ?></div></div></div>
    <?php endif; ?>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
