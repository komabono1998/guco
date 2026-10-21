</main>
<footer class="site-footer">
  <div class="container footgrid">
    <div>
      <h2>Gulu University Convocation (GUCO)</h2>
      <p class="tag">Community Transformation Actualized</p>
      <p>An alumni association committed to mobilising alumni, supporting Gulu University and contributing to community transformation.</p>
      <p>Gulu University, P.O. Box 166, Laroo Road, Gulu City, Uganda.</p>
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
        <li><a href="<?= url('membership.php') ?>">Membership</a></li>
        <li><a href="<?= url('leadership.php') ?>">Leadership &amp; Governance</a></li>
        <li><a href="<?= url('chapters.php') ?>">Alumni Chapters</a></li>
        <li><a href="<?= url('news.php') ?>">News &amp; Events</a></li>
        <li><a href="<?= url('resources.php') ?>">Resources</a></li>
        <li><a href="<?= url('contact.php') ?>">Contact</a></li>
        <li><a href="<?= url('privacy.php') ?>">Privacy Notice</a></li>
        <li><a href="<?= url('accessibility.php') ?>">Accessibility</a></li>
      </ul>
    </div>
    <div>
      <h2>Newsletter</h2>
      <form method="post" action="<?= url('subscribe.php') ?>" class="stack">
        <?= csrf_field() ?><?= spam_fields() ?>
        <label>Email address <input type="email" name="email" required autocomplete="email"></label>
        <button>Subscribe</button>
      </form>
    </div>
  </div>
  <div class="container copy">&copy; <?= date('Y') ?> Gulu University Convocation. All rights reserved.</div>
</footer>
</body>
</html>
