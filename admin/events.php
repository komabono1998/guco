<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$pageTitle = 'Manage events';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['delete'])) {
        db()->prepare('DELETE FROM events WHERE id = ?')->execute([(int) $_POST['delete']]);
    } else {
        $title = trim($_POST['title'] ?? '');
        $when = str_replace('T', ' ', $_POST['starts_at'] ?? '');
        if ($title === '' || $when === '') {
            flash('Title and date are required.', 'error');
        } else {
            db()->prepare('INSERT INTO events (title, description, location, starts_at) VALUES (?,?,?,?)')
                ->execute([$title, trim($_POST['description'] ?? ''), trim($_POST['location'] ?? ''), $when]);
            flash('Event added.');
        }
    }
    redirect('admin/events.php');
}
$rows = db()->query('SELECT * FROM events ORDER BY starts_at DESC')->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<h1>Events</h1>
<form method="post" class="stack">
  <?= csrf_field() ?>
  <label>Title <input name="title" required></label>
  <label>Date &amp; time <input type="datetime-local" name="starts_at" required></label>
  <label>Location <input name="location"></label>
  <label>Description <textarea name="description" rows="4"></textarea></label>
  <button>Add event</button>
</form>
<h2>All events</h2>
<table>
  <?php foreach ($rows as $r): ?>
  <tr><td><?= e($r['title']) ?></td><td><?= e($r['starts_at']) ?></td><td><?= e($r['location']) ?></td>
    <td><form method="post"><?= csrf_field() ?><button class="danger" name="delete" value="<?= (int) $r['id'] ?>" onclick="return confirm('Delete?')">Delete</button></form></td></tr>
  <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
