<?php
// Sends one test email using the configured SMTP settings and prints the result. Command line only.
//   php tools/smtp_check.php you@example.org
if (PHP_SAPI !== 'cli') {
    exit('CLI only.');
}
$to = $argv[1] ?? '';
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    exit("Usage: php tools/smtp_check.php you@example.org\n");
}
require __DIR__ . '/../includes/bootstrap.php';
$smtp = config('smtp');
echo is_array($smtp) && !empty($smtp['host']) ? "Using SMTP {$smtp['host']}:" . ($smtp['port'] ?? 587) . "\n" : "No smtp section in config.local.php: using PHP mail().\n";
$ok = deliver_mail($to, 'GUCO email test', "This is a test email from the GUCO website.\n\nIf you can read it, email sending works.", ['context' => 'test']);
$last = q('SELECT status, error FROM mail_log ORDER BY id DESC LIMIT 1')->fetch();
echo $ok ? "Sent to $to.\n" : 'Failed: ' . ($last['error'] ?? 'unknown error') . "\n";
