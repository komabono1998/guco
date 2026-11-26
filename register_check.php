<?php
// Used by the Join GUCO form: looks for people who may already be on record, and lets an existing member pick their record.
require_once __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function dq_out(array $d, int $code = 200): void
{
    http_response_code($code);
    exit(json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    dq_out(['ok' => false], 405);
}
csrf_check();
$ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);
q('DELETE FROM login_attempts WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
if ((int) q("SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND email = 'dupecheck' AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)", [$ip])->fetchColumn() >= 40) {
    dq_out(['ok' => false, 'error' => 'Too many checks. Please wait a few minutes and try again.'], 429);
}
q('INSERT INTO login_attempts (ip, email) VALUES (?,?)', [$ip, 'dupecheck']);

$action = $_POST['action'] ?? 'check';
if ($action === 'claim') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!in_array($id, $_SESSION['dupe_options'] ?? [], true)) {
        dq_out(['ok' => false, 'error' => 'Please check again and choose your record.'], 400);
    }
    $row = q("SELECT id FROM legacy_members WHERE id = ? AND status = 'unclaimed' AND claimed_user_id IS NULL", [$id])->fetch();
    if (!$row) {
        dq_out(['ok' => false, 'error' => 'That record is no longer available. Please contact the Secretariat.'], 409);
    }
    $_SESSION['legacy_claim'] = ['id' => $id, 'at' => time()];
    dq_out(['ok' => true, 'redirect' => url('register.php')]);
}

$name = trim((string) ($_POST['full_name'] ?? ''));
if (mb_strlen($name) < 3) {
    dq_out(['ok' => true, 'matches' => []]);
}
$matches = dedupe_candidates($name, trim((string) ($_POST['phone'] ?? '')));
$_SESSION['dupe_options'] = array_values(array_map(fn($m) => $m['id'], array_filter($matches, fn($m) => $m['kind'] === 'register' && $m['status'] === 'unclaimed')));
dq_out(['ok' => true, 'matches' => array_map(fn($m) => [
    'kind' => $m['kind'], 'id' => $m['kind'] === 'register' ? $m['id'] : 0, 'guco_no' => $m['guco_no'], 'name' => $m['name'], 'detail' => $m['detail'],
    'claimable' => $m['kind'] === 'register' && $m['status'] === 'unclaimed', 'reason' => $m['reason'], 'email_hint' => $m['email_hint'],
], $matches)]);
