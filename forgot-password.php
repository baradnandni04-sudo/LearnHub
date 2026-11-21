<?php
require_once __DIR__ . '/includes/functions.php';
$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $query = db()->prepare('SELECT id, email FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
        $query->execute([$email]);
        $account = $query->fetch();
        if ($account) {
            $token = bin2hex(random_bytes(32));
            $save = db()->prepare(
                'INSERT INTO password_resets (user_id, token_hash, expires_at)
                 VALUES (?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 HOUR))
                 ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash), expires_at = VALUES(expires_at), created_at = UTC_TIMESTAMP()'
            );
            $save->execute([$account['id'], hash('sha256', $token)]);
            $resetLink = url('reset-password.php?token=' . rawurlencode($token));
            $from = getenv('LEARNHUB_MAIL_FROM') ?: 'no-reply@learnhub.local';
            $headers = "From: {$from}\r\nContent-Type: text/plain; charset=UTF-8";
            if (!mail($account['email'], 'Reset your LearnHub password', "Use this link within one hour to reset your password:\n\n{$resetLink}\n")) {
                error_log('LearnHub password reset email could not be sent.');
            }
        }
    }
    $sent = true;
}
$pageTitle = 'Reset your password';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-section"><div class="container"><div class="auth-layout compact"><div class="auth-aside"><span class="section-kicker">WE’RE HERE TO HELP</span><h1>A fresh start<br>is just ahead.</h1><p>We’ll help you get back to the learning you love.</p></div><div class="auth-card"><div class="auth-card-heading"><span class="section-kicker">ACCOUNT RECOVERY</span><h2>Forgot your password?</h2><p>Enter your account email and we’ll send you a reset link.</p></div><?php if ($sent): ?><div class="alert alert-info" role="status">If an active account uses that email, a reset link will be sent. If no email arrives, contact the site administrator.</div><?php endif; ?><form method="post"><?= csrf_field() ?><div class="mb-3"><label class="form-label" for="email">Email address</label><input class="form-control" id="email" name="email" type="email" autocomplete="email" required placeholder="you@example.com"></div><button class="btn btn-primary w-100 py-3 rounded-pill" type="submit">Send reset link <i class="bi bi-arrow-right ms-2"></i></button><p class="auth-switch mt-4"><a href="<?= e(url('login.php')) ?>"><i class="bi bi-arrow-left me-1"></i>Back to sign in</a></p></form></div></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
