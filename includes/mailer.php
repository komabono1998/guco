<?php
/**
 * Outgoing email with a delivery log (Admin > Mail log).
 * Sends through SMTP when config.local.php has an 'smtp' section, otherwise through PHP mail().
 * A message that could not be sent is logged as "failed" so the Secretariat can see what was not delivered.
 *
 * config.local.php example:
 *   'smtp' => ['host' => 'smtp.example.org', 'port' => 587, 'encryption' => 'tls', 'username' => 'no-reply@example.org',
 *              'password' => '...', 'from' => 'no-reply@example.org'],
 * encryption: 'tls' (STARTTLS, usually port 587), 'ssl' (usually port 465) or '' (none, only for a trusted local relay).
 */
function smtp_deliver(array $cfg, string $to, string $from, string $subject, string $headers, string $body): array
{
    $host = (string) ($cfg['host'] ?? '');
    $port = (int) ($cfg['port'] ?? 587);
    $enc = strtolower((string) ($cfg['encryption'] ?? 'tls'));
    $errno = 0;
    $errstr = '';
    $fp = @stream_socket_client(($enc === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port, $errno, $errstr, 15);
    if (!$fp) {
        return [false, "Could not connect to $host:$port ($errstr)"];
    }
    stream_set_timeout($fp, 20);
    $read = function () use ($fp) {
        $out = '';
        while (($line = fgets($fp, 1024)) !== false) {
            $out .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        return $out;
    };
    $cmd = function (string $line, array $ok) use ($fp, $read, &$lastReply) {
        if ($line !== '') {
            fwrite($fp, $line . "\r\n");
        }
        $lastReply = trim($read());
        return in_array((int) substr($lastReply, 0, 3), $ok, true);
    };
    $fail = function (string $step) use (&$lastReply, $fp) {
        @fwrite($fp, "QUIT\r\n");
        @fclose($fp);
        return [false, "SMTP $step failed: " . mb_substr(preg_replace('/\s+/', ' ', (string) $lastReply), 0, 160)];
    };
    $lastReply = '';
    if (!$cmd('', [220])) {
        return $fail('greeting');
    }
    $me = preg_replace('/[^A-Za-z0-9.\-]/', '', gethostname() ?: 'localhost') ?: 'localhost';
    if (!$cmd("EHLO $me", [250])) {
        return $fail('hello');
    }
    if ($enc === 'tls') {
        if (!$cmd('STARTTLS', [220]) || !@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            return $fail('STARTTLS');
        }
        if (!$cmd("EHLO $me", [250])) {
            return $fail('hello after STARTTLS');
        }
    }
    if (($cfg['username'] ?? '') !== '') {
        if (!$cmd('AUTH LOGIN', [334]) || !$cmd(base64_encode((string) $cfg['username']), [334]) || !$cmd(base64_encode((string) ($cfg['password'] ?? '')), [235])) {
            return $fail('sign-in (check the username and password)');
        }
    }
    if (!$cmd("MAIL FROM:<$from>", [250])) {
        return $fail('sender');
    }
    if (!$cmd("RCPT TO:<$to>", [250, 251])) {
        return $fail('recipient');
    }
    if (!$cmd('DATA', [354])) {
        return $fail('data');
    }
    $domain = substr(strrchr($from, '@') ?: '@localhost', 1);
    $msg = 'Date: ' . date('r') . "\r\nMessage-ID: <" . bin2hex(random_bytes(10)) . '@' . $domain . ">\r\nTo: $to\r\nSubject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n" . $headers . "\r\n\r\n" . $body;
    $msg = preg_replace('/(?<!\r)\n/', "\r\n", $msg);
    $msg = preg_replace('/^\./m', '..', $msg);
    fwrite($fp, $msg . "\r\n.\r\n");
    $lastReply = trim($read());
    if ((int) substr($lastReply, 0, 3) !== 250) {
        return $fail('delivery');
    }
    @fwrite($fp, "QUIT\r\n");
    @fclose($fp);
    return [true, null];
}

function deliver_mail(string $to, string $subject, string $text, array $opts = []): bool
{
    $to = trim(str_replace(["\r", "\n", ','], '', $to));
    $subject = str_replace(["\r", "\n"], ' ', $subject);
    $smtp = config('smtp');
    $from = is_array($smtp) && !empty($smtp['from']) ? (string) $smtp['from'] : setting('contact_email', 'no-reply@localhost');
    $fromName = setting('site_name', 'Gulu University Convocation');
    $boundary = 'b' . bin2hex(random_bytes(8));
    $headers = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>\r\nReply-To: $from\r\nMIME-Version: 1.0\r\n";

    $parts = [];
    $parts[] = "Content-Type: text/plain; charset=utf-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($text));
    if (!empty($opts['html'])) {
        $parts[] = "Content-Type: text/html; charset=utf-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($opts['html']));
    }
    if (!empty($opts['ics'])) {
        $parts[] = "Content-Type: text/calendar; charset=utf-8; method=REQUEST\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($opts['ics']));
    }
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"";
    $body = '';
    foreach ($parts as $part) {
        $body .= "--$boundary\r\n$part\r\n";
    }
    $body .= "--$boundary--";

    // On a development computer, keep a readable copy of every email so it can be checked without a mail server.
    if (config('dev_mode')) {
        $dir = __DIR__ . '/../storage/outbox';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            file_put_contents($dir . '/.htaccess', "Require all denied\n");
        }
        @file_put_contents($dir . '/' . date('Ymd-His') . '-' . substr(preg_replace('/[^a-z0-9]+/i', '-', $to), 0, 40) . '-' . bin2hex(random_bytes(2)) . '.eml',
            "To: $to\r\nSubject: $subject\r\n" . $headers . "\r\n\r\n" . $body);
    }
    $ok = false;
    $error = null;
    try {
        if (is_array($smtp) && !empty($smtp['host'])) {
            [$ok, $error] = smtp_deliver($smtp, $to, $from, $subject, $headers, $body);
        } else {
            $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
    if (!$ok && !$error) {
        $error = 'The server could not hand this message to a mail server. Add an smtp section to config.local.php (see includes/mailer.php) or configure sendmail.';
    }
    $u = current_user();
    q('INSERT INTO mail_log (to_email, subject, context, status, error, sent_by) VALUES (?,?,?,?,?,?)',
        [mb_substr($to, 0, 190), mb_substr($subject, 0, 250), $opts['context'] ?? 'system', $ok ? 'sent' : 'failed', $error ? mb_substr($error, 0, 250) : null, $u['id'] ?? null]);
    return $ok;
}

function valid_emails(array $list): array
{
    $out = [];
    foreach ($list as $e) {
        $e = strtolower(trim($e));
        if (filter_var($e, FILTER_VALIDATE_EMAIL)) {
            $out[$e] = $e;
        }
    }
    return array_values($out);
}

/** Absolute URL for links inside emails. */
function absolute_url(string $path = ''): string
{
    return site_origin() . url($path);
}

/** Audio/video upload into the public uploads folder. $kind: audio|video. */
function save_media(array $file, string $kind): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    $types = $kind === 'audio'
        ? ['audio/mpeg' => 'mp3', 'audio/mp4' => 'm4a', 'audio/x-m4a' => 'm4a', 'audio/ogg' => 'ogg', 'application/ogg' => 'ogg', 'audio/wav' => 'wav', 'audio/x-wav' => 'wav', 'audio/webm' => 'webm', 'video/mp4' => 'mp4']
        : ['video/mp4' => 'mp4', 'video/webm' => 'webm'];
    $max = $kind === 'audio' ? 25 * 1024 * 1024 : 80 * 1024 * 1024;
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    // MP4 files are accepted for recordings too, but only up to 5MB.
    if ($kind === 'audio' && $mime === 'video/mp4') {
        $max = 5 * 1024 * 1024;
    }
    if (!isset($types[$mime]) || $file['size'] > $max) {
        return null;
    }
    $dir = config('upload_dir');
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = bin2hex(random_bytes(8)) . '.' . $types[$mime];
    return move_uploaded_file($file['tmp_name'], "$dir/$name") ? $name : null;
}
