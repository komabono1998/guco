<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$pageTitle = 'Manage gallery';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['delete'])) {
        $s = db()->prepare('SELECT filename FROM gallery WHERE id = ?');
        $s->execute([(int) $_POST['delete']]);
        if ($f = $s->fetchColumn()) {
            @unlink(config('upload_dir') . '/' . $f);
            db()->prepare('DELETE FROM gallery WHERE id = ?')->execute([(int) $_POST['delete']]);
        }
    } else {
        $name = isset($_FILES['photo']) ? save_upload($_FILES['photo']) : null;
        if ($name) {
            db()->prepare('INSERT INTO gallery (caption, filename) VALUES (?,?)')->execute([trim($_POST['caption'] ?? ''), $name]);
            flash('Photo uploaded.');
        } else {
            flash('Upload failed. Use a JPG, PNG or WebP under 5MB.', 'error');
        }
    }
    redirect('admin/gallery.php');
}
$rows = db()->query('SELECT * FROM gallery ORDER BY created_at DESC')->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<h1>Gallery</h1>
<form method="post" enctype="multipart/form-data" class="stack">
  <?= csrf_field() ?>
  <label>Photo <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required></label>
  <label>Caption <input name="caption"></label>
  <button>Upload</button>
</form>
<div class="grid gallery" style="margin-top:24px">
  <?php foreach ($rows as $r): ?>
  <figure class="card" style="margin:0">
    <img src="<?= url('uploads/' . rawurlencode($r['filename'])) ?>" alt="">
    <figcaption class="meta"><?= e($r['caption']) ?></figcaption>
    <form method="post"><?= csrf_field() ?><button class="danger" name="delete" value="<?= (int) $r['id'] ?>" onclick="return confirm('Delete?')">Delete</button></form>
  </figure>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
