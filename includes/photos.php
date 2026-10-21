<?php
/** Photos from the Alumni & Stakeholders Dinner 2026, stored in assets/img/dinner/. */
const DINNER_ALBUM = 'Alumni & Stakeholders Dinner 2026';

const DINNER_PHOTOS = [
    3 => 'Presenters on stage holding programme folders at the Alumni and Stakeholders Dinner',
    4 => 'A Certificate of Recognition being presented on stage',
    5 => 'A guest at the dinner table',
    6 => 'Guests sharing a meal at the dinner',
    7 => 'Guests serving themselves at the dinner buffet',
    8 => 'Portrait of a guest at the dinner',
    9 => 'Two alumni posing together at the dinner',
    10 => 'A guest seated near the stage',
    11 => 'A speaker addressing the dinner',
    12 => 'A speaker addressing the dinner',
    13 => 'A speaker addressing the dinner with other guests standing behind',
    14 => 'Three guests on stage in front of the dinner screen',
    15 => 'A speaker addressing guests at the dinner',
    16 => 'Three guests on stage, one speaking',
    17 => 'Portrait of a guest at the dinner',
    18 => 'A guest in profile in front of the dinner screen',
    19 => 'Four guests standing on stage',
    21 => 'Three guests standing on stage',
    22 => 'A speaker addressing the dinner',
];

function dinner_src(int $n): string
{
    return url('assets/img/dinner/dinner-' . sprintf('%02d', $n) . '.jpg');
}

/** A single photo wrapped in a figure; $caption is optional visible text. */
function dinner_figure(int $n, string $class = '', ?string $caption = null): string
{
    $img = '<img src="' . e(dinner_src($n)) . '" alt="' . e(DINNER_PHOTOS[$n]) . '" loading="lazy">';
    return '<figure class="photo ' . e($class) . '">' . $img . ($caption ? '<figcaption>' . e($caption) . '</figcaption>' : '') . '</figure>';
}
