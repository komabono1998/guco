<?php
require_once __DIR__ . '/../includes/admin.php';
$coordinators = ['' => 'None'] + q("SELECT id, full_name FROM users WHERE role = 'chapter_coordinator' AND status = 'approved' ORDER BY full_name")->fetchAll(PDO::FETCH_KEY_PAIR);
crud([
    'table' => 'chapters', 'title' => 'Alumni chapters', 'perm' => 'chapters', 'order' => 'name',
    'scope_unless' => 'chapters_all',
    'scope' => fn($me) => ['coordinator_user_id = ?', [$me['id']]],
    'fields' => [
        'name' => ['label' => 'Chapter name', 'type' => 'text', 'required' => true, 'list' => true],
        'focus' => ['label' => 'Focus (region, college, profession…)', 'type' => 'text', 'list' => true],
        'description' => ['label' => 'Description', 'type' => 'textarea'],
        'contact_name' => ['label' => 'Official contact name', 'type' => 'text'],
        'contact_email' => ['label' => 'Official contact email', 'type' => 'text'],
        'coordinator_user_id' => ['label' => 'Chapter coordinator (user)', 'type' => 'select', 'options' => $coordinators, 'allow_empty' => true, 'perm' => 'chapters_all'],
        'is_public' => ['label' => 'Show on website', 'type' => 'check', 'default' => 1, 'list' => true],
    ],
]);
