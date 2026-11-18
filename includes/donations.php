<?php
/**
 * Giving: campaigns, donations, receipts, and the in-platform notification centre.
 *
 * Payments are never marked successful automatically. A donor pays to the published GUCO
 * account (Mobile Money, bank or cash) and submits the transaction reference; the Finance
 * Officer checks it against the real statement and verifies it. Live API integration with
 * MTN MoMo / Airtel Money needs merchant credentials: see docs/PAYMENTS.md.
 */

const DONATION_METHODS = [
    'mtn_momo' => 'MTN Mobile Money',
    'airtel_money' => 'Airtel Money',
    'bank' => 'Bank transfer / deposit',
    'cash' => 'Cash at the Secretariat',
    'card' => 'Card (via payment gateway)',
];

const DONATION_CATEGORIES = ['General', 'Scholarships', 'Infrastructure', 'Student support', 'Research', 'Community development', 'Endowment'];

const DONATION_RECURRING = ['none' => 'One-time', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly'];

/** Verified total raised, optionally for one campaign. */
function donations_raised(?int $campaignId = null): int
{
    return (int) ($campaignId
        ? q("SELECT COALESCE(SUM(amount),0) FROM donations WHERE status = 'verified' AND campaign_id = ?", [$campaignId])->fetchColumn()
        : q("SELECT COALESCE(SUM(amount),0) FROM donations WHERE status = 'verified'")->fetchColumn());
}

function receipt_url(array $d): string
{
    return absolute_url('receipt.php?id=' . (int) $d['id'] . '&t=' . urlencode((string) $d['access_token']));
}

/** Marks a pending donation verified, issues the receipt number and tells the donor. */
function verify_donation(int $id, array $by): bool
{
    $d = q("SELECT * FROM donations WHERE id = ? AND status = 'pending'", [$id])->fetch();
    if (!$d) {
        return false;
    }
    $n = (int) q('SELECT COUNT(*) FROM donations WHERE receipt_no IS NOT NULL')->fetchColumn() + 1;
    $receipt = 'GUCO-R-' . date('Y') . '-' . str_pad((string) $n, 6, '0', STR_PAD_LEFT);
    while (q('SELECT 1 FROM donations WHERE receipt_no = ?', [$receipt])->fetch()) {
        $n++;
        $receipt = 'GUCO-R-' . date('Y') . '-' . str_pad((string) $n, 6, '0', STR_PAD_LEFT);
    }
    q("UPDATE donations SET status = 'verified', receipt_no = ?, verified_by = ?, verified_at = NOW(), reject_reason = NULL WHERE id = ?", [$receipt, $by['id'], $id]);
    $d['receipt_no'] = $receipt;
    audit('verify_donation', "#$id $receipt " . $d['currency'] . ' ' . $d['amount']);
    $link = receipt_url($d);
    send_mail($d['donor_email'], 'Thank you: your GUCO donation receipt',
        "Dear {$d['donor_name']},\n\nThank you for your gift of {$d['currency']} " . number_format((int) $d['amount']) . ". Your payment has been verified.\n\nReceipt number: $receipt\nView or print your receipt: $link\n\nGulu University Convocation");
    if ($d['user_id']) {
        notify((int) $d['user_id'], 'Donation verified', "Receipt $receipt for {$d['currency']} " . number_format((int) $d['amount']), 'member/giving.php');
    }
    return true;
}

function reject_donation(int $id, string $reason): bool
{
    $d = q("SELECT * FROM donations WHERE id = ? AND status = 'pending'", [$id])->fetch();
    if (!$d) {
        return false;
    }
    q("UPDATE donations SET status = 'rejected', reject_reason = ? WHERE id = ?", [mb_substr($reason, 0, 250), $id]);
    audit('reject_donation', "#$id: " . mb_substr($reason, 0, 120));
    send_mail($d['donor_email'], 'About your GUCO donation',
        "Dear {$d['donor_name']},\n\nWe could not match your donation (reference {$d['reference']}) to a payment received.\n" . ($reason !== '' ? "Note: $reason\n" : '') . "\nPlease contact the Secretariat if you believe this is a mistake.\n\nGulu University Convocation");
    if ($d['user_id']) {
        notify((int) $d['user_id'], 'Donation could not be verified', 'Reference ' . $d['reference'] . ($reason !== '' ? ": $reason" : ''), 'member/giving.php');
    }
    return true;
}

/** Adds an in-platform notification for a member. */
function notify(int $userId, string $title, string $body = '', string $link = ''): void
{
    q('INSERT INTO notifications (user_id, title, body, link) VALUES (?,?,?,?)', [$userId, mb_substr($title, 0, 200), mb_substr($body, 0, 500), $link !== '' ? mb_substr($link, 0, 300) : null]);
}

function unread_notifications(int $userId): int
{
    return (int) q('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL', [$userId])->fetchColumn();
}
