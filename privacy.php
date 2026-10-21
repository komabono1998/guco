<?php
$pageTitle = 'Privacy Notice';
$pageDesc = 'How Gulu University Convocation collects, uses and protects personal information on this website.';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Privacy Notice</h1></div>
<?= cr('privacy.body') ?>
<p>Questions or requests? Use the <a href="<?= url('contact.php?category=Profile+correction') ?>">contact form</a>.</p>
<?php require __DIR__ . '/includes/footer.php'; ?>
