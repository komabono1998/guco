<?php
require_once __DIR__ . '/../includes/portal.php';
$me = require_member();
$mid = (int) $me['id'];
$q = trim($_GET['q'] ?? '');
$year = (int) ($_GET['year'] ?? 0);
$chapterId = (int) ($_GET['chapter'] ?? 0);
$chapters = q('SELECT id, name FROM chapters ORDER BY sort_order, name')->fetchAll(PDO::FETCH_KEY_PAIR);
$sql = "SELECT u.id, u.full_name, p.college, p.programme, p.graduation_year, p.profession, p.location, c.name AS chapter,
        (SELECT status FROM connections x WHERE (x.requester_id = u.id AND x.addressee_id = ?) OR (x.requester_id = ? AND x.addressee_id = u.id) LIMIT 1) AS conn
        FROM users u JOIN member_profiles p ON p.user_id = u.id LEFT JOIN chapters c ON c.id = p.chapter_id
        WHERE u.status = 'approved' AND p.card_status <> 'revoked' AND p.directory_consent = 1 AND u.id <> ?";
$args = [$mid, $mid, $mid];
if ($q !== '') {
    $sql .= ' AND (u.full_name LIKE ? OR p.college LIKE ? OR p.programme LIKE ? OR p.profession LIKE ? OR p.location LIKE ? OR p.employer LIKE ?)';
    $like = "%$q%";
    array_push($args, $like, $like, $like, $like, $like, $like);
}
if ($year) {
    $sql .= ' AND p.graduation_year = ?';
    $args[] = $year;
}
if ($chapterId) {
    $sql .= ' AND p.chapter_id = ?';
    $args[] = $chapterId;
}
$rows = q($sql . ' ORDER BY u.full_name LIMIT 200', $args)->fetchAll();
$years = q("SELECT DISTINCT p.graduation_year FROM member_profiles p JOIN users u ON u.id = p.user_id WHERE p.graduation_year IS NOT NULL AND p.directory_consent = 1 AND u.status = 'approved' ORDER BY 1 DESC")->fetchAll(PDO::FETCH_COLUMN);
portal_header("Alumni Connect");
?>
<p class="meta">Only members who gave consent are listed. Contact details stay private until someone accepts your connection request. <a href="connections.php">My connections</a> &middot; <a href="mentorship.php">Mentorship Hub</a></p>
<form method="get" class="filters">
  <label>Search <input type="search" name="q" value="<?= e($q) ?>" placeholder="Name, programme, profession, employer, place..."></label>
  <label>Graduation year <select name="year"><option value="">Any</option><?php foreach ($years as $y): ?><option<?= $y == $year ? ' selected' : '' ?>><?= (int) $y ?></option><?php endforeach; ?></select></label>
  <label>Chapter <select name="chapter"><option value="">Any</option><?php foreach ($chapters as $cid => $cn): ?><option value="<?= (int) $cid ?>"<?= $cid == $chapterId ? ' selected' : '' ?>><?= e($cn) ?></option><?php endforeach; ?></select></label>
  <button>Search</button>
</form>
<section class="panel">
  <div class="table-wrap"><table>
    <tr><th>Name</th><th>Programme</th><th>Year</th><th>Profession</th><th>Location</th><th>Chapter</th><th></th></tr>
    <?php foreach ($rows as $r): ?>
      <tr><td><?= e($r['full_name']) ?></td><td><?= e($r['programme']) ?><br><span class="meta"><?= e($r['college']) ?></span></td>
        <td><?= e($r['graduation_year']) ?></td><td><?= e($r['profession']) ?></td><td><?= e($r['location']) ?></td><td><?= e($r['chapter']) ?></td>
        <td><?php if ($r['conn'] === 'accepted'): ?><span class="badge">Connected</span>
          <?php elseif ($r['conn'] === 'pending'): ?><span class="badge warn">Request pending</span>
          <?php elseif ($r['conn'] === 'declined'): ?><span class="meta">-</span>
          <?php else: ?><form method="post" action="connections.php"><?= csrf_field() ?><input type="hidden" name="action" value="request"><input type="hidden" name="to" value="<?= (int) $r['id'] ?>"><input type="hidden" name="q" value="<?= e($q) ?>"><button class="secondary">Connect</button></form><?php endif; ?></td></tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="7">No members found.</td></tr><?php endif; ?>
  </table></div>
</section>
<?php portal_footer(); ?>
