<?php
$pageTitle = 'Accessibility';
$pageDesc = 'Accessibility features and how to report a barrier on the GUCO website.';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Accessibility</h1></div>
<p>GUCO aims to make this website usable by everyone, including people who use assistive technology.</p>
<ul class="checks">
  <li>Clear headings, a "skip to main content" link and full keyboard navigation, including the menus.</li>
  <li>Text contrast designed to meet WCAG AA, visible focus indicators and responsive layouts for phones, tablets and desktops.</li>
  <li>Meaningful alternative text for images and labelled form fields.</li>
  <li>Event pages show accessibility information where the organiser has supplied it.</li>
</ul>
<p>If you meet a barrier or need information in another format, please <a href="<?= url('contact.php') ?>">contact the Secretariat</a>.</p>
<?php require __DIR__ . '/includes/footer.php'; ?>
