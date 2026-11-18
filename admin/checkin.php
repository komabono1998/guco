<?php
require_once __DIR__ . '/../includes/admin.php';
$me = require_perm('checkin');
$events = q("SELECT id, title, starts_at FROM events WHERE status = 'scheduled' ORDER BY starts_at IS NULL, starts_at DESC")->fetchAll();
$evId = (int) ($_GET['event'] ?? ($events[0]['id'] ?? 0));
$ev = q('SELECT * FROM events WHERE id = ?', [$evId])->fetch();
$result = null;
if ($ev && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($_POST['code'] ?? '')));
    if (isset($_POST['manual'])) {
        $row = q('SELECT * FROM event_registrations WHERE id = ? AND event_id = ?', [(int) $_POST['manual'], $ev['id']])->fetch();
    } else {
        $row = $code !== '' ? q('SELECT * FROM event_registrations WHERE ticket_code = ?', [$code])->fetch() : null;
    }
    if (!$row) {
        $result = ['bad', 'No ticket found with that code.'];
    } elseif ((int) $row['event_id'] !== (int) $ev['id']) {
        $result = ['bad', 'This ticket is for a different event.'];
    } elseif ($row['checked_in_at']) {
        $result = ['warn', $row['name'] . ' was already checked in at ' . fmt_date($row['checked_in_at'], 'g:ia, j M') . '.'];
    } else {
        q('UPDATE event_registrations SET checked_in_at = NOW(), checked_in_by = ? WHERE id = ? AND checked_in_at IS NULL', [$me['id'], $row['id']]);
        audit('event_checkin', $ev['title'] . ': ' . $row['name']);
        $result = ['ok', 'Welcome, ' . $row['name'] . '. Checked in.'];
    }
}
$regs = $ev ? q('SELECT * FROM event_registrations WHERE event_id = ? ORDER BY checked_in_at IS NULL, checked_in_at DESC, name', [$ev['id']])->fetchAll() : [];
if ($ev && isset($_GET['export'])) {
    audit('export_attendance', $ev['title']);
    csv_download('guco-attendance-' . (int) $ev['id'] . '.csv', ['Name', 'Email', 'Phone', 'Registered', 'Ticket', 'Checked in'],
        array_map(fn($r) => [$r['name'], $r['email'], $r['phone'], $r['created_at'], $r['ticket_code'], $r['checked_in_at']], $regs));
}
$in = count(array_filter($regs, fn($r) => $r['checked_in_at']));
admin_header('Event check-in', 'Scan or type the ticket code to admit guests');
?>
<form method="get" class="filters"><label>Event <select name="event" onchange="this.form.submit()"><?php foreach ($events as $e2): ?><option value="<?= (int) $e2['id'] ?>"<?= $e2['id'] == $evId ? ' selected' : '' ?>><?= e($e2['title']) ?><?= $e2['starts_at'] ? ' (' . e(fmt_date($e2['starts_at'])) . ')' : '' ?></option><?php endforeach; ?></select></label><noscript><button>Show</button></noscript></form>
<?php if (!$ev): ?><p>No scheduled events yet.</p><?php else: ?>
  <?php if ($result): ?><div class="notice <?= $result[0] === 'bad' ? 'bad' : '' ?>" role="status" style="font-size:1.05rem"><strong><?= e($result[1]) ?></strong></div><?php endif; ?>
  <form method="post" class="stack" style="max-width:420px"><?= csrf_field() ?>
    <label>Ticket code <input name="code" autofocus autocomplete="off" required placeholder="Scan the QR or type the code" style="font-size:1.1rem"></label>
    <button>Check in</button>
  </form>
  <p class="meta" style="margin-top:14px"><strong><?= $in ?></strong> of <?= count($regs) ?> registered guests checked in<?= $ev['capacity'] ? ' (capacity ' . (int) $ev['capacity'] . ')' : '' ?> &middot; <a href="?event=<?= (int) $ev['id'] ?>&export=1">Export attendance CSV</a></p>
  <div class="table-wrap"><table>
    <tr><th>Guest</th><th>Contact</th><th>Ticket</th><th>Status</th></tr>
    <?php foreach ($regs as $r): ?>
    <tr><td><?= e($r['name']) ?></td><td><?= e($r['email']) ?><br><span class="meta"><?= e($r['phone']) ?></span></td><td class="mono"><?= e($r['ticket_code']) ?></td>
      <td><?php if ($r['checked_in_at']): ?><span class="badge">In <?= e(fmt_date($r['checked_in_at'], 'g:ia')) ?></span>
        <?php else: ?><form method="post" style="display:inline"><?= csrf_field() ?><button class="secondary" name="manual" value="<?= (int) $r['id'] ?>">Check in</button></form><?php endif; ?></td></tr>
    <?php endforeach; if (!$regs): ?><tr><td colspan="4">No registrations yet.</td></tr><?php endif; ?>
  </table></div>
<?php endif; ?>
<?php admin_footer(); ?>
