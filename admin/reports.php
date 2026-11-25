<?php
require_once __DIR__ . '/../includes/admin.php';
require_perm('reports');
require_once __DIR__ . '/../includes/careers.php';

// ---- Date range: quick presets or a custom range ----
$today = date('Y-m-d');
$presets = [
    '7' => ['Last 7 days', date('Y-m-d', strtotime('-6 days')), $today],
    '30' => ['Last 30 days', date('Y-m-d', strtotime('-29 days')), $today],
    '90' => ['Last 90 days', date('Y-m-d', strtotime('-89 days')), $today],
    'year' => ['This year', date('Y-01-01'), $today],
    'lastyear' => ['Last year', (date('Y') - 1) . '-01-01', (date('Y') - 1) . '-12-31'],
    'all' => ['All time', '2015-01-01', $today],
];
$isDate = fn($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) !== false;
if (isset($_GET['range'], $presets[$_GET['range']])) {
    [, $from, $to] = $presets[$_GET['range']];
} else {
    $from = $isDate($_GET['from'] ?? null) ? $_GET['from'] : date('Y-01-01');
    $to = $isDate($_GET['to'] ?? null) ? $_GET['to'] : $today;
    if ($from > $to) {
        [$from, $to] = [$to, $from];
    }
}
$activeRange = 'custom';
foreach ($presets as $pk => [, $pf, $pt]) {
    if ($pf === $from && $pt === $to) {
        $activeRange = $pk;
        break;
    }
}
$toEnd = $to . ' 23:59:59';
$rows = fn($sql, $a = []) => array_map('array_values', q($sql, $a)->fetchAll());
$label = fn(array $map) => fn($v) => $map[$v] ?? ($v === null || $v === '' ? 'Not stated' : $v);
$mapCol = function (array $data, int $col, callable $f) {
    return array_map(function ($r) use ($col, $f) {
        $r[$col] = $f($r[$col]);
        return $r;
    }, $data);
};

$reports = [];
$reports['members_status'] = ['Members by category and status', ['Category', 'Approved', 'Pending', 'Rejected', 'Total'],
    $mapCol($rows("SELECT p.category, SUM(u.status='approved'), SUM(u.status='pending'), SUM(u.status='rejected'), COUNT(*) FROM users u JOIN member_profiles p ON p.user_id = u.id WHERE u.role = 'member' GROUP BY p.category"), 0, $label(CATEGORY_LABELS))];
$reports['members_chapter'] = ['Members by chapter', ['Chapter', 'Approved members', 'Pending'],
    $rows("SELECT c.name, SUM(m.status='approved'), SUM(m.status='pending') FROM chapters c LEFT JOIN chapter_members m ON m.chapter_id = c.id GROUP BY c.id ORDER BY c.sort_order, c.name")];
$reports['gender'] = ['Approved members by gender', ['Gender', 'Members'],
    $mapCol($rows("SELECT p.gender, COUNT(*) FROM users u JOIN member_profiles p ON p.user_id = u.id WHERE u.role = 'member' AND u.status = 'approved' GROUP BY p.gender ORDER BY 2 DESC"), 0, $label(GENDER_LABELS))];
$reports['employment'] = ['Approved members by employment status', ['Status', 'Members'],
    $mapCol($rows("SELECT p.employment_status, COUNT(*) FROM users u JOIN member_profiles p ON p.user_id = u.id WHERE u.role = 'member' AND u.status = 'approved' GROUP BY p.employment_status ORDER BY 2 DESC"), 0, $label(EMPLOYMENT_LABELS))];
$reports['qualification'] = ['Approved members by qualification level', ['Level', 'Members'],
    $mapCol($rows("SELECT p.qualification_level, COUNT(*) FROM users u JOIN member_profiles p ON p.user_id = u.id WHERE u.role = 'member' AND u.status = 'approved' GROUP BY p.qualification_level ORDER BY 2 DESC"), 0, $label([]))];
$reports['graduation'] = ['Approved members by graduation year', ['Year', 'Members'],
    $mapCol($rows("SELECT p.graduation_year, COUNT(*) FROM users u JOIN member_profiles p ON p.user_id = u.id WHERE u.role = 'member' AND u.status = 'approved' GROUP BY p.graduation_year ORDER BY p.graduation_year DESC LIMIT 15"), 0, $label([]))];
$reports['pay_month'] = ["Verified payments by month ($from to $to)", ['Month', 'Payments', 'Total (UGX)'],
    $rows("SELECT DATE_FORMAT(verified_at, '%Y-%m'), COUNT(*), SUM(amount) FROM member_payments WHERE status = 'verified' AND verified_at BETWEEN ? AND ? GROUP BY 1 ORDER BY 1", [$from, $toEnd])];
$reports['pay_purpose'] = ["Verified payments by purpose and method ($from to $to)", ['Purpose', 'Method', 'Payments', 'Total (UGX)'],
    $rows("SELECT purpose, method, COUNT(*), SUM(amount) FROM member_payments WHERE status = 'verified' AND verified_at BETWEEN ? AND ? GROUP BY purpose, method ORDER BY 4 DESC", [$from, $toEnd])];
$reports['pay_open'] = ['Payments not yet verified', ['Status', 'Payments', 'Total (UGX)'],
    $rows("SELECT status, COUNT(*), COALESCE(SUM(amount),0) FROM member_payments WHERE status <> 'verified' GROUP BY status")];
$reports['events'] = ["Events and registrations ($from to $to)", ['Event', 'Date', 'Category', 'Status', 'Registrations'],
    $rows("SELECT e.title, DATE_FORMAT(e.starts_at, '%Y-%m-%d'), e.category, e.status, (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id) FROM events e WHERE e.starts_at IS NULL OR e.starts_at BETWEEN ? AND ? ORDER BY e.starts_at", [$from, $toEnd])];
$reports['subscribers'] = ["Newsletter subscribers gained ($from to $to)", ['Month', 'New subscribers'],
    $rows("SELECT DATE_FORMAT(created_at, '%Y-%m'), COUNT(*) FROM subscribers WHERE created_at BETWEEN ? AND ? GROUP BY 1 ORDER BY 1", [$from, $toEnd])];
$reports['messages'] = ["Enquiries by category ($from to $to)", ['Category', 'Messages', 'Still new'],
    $rows("SELECT category, COUNT(*), SUM(status='new') FROM messages WHERE created_at BETWEEN ? AND ? GROUP BY category ORDER BY 2 DESC", [$from, $toEnd])];
$reports['faculty'] = ['Approved members by college and programme', ['College', 'Programme', 'Members'],
    $rows("SELECT COALESCE(NULLIF(p.college,''),'Not stated'), COALESCE(NULLIF(p.programme,''),'Not stated'), COUNT(*) FROM users u JOIN member_profiles p ON p.user_id = u.id WHERE u.role = 'member' AND u.status = 'approved' GROUP BY 1, 2 ORDER BY 3 DESC LIMIT 40")];
$reports['attendance'] = ["Event attendance ($from to $to)", ['Event', 'Date', 'Registered', 'Checked in', 'Attendance %'],
    $rows("SELECT e.title, DATE_FORMAT(e.starts_at, '%Y-%m-%d'), COUNT(r.id), SUM(r.checked_in_at IS NOT NULL), IFNULL(ROUND(100 * SUM(r.checked_in_at IS NOT NULL) / NULLIF(COUNT(r.id), 0)), 0) FROM events e LEFT JOIN event_registrations r ON r.event_id = e.id WHERE e.starts_at BETWEEN ? AND ? GROUP BY e.id ORDER BY e.starts_at", [$from, $toEnd])];
$reports['careers'] = ['Opportunities and applications', ['Type', 'Opportunities', 'Applications'],
    $mapCol($rows("SELECT j.kind, COUNT(DISTINCT j.id), COUNT(a.id) FROM jobs j LEFT JOIN job_applications a ON a.job_id = j.id GROUP BY j.kind ORDER BY 2 DESC"), 0, $label(JOB_KINDS))];
if (can('donations')) {
    $reports['don_campaign'] = ["Verified donations by campaign ($from to $to)", ['Campaign', 'Gifts', 'Total (UGX)'],
        $rows("SELECT COALESCE(c.title, 'General giving'), COUNT(*), SUM(d.amount) FROM donations d LEFT JOIN campaigns c ON c.id = d.campaign_id WHERE d.status = 'verified' AND d.verified_at BETWEEN ? AND ? GROUP BY d.campaign_id ORDER BY 3 DESC", [$from, $toEnd])];
    $reports['don_method'] = ["Verified donations by method and month ($from to $to)", ['Month', 'Method', 'Gifts', 'Total (UGX)'],
        $mapCol($rows("SELECT DATE_FORMAT(verified_at, '%Y-%m'), method, COUNT(*), SUM(amount) FROM donations WHERE status = 'verified' AND verified_at BETWEEN ? AND ? GROUP BY 1, 2 ORDER BY 1, 4 DESC", [$from, $toEnd]), 1, $label(DONATION_METHODS))];
}
$reports['activity'] = ["Administrative activity ($from to $to)", ['Action', 'Times'],
    $rows("SELECT action, COUNT(*) FROM audit_log WHERE created_at BETWEEN ? AND ? GROUP BY action ORDER BY 2 DESC LIMIT 30", [$from, $toEnd])];
if (can('projects')) {
    $reports['projects'] = ['Projects by status', ['Status', 'Projects', 'Budget (UGX)', 'Verified mobilised (UGX)'],
        $mapCol($rows("SELECT p.status, COUNT(*), SUM(p.budget), COALESCE(SUM((SELECT SUM(x.amount) FROM project_updates x WHERE x.project_id = p.id AND x.kind = 'mobilisation' AND x.verified = 1)), 0) FROM projects p WHERE p.status <> 'draft' GROUP BY p.status"), 0, fn($s) => PROJECT_STATUS[$s][0] ?? $s)];
    $reports['project_theme'] = ['Projects by theme', ['Theme', 'Projects', 'Budget (UGX)'],
        $rows("SELECT theme, COUNT(*), SUM(budget) FROM projects WHERE status <> 'draft' GROUP BY theme ORDER BY 2 DESC")];
}

$reports['apps_month'] = ["New applications by month ($from to $to)", ['Month', 'Applications'],
    $rows("SELECT DATE_FORMAT(created_at, '%Y-%m'), COUNT(*) FROM users WHERE role = 'member' AND created_at BETWEEN ? AND ? GROUP BY 1 ORDER BY 1", [$from, $toEnd])];

if (isset($_GET['export']) && isset($reports[$_GET['export']])) {
    [$title, $head, $data] = $reports[$_GET['export']];
    audit('export_report', $_GET['export']);
    csv_download('guco-' . $_GET['export'] . '-' . date('Ymd') . '.csv', $head, $data);
}

// ---- Headline figures, with a comparison against the period just before ----
$days = max(1, (int) floor((strtotime($to) - strtotime($from)) / 86400) + 1);
$prevTo = date('Y-m-d', strtotime($from . ' -1 day'));
$prevFrom = date('Y-m-d', strtotime($prevTo . ' -' . ($days - 1) . ' days'));
$canCompare = $days <= 800;
$figure = function (string $sql, string $a, string $b) {
    return (float) q($sql, [$a . ' 00:00:00', $b . ' 23:59:59'])->fetchColumn();
};
$sqls = [
    'income' => "SELECT COALESCE(SUM(amount),0) FROM member_payments WHERE status = 'verified' AND verified_at BETWEEN ? AND ?",
    'apps' => "SELECT COUNT(*) FROM users WHERE role = 'member' AND created_at BETWEEN ? AND ?",
    'regs' => 'SELECT COUNT(*) FROM event_registrations WHERE created_at BETWEEN ? AND ?',
    'subs' => 'SELECT COUNT(*) FROM subscribers WHERE created_at BETWEEN ? AND ?',
];
$cur = $prev = [];
foreach ($sqls as $k => $sql) {
    $cur[$k] = $figure($sql, $from, $to);
    $prev[$k] = $canCompare ? $figure($sql, $prevFrom, $prevTo) : null;
}
$members = (int) q("SELECT COUNT(*) FROM users WHERE role = 'member' AND status = 'approved'")->fetchColumn();
$pending = (int) q("SELECT COUNT(*) FROM users WHERE role = 'member' AND status = 'pending'")->fetchColumn();
$kpis = [
    ['Members (approved)', $members, 'users', 'green', null, $pending ? $pending . ' awaiting approval' : 'Nobody waiting'],
    ['Verified income (UGX)', $cur['income'], 'credit-card', 'gold', $prev['income'], ''],
    ['New applications', $cur['apps'], 'user-plus', 'blue', $prev['apps'], ''],
    ['Event registrations', $cur['regs'], 'calendar', 'teal', $prev['regs'], ''],
    ['Newsletter subscribers gained', $cur['subs'], 'mail', 'violet', $prev['subs'], ''],
];

// ---- Which tab each report lives in, and how it is drawn ----
$tabs = [
    'overview' => ['Overview', 'grid'],
    'members' => ['Members', 'users'],
    'finance' => ['Finance', 'credit-card'],
    'engage' => ['Events and engagement', 'calendar'],
    'projects' => ['Projects', 'target'],
    'activity' => ['Activity', 'activity'],
];
$where = [
    'members_status' => 'members', 'members_chapter' => 'members', 'gender' => 'members', 'employment' => 'members', 'qualification' => 'members', 'graduation' => 'members', 'faculty' => 'members', 'apps_month' => 'members',
    'pay_month' => 'finance', 'pay_purpose' => 'finance', 'pay_open' => 'finance', 'don_campaign' => 'finance', 'don_method' => 'finance',
    'events' => 'engage', 'attendance' => 'engage', 'subscribers' => 'engage', 'messages' => 'engage', 'careers' => 'engage',
    'projects' => 'projects', 'project_theme' => 'projects', 'activity' => 'activity',
];
// chart type, label column(s), value column, unit
$draw = [
    'members_status' => ['bars', [0], 1, 'approved'], 'members_chapter' => ['bars', [0], 1, 'approved'], 'gender' => ['donut', [0], 1, ''], 'employment' => ['donut', [0], 1, ''],
    'qualification' => ['bars', [0], 1, ''], 'graduation' => ['columns', [0], 1, ''], 'faculty' => ['bars', [0, 1], 2, ''], 'apps_month' => ['columns', [0], 1, ''],
    'pay_month' => ['columns', [0], 2, 'UGX'], 'pay_purpose' => ['bars', [0, 1], 3, 'UGX'], 'pay_open' => ['bars', [0], 2, 'UGX'], 'don_campaign' => ['bars', [0], 2, 'UGX'],
    'attendance' => ['bars', [0], 4, '%'], 'subscribers' => ['columns', [0], 1, ''], 'messages' => ['donut', [0], 1, ''], 'careers' => ['bars', [0], 1, ''],
    'projects' => ['bars', [0], 1, ''], 'project_theme' => ['bars', [0], 1, ''], 'activity' => ['bars', [0], 1, ''],
];
$palette = ['#14532d', '#f2b807', '#0f766e', '#1d4ed8', '#be185d', '#6d28d9', '#b45309', '#475569'];
$nf = fn($v) => number_format((float) $v);
$short = function ($n) {
    $n = (float) $n;
    return $n >= 1e6 ? round($n / 1e6, 1) . 'M' : ($n >= 1e3 ? round($n / 1e3, 1) . 'k' : (string) round($n));
};
$monthLabel = fn($v) => preg_match('/^\d{4}-\d{2}$/', (string) $v) ? date('M y', strtotime($v . '-01')) : (string) $v;

$chart = function (string $key, array $head, array $data) use ($draw, $palette, $nf, $short, $monthLabel): string {
    if (!isset($draw[$key]) || !$data) {
        return '';
    }
    [$type, $lab, $vcol, $unit] = $draw[$key];
    $items = [];
    foreach ($data as $r) {
        $name = implode(' - ', array_map(fn($i) => $monthLabel($r[$i] ?? ''), $lab));
        $items[] = [$name, (float) ($r[$vcol] ?? 0), $r];
    }
    $sum = array_sum(array_column($items, 1));
    $max = max(1, ...array_column($items, 1));
    $vlab = $head[$vcol] ?? '';
    ob_start();
    if ($type === 'donut') {
        $acc = 0;
        $stops = [];
        foreach ($items as $i => [$n, $v]) {
            $p = $sum > 0 ? $v / $sum * 100 : 0;
            $stops[] = $palette[$i % 8] . " {$acc}% " . ($acc + $p) . '%';
            $acc += $p;
        } ?>
      <div class="rp-donut"><div class="rp-ring" style="background:conic-gradient(<?= e(implode(', ', $stops ?: ['#e3e8ef 0 100%'])) ?>)" role="img" aria-label="<?= e($vlab) ?> split"><b><?= $nf($sum) ?></b><small>total</small></div>
        <ul class="rp-legend"><?php foreach ($items as $i => [$n, $v]): ?><li><i style="background:<?= $palette[$i % 8] ?>"></i><span><?= e($n) ?></span><b><?= $nf($v) ?></b><small><?= $sum > 0 ? round($v / $sum * 100) : 0 ?>%</small></li><?php endforeach; ?></ul></div>
    <?php } elseif ($type === 'columns') { ?>
      <div class="rp-cols" role="img" aria-label="<?= e($vlab) ?> over time"><?php foreach ($items as [$n, $v, $r]): $h = max(3, round($v / $max * 100)); ?>
        <div class="rp-col" title="<?= e($n . ': ' . $nf($v) . ($unit ? ' ' . $unit : '')) ?>"><span class="rp-val"><?= $short($v) ?></span><i style="height:<?= $h ?>%"></i><em><?= e($n) ?></em></div><?php endforeach; ?></div>
    <?php } else { $shown = array_slice($items, 0, 10); ?>
      <ul class="rp-bars"><?php foreach ($shown as $i => [$n, $v]): ?>
        <li><span class="rp-n" title="<?= e($n) ?>"><?= e($n) ?></span><span class="rp-track"><i style="width:<?= max(2, round($v / $max * 100)) ?>%;background:<?= $palette[$i % 8] ?>"></i></span><b><?= $nf($v) ?><?= $unit === '%' ? '%' : '' ?></b></li><?php endforeach; ?>
      </ul><?php if (count($items) > 10): ?><p class="rp-more">Top 10 of <?= count($items) ?>. Switch to the table to see all.</p><?php endif; ?>
    <?php }
    return ob_get_clean();
};

$totalsFor = function (array $head, array $data) {
    if (count($data) < 2) {
        return null;
    }
    $tot = [];
    foreach ($head as $i => $h) {
        if ($i === 0 || stripos($h, '%') !== false || stripos($h, 'Date') !== false || stripos($h, 'Year') !== false) {
            $tot[$i] = '';
            continue;
        }
        $ok = true;
        $sum = 0;
        foreach ($data as $r) {
            if (!is_numeric($r[$i] ?? null)) {
                $ok = false;
                break;
            }
            $sum += $r[$i];
        }
        $tot[$i] = $ok ? number_format($sum) : '';
    }
    return array_filter($tot, fn($v) => $v !== '') ? $tot : null;
};
$delta = function ($now, $before) {
    if ($before === null) {
        return '';
    }
    if ((float) $before == 0) {
        return $now > 0 ? '<span class="rp-delta up">New activity</span>' : '<span class="rp-delta flat">No change</span>';
    }
    $p = round(($now - $before) / $before * 100);
    if ($p == 0) {
        return '<span class="rp-delta flat">No change</span>';
    }
    return '<span class="rp-delta ' . ($p > 0 ? 'up' : 'down') . '">' . ($p > 0 ? '&#9650; +' : '&#9660; ') . $p . '%</span>';
};
$rangeLabel = date('j M Y', strtotime($from)) . ' to ' . date('j M Y', strtotime($to));
$counts = array_fill_keys(array_keys($tabs), 0);
foreach ($reports as $key => $_) {
    $counts[$where[$key] ?? 'activity']++;
}
admin_header('Reports & analytics', 'Membership, finance and engagement figures, with charts and CSV export');
?>
<div class="rp">
  <form method="get" class="rp-range" id="rpRange">
    <div class="rp-presets" role="group" aria-label="Choose a period">
      <?php foreach ($presets as $pk => [$pl]): ?><a href="?range=<?= e($pk) ?>" class="<?= $activeRange === $pk ? 'on' : '' ?>"><?= e($pl) ?></a><?php endforeach; ?>
      <button type="button" class="<?= $activeRange === 'custom' ? 'on' : '' ?>" id="rpCustomBtn" aria-expanded="<?= $activeRange === 'custom' ? 'true' : 'false' ?>">Custom</button>
    </div>
    <div class="rp-custom" id="rpCustom"<?= $activeRange === 'custom' ? '' : ' hidden' ?>>
      <label>From <input type="date" name="from" value="<?= e($from) ?>"></label>
      <label>To <input type="date" name="to" value="<?= e($to) ?>"></label>
      <button>Apply</button>
    </div>
    <p class="rp-now"><?= icon('calendar', 16) ?> Showing <b><?= e($rangeLabel) ?></b> (<?= $days ?> day<?= $days === 1 ? '' : 's' ?>). The range applies to finance, applications, events, subscribers and messages. Member profile reports show the current position.</p>
  </form>

  <div class="rp-kpis">
    <?php foreach ($kpis as [$l, $v, $ic, $tone, $pv, $note]): ?>
      <div class="rp-kpi tone-<?= e($tone) ?>"><span class="rp-ic"><?= icon($ic, 22) ?></span>
        <div><div class="rp-l"><?= e($l) ?></div><div class="rp-v" data-n="<?= (float) $v ?>"><?= $nf($v) ?></div>
        <div class="rp-s"><?= $pv !== null ? $delta($v, $pv) . ' <small>vs the period before</small>' : e($note) ?></div></div></div>
    <?php endforeach; ?>
  </div>

  <div class="rp-bar">
    <div class="rp-tabs" role="tablist" aria-label="Report topics" id="rpTabs">
      <?php foreach ($tabs as $tk => [$tl, $ti]): if ($tk !== 'overview' && !$counts[$tk]) continue; ?>
        <button type="button" role="tab" data-t="<?= e($tk) ?>" aria-selected="<?= $tk === 'overview' ? 'true' : 'false' ?>" class="<?= $tk === 'overview' ? 'on' : '' ?>"><?= icon($ti, 16) ?> <?= e($tl) ?><?= $tk !== 'overview' ? ' <i>' . $counts[$tk] . '</i>' : '' ?></button>
      <?php endforeach; ?>
      <button type="button" role="tab" data-t="all" aria-selected="false">All reports <i><?= count($reports) ?></i></button>
    </div>
    <label class="rp-find"><?= icon('search', 16) ?><input type="search" id="rpFind" placeholder="Find a report..." aria-label="Find a report"></label>
    <button type="button" class="rp-print" onclick="window.print()"><?= icon('file', 16) ?> Print</button>
  </div>

  <section class="rp-panel" data-panel="overview" id="rpOverview">
    <div class="rp-two">
      <?php foreach (['pay_month' => 'Verified income by month', 'apps_month' => 'New applications by month'] as $ck => $ct): if (!isset($reports[$ck])) continue; [$ttl, $hd, $dt] = $reports[$ck]; ?>
        <article class="rp-card"><header><h3><?= e($ct) ?></h3><a href="#<?= e($where[$ck]) ?>" data-go="<?= e($where[$ck]) ?>">Open &rarr;</a></header>
          <?= $chart($ck, $hd, $dt) ?: '<p class="rp-empty">Nothing recorded in this period.</p>' ?></article>
      <?php endforeach; ?>
    </div>
    <div class="rp-two">
      <?php foreach (['gender' => 'Members by gender', 'members_chapter' => 'Members by chapter'] as $ck => $ct): if (!isset($reports[$ck])) continue; [$ttl, $hd, $dt] = $reports[$ck]; ?>
        <article class="rp-card"><header><h3><?= e($ct) ?></h3><a href="#members" data-go="members">Open &rarr;</a></header>
          <?= $chart($ck, $hd, $dt) ?: '<p class="rp-empty">No data yet.</p>' ?></article>
      <?php endforeach; ?>
    </div>
    <div class="rp-jump">
      <?php foreach ($tabs as $tk => [$tl, $ti]): if ($tk === 'overview' || !$counts[$tk]) continue; ?>
        <a href="#<?= e($tk) ?>" data-go="<?= e($tk) ?>"><span><?= icon($ti, 22) ?></span><b><?= e($tl) ?></b><small><?= $counts[$tk] ?> report<?= $counts[$tk] === 1 ? '' : 's' ?></small></a>
      <?php endforeach; ?>
    </div>
  </section>

  <div class="rp-grid" id="rpGrid">
    <?php foreach ($reports as $key => [$title, $head, $data]):
        $ch = $chart($key, $head, $data);
        $tot = $totalsFor($head, $data);
        $tabKey = $where[$key] ?? 'activity'; ?>
      <article class="rp-card" data-panel="<?= e($tabKey) ?>" data-title="<?= e(strtolower($title)) ?>" hidden>
        <header><h3><?= e($title) ?></h3>
          <?php if ($ch): ?><div class="rp-vt" role="group" aria-label="Chart or table"><button type="button" class="on" data-v="chart">Chart</button><button type="button" data-v="table">Table</button></div><?php endif; ?></header>
        <?php if ($ch): ?><div class="rp-chart"><?= $ch ?></div><?php endif; ?>
        <div class="rp-tablebox"<?= $ch ? ' hidden' : '' ?>><div class="table-wrap" style="box-shadow:none"><table>
          <tr><?php foreach ($head as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr>
          <?php foreach ($data as $r): ?><tr><?php foreach ($r as $i => $cell): ?><td><?= is_numeric($cell) && $i > 0 && !preg_match('/^\d{4}$/', (string) $cell) ? e(number_format((float) $cell)) : e($cell) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
          <?php if ($tot): ?><tr class="rp-total"><?php foreach ($head as $i => $h): ?><td><?= $i === 0 ? 'Total' : e($tot[$i] ?? '') ?></td><?php endforeach; ?></tr><?php endif; ?>
          <?php if (!$data): ?><tr><td colspan="<?= count($head) ?>">No data for this report yet.</td></tr><?php endif; ?>
        </table></div></div>
        <footer><span class="rp-rows"><?= count($data) ?> row<?= count($data) === 1 ? '' : 's' ?></span>
          <a class="btn secondary" href="?<?= e(http_build_query(['export' => $key, 'from' => $from, 'to' => $to])) ?>"><?= icon('download', 14) ?> Export CSV</a></footer>
      </article>
    <?php endforeach; ?>
  </div>
  <p class="rp-nomatch" id="rpNone" hidden>No report matches that. Try another word.</p>
</div>
<script>
(function () {
  var root = document.querySelector('.rp'); if (!root) return;
  var tabs = root.querySelector('#rpTabs'), cards = [].slice.call(root.querySelectorAll('.rp-grid .rp-card')), over = root.querySelector('#rpOverview'), none = root.querySelector('#rpNone');
  var find = root.querySelector('#rpFind'), cur = 'overview';
  function show(t, fromHash) {
    cur = t; var term = find.value.trim().toLowerCase(), n = 0;
    tabs.querySelectorAll('button').forEach(function (b) { var on = b.dataset.t === t; b.classList.toggle('on', on); b.setAttribute('aria-selected', on ? 'true' : 'false'); });
    over.hidden = !(t === 'overview' && !term);
    cards.forEach(function (c) {
      var inTab = t === 'all' || c.dataset.panel === t || (t === 'overview' && term);
      var hit = !term || c.dataset.title.indexOf(term) !== -1;
      c.hidden = !(inTab && hit); if (!c.hidden) n++;
    });
    none.hidden = n > 0 || (t === 'overview' && !term);
    if (!fromHash) { try { history.replaceState(null, '', '#' + t); } catch (e) {} }
  }
  tabs.addEventListener('click', function (e) { var b = e.target.closest('button'); if (b) show(b.dataset.t); });
  root.querySelectorAll('[data-go]').forEach(function (a) { a.addEventListener('click', function (e) { e.preventDefault(); show(a.dataset.go); root.querySelector('.rp-bar').scrollIntoView({ behavior: 'smooth', block: 'start' }); }); });
  find.addEventListener('input', function () { show(cur === 'overview' && find.value.trim() ? 'all' : cur); });
  root.querySelectorAll('.rp-vt').forEach(function (g) {
    g.addEventListener('click', function (e) {
      var b = e.target.closest('button'); if (!b) return; var card = g.closest('.rp-card');
      g.querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x === b); });
      card.querySelector('.rp-chart').hidden = b.dataset.v !== 'chart'; card.querySelector('.rp-tablebox').hidden = b.dataset.v !== 'table';
    });
  });
  var cb = root.querySelector('#rpCustomBtn'), cp = root.querySelector('#rpCustom');
  cb.addEventListener('click', function () { cp.hidden = !cp.hidden; cb.setAttribute('aria-expanded', cp.hidden ? 'false' : 'true'); });
  // Numbers count up once.
  if (!(window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches)) {
    root.querySelectorAll('.rp-v').forEach(function (el) {
      var n = parseFloat(el.dataset.n) || 0, t0 = null, fmt = function (v) { return Math.round(v).toLocaleString('en-US'); };
      if (n === 0) return;
      (function step(t) { if (!t0) t0 = t; var p = Math.min(1, (t - t0) / 900); el.textContent = fmt(n * (1 - Math.pow(1 - p, 3))); if (p < 1) requestAnimationFrame(step); })(performance.now());
    });
  }
  var h = location.hash.replace('#', ''); var valid = [].map.call(tabs.querySelectorAll('button'), function (b) { return b.dataset.t; });
  show(valid.indexOf(h) !== -1 ? h : 'overview', true);
})();
</script>
<?php admin_footer(); ?>
