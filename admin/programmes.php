<?php
require_once __DIR__ . '/../includes/admin.php';
crud([
    'table' => 'programmes', 'title' => 'Programme impact reports', 'perm' => 'programmes',
    'fields' => [
        'title' => ['label' => 'Activity or programme name', 'type' => 'text', 'required' => true, 'list' => true],
        'area' => ['label' => 'Programme area', 'type' => 'text', 'list' => true],
        'date_location' => ['label' => 'Date and location', 'type' => 'text'],
        'objective' => ['label' => 'Objective', 'type' => 'textarea', 'rows' => 3],
        'target_group' => ['label' => 'Target group', 'type' => 'text'],
        'reach' => ['label' => 'Number reached', 'type' => 'text'],
        'partners' => ['label' => 'Partners and contributors', 'type' => 'text'],
        'outcomes' => ['label' => 'Outputs and measurable outcomes', 'type' => 'textarea', 'rows' => 4],
        'next_steps' => ['label' => 'Next steps', 'type' => 'textarea', 'rows' => 3],
        'is_public' => ['label' => 'Publish (approved activities only)', 'type' => 'check', 'list' => true],
    ],
]);
