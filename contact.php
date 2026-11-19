<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/forms.php';
$pageTitle = 'Contact';
$pageDesc = 'Contact the GUCO Secretariat about membership, alumni chapters, events or partnerships.';
handle_enquiry_post('contact.php');
require __DIR__ . '/includes/header.php';
$details = [
    'Email' => setting('contact_email'), 'Telephone' => setting('contact_phone'), 'Office hours' => setting('office_hours'),
    'Membership support' => setting('membership_contact'), 'Chapter enquiries' => setting('chapter_contact'),
];
?>
<div class="page-head"><h1>Contact GUCO</h1><?= cr('contact.intro') ?></div>
<?php
$ctIcons = ['Email' => 'mail', 'Telephone' => 'phone', 'Office hours' => 'clock', 'Membership support' => 'users', 'Chapter enquiries' => 'map-pin'];
$ctLink = function (string $label, string $v): string {
    if ($label === 'Email') return 'mailto:' . $v;
    if ($label === 'Telephone') return 'tel:' . preg_replace('/[^\d+]/', '', $v);
    return '';
};
?>
<?php if (array_filter($details)): ?>
<section class="ct-quick" aria-label="Ways to reach GUCO">
  <?php foreach (array_filter($details) as $label => $v): $href = $ctLink($label, $v); ?>
    <<?= $href ? 'a href="' . e($href) . '"' : 'div' ?> class="ct-tile">
      <span class="ct-ic"><?= icon($ctIcons[$label] ?? 'mail', 22) ?></span>
      <span class="ct-tx"><small><?= e($label) ?></small><b><?= e($v) ?></b></span>
    </<?= $href ? 'a' : 'div' ?>>
  <?php endforeach; ?>
</section>
<?php endif; ?>

<section class="ct-main">
  <div class="ct-form">
    <span class="eyebrow">Write to the Secretariat</span>
    <h2>Send us a message</h2>
    <p class="ct-lead">Tell us what you need and choose the topic that fits. We will reply by email.</p>
    <?php enquiry_form(in_array($_GET['category'] ?? '', ENQUIRY_CATEGORIES, true) ? $_GET['category'] : 'General'); ?>
  </div>
  <aside class="ct-side">
    <address class="ct-addr">
      <span class="ct-addr-ic"><?= icon('map-pin', 26) ?></span>
      <h2>Official address</h2>
      <p><?= implode('<br>', array_map('e', c_lines('contact.address'))) ?></p>
      <a class="ct-addr-link" href="#gmap">See it on the map <?= icon('arrow-right', 16) ?></a>
    </address>
    <div class="ct-steps">
      <h3>What happens next</h3>
      <ol>
        <li><span>1</span><div><b>You send your message</b><small>Choose the topic so it reaches the right officer.</small></div></li>
        <li><span>2</span><div><b>The Secretariat reads it</b><small>Your message is stored safely and handled under our privacy notice.</small></div></li>
        <li><span>3</span><div><b>You get a reply</b><small>We answer by email, so check the address you give.</small></div></li>
      </ol>
    </div>
    <div class="ct-links">
      <a href="<?= url('register.php') ?>"><?= icon('user-plus', 18) ?> Join GUCO</a>
      <a href="<?= url('claim.php') ?>"><?= icon('id-card', 18) ?> Find your GUCO number</a>
      <a href="<?= url('member/login.php') ?>"><?= icon('lock', 18) ?> Member Portal</a>
    </div>
  </aside>
</section>

<?php require_once __DIR__ . '/includes/places.php'; ?>
<section class="gm" id="gmap" aria-labelledby="gmTitle" data-places="<?= e(json_encode(gulu_places(), JSON_UNESCAPED_UNICODE)) ?>" data-groups="<?= e(json_encode(PLACE_GROUPS, JSON_UNESCAPED_UNICODE)) ?>">
  <header class="gm-head">
    <span class="eyebrow">Find us in Gulu</span>
    <h2 id="gmTitle">Map of Gulu, with directions</h2>
    <p>See Gulu University and the main public offices in Gulu City. Choose a place for a driving route, or switch on your location to be guided from where you are.</p>
  </header>
  <div class="gm-bar">
    <label class="gm-from">Start from <select id="gmFrom" aria-label="Start from"></select></label>
    <button type="button" class="gm-btn" id="gmLocate"><?= icon('map-pin', 16) ?> Use my location</button>
    <button type="button" class="gm-btn gm-live" id="gmTrack" aria-pressed="false">Track my location live</button>
    <button type="button" class="gm-btn gm-ghost" id="gmAll">Show all of Gulu</button>
    <button type="button" class="gm-btn gm-ghost" id="gmClear" hidden>Clear route</button>
  </div>
  <p class="gm-status" id="gmStatus" role="status" aria-live="polite">Choose a place on the map or in the list to see the way there.</p>
  <div class="gm-grid">
    <aside class="gm-side">
      <label class="gm-search"><?= icon('search', 16) ?><input type="search" id="gmQ" placeholder="Search places" aria-label="Search places"></label>
      <div class="gm-chips" role="group" aria-label="Type of place">
        <button type="button" class="on" data-group="">All</button>
        <?php foreach (PLACE_GROUPS as $k => $l): ?><button type="button" data-group="<?= e($k) ?>"><?= e($l) ?></button><?php endforeach; ?>
      </div>
      <ul class="gm-list" id="gmList"></ul>
    </aside>
    <div class="gm-mapwrap">
      <div id="gmapCanvas" class="gm-canvas" role="application" aria-label="Map of Gulu with Gulu University and public offices"></div>
    </div>
  </div>
  <p class="gm-note">Map data &copy; OpenStreetMap contributors; routes by OSRM. Positions are approximate, so please confirm on the ground. Your location is used only in your browser to draw the map and the route. GUCO does not receive or store it, and live tracking stops when you leave the page. Location needs a secure (https) address on the live site.</p>
</section>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" integrity="sha256-tXCrvaljxgtN5LT/Syb5Mm9T+yzPFGH98JVcoJT7JTk=" crossorigin="">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" integrity="sha256-XJrs/DDkVkUZ29zdzFOkGCJ9zHVo5hnpdi3c7HYJ7Uc=" crossorigin=""></script>
<script src="<?= asset('assets/js/gulu-map.js') ?>"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
