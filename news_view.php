<?php
require_once __DIR__ . '/includes/bootstrap.php';
$n = q("SELECT * FROM news WHERE slug = ? AND status = 'published' AND published_at <= NOW()", [$_GET['slug'] ?? ''])->fetch();
if (!$n) {
    http_response_code(404);
}
$pageTitle = $n['title'] ?? 'Not found';
$pageDesc = $n ? ($n['summary'] ?: excerpt($n['body'], 150)) : null;
require __DIR__ . '/includes/header.php';
if (!$n): ?>
  <h1>Article not found</h1><p><a href="<?= url('news.php') ?>">Back to news</a></p>
<?php else: ?>
  <article style="max-width:760px">
    <span class="badge"><?= e($n['category']) ?></span>
    <h1><?= e($n['title']) ?></h1>
    <p class="meta"><?= e(fmt_date($n['published_at'])) ?></p>
    <?php if ($n['image']): ?><img src="<?= url('uploads/' . rawurlencode($n['image'])) ?>" alt="" style="width:100%;border-radius:8px"><?php endif; ?>
    <div><?= nl2br(e($n['body'])) ?></div>
    <p><a href="<?= url('news.php') ?>">&larr; All news</a></p>
  </article>
<?php endif;
require __DIR__ . '/includes/footer.php'; ?>
