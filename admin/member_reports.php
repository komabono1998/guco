<?php
require_once __DIR__ . '/../includes/admin.php';
$admin = require_perm('members');

$all = member_report_all();
[$rows, $desc] = member_report_filter($all, $_GET);

$chosen = array_values(array_filter((array) ($_GET['cols'] ?? []), fn($c) => isset(REPORT_COLUMNS[$c])));
$cols = $chosen ?: REPORT_DEFAULT_COLUMNS;
$head = array_map(fn($c) => REPORT_COLUMNS[$c], $cols);

$format = $_GET['export'] ?? '';
if (in_array($format, ['csv', 'xlsx', 'docx', 'pdf'], true)) {
    audit('export_members_report', strtoupper($format) . ', ' . count($rows) . ' records' . ($desc ? ' (' . implode('; ', $desc) . ')' : ''));
    $subtitle = count($rows) . ' member' . (count($rows) === 1 ? '' : 's') . ' · ' . ($desc ? implode(' · ', $desc) : 'All members') . ' · Generated ' . date('j F Y, H:i') . ' by ' . $admin['full_name'];
    member_report_send($format, 'GUCO members report', $head, member_report_table($rows, $cols), $subtitle);
}

$uniq = function (string $key) use ($all) {
    $v = array_unique(array_filter(array_map(fn($r) => (string) $r[$key], $all), fn($x) => $x !== ''));
    natcasesort($v);
    return array_values($v);
};
$series = $uniq('grad_series');
usort($series, fn($a, $b) => (int) $a <=> (int) $b);
$years = $uniq('grad_year');
rsort($years);
$val = fn($k) => e($_GET[$k] ?? '');
$opt = function (array $list, string $key, ?array $labels = null) {
    $cur = $_GET[$key] ?? '';
    $h = '<option value="">Any</option>';
    foreach ($list as $k => $v) {
        $value = $labels !== null ? $k : $v;
        $label = $labels !== null ? $v : $v;
        $h .= '<option value="' . e((string) $value) . '"' . ((string) $cur === (string) $value && $cur !== '' ? ' selected' : '') . '>' . e((string) $label) . '</option>';
    }
    return $h;
};
$chapters = array_values(array_unique(array_filter(array_map(fn($r) => $r['chapter'], $all))));
$qs = http_build_query(array_filter($_GET, fn($v, $k) => $k !== 'export' && $v !== '' && $v !== [], ARRAY_FILTER_USE_BOTH));
$shown = array_slice($rows, 0, 300);

admin_header('Members database', 'Everyone on the register of existing members and every portal member: filter, then export');
?>
<div class="kpis">
  <div class="kpi"><span class="ic"><?= icon('users', 22) ?></span><div><div class="lbl">People in the database</div><div class="val"><?= count($all) ?></div></div></div>
  <div class="kpi"><span class="ic"><?= icon('users', 22) ?></span><div><div class="lbl">Match your filters</div><div class="val"><?= count($rows) ?></div></div></div>
  <div class="kpi"><span class="ic"><?= icon('users', 22) ?></span><div><div class="lbl">On a subscribed list</div><div class="val"><?= count(array_filter($all, fn($r) => $r['subscribed_year'] !== '')) ?></div></div></div>
  <div class="kpi"><span class="ic"><?= icon('users', 22) ?></span><div><div class="lbl">With a portal account</div><div class="val"><?= count(array_filter($all, fn($r) => $r['portal'] === 'Yes')) ?></div></div></div>
</div>

<form method="get" class="card stack wide" id="mrForm">
  <h3 style="margin:0">Filter the members</h3>
  <div class="cols">
    <label>Search <input type="search" name="q" value="<?= $val('q') ?>" placeholder="Name, GUCO number, email, phone or course"></label>
    <label>Alumni or staff
      <select name="type"><option value="">Any</option>
        <?php foreach (['alumnus' => 'Alumni only', 'staff' => 'Staff only', 'alumnus_staff' => 'Both alumni and staff', 'alumni_any' => 'Alumni (including staff who are alumni)', 'staff_any' => 'Staff (including alumni who are staff)'] as $k => $l): ?><option value="<?= $k ?>"<?= ($_GET['type'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
    <label>Sex <select name="sex"><option value="">Any</option><option value="F"<?= ($_GET['sex'] ?? '') === 'F' ? ' selected' : '' ?>>Female</option><option value="M"<?= ($_GET['sex'] ?? '') === 'M' ? ' selected' : '' ?>>Male</option></select></label>
  </div>
  <div class="cols">
    <label>Course <input name="course" list="mrCourses" value="<?= $val('course') ?>" placeholder="Part of a course name">
      <datalist id="mrCourses"><?php foreach ($uniq('course') as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist></label>
    <label>Faculty attended <select name="faculty"><?= $opt($uniq('faculty'), 'faculty') ?></select></label>
    <label>Graduation series <select name="series"><?= $opt($series, 'series') ?></select></label>
    <label>Graduation year <select name="year"><?= $opt($years, 'year') ?></select></label>
  </div>
  <div class="cols">
    <label>Membership category <select name="category"><?= $opt(CATEGORY_LABELS, 'category', CATEGORY_LABELS) ?></select></label>
    <label>Subscription
      <select name="subscription"><option value="">Any</option>
        <?php foreach (['subscribed_any' => 'On a subscribed-members list', 'active' => 'Active (portal)', 'arrears' => 'In arrears (portal)', 'unpaid' => 'Unpaid (portal)', 'none' => 'No subscription recorded'] as $k => $l): ?><option value="<?= $k ?>"<?= ($_GET['subscription'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
    <label>Subscribed year <select name="subscribed_year"><?= $opt($uniq('subscribed_year'), 'subscribed_year') ?></select></label>
    <label>Chapter <select name="chapter"><?= $opt($chapters, 'chapter') ?></select></label>
  </div>
  <div class="cols">
    <label>Portal account <select name="portal"><option value="">Any</option><option value="yes"<?= ($_GET['portal'] ?? '') === 'yes' ? ' selected' : '' ?>>Has an account</option><option value="no"<?= ($_GET['portal'] ?? '') === 'no' ? ' selected' : '' ?>>No account yet</option></select></label>
    <label>Membership status
      <select name="status"><option value="">Any</option>
        <?php foreach (['approved' => 'Approved members', 'pending' => 'Pending approval', 'rejected' => 'Rejected', 'none' => 'On the register only'] as $k => $l): ?><option value="<?= $k ?>"<?= ($_GET['status'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
    <label>Employment <select name="employment"><?= $opt($uniq('employment'), 'employment') ?></select></label>
    <label>Source <select name="source"><?= $opt($uniq('source'), 'source') ?></select></label>
  </div>
  <div class="cols">
    <label>Sort by
      <select name="sort"><?php foreach (['guco' => 'GUCO number', 'name' => 'Name', 'course' => 'Course', 'series' => 'Graduation series'] as $k => $l): ?><option value="<?= $k ?>"<?= ($_GET['sort'] ?? 'guco') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
  </div>
  <details>
    <summary class="btn secondary" style="display:inline-block">Choose the columns to show and export</summary>
    <div class="checks-grid" style="margin-top:10px">
      <?php foreach (REPORT_COLUMNS as $k => $l): ?><label class="check"><input type="checkbox" name="cols[]" value="<?= e($k) ?>"<?= in_array($k, $cols, true) ? ' checked' : '' ?>> <?= e($l) ?></label><?php endforeach; ?>
    </div>
  </details>
  <div class="row-actions">
    <button>Apply filters</button>
    <a class="btn secondary" href="member_reports.php">Clear</a>
    <span style="flex:1"></span>
    <span class="meta">Export these <?= count($rows) ?> members as</span>
    <button class="secondary" name="export" value="pdf">PDF</button>
    <button class="secondary" name="export" value="docx">Word</button>
    <button class="secondary" name="export" value="xlsx">Excel</button>
    <button class="secondary" name="export" value="csv">CSV</button>
  </div>
</form>

<?php if ($desc): ?><p class="meta" style="margin-top:12px">Filters: <?= e(implode(' · ', $desc)) ?></p><?php endif; ?>
<div class="table-wrap" style="margin-top:12px"><table>
  <tr><?php foreach ($head as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr>
  <?php foreach (member_report_table($shown, $cols) as $r): ?><tr><?php foreach ($r as $v): ?><td><?= e($v) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="<?= count($head) ?>">No members match these filters.</td></tr><?php endif; ?>
</table></div>
<?php if (count($rows) > count($shown)): ?><p class="meta">Showing the first <?= count($shown) ?> of <?= count($rows) ?>. Exports include all <?= count($rows) ?>.</p><?php endif; ?>
<p class="meta">Faculty marked "(to confirm)" was suggested from the course, because the subscribed list does not give it. Update it on the member's record or let the member fill it in when they register. Exports contain personal data: keep them private.</p>
<?php admin_footer(); ?>
