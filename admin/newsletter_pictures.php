<?php
require_once __DIR__ . '/../includes/admin.php';
require_perm('newsletters');
$nl = q('SELECT * FROM newsletters WHERE id = ?', [(int) ($_GET['id'] ?? $_POST['id'] ?? 0)])->fetch();
if (!$nl) {
    http_response_code(404);
    exit('Newsletter not found.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['delete'])) {
        $row = q('SELECT * FROM newsletter_images WHERE id = ? AND newsletter_id = ?', [(int) $_POST['delete'], $nl['id']])->fetch();
        if ($row) {
            @unlink(config('upload_dir') . '/' . basename($row['filename']));
            q('DELETE FROM newsletter_images WHERE id = ?', [$row['id']]);
            flash('Picture removed.');
        }
    } elseif (isset($_POST['save_captions'])) {
        foreach ($_POST['caption'] ?? [] as $imgId => $cap) {
            q('UPDATE newsletter_images SET caption = ?, sort_order = ? WHERE id = ? AND newsletter_id = ?', [mb_substr(trim($cap), 0, 200), (int) ($_POST['order'][$imgId] ?? 100), (int) $imgId, $nl['id']]);
        }
        flash('Captions saved.');
    } else {
        $added = $failed = 0;
        $files = $_FILES['photos'] ?? null;
        if ($files && is_array($files['name'])) {
            foreach ($files['name'] as $i => $nm) {
                if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $one = ['name' => $nm, 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
                $saved = save_upload($one);
                if ($saved) {
                    q('INSERT INTO newsletter_images (newsletter_id, filename, caption) VALUES (?,?,?)', [$nl['id'], $saved, trim($_POST['caption_new'] ?? '')]);
                    $added++;
                } else {
                    $failed++;
                }
            }
        }
        audit('add_newsletter_pictures', "newsletter {$nl['id']}: $added added");
        flash($added ? "$added picture(s) added." . ($failed ? " $failed could not be uploaded (use JPG, PNG or WebP under 5MB)." : '') : 'No pictures were added. Use JPG, PNG or WebP under 5MB.', $added ? 'success' : 'error');
    }
    redirect('admin/newsletter_pictures.php?id=' . $nl['id']);
}
$images = q('SELECT * FROM newsletter_images WHERE newsletter_id = ? ORDER BY sort_order, id', [$nl['id']])->fetchAll();
admin_header('Newsletter pictures: ' . $nl['title']);
?>
<p><a href="<?= url('admin/newsletters.php') ?>">&larr; Newsletters</a></p>
<form method="post" enctype="multipart/form-data" class="stack wide card">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $nl['id'] ?>">
  <h3 style="margin:0">Add pictures</h3>
  <label>Choose one or more pictures <span class="hint">(JPG, PNG or WebP up to 5MB each; hold Ctrl or Shift to pick several)</span><input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" required></label>
  <label>Caption <span class="hint">(optional, applied to this batch; edit individually below)</span><input name="caption_new"></label>
  <button>Upload</button>
</form>
<?php if ($images): ?>
<form method="post" style="margin-top:20px"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $nl['id'] ?>"><input type="hidden" name="save_captions" value="1">
  <div class="grid">
    <?php foreach ($images as $im): ?>
      <div class="card">
        <img src="<?= e(url('uploads/' . rawurlencode($im['filename']))) ?>" alt="" style="width:100%;height:150px;object-fit:cover;border-radius:8px">
        <label>Caption <input name="caption[<?= (int) $im['id'] ?>]" value="<?= e($im['caption']) ?>"></label>
        <label>Order <input type="number" name="order[<?= (int) $im['id'] ?>]" value="<?= (int) $im['sort_order'] ?>"></label>
        <button class="danger" name="delete" value="<?= (int) $im['id'] ?>" formnovalidate onclick="return confirm('Remove this picture?')">Remove</button>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="savebar"><button>Save captions and order</button></div>
</form>
<?php else: ?><p class="meta">No pictures yet.</p><?php endif; ?>
<?php admin_footer(); ?>
