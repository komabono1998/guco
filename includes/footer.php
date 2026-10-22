</main>
<section class="newsletter-band" aria-label="Newsletter">
  <div class="container nb">
    <div class="nb-text">
      <strong>Stay updated with GUCO</strong>
      <span>Subscribe to the newsletter for verified news, events and notices.</span>
    </div>
    <form method="post" action="<?= url('subscribe.php') ?>" class="nb-form">
      <?= csrf_field() ?><?= spam_fields() ?>
      <label class="sr-only" for="nb-email">Email address</label>
      <input id="nb-email" type="email" name="email" required autocomplete="email" placeholder="Enter your email address">
      <button>Subscribe</button>
    </form>
    <div class="nb-note"><strong>Verified membership</strong><span>Every GUCO card carries a unique ID you can check online.</span></div>
  </div>
</section>
<footer class="site-footer">
  <div class="container footgrid">
    <div>
      <div class="foot-brand"><img src="<?= e(logo_url()) ?>" alt="" width="48" height="48"><strong><?= e(setting('site_name', 'Gulu University Convocation')) ?></strong></div>
      <p class="tag"><?= e(setting('site_tagline', 'Community Transformation Actualized')) ?></p>
      <?= cr('footer.about') ?>
      <?php
      $socials = ['social_facebook' => 'Facebook', 'social_x' => 'X', 'social_linkedin' => 'LinkedIn', 'social_youtube' => 'YouTube'];
      foreach ($socials as $k => $label) {
          if (setting($k)) {
              echo '<a class="social" href="' . e(setting($k)) . '" rel="noopener" target="_blank">' . $label . '</a> ';
          }
      }
      ?>
    </div>
    <div>
      <h2>Quick links</h2>
      <ul class="plain">
        <li><a href="<?= url('about.php') ?>">About GUCO</a></li>
        <li><a href="<?= url('leadership.php') ?>">Leadership &amp; Governance</a></li>
        <li><a href="<?= url('programmes.php') ?>">Programmes</a></li>
        <li><a href="<?= url('chapters.php') ?>">Alumni Chapters</a></li>
        <li><a href="<?= url('awards.php') ?>">Awards</a></li>
        <li><a href="<?= url('partnerships.php') ?>">Partnerships</a></li>
      </ul>
    </div>
    <div>
      <h2>Membership</h2>
      <ul class="plain">
        <li><a href="<?= url('membership.php') ?>">Categories &amp; benefits</a></li>
        <li><a href="<?= url('register.php') ?>">Join GUCO</a></li>
        <li><a href="<?= url('member/') ?>">Member Portal</a></li>
        <li><a href="<?= url('verify.php') ?>">Verify a card</a></li>
        <li><a href="<?= url('news.php') ?>">News</a></li>
        <li><a href="<?= url('events.php') ?>">Events</a></li>
        <li><a href="<?= url('resources.php') ?>">Resources</a></li>
      </ul>
    </div>
    <div>
      <h2>Contact us</h2>
      <ul class="plain contact-list">
        <li><?= ct('footer.address') ?></li>
        <?php if (setting('contact_email')): ?><li><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></li><?php endif; ?>
        <?php if (setting('contact_phone')): ?><li><?= e(setting('contact_phone')) ?></li><?php endif; ?>
        <?php if (setting('office_hours')): ?><li><?= e(setting('office_hours')) ?></li><?php endif; ?>
        <li><a href="<?= url('contact.php') ?>">Send a message</a></li>
      </ul>
    </div>
  </div>
  <div class="container copy">
    <span>&copy; <?= date('Y') ?> <?= e(setting('site_name', 'Gulu University Convocation')) ?>. All rights reserved.</span>
    <span><a href="<?= url('privacy.php') ?>">Privacy Notice</a> &middot; <a href="<?= url('accessibility.php') ?>">Accessibility</a></span>
  </div>
</footer>
<button type="button" class="to-top" id="toTop" aria-label="Back to top" onclick="window.scrollTo({top:0,behavior:'smooth'})"><?= icon('arrow-right', 20) ?></button>
<script src="<?= url('assets/js/countdown.js') ?>"></script>
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
