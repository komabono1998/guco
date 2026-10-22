<?php
$pageTitle = 'Gallery';
$pageDesc = 'Photo albums from GUCO events and activities.';
require __DIR__ . '/includes/header.php';
$albums = q("SELECT a.*, (SELECT COUNT(*) FROM gallery g WHERE g.album_id = a.id) AS n,
              (SELECT g.filename FROM gallery g WHERE g.album_id = a.id ORDER BY g.sort_order, g.id LIMIT 1) AS first_photo
             FROM gallery_albums a WHERE a.is_public = 1 ORDER BY a.sort_order, a.event_date DESC, a.id DESC")->fetchAll();
?>
<div class="page-head"><h1>Gallery</h1><p>Photo albums published with consent</p></div>
<div class="grid">
  <?php foreach ($albums as $a): $cover = $a['cover'] ?: $a['first_photo']; ?>
    <a class="card album-card" href="<?= url('gallery_album.php?id=' . (int) $a['id']) ?>">
      <?php if ($cover): ?><img src="<?= e(gallery_src($cover)) ?>" alt="" loading="lazy" style="width:100%;height:190px;object-fit:cover;border-radius:10px;margin-bottom:8px"><?php endif; ?>
      <h3 style="margin:.2em 0"><?= e($a['title']) ?></h3>
      <p class="meta" style="margin:0"><?= $a['event_date'] ? e(fmt_date($a['event_date'], 'F Y')) . ' &middot; ' : '' ?><?= (int) $a['n'] ?> photo<?= (int) $a['n'] === 1 ? '' : 's' ?></p>
      <?php if ($a['description']): ?><p style="margin:.4em 0 0"><?= e(excerpt($a['description'], 110)) ?></p><?php endif; ?>
    </a>
  <?php endforeach; if (!$albums): ?><p>No albums yet.</p><?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
