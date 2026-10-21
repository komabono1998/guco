<?php
require_once __DIR__ . '/../includes/portal.php';
require_member();
$q = trim($_GET['q'] ?? '');
$sql = "SELECT u.full_name, p.college, p.programme, p.graduation_year, p.profession, p.location, c.name AS chapter
        FROM users u JOIN member_profiles p ON p.user_id = u.id LEFT JOIN chapters c ON c.id = p.chapter_id
        WHERE u.status = 'approved' AND p.card_status <> 'revoked' AND p.directory_consent = 1";
$args = [];
if ($q !== '') {
    $sql .= ' AND (u.full_name LIKE ? OR p.college LIKE ? OR p.programme LIKE ? OR p.profession LIKE ? OR p.location LIKE ? OR p.graduation_year = ?)';
    $like = "%$q%";
    $args = [$like, $like, $like, $like, $like, (int) $q];
}
$rows = q($sql . ' ORDER BY u.full_name', $args)->fetchAll();
portal_header("Members' directory");
?>
<p class="meta">Only members who gave consent are listed. Contact details are never shown.</p>
<form method="get" class="filters">
  <label>Search <input type="search" name="q" value="<?= e($q) ?>" placeholder="Name, programme, year, location..."></label>
  <button>Search</button>
</form>
<section class="panel">
  <div class="table-wrap"><table>
    <tr><th>Name</th><th>Programme</th><th>Year</th><th>Profession</th><th>Location</th><th>Chapter</th></tr>
    <?php foreach ($rows as $r): ?>
      <tr><td><?= e($r['full_name']) ?></td><td><?= e($r['programme']) ?><br><span class="meta"><?= e($r['college']) ?></span></td>
        <td><?= e($r['graduation_year']) ?></td><td><?= e($r['profession']) ?></td><td><?= e($r['location']) ?></td><td><?= e($r['chapter']) ?></td></tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="6">No members found.</td></tr><?php endif; ?>
  </table></div>
</section>
<?php portal_footer(); ?>
