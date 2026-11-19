<?php
require_once __DIR__ . '/../includes/admin.php';
$admin = require_perm('mail');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'test') {
    csrf_check();
    $ok = deliver_mail($admin['email'], 'GUCO email test', "This is a test email from the GUCO website.\n\nIf you can read it, email sending works. Two-step sign-in codes and member emails will reach people.", ['context' => 'test']);
    $last = q('SELECT error FROM mail_log ORDER BY id DESC LIMIT 1')->fetchColumn();
    audit('mail_test', $ok ? 'sent' : 'failed');
    flash($ok ? 'A test email was sent to ' . $admin['email'] . '. Check your inbox (and spam folder).' : 'The test email could not be sent: ' . ($last ?: 'unknown error'), $ok ? 'success' : 'error');
    redirect('admin/mail_log.php');
}
$statusF = $_GET['status'] ?? '';
$where = in_array($statusF, ['sent', 'failed'], true) ? 'WHERE m.status = ?' : '';
$rows = q("SELECT m.*, u.full_name FROM mail_log m LEFT JOIN users u ON u.id = m.sent_by $where ORDER BY m.id DESC LIMIT 300", $where ? [$statusF] : [])->fetchAll();
$failed = (int) db()->query("SELECT COUNT(*) FROM mail_log WHERE status = 'failed'")->fetchColumn();
admin_header('Mail log');
?>
<p class="meta">Every email the website sends (invitations, newsletters, approvals, password resets) is recorded here.</p>
<?php if ($failed): ?><div class="notice"><strong><?= $failed ?> email(s) were not delivered.</strong> To send real email, add an <code>smtp</code> section to <code>config.local.php</code> on the server (host, port, encryption, username, password, from address; see <code>includes/mailer.php</code>), or configure sendmail. Then use the test button below.</div><?php endif; ?>
<form method="post" class="card" style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;margin:12px 0">
  <?= csrf_field() ?><input type="hidden" name="action" value="test">
  <div style="flex:1;min-width:240px"><strong>Check that email works</strong><br><span class="meta">Sends a test message to <?= e($admin['email']) ?>. Sending through <?= is_array(config('smtp')) && !empty(config('smtp')['host']) ? 'SMTP (' . e(config('smtp')['host']) . ')' : 'the server\'s mail function (no SMTP configured)' ?>.</span></div>
  <button>Send a test email to me</button>
</form>
<form method="get" class="filters"><label>Status <select name="status"><option value="">All</option><option<?= $statusF === 'sent' ? ' selected' : '' ?>>sent</option><option<?= $statusF === 'failed' ? ' selected' : '' ?>>failed</option></select></label><button>Filter</button></form>
<div class="table-wrap"><table>
  <tr><th>When</th><th>To</th><th>Subject</th><th>Context</th><th>Status</th><th>By</th></tr>
  <?php foreach ($rows as $r): ?>
    <tr><td><?= e(fmt_date($r['created_at'], 'j M, g:ia')) ?></td><td><?= e($r['to_email']) ?></td><td><?= e($r['subject']) ?></td><td><?= e($r['context']) ?></td>
      <td><span class="badge <?= $r['status'] === 'sent' ? '' : 'bad' ?>"><?= e($r['status']) ?></span><?= $r['error'] ? '<br><span class="meta">' . e($r['error']) . '</span>' : '' ?></td><td><?= e($r['full_name'] ?? 'system') ?></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="6">Nothing sent yet.</td></tr><?php endif; ?>
</table></div>
<?php admin_footer(); ?>
