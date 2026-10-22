<?php
require_once __DIR__ . '/includes/bootstrap.php';
$a = q('SELECT * FROM gallery_albums WHERE id = ? AND is_public = 1', [(int) ($_GET['id'] ?? 0)])->fetch();
if (!$a) {
    http_response_code(404);
}
$photos = $a ? q('SELECT * FROM gallery WHERE album_id = ? ORDER BY sort_order, id', [$a['id']])->fetchAll() : [];
$pageTitle = $a['title'] ?? 'Album not found';
$pageDesc = $a ? ($a['description'] ?: 'Photo album from GUCO.') : null;
require __DIR__ . '/includes/header.php';
if (!$a): ?>
  <h1>Album not found</h1><p><a href="<?= url('gallery.php') ?>">Back to the gallery</a></p>
<?php else: ?>
  <p><a href="<?= url('gallery.php') ?>">&larr; All albums</a></p>
  <div class="page-head"><h1><?= e($a['title']) ?></h1><p><?= $a['event_date'] ? e(fmt_date($a['event_date'], 'j F Y')) . ' &middot; ' : '' ?><?= e($a['description']) ?></p></div>
  <div class="album">
    <?php foreach ($photos as $p): ?>
      <a href="<?= e(gallery_src($p['filename'])) ?>" target="_blank" rel="noopener"><figure class="photo" style="margin:0"><img src="<?= e(gallery_src($p['filename'])) ?>" alt="<?= e($p['caption'] ?: $a['title']) ?>" loading="lazy"><?= $p['caption'] ? '<figcaption>' . e($p['caption']) . '</figcaption>' : '' ?></figure></a>
    <?php endforeach; if (!$photos): ?><p>No photos in this album yet.</p><?php endif; ?>
  </div>
<?php endif;
require __DIR__ . '/includes/footer.php'; ?>
