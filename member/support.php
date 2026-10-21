<?php
require_once __DIR__ . '/../includes/portal.php';
require_once __DIR__ . '/../includes/forms.php';
$me = require_login();
$cats = ['Profile correction', 'Membership', 'Payments', 'Membership card', 'Events', 'Other'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $body = trim($_POST['body'] ?? '');
    $cat = in_array($_POST['category'] ?? '', $cats, true) ? $_POST['category'] : 'Other';
    if ($body === '') {
        flash('Please describe what you need help with.', 'error');
    } else {
        q('INSERT INTO messages (user_id, name, email, category, subject, body) VALUES (?,?,?,?,?,?)', [
            $me['id'], $me['full_name'], $me['email'], in_array($cat, ENQUIRY_CATEGORIES, true) ? $cat : 'Other',
            mb_substr(trim($_POST['subject'] ?? $cat), 0, 190), $body,
        ]);
        flash('Your request has been sent to the Secretariat.');
    }
    redirect('member/support.php');
}
$history = q('SELECT * FROM messages WHERE user_id = ? ORDER BY created_at DESC LIMIT 20', [$me['id']])->fetchAll();
portal_header('Support');
?>
<div class="dash-grid">
  <section class="panel">
    <h2>Send a request</h2>
    <p class="meta">Ask for a correction to your name, email, date of birth or category, or get help with payments and your card.</p>
    <form method="post" class="stack">
      <?= csrf_field() ?>
      <label>Topic <select name="category"><?php foreach ($cats as $c): ?><option<?= ($_GET['topic'] ?? '') === $c ? ' selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></label>
      <label>Subject <input name="subject"></label>
      <label>Message <textarea name="body" rows="6" required></textarea></label>
      <button>Send request</button>
    </form>
  </section>
  <section class="panel">
    <h2>My requests</h2>
    <?php foreach ($history as $m): ?>
      <p style="margin:.5em 0"><span class="badge <?= $m['status'] === 'handled' ? '' : 'warn' ?>"><?= $m['status'] === 'handled' ? 'Handled' : 'Open' ?></span>
        <strong><?= e($m['subject'] ?: $m['category']) ?></strong><br><span class="meta"><?= e(fmt_date($m['created_at'], 'j M Y')) ?> &middot; <?= e(excerpt($m['body'], 70)) ?></span></p>
    <?php endforeach; if (!$history): ?><p class="meta">No requests yet.</p><?php endif; ?>
  </section>
</div>
<?php portal_footer(); ?>
