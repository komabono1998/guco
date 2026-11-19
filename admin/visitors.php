<?php
require_once __DIR__ . '/../includes/admin.php';
require_perm('analytics');

$days = (int) ($_GET['days'] ?? 30);
if (!in_array($days, [7, 30, 90, 365], true)) {
    $days = 30;
}
$since = date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'));
$ready = true;
try {
    $total = q('SELECT COUNT(*) pv, COUNT(DISTINCT visitor) uv, COUNT(DISTINCT country) cc FROM site_visits WHERE visited_at >= ?', [$since])->fetch();
} catch (Throwable $e) {
    $ready = false;
}
admin_header('Website visitors', 'Who is visiting the public website, from where, and what they read.');
if (!$ready): ?>
  <div class="notice"><strong>Visitor statistics are not set up yet.</strong> Run <code>php tools/migrate.php</code>, or import <code>sql/migrations/030_site_visits.sql</code> in phpMyAdmin, then reload this page.</div>
<?php admin_footer(); exit; endif;

$today = q('SELECT COUNT(*) pv, COUNT(DISTINCT visitor) uv FROM site_visits WHERE visited_at >= CURDATE()')->fetch();
$online = (int) q('SELECT COUNT(DISTINCT visitor) FROM site_visits WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)')->fetchColumn();
$returning = (int) q('SELECT COUNT(*) FROM (SELECT visitor FROM site_visits WHERE visited_at >= ? GROUP BY visitor HAVING COUNT(DISTINCT DATE(visited_at)) > 1) t', [$since])->fetchColumn();
$perVisit = $total['uv'] ? round($total['pv'] / $total['uv'], 1) : 0;

// Daily series with zero-filled gaps (weekly buckets for a year).
$byDay = [];
foreach (q('SELECT DATE(visited_at) d, COUNT(*) pv, COUNT(DISTINCT visitor) uv FROM site_visits WHERE visited_at >= ? GROUP BY DATE(visited_at)', [$since])->fetchAll() as $r) {
    $byDay[$r['d']] = $r;
}
$series = [];
for ($i = $days - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $series[] = ['d' => $d, 'pv' => (int) ($byDay[$d]['pv'] ?? 0), 'uv' => (int) ($byDay[$d]['uv'] ?? 0)];
}
if ($days === 365) {
    $weekly = [];
    foreach (array_chunk($series, 7) as $chunk) {
        $weekly[] = ['d' => $chunk[0]['d'], 'pv' => array_sum(array_column($chunk, 'pv')), 'uv' => array_sum(array_column($chunk, 'uv'))];
    }
    $series = $weekly;
}

$pages = q('SELECT page, MAX(title) title, COUNT(*) views, COUNT(DISTINCT visitor) uv FROM site_visits WHERE visited_at >= ? GROUP BY page ORDER BY views DESC LIMIT 12', [$since])->fetchAll();
$countries = q('SELECT country, COUNT(*) views, COUNT(DISTINCT visitor) uv FROM site_visits WHERE visited_at >= ? GROUP BY country ORDER BY uv DESC, views DESC LIMIT 12', [$since])->fetchAll();
$devices = q('SELECT device, COUNT(DISTINCT visitor) uv FROM site_visits WHERE visited_at >= ? GROUP BY device ORDER BY uv DESC', [$since])->fetchAll();
$refs = q('SELECT referrer, COUNT(*) views FROM site_visits WHERE visited_at >= ? AND referrer IS NOT NULL GROUP BY referrer ORDER BY views DESC LIMIT 8', [$since])->fetchAll();
$direct = (int) q('SELECT COUNT(*) FROM site_visits WHERE visited_at >= ? AND referrer IS NULL', [$since])->fetchColumn();
$hours = array_fill(0, 24, 0);
foreach (q('SELECT HOUR(visited_at) h, COUNT(*) c FROM site_visits WHERE visited_at >= ? GROUP BY HOUR(visited_at)', [$since])->fetchAll() as $r) {
    $hours[(int) $r['h']] = (int) $r['c'];
}
$recent = q('SELECT visited_at, page, title, country, device FROM site_visits ORDER BY id DESC LIMIT 15')->fetchAll();

// ---- Area chart (inline SVG) ----
$W = 760; $H = 230; $padL = 36; $padB = 26; $padT = 10; $padR = 8;
$max = max(1, ...array_column($series, 'pv'));
$step = $max <= 5 ? 1 : (int) ceil($max / 5);
$top = $step * (int) ceil($max / $step);
$n = count($series);
$x = fn($i) => $padL + ($n > 1 ? $i * ($W - $padL - $padR) / ($n - 1) : 0);
$y = fn($v) => $padT + ($H - $padT - $padB) * (1 - $v / $top);
$line = function (string $key) use ($series, $x, $y) {
    $pts = [];
    foreach ($series as $i => $s) { $pts[] = round($x($i), 1) . ',' . round($y($s[$key]), 1); }
    return implode(' ', $pts);
};
$area = fn(string $key) => $line($key) . ' ' . round($x($n - 1), 1) . ',' . ($H - $padB) . ' ' . round($x(0), 1) . ',' . ($H - $padB);
$maxPage = max(1, ...array_map(fn($r) => (int) $r['views'], $pages ?: [['views' => 1]]));
$maxCountry = max(1, ...array_map(fn($r) => (int) $r['uv'], $countries ?: [['uv' => 1]]));
$devTotal = max(1, array_sum(array_column($devices, 'uv')));
$devColor = ['desktop' => '#14532d', 'mobile' => '#f2b807', 'tablet' => '#0f766e'];
$conic = []; $acc = 0;
foreach ($devices as $d) {
    $p = $d['uv'] / $devTotal * 100;
    $conic[] = ($devColor[$d['device']] ?? '#999') . " {$acc}% " . ($acc + $p) . '%';
    $acc += $p;
}
$maxHour = max(1, ...$hours);
?>
<style>
.vz { display: grid; gap: 16px; }
.vz-top { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
.vz-range { display: inline-flex; background: var(--adm-card); border: 1px solid var(--adm-line); border-radius: 999px; padding: 3px; }
.vz-range a { padding: 6px 14px; border-radius: 999px; font-weight: 600; font-size: .85rem; color: var(--adm-muted); text-decoration: none; }
.vz-range a.on { background: var(--green); color: #fff; }
.vz-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; }
.vz-kpi { background: var(--adm-card); border: 1px solid var(--adm-line); border-radius: var(--adm-r); padding: 16px 18px; box-shadow: var(--adm-shadow); position: relative; overflow: hidden; }
.vz-kpi::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: var(--gold); }
.vz-kpi b { display: block; font-size: 1.9rem; line-height: 1.1; color: var(--adm-ink); }
.vz-kpi span { color: var(--adm-muted); font-size: .85rem; }
.vz-kpi i { font-style: normal; display: inline-block; width: 9px; height: 9px; border-radius: 50%; background: #22c55e; margin-right: 6px; box-shadow: 0 0 0 0 rgba(34, 197, 94, .6); animation: vzp 1.8s infinite; }
@keyframes vzp { 70% { box-shadow: 0 0 0 9px rgba(34, 197, 94, 0); } 100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); } }
.vz-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 16px; }
.vz-card { background: var(--adm-card); border: 1px solid var(--adm-line); border-radius: var(--adm-r); padding: 18px; box-shadow: var(--adm-shadow); min-width: 0; }
.vz-card h2 { margin: 0 0 12px; font-size: 1.02rem; color: var(--adm-ink); display: flex; justify-content: space-between; align-items: baseline; gap: 8px; }
.vz-card h2 small { font-weight: 500; color: var(--adm-muted); font-size: .78rem; }
.vz-chart { width: 100%; height: auto; display: block; }
.vz-legend { display: flex; gap: 16px; font-size: .82rem; color: var(--adm-muted); margin-bottom: 6px; }
.vz-legend i { display: inline-block; width: 10px; height: 10px; border-radius: 3px; margin-right: 5px; vertical-align: -1px; }
.vz-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 4px 12px; align-items: center; padding: 7px 0; border-bottom: 1px solid var(--adm-line); font-size: .9rem; }
.vz-row:last-child { border-bottom: 0; }
.vz-row .nm { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--adm-ink); }
.vz-row .nm small { color: var(--adm-muted); }
.vz-row .num { font-weight: 700; color: var(--adm-ink); font-variant-numeric: tabular-nums; }
.vz-bar { grid-column: 1 / -1; height: 6px; border-radius: 6px; background: #eef2f6; overflow: hidden; }
.vz-bar s { display: block; height: 100%; border-radius: 6px; background: linear-gradient(90deg, var(--green), #2f9e5b); text-decoration: none; }
.vz-bar.gold s { background: linear-gradient(90deg, var(--gold), #f7d35a); }
.vz-flag { font-size: 1.15rem; margin-right: 6px; }
.vz-donut { display: flex; align-items: center; gap: 22px; flex-wrap: wrap; }
.vz-ring { width: 130px; height: 130px; border-radius: 50%; position: relative; flex: none; }
.vz-ring::after { content: ""; position: absolute; inset: 24px; border-radius: 50%; background: var(--adm-card); }
.vz-hours { display: flex; align-items: flex-end; gap: 3px; height: 110px; }
.vz-hours div { flex: 1; background: linear-gradient(0deg, var(--green), #6fcf97); border-radius: 3px 3px 0 0; min-height: 2px; opacity: .85; }
.vz-hours div:hover { opacity: 1; }
.vz-hlab { display: flex; justify-content: space-between; font-size: .72rem; color: var(--adm-muted); margin-top: 4px; }
.vz-empty { color: var(--adm-muted); padding: 10px 0; }
@media (max-width: 600px) { .vz-grid { grid-template-columns: 1fr; } }
</style>

<div class="vz">
  <div class="vz-top">
    <div class="meta">Counts public page views only. Staff, search-engine bots and "Do Not Track" browsers are left out. No IP addresses or cookies are stored.</div>
    <div class="vz-range" role="tablist" aria-label="Time range">
      <?php foreach ([7 => '7 days', 30 => '30 days', 90 => '90 days', 365 => '1 year'] as $d => $l): ?>
        <a href="?days=<?= $d ?>" class="<?= $d === $days ? 'on' : '' ?>"><?= e($l) ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="vz-kpis">
    <div class="vz-kpi"><b><?= number_format((int) $total['uv']) ?></b><span>Unique visitors</span></div>
    <div class="vz-kpi"><b><?= number_format((int) $total['pv']) ?></b><span>Page views</span></div>
    <div class="vz-kpi"><b><?= number_format((int) $today['uv']) ?></b><span>Visitors today (<?= number_format((int) $today['pv']) ?> views)</span></div>
    <div class="vz-kpi"><b><?= $online ?></b><span><i></i>Active in the last 5 minutes</span></div>
    <div class="vz-kpi"><b><?= $perVisit ?></b><span>Pages per visitor</span></div>
    <div class="vz-kpi"><b><?= number_format((int) $total['cc']) ?></b><span>Countries reached</span></div>
    <div class="vz-kpi"><b><?= number_format($returning) ?></b><span>Returning visitors</span></div>
  </div>

  <div class="vz-card">
    <h2>Visits over time <small><?= $days === 365 ? 'by week' : 'by day' ?></small></h2>
    <div class="vz-legend"><span><i style="background:#14532d"></i>Page views</span><span><i style="background:#f2b807"></i>Unique visitors</span></div>
    <svg class="vz-chart" viewBox="0 0 <?= $W ?> <?= $H ?>" role="img" aria-label="Page views and visitors over time">
      <?php for ($g = 0; $g <= $top; $g += $step): ?>
        <line x1="<?= $padL ?>" x2="<?= $W - $padR ?>" y1="<?= round($y($g), 1) ?>" y2="<?= round($y($g), 1) ?>" stroke="#e3e8ef" stroke-width="1"/>
        <text x="<?= $padL - 6 ?>" y="<?= round($y($g) + 4, 1) ?>" text-anchor="end" font-size="10" fill="#647087"><?= $g ?></text>
      <?php endfor; ?>
      <polygon points="<?= $area('pv') ?>" fill="#14532d" opacity=".14"/>
      <polyline points="<?= $line('pv') ?>" fill="none" stroke="#14532d" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round"/>
      <polygon points="<?= $area('uv') ?>" fill="#f2b807" opacity=".18"/>
      <polyline points="<?= $line('uv') ?>" fill="none" stroke="#d99a00" stroke-width="2.2" stroke-linejoin="round" stroke-linecap="round"/>
      <?php $labelEvery = max(1, (int) ceil($n / 8)); foreach ($series as $i => $s): ?>
        <circle cx="<?= round($x($i), 1) ?>" cy="<?= round($y($s['pv']), 1) ?>" r="<?= $n > 40 ? 0 : 3 ?>" fill="#14532d"><title><?= e(date('D j M Y', strtotime($s['d']))) ?>: <?= $s['pv'] ?> views, <?= $s['uv'] ?> visitors</title></circle>
        <?php if ($i % $labelEvery === 0): ?><text x="<?= round($x($i), 1) ?>" y="<?= $H - 8 ?>" text-anchor="middle" font-size="10" fill="#647087"><?= e(date('j M', strtotime($s['d']))) ?></text><?php endif; ?>
      <?php endforeach; ?>
    </svg>
  </div>

  <div class="vz-grid">
    <div class="vz-card">
      <h2>Most visited pages <small>views</small></h2>
      <?php foreach ($pages as $p): ?>
        <div class="vz-row"><span class="nm" title="<?= e($p['page']) ?>"><?= e($p['page'] === '/' ? 'Home page' : ($p['title'] ?: $p['page'])) ?> <small><?= e($p['page'] === '/' ? '' : $p['page']) ?></small></span><span class="num"><?= number_format((int) $p['views']) ?></span>
          <div class="vz-bar"><s style="width:<?= round($p['views'] / $maxPage * 100) ?>%"></s></div></div>
      <?php endforeach; if (!$pages): ?><div class="vz-empty">No visits recorded yet. Open the public website and they will start to appear.</div><?php endif; ?>
    </div>

    <div class="vz-card">
      <h2>Visitors by country <small>unique visitors</small></h2>
      <?php foreach ($countries as $c): ?>
        <div class="vz-row"><span class="nm"><span class="vz-flag"><?= visit_flag($c['country']) ?></span><?= e(visit_country_name($c['country'])) ?></span><span class="num"><?= number_format((int) $c['uv']) ?></span>
          <div class="vz-bar gold"><s style="width:<?= round($c['uv'] / $maxCountry * 100) ?>%"></s></div></div>
      <?php endforeach; if (!$countries): ?><div class="vz-empty">No visitors yet.</div><?php endif; ?>
      <p class="meta" style="margin:10px 0 0">"Unknown" means the country could not be worked out (for example, a private network or a lookup that has not finished).</p>
    </div>

    <div class="vz-card">
      <h2>Devices <small>unique visitors</small></h2>
      <?php if ($devices): ?>
      <div class="vz-donut">
        <div class="vz-ring" style="background:conic-gradient(<?= e(implode(', ', $conic)) ?>)" role="img" aria-label="Device split"></div>
        <div>
          <?php foreach ($devices as $d): ?><div class="vz-row" style="grid-template-columns:auto auto;gap:14px"><span class="nm"><i style="display:inline-block;width:10px;height:10px;border-radius:3px;background:<?= $devColor[$d['device']] ?? '#999' ?>;margin-right:7px"></i><?= e(ucfirst($d['device'])) ?></span><span class="num"><?= round($d['uv'] / $devTotal * 100) ?>% <small class="meta">(<?= (int) $d['uv'] ?>)</small></span></div><?php endforeach; ?>
        </div>
      </div>
      <?php else: ?><div class="vz-empty">No data yet.</div><?php endif; ?>
    </div>

    <div class="vz-card">
      <h2>Where visitors come from <small>page views</small></h2>
      <div class="vz-row"><span class="nm">Direct, or no referrer</span><span class="num"><?= number_format($direct) ?></span></div>
      <?php foreach ($refs as $r): ?><div class="vz-row"><span class="nm"><?= e($r['referrer']) ?></span><span class="num"><?= number_format((int) $r['views']) ?></span></div><?php endforeach; ?>
    </div>

    <div class="vz-card">
      <h2>Busiest hours <small>server time</small></h2>
      <div class="vz-hours"><?php foreach ($hours as $h => $c): ?><div style="height:<?= max(2, round($c / $maxHour * 100)) ?>%" title="<?= sprintf('%02d:00', $h) ?> - <?= $c ?> views"></div><?php endforeach; ?></div>
      <div class="vz-hlab"><span>00:00</span><span>06:00</span><span>12:00</span><span>18:00</span><span>23:00</span></div>
    </div>

    <div class="vz-card">
      <h2>Latest visits <small>live</small></h2>
      <?php foreach ($recent as $r): ?>
        <div class="vz-row" style="grid-template-columns:minmax(0,1fr) auto"><span class="nm"><span class="vz-flag"><?= visit_flag($r['country']) ?></span><?= e($r['page'] === '/' ? 'Home page' : ($r['title'] ?: $r['page'])) ?></span><span class="meta"><?= e(date('j M, H:i', strtotime($r['visited_at']))) ?></span></div>
      <?php endforeach; if (!$recent): ?><div class="vz-empty">Nothing yet.</div><?php endif; ?>
    </div>
  </div>
</div>
<script>setTimeout(function () { location.reload(); }, 120000);</script>
<?php admin_footer(); ?>
