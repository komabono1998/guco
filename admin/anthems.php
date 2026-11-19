<?php
require_once __DIR__ . '/../includes/admin.php';
crud([
    'table' => 'anthems', 'title' => 'Anthems', 'perm' => 'content', 'order' => 'sort_order, id',
    'fields' => [
        'title' => ['label' => 'Title', 'type' => 'text', 'required' => true, 'list' => true],
        'slug' => ['label' => 'Short name for the web address', 'type' => 'text', 'required' => true, 'hint' => 'Lower-case letters, numbers and hyphens, for example gulu-university.'],
        'kicker' => ['label' => 'Type (shown above the title)', 'type' => 'text', 'list' => true, 'hint' => 'For example University anthem, National anthem, Regional anthem.'],
        'body' => ['label' => 'Belongs to', 'type' => 'text', 'hint' => 'For example Gulu University, Republic of Uganda, East African Community.'],
        'language' => ['label' => 'Language', 'type' => 'text'],
        'description' => ['label' => 'Short description', 'type' => 'text'],
        'credit' => ['label' => 'Credit (writer, composer or source)', 'type' => 'text'],
        'lyrics' => ['label' => 'Lyrics', 'type' => 'textarea', 'rows' => 14, 'hint' => 'One line per line. A blank line starts a new verse or chorus.'],
        'audio' => ['label' => 'Recording (MP3, M4A, OGG or WAV up to 25MB, or MP4 up to 5MB)', 'type' => 'audio', 'list' => true],
        'video_url' => ['label' => 'Video link (optional)', 'type' => 'text', 'hint' => 'A YouTube link plays on the page; any other link opens in a new tab.'],
        'show_home' => ['label' => 'Play on the home page', 'type' => 'check', 'list' => true, 'hint' => 'Only the first ticked anthem is shown on the home page.'],
        'sort_order' => ['label' => 'Order (smaller first)', 'type' => 'number', 'default' => 100],
        'is_public' => ['label' => 'Published', 'type' => 'check', 'default' => 1, 'list' => true],
    ],
    'hook' => function (array &$v, ?array $old) {
        if (!preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $v['slug'])) {
            return 'The short name may use only lower-case letters, numbers and hyphens.';
        }
        $dup = q('SELECT 1 FROM anthems WHERE slug = ? AND id <> ?', [$v['slug'], $old['id'] ?? 0])->fetch();
        if ($dup) {
            return 'Another anthem already uses that short name.';
        }
        if ($v['video_url'] !== '' && !preg_match('#^https?://[^\s]+$#i', $v['video_url'])) {
            return 'The video link must start with http:// or https://';
        }
        return null;
    },
]);
