<?php
$pageTitle = 'Gallery';
$pageDesc = 'Photos from GUCO events and activities.';
require __DIR__ . '/includes/header.php';
$photos = q('SELECT * FROM gallery ORDER BY created_at DESC')->fetchAll();
?>
<div class="page-head"><h1>Gallery</h1><p>Photos published with consent</p></div>
<div class="grid gallery">
<?php foreach ($photos as $p): ?>
  <figure class="card" style="margin:0">
    <img src="<?= url('uploads/' . rawurlencode($p['filename'])) ?>" alt="<?= e($p['caption'] ?: 'GUCO event photo') ?>" loading="lazy">
    <figcaption class="meta"><?= e($p['caption']) ?></figcaption>
  </figure>
<?php endforeach; if (!$photos): ?><p>No photos yet.</p><?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
