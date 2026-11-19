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
