<?php
/**
 * Outgoing email with a delivery log (Admin > Mail log).
 * Uses PHP mail(); on a server without a mail transport the message is logged as "failed"
 * so the Secretariat can see what was not delivered.
 */
function deliver_mail(string $to, string $subject, string $text, array $opts = []): bool
{
    $from = setting('contact_email', 'no-reply@localhost');
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

    $ok = false;
    $error = null;
    try {
        $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
    if (!$ok && !$error) {
        $error = 'The server could not hand this message to a mail server. Configure SMTP in php.ini (or sendmail) to deliver email.';
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
    return (($_SERVER['HTTPS'] ?? '') ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url($path);
}

/** Audio/video upload into the public uploads folder. $kind: audio|video. */
function save_media(array $file, string $kind): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    $types = $kind === 'audio'
        ? ['audio/mpeg' => 'mp3', 'audio/mp4' => 'm4a', 'audio/x-m4a' => 'm4a', 'audio/ogg' => 'ogg', 'application/ogg' => 'ogg', 'audio/wav' => 'wav', 'audio/x-wav' => 'wav', 'audio/webm' => 'webm']
        : ['video/mp4' => 'mp4', 'video/webm' => 'webm'];
    $max = $kind === 'audio' ? 25 * 1024 * 1024 : 80 * 1024 * 1024;
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
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
