</main>
<?php
$ftName = setting('site_name', 'Gulu University Convocation');
$ftSocials = ['social_facebook' => 'Facebook', 'social_x' => 'X', 'social_linkedin' => 'LinkedIn', 'social_youtube' => 'YouTube'];
?>
<footer class="ft">
  <div class="container ft-news-wrap">
    <section class="ft-news" aria-label="Newsletter">
      <div class="ft-news-text">
        <span class="ft-ic"><?= icon('mail', 26) ?></span>
        <div><strong>Stay updated with GUCO</strong><span>Subscribe to the newsletter for verified news, events and notices.</span></div>
      </div>
      <form method="post" action="<?= url('subscribe.php') ?>" class="ft-news-form">
        <?= csrf_field() ?><?= spam_fields() ?>
        <label class="sr-only" for="nb-email">Email address</label>
        <input id="nb-email" type="email" name="email" required autocomplete="email" placeholder="Enter your email address">
        <button>Subscribe</button>
      </form>
    </section>
  </div>

  <div class="ft-bg">
    <div class="container ft-grid">
      <div class="ft-brand">
        <a class="ft-logo" href="<?= url() ?>"><img src="<?= e(logo_url()) ?>" alt="" width="52" height="52"><strong><?= e($ftName) ?></strong></a>
        <p class="ft-tag"><?= e(setting('site_tagline', 'Community Transformation Actualized')) ?></p>
        <div class="ft-about"><?= cr('footer.about') ?></div>
        <div class="ft-actions">
          <a class="btn gold" href="<?= url('register.php') ?>">Join GUCO</a>
          <a class="btn outline" href="<?= url('donate.php') ?>"><?= icon('heart', 16) ?> Give</a>
        </div>
        <?php $ftHasSocial = array_filter(array_map('setting', array_keys($ftSocials))); if ($ftHasSocial): ?>
          <div class="ft-social">
            <?php foreach ($ftSocials as $k => $label): if (setting($k)): ?><a href="<?= e(setting($k)) ?>" rel="noopener" target="_blank"><?= $label ?></a><?php endif; endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <nav class="ft-col" aria-label="Explore GUCO">
        <h2>Explore</h2>
        <ul>
          <li><a href="<?= url('about.php') ?>">About GUCO</a></li>
          <li><a href="<?= url('leadership.php') ?>">Leadership &amp; Governance</a></li>
          <li><a href="<?= url('programmes.php') ?>">Programmes</a></li>
          <li><a href="<?= url('chapters.php') ?>">Alumni Chapters</a></li>
          <li><a href="<?= url('awards.php') ?>">Awards &amp; Hall of Fame</a></li>
          <li><a href="<?= url('partnerships.php') ?>">Partnerships</a></li>
        </ul>
      </nav>

      <nav class="ft-col" aria-label="Membership and information">
        <h2>Members &amp; updates</h2>
        <ul>
          <li><a href="<?= url('membership.php') ?>">Categories &amp; benefits</a></li>
          <li><a href="<?= url('member/') ?>">Member Portal</a></li>
          <li><a href="<?= url('news.php') ?>">News</a></li>
          <li><a href="<?= url('events.php') ?>">Events</a></li>
          <li><a href="<?= url('resources.php') ?>">Resources</a></li>
          <li><a href="<?= url('careers.php') ?>">Careers</a></li>
        </ul>
      </nav>

      <div class="ft-col ft-contact">
        <h2>Contact us</h2>
        <ul>
          <li><span class="ft-ci"><?= icon('map-pin', 18) ?></span><span><?= ct('footer.address') ?></span></li>
          <?php if (setting('contact_email')): ?><li><span class="ft-ci"><?= icon('mail', 18) ?></span><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></li><?php endif; ?>
          <?php if (setting('contact_phone')): ?><li><span class="ft-ci"><?= icon('phone', 18) ?></span><span><?= e(setting('contact_phone')) ?></span></li><?php endif; ?>
          <?php if (setting('office_hours')): ?><li><span class="ft-ci"><?= icon('clock', 18) ?></span><span><?= e(setting('office_hours')) ?></span></li><?php endif; ?>
        </ul>
        <a class="ft-msg" href="<?= url('contact.php') ?>">Send us a message <?= icon('arrow-right', 16) ?></a>
      </div>
    </div>

    <div class="container">
      <ul class="ft-trust">
        <li><span class="ft-ci"><?= icon('shield', 20) ?></span><div><strong>Verified membership</strong><span>Every GUCO card carries a unique ID you can <a href="<?= url('verify.php') ?>">check online</a>.</span></div></li>
        <li><span class="ft-ci"><?= icon('file', 20) ?></span><div><strong>Official documents</strong><span>The Constitution and reports are in <a href="<?= url('resources.php') ?>">Resources</a>.</span></div></li>
        <li><span class="ft-ci"><?= icon('lock', 20) ?></span><div><strong>Private member portal</strong><span>Your profile, card and payments, <a href="<?= url('member/login.php') ?>">after you log in</a>.</span></div></li>
      </ul>
    </div>

    <div class="ft-bottom">
      <div class="container ft-bottom-in">
        <span>&copy; <?= date('Y') ?> <?= e($ftName) ?>. All rights reserved.</span>
        <span class="ft-legal"><a href="<?= url('privacy.php') ?>">Privacy Notice</a><a href="<?= url('accessibility.php') ?>">Accessibility</a><a href="<?= url('contact.php') ?>">Contact</a></span>
      </div>
    </div>
  </div>
</footer>
<button type="button" class="to-top" id="toTop" aria-label="Back to top" onclick="window.scrollTo({top:0,behavior:'smooth'})"><?= icon('arrow-right', 20) ?></button>
<script src="<?= asset('assets/js/countdown.js') ?>"></script>
<script src="<?= asset('assets/js/site.js') ?>" defer></script>
<script>
(function () {
  var b = document.getElementById('toTop');
  if (!b) return;
  b.firstElementChild.style.transform = 'rotate(-90deg)';
  window.addEventListener('scroll', function () { b.classList.toggle('show', window.scrollY > 600); }, { passive: true });
})();
</script>
</body>
</html>
