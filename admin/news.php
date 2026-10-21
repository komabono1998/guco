<?php
require_once __DIR__ . '/../includes/admin.php';
crud([
    'table' => 'news', 'title' => 'News & stories', 'perm' => 'news', 'order' => 'created_at DESC',
    'fields' => [
        'title' => ['label' => 'Headline', 'type' => 'text', 'required' => true, 'list' => true],
        'category' => ['label' => 'Category', 'type' => 'select', 'options' => array_combine(NEWS_CATEGORIES, NEWS_CATEGORIES), 'list' => true],
        'summary' => ['label' => 'Summary', 'type' => 'text', 'hint' => 'Optional, up to 300 characters.'],
        'body' => ['label' => 'Article', 'type' => 'textarea', 'rows' => 12, 'required' => true],
        'image' => ['label' => 'Photo', 'type' => 'image', 'hint' => 'Only with permission to publish.'],
        'status' => ['label' => 'Status', 'type' => 'select', 'list' => true,
            'options' => ['draft' => 'Draft', 'pending' => 'Submit for review', 'published' => 'Published (publishers only)']],
        'published_at' => ['label' => 'Publish date/time', 'type' => 'datetime', 'list' => true, 'hint' => 'Leave blank to publish now. A future date schedules the article.'],
    ],
    'hook' => function (array &$v, ?array $old) {
        $me = current_user();
        if (!can('publish')) {
            if ($old && $old['status'] === 'published') {
                return 'Only publishers can edit a published article.';
            }
            if ($v['status'] === 'published') {
                $v['status'] = 'pending';
            }
        }
        if ($v['status'] === 'published' && empty($v['published_at'])) {
            $v['published_at'] = date('Y-m-d H:i:s');
        }
        if (!$old) {
            $v['slug'] = admin_slug('news', $v['title']);
            $v['author_id'] = $me['id'];
        }
        return null;
    },
]);
