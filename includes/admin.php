<?php
require_once __DIR__ . '/bootstrap.php';

const ADMIN_SECTIONS = [
    ['Dashboard', 'index.php', null],
    ['Members', 'members.php', 'members'],
    ['News', 'news.php', 'news'],
    ['Events', 'events.php', 'events'],
    ['Chapters', 'chapters.php', 'chapters'],
    ['Leadership', 'leaders.php', 'leaders'],
    ['Programmes', 'programmes.php', 'programmes'],
    ['Awards', 'awards.php', 'awards'],
    ['Resources', 'resources.php', 'resources'],
    ['Gallery', 'gallery.php', 'gallery'],
    ['Messages', 'messages.php', 'messages'],
    ['Subscribers', 'subscribers.php', 'subscribers'],
    ['Settings', 'settings.php', 'settings'],
    ['Users & roles', 'users.php', 'users'],
    ['Audit log', 'audit.php', 'audit'],
];

function admin_header(string $title): void
{
    $pageTitle = 'Admin: ' . $title;
    $current = basename($_SERVER['SCRIPT_NAME']);
    require __DIR__ . '/header.php';
    echo '<nav class="adminnav" aria-label="Admin">';
    foreach (ADMIN_SECTIONS as [$label, $file, $perm]) {
        if ($perm === null || can($perm)) {
            echo '<a href="' . e(url('admin/' . $file)) . '"' . ($current === $file ? ' aria-current="page"' : '') . '>' . e($label) . '</a>';
        }
    }
    echo '</nav><h1>' . e($title) . '</h1>';
}

function admin_footer(): void
{
    require __DIR__ . '/footer.php';
}

function admin_slug(string $table, string $title): string
{
    $base = slugify($title);
    $slug = $base;
    for ($i = 2; q("SELECT 1 FROM `$table` WHERE slug = ?", [$slug])->fetch(); $i++) {
        $slug = "$base-$i";
    }
    return $slug;
}

/**
 * Generic list + create/edit/delete page for a table.
 * Field types: text, textarea, select, datetime, date, number, check, image, doc.
 */
function crud(array $c): void
{
    $me = require_perm($c['perm']);
    $self = 'admin/' . basename($_SERVER['SCRIPT_NAME']);
    $t = $c['table'];
    $fields = array_filter($c['fields'], fn($f) => empty($f['perm']) || can($f['perm']));

    $scopeSql = '';
    $scopeArgs = [];
    $limited = !empty($c['scope_unless']) && !can($c['scope_unless']);
    if ($limited) {
        [$sql, $scopeArgs] = $c['scope']($me);
        $scopeSql = " AND ($sql)";
    }
    $fetch = fn(int $id) => q("SELECT * FROM `$t` WHERE id = ?$scopeSql", array_merge([$id], $scopeArgs))->fetch();
    $deny = function () {
        http_response_code(403);
        exit('Not allowed.');
    };

    $edit = null;
    $editing = isset($_GET['id']) || isset($_GET['new']);
    if (isset($_GET['id'])) {
        $edit = $fetch((int) $_GET['id']);
        if (!$edit) {
            $deny();
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        if (isset($_POST['delete'])) {
            $row = $limited ? null : $fetch((int) $_POST['delete']);
            if ($row) {
                foreach ($fields as $n => $f) {
                    if ($f['type'] === 'image' && !empty($row[$n])) {
                        @unlink(config('upload_dir') . '/' . basename($row[$n]));
                    }
                }
                if (!empty($row['filename']) && $t === 'resources') {
                    @unlink(config('storage_dir') . '/' . basename($row['filename']));
                }
                q("DELETE FROM `$t` WHERE id = ?", [$row['id']]);
                audit("delete_$t", "id {$row['id']}");
                flash('Deleted.');
            }
            redirect($self);
        }

        $id = (int) ($_POST['id'] ?? 0);
        $old = $id ? $fetch($id) : null;
        if (($id && !$old) || (!$id && $limited)) {
            $deny();
        }
        $vals = [];
        $err = null;
        foreach ($fields as $n => $f) {
            $label = $f['label'];
            $raw = $_POST[$n] ?? '';
            switch ($f['type']) {
                case 'check':
                    $vals[$n] = isset($_POST[$n]) ? 1 : 0;
                    break;
                case 'image':
                    $has = ($_FILES[$n]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
                    if ($has) {
                        $name = save_upload($_FILES[$n]);
                        $name ? $vals[$n] = $name : $err = "$label: use a JPG, PNG or WebP under 5MB.";
                    } elseif (!$old && !empty($f['required'])) {
                        $err = "$label is required.";
                    }
                    break;
                case 'doc':
                    $has = ($_FILES[$n]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
                    if ($has) {
                        $doc = save_document($_FILES[$n]);
                        $doc ? $vals += $doc : $err = "$label: use a PDF or Office file under 20MB.";
                    } elseif (!$old && !empty($f['required'])) {
                        $err = "$label is required.";
                    }
                    break;
                case 'datetime':
                    $v = str_replace('T', ' ', trim($raw));
                    $vals[$n] = $v === '' ? null : (strlen($v) === 16 ? "$v:00" : $v);
                    break;
                case 'date':
                    $vals[$n] = trim($raw) === '' ? null : trim($raw);
                    break;
                case 'number':
                    $vals[$n] = trim($raw) === '' ? ($f['default'] ?? 0) : (int) $raw;
                    break;
                case 'select':
                    if (!array_key_exists($raw, $f['options'])) {
                        if (!empty($f['allow_empty'])) {
                            $raw = '';
                        } else {
                            $err = "$label: choose a valid option.";
                        }
                    }
                    $vals[$n] = $raw === '' ? null : $raw;
                    break;
                default:
                    $vals[$n] = trim($raw);
            }
            if (!empty($f['required']) && !in_array($f['type'], ['image', 'doc', 'check'], true) && ($vals[$n] ?? '') === '') {
                $err = "$label is required.";
            }
        }
        if (!$err && !empty($c['hook'])) {
            $err = $c['hook']($vals, $old);
        }
        if ($err) {
            flash($err, 'error');
            $editing = true;
            $edit = array_merge($old ?: [], $_POST, ['id' => $id ?: null]);
        } else {
            if ($old) {
                $set = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($vals)));
                q("UPDATE `$t` SET $set WHERE id = ?", array_merge(array_values($vals), [$old['id']]));
                audit("update_$t", "id {$old['id']}");
            } else {
                $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($vals)));
                q("INSERT INTO `$t` ($cols) VALUES (" . implode(',', array_fill(0, count($vals), '?')) . ')', array_values($vals));
                audit("create_$t", 'id ' . db()->lastInsertId());
            }
            flash('Saved.');
            redirect($self);
        }
    }

    admin_header($c['title']);
    if ($editing) {
        $val = fn($n) => $edit[$n] ?? ($fields[$n]['default'] ?? '');
        ?>
<p><a href="<?= url($self) ?>">&larr; Back to list</a></p>
<form method="post" enctype="multipart/form-data" class="stack wide">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
  <?php foreach ($fields as $n => $f): $label = e($f['label']) . (!empty($f['required']) ? ' *' : ''); $hint = !empty($f['hint']) ? ' <span class="hint">' . e($f['hint']) . '</span>' : ''; ?>
    <?php if ($f['type'] === 'check'): ?>
      <label class="check"><input type="checkbox" name="<?= $n ?>" <?= ($edit ? !empty($edit[$n]) : !empty($f['default'])) ? 'checked' : '' ?>> <?= e($f['label']) ?><?= $hint ?></label>
    <?php elseif ($f['type'] === 'textarea'): ?>
      <label><?= $label . $hint ?><textarea name="<?= $n ?>" rows="<?= (int) ($f['rows'] ?? 5) ?>"><?= e($val($n)) ?></textarea></label>
    <?php elseif ($f['type'] === 'select'): ?>
      <label><?= $label . $hint ?><select name="<?= $n ?>">
        <?php foreach ($f['options'] as $k => $l): ?><option value="<?= e($k) ?>"<?= (string) $val($n) === (string) $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
      </select></label>
    <?php elseif ($f['type'] === 'image' || $f['type'] === 'doc'): ?>
      <label><?= $label . $hint ?>
        <?php if (!empty($edit[$f['type'] === 'image' ? $n : 'filename'])): ?><span class="hint">Current: <?= e($f['type'] === 'image' ? $edit[$n] : ($edit['original_name'] ?? $edit['filename'])) ?> (upload to replace)</span><?php endif; ?>
        <input type="file" name="<?= $n ?>" accept="<?= $f['type'] === 'image' ? 'image/jpeg,image/png,image/webp' : '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx' ?>"></label>
    <?php elseif ($f['type'] === 'datetime'): ?>
      <label><?= $label . $hint ?><input type="datetime-local" name="<?= $n ?>" value="<?= e($val($n) ? str_replace(' ', 'T', substr((string) $val($n), 0, 16)) : '') ?>"></label>
    <?php else: ?>
      <label><?= $label . $hint ?><input type="<?= $f['type'] === 'date' ? 'date' : ($f['type'] === 'number' ? 'number' : 'text') ?>" name="<?= $n ?>" value="<?= e($val($n)) ?>"></label>
    <?php endif; ?>
  <?php endforeach; ?>
  <button>Save</button>
</form>
        <?php
    } else {
        $order = $c['order'] ?? 'id DESC';
        $rows = q("SELECT * FROM `$t` WHERE 1=1$scopeSql ORDER BY $order", $scopeArgs)->fetchAll();
        $cols = array_filter($fields, fn($f) => !empty($f['list']));
        if (!$limited) {
            echo '<p><a class="btn" href="' . e(url($self . '?new=1')) . '">Add new</a></p>';
        }
        echo '<div class="table-wrap"><table><tr>';
        foreach ($cols as $f) {
            echo '<th>' . e($f['label']) . '</th>';
        }
        echo '<th></th></tr>';
        foreach ($rows as $r) {
            echo '<tr>';
            foreach ($cols as $n => $f) {
                $v = $r[$n] ?? '';
                echo '<td>';
                if ($f['type'] === 'check') {
                    echo $v ? 'Yes' : 'No';
                } elseif ($f['type'] === 'select') {
                    echo e($f['options'][$v] ?? $v);
                } elseif ($f['type'] === 'datetime') {
                    echo e(fmt_date($v, 'j M Y, g:ia'));
                } elseif ($f['type'] === 'date') {
                    echo e(fmt_date($v));
                } elseif ($f['type'] === 'image') {
                    echo $v ? '<img src="' . e(url('uploads/' . rawurlencode($v))) . '" alt="" style="width:64px;height:48px;object-fit:cover;border-radius:4px">' : '';
                } else {
                    echo e(excerpt((string) $v, 80));
                }
                echo '</td>';
            }
            echo '<td class="row-actions"><a class="btn secondary" href="' . e(url($self . '?id=' . $r['id'])) . '">Edit</a>';
            if (!empty($c['extra_actions'])) {
                echo $c['extra_actions']($r);
            }
            if (!$limited) {
                echo '<form method="post">' . csrf_field() . '<button class="danger" name="delete" value="' . (int) $r['id'] . '" onclick="return confirm(\'Delete this item?\')">Delete</button></form>';
            }
            echo '</td></tr>';
        }
        if (!$rows) {
            echo '<tr><td colspan="' . (count($cols) + 1) . '">Nothing here yet.</td></tr>';
        }
        echo '</table></div>';
    }
    admin_footer();
}
