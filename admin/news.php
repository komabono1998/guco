<?php
require_once __DIR__ . '/../includes/admin.php';
crud([
    'table' => 'news', 'title' => 'News & stories', 'perm' => 'news', 'order' => 'created_at DESC',
    'fields' => [
        'source_type' => ['label' => 'How are you publishing this news?', 'type' => 'select', 'list' => true,
            'options' => ['article' => 'Write the article here', 'link' => 'Share a link from a news source', 'document' => 'Upload a document (PDF or Office file)'],
            'hint' => 'Link: fill in the source link below. Document: upload the file below. Article: write the story in the Article box.'],
        'title' => ['label' => 'Headline', 'type' => 'text', 'required' => true, 'list' => true],
        'category' => ['label' => 'Category', 'type' => 'select', 'options' => array_combine(NEWS_CATEGORIES, NEWS_CATEGORIES), 'list' => true],
        'external_url' => ['label' => 'Link to the news source', 'type' => 'text', 'hint' => 'Full address, e.g. https://www.newvision.co.ug/... (for "Share a link").'],
        'source_name' => ['label' => 'Source name', 'type' => 'text', 'hint' => 'e.g. New Vision, Daily Monitor, Gulu University website.'],
        'summary' => ['label' => 'Summary', 'type' => 'text', 'hint' => 'Optional, up to 300 characters. Recommended for links and documents.'],
        'body' => ['label' => 'Article', 'type' => 'textarea', 'rows' => 12, 'hint' => 'For written articles. Blank line = new paragraph; ## Heading, - bullet, **bold**, [link](https://...).'],
        'image' => ['label' => 'Photo', 'type' => 'image', 'hint' => 'Only with permission to publish.'],
        'document' => ['label' => 'Document', 'type' => 'doc', 'hint' => 'PDF or Office file up to 20MB (for "Upload a document").',
            'map' => ['filename' => 'document', 'original_name' => 'document_original', 'file_size' => 'document_size']],
        'status' => ['label' => 'Status', 'type' => 'select', 'list' => true,
            'options' => ['draft' => 'Draft', 'pending' => 'Submit for review', 'published' => 'Published (publishers only)']],
        'published_at' => ['label' => 'Publish date/time', 'type' => 'datetime', 'list' => true, 'hint' => 'Leave blank to publish now. A future date schedules the story.'],
    ],
    'hook' => function (array &$v, ?array $old) {
        $me = current_user();
        if ($v['source_type'] === 'link') {
            if (!preg_match('#^https?://[^\s]+$#i', $v['external_url'])) {
                return 'Enter the full link to the news source, starting with http:// or https://';
            }
            $v['body'] = $v['body'] !== '' ? $v['body'] : null;
        } elseif ($v['source_type'] === 'document') {
            if (!$old || empty($old['document'])) {
                if (empty($v['document'])) {
                    return 'Upload the document to publish.';
                }
            }
            $v['external_url'] = null;
        } else {
            if ($v['body'] === '') {
                return 'Write the article, or choose another way of publishing.';
            }
            $v['external_url'] = null;
        }
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
