<?php
require_once __DIR__ . '/includes/functions.php';
$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$tokenValid = preg_match('/^[a-f0-9]{64}$/', $token) === 1;
$error = '';
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = (string) ($_POST['password'] ?? '');
    if (!$tokenValid || strlen($password) < 8 || strlen($password) > 72) {
        $error = 'The reset link may have expired, or your password does not meet the requirements.';
    } else {
        $pdo = db();
        $find = $pdo->prepare(
            'SELECT user_id FROM password_resets WHERE token_hash = ? AND expires_at > UTC_TIMESTAMP()'
        );
        $find->execute([hash('sha256', $token)]);
        $reset = $find->fetch();
        if (!$reset) {
            $error = 'This reset link is invalid or has expired. Request a new one.';
        } else {
            $pdo->beginTransaction();
            $update = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $update->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);
            $delete = $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?');
            $delete->execute([$reset['user_id']]);
            $pdo->commit();
            $success = true;
        }
    }
}
$pageTitle = 'Choose a new password';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-section"><div class="container"><div class="auth-card standalone-auth"><div class="auth-card-heading"><span class="section-kicker">ALMOST THERE</span><h2>Choose a new password</h2><p>Make it at least 8 characters and easy for you to remember.</p></div><?php if ($success): ?><div class="alert alert-success">Your password has been updated. You can now sign in.</div><a class="btn btn-primary rounded-pill w-100 py-3" href="<?= e(url('login.php')) ?>">Go to sign in</a><?php else: ?><?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><form method="post"><?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>"><div class="mb-3"><label class="form-label" for="password">New password</label><input class="form-control" id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required></div><button class="btn btn-primary w-100 rounded-pill py-3" type="submit">Update password</button></form><?php endif; ?></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
