<?php
require_once __DIR__ . '/../includes/admin.php';
$cats = array_combine(DONATION_CATEGORIES, DONATION_CATEGORIES);
crud([
    'table' => 'campaigns', 'title' => 'Fundraising campaigns', 'perm' => 'donations', 'order' => 'id DESC',
    'fields' => [
        'title' => ['label' => 'Campaign title', 'type' => 'text', 'required' => true, 'list' => true],
        'category' => ['label' => 'Cause', 'type' => 'select', 'options' => $cats, 'list' => true],
        'summary' => ['label' => 'Short summary (shown on the Give page)', 'type' => 'text'],
        'description' => ['label' => 'Objectives and details', 'type' => 'textarea', 'hint' => 'Explain what the money will do. A blank line starts a new paragraph.'],
        'goal_amount' => ['label' => 'Target amount (UGX, 0 for no target)', 'type' => 'number', 'list' => true],
        'starts_on' => ['label' => 'Starts on', 'type' => 'date'],
        'ends_on' => ['label' => 'Ends on', 'type' => 'date', 'list' => true],
        'image' => ['label' => 'Campaign picture', 'type' => 'image'],
        'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft (hidden)', 'active' => 'Active (taking gifts)', 'closed' => 'Closed'], 'list' => true],
        'is_public' => ['label' => 'Show on the public Give page', 'type' => 'check', 'default' => 1],
    ],
    'list_extra' => [
        'Raised (UGX)' => fn(array $r) => number_format(donations_raised((int) $r['id'])),
    ],
]);
