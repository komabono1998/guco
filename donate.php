<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Give';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $amount = (float) ($_POST['amount'] ?? 0);
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $amount <= 0) {
        flash('Please enter your name, a valid email and an amount.', 'error');
    } else {
        db()->prepare('INSERT INTO donation_pledges (name, email, amount, purpose) VALUES (?,?,?,?)')
            ->execute([$name, $email, $amount, trim($_POST['purpose'] ?? '')]);
        flash('Thank you! Your pledge is recorded. Please use the payment details below to complete it.');
        redirect('donate.php');
    }
}
require __DIR__ . '/includes/header.php';
$d = config('donation');
?>
<h1>Support GUCO</h1>
<p>Your contributions fund alumni projects and support Gulu University students.</p>
<div class="card" style="max-width:520px;margin-bottom:24px">
  <p><strong>Mobile money:</strong> <?= e($d['mobile_money']) ?></p>
  <p><strong>Bank:</strong> <?= e($d['bank']) ?></p>
</div>
<h2>Make a pledge</h2>
<form method="post" class="stack">
  <?= csrf_field() ?>
  <label>Name <input name="name" required></label>
  <label>Email <input type="email" name="email" required></label>
  <label>Amount (UGX) <input type="number" name="amount" min="1" step="any" required></label>
  <label>Purpose (optional) <input name="purpose" placeholder="e.g. Scholarship fund"></label>
  <button>Submit pledge</button>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
