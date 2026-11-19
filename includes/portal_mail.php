<?php
/**
 * Emails sent to members about their Member Portal account: a clear link, how to sign in, and what to do next.
 * Every email has a plain-text part and an HTML part. Passwords are never emailed except a temporary one that an
 * administrator created for the member, which the email asks them to change.
 */

/**
 * Builds and sends a portal email.
 * $sections: list of ['title' => string, 'lines' => string[]] (shown as a list) or ['title' => string, 'steps' => string[]] (numbered)
 * or ['title' => string, 'facts' => [label => value]]. $button: [label, url].
 */
function portal_mail_send(string $to, string $subject, string $heading, string $lead, array $sections, array $button, string $context = 'portal'): bool
{
    $site = setting('site_name', 'Gulu University Convocation');
    $help = setting('contact_email');
    $logo = absolute_url('assets/img/logo-sm.webp');

    $text = $heading . "\n" . str_repeat('=', min(60, mb_strlen($heading))) . "\n\n" . $lead . "\n\n";
    $text .= strtoupper($button[0]) . ":\n" . $button[1] . "\n\n";
    foreach ($sections as $s) {
        $text .= strtoupper($s['title']) . "\n";
        foreach ($s['steps'] ?? [] as $i => $line) {
            $text .= ($i + 1) . '. ' . html_entity_decode(strip_tags($line), ENT_QUOTES, 'UTF-8') . "\n";
        }
        foreach ($s['lines'] ?? [] as $line) {
            $text .= '- ' . $line . "\n";
        }
        foreach ($s['facts'] ?? [] as $k => $v) {
            $text .= "$k: $v\n";
        }
        $text .= "\n";
    }
    $text .= ($help !== '' ? "Need help? Reply to this email or write to $help.\n" : "Need help? Use Support inside the portal, or contact the Secretariat.\n")
        . "We will never ask you for your password by email.\n\n$site";

    $h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $body = '';
    foreach ($sections as $s) {
        $body .= '<h3 style="margin:26px 0 8px;font-size:15px;color:#14532d;font-family:Arial,sans-serif">' . $h($s['title']) . '</h3>';
        if (!empty($s['steps'])) {
            $body .= '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%">';
            foreach ($s['steps'] as $i => $line) {
                $body .= '<tr><td style="width:34px;vertical-align:top;padding:5px 0"><span style="display:inline-block;width:26px;height:26px;line-height:26px;border-radius:13px;background:#14532d;color:#fff;text-align:center;font:bold 13px Arial,sans-serif">' . ($i + 1) . '</span></td>'
                    . '<td style="padding:5px 0;font:15px/1.5 Arial,sans-serif;color:#1b2a22;word-break:break-word;overflow-wrap:anywhere">' . $line . '</td></tr>';
            }
            $body .= '</table>';
        }
        if (!empty($s['lines'])) {
            $body .= '<ul style="margin:0;padding-left:20px;font:15px/1.7 Arial,sans-serif;color:#1b2a22">';
            foreach ($s['lines'] as $line) {
                $body .= '<li>' . $h($line) . '</li>';
            }
            $body .= '</ul>';
        }
        if (!empty($s['facts'])) {
            $body .= '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border:1px solid #dbe7df;border-radius:10px;border-collapse:separate">';
            foreach ($s['facts'] as $k => $v) {
                $body .= '<tr><td style="padding:9px 14px;font:13px Arial,sans-serif;color:#5b665f;width:38%;border-bottom:1px solid #eef3ef">' . $h($k) . '</td><td style="padding:9px 14px;font:bold 14px Arial,sans-serif;color:#1b2a22;border-bottom:1px solid #eef3ef">' . $h($v) . '</td></tr>';
            }
            $body .= '</table>';
        }
    }
    $html = '<!doctype html><html><head><meta name="viewport" content="width=device-width, initial-scale=1"></head><body style="margin:0;background:#eef3ef;padding:24px 12px">'
        . '<table role="presentation" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;table-layout:fixed;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;border:1px solid #dbe7df">'
        . '<tr><td style="background:#14532d;padding:22px 28px;border-bottom:5px solid #f2b807"><img src="' . $h($logo) . '" width="54" height="54" alt="" style="vertical-align:middle;border-radius:27px;background:#fff"> '
        . '<span style="vertical-align:middle;margin-left:10px;color:#fff;font:bold 17px Arial,sans-serif">' . $h($site) . '</span></td></tr>'
        . '<tr><td style="padding:30px 28px 10px"><h1 style="margin:0 0 10px;font:bold 22px Arial,sans-serif;color:#14532d">' . $h($heading) . '</h1>'
        . '<p style="margin:0 0 22px;font:16px/1.6 Arial,sans-serif;color:#1b2a22">' . $h($lead) . '</p>'
        . '<p style="margin:0 0 6px"><a href="' . $h($button[1]) . '" style="display:inline-block;background:#14532d;color:#ffffff;text-decoration:none;font:bold 16px Arial,sans-serif;padding:14px 28px;border-radius:30px">' . $h($button[0]) . ' &rarr;</a></p>'
        . '<p style="margin:10px 0 0;font:12px/1.5 Arial,sans-serif;color:#5b665f">Or copy this link into your browser:<br><a href="' . $h($button[1]) . '" style="color:#14532d;word-break:break-all">' . $h($button[1]) . '</a></p>'
        . $body
        . '<p style="margin:28px 0 0;padding:14px 16px;background:#fff8e1;border-radius:10px;font:13px/1.5 Arial,sans-serif;color:#5a4a00">'
        . ($help !== '' ? 'Need help? Reply to this email or write to <a href="mailto:' . $h($help) . '" style="color:#14532d">' . $h($help) . '</a>.' : 'Need help? Use Support inside the portal, or contact the Secretariat.')
        . ' We will never ask you for your password by email.</p></td></tr>'
        . '<tr><td style="padding:18px 28px 26px;font:12px Arial,sans-serif;color:#7a857e">&copy; ' . date('Y') . ' ' . $h($site) . '</td></tr></table></body></html>';
    return deliver_mail($to, $subject, $text, ['html' => $html, 'context' => $context]);
}

/** Welcome email for a new member account. $o: name, email, mode (applied|created), password, facts[], next[] (list of lines). */
function portal_welcome_mail(array $o): bool
{
    $login = absolute_url('member/login.php');
    $forgot = absolute_url('forgot.php');
    $created = ($o['mode'] ?? 'applied') === 'created';
    $steps = [
        'Open the Member Portal: <a href="' . htmlspecialchars($login) . '" style="color:#14532d;font-weight:bold">' . htmlspecialchars($login) . '</a>',
        'Sign in with your email address: <strong>' . htmlspecialchars($o['email']) . '</strong>',
        $created && !empty($o['password'])
            ? 'Use this temporary password: <strong style="font-family:Consolas,monospace;background:#f1f5f2;padding:2px 8px;border-radius:6px">' . htmlspecialchars($o['password']) . '</strong>. Please change it straight away under My profile, Change password.'
            : 'Enter the password you chose when you applied.',
        'Forgotten your password? Choose a new one here: <a href="' . htmlspecialchars($forgot) . '" style="color:#14532d">' . htmlspecialchars($forgot) . '</a>',
    ];
    $sections = [['title' => 'How to get into your portal', 'steps' => $steps]];
    if (!empty($o['facts'])) {
        $sections[] = ['title' => $created ? 'Your membership' : 'Your application', 'facts' => $o['facts']];
    }
    if (!empty($o['next'])) {
        $sections[] = ['title' => 'What happens next', 'lines' => $o['next']];
    }
    $sections[] = ['title' => 'What you can do in the portal', 'lines' => [
        'Follow your application and membership status', 'Pay or renew, and upload proof of payment', 'View and print your membership card with its QR code (after approval)',
        'Register for events and see your tickets', 'Join a chapter, propose or follow community projects, and find a mentor', 'Track your job, scholarship and fellowship applications',
        'Update your details, see your giving history and read your notifications',
    ]];
    $name = $o['name'];
    $lead = $created
        ? "Dear $name, the Secretariat has created a Member Portal account for you. Use the button below to sign in."
        : "Dear $name, thank you for applying to join Gulu University Convocation. Your Member Portal account is ready. Use the button below to sign in and follow your application.";
    return portal_mail_send($o['email'], 'Welcome to GUCO: your Member Portal account', $created ? 'Your Member Portal account is ready' : 'Welcome to GUCO', $lead, $sections, ['Open the Member Portal', $login], 'welcome');
}

/** Email sent when the Secretariat approves a membership. */
function portal_approved_mail(string $name, string $email, string $membershipId): bool
{
    $card = absolute_url('member/card.php');
    return portal_mail_send($email, 'Your GUCO membership is approved', 'Your membership is approved',
        "Dear $name, your membership of Gulu University Convocation has been approved and your membership card is ready.",
        [
            ['title' => 'Your membership', 'facts' => ['Membership ID' => $membershipId]],
            ['title' => 'How to see your card', 'steps' => [
                'Sign in to the Member Portal: ' . absolute_url('member/login.php'),
                'Open "Membership card" in the portal, or use the button above.',
                'Print the card or show the QR code on your phone. Anyone can scan it to check that your membership is genuine.',
            ]],
        ], ['View my membership card', $card], 'approved');
}
