<?php
require_once __DIR__ . '/../includes/admin.php';
$admin = require_perm('members');
$chapters = ['' => 'None'] + q('SELECT id, name FROM chapters ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    $user = q('SELECT * FROM users WHERE id = ?', [$id])->fetch();
    if ($user && $id !== (int) $admin['id']) {
        if (isset($_POST['delete'])) {
            q('DELETE FROM users WHERE id = ?', [$id]);
            audit('delete_member', "{$user['email']} (id $id)");
            flash('Member deleted.');
            redirect('admin/members.php');
        }
        $status = in_array($_POST['status'] ?? '', ['pending', 'approved', 'rejected'], true) ? $_POST['status'] : $user['status'];
        $cat = isset(CATEGORY_LABELS[$_POST['category'] ?? '']) ? $_POST['category'] : 'ordinary';
        $sub = in_array($_POST['subscription_status'] ?? '', ['unpaid', 'active', 'arrears'], true) ? $_POST['subscription_status'] : 'unpaid';
        q('UPDATE users SET status = ? WHERE id = ?', [$status, $id]);
        q('UPDATE member_profiles SET category=?, subscription_status=?, paid_until=?, chapter_id=?, admin_notes=? WHERE user_id=?', [
            $cat, $sub, ($_POST['paid_until'] ?? '') ?: null, (int) ($_POST['chapter_id'] ?? 0) ?: null, trim($_POST['admin_notes'] ?? ''), $id,
        ]);
        audit('update_member', "{$user['email']}: status=$status, category=$cat, subscription=$sub");
        flash('Member updated.');
    }
    redirect('admin/members.php' . ($id ? "?id=$id" : ''));
}

$statusF = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');
$where = '1=1';
$args = [];
if (in_array($statusF, ['pending', 'approved', 'rejected'], true)) {
    $where .= ' AND u.status = ?';
    $args[] = $statusF;
}
if ($search !== '') {
    $where .= ' AND (u.full_name LIKE ? OR u.email LIKE ? OR p.programme LIKE ?)';
    array_push($args, "%$search%", "%$search%", "%$search%");
}
$base = "FROM users u LEFT JOIN member_profiles p ON p.user_id = u.id WHERE $where";

if (isset($_GET['export'])) {
    $rows = q("SELECT u.full_name, u.email, u.role, u.status, p.category, p.subscription_status, p.paid_until, p.phone, p.college, p.programme, p.graduation_year, p.profession, p.location $base ORDER BY u.full_name", $args)->fetchAll();
    audit('export_members', count($rows) . ' records');
    csv_download('guco-members-' . date('Ymd') . '.csv', ['Name', 'Email', 'Role', 'Status', 'Category', 'Subscription', 'Paid until', 'Phone', 'College', 'Programme', 'Graduation year', 'Profession', 'Location'], $rows);
}

if (isset($_GET['id'])) {
    $m = q('SELECT u.*, p.*, c.name AS chapter_name FROM users u LEFT JOIN member_profiles p ON p.user_id = u.id LEFT JOIN chapters c ON c.id = p.chapter_id WHERE u.id = ?', [(int) $_GET['id']])->fetch();
    if (!$m) {
        http_response_code(404);
        exit('Member not found.');
    }
    admin_header('Review member');
    ?>
<p><a href="<?= url('admin/members.php') ?>">&larr; All members</a></p>
<div class="grid two">
  <div class="card">
    <h2 style="margin-top:0"><?= e($m['full_name']) ?></h2>
    <p><?= e($m['email']) ?><br><?= e($m['phone']) ?></p>
    <p><strong>College:</strong> <?= e($m['college']) ?><br><strong>Programme:</strong> <?= e($m['programme']) ?><br><strong>Graduation year:</strong> <?= e($m['graduation_year']) ?><br>
       <strong>Profession:</strong> <?= e($m['profession']) ?><br><strong>Location:</strong> <?= e($m['location']) ?></p>
    <p><strong>Eligibility details:</strong><br><?= nl2br(e($m['eligibility_details'])) ?></p>
    <p><strong>Payment reference:</strong> <?= e($m['payment_reference'] ?: 'none') ?></p>
    <p><strong>Applied:</strong> <?= e(fmt_date($m['created_at'], 'j M Y')) ?> &middot; <strong>Directory consent:</strong> <?= $m['directory_consent'] ? 'yes' : 'no' ?></p>
  </div>
  <form method="post" class="stack card">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
    <label>Application status
      <select name="status"><?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $k => $l): ?><option value="<?= $k ?>"<?= $m['status'] === $k ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></label>
    <label>Membership category
      <select name="category"><?php foreach (CATEGORY_LABELS as $k => $l): ?><option value="<?= $k ?>"<?= $m['category'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
    <label>Subscription status
      <select name="subscription_status"><?php foreach (['unpaid' => 'Unpaid / awaiting confirmation', 'active' => 'Active (paid up)', 'arrears' => 'In arrears'] as $k => $l): ?><option value="<?= $k ?>"<?= $m['subscription_status'] === $k ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></label>
    <label>Paid up to <input type="date" name="paid_until" value="<?= e($m['paid_until']) ?>"></label>
    <label>Chapter <select name="chapter_id"><?php foreach ($chapters as $k => $l): ?><option value="<?= e($k) ?>"<?= (string) $m['chapter_id'] === (string) $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
    <label>Internal notes <span class="hint">(never shown to the member)</span><textarea name="admin_notes" rows="3"><?= e($m['admin_notes']) ?></textarea></label>
    <button>Save</button>
    <?php if ((int) $m['id'] !== (int) $admin['id']): ?>
      <button class="danger" name="delete" value="1" onclick="return confirm('Permanently delete this member and their data?')">Delete member</button>
    <?php endif; ?>
  </form>
</div>
    <?php
    admin_footer();
    exit;
}

$rows = q("SELECT u.id, u.full_name, u.email, u.role, u.status, u.created_at, p.category, p.subscription_status, p.graduation_year $base ORDER BY FIELD(u.status,'pending','approved','rejected'), u.created_at DESC", $args)->fetchAll();
admin_header('Members & applications');
?>
<form method="get" class="filters">
  <label>Search <input type="search" name="q" value="<?= e($search) ?>"></label>
  <label>Status <select name="status"><option value="">All</option><?php foreach (['pending', 'approved', 'rejected'] as $s): ?><option<?= $s === $statusF ? ' selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></label>
  <button>Filter</button>
  <a class="btn secondary" href="?<?= e(http_build_query(array_filter(['q' => $search, 'status' => $statusF]) + ['export' => 1])) ?>">Export CSV</a>
</form>
<div class="table-wrap"><table>
  <tr><th>Name</th><th>Email</th><th>Category</th><th>Year</th><th>Status</th><th>Subscription</th><th></th></tr>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><?= e($r['full_name']) ?><?= $r['role'] !== 'member' ? ' <span class="badge">' . e(ROLE_LABELS[$r['role']]) . '</span>' : '' ?></td>
    <td><?= e($r['email']) ?></td><td><?= e(CATEGORY_LABELS[$r['category']] ?? '') ?></td><td><?= e($r['graduation_year']) ?></td>
    <td><span class="badge <?= $r['status'] === 'approved' ? '' : ($r['status'] === 'pending' ? 'warn' : 'bad') ?>"><?= e($r['status']) ?></span></td>
    <td><?= e($r['subscription_status']) ?></td>
    <td><a class="btn secondary" href="?id=<?= (int) $r['id'] ?>">Review</a></td>
  </tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="7">No members found.</td></tr><?php endif; ?>
</table></div>
<?php admin_footer(); ?>
