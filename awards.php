<?php
$pageTitle = 'Awards & Recognition';
$pageDesc = 'GUCO awards: Outstanding Achievement and Distinguished Service awards for Gulu University alumni.';
require __DIR__ . '/includes/header.php';
$awards = q('SELECT * FROM awards WHERE is_public = 1 ORDER BY id')->fetchAll();
$status = ['not_open' => ['Nominations not open', 'warn'], 'open' => ['Nominations open', ''], 'closed' => ['Nominations closed', 'bad']];
?>
<div class="page-head"><h1>Awards &amp; Recognition</h1><p>Celebrating outstanding alumni</p></div>
<div class="lead"><?= cr('awards.intro') ?></div>
<div class="split" style="margin:24px 0">
  <?php if ($img = c_image('awards.image')): ?><figure class="photo"><img src="<?= e($img) ?>" alt="GUCO awards" loading="lazy"></figure><?php else: ?><?= dinner_figure(4, '', 'Certificates of Recognition are presented at GUCO events.') ?><?php endif; ?>
  <div class="card accent"><h2 style="margin-top:0">Recognising excellence</h2><?= cr('awards.feature') ?></div>
</div>
<div class="grid two">
<?php foreach ($awards as $a): [$label, $cls] = $status[$a['nomination_status']]; ?>
  <article class="card accent">
    <h2 style="margin-top:0"><?= e($a['name']) ?></h2>
    <span class="badge <?= $cls ?>"><?= e($label) ?></span>
    <p><?= nl2br(e($a['description'])) ?></p>
    <?php if ($a['eligibility']): ?><p><strong>Eligibility:</strong> <?= nl2br(e($a['eligibility'])) ?></p><?php endif; ?>
    <?php if ($a['nomination_status'] === 'open'): ?>
      <?php if ($a['deadline']): ?><p><strong>Deadline:</strong> <?= e(fmt_date($a['deadline'])) ?></p><?php endif; ?>
      <?php if ($a['required_documents']): ?><p><strong>Required documents:</strong> <?= nl2br(e($a['required_documents'])) ?></p><?php endif; ?>
    <?php endif; ?>
    <?php if ($a['selection_process']): ?><p><strong>Selection process:</strong> <?= nl2br(e($a['selection_process'])) ?></p><?php endif; ?>
    <?php if ($a['past_recipients']): ?><p><strong>Past recipients:</strong> <?= nl2br(e($a['past_recipients'])) ?></p><?php endif; ?>
    <?php if ($a['contact']): ?><p class="meta">Enquiries: <?= e($a['contact']) ?></p><?php endif; ?>
  </article>
<?php endforeach; ?>
</div>
<div class="notice"><?= cr('awards.notice') ?></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
