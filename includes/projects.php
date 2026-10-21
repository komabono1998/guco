<?php
const CHAPTER_ROLES = [
    'member' => 'Member', 'chairperson' => 'Chairperson', 'vice_chairperson' => 'Vice Chairperson', 'secretary' => 'Secretary',
    'treasurer' => 'Treasurer', 'organising_secretary' => 'Organising Secretary', 'committee_member' => 'Executive committee member',
];

const AVAILABILITY_OPTIONS = ['Weekdays', 'Evenings', 'Weekends', 'Flexible'];

const PROJECT_THEMES = [
    'Education and scholarships', 'Health and wellbeing', 'Agriculture and livelihoods', 'Environment and climate',
    'Water, sanitation and infrastructure', 'Youth and skills', 'Gender and inclusion', 'Research and innovation',
    'Peace and community development', 'Other',
];

/** status => [label, badge class, who sees it]. */
const PROJECT_STATUS = [
    'draft' => ['Draft', 'warn'],
    'submitted' => ['Submitted', 'warn'],
    'under_review' => ['Under review', 'warn'],
    'changes_requested' => ['Changes requested', 'bad'],
    'approved' => ['Approved', ''],
    'in_progress' => ['In progress', ''],
    'completed' => ['Completed', ''],
    'rejected' => ['Not approved', 'bad'],
    'cancelled' => ['Cancelled', 'bad'],
];

/** Statuses where the proposer may still edit the proposal. */
const PROJECT_EDITABLE = ['draft', 'changes_requested'];

/** Statuses where the approved lead drives implementation and resource mobilisation. */
const PROJECT_ACTIVE = ['approved', 'in_progress'];

function project_badge(string $status): string
{
    [$label, $cls] = PROJECT_STATUS[$status] ?? [$status, ''];
    return '<span class="badge ' . $cls . '">' . e($label) . '</span>';
}

/** Verified funds mobilised for a project. */
function project_raised(int $projectId): float
{
    return (float) q("SELECT COALESCE(SUM(amount), 0) FROM project_updates WHERE project_id = ? AND kind = 'mobilisation' AND verified = 1", [$projectId])->fetchColumn();
}

/** "Jane T." style name for public pages. */
function short_name(string $full): string
{
    $parts = preg_split('/\s+/', trim($full));
    return count($parts) > 1 ? $parts[0] . ' ' . mb_strtoupper(mb_substr(end($parts), 0, 1)) . '.' : $full;
}

/** Joins a member to a chapter; approved immediately when the chapter does not need approval. */
function chapter_activate_primary(int $userId, int $chapterId): void
{
    q('UPDATE member_profiles SET chapter_id = ? WHERE user_id = ? AND chapter_id IS NULL', [$chapterId, $userId]);
}

/** After a membership ends, point the profile at another approved chapter (or none). */
function chapter_refresh_primary(int $userId): void
{
    $cur = q('SELECT chapter_id FROM member_profiles WHERE user_id = ?', [$userId])->fetchColumn();
    $ok = $cur && q("SELECT 1 FROM chapter_members WHERE user_id = ? AND chapter_id = ? AND status = 'approved'", [$userId, $cur])->fetch();
    if (!$ok) {
        $next = q("SELECT chapter_id FROM chapter_members WHERE user_id = ? AND status = 'approved' ORDER BY created_at LIMIT 1", [$userId])->fetchColumn();
        q('UPDATE member_profiles SET chapter_id = ? WHERE user_id = ?', [$next ?: null, $userId]);
    }
}
