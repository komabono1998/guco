<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Contact';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $body = trim($_POST['body'] ?? '');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $body === '') {
        flash('Please fill in your name, a valid email and a message.', 'error');
    } else {
        db()->prepare('INSERT INTO messages (name, email, subject, body) VALUES (?,?,?,?)')
            ->execute([$name, $email, trim($_POST['subject'] ?? ''), $body]);
        flash('Message sent. We will get back to you soon.');
        redirect('contact.php');
    }
}
require __DIR__ . '/includes/header.php';
?>
<h1>Contact us</h1>
<p>Email: <a href="mailto:<?= e(config('contact_email')) ?>"><?= e(config('contact_email')) ?></a></p>
<form method="post" class="stack">
  <?= csrf_field() ?>
  <label>Name <input name="name" required></label>
  <label>Email <input type="email" name="email" required></label>
  <label>Subject <input name="subject"></label>
  <label>Message <textarea name="body" rows="5" required></textarea></label>
  <button>Send</button>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
