<?php
$pageTitle = 'About GUCO';
$pageDesc = 'Overview, vision, mission, core values and objectives of Gulu University Convocation (GUCO).';
require __DIR__ . '/includes/header.php';
$img = c_image('about.image');
?>
<div class="page-head"><h1>About GUCO</h1><p><?= e(setting('site_tagline', 'Community Transformation Actualized')) ?></p></div>

<section id="overview" class="section split">
  <div>
    <h2>Overview</h2>
    <?= cr('about.overview') ?>
  </div>
  <?php if ($img): ?><figure class="photo"><img src="<?= e($img) ?>" alt="GUCO" loading="lazy"></figure><?php else: ?><?= dinner_figure(19, '', DINNER_ALBUM) ?><?php endif; ?>
</section>

<section id="vision" class="section grid two">
  <div class="card accent"><h2>Vision</h2><?= cr('about.vision') ?></div>
  <div class="card accent"><h2>Mission</h2><?= cr('about.mission') ?></div>
</section>

<section class="section">
  <h2>Core values</h2>
  <div class="grid">
    <?php foreach (c_lines('about.values') as $v): ?><div class="card"><strong><?= e($v) ?></strong></div><?php endforeach; ?>
  </div>
</section>

<section id="objectives" class="section">
  <h2>Our objectives</h2>
  <ul class="checks"><?php foreach (c_lines('about.objectives') as $o): ?><li><?= e($o) ?></li><?php endforeach; ?></ul>
</section>

<section class="section">
  <h2>Our mandate</h2>
  <?= cr('about.mandate') ?>
</section>

<section class="section card">
  <h2 style="margin-top:0">The Constitution</h2>
  <?= cr('about.constitution') ?>
  <a class="btn" href="<?= url('resources.php?q=constitution') ?>">READ THE GUCO CONSTITUTION (2023)</a>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
