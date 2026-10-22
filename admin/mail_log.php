<?php
require_once __DIR__ . '/../includes/admin.php';
require_perm('mail');
$statusF = $_GET['status'] ?? '';
$where = in_array($statusF, ['sent', 'failed'], true) ? 'WHERE m.status = ?' : '';
$rows = q("SELECT m.*, u.full_name FROM mail_log m LEFT JOIN users u ON u.id = m.sent_by $where ORDER BY m.id DESC LIMIT 300", $where ? [$statusF] : [])->fetchAll();
$failed = (int) db()->query("SELECT COUNT(*) FROM mail_log WHERE status = 'failed'")->fetchColumn();
admin_header('Mail log');
?>
<p class="meta">Every email the website sends (invitations, newsletters, approvals, password resets) is recorded here.</p>
<?php if ($failed): ?><div class="notice"><strong><?= $failed ?> email(s) were not delivered.</strong> The server could not hand them to a mail server. To send real email, configure SMTP in <code>php.ini</code> (the <code>[mail function]</code> section) or install a sendmail replacement, then send again.</div><?php endif; ?>
<form method="get" class="filters"><label>Status <select name="status"><option value="">All</option><option<?= $statusF === 'sent' ? ' selected' : '' ?>>sent</option><option<?= $statusF === 'failed' ? ' selected' : '' ?>>failed</option></select></label><button>Filter</button></form>
<div class="table-wrap"><table>
  <tr><th>When</th><th>To</th><th>Subject</th><th>Context</th><th>Status</th><th>By</th></tr>
  <?php foreach ($rows as $r): ?>
    <tr><td><?= e(fmt_date($r['created_at'], 'j M, g:ia')) ?></td><td><?= e($r['to_email']) ?></td><td><?= e($r['subject']) ?></td><td><?= e($r['context']) ?></td>
      <td><span class="badge <?= $r['status'] === 'sent' ? '' : 'bad' ?>"><?= e($r['status']) ?></span><?= $r['error'] ? '<br><span class="meta">' . e($r['error']) . '</span>' : '' ?></td><td><?= e($r['full_name'] ?? 'system') ?></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="6">Nothing sent yet.</td></tr><?php endif; ?>
</table></div>
<?php admin_footer(); ?>
