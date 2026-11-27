<?php
/**
 * The registration guide (PDF) shared on the Join, Existing member, Membership and Payments pages.
 * The page numbers point to the start of each route inside the PDF: update them if the guide is rewritten.
 */
const GUIDE_PDF = 'assets/docs/GUCO-Membership-Registration-and-Renewal-Guide.pdf';
const GUIDE_PAGE_NEW = 2;       // Route A: new registration
const GUIDE_PAGE_EXISTING = 4;  // Route B: existing GUCO members
const GUIDE_PAGE_RENEW = 5;     // Route C: updating details and renewing

function guide_url(int $page = 1): string
{
    return asset(GUIDE_PDF) . '#page=' . $page;
}

function guide_size(): string
{
    $f = __DIR__ . '/../' . GUIDE_PDF;
    return is_file($f) ? number_format(filesize($f) / 1024) . ' KB' : '';
}

/** A banner offering the guide, with a shortcut for each kind of member. $focus: new | existing | renew | all. */
function guide_strip(string $focus = 'all'): string
{
    if (!is_file(__DIR__ . '/../' . GUIDE_PDF)) {
        return '';
    }
    $btn = function (string $key, string $label, int $page) use ($focus): string {
        return '<a class="gs-btn' . ($focus === $key ? ' main' : '') . '" href="' . e(guide_url($page)) . '" target="_blank" rel="noopener">' . e($label) . '</a>';
    };
    return '<section class="guide-strip" aria-label="Registration guide">'
        . '<span class="gs-ic" aria-hidden="true">' . icon('file', 26) . '</span>'
        . '<div class="gs-tx"><b>Registration guide (PDF)</b><small>Step by step: what to prepare, how to register or renew, and what happens next. ' . e(guide_size()) . '.</small></div>'
        . '<div class="gs-btns">'
        . $btn('new', 'I am new to GUCO', GUIDE_PAGE_NEW)
        . $btn('existing', 'I subscribed before', GUIDE_PAGE_EXISTING)
        . $btn('renew', 'Renew or update', GUIDE_PAGE_RENEW)
        . '<a class="gs-btn full" href="' . e(guide_url(1)) . '" target="_blank" rel="noopener" download>Download the full guide</a>'
        . '</div></section>';
}
