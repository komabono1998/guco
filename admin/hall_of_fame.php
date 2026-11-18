<?php
require_once __DIR__ . '/../includes/admin.php';
$cats = array_combine(FAME_CATEGORIES, FAME_CATEGORIES);
crud([
    'table' => 'hall_of_fame', 'title' => 'Alumni Hall of Fame', 'perm' => 'halloffame', 'order' => 'sort_order, inducted_year DESC, name',
    'fields' => [
        'name' => ['label' => 'Name', 'type' => 'text', 'required' => true, 'list' => true],
        'title' => ['label' => 'Position / title', 'type' => 'text', 'list' => true],
        'category' => ['label' => 'Category', 'type' => 'select', 'options' => $cats, 'list' => true],
        'programme' => ['label' => 'Programme studied', 'type' => 'text'],
        'graduation_year' => ['label' => 'Graduation year', 'type' => 'number'],
        'inducted_year' => ['label' => 'Year inducted', 'type' => 'number', 'list' => true],
        'citation' => ['label' => 'Citation: achievements and why they are honoured', 'type' => 'textarea', 'hint' => 'A blank line starts a new paragraph. Publish only facts the honouree or the Awards Committee has confirmed.'],
        'photo' => ['label' => 'Portrait photo', 'type' => 'image'],
        'featured' => ['label' => 'Feature on the home page', 'type' => 'check', 'list' => true],
        'sort_order' => ['label' => 'Order (smaller first)', 'type' => 'number', 'default' => 100],
        'is_public' => ['label' => 'Published', 'type' => 'check', 'default' => 1, 'list' => true],
    ],
]);
