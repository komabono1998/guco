<?php
$pageTitle = 'Alumni directory';
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
require __DIR__ . '/includes/header.php';
$q = trim($_GET['q'] ?? '');
$sql = "SELECT u.full_name, p.* FROM users u JOIN member_profiles p ON p.user_id = u.id
        WHERE u.status = 'approved' AND p.show_in_directory = 1";
$args = [];
if ($q !== '') {
    $sql .= ' AND (u.full_name LIKE ? OR p.faculty LIKE ? OR p.course LIKE ? OR p.occupation LIKE ? OR p.location LIKE ? OR p.graduation_year = ?)';
    $like = "%$q%";
    $args = [$like, $like, $like, $like, $like, (int) $q];
}
$s = db()->prepare($sql . ' ORDER BY u.full_name');
$s->execute($args);
$rows = $s->fetchAll();
?>
<h1>Alumni directory</h1>
<form method="get" class="stack" style="grid-template-columns:1fr auto;max-width:520px">
  <input name="q" value="<?= e($q) ?>" placeholder="Search name, faculty, year, location…">
  <button>Search</button>
</form>
<br>
<table>
  <tr><th>Name</th><th>Faculty / Course</th><th>Year</th><th>Occupation</th><th>Location</th></tr>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['full_name']) ?></td>
      <td><?= e($r['faculty']) ?><br><span class="meta"><?= e($r['course']) ?></span></td>
      <td><?= e($r['graduation_year']) ?></td>
      <td><?= e($r['occupation']) ?></td>
      <td><?= e($r['location']) ?></td>
    </tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="5">No members found.</td></tr><?php endif; ?>
</table>
<?php require __DIR__ . '/includes/footer.php'; ?>
