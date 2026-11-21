<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_role('student');
$courseId = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT) ?: 0;
$pdo = db();
$access = $pdo->prepare(
    'SELECT co.title FROM courses co INNER JOIN enrollments en ON en.course_id = co.id
     WHERE co.id = ? AND en.student_id = ?'
);
$access->execute([$courseId, $user['id']]);
$course = $access->fetch();
if (!$course) {
    flash('warning', 'Enroll in the course before taking its quiz.');
    redirect('courses.php');
}
$questionsQuery = $pdo->prepare(
    'SELECT id, question_text, option_a, option_b, option_c, option_d
     FROM quiz_questions WHERE course_id = ? ORDER BY sort_order, id'
);
$questionsQuery->execute([$courseId]);
$questions = $questionsQuery->fetchAll();
$error = '';
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (!$questions) {
        $error = 'There are no quiz questions available yet.';
    } else {
        $answers = is_array($_POST['answers'] ?? null) ? $_POST['answers'] : [];
        $correctQuery = $pdo->prepare('SELECT id, correct_option FROM quiz_questions WHERE course_id = ?');
        $correctQuery->execute([$courseId]);
        $correctAnswers = [];
        foreach ($correctQuery->fetchAll() as $row) {
            $correctAnswers[(int) $row['id']] = $row['correct_option'];
        }
        $correct = 0;
        foreach ($correctAnswers as $questionId => $correctOption) {
            $answer = $answers[(string) $questionId] ?? '';
            if (is_string($answer) && hash_equals($correctOption, $answer)) {
                $correct++;
            }
        }
        $total = count($correctAnswers);
        $score = $total ? (int) round(($correct / $total) * 100) : 0;
        $passed = $score >= 70;
        $save = $pdo->prepare('INSERT INTO quiz_attempts (student_id, course_id, score_percent, passed) VALUES (?, ?, ?, ?)');
        $save->execute([$user['id'], $courseId, $score, (int) $passed]);
        $result = ['score' => $score, 'correct' => $correct, 'total' => $total, 'passed' => $passed];
    }
}
$pageTitle = 'Course quiz';
require __DIR__ . '/includes/header.php';
?>
<section class="section-space quiz-section"><div class="container quiz-container"><a class="back-link" href="<?= e(url('learn.php?course_id=' . $courseId)) ?>"><i class="bi bi-arrow-left"></i> Back to lessons</a><div class="quiz-card"><div class="quiz-heading"><span class="section-kicker">PUT YOUR KNOWLEDGE TO WORK</span><h1><?= e($course['title']) ?> quiz</h1><p>Choose the best answer for each question. You need 70% to pass.</p></div>
    <?php if ($result): ?><div class="quiz-result <?= $result['passed'] ? 'passed' : 'not-passed' ?>"><span class="quiz-result-icon"><i class="bi <?= $result['passed'] ? 'bi-trophy-fill' : 'bi-arrow-repeat' ?>"></i></span><span class="section-kicker"><?= $result['passed'] ? 'NICELY DONE' : 'KEEP PRACTICING' ?></span><strong><?= $result['score'] ?>%</strong><p>You got <?= $result['correct'] ?> out of <?= $result['total'] ?> questions right. <?= $result['passed'] ? 'You passed this quiz!' : 'Review your lessons and give it another try.' ?></p><a class="btn btn-primary rounded-pill px-4" href="<?= e(url('learn.php?course_id=' . $courseId)) ?>">Back to learning</a></div>
    <?php else: ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <?php if (!$questions): ?><div class="empty-state"><i class="bi bi-patch-question"></i><h2>Quiz coming soon</h2><p>Your instructor hasn’t added questions yet.</p></div>
        <?php else: ?><form method="post" class="quiz-form"><?= csrf_field() ?><?php foreach ($questions as $index => $question): ?><fieldset class="quiz-question"><legend><span>QUESTION <?= $index + 1 ?></span><?= e($question['question_text']) ?></legend><?php foreach (['a', 'b', 'c', 'd'] as $option): ?><label class="quiz-option"><input type="radio" name="answers[<?= (int) $question['id'] ?>]" value="<?= $option ?>" required><span class="option-letter"><?= strtoupper($option) ?></span><span><?= e($question['option_' . $option]) ?></span></label><?php endforeach; ?></fieldset><?php endforeach; ?><button class="btn btn-primary rounded-pill px-5 py-3" type="submit">Submit my answers <i class="bi bi-arrow-right ms-2"></i></button></form><?php endif; ?>
    <?php endif; ?></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
