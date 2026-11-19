<?php
// Emergency control for the staff two-step sign-in (command line only).
//   php tools/twofa.php status          show whether it is on
//   php tools/twofa.php off             switch it off for everyone (use if the server cannot send email)
//   php tools/twofa.php on              switch it on again
//   php tools/twofa.php unlock EMAIL    clear locked-out attempts and pending codes for one account
if (PHP_SAPI !== 'cli') {
    exit('CLI only.');
}
require __DIR__ . '/../includes/bootstrap.php';
$cmd = $argv[1] ?? 'status';
switch ($cmd) {
    case 'status':
        echo 'Two-step sign-in is ' . (twofa_enabled() ? 'ON' : 'OFF') . ".\n";
        if (config('admin_2fa') === false) {
            echo "It is switched off in config.local.php.\n";
        }
        break;
    case 'off':
    case 'on':
        q('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', ['admin_2fa', $cmd === 'on' ? '1' : '0']);
        q('INSERT INTO audit_log (user_id, action, detail, ip) VALUES (NULL, ?, ?, ?)', ['admin_2fa_switch', "$cmd (command line)", 'cli']);
        echo "Two-step sign-in is now " . strtoupper($cmd) . ".\n";
        break;
    case 'unlock':
        $u = q('SELECT id, email FROM users WHERE email = ?', [strtolower($argv[2] ?? '')])->fetch();
        if (!$u) {
            exit("No account with that email.\n");
        }
        q('DELETE FROM login_otps WHERE user_id = ?', [$u['id']]);
        q('DELETE FROM login_attempts WHERE email IN (?, ?)', ['2fa:' . $u['id'], $u['email']]);
        echo "Cleared pending codes and failed attempts for {$u['email']}.\n";
        break;
    default:
        echo "Usage: php tools/twofa.php status|off|on|unlock EMAIL\n";
}
