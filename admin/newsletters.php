<?php
require_once __DIR__ . '/../includes/admin.php';
crud([
    'table' => 'newsletters', 'title' => 'Newsletters', 'perm' => 'newsletters', 'order' => 'COALESCE(published_at, created_at) DESC',
    'fields' => [
        'title' => ['label' => 'Newsletter title', 'type' => 'text', 'required' => true, 'list' => true],
        'issue_label' => ['label' => 'Issue', 'type' => 'text', 'list' => true, 'hint' => 'e.g. Issue 3, June 2026.'],
        'summary' => ['label' => 'Short summary', 'type' => 'text', 'hint' => 'Shown on the newsletter list and in the email to subscribers (up to 400 characters).'],
        'cover' => ['label' => 'Cover picture', 'type' => 'image', 'hint' => 'Optional. Add more pictures after saving, with the Pictures button.'],
        'body' => ['label' => 'Write the newsletter', 'type' => 'textarea', 'rows' => 16,
            'hint' => 'Optional if you upload a PDF. Blank line = new paragraph; ## Heading, - bullet, **bold**, [link](https://...).'],
        'pdf' => ['label' => 'Newsletter PDF', 'type' => 'doc', 'hint' => 'Optional if you write the newsletter above. PDF up to 20MB.',
            'map' => ['filename' => 'pdf', 'original_name' => 'pdf_original', 'file_size' => 'pdf_size']],
        'status' => ['label' => 'Status', 'type' => 'select', 'list' => true, 'options' => ['draft' => 'Draft', 'published' => 'Published']],
        'published_at' => ['label' => 'Publish date', 'type' => 'datetime', 'list' => true, 'hint' => 'Leave blank to publish now.'],
    ],
    'hook' => function (array &$v, ?array $old) {
        $hasPdf = !empty($v['pdf']) || ($old && !empty($old['pdf']));
        if ($v['body'] === '' && !$hasPdf && $v['status'] === 'published') {
            return 'Write the newsletter or upload its PDF before publishing.';
        }
        if ($v['status'] === 'published' && empty($v['published_at'])) {
            $v['published_at'] = date('Y-m-d H:i:s');
        }
        if (!$old) {
            $v['slug'] = admin_slug('newsletters', $v['title']);
        }
        return null;
    },
    'extra_actions' => fn($r) => '<a class="btn secondary" href="' . e(url('admin/newsletter_pictures.php?id=' . (int) $r['id'])) . '">Pictures</a>'
        . ($r['status'] === 'published' ? '<a class="btn secondary" href="' . e(url('admin/newsletter_send.php?id=' . (int) $r['id'])) . '">Email</a>' : ''),
]);
