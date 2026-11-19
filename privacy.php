<?php
$pageTitle = 'Privacy Notice';
$pageDesc = 'How Gulu University Convocation collects, uses and protects personal information on this website.';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Privacy Notice</h1></div>
<?= cr('privacy.body') ?>
<h2>Visitor statistics</h2>
<p>To understand how the website is used, we count page views. We do not store your IP address or set tracking cookies. Your visit is recorded as an anonymous code made from your connection and browser, together with the page viewed, your country, your device type and the site you came from. Browsers that send &quot;Do Not Track&quot; are not counted. Records are deleted after about 13 months.</p>
<p>Questions or requests? Use the <a href="<?= url('contact.php?category=Profile+correction') ?>">contact form</a>.</p>
<?php require __DIR__ . '/includes/footer.php'; ?>
