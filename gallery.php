<?php
$pageTitle = 'Gallery';
$pageDesc = 'Photos from GUCO events and activities.';
require __DIR__ . '/includes/header.php';
$photos = q('SELECT * FROM gallery ORDER BY created_at DESC')->fetchAll();
?>
<div class="page-head"><h1>Gallery</h1><p>Photos published with consent</p></div>
<h2><?= e(DINNER_ALBUM) ?></h2>
<div class="album">
<?php foreach (array_keys(DINNER_PHOTOS) as $n): ?>
  <a href="<?= e(dinner_src($n)) ?>" target="_blank" rel="noopener"><?= dinner_figure($n) ?></a>
<?php endforeach; ?>
</div>
<?php if ($photos): ?><h2 style="margin-top:36px">More photos</h2><?php endif; ?>
<div class="grid gallery">
<?php foreach ($photos as $p): ?>
  <figure class="card" style="margin:0">
    <img src="<?= url('uploads/' . rawurlencode($p['filename'])) ?>" alt="<?= e($p['caption'] ?: 'GUCO event photo') ?>" loading="lazy">
    <figcaption class="meta"><?= e($p['caption']) ?></figcaption>
  </figure>
<?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
