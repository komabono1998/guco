<?php
require_once __DIR__ . '/../includes/admin.php';
$me = require_login();
if (!is_staff()) {
    http_response_code(403);
    exit('Staff only.');
}
$n = fn($sql, $a = []) => (int) q($sql, $a)->fetchColumn();
$num = fn($v) => number_format((float) $v);
$monthStart = date('Y-m-01');
$lastMonthStart = date('Y-m-01', strtotime('-1 month'));

/** Last 6 months as [ym => label]. */
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $t = strtotime(date('Y-m-01') . " -$i month");
    $months[date('Y-m', $t)] = date('M', $t);
}
$series = function (string $sql) use ($months) {
    $rows = db()->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);
    return array_map(fn($ym) => (float) ($rows[$ym] ?? 0), array_keys($months));
};
$barChart = function (array $values, string $cls = '', string $fmt = 'n') use ($months) {
    $max = max($values) ?: 1;
    $labels = array_values($months);
    $out = '<div class="bars">';
    foreach ($values as $i => $v) {
        $shown = $fmt === 'k' ? ($v >= 1000000 ? round($v / 1000000, 1) . 'M' : ($v >= 1000 ? round($v / 1000) . 'k' : (int) $v)) : (int) $v;
        $out .= '<div class="b ' . $cls . '"><em>' . ($v > 0 ? $shown : '') . '</em><i style="height:' . max(2, round($v / $max * 100)) . '%"></i><span>' . e($labels[$i]) . '</span></div>';
    }
    return $out . '</div>';
};
$delta = function (int $now, int $prev) {
    if ($prev === 0) {
        return $now > 0 ? '<span class="up">+' . $now . ' new</span> this month' : 'No change this month';
    }
    $d = round(($now - $prev) / $prev * 100);
    return '<span class="' . ($d >= 0 ? 'up' : 'down') . '">' . ($d >= 0 ? '+' : '') . $d . '%</span> vs last month';
};

// ---- KPIs ----
$kpis = [];
if (can('members')) {
    $total = $n("SELECT COUNT(*) FROM users WHERE role = 'member' AND status = 'approved'");
    $applied = $n("SELECT COUNT(*) FROM users WHERE role = 'member' AND created_at >= ?", [$monthStart]);
    $appliedPrev = $n("SELECT COUNT(*) FROM users WHERE role = 'member' AND created_at >= ? AND created_at < ?", [$lastMonthStart, $monthStart]);
    $kpis[] = ['members.php', 'Approved members', $num($total), $delta($applied, $appliedPrev), 'users', ''];
    $kpis[] = ['members.php?card=active', 'Active membership cards', $num($n("SELECT COUNT(*) FROM member_profiles WHERE card_status = 'active'")), $num($n("SELECT COUNT(*) FROM member_profiles WHERE card_status = 'revoked'")) . ' revoked', 'id-card', 'blue'];
}
if (can('payments')) {
    $rev = (float) q("SELECT COALESCE(SUM(amount),0) FROM member_payments WHERE status = 'verified' AND verified_at >= ?", [$monthStart])->fetchColumn();
    $revAll = (float) db()->query("SELECT COALESCE(SUM(amount),0) FROM member_payments WHERE status = 'verified'")->fetchColumn();
    $kpis[] = ['payments.php?all=1', 'Verified this month (UGX)', $num($rev), 'UGX ' . $num($revAll) . ' all time', 'credit-card', 'purple'];
}
if (can('events')) {
    $next = q("SELECT title, starts_at FROM events WHERE starts_at >= NOW() AND is_public = 1 AND status = 'scheduled' ORDER BY starts_at LIMIT 1")->fetch();
    $kpis[] = ['events.php', 'Upcoming dated events', $num($n('SELECT COUNT(*) FROM events WHERE starts_at >= NOW()')), $next ? 'Next: ' . e(excerpt($next['title'], 24)) . ', ' . e(fmt_date($next['starts_at'], 'j M')) : 'No dated events yet', 'calendar', 'amber'];
}
if (can('subscribers')) {
    $subNew = $n('SELECT COUNT(*) FROM subscribers WHERE created_at >= ?', [$monthStart]);
    $kpis[] = ['subscribers.php', 'Newsletter subscribers', $num($n('SELECT COUNT(*) FROM subscribers')), '+' . $subNew . ' this month', 'mail', ''];
}
if (can('projects')) {
    $raised = (float) db()->query("SELECT COALESCE(SUM(amount),0) FROM project_updates WHERE kind = 'mobilisation' AND verified = 1")->fetchColumn();
    $kpis[] = ['projects.php', 'Active projects', $num($n("SELECT COUNT(*) FROM projects WHERE status IN ('approved','in_progress')")), 'UGX ' . $num($raised) . ' mobilised', 'target', 'blue'];
}
if (can('chapters')) {
    $kpis[] = ['chapter_members.php', 'Chapter members', $num($n("SELECT COUNT(*) FROM chapter_members WHERE status = 'approved'")), $num($n('SELECT COUNT(*) FROM chapters WHERE is_public = 1')) . ' chapters', 'map-pin', ''];
}
if (can('news')) {
    $kpis[] = ['news.php', 'Published news', $num($n("SELECT COUNT(*) FROM news WHERE status = 'published'")), $num($n("SELECT COUNT(*) FROM newsletters WHERE status = 'published'")) . ' newsletters', 'newspaper', 'amber'];
}

// ---- Needs attention ----
$attention = [];
$add = function (string $perm, int $count, string $label, string $hint, string $href, bool $red = true) use (&$attention) {
    if ($count > 0 && can($perm)) {
        $attention[] = [$count, $label, $hint, $href, $red];
    }
};
$add('members', $n("SELECT COUNT(*) FROM users WHERE status = 'pending'"), 'Membership applications to review', 'Check details and approve or decline', 'members.php?status=pending');
$add('payments', $n("SELECT COUNT(*) FROM member_payments WHERE status = 'pending'"), 'Payments waiting for verification', 'Open the proof of payment and verify', 'payments.php');
$add('projects', $n("SELECT COUNT(*) FROM projects WHERE status IN ('submitted','under_review')"), 'Project proposals to review', 'Approve, request changes or decline', 'projects.php?status=submitted');
$add('projects', $n("SELECT COUNT(*) FROM project_updates WHERE kind = 'mobilisation' AND verified = 0"), 'Project contributions to verify', 'Confirm funds reported by project leads', 'projects.php');
$add('chapters', $n("SELECT COUNT(*) FROM chapter_members WHERE status = 'pending'"), 'Chapter registrations to approve', 'Approve new chapter members', 'chapter_members.php?status=pending');
$add('messages', $n("SELECT COUNT(*) FROM messages WHERE status = 'new'"), 'New messages and enquiries', 'Reply and mark as handled', 'messages.php');
$add('news', $n("SELECT COUNT(*) FROM news WHERE status = 'pending'"), 'News stories awaiting review', 'Publish or send back to the writer', 'news.php', false);
$add('members', $n("SELECT COUNT(*) FROM member_profiles p JOIN users u ON u.id = p.user_id WHERE u.status = 'approved' AND p.category = 'ordinary' AND p.card_status = 'active' AND p.paid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)"), 'Subscriptions expiring in 30 days', 'Remind members to renew', 'members.php', false);
$add('events', $n('SELECT COUNT(*) FROM events WHERE starts_at IS NULL AND is_public = 1'), 'Events still without a date', 'Add dates so countdowns and invitations work', 'events.php', false);
$add('mail', $n("SELECT COUNT(*) FROM mail_log WHERE status = 'failed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"), 'Emails that failed to send (7 days)', 'Check the mail server settings', 'mail_log.php?status=failed', false);

// ---- Charts ----
$regSeries = can('members') ? $series("SELECT DATE_FORMAT(created_at, '%Y-%m') ym, COUNT(*) FROM users WHERE role = 'member' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY ym") : [];
$paySeries = can('payments') ? $series("SELECT DATE_FORMAT(verified_at, '%Y-%m') ym, SUM(amount) FROM member_payments WHERE status = 'verified' AND verified_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY ym") : [];
$cats = can('members') ? q("SELECT p.category, COUNT(*) c FROM member_profiles p JOIN users u ON u.id = p.user_id WHERE u.role = 'member' AND u.status = 'approved' GROUP BY p.category")->fetchAll(PDO::FETCH_KEY_PAIR) : [];
$palette = ['#14532d', '#d4a017', '#2457a6', '#b42323'];
$catTotal = array_sum($cats);
$gradient = [];
$acc = 0;
$i = 0;
foreach (CATEGORY_LABELS as $k => $lbl) {
    $c = (int) ($cats[$k] ?? 0);
    if ($c) {
        $from = round($acc / max(1, $catTotal) * 100, 2);
        $acc += $c;
        $gradient[] = $palette[$i % 4] . " $from% " . round($acc / $catTotal * 100, 2) . '%';
    }
    $i++;
}
$byChapter = can('chapters') ? q("SELECT c.name, COUNT(m.id) n FROM chapters c LEFT JOIN chapter_members m ON m.chapter_id = c.id AND m.status = 'approved' WHERE c.is_public = 1 GROUP BY c.id ORDER BY c.sort_order, c.name")->fetchAll() : [];
$chMax = max(array_column($byChapter, 'n') ?: [1]) ?: 1;
$pipeline = can('projects') ? q('SELECT status, COUNT(*) FROM projects GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR) : [];

// ---- Lists ----
// ---- Countdowns: pinned events and custom timers ----
$cd = [];
foreach (q("SELECT id, title, starts_at, location FROM events WHERE pin_dashboard = 1 AND starts_at >= NOW() ORDER BY starts_at")->fetchAll() as $e) {
    $cd[] = ['event', (int) $e['id'], $e['title'], strtotime($e['starts_at']), (string) $e['location'], 'events.php?id=' . (int) $e['id']];
}
foreach (q('SELECT id, title, target_at, note FROM admin_countdowns WHERE target_at >= NOW() ORDER BY target_at')->fetchAll() as $c) {
    $cd[] = ['custom', (int) $c['id'], $c['title'], strtotime($c['target_at']), (string) $c['note'], null];
}
usort($cd, fn($a, $b) => $a[3] <=> $b[3]);
$deadlines = [];
foreach (q("SELECT name, deadline FROM awards WHERE nomination_status = 'open' AND deadline >= CURDATE() ORDER BY deadline")->fetchAll() as $a) {
    $deadlines[] = ['Award nominations close: ' . $a['name'], strtotime($a['deadline']), 'awards.php'];
}
if (can('projects')) {
    foreach (q("SELECT id, title, end_date FROM projects WHERE status IN ('approved','in_progress') AND end_date >= CURDATE() ORDER BY end_date LIMIT 5")->fetchAll() as $p) {
        $deadlines[] = ['Project ends: ' . $p['title'], strtotime($p['end_date']), 'projects.php?id=' . $p['id']];
    }
}
usort($deadlines, fn($a, $b) => $a[1] <=> $b[1]);
$canCount = can('events');
$events = can('events') ? q("SELECT e.*, (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id) AS regs FROM events e WHERE e.starts_at >= NOW() OR e.starts_at IS NULL ORDER BY e.starts_at IS NULL, e.starts_at LIMIT 5")->fetchAll() : [];
$recentApps = can('members') ? q("SELECT u.id, u.full_name, u.status, u.created_at, p.category, p.member_no FROM users u LEFT JOIN member_profiles p ON p.user_id = u.id WHERE u.role = 'member' ORDER BY u.created_at DESC LIMIT 6")->fetchAll() : [];
$messages = can('messages') ? q('SELECT * FROM messages ORDER BY created_at DESC LIMIT 5')->fetchAll() : [];
$recent = can('audit') ? q('SELECT a.*, u.full_name FROM audit_log a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 8')->fetchAll() : [];

// ---- Launch checklist ----
$check = [];
if (can('settings')) {
    $check[] = [setting('contact_email') !== '' && setting('contact_phone') !== '', 'Official email and telephone entered', 'settings.php'];
    $check[] = [setting('social_facebook') . setting('social_x') . setting('social_linkedin') . setting('social_youtube') !== '', 'Official social media links added', 'settings.php'];
    $check[] = [setting('show_fees') === '1' || setting('payment_instructions') !== '', 'Membership fees confirmed and payment instructions added', 'settings.php'];
}
if (can('resources')) {
    $check[] = [$n("SELECT COUNT(*) FROM resources WHERE category = 'Constitution' AND (filename IS NOT NULL OR external_url IS NOT NULL)") > 0, 'Constitution (2023) uploaded under Reports & documents', 'resources.php?new=1'];
}
if (can('leaders')) {
    $check[] = [$n("SELECT COUNT(*) FROM leaders WHERE is_public = 1 AND name <> ''") > 0, 'Confirmed office bearers added to Leadership', 'leaders.php'];
}
if (can('news')) {
    $check[] = [$n("SELECT COUNT(*) FROM news WHERE status = 'published'") >= 3, 'At least three news posts published', 'news.php?new=1'];
}
if (can('events')) {
    $check[] = [$n('SELECT COUNT(*) FROM events WHERE starts_at IS NOT NULL AND is_public = 1') > 0, 'At least one event has a confirmed date', 'events.php'];
}
if (can('content')) {
    $check[] = [c('home.anthem_audio') !== '' || trim(c('home.anthem_video')) !== '', 'Gulu University anthem added to the home page', 'content.php?page=home'];
}
if (can('campus')) {
    $check[] = [$n('SELECT COUNT(*) FROM campus_spots WHERE is_public = 1') > 0, 'Campus places added to "Explore your campus"', 'campus.php'];
}
if (can('newsletters')) {
    $check[] = [$n("SELECT COUNT(*) FROM newsletters WHERE status = 'published'") > 0, 'First newsletter published', 'newsletters.php?new=1'];
}
if (can('users')) {
    $check[] = [!config('dev_mode'), 'Development mode switched off (config.php)', 'users.php'];
}
$done = count(array_filter($check, fn($c) => $c[0]));
$pct = $check ? (int) round($done / count($check) * 100) : 100;

// ---- Quick actions ----
$quick = [
    ['members', 'members.php?status=pending', 'Review applications', 'id-card'], ['payments', 'payments.php', 'Verify payments', 'credit-card'],
    ['news', 'news.php?new=1', 'Write news', 'newspaper'], ['newsletters', 'newsletters.php?new=1', 'New newsletter', 'mail'],
    ['events', 'events.php?new=1', 'Add event', 'calendar'], ['resources', 'resources.php?new=1', 'Upload a report', 'folder'],
    ['gallery', 'gallery.php?new=1', 'New photo album', 'image'], ['content', 'content.php', 'Edit page text', 'edit'],
    ['menu', 'menu.php', 'Edit menu', 'menu'], ['pages', 'pages.php?new=1', 'Create a page', 'file'],
    ['branding', 'branding.php', 'Branding & layout', 'layout'], ['reports', 'reports.php', 'View reports', 'bar-chart'],
];
$hour = (int) date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
admin_header('Dashboard');
?>
<section class="dash-hero">
  <div>
    <h2><?= $greet ?>, <?= e(strtok($me['full_name'], ' ')) ?></h2>
    <p><?= e(date('l, j F Y')) ?> &middot; <?= e(ROLE_LABELS[$me['role']]) ?><?= $attention ? ' &middot; <strong>' . array_sum(array_column($attention, 0)) . ' items need your attention</strong>' : ' &middot; Nothing is waiting for you' ?></p>
  </div>
  <?php if ($check): ?>
  <div class="prog"><strong>Website setup: <?= $pct ?>%</strong><div class="bar"><i style="width:<?= $pct ?>%"></i></div><small><?= $done ?> of <?= count($check) ?> launch tasks done &middot; <a href="#setup" style="color:#fff">see the list</a></small></div>
  <?php endif; ?>
</section>

<?php if ($kpis): ?>
<div class="kpis">
  <?php foreach ($kpis as [$href, $label, $val, $note, $ic, $tone]): ?>
    <a class="kpi <?= $tone ?>" href="<?= e($href) ?>"><span class="ic"><?= icon($ic, 22) ?></span><div><div class="lbl"><?= e($label) ?></div><div class="val"><?= $val ?></div><div class="note"><?= $note ?></div></div></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<section class="wid" id="countdowns" style="margin-bottom:18px">
  <h3><span><?= icon('clock', 18) ?> Countdowns</span><small><?= count($cd) ? count($cd) . ' running' : 'none running' ?></small></h3>
  <?php if ($cd): ?>
    <div class="cd-grid">
      <?php foreach ($cd as [$kind, $cid, $ctitle, $cts, $csub, $chref]): ?>
        <div class="cd-card">
          <div class="cd-top"><span class="badge <?= $kind === 'event' ? '' : 'warn' ?>"><?= $kind === 'event' ? 'Event' : 'Timer' ?></span>
            <strong><?= $chref ? '<a href="' . e($chref) . '">' . e($ctitle) . '</a>' : e($ctitle) ?></strong>
            <small><?= e(fmt_date(date('Y-m-d H:i:s', $cts), 'D j M Y, g:ia')) ?><?= $csub !== '' ? ' &middot; ' . e($csub) : '' ?></small></div>
          <?= countdown_box($cts, $ctitle, 'small') ?>
          <?php if ($canCount): ?>
            <form method="post" action="countdowns.php"><?= csrf_field() ?>
              <?php if ($kind === 'event'): ?><input type="hidden" name="event_id" value="<?= $cid ?>"><button class="link-btn" name="action" value="unpin">Remove from dashboard</button>
              <?php else: ?><input type="hidden" name="id" value="<?= $cid ?>"><button class="link-btn" name="action" value="delete" onclick="return confirm('Remove this countdown?')">Remove</button><?php endif; ?>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="meta" style="margin:0 0 10px">No countdowns are running. Pin an event below, or add your own timer for a meeting, deadline or launch.</p>
  <?php endif; ?>
  <?php if ($deadlines): ?>
    <ul class="mini" style="margin-top:12px"><?php foreach (array_slice($deadlines, 0, 4) as [$dl, $dts, $dhref]): $days = (int) ceil(($dts - strtotime('today')) / 86400); ?>
      <li><span class="avs"><?= icon('alert', 16) ?></span><div class="t"><a href="<?= e($dhref) ?>"><?= e($dl) ?></a><small><?= e(fmt_date(date('Y-m-d', $dts), 'D j M Y')) ?></small></div><span class="badge <?= $days <= 7 ? 'bad' : 'warn' ?>"><?= $days === 0 ? 'today' : 'in ' . $days . ' day' . ($days === 1 ? '' : 's') ?></span></li>
    <?php endforeach; ?></ul>
  <?php endif; ?>
  <?php if ($canCount): ?>
    <details class="cd-add"><summary><?= icon('plus', 14) ?> Add a custom countdown</summary>
      <form method="post" action="countdowns.php" class="cols" style="margin-top:12px;align-items:end"><?= csrf_field() ?><input type="hidden" name="action" value="add">
        <label>Name <input name="title" required placeholder="e.g. Executive meeting, AGM notice deadline"></label>
        <label>Date and time <input type="datetime-local" name="target_at" required></label>
        <label>Note <span class="hint">(optional)</span><input name="note"></label>
        <button>Start countdown</button>
      </form>
    </details>
  <?php endif; ?>
</section>

<div class="dgrid">
  <section class="wid w5" id="attention">
    <h3>Needs your attention <small><?= array_sum(array_column($attention, 0)) ?> open</small></h3>
    <?php if ($attention): ?>
      <ul class="attn">
        <?php foreach ($attention as [$count, $label, $hint, $href, $red]): ?>
          <li><a href="<?= e($href) ?>"><span class="n <?= $red ? '' : 'amber' ?>"><?= $count ?></span><span class="t"><?= e($label) ?><small><?= e($hint) ?></small></span><?= icon('arrow-right', 16) ?></a></li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <div class="allclear"><?= icon('check-circle', 34) ?><strong>You are all caught up</strong><br>New applications, payments and messages will appear here.</div>
    <?php endif; ?>
  </section>

  <section class="wid w7">
    <h3>Quick actions</h3>
    <div class="quick-grid">
      <?php foreach ($quick as [$perm, $href, $label, $ic]): if (!can($perm)) continue; ?>
        <a href="<?= e($href) ?>"><?= icon($ic, 22) ?><?= e($label) ?></a>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<div class="dgrid">
  <?php if ($regSeries): ?>
  <section class="wid w4"><h3>New registrations <small>last 6 months</small></h3><?= $barChart($regSeries) ?></section>
  <?php endif; ?>
  <?php if ($paySeries): ?>
  <section class="wid w4"><h3>Verified payments <small>UGX, last 6 months</small></h3><?= $barChart($paySeries, 'alt', 'k') ?></section>
  <?php endif; ?>
  <?php if (can('members')): ?>
  <section class="wid w4"><h3>Members by category <small><?= $catTotal ?> approved</small></h3>
    <div class="donut-row">
      <div class="donut" style="background:<?= $gradient ? 'conic-gradient(' . implode(', ', $gradient) . ')' : '#e6ecf3' ?>"><b><?= $catTotal ?><small>members</small></b></div>
      <ul class="legend"><?php $i = 0; foreach (CATEGORY_LABELS as $k => $lbl): ?><li><i style="background:<?= $palette[$i % 4] ?>"></i><span><?= e($lbl) ?></span><span><?= (int) ($cats[$k] ?? 0) ?></span></li><?php $i++; endforeach; ?></ul>
    </div>
  </section>
  <?php endif; ?>
</div>

<div class="dgrid">
  <?php if ($events || can('events')): ?>
  <section class="wid w6">
    <h3>Upcoming events <a href="events.php">Manage events &rarr;</a></h3>
    <ul class="mini">
      <?php foreach ($events as $ev): ?>
        <li>
          <div class="d"><b><?= $ev['starts_at'] ? e(fmt_date($ev['starts_at'], 'j')) : 'TBA' ?></b><span><?= $ev['starts_at'] ? e(fmt_date($ev['starts_at'], 'M')) : 'date' ?></span></div>
          <div class="t"><a href="events.php?id=<?= (int) $ev['id'] ?>"><?= e($ev['title']) ?></a><small><?= e(event_when($ev, 'D g:ia')) ?><?= $ev['location'] ? ' &middot; ' . e($ev['location']) : '' ?> &middot; <?= (int) $ev['regs'] ?> registered</small></div>
          <a class="btn secondary" style="padding:6px 12px;font-size:.78rem" href="event_invite.php?event=<?= (int) $ev['id'] ?>">Invite</a>
          <?php if ($canCount && $ev['starts_at']): ?><form method="post" action="countdowns.php" style="margin:0"><?= csrf_field() ?><input type="hidden" name="event_id" value="<?= (int) $ev['id'] ?>"><button class="btn secondary" style="padding:6px 12px;font-size:.78rem" name="action" value="<?= $ev['pin_dashboard'] ? 'unpin' : 'pin' ?>" title="Show a live countdown for this event on the dashboard"><?= $ev['pin_dashboard'] ? 'Unpin timer' : 'Pin timer' ?></button></form><?php endif; ?>
        </li>
      <?php endforeach; if (!$events): ?><li class="meta">No events yet. <a href="events.php?new=1">Add the first event</a>.</li><?php endif; ?>
    </ul>
  </section>
  <?php endif; ?>
  <?php if ($byChapter): ?>
  <section class="wid w6">
    <h3>Members by chapter <a href="chapter_members.php">View chapters &rarr;</a></h3>
    <div class="hbar">
      <?php foreach ($byChapter as $c): ?><div class="r"><span><?= e(str_replace(' Chapter', '', $c['name'])) ?></span><span class="track"><i style="width:<?= round($c['n'] / $chMax * 100) ?>%"></i></span><b><?= (int) $c['n'] ?></b></div><?php endforeach; ?>
    </div>
    <?php if ($pipeline): ?>
      <h3 style="margin-top:22px">Project pipeline</h3>
      <div class="pipeline">
        <?php foreach (PROJECT_STATUS as $k => [$label, $cls]): if ($k === 'draft' || empty($pipeline[$k])) continue; ?><div class="r"><?= project_badge($k) ?><span class="n"><?= (int) $pipeline[$k] ?></span></div><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>
</div>

<div class="dgrid">
  <?php if ($recentApps): ?>
  <section class="wid w6">
    <h3>Recent members <a href="members.php">All members &rarr;</a></h3>
    <ul class="mini">
      <?php foreach ($recentApps as $r): $ini = mb_strtoupper(mb_substr($r['full_name'], 0, 1) . mb_substr((string) strrchr(' ' . $r['full_name'], ' '), 1, 1)); ?>
        <li><span class="avs"><?= e($ini) ?></span><div class="t"><a href="members.php?id=<?= (int) $r['id'] ?>"><?= e($r['full_name']) ?></a><small><?= e(CATEGORY_LABELS[$r['category']] ?? '') ?> &middot; applied <?= e(fmt_date($r['created_at'], 'j M')) ?></small></div>
          <span class="badge <?= $r['status'] === 'approved' ? '' : ($r['status'] === 'pending' ? 'warn' : 'bad') ?>"><?= e($r['status']) ?></span></li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>
  <?php if (can('messages')): ?>
  <section class="wid w6">
    <h3>Latest messages <a href="messages.php">Inbox &rarr;</a></h3>
    <ul class="mini">
      <?php foreach ($messages as $m): ?>
        <li><span class="avs"><?= icon('mail', 16) ?></span><div class="t"><a href="messages.php"><?= e($m['subject'] ?: $m['category']) ?></a><small><?= e($m['name']) ?> &middot; <?= e(excerpt($m['body'], 60)) ?></small></div><span class="badge <?= $m['status'] === 'new' ? 'warn' : '' ?>"><?= e($m['status']) ?></span></li>
      <?php endforeach; if (!$messages): ?><li class="meta">No messages yet.</li><?php endif; ?>
    </ul>
  </section>
  <?php endif; ?>
</div>

<div class="dgrid">
  <?php if ($check): ?>
  <section class="wid w6" id="setup">
    <h3>Website setup checklist <small><?= $done ?>/<?= count($check) ?> done</small></h3>
    <ul class="checklist">
      <?php foreach ($check as [$ok, $label, $href]): ?>
        <li><span class="<?= $ok ? 'ok' : 'todo' ?>"><?= icon($ok ? 'check-circle' : 'alert', 16) ?></span><?= $ok ? e($label) : '<a href="' . e($href) . '">' . e($label) . '</a>' ?></li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>
  <?php if ($recent): ?>
  <section class="wid w6">
    <h3>Recent activity <a href="audit.php">Audit log &rarr;</a></h3>
    <ul class="mini">
      <?php foreach ($recent as $r): ?>
        <li><span class="avs"><?= icon('activity', 16) ?></span><div class="t"><strong><?= e($r['full_name'] ?? 'system') ?></strong> <?= e(str_replace('_', ' ', $r['action'])) ?><small><?= e(fmt_date($r['created_at'], 'j M, g:ia')) ?> &middot; <?= e(excerpt((string) $r['detail'], 60)) ?></small></div></li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>
</div>
<?php admin_footer(); ?>
