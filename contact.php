<?php
require_once __DIR__ . '/includes/functions.php';
$user = current_user();
$errors = [];
$name = (string) ($user['name'] ?? '');
$email = (string) ($user['email'] ?? '');
$subject = '';
$message = '';
$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
        $errors[] = 'Please enter your name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        $errors[] = 'Enter a valid email address.';
    }
    if (mb_strlen($subject) < 3 || mb_strlen($subject) > 180) {
        $errors[] = 'Enter a subject between 3 and 180 characters.';
    }
    if (mb_strlen($message) < 10 || mb_strlen($message) > 5000) {
        $errors[] = 'Your message must be between 10 and 5,000 characters.';
    }
    if (!$errors) {
        $insert = db()->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)');
        $insert->execute([$name, $email, $subject, $message]);
        $sent = true;
        $name = $user['name'] ?? '';
        $email = $user['email'] ?? '';
    }
}
$pageTitle = 'Contact us';
$activePage = 'contact';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero compact-page-hero"><div class="container"><span class="section-kicker">WE’RE ALL EARS</span><h1>Let’s talk about <span>what’s next.</span></h1><p>Questions, feedback, or a bright idea? We’d love to hear from you.</p></div></section><section class="contact-section"><div class="container"><div class="contact-layout"><div class="contact-aside"><div class="contact-note"><span class="note-icon lavender"><i class="bi bi-chat-heart"></i></span><h2>Here to help.</h2><p>Send us a note and our team will be in touch. Every message goes to a real person who cares.</p><span class="contact-reply"><i class="bi bi-clock"></i> We usually reply within two business days</span></div></div><div class="contact-form-card"><?php if ($sent): ?><div class="alert alert-success">Thanks for reaching out. Your message has been received!</div><?php endif; ?><?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><form method="post"><?= csrf_field() ?><div class="row g-3"><div class="col-md-6"><label class="form-label" for="name">Your name</label><input class="form-control" id="name" name="name" value="<?= e($name) ?>" required maxlength="120"></div><div class="col-md-6"><label class="form-label" for="email">Email address</label><input class="form-control" id="email" name="email" type="email" value="<?= e($email) ?>" required></div><div class="col-12"><label class="form-label" for="subject">Subject</label><input class="form-control" id="subject" name="subject" value="<?= e($subject) ?>" required maxlength="180" placeholder="How can we help?"></div><div class="col-12"><label class="form-label" for="message">Your message</label><textarea class="form-control" id="message" name="message" rows="5" required minlength="10" maxlength="5000" placeholder="Tell us a little more..."><?= e($message) ?></textarea></div><div class="col-12"><button class="btn btn-primary rounded-pill px-4 py-3" type="submit">Send message <i class="bi bi-arrow-right ms-2"></i></button></div></div></form></div></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
