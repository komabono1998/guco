<?php
require_once __DIR__ . '/../includes/portal.php';
require_member();
$search = trim($_GET['q'] ?? '');
$cat = trim($_GET['category'] ?? '');
$where = 'is_public = 1';
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
portal_header('Documents');
?>
<p class="meta">Official documents, including those reserved for members.</p>
<form method="get" class="filters">
  <label>Search <input type="search" name="q" value="<?= e($search) ?>"></label>
  <label>Category <select name="category"><option value="">All</option><?php foreach ($cats as $c): ?><option<?= $c === $cat ? ' selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></label>
  <button>Search</button>
</form>
<section class="panel">
  <div class="table-wrap"><table>
    <tr><th>Title</th><th>Category</th><th>Published</th><th>Size</th><th></th></tr>
    <?php foreach ($items as $r): ?>
      <tr><td><strong><?= e($r['title']) ?></strong><?= $r['member_only'] ? ' <span class="badge warn">Members only</span>' : '' ?><br><span class="meta"><?= e($r['description']) ?></span></td>
        <td><?= e($r['category']) ?></td><td><?= e(fmt_date($r['published_at'])) ?></td><td><?= e(fmt_size($r['file_size'])) ?></td>
        <td><?php if ($r['filename']): ?><a class="btn" href="<?= e(url('download.php?id=' . (int) $r['id'])) ?>">Download</a><?php endif; ?></td></tr>
    <?php endforeach; if (!$items): ?><tr><td colspan="5">No documents found.</td></tr><?php endif; ?>
  </table></div>
</section>
<?php portal_footer(); ?>
