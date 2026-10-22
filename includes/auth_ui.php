<?php
/** Shared two-column layout for login, password reset and similar pages. */
function auth_open(string $title, string $sub = ''): void
{
    ?>
<div class="auth">
  <aside class="auth-side">
    <img src="<?= e(logo_url()) ?>" alt="" width="84" height="84">
    <h2><?= e(setting('site_name', 'Gulu University Convocation')) ?></h2>
    <p><?= e(setting('site_tagline', 'Community Transformation Actualized')) ?></p>
    <ul>
      <li><?= icon('id-card', 20) ?> Your membership card and ID</li>
      <li><?= icon('map-pin', 20) ?> Your chapter and its activities</li>
      <li><?= icon('calendar', 20) ?> Events, registration and payments</li>
      <li><?= icon('target', 20) ?> Propose and lead community projects</li>
    </ul>
  </aside>
  <div class="auth-main">
    <h1><?= e($title) ?></h1>
    <?php if ($sub): ?><p class="sub"><?= e($sub) ?></p><?php endif; ?>
<?php
}

function auth_close(): void
{
    echo "  </div>\n</div>\n";
}
