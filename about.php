<?php
$pageTitle = 'About GUCO';
$pageDesc = 'Overview, vision, mission, core values and objectives of Gulu University Convocation (GUCO).';
require __DIR__ . '/includes/header.php';
$img = c_image('about.image');
$valueIcons = ['shield', 'users', 'graduation', 'link', 'star', 'heart'];
$objectives = c_lines('about.objectives');
?>
<div class="page-head"><h1>About GUCO</h1><p><?= e(setting('site_tagline', 'Community Transformation Actualized')) ?></p></div>

<section id="overview" class="section split">
  <div>
    <span class="eyebrow-s">Who we are</span>
    <h2 style="margin-top:4px">Overview</h2>
    <?= cr('about.overview') ?>
    <div class="fact-row">
      <div><b>2023</b><span>Constitution in force</span></div>
      <div><b>4</b><span>Membership categories</span></div>
      <div><b>6</b><span>Core values</span></div>
      <div><b><?= count($objectives) ?></b><span>Objectives</span></div>
    </div>
  </div>
  <?php if ($img): ?><figure class="photo"><img src="<?= e($img) ?>" alt="GUCO" loading="lazy"></figure><?php else: ?><?= dinner_figure(19, '', DINNER_ALBUM) ?><?php endif; ?>
</section>

<section id="vision" class="section">
  <div class="purpose">
    <article class="p-card vision">
      <span class="p-ic"><?= icon('eye', 30) ?></span>
      <h2>Our Vision</h2>
      <div class="p-text"><?= cr('about.vision') ?></div>
      <span class="p-mark"><?= icon('eye', 150) ?></span>
    </article>
    <article class="p-card mission">
      <span class="p-ic"><?= icon('target', 30) ?></span>
      <h2>Our Mission</h2>
      <div class="p-text"><?= cr('about.mission') ?></div>
      <span class="p-mark"><?= icon('target', 150) ?></span>
    </article>
  </div>
</section>

<section class="section">
  <div class="sec-head"><div><span class="eyebrow-s">What guides us</span><h2>Our core values</h2></div></div>
  <div class="values">
    <?php foreach (c_pairs('about.values') as $i => [$name, $desc]): ?>
      <div class="value v<?= $i % 6 ?>">
        <span class="v-ic"><?= icon($valueIcons[$i % 6], 26) ?></span>
        <h3><?= e($name) ?></h3>
        <?php if ($desc): ?><p><?= e($desc) ?></p><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section id="objectives" class="section">
  <div class="sec-head"><div><span class="eyebrow-s">What we aim to achieve</span><h2>Our objectives</h2></div></div>
  <div class="objectives">
    <?php foreach ($objectives as $i => $o): ?>
      <div class="objective"><span class="o-n"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span><p><?= e($o) ?></p></div>
    <?php endforeach; ?>
  </div>
</section>

<section class="section split">
  <div class="card accent mandate">
    <span class="p-ic dark"><?= icon('shield', 26) ?></span>
    <h2 style="margin-top:8px">Our mandate</h2>
    <?= cr('about.mandate') ?>
  </div>
  <div class="constitution-card">
    <span class="p-ic"><?= icon('book', 28) ?></span>
    <h2>The Constitution</h2>
    <?= cr('about.constitution') ?>
    <a class="btn gold" href="<?= url('resources.php?q=constitution') ?>">READ THE GUCO CONSTITUTION (2023)</a>
  </div>
</section>

<section class="cta">
  <h2 style="margin-top:0">Be part of the story</h2>
  <p>Join the Convocation, take part in chapters and projects, and help build a stronger Gulu University.</p>
  <a class="btn" href="<?= url('register.php') ?>">JOIN GUCO</a>
  <a class="btn secondary" href="<?= url('membership.php') ?>">Membership benefits</a>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
