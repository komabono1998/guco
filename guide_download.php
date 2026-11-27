<?php
// Sends the registration guide as a file download (?f=pdf or ?f=docx).
$files = [
    'pdf'  => ['assets/docs/GUCO-Membership-Registration-and-Renewal-Guide.pdf', 'application/pdf'],
    'docx' => ['assets/docs/GUCO-Membership-Registration-and-Renewal-Guide.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
];
$key = ($_GET['f'] ?? 'pdf') === 'docx' ? 'docx' : 'pdf';
[$rel, $mime] = $files[$key];
$path = __DIR__ . '/' . $rel;
if (!is_file($path)) {
    http_response_code(404);
    exit('File not found.');
}
while (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="GUCO-Membership-Registration-and-Renewal-Guide.' . $key . '"');
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=300');
readfile($path);
