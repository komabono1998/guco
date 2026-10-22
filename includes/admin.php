<?php
require_once __DIR__ . '/bootstrap.php';

const ADMIN_GROUPS = [
    'Overview' => [['Dashboard', 'index.php', null]],
    'Website content' => [
        ['Site content', 'content.php', 'content'], ['Pages', 'pages.php', 'pages'], ['Menu', 'menu.php', 'menu'],
        ['News', 'news.php', 'news'], ['Newsletters', 'newsletters.php', 'newsletters'], ['Events & calendar', 'events.php', 'events'],
        ['Reports & documents', 'resources.php', 'resources'], ['Gallery', 'gallery.php', 'gallery'],
        ['Campus', 'campus.php', 'campus'], ['Partners', 'partners.php', 'partners'],
    ],
    'Organisation' => [
        ['Leadership', 'leaders.php', 'leaders'], ['Chapters', 'chapters.php', 'chapters'], ['Chapter members', 'chapter_members.php', 'chapters'], ['Projects', 'projects.php', 'projects'],
        ['Programmes', 'programmes.php', 'programmes'], ['Awards', 'awards.php', 'awards'],
    ],
    'Membership' => [['Members', 'members.php', 'members'], ['Payments', 'payments.php', 'payments']],
    'Inbox' => [['Messages', 'messages.php', 'messages'], ['Subscribers', 'subscribers.php', 'subscribers']],
    'Site' => [['Branding & layout', 'branding.php', 'branding'], ['Settings', 'settings.php', 'settings']],
    'System' => [['Users & roles', 'users.php', 'users'], ['Mail log', 'mail_log.php', 'mail'], ['Audit log', 'audit.php', 'audit']],
];

/** Admin chrome: top bar, grouped sidebar and a content area (closed by admin_footer). */
function admin_header(string $title): void
{
    $me = current_user();
    $flash = flash();
    $current = basename($_SERVER['SCRIPT_NAME']);
    $siteName = setting('site_name', 'Gulu University Convocation');
    $brandCss = '';
    foreach (['color_primary' => ['--green', '--green-2'], 'color_accent' => ['--gold']] as $key => $vars) {
        if (preg_match('/^#[0-9a-fA-F]{6}$/', setting($key))) {
            foreach ($vars as $v) {
                $brandCss .= "$v:" . setting($key) . ';';
            }
        }
    }
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Admin: <?= e($title) ?> | <?= e(config('short_name')) ?></title>
<link rel="icon" href="<?= e(logo_url()) ?>">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
<?php if ($brandCss): ?><style>:root{<?= $brandCss ?>}</style><?php endif; ?>
</head>
<body class="admin">
<a class="skip" href="#main">Skip to main content</a>
<header class="admin-top">
  <a class="admin-brand" href="<?= url('admin/') ?>"><img src="<?= e(logo_url()) ?>" alt="" width="34" height="34"><span><?= e(config('short_name')) ?> Admin</span></a>
  <button class="menu-toggle secondary" type="button" aria-expanded="false" aria-controls="adminside" onclick="var n=document.getElementById('adminside');var o=n.classList.toggle('open');this.setAttribute('aria-expanded',o)">Menu</button>
  <div class="admin-user">
    <a href="<?= url() ?>" target="_blank" rel="noopener">View website</a>
    <a href="<?= url('member/') ?>">Member Portal</a>
    <span><?= e($me['full_name'] ?? '') ?> <small>(<?= e(ROLE_LABELS[$me['role'] ?? 'member'] ?? '') ?>)</small></span>
    <a href="<?= url('logout.php') ?>">Log out</a>
  </div>
</header>
<div class="admin-layout">
  <aside id="adminside" class="admin-side" aria-label="Admin navigation">
    <?php foreach (ADMIN_GROUPS as $group => $items):
        $visible = array_filter($items, fn($i) => $i[2] === null || can($i[2]));
        if (!$visible) {
            continue;
        } ?>
      <h2><?= e($group) ?></h2>
      <?php foreach ($visible as [$label, $file]): ?>
        <a href="<?= e(url('admin/' . $file)) ?>"<?= $current === $file ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </aside>
  <main id="main" class="admin-main">
    <?php if ($flash): ?><div class="flash <?= e($flash[1]) ?>" role="status"><?= e($flash[0]) ?></div><?php endif; ?>
    <h1><?= e($title) ?></h1>
<?php
}

function admin_footer(): void
{
    echo "  </main>\n</div>\n</body>\n</html>\n";
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
                foreach ($fields as $n => $f) {
                    $col = $f['map']['filename'] ?? 'filename';
                    if ($f['type'] === 'doc' && !empty($row[$col])) {
                        @unlink(config('storage_dir') . '/' . basename($row[$col]));
                    }
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
                        if ($doc) {
                            foreach ($doc as $dk => $dv) {
                                $vals[$f['map'][$dk] ?? $dk] = $dv;
                            }
                        } else {
                            $err = "$label: use a PDF or Office file under 20MB.";
                        }
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
        <?php $mf = $f['map']['filename'] ?? 'filename'; $mo = $f['map']['original_name'] ?? 'original_name'; if (!empty($edit[$f['type'] === 'image' ? $n : $mf])): ?><span class="hint">Current: <?= e($f['type'] === 'image' ? $edit[$n] : ($edit[$mo] ?? $edit[$mf])) ?> (upload to replace)</span><?php endif; ?>
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
