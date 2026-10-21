<?php
// Serves private member files (photos, proofs of payment) to their owner and authorised staff only.
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_login();
$type = $_GET['type'] ?? '';
$id = (int) ($_GET['id'] ?? 0);
$file = null;
$owner = null;
if ($type === 'photo') {
    $row = q('SELECT user_id, photo FROM member_profiles WHERE user_id = ?', [$id])->fetch();
    $file = $row['photo'] ?? null;
    $owner = $row['user_id'] ?? null;
} elseif ($type === 'proof') {
    $row = q('SELECT user_id, proof_file, proof_original FROM member_payments WHERE id = ?', [$id])->fetch();
    $file = $row['proof_file'] ?? null;
    $owner = $row['user_id'] ?? null;
} elseif ($type === 'project') {
    $row = q('SELECT user_id, attachment FROM projects WHERE id = ?', [$id])->fetch();
    $file = $row['attachment'] ?? null;
    $owner = $row['user_id'] ?? null;
}
if (!$file || !($owner == $me['id'] || can('members') || can('payments') || can('projects'))) {
    http_response_code(404);
    exit('File not found.');
}
$path = dirname(config('storage_dir')) . '/members/' . basename($file);
if (!is_file($path)) {
    http_response_code(404);
    exit('File not found.');
}
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=300');
readfile($path);
