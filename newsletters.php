<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Newsletters';
$pageDesc = 'Read or download the GUCO newsletters.';
$page = max(1, (int) ($_GET['page'] ?? 1));
$per = 9;
$total = (int) db()->query("SELECT COUNT(*) FROM newsletters WHERE status = 'published' AND published_at <= NOW()")->fetchColumn();
$items = q("SELECT * FROM newsletters WHERE status = 'published' AND published_at <= NOW() ORDER BY published_at DESC LIMIT $per OFFSET " . (($page - 1) * $per))->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Newsletters</h1><p>News from GUCO, in your inbox and online</p></div>
<div class="grid">
  <?php foreach ($items as $n): ?>
    <article class="card newsletter-card">
      <?php if ($n['cover']): ?><a href="<?= url('newsletter_view.php?slug=' . urlencode($n['slug'])) ?>"><img src="<?= e(url('uploads/' . rawurlencode($n['cover']))) ?>" alt="" loading="lazy" style="width:100%;height:170px;object-fit:cover;border-radius:10px;margin-bottom:8px"></a><?php endif; ?>
      <?php if ($n['issue_label']): ?><span class="badge"><?= e($n['issue_label']) ?></span><?php endif; ?>
      <h3 style="margin:.4em 0"><a href="<?= url('newsletter_view.php?slug=' . urlencode($n['slug'])) ?>"><?= e($n['title']) ?></a></h3>
      <p class="meta" style="margin:0"><?= e(fmt_date($n['published_at'])) ?></p>
      <p><?= e($n['summary'] ?: excerpt((string) $n['body'], 140)) ?></p>
      <p style="margin-top:auto">
        <?php if ($n['body']): ?><a class="btn" href="<?= url('newsletter_view.php?slug=' . urlencode($n['slug'])) ?>">Read online</a><?php endif; ?>
        <?php if ($n['pdf']): ?><a class="btn secondary" href="<?= url('file.php?type=newsletter&id=' . (int) $n['id']) ?>"><?= icon('file', 16) ?> PDF<?= $n['pdf_size'] ? ' (' . e(fmt_size((int) $n['pdf_size'])) . ')' : '' ?></a><?php endif; ?>
      </p>
    </article>
  <?php endforeach; ?>
</div>
<?php if (!$items): ?><p>The first newsletter will appear here once published. Subscribe using the form at the bottom of the page.</p><?php endif; ?>
<?= pagination($total, $per, $page) ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
