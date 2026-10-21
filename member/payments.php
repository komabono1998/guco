<?php
require_once __DIR__ . '/../includes/portal.php';
$me = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $amount = (float) ($_POST['amount'] ?? 0);
    $proof = ($_FILES['proof']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE ? save_private_file($_FILES['proof'], 'proof') : null;
    if ($amount <= 0 || trim($_POST['reference'] ?? '') === '' || !$proof) {
        flash('Enter the amount and reference and upload a proof of payment (JPG, PNG, WebP or PDF, up to 5MB).', 'error');
    } else {
        q('INSERT INTO member_payments (user_id, purpose, method, amount, reference, paid_on, proof_file, proof_original) VALUES (?,?,?,?,?,?,?,?)', [
            $me['id'], in_array($_POST['purpose'] ?? '', ['renewal', 'other'], true) ? $_POST['purpose'] : 'renewal',
            in_array($_POST['method'] ?? '', PAYMENT_METHODS, true) ? $_POST['method'] : 'Other', $amount,
            trim($_POST['reference']), ($_POST['paid_on'] ?? '') ?: null, $proof['name'], $proof['original'],
        ]);
        flash('Payment submitted. The Secretariat will verify it and update your membership.');
    }
    redirect('member/payments.php');
}

$p = q('SELECT * FROM member_profiles WHERE user_id = ?', [$me['id']])->fetch() ?: [];
$payments = q('SELECT * FROM member_payments WHERE user_id = ? ORDER BY created_at DESC', [$me['id']])->fetchAll();
$badge = ['pending' => ['Awaiting verification', 'warn'], 'verified' => ['Verified', ''], 'rejected' => ['Rejected', 'bad']];
portal_header('Payments & renewal');
?>
<div class="tiles">
  <div class="tile"><span class="ic"><?= icon('wallet', 24) ?></span><div><small>Subscription</small><b><?= e(['unpaid' => 'Awaiting confirmation', 'active' => 'Active', 'arrears' => 'In arrears'][$p['subscription_status'] ?? 'unpaid']) ?></b></div></div>
  <div class="tile"><span class="ic"><?= icon('calendar', 24) ?></span><div><small>Paid up to</small><b><?= !empty($p['paid_until']) ? e(fmt_date($p['paid_until'])) : (($p['category'] ?? 'ordinary') === 'ordinary' ? 'Not yet recorded' : 'Lifetime') ?></b></div></div>
</div>

<?php if (setting('payment_instructions')): ?>
  <div class="notice"><strong>Payment instructions</strong><br><?= nl2br(e(setting('payment_instructions'))) ?></div>
<?php else: ?>
  <div class="notice">Confirm the current fee and the approved payment channel with the <a href="support.php">Secretariat</a> before you pay (Article 9 of the Constitution).</div>
<?php endif; ?>

<section class="panel">
  <h2>Payment history</h2>
  <div class="table-wrap"><table>
    <tr><th>Date</th><th>Purpose</th><th>Method</th><th>Amount (UGX)</th><th>Reference</th><th>Status</th><th>Proof</th></tr>
    <?php foreach ($payments as $x): [$pl, $pc] = $badge[$x['status']]; ?>
      <tr><td><?= e(fmt_date($x['paid_on'] ?: $x['created_at'])) ?></td><td><?= e(ucfirst($x['purpose'])) ?></td><td><?= e($x['method']) ?></td>
        <td><?= e(number_format((float) $x['amount'])) ?></td><td><?= e($x['reference']) ?></td>
        <td><span class="badge <?= $pc ?>"><?= e($pl) ?></span><?= $x['admin_note'] ? '<br><span class="meta">' . e($x['admin_note']) . '</span>' : '' ?></td>
        <td><?= $x['proof_file'] ? '<a href="' . e(member_file_url('proof', (int) $x['id'])) . '" target="_blank" rel="noopener">View</a>' : '' ?></td></tr>
    <?php endforeach; if (!$payments): ?><tr><td colspan="7">No payments recorded yet.</td></tr><?php endif; ?>
  </table></div>
</section>

<section class="panel" id="renew">
  <h2>Renew or submit another payment</h2>
  <p class="meta">Ordinary members pay an annual subscription. Pay through the approved channel, then upload your proof here for verification.</p>
  <form method="post" enctype="multipart/form-data" class="stack wide">
    <?= csrf_field() ?>
    <div class="cols">
      <label>Purpose <select name="purpose"><option value="renewal">Subscription renewal</option><option value="other">Other</option></select></label>
      <label>Payment method <select name="method"><?php foreach (PAYMENT_METHODS as $m): ?><option><?= e($m) ?></option><?php endforeach; ?></select></label>
      <label>Amount paid (UGX) <input type="number" name="amount" min="1" step="any" required></label>
    </div>
    <div class="cols">
      <label>Reference <input name="reference" required></label>
      <label>Date paid <input type="date" name="paid_on" max="<?= date('Y-m-d') ?>"></label>
    </div>
    <label>Proof of payment <input type="file" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf" required></label>
    <button>Submit payment</button>
  </form>
</section>
<?php portal_footer(); ?>
