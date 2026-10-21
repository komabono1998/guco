<?php
require_once __DIR__ . '/../includes/admin.php';
require_perm('audit');
$rows = q('SELECT a.*, u.full_name, u.email FROM audit_log a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 300')->fetchAll();
admin_header('Audit log');
?>
<p class="meta">The most recent 300 sensitive actions.</p>
<div class="table-wrap"><table>
  <tr><th>When</th><th>Who</th><th>Action</th><th>Detail</th><th>IP</th></tr>
  <?php foreach ($rows as $r): ?>
  <tr><td><?= e($r['created_at']) ?></td><td><?= e($r['full_name'] ?? 'system') ?></td><td><?= e($r['action']) ?></td><td><?= e($r['detail']) ?></td><td><?= e($r['ip']) ?></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="5">Nothing logged yet.</td></tr><?php endif; ?>
</table></div>
<?php admin_footer(); ?>
