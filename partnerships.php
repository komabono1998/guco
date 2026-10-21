<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/forms.php';
$pageTitle = 'Partnerships & Support';
$pageDesc = 'Partner with or support Gulu University Convocation: collaboration, sponsorship, mentorship and approved initiatives.';
handle_enquiry_post('partnerships.php');
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Partnerships &amp; Support</h1><p>Work with GUCO</p></div>
<div class="lead"><?= cr('partnerships.intro') ?></div>

<section class="section">
  <?= dinner_figure(14, '', DINNER_ALBUM . ': celebrating excellence, strengthening partnerships and investing in the future of Gulu University') ?>
</section>

<section class="section grid two">
  <div class="card"><h2>Ways to collaborate</h2><ul class="checks"><?php foreach (c_lines('partnerships.ways') as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ul></div>
  <div class="card"><h2>Support GUCO</h2><?= cr('partnerships.support') ?></div>
</section>

<section class="section">
  <h2>Discuss a partnership or enquire about support</h2>
  <?php enquiry_form(in_array($_GET['category'] ?? '', ['Partnerships', 'Support GUCO'], true) ? $_GET['category'] : 'Partnerships'); ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
