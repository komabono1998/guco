<?php
$pageTitle = 'Gallery';
require __DIR__ . '/includes/header.php';
$photos = db()->query('SELECT * FROM gallery ORDER BY created_at DESC')->fetchAll();
?>
<h1>Gallery</h1>
<div class="grid gallery">
<?php foreach ($photos as $p): ?>
  <figure class="card" style="margin:0">
    <img src="<?= url('uploads/' . rawurlencode($p['filename'])) ?>" alt="<?= e($p['caption']) ?>" loading="lazy">
    <figcaption class="meta"><?= e($p['caption']) ?></figcaption>
  </figure>
<?php endforeach; if (!$photos): ?><p>No photos yet.</p><?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
