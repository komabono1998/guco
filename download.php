<?php
require_once __DIR__ . '/includes/bootstrap.php';
$r = q('SELECT * FROM resources WHERE id = ? AND is_public = 1', [(int) ($_GET['id'] ?? 0)])->fetch();
if (!$r || (!$r['filename'] && !$r['external_url'])) {
    http_response_code(404);
    exit('Document not found.');
}
if ($r['member_only']) {
    require_member();
}
if (!$r['filename']) {
    header('Location: ' . $r['external_url']);
    exit;
}
$path = config('storage_dir') . '/' . basename($r['filename']);
if (!is_file($path)) {
    http_response_code(404);
    exit('File is missing.');
}
$types = ['pdf' => 'application/pdf'];
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . ($ext === 'pdf' ? 'inline' : 'attachment') . '; filename="' . str_replace('"', '', $r['original_name'] ?: $r['filename']) . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
