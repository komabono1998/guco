<?php
require_once __DIR__ . '/../includes/admin.php';
$me = require_login();
if (!is_staff()) {
    http_response_code(403);
    exit('Staff only.');
}
$count = fn($sql) => (int) db()->query($sql)->fetchColumn();

// Headline numbers, each shown only to roles that can open the related section.
$stats = [
    ['members', 'members.php?status=pending', $count("SELECT COUNT(*) FROM users WHERE status='pending'"), 'Applications to review', true],
    ['payments', 'payments.php', $count("SELECT COUNT(*) FROM member_payments WHERE status='pending'"), 'Payments to verify', true],
    ['projects', 'projects.php?status=submitted', $count("SELECT COUNT(*) FROM projects WHERE status IN ('submitted','under_review')"), 'Projects to review', true],
    ['chapters', 'chapter_members.php?status=pending', $count("SELECT COUNT(*) FROM chapter_members WHERE status='pending'"), 'Chapter registrations to approve', true],
    ['messages', 'messages.php', $count("SELECT COUNT(*) FROM messages WHERE status='new'"), 'New messages', true],
    ['news', 'news.php', $count("SELECT COUNT(*) FROM news WHERE status='pending'"), 'News awaiting review', true],
    ['members', 'members.php?card=active', $count("SELECT COUNT(*) FROM member_profiles WHERE card_status='active'"), 'Active membership cards', false],
    ['members', 'members.php', $count("SELECT COUNT(*) FROM users WHERE role='member' AND status='approved'"), 'Approved members', false],
    ['events', 'events.php', $count('SELECT COUNT(*) FROM events WHERE starts_at >= NOW()'), 'Upcoming dated events', false],
    ['subscribers', 'subscribers.php', $count('SELECT COUNT(*) FROM subscribers'), 'Newsletter subscribers', false],
];

$quick = [
    ['news', 'news.php?new=1', 'Write news'], ['events', 'events.php?new=1', 'Add event'], ['members', 'members.php?status=pending', 'Review applications'],
    ['content', 'content.php', 'Edit page content'], ['pages', 'pages.php?new=1', 'New page'], ['resources', 'resources.php?new=1', 'Upload document'],
    ['gallery', 'gallery.php?new=1', 'Add photo'], ['menu', 'menu.php', 'Edit menu'],
];

// Launch checklist: what still needs confirming before the site is fully ready.
$check = [];
if (can('settings')) {
    $check[] = [setting('contact_email') !== '' && setting('contact_phone') !== '', 'Official email and telephone entered', 'settings.php'];
    $check[] = [setting('social_facebook') . setting('social_x') . setting('social_linkedin') . setting('social_youtube') !== '', 'Official social media links added', 'settings.php'];
    $check[] = [setting('show_fees') === '1' || setting('payment_instructions') !== '', 'Membership fees confirmed and payment instructions added', 'settings.php'];
}
if (can('resources')) {
    $check[] = [$count("SELECT COUNT(*) FROM resources WHERE category = 'Constitution' AND filename IS NOT NULL") > 0, 'Constitution (2023) uploaded to Resources', 'resources.php?new=1'];
}
if (can('leaders')) {
    $check[] = [$count("SELECT COUNT(*) FROM leaders WHERE is_public = 1 AND name <> ''") > 0, 'Confirmed office bearers added to Leadership', 'leaders.php'];
}
if (can('news')) {
    $check[] = [$count("SELECT COUNT(*) FROM news WHERE status = 'published'") >= 3, 'At least three news posts published', 'news.php?new=1'];
}
if (can('events')) {
    $check[] = [$count('SELECT COUNT(*) FROM events WHERE starts_at IS NOT NULL AND is_public = 1') > 0, 'At least one event has a confirmed date', 'events.php'];
}
if (can('users')) {
    $check[] = [!config('dev_mode'), 'Development mode switched off (config.php)', 'users.php'];
}
$done = count(array_filter($check, fn($c) => $c[0]));
$recent = can('audit') ? q('SELECT a.*, u.full_name FROM audit_log a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 8')->fetchAll() : [];

admin_header('Dashboard');
?>
<p>Welcome back, <strong><?= e($me['full_name']) ?></strong>. You are signed in as <?= e(ROLE_LABELS[$me['role']]) ?>.</p>

<div class="stat-grid">
  <?php foreach ($stats as [$perm, $href, $n, $label, $alert]): if (!can($perm)) continue; ?>
    <a class="stat<?= $alert && $n ? ' alert' : '' ?>" href="<?= e($href) ?>"><b><?= $n ?></b><span><?= e($label) ?></span></a>
  <?php endforeach; ?>
</div>

<div class="quick">
  <?php foreach ($quick as [$perm, $href, $label]): if (!can($perm)) continue; ?><a class="btn secondary" href="<?= e($href) ?>"><?= e($label) ?></a><?php endforeach; ?>
</div>

<div class="grid two">
  <?php if ($check): ?>
  <section class="panel">
    <h2>Launch checklist <span class="meta">(<?= $done ?> of <?= count($check) ?> done)</span></h2>
    <ul class="todo">
      <?php foreach ($check as [$ok, $label, $href]): ?>
        <li class="<?= $ok ? 'ok' : 'todo-open' ?>"><?= $ok ? e($label) : '<a href="' . e($href) . '">' . e($label) . '</a>' ?></li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>
  <section class="panel">
    <h2>Manage the website</h2>
    <ul class="todo">
      <?php if (can('content')): ?><li><span><a href="content.php">Site content</a><br><span class="meta">Edit the wording and images on every page.</span></span></li><?php endif; ?>
      <?php if (can('menu')): ?><li><span><a href="menu.php">Navigation menu</a><br><span class="meta">Rename, reorder, hide or add menu links and drop-downs.</span></span></li><?php endif; ?>
      <?php if (can('pages')): ?><li><span><a href="pages.php">Custom pages</a><br><span class="meta">Create new pages and link them from the menu.</span></span></li><?php endif; ?>
      <?php if (can('branding')): ?><li><span><a href="branding.php">Branding &amp; layout</a><br><span class="meta">Name, logo, colours and which home page sections show.</span></span></li><?php endif; ?>
      <?php if (can('settings')): ?><li><span><a href="settings.php">Settings</a><br><span class="meta">Contact details, social links, fees and applications.</span></span></li><?php endif; ?>
    </ul>
  </section>
  <?php if ($recent): ?>
  <section class="panel" style="grid-column:1/-1">
    <h2>Recent activity <a class="meta" href="audit.php">full audit log</a></h2>
    <ul class="todo">
      <?php foreach ($recent as $r): ?><li><span class="meta" style="min-width:130px"><?= e(fmt_date($r['created_at'], 'j M, g:ia')) ?></span><span><strong><?= e($r['full_name'] ?? 'system') ?></strong> &middot; <?= e($r['action']) ?> <span class="meta"><?= e($r['detail']) ?></span></span></li><?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>
</div>
<?php admin_footer(); ?>
