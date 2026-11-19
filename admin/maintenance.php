<?php
require_once __DIR__ . '/../includes/admin.php';
$admin = require_perm('settings');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle') {
        $on = !maintenance_on();
        maintenance_set('maintenance_on', $on ? '1' : '0');
        audit('maintenance_mode', $on ? 'turned on' : 'turned off');
        flash($on ? 'Maintenance mode is ON. Visitors now see the maintenance page; you and other signed-in staff can still use the site.' : 'Maintenance mode is OFF. The website is open to everyone again.');
    } elseif ($action === 'save') {
        $until = trim($_POST['maintenance_until'] ?? '');
        if ($until !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $until)) {
            flash('Enter the expected return time as a date and time, or leave it blank.', 'error');
            redirect('admin/maintenance.php');
        }
        maintenance_set('maintenance_title', mb_substr(trim($_POST['maintenance_title'] ?? ''), 0, 120));
        maintenance_set('maintenance_message', mb_substr(trim($_POST['maintenance_message'] ?? ''), 0, 1200));
        maintenance_set('maintenance_until', $until);
        maintenance_set('maintenance_whatsapp', mb_substr(trim($_POST['maintenance_whatsapp'] ?? ''), 0, 40));
        maintenance_set('maintenance_contacts', mb_substr(trim($_POST['maintenance_contacts'] ?? ''), 0, 800));
        audit('maintenance_settings', 'message and contacts updated');
        flash('Maintenance message and contacts saved.');
    }
    redirect('admin/maintenance.php');
}

$on = maintenance_on();
$until = maintenance_until_ts();
admin_header('Maintenance mode', 'Switch the public website off while you work on it, with a message and ways to reach you');
?>
<section class="card mt-hero <?= $on ? 'is-on' : 'is-off' ?>" style="max-width:860px;margin-bottom:22px;padding:26px">
  <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap">
    <span class="mt-led" aria-hidden="true"></span>
    <div style="flex:1;min-width:240px">
      <h2 style="margin:0 0 4px"><?= $on ? 'Maintenance mode is ON' : 'The website is live' ?></h2>
      <p class="meta" style="margin:0"><?= $on
          ? 'Visitors and members see the maintenance page. You, and other signed-in staff, still see the full website, and the admin area and its sign-in stay open.'
          : 'Everyone can use the website. Switch maintenance mode on before big changes, updates or repairs.' ?></p>
    </div>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="toggle">
      <button class="<?= $on ? '' : 'danger' ?>" style="font-size:1.05rem;padding:14px 26px" onclick="return confirm('<?= $on ? 'Turn the website back on for everyone?' : 'Turn the website off for visitors and show the maintenance page?' ?>')">
        <?= $on ? 'Turn the website back ON' : 'Turn the website OFF for maintenance' ?>
      </button>
    </form>
  </div>
  <p class="meta" style="margin:14px 0 0">See what visitors see: open the website in a private or incognito window, or <a href="<?= e(url('')) ?>" target="_blank" rel="noopener">open the site</a> after signing out.</p>
</section>

<form method="post" class="stack wide card">
  <?= csrf_field() ?><input type="hidden" name="action" value="save">
  <h3 style="margin:0">What visitors will read</h3>
  <label>Heading <input name="maintenance_title" maxlength="120" value="<?= e(setting('maintenance_title')) ?>" placeholder="We will be back soon"></label>
  <label>Message <span class="hint">(plain text; line breaks are kept)</span>
    <textarea name="maintenance_message" rows="5" maxlength="1200" placeholder="Gulu University Convocation (GUCO) is briefly offline for scheduled maintenance. We are improving the website and will be back shortly. Thank you for your patience."><?= e(setting('maintenance_message')) ?></textarea></label>
  <label>Expected return <span class="hint">(optional, Uganda time; shows a countdown)</span>
    <input type="datetime-local" name="maintenance_until" value="<?= e(setting('maintenance_until')) ?>"></label>

  <h3 style="margin:10px 0 0">How people can reach you meanwhile</h3>
  <p class="meta" style="margin:0">The official email, telephone and social media accounts from <a href="settings.php">Settings</a> are shown automatically. Add more below.</p>
  <label>WhatsApp number <span class="hint">(with country code, for example +256700000000)</span>
    <input name="maintenance_whatsapp" maxlength="40" value="<?= e(setting('maintenance_whatsapp')) ?>"></label>
  <label>Other contacts <span class="hint">(one per line as Label | value, for example Membership | membership@example.com)</span>
    <textarea name="maintenance_contacts" rows="4" maxlength="800" placeholder="Membership | membership@example.com&#10;Secretariat office | +256 700 000 000"><?= e(setting('maintenance_contacts')) ?></textarea></label>
  <?php $now = maintenance_channels(); ?>
  <p class="meta" style="margin:0"><strong>Showing now:</strong> <?= $now ? e(implode(', ', array_map(fn($c) => $c[0], $now))) : 'no contact channels yet. Add the official email and telephone in Settings, or the fields above.' ?></p>
  <div class="row-actions"><button>Save message and contacts</button></div>
</form>
<style>
.mt-hero.is-on { border-left: 8px solid #c24141; background: #fff7f7; }
.mt-hero.is-off { border-left: 8px solid #2e8b57; }
.mt-led { width: 22px; height: 22px; border-radius: 50%; background: #2e8b57; box-shadow: 0 0 0 6px rgba(46, 139, 87, .2); flex: none; }
.mt-hero.is-on .mt-led { background: #c24141; box-shadow: 0 0 0 6px rgba(194, 65, 65, .22); animation: mt-blink 1.6s ease-in-out infinite; }
@keyframes mt-blink { 50% { opacity: .35; } }
</style>
<?php admin_footer(); ?>
