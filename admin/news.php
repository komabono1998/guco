<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$pageTitle = 'Manage news';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['delete'])) {
        db()->prepare('DELETE FROM news WHERE id = ?')->execute([(int) $_POST['delete']]);
    } else {
        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');
        if ($title === '' || $body === '') {
            flash('Title and body are required.', 'error');
            redirect('admin/news.php');
        }
        $slug = slugify($title);
        $exists = db()->prepare('SELECT 1 FROM news WHERE slug = ?');
        $exists->execute([$slug]);
        if ($exists->fetch()) {
            $slug .= '-' . time();
        }
        db()->prepare('INSERT INTO news (title, slug, body) VALUES (?,?,?)')->execute([$title, $slug, $body]);
        flash('Post published.');
    }
    redirect('admin/news.php');
}
$rows = db()->query('SELECT id, title, published_at FROM news ORDER BY published_at DESC')->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<h1>News</h1>
<form method="post" class="stack">
  <?= csrf_field() ?>
  <label>Title <input name="title" required></label>
  <label>Body <textarea name="body" rows="6" required></textarea></label>
  <button>Publish</button>
</form>
<h2>Posts</h2>
<table>
  <?php foreach ($rows as $r): ?>
  <tr><td><?= e($r['title']) ?></td><td><?= e($r['published_at']) ?></td>
    <td><form method="post"><?= csrf_field() ?><button class="danger" name="delete" value="<?= (int) $r['id'] ?>" onclick="return confirm('Delete?')">Delete</button></form></td></tr>
  <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
