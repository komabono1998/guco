<?php
require_once __DIR__ . '/../includes/admin.php';
require_perm('branding');
$toggles = [
    'home_show_welcome' => 'Welcome message', 'home_show_cards' => 'Highlight cards (About, Membership, Chapters, Partnerships)',
    'home_show_news' => 'News and newsletter', 'home_show_events' => 'Upcoming events list',
    'home_show_awards' => 'Awards summary', 'home_show_gallery' => 'Photo strip', 'home_show_cta' => 'Closing call to action',
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $set = fn($k, $v) => q('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$k, $v]);
    $name = trim($_POST['site_name'] ?? '');
    if ($name === '') {
        flash('The site name cannot be empty.', 'error');
        redirect('admin/branding.php');
    }
    $set('site_name', $name);
    $set('site_tagline', trim($_POST['site_tagline'] ?? ''));
    foreach (['color_primary', 'color_accent'] as $k) {
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $_POST[$k] ?? '')) {
            $set($k, strtolower($_POST[$k]));
        }
    }
    if (!empty($_POST['reset_colors'])) {
        $set('color_primary', '#14532d');
        $set('color_accent', '#f2b807');
    }
    if (($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $logo = save_upload($_FILES['logo']);
        if (!$logo) {
            flash('The logo must be a JPG, PNG or WebP image under 5MB.', 'error');
            redirect('admin/branding.php');
        }
        $set('logo', $logo);
    } elseif (!empty($_POST['remove_logo'])) {
        $set('logo', '');
    }
    foreach ($toggles as $k => $_) {
        $set($k, isset($_POST[$k]) ? '1' : '0');
    }
    audit('update_branding', 'site identity, colours and homepage sections');
    flash('Branding and layout saved.');
    redirect('admin/branding.php');
}
admin_header('Branding & layout');
?>
<form method="post" enctype="multipart/form-data" class="stack wide">
  <?= csrf_field() ?>
  <fieldset><legend>Identity</legend>
    <div class="cols">
      <label>Website name <input name="site_name" required value="<?= e(setting('site_name', 'Gulu University Convocation')) ?>"></label>
      <label>Tagline / slogan <span class="hint">(must read exactly as approved)</span> <input name="site_tagline" value="<?= e(setting('site_tagline', 'Community Transformation Actualized')) ?>"></label>
    </div>
    <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
      <img src="<?= e(logo_url()) ?>" alt="Current logo" width="84" height="84" style="border-radius:50%;background:#fff;border:1px solid var(--line)">
      <label style="flex:1;min-width:240px">Logo <span class="hint">(square JPG, PNG or WebP, up to 5MB; used in the header, hero, browser tab and admin)</span>
        <input type="file" name="logo" accept="image/jpeg,image/png,image/webp"></label>
      <?php if (setting('logo')): ?><label class="check"><input type="checkbox" name="remove_logo" value="1"> Go back to the default logo</label><?php endif; ?>
    </div>
  </fieldset>

  <fieldset><legend>Colours</legend>
    <div class="swatch-row">
      <label>Main colour <input type="color" name="color_primary" value="<?= e(setting('color_primary', '#14532d')) ?>"></label>
      <label>Accent colour <input type="color" name="color_accent" value="<?= e(setting('color_accent', '#f2b807')) ?>"></label>
      <label class="check"><input type="checkbox" name="reset_colors" value="1"> Reset to the GUCO green and gold</label>
    </div>
    <p class="hint">The main colour is used for the header, buttons and headings; the accent colour for highlights. Keep strong contrast with white text for accessibility.</p>
  </fieldset>

  <fieldset><legend>Home page sections</legend>
    <p class="hint">Switch sections on or off. Wording and images are edited under Site content &rarr; Home page.</p>
    <div class="checks-grid">
      <?php foreach ($toggles as $k => $label): ?><label class="check"><input type="checkbox" name="<?= $k ?>" <?= setting($k, '1') === '1' ? 'checked' : '' ?>> <?= e($label) ?></label><?php endforeach; ?>
    </div>
  </fieldset>
  <div class="savebar"><button>Save</button></div>
</form>
<?php admin_footer(); ?>
