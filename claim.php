<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Existing member? Find your GUCO number';
$pageDesc = 'Members who already hold a GUCO number can find their record by number or name, confirm their registration, renew their subscription and create their Member Portal account.';
$already = current_user();
$found = null;      // the record the visitor picked
$matches = null;    // records matching a search (null before any search)
$tooMany = false;
$number = trim($_POST['guco_no'] ?? '');
$name = trim($_POST['name'] ?? '');

if (($_GET['cancel'] ?? '') === '1') {
    unset($_SESSION['legacy_claim'], $_SESSION['claim_options']);
    redirect('claim.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);
    $action = $_POST['action'] ?? 'search';
    q('DELETE FROM login_attempts WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
    $byIp = (int) q("SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND email LIKE 'claim%' AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)", [$ip])->fetchColumn();
    if (is_spam()) {
        flash('Your request could not be submitted. Please try again.', 'error');
    } elseif ($byIp >= 25) {
        flash('Too many searches. Please wait 15 minutes and try again.', 'error');
    } elseif ($action === 'pick') {
        $pickId = (int) ($_POST['id'] ?? 0);
        if (in_array($pickId, $_SESSION['claim_options'] ?? [], true)) {
            $found = q('SELECT * FROM legacy_members WHERE id = ?', [$pickId])->fetch() ?: null;
            if ($found && $found['status'] === 'unclaimed') {
                $_SESSION['legacy_claim'] = ['id' => (int) $found['id'], 'at' => time()];
                redirect('register.php');
            }
        } else {
            flash('Please search again and choose your record.', 'error');
        }
    } else {
        $no = $number !== '' ? legacy_norm_no($number) : null;
        $tokens = array_values(array_filter(preg_split('/[\s,.]+/u', $name), fn($t) => mb_strlen($t) >= 2));
        q('INSERT INTO login_attempts (ip, email) VALUES (?,?)', [$ip, 'claim']);
        if ($number === '' && !$tokens) {
            flash('Enter your GUCO number, your name, or both.', 'error');
        } elseif ($number !== '' && !$no) {
            flash('That does not look like a GUCO number. It looks like GUCO-00012.', 'error');
        } elseif ($number === '' && mb_strlen(implode('', $tokens)) < 3) {
            flash('Enter at least three letters of your name.', 'error');
        } else {
            $sql = "SELECT * FROM legacy_members WHERE status <> 'withdrawn'";
            $args = [];
            if ($no) {
                $sql .= ' AND guco_no = ?';
                $args[] = $no;
            }
            foreach ($tokens as $t) {
                $sql .= ' AND full_name LIKE ?';
                $args[] = '%' . addcslashes($t, '%_\\') . '%';
            }
            $matches = q($sql . ' ORDER BY full_name, guco_no LIMIT 11', $args)->fetchAll();
            if (count($matches) > 10) {
                $tooMany = true;
                $matches = [];
            }
            if (!$matches) {
                q('INSERT INTO login_attempts (ip, email) VALUES (?,?)', [$ip, 'claim:' . ($no ?: 'name')]);
            }
            $_SESSION['claim_options'] = array_map(fn($r) => (int) $r['id'], $matches);
        }
    }
}
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Existing member? Find your GUCO number</h1><p>Confirm your registration, renew your subscription and open your Member Portal</p></div>

<?php if ($found): ?>
  <div class="card accent" style="max-width:760px;margin-bottom:24px">
    <h2 style="margin-top:0"><?= e(legacy_display_name($found)) ?></h2>
    <p class="meta">GUCO No. <?= e($found['guco_no']) ?> &middot; <?= e(LEGACY_DESIGNATIONS[$found['designation']] ?? '') ?></p>
    <?php if ($found['status'] === 'claimed'): ?>
      <p>This number already has a Member Portal account. <a href="<?= url('member/login.php') ?>">Log in</a>, or <a href="<?= url('forgot.php') ?>">reset your password</a> if you have forgotten it.</p>
    <?php else: ?>
      <p>The Secretariat needs to confirm this record before it can be used (for example because the number appears more than once on the register). Please <a href="<?= url('contact.php?category=Membership') ?>">contact the Secretariat</a> and quote your GUCO number.</p>
    <?php endif; ?>
  </div>
<?php elseif ($matches !== null): ?>
  <?php if ($matches): ?>
    <section style="max-width:760px;margin-bottom:24px">
      <h2 style="margin-top:0"><?= count($matches) === 1 ? 'We found a record' : 'We found ' . count($matches) . ' records' ?></h2>
      <p class="meta">Choose the one that is you.</p>
      <?php foreach ($matches as $m): ?>
        <form method="post" class="card" style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:10px">
          <?= csrf_field() ?><?= spam_fields() ?><input type="hidden" name="action" value="pick"><input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
          <div style="flex:1;min-width:220px"><strong><?= e(legacy_display_name($m)) ?></strong><br>
            <span class="meta"><?= e($m['guco_no']) ?> &middot; <?= e(LEGACY_DESIGNATIONS[$m['designation']] ?? '') ?><?= $m['status'] === 'claimed' ? ' &middot; already has a portal account' : '' ?></span></div>
          <button>This is me</button>
        </form>
      <?php endforeach; ?>
      <p class="meta">Not listed? Try the number or another spelling, or <a href="<?= url('contact.php?category=Membership') ?>">contact the Secretariat</a>.</p>
    </section>
  <?php else: ?>
    <p class="notice"><strong><?= $tooMany ? 'Too many records match.' : 'We could not find a record.' ?></strong>
      <?= $tooMany ? 'Add more of your name, or your GUCO number.' : 'Check the number and the spelling of your name as on the register. If you think the register is wrong or your number is missing,' ?>
      <?= $tooMany ? '' : '<a href="' . e(url('contact.php?category=Membership')) . '">contact the Secretariat</a>.' ?></p>
  <?php endif; ?>
<?php endif; ?>

<?php if ($already): ?>
  <p class="notice">You are already logged in as <?= e($already['full_name']) ?>. To link a GUCO number to this account, ask the Secretariat through <a href="<?= url('member/support.php') ?>">Support</a>.</p>
<?php else: ?>
<ol class="steps">
  <li><strong>Find</strong> Search with your GUCO number or your name.</li>
  <li><strong>Confirm</strong> Check your details and complete the registration form.</li>
  <li><strong>Renew</strong> Pay your subscription now, or choose to pay later.</li>
  <li><strong>Portal</strong> Log in to track everything about your membership.</li>
</ol>
<form method="post" class="stack" style="max-width:560px">
  <?= csrf_field() ?><?= spam_fields() ?><input type="hidden" name="action" value="search">
  <fieldset><legend>Find my record</legend>
    <p class="meta" style="margin-top:0">Use your GUCO number, your name, or both.</p>
    <label>GUCO number <span class="hint">(for example GUCO-00012)</span>
      <input name="guco_no" autocomplete="off" placeholder="GUCO-00012" value="<?= e($number) ?>"></label>
    <label>Your name <span class="hint">(your surname or first name, as on the register)</span>
      <input name="name" autocomplete="off" value="<?= e($name) ?>"></label>
  </fieldset>
  <button>Find my record</button>
  <p class="meta">Not a member yet? <a href="<?= url('register.php') ?>">Join GUCO</a>. Already have a portal account? <a href="<?= url('member/login.php') ?>">Log in</a>.</p>
</form>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
