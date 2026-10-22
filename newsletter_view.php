<?php
require_once __DIR__ . '/includes/bootstrap.php';
$n = q("SELECT * FROM newsletters WHERE slug = ? AND status = 'published' AND published_at <= NOW()", [$_GET['slug'] ?? ''])->fetch();
if (!$n) {
    http_response_code(404);
}
$images = $n ? q('SELECT * FROM newsletter_images WHERE newsletter_id = ? ORDER BY sort_order, id', [$n['id']])->fetchAll() : [];
$pageTitle = $n['title'] ?? 'Newsletter not found';
$pageDesc = $n ? ($n['summary'] ?: excerpt((string) $n['body'], 150)) : null;
require __DIR__ . '/includes/header.php';
if (!$n): ?>
  <h1>Newsletter not found</h1><p><a href="<?= url('newsletters.php') ?>">Back to newsletters</a></p>
<?php else: ?>
  <div class="page-head"><?php if ($n['issue_label']): ?><span class="badge"><?= e($n['issue_label']) ?></span><?php endif; ?><h1><?= e($n['title']) ?></h1><p><?= e(fmt_date($n['published_at'])) ?></p></div>
  <article style="max-width:820px">
    <?php if ($n['cover']): ?><img src="<?= e(url('uploads/' . rawurlencode($n['cover']))) ?>" alt="" style="width:100%;border-radius:12px;margin-bottom:12px"><?php endif; ?>
    <?php if ($n['summary']): ?><p class="lead"><?= e($n['summary']) ?></p><?php endif; ?>
    <?php if ($n['pdf']): ?><p><a class="btn" href="<?= url('file.php?type=newsletter&id=' . (int) $n['id']) ?>"><?= icon('file', 18) ?> Open the PDF edition<?= $n['pdf_size'] ? ' (' . e(fmt_size((int) $n['pdf_size'])) . ')' : '' ?></a></p><?php endif; ?>
    <?= render_text((string) $n['body']) ?>
    <?php if ($images): ?>
      <h2>Pictures</h2>
      <div class="album">
        <?php foreach ($images as $im): ?>
          <figure class="photo" style="margin:0"><a href="<?= e(url('uploads/' . rawurlencode($im['filename']))) ?>" target="_blank" rel="noopener"><img src="<?= e(url('uploads/' . rawurlencode($im['filename']))) ?>" alt="<?= e($im['caption'] ?: 'Newsletter picture') ?>" loading="lazy" style="aspect-ratio:4/3"></a>
            <?= $im['caption'] ? '<figcaption>' . e($im['caption']) . '</figcaption>' : '' ?></figure>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <p><a href="<?= url('newsletters.php') ?>">&larr; All newsletters</a></p>
  </article>
<?php endif;
require __DIR__ . '/includes/footer.php'; ?>
