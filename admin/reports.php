<?php
require_once __DIR__ . '/../includes/admin.php';
require_perm('reports');
$from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : date('Y-01-01');
$to = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : date('Y-m-d');
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
if (can('projects')) {
    $reports['projects'] = ['Projects by status', ['Status', 'Projects', 'Budget (UGX)', 'Verified mobilised (UGX)'],
        $mapCol($rows("SELECT p.status, COUNT(*), SUM(p.budget), COALESCE(SUM((SELECT SUM(x.amount) FROM project_updates x WHERE x.project_id = p.id AND x.kind = 'mobilisation' AND x.verified = 1)), 0) FROM projects p WHERE p.status <> 'draft' GROUP BY p.status"), 0, fn($s) => PROJECT_STATUS[$s][0] ?? $s)];
    $reports['project_theme'] = ['Projects by theme', ['Theme', 'Projects', 'Budget (UGX)'],
        $rows("SELECT theme, COUNT(*), SUM(budget) FROM projects WHERE status <> 'draft' GROUP BY theme ORDER BY 2 DESC")];
}

if (isset($_GET['export']) && isset($reports[$_GET['export']])) {
    [$title, $head, $data] = $reports[$_GET['export']];
    audit('export_report', $_GET['export']);
    csv_download('guco-' . $_GET['export'] . '-' . date('Ymd') . '.csv', $head, $data);
}
$summary = [
    ['Members (approved)', (int) q("SELECT COUNT(*) FROM users WHERE role = 'member' AND status = 'approved'")->fetchColumn(), 'users'],
    ['Verified income in range (UGX)', number_format((float) q("SELECT COALESCE(SUM(amount),0) FROM member_payments WHERE status = 'verified' AND verified_at BETWEEN ? AND ?", [$from, $toEnd])->fetchColumn()), 'credit-card'],
    ['New applications in range', (int) q("SELECT COUNT(*) FROM users WHERE role = 'member' AND created_at BETWEEN ? AND ?", [$from, $toEnd])->fetchColumn(), 'user-plus'],
    ['Event registrations in range', (int) q('SELECT COUNT(*) FROM event_registrations WHERE created_at BETWEEN ? AND ?', [$from, $toEnd])->fetchColumn(), 'calendar'],
];
admin_header('Reports & analytics', 'Membership, finance and engagement figures with CSV export');
?>
<form method="get" class="filters" style="margin-bottom:18px">
  <label>From <input type="date" name="from" value="<?= e($from) ?>"></label>
  <label>To <input type="date" name="to" value="<?= e($to) ?>"></label>
  <button>Update</button>
  <a class="btn secondary" href="reports.php">This year</a>
  <span class="tbl-count">Date range applies to finance, events, subscribers and messages.</span>
</form>
<div class="kpis">
  <?php foreach ($summary as [$l, $v, $ic]): ?><div class="kpi"><span class="ic"><?= icon($ic, 22) ?></span><div><div class="lbl"><?= e($l) ?></div><div class="val"><?= is_int($v) ? number_format($v) : e($v) ?></div></div></div><?php endforeach; ?>
</div>
<div class="report-grid">
  <?php foreach ($reports as $key => [$title, $head, $data]): ?>
    <section class="wid">
      <h3><?= e($title) ?></h3>
      <div class="table-wrap" style="box-shadow:none"><table>
        <tr><?php foreach ($head as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr>
        <?php foreach ($data as $r): ?><tr><?php foreach ($r as $i => $cell): ?><td><?= is_numeric($cell) && $i > 0 ? e(number_format((float) $cell)) : e($cell) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
        <?php if (!$data): ?><tr><td colspan="<?= count($head) ?>">No data for this report yet.</td></tr><?php endif; ?>
      </table></div>
      <div class="report-actions"><a class="btn secondary" href="?<?= e(http_build_query(['export' => $key, 'from' => $from, 'to' => $to])) ?>"><?= icon('download', 14) ?> Export CSV</a></div>
    </section>
  <?php endforeach; ?>
</div>
<?php admin_footer(); ?>
