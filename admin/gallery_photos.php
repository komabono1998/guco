<?php
require_once __DIR__ . '/../includes/admin.php';
require_perm('gallery');
$album = q('SELECT * FROM gallery_albums WHERE id = ?', [(int) ($_GET['album'] ?? $_POST['album'] ?? 0)])->fetch();
if (!$album) {
    http_response_code(404);
    exit('Album not found.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['delete'])) {
        $row = q('SELECT * FROM gallery WHERE id = ? AND album_id = ?', [(int) $_POST['delete'], $album['id']])->fetch();
        if ($row) {
            if (!str_starts_with($row['filename'], 'assets/')) {
                @unlink(config('upload_dir') . '/' . basename($row['filename']));
            }
            q('DELETE FROM gallery WHERE id = ?', [$row['id']]);
            audit('delete_gallery_photo', "album {$album['id']}, photo {$row['id']}");
            flash('Photo removed.');
        }
    } elseif (isset($_POST['cover'])) {
        $row = q('SELECT filename FROM gallery WHERE id = ? AND album_id = ?', [(int) $_POST['cover'], $album['id']])->fetch();
        if ($row) {
            q('UPDATE gallery_albums SET cover = ? WHERE id = ?', [$row['filename'], $album['id']]);
            flash('Cover updated.');
        }
    } elseif (isset($_POST['save_captions'])) {
        foreach ($_POST['caption'] ?? [] as $pid => $cap) {
            q('UPDATE gallery SET caption = ?, sort_order = ? WHERE id = ? AND album_id = ?', [mb_substr(trim($cap), 0, 200), (int) ($_POST['order'][$pid] ?? 100), (int) $pid, $album['id']]);
        }
        flash('Captions and order saved.');
    } else {
        $added = $failed = 0;
        $files = $_FILES['photos'] ?? null;
        if ($files && is_array($files['name'])) {
            foreach ($files['name'] as $i => $nm) {
                if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $saved = save_upload(['name' => $nm, 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]]);
                if ($saved) {
                    q('INSERT INTO gallery (album_id, caption, filename) VALUES (?,?,?)', [$album['id'], trim($_POST['caption_new'] ?? ''), $saved]);
                    $added++;
                } else {
                    $failed++;
                }
            }
        }
        audit('add_gallery_photos', "album {$album['id']}: $added added");
        flash($added ? "$added photo(s) added." . ($failed ? " $failed could not be uploaded (JPG, PNG or WebP under 5MB)." : '') : 'No photos were added. Use JPG, PNG or WebP under 5MB.', $added ? 'success' : 'error');
    }
    redirect('admin/gallery_photos.php?album=' . $album['id']);
}
$photos = q('SELECT * FROM gallery WHERE album_id = ? ORDER BY sort_order, id', [$album['id']])->fetchAll();
admin_header('Album: ' . $album['title']);
?>
<p><a href="<?= url('admin/gallery.php') ?>">&larr; All albums</a></p>
<form method="post" enctype="multipart/form-data" class="stack wide card">
  <?= csrf_field() ?><input type="hidden" name="album" value="<?= (int) $album['id'] ?>">
  <h3 style="margin:0">Add photos</h3>
  <label>Choose one or more photos <span class="hint">(JPG, PNG or WebP up to 5MB each; hold Ctrl or Shift to pick several). Upload only photos you have permission to publish.</span><input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" required></label>
  <label>Caption <span class="hint">(optional, applied to this batch; edit individually below)</span><input name="caption_new"></label>
  <button>Upload</button>
</form>
<?php if ($photos): ?>
<form method="post" style="margin-top:20px"><?= csrf_field() ?><input type="hidden" name="album" value="<?= (int) $album['id'] ?>"><input type="hidden" name="save_captions" value="1">
  <div class="grid">
    <?php foreach ($photos as $p): ?>
      <div class="card">
        <img src="<?= e(gallery_src($p['filename'])) ?>" alt="" style="width:100%;height:140px;object-fit:cover;border-radius:8px" loading="lazy">
        <label>Caption <input name="caption[<?= (int) $p['id'] ?>]" value="<?= e($p['caption']) ?>"></label>
        <label>Order <input type="number" name="order[<?= (int) $p['id'] ?>]" value="<?= (int) $p['sort_order'] ?>"></label>
        <div class="row-actions">
          <button class="secondary" name="cover" value="<?= (int) $p['id'] ?>" formnovalidate><?= $album['cover'] === $p['filename'] ? 'Cover' : 'Set cover' ?></button>
          <button class="danger" name="delete" value="<?= (int) $p['id'] ?>" formnovalidate onclick="return confirm('Remove this photo?')">Remove</button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="savebar"><button>Save captions and order</button></div>
</form>
<?php else: ?><p class="meta">No photos yet.</p><?php endif; ?>
<?php admin_footer(); ?>
