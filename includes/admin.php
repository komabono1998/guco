<?php
require_once __DIR__ . '/bootstrap.php';

/** Sidebar structure: group => [label, file, permission (null = everyone), icon, badge key]. */
const ADMIN_GROUPS = [
    'Overview' => [
        ['Dashboard', 'index.php', null, 'grid', null],
        ['Reports & analytics', 'reports.php', 'reports', 'bar-chart', null],
        ['Website visitors', 'visitors.php', 'analytics', 'activity', null],
    ],
    'Website content' => [
        ['Site content', 'content.php', 'content', 'edit', null], ['Pages', 'pages.php', 'pages', 'file', null], ['Menu', 'menu.php', 'menu', 'menu', null],
        ['News', 'news.php', 'news', 'newspaper', 'news'], ['Newsletters', 'newsletters.php', 'newsletters', 'mail', null], ['Events & calendar', 'events.php', 'events', 'calendar', null],
        ['Reports & documents', 'resources.php', 'resources', 'folder', null], ['Gallery', 'gallery.php', 'gallery', 'image', null],
        ['Campus', 'campus.php', 'campus', 'map', null], ['Partners', 'partners.php', 'partners', 'link', null],
        ['Anthems', 'anthems.php', 'content', 'music', null],
    ],
    'Organisation' => [
        ['Leadership', 'leaders.php', 'leaders', 'users', null], ['Chapters', 'chapters.php', 'chapters', 'map-pin', null],
        ['Chapter members', 'chapter_members.php', 'chapters', 'user-plus', 'chapters'], ['Projects', 'projects.php', 'projects', 'target', 'projects'],
        ['Programmes', 'programmes.php', 'programmes', 'briefcase', null], ['Awards', 'awards.php', 'awards', 'award', null],
    ],
    'Membership' => [
        ['Members', 'members.php', 'members', 'id-card', 'members'], ['Payments', 'payments.php', 'payments', 'credit-card', 'payments'],
        ['Import / export', 'members_import.php', 'members', 'folder', null], ['Existing members', 'legacy_members.php', 'members', 'users', null], ['Members database', 'member_reports.php', 'members', 'file', null],
    ],
    'Giving and careers' => [
        ['Donations', 'donations.php', 'donations', 'heart', 'donations'], ['Campaigns', 'campaigns.php', 'donations', 'target', null],
        ['Careers', 'jobs.php', 'careers', 'briefcase', null], ['Mentorship', 'mentorship.php', 'mentorship', 'users', null],
        ['Hall of Fame', 'hall_of_fame.php', 'halloffame', 'award', null], ['Event check-in', 'checkin.php', 'checkin', 'check', null],
    ],
    'Inbox' => [
        ['Messages', 'messages.php', 'messages', 'inbox', 'messages'], ['Subscribers', 'subscribers.php', 'subscribers', 'mail', null],
    ],
    'Site' => [
        ['Branding & layout', 'branding.php', 'branding', 'layout', null], ['Settings', 'settings.php', 'settings', 'sliders', null], ['Maintenance mode', 'maintenance.php', 'settings', 'shield', null],
    ],
    'System' => [
        ['Users & roles', 'users.php', 'users', 'shield', null], ['Mail log', 'mail_log.php', 'mail', 'activity', null],
        ['Audit log', 'audit.php', 'audit', 'clock', null], ['Backup', 'backup.php', 'backup', 'folder', null], ['Help & guide', 'help.php', null, 'help', null],
    ],
];

/** Friendly details for each category: [icon, colour class, one-line purpose]. */
const ADMIN_GROUP_META = [
    'Overview' => ['grid', 'c-green', 'Your numbers at a glance'],
    'Website content' => ['edit', 'c-blue', 'Everything visitors read and see'],
    'Organisation' => ['users', 'c-teal', 'Leaders, chapters and projects'],
    'Membership' => ['id-card', 'c-gold', 'Members, payments and records'],
    'Giving and careers' => ['heart', 'c-rose', 'Donations, jobs and mentorship'],
    'Inbox' => ['inbox', 'c-violet', 'Messages and subscribers'],
    'Site' => ['sliders', 'c-slate', 'Look, settings and maintenance'],
    'System' => ['shield', 'c-red', 'Staff, security and backups'],
];

/** What each admin page is for, shown on the dashboard hub. */
const ADMIN_DESC = [
    'index.php' => 'Overview of the whole site',
    'reports.php' => 'Membership, payment and activity reports',
    'visitors.php' => 'Visitors, countries and most-read pages',
    'content.php' => 'Edit the wording on public pages',
    'pages.php' => 'Create and edit extra pages',
    'menu.php' => 'Change the website navigation menu',
    'news.php' => 'Write and publish news stories',
    'newsletters.php' => 'Create, send and archive newsletters',
    'events.php' => 'Add events, flyers and registrations',
    'resources.php' => 'Upload reports and official documents',
    'gallery.php' => 'Manage photo albums',
    'campus.php' => 'Places shown in Explore your campus',
    'partners.php' => 'Partner and sponsor logos',
    'anthems.php' => 'Anthem audio, video and lyrics',
    'leaders.php' => 'Leadership profiles and photos',
    'chapters.php' => 'Alumni chapters and contacts',
    'chapter_members.php' => 'Approve chapter join requests',
    'projects.php' => 'Review and track alumni projects',
    'programmes.php' => 'Programme impact reports',
    'awards.php' => 'Awards and recognition',
    'members.php' => 'Applications and member accounts',
    'payments.php' => 'Verify subscription payments',
    'members_import.php' => 'Import and export member lists',
    'legacy_members.php' => 'The register of existing members',
    'member_reports.php' => 'Filter and export the members database',
    'donations.php' => 'Donations received and pending',
    'campaigns.php' => 'Fundraising campaigns',
    'jobs.php' => 'Careers, scholarships and grants',
    'mentorship.php' => 'Mentors and mentee requests',
    'hall_of_fame.php' => 'Alumni Hall of Fame entries',
    'checkin.php' => 'Check guests in at events',
    'messages.php' => 'Contact form enquiries',
    'subscribers.php' => 'Newsletter subscribers',
    'branding.php' => 'Logo, colours and layout',
    'settings.php' => 'Site name, contacts and security options',
    'maintenance.php' => 'Take the site offline with a message',
    'users.php' => 'Staff accounts and roles',
    'mail_log.php' => 'Sent emails and email test',
    'audit.php' => 'Who did what, and when',
    'backup.php' => 'Download a backup of the data',
    'help.php' => 'Guides for using the admin area',
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
    if (can('donations')) {
        $b['donations'] = $n("SELECT COUNT(*) FROM donations WHERE status = 'pending'");
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
<link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
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
        }
        $gm = ADMIN_GROUP_META[$group] ?? ['grid', 'c-slate', ''];
        $hasCurrent = in_array($current, array_column($visible, 1), true);
        $groupCount = 0;
        foreach ($visible as $vi) { $groupCount += $vi[4] ? ($badges[$vi[4]] ?? 0) : 0; } ?>
      <details class="adm-grp <?= e($gm[1]) ?>" data-grp="<?= e($group) ?>"<?= $hasCurrent || $group === 'Overview' ? ' open data-keep="1"' : '' ?>>
        <summary><span class="gi"><?= icon($gm[0], 16) ?></span><span class="gname"><?= e($group) ?></span><?= $groupCount ? '<span class="cnt">' . (int) $groupCount . '</span>' : '' ?><span class="chev"><?= icon('chevron-down', 14) ?></span></summary>
        <?php foreach ($visible as [$label, $file, $perm, $ic, $bk]): $count = $bk ? ($badges[$bk] ?? 0) : 0; ?>
          <a href="<?= e(url('admin/' . $file)) ?>"<?= $current === $file ? ' aria-current="page"' : '' ?> title="<?= e($label) ?>"><?= icon($ic, 18) ?><span class="lbl"><?= e($label) ?></span><?= $count ? '<span class="cnt">' . (int) $count . '</span>' : '' ?></a>
        <?php endforeach; ?>
      </details>
    <?php endforeach; ?>
    <div class="adm-side-foot"><a href="<?= url() ?>" target="_blank" rel="noopener"><?= icon('globe', 18) ?><span class="lbl">View website</span></a></div>
  </aside>
  <main id="main" class="adm-main">
    <?php if (maintenance_on()): ?><div class="flash error" role="status" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap"><strong>Maintenance mode is ON.</strong> Visitors see the maintenance page; only signed-in staff see the website. <?php if (can('settings')): ?><a href="<?= url('admin/maintenance.php') ?>" style="font-weight:800">Manage or turn it off</a><?php endif; ?></div><?php endif; ?>
    <?php if ($flash): ?><div class="flash <?= e($flash[1]) ?>" role="status"><?= e($flash[0]) ?></div><?php endif; ?>
    <div class="adm-title">
      <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= url('admin/') ?>">Admin</a><?php if ($crumbGroup && $crumbGroup !== 'Overview'): ?> <span>/</span> <?= e($crumbGroup) ?><?php endif; ?><?php if ($current !== 'index.php'): ?> <span>/</span> <?= e($crumbLabel ?: $title) ?><?php endif; ?></nav>
      <h1><?= e($title) ?><?php if ($current !== 'index.php'): ?><button type="button" class="pin-btn" id="pinBtn" data-file="<?= e($current) ?>" data-label="<?= e($title) ?>" aria-pressed="false" title="Pin this page to your dashboard" aria-label="Pin this page to your dashboard"><?= icon('star', 18) ?></button><?php endif; ?></h1>
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
(function () {
  // Sidebar categories: remember which ones the person opened.
  var key = 'adm-groups', open = {};
  try { open = JSON.parse(localStorage.getItem(key) || '{}'); } catch (e) {}
  document.querySelectorAll('.adm-grp').forEach(function (d) {
    var g = d.getAttribute('data-grp');
    if (!d.hasAttribute('data-keep') && open[g]) d.setAttribute('open', '');
    d.addEventListener('toggle', function () {
      open[g] = d.open;
      try { localStorage.setItem(key, JSON.stringify(open)); } catch (e) {}
    });
  });
  // Pinned pages (kept in this browser only).
  var pins = [];
  try { pins = JSON.parse(localStorage.getItem('adm-pins') || '[]'); } catch (e) {}
  function savePins() { try { localStorage.setItem('adm-pins', JSON.stringify(pins)); } catch (e) {} }
  var btn = document.getElementById('pinBtn');
  if (btn) {
    var f = btn.getAttribute('data-file');
    var sync = function () { var on = pins.some(function (p) { return p.f === f; }); btn.setAttribute('aria-pressed', on ? 'true' : 'false'); btn.classList.toggle('on', on); };
    sync();
    btn.addEventListener('click', function () {
      var i = pins.findIndex(function (p) { return p.f === f; });
      if (i >= 0) pins.splice(i, 1); else pins.push({ f: f, l: btn.getAttribute('data-label') });
      savePins(); sync();
    });
  }
  var list = document.getElementById('pinList');
  if (list) {
    var render = function () {
      list.innerHTML = '';
      pins.forEach(function (p, i) {
        var a = document.createElement('a'); a.href = p.f; a.textContent = p.l;
        var x = document.createElement('button'); x.type = 'button'; x.setAttribute('aria-label', 'Unpin ' + p.l); x.textContent = '×';
        x.addEventListener('click', function (e) { e.preventDefault(); pins.splice(i, 1); savePins(); render(); });
        var w = document.createElement('span'); w.className = 'pin'; w.appendChild(a); w.appendChild(x); list.appendChild(w);
      });
      document.getElementById('pinned').hidden = !pins.length;
      document.getElementById('pinHint').hidden = !!pins.length;
    };
    render();
  }
  // Dashboard hub: category chips and search.
  var grid = document.getElementById('hubGrid');
  if (grid) {
    var cur = 'all', find = document.getElementById('hubFind');
    var apply = function () {
      var q = find.value.trim().toLowerCase(), shown = 0;
      grid.querySelectorAll('.hub-card').forEach(function (c) {
        var inGroup = cur === 'all' || c.getAttribute('data-g') === cur, any = 0;
        c.querySelectorAll('li').forEach(function (li) {
          var hit = !q || li.getAttribute('data-t').indexOf(q) !== -1;
          li.hidden = !hit; if (hit) any++;
        });
        c.hidden = !(inGroup && any); if (!c.hidden) shown++;
      });
      document.getElementById('hubNone').hidden = shown > 0;
    };
    document.getElementById('hubChips').addEventListener('click', function (e) {
      var b = e.target.closest('button'); if (!b) return;
      cur = b.getAttribute('data-g');
      this.querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x === b); });
      apply();
    });
    find.addEventListener('input', apply);
  }
})();
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
                    if (in_array($f['type'], ['image', 'audio'], true) && !empty($row[$n])) {
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
                case 'set':
                    $picked = is_array($_POST[$n] ?? null) ? array_values(array_intersect(array_keys($f['options']), $_POST[$n])) : [];
                    $vals[$n] = implode(',', $picked);
                    break;
                case 'audio':
                    $has = ($_FILES[$n]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
                    if ($has) {
                        $name = save_media($_FILES[$n], 'audio');
                        $name ? $vals[$n] = $name : $err = "$label: use an MP3, M4A, OGG or WAV file under 25MB, or an MP4 file under 5MB.";
                    }
                    if (!$err && !empty($_POST['remove_' . $n]) && $old) {
                        @unlink(config('upload_dir') . '/' . basename((string) $old[$n]));
                        $vals[$n] = null;
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
            if (!empty($f['required']) && !in_array($f['type'], ['image', 'audio', 'doc', 'check'], true) && ($vals[$n] ?? '') === '') {
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
    <?php elseif ($f['type'] === 'set'): $have = array_filter(explode(',', (string) $val($n))); ?>
      <fieldset style="border:0;padding:0;margin:0"><legend style="padding:0;font-weight:600"><?= $label . $hint ?></legend>
        <?php foreach ($f['options'] as $ok => $ol): ?><label class="check" style="display:inline-flex;margin-right:16px"><input type="checkbox" name="<?= $n ?>[]" value="<?= e($ok) ?>"<?= in_array($ok, $have, true) ? ' checked' : '' ?> style="width:auto"> <?= e($ol) ?></label><?php endforeach; ?>
      </fieldset>
    <?php elseif ($f['type'] === 'audio'): ?>
      <label><?= $label . $hint ?>
        <?php if (!empty($edit[$n])): ?><<?= str_ends_with((string) $edit[$n], '.mp4') ? 'video playsinline' : 'audio' ?> controls preload="none" src="<?= e(url('uploads/' . rawurlencode($edit[$n]))) ?>" style="max-width:100%;display:block;margin:6px 0"></<?= str_ends_with((string) $edit[$n], '.mp4') ? 'video' : 'audio' ?>><span class="check"><input type="checkbox" name="remove_<?= $n ?>" value="1" style="width:auto"> Remove this audio file</span><?php endif; ?>
        <input type="file" name="<?= $n ?>" accept="audio/mpeg,audio/mp4,video/mp4,audio/ogg,audio/wav,.mp3,.m4a,.mp4,.ogg,.wav"></label>
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
                } elseif ($f['type'] === 'audio') {
                    echo $v ? 'Audio added' : '-';
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
