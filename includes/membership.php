<?php
const CATEGORY_CODES = ['founder' => 'FDR', 'ordinary' => 'ORD', 'honorary' => 'HON', 'life' => 'LIF'];

const QUALIFICATION_LEVELS = [
    'Certificate', 'Diploma', 'Bachelor\'s degree', 'Postgraduate diploma', 'Master\'s degree', 'PhD', 'Other',
];

const EMPLOYMENT_LABELS = [
    'employed' => 'Employed', 'self_employed' => 'Self-employed', 'unemployed' => 'Unemployed / seeking work',
    'student' => 'Further studies', 'retired' => 'Retired', 'other' => 'Other',
];

const GENDER_LABELS = ['female' => 'Female', 'male' => 'Male', 'other' => 'Other', 'undisclosed' => 'Prefer not to say'];

const PAYMENT_METHODS = ['Mobile money', 'Bank deposit / transfer', 'Cash at the Secretariat', 'Other'];

const MEMBER_INTERESTS = [
    'Mentorship', 'Fundraising', 'Events and networking', 'Community service', 'Research and academic support',
    'Sports', 'Career opportunities', 'Communications',
];

/** Display form of the auto-generated member number, e.g. M-000012. */
function member_no_label($n): string
{
    return $n ? 'M-' . str_pad((string) $n, 6, '0', STR_PAD_LEFT) : '';
}

/** Next free member number. Retries on a unique-key collision. */
function next_member_no(): int
{
    return (int) db()->query('SELECT COALESCE(MAX(member_no), 0) + 1 FROM member_profiles')->fetchColumn();
}

/** Membership ID issued on approval, e.g. GUCO/ORD/2026/000012. */
function make_membership_id(string $category, int $memberNo): string
{
    return 'GUCO/' . (CATEGORY_CODES[$category] ?? 'MEM') . '/' . date('Y') . '/' . str_pad((string) $memberNo, 6, '0', STR_PAD_LEFT);
}

/**
 * Card state for a user+profile row: none | valid | expired | revoked.
 * Ordinary members hold a card valid to the end of their paid subscription; other categories are lifetime.
 */
function card_state(array $m): array
{
    if (($m['card_status'] ?? 'none') === 'revoked') {
        return ['state' => 'revoked', 'label' => 'Revoked', 'until' => null];
    }
    if (empty($m['membership_id']) || ($m['status'] ?? '') !== 'approved' || ($m['card_status'] ?? 'none') !== 'active') {
        return ['state' => 'none', 'label' => 'Not issued', 'until' => null];
    }
    if ($m['category'] === 'ordinary') {
        $until = $m['paid_until'] ?? null;
        if (($m['subscription_status'] ?? '') === 'arrears' || ($until && $until < date('Y-m-d'))) {
            return ['state' => 'expired', 'label' => 'Expired - renewal due', 'until' => $until];
        }
        return ['state' => 'valid', 'label' => 'Valid', 'until' => $until];
    }
    return ['state' => 'valid', 'label' => 'Valid', 'until' => null];
}

/**
 * Stores an uploaded file outside the web root (storage/members) and returns its stored name.
 * $kind: 'photo' accepts images up to 3MB; 'proof' accepts images or PDF up to 5MB.
 */
function save_private_file(array $file, string $kind): ?array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if ($kind === 'proof') {
        $types['application/pdf'] = 'pdf';
    }
    $max = $kind === 'photo' ? 3 * 1024 * 1024 : 5 * 1024 * 1024;
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($types[$mime]) || $file['size'] > $max) {
        return null;
    }
    $dir = dirname(config('storage_dir')) . '/members';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = $kind . '-' . bin2hex(random_bytes(10)) . '.' . $types[$mime];
    if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
        return null;
    }
    return ['name' => $name, 'original' => mb_substr(basename($file['name']), 0, 190)];
}

function member_file_url(string $type, int $id): string
{
    return url('member_file.php?type=' . $type . '&id=' . $id);
}

/** Initials or photo for a member, used on the card and portal. */
function member_avatar(array $m, string $class = 'avatar'): string
{
    if (!empty($m['photo']) && !empty($m['user_id'])) {
        return '<img class="' . e($class) . '" src="' . e(member_file_url('photo', (int) $m['user_id'])) . '" alt="Photo of ' . e($m['full_name'] ?? 'member') . '">';
    }
    $ini = '';
    foreach (preg_split('/\s+/', trim($m['full_name'] ?? '')) as $w) {
        $ini .= $w !== '' ? mb_strtoupper(mb_substr($w, 0, 1)) : '';
    }
    return '<span class="' . e($class) . ' initials" role="img" aria-label="' . e($m['full_name'] ?? '') . '">' . e(mb_substr($ini, 0, 2)) . '</span>';
}

/**
 * Approves an application: issues the membership ID and activates the card.
 * Returns an error string, or null on success. A verified payment is required unless $waive is true.
 */
function approve_member(int $userId, bool $waive): ?string
{
    $m = q('SELECT u.id, u.email, u.full_name, u.status, p.* FROM users u JOIN member_profiles p ON p.user_id = u.id WHERE u.id = ?', [$userId])->fetch();
    if (!$m) {
        return 'Member not found.';
    }
    $paid = (int) q("SELECT COUNT(*) FROM member_payments WHERE user_id = ? AND status = 'verified'", [$userId])->fetchColumn();
    if (!$paid && !$waive) {
        return 'Verify the proof of payment first, or tick "approve without payment" for a waiver.';
    }
    $no = (int) $m['member_no'] ?: next_member_no();
    $mid = $m['membership_id'] ?: make_membership_id($m['category'], $no);
    q("UPDATE users SET status = 'approved' WHERE id = ?", [$userId]);
    q("UPDATE member_profiles SET member_no = ?, membership_id = ?, approved_at = COALESCE(approved_at, NOW()),
        card_status = 'active', card_issued_at = COALESCE(card_issued_at, NOW()), card_revoked_at = NULL, card_revoke_reason = NULL WHERE user_id = ?",
        [$no, $mid, $userId]);
    audit('approve_member', "{$m['email']} -> $mid" . ($paid ? '' : ' (payment waived)'));
    send_mail($m['email'], 'Your GUCO membership is approved',
        "Dear {$m['full_name']},\n\nYour membership of Gulu University Convocation has been approved.\nMembership ID: $mid\n\nLog in to the Member Portal to view and print your membership card.\n\nGulu University Convocation");
    return null;
}

/** Default end of subscription cover for a payment: one year for ordinary members, none (lifetime) otherwise. */
function default_paid_until(array $payment, string $category): ?string
{
    if ($category !== 'ordinary') {
        return null;
    }
    $from = $payment['paid_on'] ?: date('Y-m-d');
    return date('Y-m-d', strtotime($from . ' +1 year'));
}

/** Verifies or rejects a member payment; a verified registration/renewal activates the subscription. */
function decide_payment(int $paymentId, string $decision, ?string $paidUntil, string $note): void
{
    $pay = q('SELECT * FROM member_payments WHERE id = ?', [$paymentId])->fetch();
    $me = current_user();
    if (!$pay || !in_array($decision, ['verified', 'rejected'], true)) {
        return;
    }
    q('UPDATE member_payments SET status = ?, admin_note = ?, verified_by = ?, verified_at = NOW() WHERE id = ?',
        [$decision, mb_substr($note, 0, 300), $me['id'], $paymentId]);
    if ($decision === 'verified' && in_array($pay['purpose'], ['registration', 'renewal'], true)) {
        q("UPDATE member_profiles SET subscription_status = 'active', paid_until = ? WHERE user_id = ?", [$paidUntil ?: null, $pay['user_id']]);
    }
    audit($decision === 'verified' ? 'verify_payment' : 'reject_payment', "payment $paymentId, user {$pay['user_id']}, UGX " . number_format((float) $pay['amount']));
}
