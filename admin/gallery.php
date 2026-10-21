<?php
require_once __DIR__ . '/../includes/admin.php';
crud([
    'table' => 'gallery', 'title' => 'Gallery', 'perm' => 'gallery', 'order' => 'created_at DESC',
    'fields' => [
        'filename' => ['label' => 'Photo', 'type' => 'image', 'required' => true, 'list' => true, 'hint' => 'Upload only photos with consent to publish.'],
        'caption' => ['label' => 'Caption / alt text', 'type' => 'text', 'list' => true],
    ],
]);
