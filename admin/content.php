<?php
require_once __DIR__ . '/../includes/admin.php';
require_perm('content');
$registry = content_registry();
$pageKey = $_GET['page'] ?? $_POST['page'] ?? 'home';
if (!isset($registry[$pageKey])) {
    $pageKey = 'home';
}
$norm = fn($s) => trim(str_replace("\r\n", "\n", (string) $s));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $changed = [];
    foreach ($registry[$pageKey]['blocks'] as $key => $b) {
        [$label, $type, $default] = $b;
        $field = str_replace('.', '__', $key);
        $reset = !empty($_POST['reset'][$field]);
        if ($type === 'image' || $type === 'audio') {
            $has = ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            if ($has) {
                $name = $type === 'audio' ? save_media($_FILES[$field], 'audio') : save_upload($_FILES[$field]);
                if (!$name) {
                    flash($type === 'audio' ? "$label: use an MP3, M4A, OGG or WAV file under 25MB." : "$label: use a JPG, PNG or WebP image under 5MB.", 'error');
                    redirect('admin/content.php?page=' . $pageKey);
                }
                q('INSERT INTO site_content (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$key, $name]);
                $changed[] = $key;
            } elseif ($reset) {
                q('DELETE FROM site_content WHERE k = ?', [$key]);
                $changed[] = $key;
            }
            continue;
        }
        if (!isset($_POST[$field]) && !$reset) {
            continue; // field not submitted: leave the block untouched
        }
        $value = $norm($_POST[$field] ?? '');
        $current = $norm(c($key));
        if ($reset || $value === $norm($default)) {
            if (q('DELETE FROM site_content WHERE k = ?', [$key])->rowCount()) {
                $changed[] = $key;
            }
        } elseif ($value !== $current) {
            q('INSERT INTO site_content (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$key, $value]);
            $changed[] = $key;
        }
    }
    audit('update_content', $pageKey . ': ' . ($changed ? implode(', ', $changed) : 'no changes'));
    flash($changed ? count($changed) . ' block(s) updated.' : 'Nothing changed.');
    redirect('admin/content.php?page=' . $pageKey);
}

$saved = db()->query('SELECT k FROM site_content')->fetchAll(PDO::FETCH_COLUMN);
admin_header('Site content');
?>
<p class="meta">Edit the wording and images on each page. Blocks marked <em>default</em> show the text shipped with the site; once you change one it is saved here, and "Reset to default" restores the original.
  Formatting in text areas: a blank line starts a new paragraph, <code>## Heading</code>, <code>- bullet</code>, <code>**bold**</code> and <code>[link text](page.php)</code>.</p>
<nav class="tabs" aria-label="Pages">
  <?php foreach ($registry as $k => $pg): ?><a href="?page=<?= e($k) ?>"<?= $k === $pageKey ? ' aria-current="page"' : '' ?>><?= e($pg['label']) ?></a><?php endforeach; ?>
</nav>

<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?><input type="hidden" name="page" value="<?= e($pageKey) ?>">
  <p><a href="<?= e(url($registry[$pageKey]['url'])) ?>" target="_blank" rel="noopener">View this page on the website &rarr;</a></p>
  <?php foreach ($registry[$pageKey]['blocks'] as $key => $blk):
      [$label, $type, $default, $hint] = array_pad($blk, 4, '');
      $field = str_replace('.', '__', $key); $custom = in_array($key, $saved, true); ?>
    <div class="block">
      <label for="f-<?= e($field) ?>"><span><?= e($label) ?> <span class="badge <?= $custom ? '' : 'warn' ?>"><?= $custom ? 'customised' : 'default' ?></span> <span class="key"><?= e($key) ?></span></span>
      <?php if ($type === 'text'): ?>
        <input id="f-<?= e($field) ?>" name="<?= e($field) ?>" value="<?= e(c($key)) ?>">
      <?php elseif ($type === 'image' || $type === 'audio'): ?>
        <?php if (c($key) !== '' && $type === 'image'): ?><img class="thumb" src="<?= e(c_image($key)) ?>" alt=""><?php endif; ?>
        <?php if (c($key) !== '' && $type === 'audio'): ?><audio controls src="<?= e(c_image($key)) ?>" style="max-width:100%"></audio><?php endif; ?>
        <input id="f-<?= e($field) ?>" type="file" name="<?= e($field) ?>" accept="<?= $type === 'audio' ? 'audio/mpeg,audio/mp4,audio/ogg,audio/wav,.mp3,.m4a,.ogg,.wav' : 'image/jpeg,image/png,image/webp' ?>">
      <?php else: ?>
        <textarea id="f-<?= e($field) ?>" name="<?= e($field) ?>" rows="<?= $type === 'list' ? 8 : (strlen($default) > 600 ? 12 : 4) ?>"><?= e(c($key)) ?></textarea>
      <?php endif; ?>
      </label>
      <?php if ($hint): ?><span class="hint"><?= e($hint) ?></span><?php endif; ?>
      <?php if ($custom): ?><label class="check"><input type="checkbox" name="reset[<?= e($field) ?>]" value="1"> Reset to default<?= $type === 'image' ? ' (remove image)' : ($type === 'audio' ? ' (remove audio)' : '') ?></label><?php endif; ?>
    </div>
  <?php endforeach; ?>
  <div class="savebar"><button>Save changes</button><span class="meta">Changes go live immediately.</span></div>
</form>
<?php admin_footer(); ?>
