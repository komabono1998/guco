<?php
// Dashboard countdowns: pin or unpin an event, and add or remove custom timers.
require_once __DIR__ . '/../includes/admin.php';
$me = require_perm('events');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/');
}
csrf_check();
$action = $_POST['action'] ?? '';
if ($action === 'pin' || $action === 'unpin') {
    $ev = q('SELECT id, title, starts_at FROM events WHERE id = ?', [(int) ($_POST['event_id'] ?? 0)])->fetch();
    if ($ev) {
        if ($action === 'pin' && !$ev['starts_at']) {
            flash('Give the event a date first, then pin it to the dashboard.', 'error');
        } else {
            q('UPDATE events SET pin_dashboard = ? WHERE id = ?', [$action === 'pin' ? 1 : 0, $ev['id']]);
            audit($action . '_event_countdown', "event {$ev['id']}: {$ev['title']}");
            flash($action === 'pin' ? 'Countdown added to the dashboard.' : 'Countdown removed from the dashboard.');
        }
    }
} elseif ($action === 'add') {
    $title = trim($_POST['title'] ?? '');
    $at = str_replace('T', ' ', trim($_POST['target_at'] ?? ''));
    $ts = strtotime($at);
    if ($title === '' || !$ts) {
        flash('Enter a name and a date and time for the countdown.', 'error');
    } elseif ($ts <= time()) {
        flash('Choose a date and time in the future.', 'error');
    } else {
        q('INSERT INTO admin_countdowns (title, target_at, note, created_by) VALUES (?,?,?,?)', [mb_substr($title, 0, 160), date('Y-m-d H:i:s', $ts), mb_substr(trim($_POST['note'] ?? ''), 0, 250), $me['id']]);
        audit('add_countdown', $title);
        flash('Countdown added to the dashboard.');
    }
} elseif ($action === 'delete') {
    q('DELETE FROM admin_countdowns WHERE id = ?', [(int) ($_POST['id'] ?? 0)]);
    flash('Countdown removed.');
}
redirect('admin/#countdowns');
