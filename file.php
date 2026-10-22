<?php
// Serves documents attached to published public content (news documents, newsletter PDFs).
require_once __DIR__ . '/includes/bootstrap.php';
$id = (int) ($_GET['id'] ?? 0);
$file = $orig = null;
if (($_GET['type'] ?? '') === 'news') {
    $r = q("SELECT document, document_original FROM news WHERE id = ? AND status = 'published' AND published_at <= NOW()", [$id])->fetch();
    [$file, $orig] = [$r['document'] ?? null, $r['document_original'] ?? null];
} elseif (($_GET['type'] ?? '') === 'newsletter') {
    $r = q("SELECT pdf, pdf_original FROM newsletters WHERE id = ? AND status = 'published' AND published_at <= NOW()", [$id])->fetch();
    [$file, $orig] = [$r['pdf'] ?? null, $r['pdf_original'] ?? null];
}
$path = $file ? config('storage_dir') . '/' . basename($file) : '';
if (!$file || !is_file($path)) {
    http_response_code(404);
    exit('Document not found.');
}
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
header('Content-Type: ' . ($ext === 'pdf' ? 'application/pdf' : 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . ($ext === 'pdf' ? 'inline' : 'attachment') . '; filename="' . str_replace('"', '', $orig ?: $file) . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
