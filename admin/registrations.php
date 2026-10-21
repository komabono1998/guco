<?php
require_once __DIR__ . '/../includes/admin.php';
require_perm('events');
$ev = q('SELECT * FROM events WHERE id = ?', [(int) ($_GET['event'] ?? 0)])->fetch();
if (!$ev) {
    http_response_code(404);
    exit('Event not found.');
}
$rows = q('SELECT name, email, phone, created_at FROM event_registrations WHERE event_id = ? ORDER BY created_at', [$ev['id']])->fetchAll();
if (isset($_GET['export'])) {
    audit('export_registrations', 'event ' . $ev['id']);
    csv_download('registrations-' . $ev['id'] . '.csv', ['Name', 'Email', 'Phone', 'Registered'], $rows);
}
admin_header('Registrations: ' . $ev['title']);
?>
<p><a href="<?= url('admin/events.php') ?>">&larr; Events</a> &middot; <a href="?event=<?= (int) $ev['id'] ?>&export=1">Export CSV</a></p>
<div class="table-wrap"><table>
  <tr><th>Name</th><th>Email</th><th>Phone</th><th>Registered</th></tr>
  <?php foreach ($rows as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['phone']) ?></td><td><?= e($r['created_at']) ?></td></tr><?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="4">No registrations yet.</td></tr><?php endif; ?>
</table></div>
<?php admin_footer(); ?>
