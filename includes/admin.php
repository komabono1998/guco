<?php
require_once __DIR__ . '/bootstrap.php';

/** Sidebar structure: group => [label, file, permission (null = everyone), icon, badge key]. */
const ADMIN_GROUPS = [
    'Overview' => [
        ['Dashboard', 'index.php', null, 'grid', null],
        ['Reports & analytics', 'reports.php', 'reports', 'bar-chart', null],
    ],
    'Website content' => [
        ['Site content', 'content.php', 'content', 'edit', null], ['Pages', 'pages.php', 'pages', 'file', null], ['Menu', 'menu.php', 'menu', 'menu', null],
        ['News', 'news.php', 'news', 'newspaper', 'news'], ['Newsletters', 'newsletters.php', 'newsletters', 'mail', null], ['Events & calendar', 'events.php', 'events', 'calendar', null],
        ['Reports & documents', 'resources.php', 'resources', 'folder', null], ['Gallery', 'gallery.php', 'gallery', 'image', null],
        ['Campus', 'campus.php', 'campus', 'map', null], ['Partners', 'partners.php', 'partners', 'link', null],
    ],
    'Organisation' => [
        ['Leadership', 'leaders.php', 'leaders', 'users', null], ['Chapters', 'chapters.php', 'chapters', 'map-pin', null],
        ['Chapter members', 'chapter_members.php', 'chapters', 'user-plus', 'chapters'], ['Projects', 'projects.php', 'projects', 'target', 'projects'],
        ['Programmes', 'programmes.php', 'programmes', 'briefcase', null], ['Awards', 'awards.php', 'awards', 'award', null],
    ],
    'Membership' => [
        ['Members', 'members.php', 'members', 'id-card', 'members'], ['Payments', 'payments.php', 'payments', 'credit-card', 'payments'],
    ],
    'Inbox' => [
        ['Messages', 'messages.php', 'messages', 'inbox', 'messages'], ['Subscribers', 'subscribers.php', 'subscribers', 'mail', null],
    ],
    'Site' => [
        ['Branding & layout', 'branding.php', 'branding', 'layout', null], ['Settings', 'settings.php', 'settings', 'sliders', null],
    ],
    'System' => [
        ['Users & roles', 'users.php', 'users', 'shield', null], ['Mail log', 'mail_log.php', 'mail', 'activity', null],
        ['Audit log', 'audit.php', 'audit', 'clock', null], ['Help & guide', 'help.php', null, 'help', null],
    ],
];

/** Counts of items waiting for action, keyed for the sidebar badges (only what the user may see). */
function admin_badges(): array
{
    static $b = null;
    if ($b !== null) {
        return $b;
    }
    $n = fn($sql) => (int) db()->query($sql)->fetchColumn();
    $b = [];
    if (can('members')) {
        $b['members'] = $n("SELECT COUNT(*) FROM users WHERE status = 'pending'");
    }
    if (can('payments')) {
        $b['payments'] = $n("SELECT COUNT(*) FROM member_payments WHERE status = 'pending'");
    }
    if (can('projects')) {
        $b['projects'] = $n("SELECT COUNT(*) FROM projects WHERE status IN ('submitted','under_review')")
            + $n("SELECT COUNT(*) FROM project_updates WHERE kind = 'mobilisation' AND verified = 0");
    }
    if (can('chapters')) {
        $b['chapters'] = $n("SELECT COUNT(*) FROM chapter_members WHERE status = 'pending'");
    }
    if (can('messages')) {
        $b['messages'] = $n("SELECT COUNT(*) FROM messages WHERE status = 'new'");
    }
    if (can('news')) {
        $b['news'] = $n("SELECT COUNT(*) FROM news WHERE status = 'pending'");
    }
    return $b;
}

/** Locates the sidebar group and label for a page file (for the breadcrumb). */
function admin_crumb(string $file): array
{
    foreach (ADMIN_GROUPS as $group => $items) {
        foreach ($items as $i) {
            if ($i[1] === $file) {
                return [$group, $i[0]];
            }
        }
    }
    return ['', ''];
}

/**
 * Admin chrome: top bar with search and quick-create, dark grouped sidebar with badges,
 * page title with breadcrumb. Closed by admin_footer().
 */
function admin_header(string $title, string $subtitle = ''): void
{
    $me = current_user();
    $flash = flash();
    $current = basename($_SERVER['SCRIPT_NAME']);
    $badges = admin_badges();
    $totalBadge = array_sum($badges);
    [$crumbGroup, $crumbLabel] = admin_crumb($current);
    $brandCss = '';
    foreach (['color_primary' => ['--green', '--green-2'], 'color_accent' => ['--gold']] as $key => $vars) {
        if (preg_match('/^#[0-9a-fA-F]{6}$/', setting($key))) {
            foreach ($vars as $v) {
                $brandCss .= "$v:" . setting($key) . ';';
            }
        }
    }
    $initials = '';
    foreach (preg_split('/\s+/', trim($me['full_name'] ?? '')) as $w) {
        $initials .= $w !== '' ? mb_strtoupper(mb_substr($w, 0, 1)) : '';
    }
    // [label, link, permission, icon]
    $create = [
        ['Write news', 'news.php?new=1', 'news', 'newspaper'], ['Add event', 'events.php?new=1', 'events', 'calendar'],
        ['New newsletter', 'newsletters.php?new=1', 'newsletters', 'mail'], ['Upload report or document', 'resources.php?new=1', 'resources', 'folder'],
        ['New photo album', 'gallery.php?new=1', 'gallery', 'image'], ['New page', 'pages.php?new=1', 'pages', 'file'],
        ['Add campus place', 'campus.php?new=1', 'campus', 'map'], ['Add staff account', 'users.php', 'users', 'user-plus'],
    ];
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?> | Admin | <?= e(config('short_name')) ?></title>
<link rel="icon" href="<?= e(logo_url()) ?>">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
<?php if ($brandCss): ?><style>:root{<?= $brandCss ?>}</style><?php endif; ?>
<script>try{if(localStorage.getItem('adm-collapsed')==='1')document.documentElement.classList.add('side-collapsed');}catch(e){}</script>
</head>
<body class="admin">
<a class="skip" href="#main">Skip to main content</a>
<header class="adm-top">
  <button class="adm-burger" type="button" aria-label="Toggle navigation" aria-controls="adminside" onclick="admToggleSide()"><?= icon('menu', 22) ?></button>
  <a class="adm-brand" href="<?= url('admin/') ?>"><img src="<?= e(logo_url()) ?>" alt="" width="34" height="34"><span><strong><?= e(config('short_name')) ?></strong> Admin</span></a>
  <?php if (can('members') || can('news') || can('events') || can('messages')): ?>
  <form class="adm-search" role="search" method="get" action="<?= url('admin/search.php') ?>">
    <?= icon('search', 18) ?><input type="search" name="q" placeholder="Search members, news, events, documents..." aria-label="Search the admin" value="<?= e($_GET['q'] ?? '') ?>" <?= $current === 'search.php' ? '' : '' ?>>
  </form>
  <?php else: ?><span class="adm-spacer"></span><?php endif; ?>
  <div class="adm-actions">
    <?php $allowedCreate = array_filter($create, fn($c) => can($c[2])); if ($allowedCreate): ?>
    <details class="adm-menu adm-new"><summary><?= icon('plus', 16) ?> New</summary>
      <div class="adm-drop"><?php foreach ($allowedCreate as [$l, $href, $p, $ic]): ?><a href="<?= e(url('admin/' . $href)) ?>"><?= icon($ic, 16) ?><?= e($l) ?></a><?php endforeach; ?></div>
    </details>
    <?php endif; ?>
    <a class="adm-icon-btn" href="<?= url('admin/') ?>#attention" title="Items waiting for action" aria-label="<?= (int) $totalBadge ?> items waiting for action"><?= icon('bell', 20) ?><?= $totalBadge ? '<span class="dot">' . ($totalBadge > 99 ? '99+' : (int) $totalBadge) . '</span>' : '' ?></a>
    <a class="adm-icon-btn" href="<?= url() ?>" target="_blank" rel="noopener" title="View the website" aria-label="View the website"><?= icon('external', 20) ?></a>
    <details class="adm-menu adm-user"><summary><span class="av"><?= e(mb_substr($initials, 0, 2)) ?></span><span class="who"><strong><?= e($me['full_name'] ?? '') ?></strong><small><?= e(ROLE_LABELS[$me['role'] ?? 'member'] ?? '') ?></small></span><?= icon('chevron-down', 16) ?></summary>
      <div class="adm-drop right">
        <a href="<?= url('admin/account.php') ?>"><?= icon('user', 16) ?>My account</a>
        <a href="<?= url('member/') ?>"><?= icon('id-card', 16) ?>Member Portal</a>
        <a href="<?= url('admin/help.php') ?>"><?= icon('help', 16) ?>Help &amp; guide</a>
        <a href="<?= url('logout.php') ?>"><?= icon('log-out', 16) ?>Log out</a>
      </div>
    </details>
  </div>
</header>
<div class="adm-layout">
  <aside id="adminside" class="adm-side" aria-label="Admin navigation">
    <?php foreach (ADMIN_GROUPS as $group => $items):
        $visible = array_filter($items, fn($i) => $i[2] === null || can($i[2]));
        if (!$visible) {
            continue;
        } ?>
      <h2><?= e($group) ?></h2>
      <?php foreach ($visible as [$label, $file, $perm, $ic, $bk]): $count = $bk ? ($badges[$bk] ?? 0) : 0; ?>
        <a href="<?= e(url('admin/' . $file)) ?>"<?= $current === $file ? ' aria-current="page"' : '' ?> title="<?= e($label) ?>"><?= icon($ic, 18) ?><span class="lbl"><?= e($label) ?></span><?= $count ? '<span class="cnt">' . (int) $count . '</span>' : '' ?></a>
      <?php endforeach; ?>
    <?php endforeach; ?>
    <div class="adm-side-foot"><a href="<?= url() ?>" target="_blank" rel="noopener"><?= icon('globe', 18) ?><span class="lbl">View website</span></a></div>
  </aside>
  <main id="main" class="adm-main">
    <?php if ($flash): ?><div class="flash <?= e($flash[1]) ?>" role="status"><?= e($flash[0]) ?></div><?php endif; ?>
    <div class="adm-title">
      <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= url('admin/') ?>">Admin</a><?php if ($crumbGroup && $crumbGroup !== 'Overview'): ?> <span>/</span> <?= e($crumbGroup) ?><?php endif; ?><?php if ($current !== 'index.php'): ?> <span>/</span> <?= e($crumbLabel ?: $title) ?><?php endif; ?></nav>
      <h1><?= e($title) ?></h1>
      <?php if ($subtitle): ?><p class="sub"><?= e($subtitle) ?></p><?php endif; ?>
    </div>
<?php
}

function admin_footer(): void
{
    ?>
  </main>
</div>
<script src="<?= url('assets/js/countdown.js') ?>"></script>
<script>
function admToggleSide() {
  var small = window.matchMedia('(max-width: 960px)').matches;
  if (small) { document.getElementById('adminside').classList.toggle('open'); return; }
  var c = document.documentElement.classList.toggle('side-collapsed');
  try { localStorage.setItem('adm-collapsed', c ? '1' : '0'); } catch (e) {}
}
document.querySelectorAll('.adm-menu').forEach(function (d) {
  document.addEventListener('click', function (ev) { if (!d.contains(ev.target)) d.removeAttribute('open'); });
});
document.querySelectorAll('input.tbl-filter').forEach(function (inp) {
  inp.addEventListener('input', function () {
    var q = inp.value.toLowerCase(), shown = 0, rows = document.querySelectorAll('table.filterable tbody tr, table.filterable tr');
    rows.forEach(function (r) {
      if (r.querySelector('th')) return;
      var hit = r.textContent.toLowerCase().indexOf(q) !== -1;
      r.style.display = hit ? '' : 'none';
      if (hit) shown++;
    });
    var c = document.querySelector('.tbl-count'); if (c) c.textContent = shown + ' shown';
  });
});
</script>
</body>
</html>
<?php
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
                            $err = "$label: use a PDF or Office file under 40MB.";
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
        echo '<div class="toolbar"><input class="tbl-filter" type="search" placeholder="Filter this list..." aria-label="Filter this list"><span class="tbl-count">' . count($rows) . ' item' . (count($rows) === 1 ? '' : 's') . '</span><span class="spacer"></span>';
        if (!$limited) {
            echo '<a class="btn" href="' . e(url($self . '?new=1')) . '">+ Add new</a>';
        }
        echo '</div>';
        echo '<div class="table-wrap"><table class="filterable"><tr>';
        foreach ($cols as $f) {
            echo '<th>' . e($f['label']) . '</th>';
        }
        foreach ($c['list_extra'] ?? [] as $xl => $xf) {
            echo '<th>' . e($xl) . '</th>';
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
            foreach ($c['list_extra'] ?? [] as $xf) {
                echo '<td>' . e((string) $xf($r)) . '</td>';
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
