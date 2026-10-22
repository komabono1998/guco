<?php
require_once __DIR__ . '/../includes/admin.php';
$admin = require_perm('members');
$chapters = ['' => 'None'] + q('SELECT id, name FROM chapters ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    $user = q('SELECT * FROM users WHERE id = ?', [$id])->fetch();
    $action = $_POST['action'] ?? '';
    if ($user && $id !== (int) $admin['id']) {
        if ($action === 'delete') {
            q('DELETE FROM users WHERE id = ?', [$id]);
            audit('delete_member', "{$user['email']} (id $id)");
            flash('Member deleted.');
            redirect('admin/members.php');
        }
        if (in_array($action, ['pay_verified', 'pay_rejected'], true)) {
            decide_payment((int) $_POST['payment_id'], substr($action, 4), ($_POST['paid_until'] ?? '') ?: null, trim($_POST['note'] ?? ''));
            flash($action === 'pay_verified' ? 'Payment verified.' : 'Payment rejected.');
        } elseif ($action === 'reset_password') {
            $new = temp_password();
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $id]);
            audit('reset_member_password', $user['email']);
            flash('New temporary password for ' . $user['full_name'] . ': ' . $new . '  (shown once; ask the member to change it after logging in).');
        } elseif ($action === 'revoke') {
            $reason = trim($_POST['reason'] ?? '');
            if ($reason === '') {
                flash('Enter a reason for revoking the card.', 'error');
            } else {
                q("UPDATE member_profiles SET card_status = 'revoked', card_revoked_at = NOW(), card_revoke_reason = ? WHERE user_id = ? AND membership_id IS NOT NULL", [mb_substr($reason, 0, 300), $id]);
                audit('revoke_card', "{$user['email']}: $reason");
                flash('Membership card revoked. The member loses member-only access.');
            }
        } elseif ($action === 'reinstate') {
            q("UPDATE member_profiles SET card_status = 'active', card_revoked_at = NULL, card_revoke_reason = NULL WHERE user_id = ? AND membership_id IS NOT NULL", [$id]);
            audit('reinstate_card', $user['email']);
            flash('Membership card reinstated.');
        } elseif (in_array($action, ['save', 'approve', 'reject'], true)) {
            $cat = isset(CATEGORY_LABELS[$_POST['category'] ?? '']) ? $_POST['category'] : 'ordinary';
            $sub = in_array($_POST['subscription_status'] ?? '', ['unpaid', 'active', 'arrears'], true) ? $_POST['subscription_status'] : 'unpaid';
            q('UPDATE member_profiles SET category=?, subscription_status=?, paid_until=?, chapter_id=?, admin_notes=? WHERE user_id=?', [
                $cat, $sub, ($_POST['paid_until'] ?? '') ?: null, (int) ($_POST['chapter_id'] ?? 0) ?: null, trim($_POST['admin_notes'] ?? ''), $id,
            ]);
            if ($action === 'approve') {
                $err = approve_member($id, !empty($_POST['waive']));
                $err ? flash($err, 'error') : flash('Member approved: membership ID issued and card activated.');
            } elseif ($action === 'reject') {
                q("UPDATE users SET status = 'rejected' WHERE id = ?", [$id]);
                audit('reject_member', $user['email']);
                flash('Application rejected.');
            } else {
                audit('update_member', "{$user['email']}: category=$cat, subscription=$sub");
                flash('Member updated.');
            }
        }
    }
    redirect('admin/members.php' . ($id ? "?id=$id" : ''));
}

$statusF = $_GET['status'] ?? '';
$cardF = $_GET['card'] ?? '';
$search = trim($_GET['q'] ?? '');
$where = '1=1';
$args = [];
if (in_array($statusF, ['pending', 'approved', 'rejected'], true)) {
    $where .= ' AND u.status = ?';
    $args[] = $statusF;
}
if (in_array($cardF, ['none', 'active', 'revoked'], true)) {
    $where .= ' AND p.card_status = ?';
    $args[] = $cardF;
}
if ($search !== '') {
    $where .= ' AND (u.full_name LIKE ? OR u.email LIKE ? OR p.programme LIKE ? OR p.membership_id LIKE ? OR p.member_no = ?)';
    array_push($args, "%$search%", "%$search%", "%$search%", "%$search%", (int) ltrim(preg_replace('/\D/', '', $search), '0'));
}
$base = "FROM users u LEFT JOIN member_profiles p ON p.user_id = u.id WHERE $where";

if (isset($_GET['export'])) {
    $rows = q("SELECT p.member_no, p.membership_id, u.full_name, u.email, u.role, u.status, p.category, p.card_status, p.subscription_status, p.paid_until, p.gender, p.date_of_birth, p.nationality, p.phone, p.address, p.location, p.country, p.college, p.programme, p.qualification_level, p.graduation_year, p.employment_status, p.employer, p.profession, p.interests, p.approved_at $base ORDER BY p.member_no, u.full_name", $args)->fetchAll();
    audit('export_members', count($rows) . ' records');
    csv_download('guco-members-' . date('Ymd') . '.csv', ['Member no', 'Membership ID', 'Name', 'Email', 'Role', 'Status', 'Category', 'Card', 'Subscription', 'Paid until', 'Gender', 'Date of birth', 'Nationality', 'Phone', 'Address', 'City/district', 'Country', 'College', 'Programme', 'Qualification', 'Graduation year', 'Employment', 'Employer', 'Profession', 'Interests', 'Approved'], $rows);
}

if (isset($_GET['id'])) {
    $m = q('SELECT u.*, p.*, c.name AS chapter_name FROM users u LEFT JOIN member_profiles p ON p.user_id = u.id LEFT JOIN chapters c ON c.id = p.chapter_id WHERE u.id = ?', [(int) $_GET['id']])->fetch();
    if (!$m) {
        http_response_code(404);
        exit('Member not found.');
    }
    $pays = q('SELECT * FROM member_payments WHERE user_id = ? ORDER BY created_at DESC', [$m['id']])->fetchAll();
    $card = card_state($m);
    $payBadge = ['pending' => 'warn', 'verified' => '', 'rejected' => 'bad'];
    admin_header('Review member');
    ?>
<p><a href="<?= url('admin/members.php') ?>">&larr; All members</a></p>
<div class="grid two">
  <div class="card">
    <div style="display:flex;gap:14px;align-items:center"><?= member_avatar($m, 'avatar') ?>
      <div><h2 style="margin:0"><?= e($m['full_name']) ?></h2>
        <span class="badge <?= $m['status'] === 'approved' ? '' : ($m['status'] === 'pending' ? 'warn' : 'bad') ?>"><?= e($m['status']) ?></span>
        <?php if ($m['member_no']): ?> <span class="meta">Member no. <?= e(member_no_label($m['member_no'])) ?></span><?php endif; ?></div></div>
    <?php if ($m['membership_id']): ?><p>Membership ID: <strong class="mono"><?= e($m['membership_id']) ?></strong><br>Card: <span class="badge <?= $card['state'] === 'valid' ? '' : 'bad' ?>"><?= e($card['label']) ?></span>
      <?= $m['card_status'] === 'revoked' ? '<br><span class="meta">Revoked ' . e(fmt_date($m['card_revoked_at'])) . ': ' . e($m['card_revoke_reason']) . '</span>' : '' ?></p><?php endif; ?>
    <h3>Personal</h3>
    <p><?= e($m['email']) ?> &middot; <?= e($m['phone']) ?><br>
      <?= e(GENDER_LABELS[$m['gender']] ?? '') ?><?= $m['date_of_birth'] ? ' &middot; born ' . e(fmt_date($m['date_of_birth'])) : '' ?> &middot; <?= e($m['nationality']) ?><br>
      <?= e(trim($m['address'] . ', ' . $m['location'] . ', ' . $m['country'], ', ')) ?></p>
    <h3>Academic</h3>
    <p><?= e($m['programme']) ?> (<?= e($m['qualification_level']) ?>)<br><?= e($m['college']) ?> &middot; graduated <?= e($m['graduation_year']) ?>
      <?= $m['student_reg_no'] ? '<br>Reg. no. ' . e($m['student_reg_no']) : '' ?></p>
    <p><strong>Basis for eligibility:</strong><br><?= nl2br(e($m['eligibility_details'])) ?></p>
    <h3>Employment</h3>
    <p><?= e(EMPLOYMENT_LABELS[$m['employment_status']] ?? '') ?><?= $m['employer'] ? ' &middot; ' . e($m['employer']) : '' ?><?= $m['profession'] ? ' &middot; ' . e($m['profession']) : '' ?></p>
    <p><strong>Interests:</strong> <?= e($m['interests'] ?: 'none given') ?><br><strong>Chapter:</strong> <?= e($m['chapter_name'] ?: 'none') ?> &middot; <strong>Directory consent:</strong> <?= $m['directory_consent'] ? 'yes' : 'no' ?><br>
      <strong>Applied:</strong> <?= e(fmt_date($m['created_at'], 'j M Y')) ?></p>
  </div>

  <div>
    <form method="post" class="stack card">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
      <h3 style="margin:0">Membership decision</h3>
      <label>Membership category <?= $m['membership_id'] ? '<span class="hint">(the issued ID keeps its original code)</span>' : '' ?>
        <select name="category"><?php foreach (CATEGORY_LABELS as $k => $l): ?><option value="<?= $k ?>"<?= $m['category'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
      <label>Subscription status
        <select name="subscription_status"><?php foreach (['unpaid' => 'Unpaid / awaiting confirmation', 'active' => 'Active (paid up)', 'arrears' => 'In arrears'] as $k => $l): ?><option value="<?= $k ?>"<?= $m['subscription_status'] === $k ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></label>
      <label>Paid up to <span class="hint">(ordinary members; leave blank for lifetime categories)</span> <input type="date" name="paid_until" value="<?= e($m['paid_until']) ?>"></label>
      <label>Chapter <select name="chapter_id"><?php foreach ($chapters as $k => $l): ?><option value="<?= e($k) ?>"<?= (string) $m['chapter_id'] === (string) $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
      <label>Internal notes <span class="hint">(never shown to the member)</span><textarea name="admin_notes" rows="3"><?= e($m['admin_notes']) ?></textarea></label>
      <?php if (!$m['membership_id']): ?>
        <label class="check"><input type="checkbox" name="waive"> Approve without a verified payment (waiver, e.g. honorary invitation)</label>
        <p class="meta">Approving issues the membership ID and activates the card. Member no. <?= e(member_no_label($m['member_no'])) ?> was assigned at registration.</p>
      <?php endif; ?>
      <div class="row-actions">
        <button class="secondary" name="action" value="save">Save</button>
        <button class="secondary" name="action" value="reset_password" onclick="return confirm('Generate a new temporary password for this member?')">Reset password</button>
        <?php if (!$m['membership_id']): ?><button name="action" value="approve">Approve &amp; issue ID</button><?php endif; ?>
        <?php if ($m['status'] !== 'rejected'): ?><button class="danger" name="action" value="reject" onclick="return confirm('Reject this application?')">Reject</button><?php endif; ?>
        <?php if ((int) $m['id'] !== (int) $admin['id']): ?><button class="danger" name="action" value="delete" onclick="return confirm('Permanently delete this member and their data?')">Delete</button><?php endif; ?>
      </div>
    </form>

    <?php if ($m['membership_id']): ?>
    <form method="post" class="stack card" style="margin-top:16px">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
      <h3 style="margin:0">Membership card</h3>
      <?php if ($m['card_status'] === 'revoked'): ?>
        <p>This card is <strong>revoked</strong>. The member cannot use member-only features and verification shows the card as revoked.</p>
        <button name="action" value="reinstate">Reinstate card</button>
      <?php else: ?>
        <label>Reason for revoking <textarea name="reason" rows="2" placeholder="Required, e.g. disciplinary decision, fraudulent payment"></textarea></label>
        <button class="danger" name="action" value="revoke" onclick="return confirm('Revoke this membership card?')">Revoke card</button>
      <?php endif; ?>
    </form>
    <?php endif; ?>
  </div>
</div>

<h2 style="margin-top:32px">Payments and proof</h2>
<div class="table-wrap"><table>
  <tr><th>Date</th><th>Purpose</th><th>Method / reference</th><th>Amount (UGX)</th><th>Proof</th><th>Status</th><th>Decision</th></tr>
  <?php foreach ($pays as $x): ?>
    <tr>
      <td><?= e(fmt_date($x['paid_on'] ?: $x['created_at'])) ?></td><td><?= e(ucfirst($x['purpose'])) ?></td>
      <td><?= e($x['method']) ?><br><span class="meta"><?= e($x['reference']) ?></span></td><td><?= e(number_format((float) $x['amount'])) ?></td>
      <td><?= $x['proof_file'] ? '<a href="' . e(member_file_url('proof', (int) $x['id'])) . '" target="_blank" rel="noopener">View proof</a>' : '<span class="meta">none</span>' ?></td>
      <td><span class="badge <?= $payBadge[$x['status']] ?>"><?= e($x['status']) ?></span><?= $x['admin_note'] ? '<br><span class="meta">' . e($x['admin_note']) . '</span>' : '' ?></td>
      <td>
        <?php if ($x['status'] === 'pending'): ?>
          <form method="post" class="stack" style="gap:6px;min-width:200px">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><input type="hidden" name="payment_id" value="<?= (int) $x['id'] ?>">
            <label>Cover paid up to <input type="date" name="paid_until" value="<?= e(default_paid_until($x, $m['category'])) ?>"></label>
            <input name="note" placeholder="Note (optional)">
            <div class="row-actions"><button name="action" value="pay_verified">Verify</button><button class="danger" name="action" value="pay_rejected">Reject</button></div>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; if (!$pays): ?><tr><td colspan="7">No payments recorded.</td></tr><?php endif; ?>
</table></div>
    <?php
    admin_footer();
    exit;
}

$rows = q("SELECT u.id, u.full_name, u.email, u.role, u.status, u.created_at, p.member_no, p.membership_id, p.category, p.card_status, p.subscription_status, p.paid_until, p.approved_at, p.graduation_year,
    (SELECT COUNT(*) FROM member_payments mp WHERE mp.user_id = u.id AND mp.status = 'pending') AS pending_pay $base
    ORDER BY FIELD(u.status,'pending','approved','rejected'), u.created_at DESC", $args)->fetchAll();
admin_header('Members & applications');
?>
<form method="get" class="filters">
  <label>Search <input type="search" name="q" value="<?= e($search) ?>" placeholder="Name, email, ID or member no."></label>
  <label>Application <select name="status"><option value="">All</option><?php foreach (['pending', 'approved', 'rejected'] as $s): ?><option<?= $s === $statusF ? ' selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></label>
  <label>Card <select name="card"><option value="">All</option><?php foreach (['none' => 'Not issued', 'active' => 'Active', 'revoked' => 'Revoked'] as $k => $l): ?><option value="<?= $k ?>"<?= $k === $cardF ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></label>
  <button>Filter</button>
  <a class="btn secondary" href="?<?= e(http_build_query(array_filter(['q' => $search, 'status' => $statusF, 'card' => $cardF]) + ['export' => 1])) ?>">Export CSV</a>
  <a class="btn" href="member_add.php">+ Add member</a>
</form>
<div class="table-wrap"><table>
  <tr><th>No.</th><th>Name</th><th>Membership ID</th><th>Category</th><th>Application</th><th>Card</th><th>Subscription</th><th></th></tr>
  <?php foreach ($rows as $r): $cs = card_state($r + ['status' => $r['status']]); ?>
  <tr>
    <td><?= e(member_no_label($r['member_no'])) ?></td>
    <td><?= e($r['full_name']) ?><?= $r['role'] !== 'member' ? ' <span class="badge">' . e(ROLE_LABELS[$r['role']]) . '</span>' : '' ?><br><span class="meta"><?= e($r['email']) ?></span></td>
    <td class="mono"><?= e($r['membership_id']) ?></td><td><?= e(CATEGORY_LABELS[$r['category']] ?? '') ?></td>
    <td><span class="badge <?= $r['status'] === 'approved' ? '' : ($r['status'] === 'pending' ? 'warn' : 'bad') ?>"><?= e($r['status']) ?></span>
      <?= $r['pending_pay'] ? '<br><span class="badge warn">payment to verify</span>' : '' ?></td>
    <td><?= $cs['state'] === 'none' ? '<span class="meta">-</span>' : '<span class="badge ' . ($cs['state'] === 'valid' ? '' : 'bad') . '">' . e($cs['label']) . '</span>' ?></td>
    <td><?= e($r['subscription_status']) ?></td>
    <td><a class="btn secondary" href="?id=<?= (int) $r['id'] ?>">Review</a></td>
  </tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="8">No members found.</td></tr><?php endif; ?>
</table></div>
<?php admin_footer(); ?>
