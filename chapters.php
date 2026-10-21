<?php
$pageTitle = 'Alumni Chapters';
$pageDesc = 'Join or establish a recognised GUCO alumni chapter by region, profession, college or graduation year.';
require __DIR__ . '/includes/header.php';
$chapters = q('SELECT * FROM chapters WHERE is_public = 1 ORDER BY name')->fetchAll();
?>
<div class="page-head"><h1>Alumni Chapters</h1><p>Stay connected through shared disciplines, regions and interests</p></div>
<div class="lead"><?= cr('chapters.intro') ?></div>

<section class="section">
  <h2>Recognised chapters</h2>
  <div class="grid">
    <?php foreach ($chapters as $c): ?>
      <div class="card">
        <h3><?= e($c['name']) ?></h3>
        <?php if ($c['focus']): ?><span class="badge"><?= e($c['focus']) ?></span><?php endif; ?>
        <p><?= nl2br(e($c['description'])) ?></p>
        <?php if ($c['contact_name'] || $c['contact_email']): ?>
          <p class="meta">Contact: <?= e($c['contact_name']) ?><?= $c['contact_email'] ? ' &middot; <a href="mailto:' . e($c['contact_email']) . '">' . e($c['contact_email']) . '</a>' : '' ?></p>
        <?php endif; ?>
      </div>
    <?php endforeach; if (!$chapters): ?>
      <p>The list of recognised chapters will be published here. Contact the Secretariat for the current list.</p>
    <?php endif; ?>
  </div>
</section>

<section class="section grid two">
  <div class="card"><h2>What chapters do</h2><ul class="checks"><?php foreach (c_lines('chapters.activities') as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ul></div>
  <div class="card"><h2>Chapter leadership</h2><?= cr('chapters.leadership') ?></div>
</section>

<section class="cta">
  <h2 style="color:#fff;margin-top:0">Find or establish a chapter</h2>
  <?= cr('chapters.cta') ?>
  <a class="btn" href="<?= url('contact.php?category=Alumni+Chapters') ?>">ENQUIRE ABOUT ALUMNI CHAPTERS</a>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
