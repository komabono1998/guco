<?php
require_once __DIR__ . '/../includes/admin.php';
$coordinators = ['' => 'None'] + q("SELECT id, full_name FROM users WHERE role = 'chapter_coordinator' AND status = 'approved' ORDER BY full_name")->fetchAll(PDO::FETCH_KEY_PAIR);
crud([
    'table' => 'chapters', 'title' => 'Alumni chapters', 'perm' => 'chapters', 'order' => 'sort_order, name',
    'scope_unless' => 'chapters_all',
    'scope' => fn($me) => ['coordinator_user_id = ?', [$me['id']]],
    'fields' => [
        'name' => ['label' => 'Chapter name', 'type' => 'text', 'required' => true, 'list' => true, 'hint' => 'e.g. Northern Uganda Chapter, Diaspora Chapter.'],
        'focus' => ['label' => 'Type', 'type' => 'select', 'list' => true, 'allow_empty' => true,
            'options' => ['' => 'Not set', 'Regional chapter' => 'Regional chapter', 'Diaspora chapter' => 'Diaspora chapter', 'Professional chapter' => 'Professional chapter', 'College / faculty chapter' => 'College / faculty chapter', 'Year-group chapter' => 'Year-group chapter']],
        'image' => ['label' => 'Chapter photo', 'type' => 'image', 'hint' => 'Optional banner shown on the Chapters page.'],
        'description' => ['label' => 'About the chapter', 'type' => 'textarea', 'rows' => 4],
        'districts' => ['label' => 'Districts / areas covered', 'type' => 'textarea', 'rows' => 3, 'hint' => 'One line or a comma-separated list. For the Diaspora chapter list countries or leave blank.'],
        'meeting_info' => ['label' => 'Meetings and activities', 'type' => 'text', 'hint' => 'When and where the chapter meets.'],
        'contact_name' => ['label' => 'Official contact name', 'type' => 'text'],
        'contact_email' => ['label' => 'Official contact email', 'type' => 'text'],
        'coordinator_user_id' => ['label' => 'Chapter coordinator (staff user)', 'type' => 'select', 'options' => $coordinators, 'allow_empty' => true, 'perm' => 'chapters_all',
            'hint' => 'Create the account in Users & roles with the Chapter Coordinator role. Coordinators approve this chapter\'s members.'],
        'accepting_members' => ['label' => 'Accepting new members', 'type' => 'check', 'default' => 1, 'list' => true],
        'requires_approval' => ['label' => 'New members need coordinator approval (untick for instant joining)', 'type' => 'check', 'default' => 1],
        'sort_order' => ['label' => 'Display order', 'type' => 'number', 'default' => 100, 'hint' => 'Lower numbers appear first.'],
        'is_public' => ['label' => 'Show on website', 'type' => 'check', 'default' => 1, 'list' => true],
    ],
    'extra_actions' => fn($r) => '<a class="btn secondary" href="' . e(url('admin/chapter_members.php?chapter=' . (int) $r['id'])) . '">Members</a>',
]);
