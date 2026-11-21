<?php
require_once __DIR__ . '/includes/functions.php';
if (current_user()) {
    redirect('dashboard.php');
}

$errors = [];
$name = '';
$email = '';
$mobile = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $mobile = trim((string) ($_POST['mobile'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
        $errors[] = 'Please enter your name (2–120 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        $errors[] = 'Enter a valid email address.';
    }
    if (!preg_match('/^[+0-9().\-\s]{7,24}$/', $mobile)) {
        $errors[] = 'Enter a valid mobile number.';
    }
    if (strlen($password) < 8 || strlen($password) > 72) {
        $errors[] = 'Your password must be between 8 and 72 characters.';
    }
    if (!$errors) {
        try {
            $insert = db()->prepare(
                'INSERT INTO users (name, email, mobile, password_hash, role) VALUES (?, ?, ?, ?, "student")'
            );
            $insert->execute([$name, $email, $mobile, password_hash($password, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int) db()->lastInsertId(),
                'name' => $name,
                'email' => $email,
                'role' => 'student',
            ];
            flash('success', 'Welcome to LearnHub! Your learning journey starts here.');
            redirect('dashboard.php');
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $errors[] = 'An account with that email already exists. Try signing in instead.';
            } else {
                throw $exception;
            }
        }
    }
}
$pageTitle = 'Create your account';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-section"><div class="container"><div class="auth-layout"><div class="auth-aside"><span class="section-kicker">YOUR NEXT CHAPTER STARTS HERE</span><h1>Curiosity looks<br>good on you.</h1><p>One account opens the door to skills, ideas, and opportunities that move you forward.</p><div class="auth-benefits"><span><i class="bi bi-check-circle-fill"></i> Learn at your own pace</span><span><i class="bi bi-check-circle-fill"></i> Keep your progress in one place</span><span><i class="bi bi-check-circle-fill"></i> Celebrate every milestone</span></div><div class="auth-doodle"><i class="bi bi-stars"></i></div></div><div class="auth-card"><div class="auth-card-heading"><span class="section-kicker">GET STARTED FOR FREE</span><h2>Create your account</h2><p>Join a community of curious learners.</p></div>
    <?php if ($errors): ?><div class="alert alert-danger" role="alert"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" novalidate><?= csrf_field() ?><div class="mb-3"><label class="form-label" for="name">Your name</label><input class="form-control" id="name" name="name" value="<?= e($name) ?>" autocomplete="name" required minlength="2" maxlength="120" placeholder="e.g. Jamie Rivera"></div><div class="mb-3"><label class="form-label" for="email">Email address</label><input class="form-control" id="email" name="email" type="email" value="<?= e($email) ?>" autocomplete="email" required placeholder="you@example.com"></div><div class="mb-3"><label class="form-label" for="mobile">Mobile number</label><input class="form-control" id="mobile" name="mobile" type="tel" value="<?= e($mobile) ?>" autocomplete="tel" required placeholder="+1 (555) 000-0000"></div><div class="mb-3"><label class="form-label" for="password">Create a password</label><div class="input-group"><input class="form-control" id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required placeholder="At least 8 characters"><button class="btn btn-outline-secondary password-toggle" type="button" aria-label="Show password"><i class="bi bi-eye"></i></button></div><div class="form-text">Use at least 8 characters.</div></div><button class="btn btn-primary w-100 py-3 rounded-pill mt-2" type="submit">Create my account <i class="bi bi-arrow-right ms-2"></i></button><p class="auth-legal">By joining, you agree to our community guidelines and terms.</p><p class="auth-switch">Already learning with us? <a href="<?= e(url('login.php')) ?>">Sign in</a></p></form></div></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
