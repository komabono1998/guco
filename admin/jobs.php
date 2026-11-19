<?php
require_once __DIR__ . '/../includes/admin.php';
require_once __DIR__ . '/../includes/careers.php';
crud([
    'table' => 'jobs', 'title' => 'Careers & opportunities', 'perm' => 'careers', 'order' => 'created_at DESC',
    'fields' => [
        'title' => ['label' => 'Title', 'type' => 'text', 'required' => true, 'list' => true],
        'kind' => ['label' => 'Type', 'type' => 'select', 'options' => JOB_KINDS, 'list' => true],
        'levels' => ['label' => 'Study or career level', 'type' => 'set', 'options' => JOB_LEVELS, 'hint' => 'Tick every level it suits. Leave all unticked if it suits any level.'],
        'scope' => ['label' => 'Where', 'type' => 'select', 'options' => JOB_SCOPES, 'list' => true],
        'organisation' => ['label' => 'Employer / organisation', 'type' => 'text', 'required' => true, 'list' => true],
        'location' => ['label' => 'Location', 'type' => 'text'],
        'description' => ['label' => 'Description', 'type' => 'textarea'],
        'requirements' => ['label' => 'Requirements', 'type' => 'textarea'],
        'apply_url' => ['label' => 'Application link (optional)', 'type' => 'text', 'hint' => 'Full address starting with https://'],
        'apply_email' => ['label' => 'Application email (optional)', 'type' => 'text'],
        'allow_applications' => ['label' => 'Let members apply through the GUCO portal', 'type' => 'check', 'default' => 1],
        'deadline' => ['label' => 'Closing date', 'type' => 'date', 'list' => true, 'hint' => 'Leave blank for open or ongoing opportunities. Expired ones are hidden automatically.'],
        'verified_on' => ['label' => 'Official link last checked on', 'type' => 'date', 'hint' => 'Shown to visitors. Update it each time you re-check the official page.'],
        'member_only' => ['label' => 'Visible to approved members only', 'type' => 'check', 'list' => true],
        'is_public' => ['label' => 'Published', 'type' => 'check', 'default' => 1, 'list' => true],
    ],
    'hook' => function (array &$v, ?array $old) {
        if ($v['apply_url'] !== '' && !preg_match('#^https?://[^\s]+$#i', $v['apply_url'])) {
            return 'The application link must start with http:// or https://';
        }
        if ($v['apply_email'] !== '' && !filter_var($v['apply_email'], FILTER_VALIDATE_EMAIL)) {
            return 'Enter a valid application email or leave it blank.';
        }
        if (!$old) {
            $v['posted_by'] = current_user()['id'] ?? null;
        }
        return null;
    },
    'extra_actions' => fn(array $r) => '<a class="btn secondary" href="' . e(url('admin/job_applications.php?job=' . (int) $r['id'])) . '">Applicants (' . (int) q('SELECT COUNT(*) FROM job_applications WHERE job_id = ?', [$r['id']])->fetchColumn() . ')</a>',
]);
