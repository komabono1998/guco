<?php
require_once __DIR__ . '/../includes/admin.php';
require_perm('messages');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    if (isset($_POST['delete'])) {
        q('DELETE FROM messages WHERE id = ?', [$id]);
        audit('delete_message', "id $id");
    } else {
        q('UPDATE messages SET status = ? WHERE id = ?', [($_POST['status'] ?? '') === 'handled' ? 'handled' : 'new', $id]);
    }
    redirect('admin/messages.php');
}
$cat = $_GET['category'] ?? '';
$rows = q('SELECT * FROM messages' . ($cat !== '' ? ' WHERE category = ?' : '') . " ORDER BY status = 'handled', created_at DESC", $cat !== '' ? [$cat] : [])->fetchAll();
admin_header('Messages & enquiries');
?>
<form method="get" class="filters">
  <label>Category <select name="category"><option value="">All</option><?php require_once __DIR__ . '/../includes/forms.php'; foreach (ENQUIRY_CATEGORIES as $c): ?><option<?= $c === $cat ? ' selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></label>
  <button>Filter</button>
</form>
<div class="table-wrap"><table>
  <?php foreach ($rows as $m): ?>
  <tr>
    <td><strong><?= e($m['name']) ?></strong><br><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a><br><?= e($m['phone']) ?><br><span class="meta"><?= e($m['created_at']) ?></span></td>
    <td><span class="badge"><?= e($m['category']) ?></span> <strong><?= e($m['subject']) ?></strong><br><?= nl2br(e($m['body'])) ?></td>
    <td class="row-actions">
      <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
        <button class="secondary" name="status" value="<?= $m['status'] === 'new' ? 'handled' : 'new' ?>"><?= $m['status'] === 'new' ? 'Mark handled' : 'Reopen' ?></button>
        <button class="danger" name="delete" value="1" onclick="return confirm('Delete this message?')">Delete</button></form>
    </td>
  </tr>
  <?php endforeach; if (!$rows): ?><tr><td>No messages.</td></tr><?php endif; ?>
</table></div>
<?php admin_footer(); ?>
