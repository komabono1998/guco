<?php
/**
 * Two-step sign-in for staff: after the password is accepted, a six-digit code is emailed and must be entered
 * before the session is opened. Backup codes cover the case where the email does not arrive.
 *
 * Emergency switch for a server where email cannot be sent: set 'admin_2fa' => false in config.local.php,
 * or run  php tools/twofa.php off  on the server.
 */
const TWOFA_CODE_MINUTES = 10;
const TWOFA_PENDING_MINUTES = 15;
const TWOFA_MAX_TRIES = 5;

function twofa_enabled(): bool
{
    return config('admin_2fa') !== false && setting('admin_2fa', '1') === '1';
}

function twofa_required(array $u): bool
{
    return twofa_enabled() && ($u['status'] ?? '') === 'approved' && in_array($u['role'] ?? '', STAFF_ROLES, true);
}

/** A secret kept in the settings table, used so stored codes cannot be checked without it. */
function twofa_key(): string
{
    $k = setting('app_key');
    if (strlen($k) < 32) {
        $k = bin2hex(random_bytes(32));
        q('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', ['app_key', $k]);
    }
    return $k;
}

function twofa_hash(int $uid, string $code): string
{
    return hash_hmac('sha256', $uid . ':' . strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code)), twofa_key());
}

function twofa_mask_email(string $email): string
{
    [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
    return mb_substr($local, 0, 1) . str_repeat('*', max(2, min(6, mb_strlen($local) - 1))) . '@' . $domain;
}

/** The sign-in that is waiting for its code, or null. */
function twofa_pending(): ?array
{
    $p = $_SESSION['2fa'] ?? null;
    if (!is_array($p) || time() - (int) ($p['at'] ?? 0) > TWOFA_PENDING_MINUTES * 60) {
        unset($_SESSION['2fa']);
        return null;
    }
    $u = q('SELECT * FROM users WHERE id = ?', [(int) $p['uid']])->fetch();
    return $u ?: null;
}

/** Emails a new code. Returns [sent, message]. */
function twofa_send(array $u): array
{
    $uid = (int) $u['id'];
    $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);
    $recent = (int) q('SELECT COUNT(*) FROM login_otps WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)', [$uid])->fetchColumn();
    if ($recent >= 6) {
        return [false, 'Too many codes were requested. Please wait a while, or use a backup code.'];
    }
    $code = (string) random_int(100000, 999999);
    q('DELETE FROM login_otps WHERE user_id = ?', [$uid]);
    q('INSERT INTO login_otps (user_id, code_hash, expires_at, ip) VALUES (?,?,DATE_ADD(NOW(), INTERVAL ' . TWOFA_CODE_MINUTES . ' MINUTE),?)', [$uid, twofa_hash($uid, $code), $ip]);
    $site = setting('site_name', 'Gulu University Convocation');
    $when = date('j F Y, H:i') . ' (Uganda time)';
    $text = "Dear {$u['full_name']},\n\nYour $site admin sign-in code is:\n\n    $code\n\nIt works for " . TWOFA_CODE_MINUTES . " minutes and only once.\n\n"
        . "Sign-in requested: $when\nFrom address: " . ($ip ?: 'unknown') . "\n\nIf this was not you, someone knows your password. Change it now and tell the system administrator. Never share this code with anyone.\n\n$site";
    $h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $html = '<!doctype html><html><head><meta name="viewport" content="width=device-width, initial-scale=1"></head><body style="margin:0;background:#eef3ef;padding:24px 12px">'
        . '<table role="presentation" cellpadding="0" cellspacing="0" style="max-width:520px;width:100%;table-layout:fixed;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;border:1px solid #dbe7df">'
        . '<tr><td style="background:#14532d;padding:18px 26px;border-bottom:5px solid #f2b807;color:#fff;font:bold 16px Arial,sans-serif">' . $h($site) . ' &middot; Admin</td></tr>'
        . '<tr><td style="padding:28px 26px"><p style="margin:0 0 6px;font:15px Arial,sans-serif;color:#1b2a22">Dear ' . $h($u['full_name']) . ', your sign-in code is</p>'
        . '<p style="margin:10px 0 14px;text-align:center"><span style="display:inline-block;background:#f1f8f3;border:2px dashed #14532d;border-radius:12px;padding:14px 26px;font:bold 34px/1 Consolas,monospace;letter-spacing:8px;color:#14532d">' . $h($code) . '</span></p>'
        . '<p style="margin:0 0 14px;font:14px/1.6 Arial,sans-serif;color:#44514a">It works for ' . TWOFA_CODE_MINUTES . ' minutes and only once.<br>Requested: ' . $h($when) . '<br>From address: ' . $h($ip ?: 'unknown') . '</p>'
        . '<p style="margin:0;padding:12px 14px;background:#fff1f1;border-radius:10px;font:13px/1.5 Arial,sans-serif;color:#7a1f1f">If this was not you, someone knows your password. Change it now and tell the system administrator. Never share this code with anyone.</p></td></tr></table></body></html>';
    $ok = deliver_mail($u['email'], 'Your ' . config('short_name') . ' admin sign-in code: ' . $code, $text, ['html' => $html, 'context' => 'admin-otp']);
    audit_for($uid, $ok ? 'admin_2fa_sent' : 'admin_2fa_send_failed', $u['email']);
    return $ok ? [true, 'We emailed a 6-digit code to ' . twofa_mask_email($u['email']) . '. It works for ' . TWOFA_CODE_MINUTES . ' minutes.']
        : [false, 'We could not email the code. Use one of your backup codes, or ask a system administrator to check the email settings.'];
}

/** Audit entry for a user who is not signed in yet. */
function audit_for(int $uid, string $action, string $detail = ''): void
{
    q('INSERT INTO audit_log (user_id, action, detail, ip) VALUES (?,?,?,?)', [$uid, $action, mb_substr($detail, 0, 250), substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45)]);
}

/** Checks an emailed code or a backup code. Returns [ok, message]. */
function twofa_check(array $u, string $input): array
{
    $uid = (int) $u['id'];
    $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);
    $key = '2fa:' . $uid;
    $fails = (int) q('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)', [$key])->fetchColumn();
    $byIp = (int) q("SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND email LIKE '2fa:%' AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)", [$ip])->fetchColumn();
    if ($fails >= 8 || $byIp >= 15) {
        return [false, 'Too many wrong codes. Please wait 15 minutes and try again.'];
    }
    $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $input));
    if ($clean === '') {
        return [false, 'Enter the code from your email.'];
    }
    $hash = twofa_hash($uid, $clean);
    // Emailed code
    $otp = q('SELECT * FROM login_otps WHERE user_id = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1', [$uid])->fetch();
    if ($otp && (int) $otp['attempts'] < TWOFA_MAX_TRIES && strlen($clean) === 6 && ctype_digit($clean) && hash_equals($otp['code_hash'], $hash)) {
        q('DELETE FROM login_otps WHERE user_id = ?', [$uid]);
        q('DELETE FROM login_attempts WHERE email = ?', [$key]);
        return [true, 'code'];
    }
    // Backup code
    $bk = q('SELECT id FROM admin_backup_codes WHERE user_id = ? AND used_at IS NULL AND code_hash = ?', [$uid, $hash])->fetch();
    if ($bk && strlen($clean) === 8) {
        q('UPDATE admin_backup_codes SET used_at = NOW() WHERE id = ?', [$bk['id']]);
        q('DELETE FROM login_attempts WHERE email = ?', [$key]);
        return [true, 'backup'];
    }
    if ($otp) {
        q('UPDATE login_otps SET attempts = attempts + 1 WHERE id = ?', [$otp['id']]);
    }
    q('INSERT INTO login_attempts (ip, email) VALUES (?,?)', [$ip, $key]);
    audit_for($uid, 'admin_2fa_failed', $u['email']);
    if ($otp && (int) $otp['attempts'] + 1 >= TWOFA_MAX_TRIES) {
        return [false, 'That code is no longer valid. Request a new code.'];
    }
    return [false, 'That code is not correct. Check the latest email and try again.'];
}

/** Opens the session once the code is right. */
function twofa_complete(array $u, string $how): void
{
    unset($_SESSION['2fa']);
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    $_SESSION['2fa_at'] = time();
    audit_for((int) $u['id'], 'admin_login', $u['email'] . ($how === 'backup' ? ' (backup code)' : ' (email code)'));
}

function twofa_backup_remaining(int $uid): int
{
    return (int) q('SELECT COUNT(*) FROM admin_backup_codes WHERE user_id = ? AND used_at IS NULL', [$uid])->fetchColumn();
}

/** Replaces the user's backup codes and returns the new plain codes (shown once). */
function twofa_make_backup_codes(int $uid, int $n = 8): array
{
    q('DELETE FROM admin_backup_codes WHERE user_id = ?', [$uid]);
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $codes = [];
    for ($i = 0; $i < $n; $i++) {
        $c = '';
        for ($k = 0; $k < 8; $k++) {
            $c .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $codes[] = substr($c, 0, 4) . '-' . substr($c, 4);
        q('INSERT INTO admin_backup_codes (user_id, code_hash) VALUES (?,?)', [$uid, twofa_hash($uid, $c)]);
    }
    return $codes;
}
