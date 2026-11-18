<?php
require_once __DIR__ . '/../includes/admin.php';
$me = require_perm('donations');
$campaigns = q('SELECT id, title FROM campaigns ORDER BY id DESC')->fetchAll(PDO::FETCH_KEY_PAIR);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'verify') {
        verify_donation($id, $me) ? flash('Donation verified and receipt sent.') : flash('That donation is not pending.', 'error');
    } elseif ($action === 'reject') {
        reject_donation($id, trim($_POST['reason'] ?? '')) ? flash('Donation rejected and the donor informed.') : flash('That donation is not pending.', 'error');
    } elseif ($action === 'record') {
        $name = trim($_POST['donor_name'] ?? '');
        $email = strtolower(trim($_POST['donor_email'] ?? ''));
        $amount = (int) preg_replace('/\D/', '', $_POST['amount'] ?? '');
        $method = $_POST['method'] ?? '';
        $ref = trim($_POST['reference'] ?? '');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $amount < 1 || !isset(DONATION_METHODS[$method]) || $ref === '') {
            flash('Enter the donor name, a valid email, amount, method and reference.', 'error');
        } elseif (q('SELECT 1 FROM donations WHERE method = ? AND reference = ?', [$method, $ref])->fetch()) {
            flash('That method and reference already exist.', 'error');
        } else {
            $cid = (int) ($_POST['campaign_id'] ?? 0);
            q('INSERT INTO donations (campaign_id, donor_name, donor_email, amount, method, reference, category, is_anonymous, access_token) VALUES (?,?,?,?,?,?,?,?,?)',
                [isset($campaigns[$cid]) ? $cid : null, $name, $email, $amount, $method, $ref, 'General', !empty($_POST['is_anonymous']) ? 1 : 0, bin2hex(random_bytes(16))]);
            $newId = (int) db()->lastInsertId();
            audit('record_donation', "#$newId $method $ref");
            verify_donation($newId, $me);
            flash('Donation recorded and verified.');
        }
    }
    redirect('admin/donations.php' . (isset($_GET['all']) ? '?all=1' : ''));
}
$all = isset($_GET['all']);
$rows = q('SELECT d.*, c.title AS campaign FROM donations d LEFT JOIN campaigns c ON c.id = d.campaign_id' . ($all ? '' : " WHERE d.status = 'pending'") . ' ORDER BY d.created_at DESC LIMIT 500')->fetchAll();
if (isset($_GET['export'])) {
    audit('export_donations', count($rows) . ' records');
    csv_download('guco-donations-' . date('Ymd') . '.csv', ['Receipt', 'Date', 'Donor', 'Email', 'Campaign', 'Cause', 'Amount', 'Method', 'Reference', 'Recurring', 'Anonymous', 'Status'],
        array_map(fn($r) => [$r['receipt_no'], $r['created_at'], $r['donor_name'], $r['donor_email'], $r['campaign'], $r['category'], $r['amount'], $r['method'], $r['reference'], $r['recurring'], $r['is_anonymous'] ? 'yes' : 'no', $r['status']], $rows));
}
$totals = q("SELECT COALESCE(SUM(CASE WHEN status='verified' THEN amount END),0) v, COALESCE(SUM(CASE WHEN status='pending' THEN amount END),0) p, COALESCE(SUM(status='pending'),0) pc, COALESCE(SUM(status='verified'),0) vc FROM donations")->fetch();
$badge = ['pending' => 'warn', 'verified' => '', 'rejected' => 'bad'];
admin_header('Donations', 'Match each gift to the real payment received, then verify it to issue the receipt');
?>
<div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:14px">
  <div class="card"><strong>UGX <?= e(number_format((int) $totals['v'])) ?></strong><br><span class="meta">Verified (<?= (int) $totals['vc'] ?> gifts)</span></div>
  <div class="card"><strong>UGX <?= e(number_format((int) $totals['p'])) ?></strong><br><span class="meta">Awaiting verification (<?= (int) $totals['pc'] ?>)</span></div>
</div>
<p><?= $all ? '<a href="?">Show pending only</a>' : '<a href="?all=1">Show all donations</a>' ?> &middot; <a href="?<?= $all ? 'all=1&' : '' ?>export=1">Export CSV</a> &middot; <a href="campaigns.php">Manage campaigns</a></p>
<div class="table-wrap"><table>
  <tr><th>Date</th><th>Donor</th><th>For</th><th>Amount (UGX)</th><th>Method / reference</th><th>Status</th><th>Decision</th></tr>
  <?php foreach ($rows as $x): ?>
  <tr>
    <td><?= e(fmt_date($x['created_at'], 'j M Y, g:ia')) ?></td>
    <td><?= e($x['donor_name']) ?><?= $x['is_anonymous'] ? ' <span class="badge">anonymous</span>' : '' ?><br><span class="meta"><?= e($x['donor_email']) ?><?= $x['donor_phone'] ? ' &middot; ' . e($x['donor_phone']) : '' ?></span></td>
    <td><?= e($x['campaign'] ?: $x['category']) ?><?= $x['recurring'] !== 'none' ? '<br><span class="meta">' . e(DONATION_RECURRING[$x['recurring']]) . ' pledge</span>' : '' ?></td>
    <td><?= e(number_format((int) $x['amount'])) ?></td>
    <td><?= e(DONATION_METHODS[$x['method']] ?? $x['method']) ?><br><span class="meta"><?= e($x['reference']) ?></span></td>
    <td><span class="badge <?= $badge[$x['status']] ?>"><?= e($x['status']) ?></span><?= $x['receipt_no'] ? '<br><a class="meta" href="' . e(url('receipt.php?id=' . (int) $x['id'])) . '" target="_blank" rel="noopener">' . e($x['receipt_no']) . '</a>' : '' ?><?= $x['reject_reason'] ? '<br><span class="meta">' . e($x['reject_reason']) . '</span>' : '' ?></td>
    <td>
      <?php if ($x['status'] === 'pending'): ?>
        <form method="post" class="stack" style="gap:6px;min-width:190px"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $x['id'] ?>">
          <button name="action" value="verify" onclick="return confirm('Confirm that you have seen this payment in the account statement?')">Verify payment</button>
          <input name="reason" placeholder="Reason if rejecting">
          <button class="danger" name="action" value="reject">Reject</button>
        </form>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="7">Nothing to show.</td></tr><?php endif; ?>
</table></div>

<h2 style="margin-top:28px">Record a gift received directly</h2>
<p class="meta">For cash handed in or a bank deposit seen on the statement. It is verified immediately and the receipt is emailed.</p>
<form method="post" class="stack wide"><?= csrf_field() ?><input type="hidden" name="action" value="record">
  <div class="cols"><label>Donor name * <input name="donor_name" required></label><label>Donor email * <input type="email" name="donor_email" required></label></div>
  <div class="cols"><label>Amount (UGX) * <input name="amount" inputmode="numeric" required></label>
    <label>Method * <select name="method"><?php foreach (DONATION_METHODS as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?></select></label></div>
  <div class="cols"><label>Reference / slip number * <input name="reference" required></label>
    <label>Campaign <select name="campaign_id"><option value="0">General giving</option><?php foreach ($campaigns as $cid => $ct): ?><option value="<?= (int) $cid ?>"><?= e($ct) ?></option><?php endforeach; ?></select></label></div>
  <label class="check"><input type="checkbox" name="is_anonymous"> Donor asked to be anonymous</label>
  <button>Record and verify</button>
</form>
<?php admin_footer(); ?>
