<?php
/**
 * Maintenance mode. When it is on, visitors see a maintenance page with a message and ways to reach GUCO.
 * Signed-in staff keep full access to the website, and the admin area and its sign-in stay reachable.
 */
function maintenance_on(): bool
{
    return setting('maintenance_on', '0') === '1';
}

function maintenance_set(string $key, string $value): void
{
    q('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$key, $value]);
}

/** "2026-11-20T18:00" (Kampala time) to a timestamp, or null. */
function maintenance_until_ts(): ?int
{
    $v = setting('maintenance_until');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $v)) {
        return null;
    }
    $ts = strtotime(str_replace('T', ' ', $v));
    return $ts ?: null;
}

function maintenance_gate(): void
{
    if (PHP_SAPI === 'cli' || !maintenance_on() || is_staff()) {
        return;
    }
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (str_contains($script, '/admin/') || str_ends_with($script, '/logout.php')) {
        return;
    }
    $until = maintenance_until_ts();
    http_response_code(503);
    header('Retry-After: ' . ($until && $until > time() ? min($until - time(), 604800) : 3600));
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex');
    require __DIR__ . '/maintenance_page.php';
    exit;
}

/** Contact channels for the maintenance page: [label, text, link or ''] */
function maintenance_channels(): array
{
    $out = [];
    $email = setting('contact_email');
    if ($email !== '') {
        $out[] = ['Email', $email, 'mailto:' . $email];
    }
    $phone = setting('contact_phone');
    if ($phone !== '') {
        $out[] = ['Telephone', $phone, 'tel:' . preg_replace('/[^\d+]/', '', $phone)];
    }
    $wa = setting('maintenance_whatsapp');
    if ($wa !== '') {
        $digits = preg_replace('/\D/', '', $wa);
        $out[] = ['WhatsApp', $wa, $digits !== '' ? 'https://wa.me/' . $digits : ''];
    }
    foreach (['social_facebook' => 'Facebook', 'social_x' => 'X', 'social_linkedin' => 'LinkedIn', 'social_youtube' => 'YouTube'] as $k => $l) {
        $v = setting($k);
        if ($v !== '' && preg_match('#^https?://#i', $v)) {
            $out[] = [$l, preg_replace('#^https?://(www\.)?#i', '', rtrim($v, '/')), $v];
        }
    }
    foreach (preg_split('/\R/', setting('maintenance_contacts')) as $line) {
        $parts = array_map('trim', explode('|', $line, 2));
        if ($parts[0] === '') {
            continue;
        }
        $label = count($parts) === 2 ? $parts[0] : 'Contact';
        $val = count($parts) === 2 ? $parts[1] : $parts[0];
        $link = '';
        if (filter_var($val, FILTER_VALIDATE_EMAIL)) {
            $link = 'mailto:' . $val;
        } elseif (preg_match('#^https?://#i', $val)) {
            $link = $val;
        } elseif (preg_match('/^\+?[\d\s()\-]{7,}$/', $val)) {
            $link = 'tel:' . preg_replace('/[^\d+]/', '', $val);
        }
        $out[] = [$label, $val, $link];
    }
    return $out;
}
