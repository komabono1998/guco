<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Resources';
$pageDesc = 'Official GUCO documents: the Constitution, reports, newsletters, notices and policies.';
$me = is_member() ? current_user() : null;
$search = trim($_GET['q'] ?? '');
$cat = trim($_GET['category'] ?? '');
$where = 'is_public = 1' . ($me ? '' : ' AND member_only = 0');
$args = [];
if ($search !== '') {
    $where .= ' AND (title LIKE ? OR description LIKE ?)';
    array_push($args, "%$search%", "%$search%");
}
if ($cat !== '') {
    $where .= ' AND category = ?';
    $args[] = $cat;
}
$items = q("SELECT * FROM resources WHERE $where ORDER BY published_at DESC, id DESC", $args)->fetchAll();
$cats = db()->query('SELECT DISTINCT category FROM resources WHERE is_public = 1 ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Reports &amp; Documents</h1><p>Official reports, notices and publications for public viewing</p></div>
<form method="get" class="filters">
  <label>Search <input type="search" name="q" value="<?= e($search) ?>" placeholder="e.g. constitution"></label>
  <label>Category <select name="category"><option value="">All</option><?php foreach ($cats as $c): ?><option<?= $c === $cat ? ' selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></label>
  <button>Search</button>
</form>
<div class="lead" style="font-size:1rem"><?= cr('resources.intro') ?></div>
<div class="table-wrap"><table>
  <tr><th>Title</th><th>Category</th><th>Published</th><th>Type / size</th><th></th></tr>
  <?php foreach ($items as $r): ?>
  <tr>
    <td><strong><?= e($r['title']) ?></strong><?= $r['member_only'] ? ' <span class="badge warn">Members only</span>' : '' ?><br><span class="meta"><?= e($r['description']) ?></span></td>
    <td><?= e($r['category']) ?></td>
    <td><?= e(fmt_date($r['published_at'])) ?></td>
    <td><?= $r['filename'] ? e(strtoupper(pathinfo((string) $r['original_name'], PATHINFO_EXTENSION))) . ' ' . e(fmt_size($r['file_size'])) : 'Link' ?></td>
    <td><?php if ($r['filename']): ?><a class="btn" href="<?= url('download.php?id=' . (int) $r['id']) ?>">Download</a>
      <?php elseif ($r['external_url']): ?><a class="btn" href="<?= url('download.php?id=' . (int) $r['id']) ?>" target="_blank" rel="noopener">Open &#8599;</a><?php endif; ?></td>
  </tr>
  <?php endforeach; if (!$items): ?><tr><td colspan="5">No documents found. The Constitution and approved reports will be added here.</td></tr><?php endif; ?>
</table></div>
<?php if (!$me): ?><p class="meta">Some documents are available to logged-in members only. <a href="<?= url('login.php') ?>">Log in</a>.</p><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
