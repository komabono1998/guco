<?php
require_once __DIR__ . '/includes/bootstrap.php';
$n = q("SELECT * FROM news WHERE slug = ? AND status = 'published' AND published_at <= NOW()", [$_GET['slug'] ?? ''])->fetch();
if (!$n) {
    http_response_code(404);
}
$pageTitle = $n['title'] ?? 'Not found';
$pageDesc = $n ? ($n['summary'] ?: excerpt((string) $n['body'], 150)) : null;
require __DIR__ . '/includes/header.php';
if (!$n): ?>
  <h1>Article not found</h1><p><a href="<?= url('news.php') ?>">Back to news</a></p>
<?php else: ?>
  <article style="max-width:780px">
    <span class="badge"><?= e($n['category']) ?></span>
    <h1 style="margin-top:6px"><?= e($n['title']) ?></h1>
    <p class="meta"><?= e(fmt_date($n['published_at'])) ?><?= $n['source_name'] ? ' &middot; ' . e($n['source_name']) : '' ?></p>
    <?php if ($n['image']): ?><img src="<?= url('uploads/' . rawurlencode($n['image'])) ?>" alt="" style="width:100%;border-radius:12px;margin-bottom:12px"><?php endif; ?>
    <?php if ($n['summary']): ?><p class="lead"><?= e($n['summary']) ?></p><?php endif; ?>
    <?php if ($n['body']): ?><?= render_text((string) $n['body']) ?><?php endif; ?>
    <?php if ($n['source_type'] === 'link' && $n['external_url']): ?>
      <p><a class="btn" href="<?= e($n['external_url']) ?>" target="_blank" rel="noopener">Read the full story<?= $n['source_name'] ? ' at ' . e($n['source_name']) : '' ?> &#8599;</a></p>
    <?php endif; ?>
    <?php if ($n['source_type'] === 'document' && $n['document']): ?>
      <p><a class="btn" href="<?= url('file.php?type=news&id=' . (int) $n['id']) ?>"><?= icon('file', 18) ?> Download <?= e($n['document_original'] ?: 'the document') ?> <?= $n['document_size'] ? '(' . e(fmt_size((int) $n['document_size'])) . ')' : '' ?></a></p>
    <?php endif; ?>
    <p><a href="<?= url('news.php') ?>">&larr; All news</a></p>
  </article>
<?php endif;
require __DIR__ . '/includes/footer.php'; ?>
