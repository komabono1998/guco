<?php
const JOB_KINDS = [
    'scholarship' => 'Scholarship',
    'fellowship' => 'Fellowship',
    'grant' => 'Grant',
    'job' => 'Job',
    'internship' => 'Internship',
    'volunteer' => 'Volunteering',
    'resource' => 'Career resource',
];

const JOB_KIND_PLURAL = [
    'scholarship' => 'Scholarships', 'fellowship' => 'Fellowships', 'grant' => 'Grants and funding', 'job' => 'Jobs and employers',
    'internship' => 'Internships', 'volunteer' => 'Volunteering', 'resource' => 'Career resources',
];

const JOB_KIND_ICONS = [
    'scholarship' => 'graduation', 'fellowship' => 'award', 'grant' => 'wallet', 'job' => 'briefcase',
    'internship' => 'user-plus', 'volunteer' => 'heart', 'resource' => 'book',
];

const JOB_LEVELS = ['undergraduate' => 'Undergraduate', 'masters' => "Master's", 'phd' => 'PhD', 'professional' => 'Professionals'];

const JOB_SCOPES = ['local' => 'Local (Uganda)', 'international' => 'International'];

const JOB_APPLICATION_STATUS = ['submitted' => 'Submitted', 'shortlisted' => 'Shortlisted', 'rejected' => 'Not selected', 'successful' => 'Successful'];

const JOB_CALL_STATUS = [
    'open' => 'Open now',
    'rolling' => 'Open all year',
    'upcoming' => 'Opening soon',
    'closed' => 'Closed for now',
    'unconfirmed' => 'Check official page',
];

/** The status to show for a row: a future deadline on an unconfirmed or upcoming entry counts as open. */
function job_status_key(array $j): string
{
    $s = $j['call_status'] ?? 'unconfirmed';
    $future = !empty($j['deadline']) && $j['deadline'] >= date('Y-m-d');
    if ($future && in_array($s, ['unconfirmed', 'upcoming'], true)) {
        return 'open';
    }
    if ($s === 'open' && !empty($j['deadline']) && !$future) {
        return 'closed';
    }
    return isset(JOB_CALL_STATUS[$s]) ? $s : 'unconfirmed';
}

/** "Closes 8 Dec 2026 (19 days left)" or an empty string when there is no deadline. */
function job_deadline_text(array $j): string
{
    if (empty($j['deadline'])) {
        return '';
    }
    $days = (int) floor((strtotime($j['deadline']) - strtotime(date('Y-m-d'))) / 86400);
    if ($days < 0) {
        return 'Closed ' . fmt_date($j['deadline']);
    }
    $left = $days === 0 ? 'closes today' : ($days === 1 ? '1 day left' : $days . ' days left');
    return 'Closes ' . fmt_date($j['deadline']) . ' (' . $left . ')';
}

/** Readable list of the levels stored in a jobs.levels set value ("" means any level). */
function job_level_labels(?string $levels): array
{
    $out = [];
    foreach (array_filter(explode(',', (string) $levels)) as $l) {
        if (isset(JOB_LEVELS[$l])) {
            $out[] = JOB_LEVELS[$l];
        }
    }
    return $out;
}
