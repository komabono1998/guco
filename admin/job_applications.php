<?php
require_once __DIR__ . '/../includes/admin.php';
require_once __DIR__ . '/../includes/careers.php';
require_perm('careers');
$job = q('SELECT * FROM jobs WHERE id = ?', [(int) ($_GET['job'] ?? 0)])->fetch();
if (!$job) {
    http_response_code(404);
    exit('Opportunity not found.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $app = q('SELECT * FROM job_applications WHERE id = ? AND job_id = ?', [(int) ($_POST['id'] ?? 0), $job['id']])->fetch();
    $status = $_POST['status'] ?? '';
    if ($app && isset(JOB_APPLICATION_STATUS[$status])) {
        q('UPDATE job_applications SET status = ? WHERE id = ?', [$status, $app['id']]);
        audit('job_application_status', "#{$app['id']} {$job['title']} -> $status");
        notify((int) $app['user_id'], 'Application update', $job['title'] . ': ' . JOB_APPLICATION_STATUS[$status], 'member/applications.php');
        flash('Status updated and the applicant notified.');
    }
    redirect('admin/job_applications.php?job=' . (int) $job['id']);
}
$rows = q('SELECT a.*, u.full_name, u.email, p.programme, p.graduation_year, p.profession, p.phone FROM job_applications a JOIN users u ON u.id = a.user_id LEFT JOIN member_profiles p ON p.user_id = u.id WHERE a.job_id = ? ORDER BY a.created_at', [$job['id']])->fetchAll();
if (isset($_GET['export'])) {
    audit('export_applicants', $job['title']);
    csv_download('guco-applicants-' . (int) $job['id'] . '.csv', ['Name', 'Email', 'Phone', 'Programme', 'Graduation year', 'Profession', 'Status', 'Applied', 'Note'],
        array_map(fn($r) => [$r['full_name'], $r['email'], $r['phone'], $r['programme'], $r['graduation_year'], $r['profession'], $r['status'], $r['created_at'], $r['cover_note']], $rows));
}
admin_header('Applicants: ' . $job['title'], $job['organisation']);
?>
<p><a href="jobs.php">&larr; All opportunities</a> &middot; <a href="?job=<?= (int) $job['id'] ?>&export=1">Export CSV</a></p>
<div class="table-wrap"><table>
  <tr><th>Applicant</th><th>Background</th><th>Note</th><th>Status</th></tr>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><a href="<?= e(url('admin/members.php?id=' . (int) $r['user_id'])) ?>"><?= e($r['full_name']) ?></a><br><span class="meta"><?= e($r['email']) ?> <?= e($r['phone']) ?></span></td>
    <td><?= e($r['programme']) ?> <?= $r['graduation_year'] ? '(' . (int) $r['graduation_year'] . ')' : '' ?><br><span class="meta"><?= e($r['profession']) ?></span></td>
    <td><?= nl2br(e($r['cover_note'])) ?></td>
    <td><form method="post" class="stack" style="gap:6px"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
      <select name="status"><?php foreach (JOB_APPLICATION_STATUS as $k => $l): ?><option value="<?= $k ?>"<?= $r['status'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select><button class="secondary">Update</button></form></td>
  </tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="4">No applications yet.</td></tr><?php endif; ?>
</table></div>
<?php admin_footer(); ?>
