<?php
require_once __DIR__ . '/../includes/admin.php';
require_perm('payments');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if (in_array($action, ['pay_verified', 'pay_rejected'], true)) {
        decide_payment((int) $_POST['payment_id'], substr($action, 4), ($_POST['paid_until'] ?? '') ?: null, trim($_POST['note'] ?? ''));
        flash($action === 'pay_verified' ? 'Payment verified.' : 'Payment rejected.');
    }
    redirect('admin/payments.php' . (isset($_GET['all']) ? '?all=1' : ''));
}
$all = isset($_GET['all']);
$rows = q('SELECT mp.*, u.full_name, u.email, p.category, p.member_no FROM member_payments mp JOIN users u ON u.id = mp.user_id LEFT JOIN member_profiles p ON p.user_id = u.id'
    . ($all ? '' : " WHERE mp.status = 'pending'") . ' ORDER BY mp.created_at DESC LIMIT 300')->fetchAll();
if (isset($_GET['export'])) {
    audit('export_payments', count($rows) . ' records');
    csv_download('guco-payments-' . date('Ymd') . '.csv', ['Member no', 'Name', 'Email', 'Purpose', 'Method', 'Amount', 'Reference', 'Paid on', 'Status', 'Note'],
        array_map(fn($r) => [member_no_label($r['member_no']), $r['full_name'], $r['email'], $r['purpose'], $r['method'], $r['amount'], $r['reference'], $r['paid_on'], $r['status'], $r['admin_note']], $rows));
}
$badge = ['pending' => 'warn', 'verified' => '', 'rejected' => 'bad'];
admin_header('Payments');
?>
<p><?= $all ? '<a href="?">Show pending only</a>' : '<a href="?all=1">Show all payments</a>' ?> &middot; <a href="?<?= $all ? 'all=1&' : '' ?>export=1">Export CSV</a></p>
<div class="table-wrap"><table>
  <tr><th>Member</th><th>Purpose</th><th>Method / reference</th><th>Amount (UGX)</th><th>Proof</th><th>Status</th><th>Decision</th></tr>
  <?php foreach ($rows as $x): ?>
  <tr>
    <td><a href="<?= url('admin/members.php?id=' . (int) $x['user_id']) ?>"><?= e($x['full_name']) ?></a><br><span class="meta"><?= e(member_no_label($x['member_no'])) ?> &middot; <?= e(CATEGORY_LABELS[$x['category']] ?? '') ?></span></td>
    <td><?= e(ucfirst($x['purpose'])) ?><br><span class="meta"><?= e(fmt_date($x['paid_on'] ?: $x['created_at'])) ?></span></td>
    <td><?= e($x['method']) ?><br><span class="meta"><?= e($x['reference']) ?></span></td>
    <td><?= e(number_format((float) $x['amount'])) ?></td>
    <td><?= $x['proof_file'] ? '<a href="' . e(member_file_url('proof', (int) $x['id'])) . '" target="_blank" rel="noopener">View proof</a>' : '<span class="meta">none</span>' ?></td>
    <td><span class="badge <?= $badge[$x['status']] ?>"><?= e($x['status']) ?></span><?= $x['admin_note'] ? '<br><span class="meta">' . e($x['admin_note']) . '</span>' : '' ?></td>
    <td>
      <?php if ($x['status'] === 'pending'): ?>
        <form method="post" class="stack" style="gap:6px;min-width:200px">
          <?= csrf_field() ?><input type="hidden" name="payment_id" value="<?= (int) $x['id'] ?>">
          <label>Cover paid up to <input type="date" name="paid_until" value="<?= e(default_paid_until($x, $x['category'] ?? 'ordinary')) ?>"></label>
          <input name="note" placeholder="Note (optional)">
          <div class="row-actions"><button name="action" value="pay_verified">Verify</button><button class="danger" name="action" value="pay_rejected">Reject</button></div>
        </form>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="7"><?= $all ? 'No payments yet.' : 'No payments waiting for verification.' ?></td></tr><?php endif; ?>
</table></div>
<?php admin_footer(); ?>
