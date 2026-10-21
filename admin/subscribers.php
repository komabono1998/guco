<?php
require_once __DIR__ . '/../includes/admin.php';
require_perm('subscribers');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    q('DELETE FROM subscribers WHERE id = ?', [(int) ($_POST['delete'] ?? 0)]);
    audit('delete_subscriber', 'id ' . (int) $_POST['delete']);
    redirect('admin/subscribers.php');
}
$rows = q('SELECT id, email, created_at FROM subscribers ORDER BY created_at DESC')->fetchAll();
if (isset($_GET['export'])) {
    audit('export_subscribers', count($rows) . ' records');
    csv_download('guco-subscribers-' . date('Ymd') . '.csv', ['ID', 'Email', 'Subscribed'], $rows);
}
admin_header('Newsletter subscribers');
?>
<p><?= count($rows) ?> subscribers &middot; <a href="?export=1">Export CSV</a></p>
<div class="table-wrap"><table>
  <tr><th>Email</th><th>Subscribed</th><th></th></tr>
  <?php foreach ($rows as $r): ?>
  <tr><td><?= e($r['email']) ?></td><td><?= e($r['created_at']) ?></td>
    <td><form method="post"><?= csrf_field() ?><button class="danger" name="delete" value="<?= (int) $r['id'] ?>" onclick="return confirm('Remove this subscriber?')">Remove</button></form></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="3">No subscribers yet.</td></tr><?php endif; ?>
</table></div>
<?php admin_footer(); ?>
