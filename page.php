<?php
// Custom pages created in Admin > Pages.
require_once __DIR__ . '/includes/bootstrap.php';
$p = q('SELECT * FROM pages WHERE slug = ? AND is_published = 1', [$_GET['slug'] ?? ''])->fetch();
if (!$p) {
    http_response_code(404);
}
$pageTitle = $p['title'] ?? 'Page not found';
$pageDesc = $p ? ($p['meta_description'] ?: excerpt((string) $p['body'], 150)) : null;
require __DIR__ . '/includes/header.php';
if (!$p): ?>
  <h1>Page not found</h1><p><a href="<?= url() ?>">Back to the home page</a></p>
<?php else: ?>
  <div class="page-head"><h1><?= e($p['title']) ?></h1></div>
  <article style="max-width:820px">
    <?php if ($p['image']): ?><img src="<?= url('uploads/' . rawurlencode($p['image'])) ?>" alt="" style="width:100%;border-radius:10px;margin-bottom:16px"><?php endif; ?>
    <?= render_text((string) $p['body']) ?>
  </article>
<?php endif;
require __DIR__ . '/includes/footer.php'; ?>
