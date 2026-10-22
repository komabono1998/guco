<?php
require_once __DIR__ . '/includes/bootstrap.php';
http_response_code(404);
$pageTitle = 'Page not found';
require __DIR__ . '/includes/header.php';
?>
<section class="notfound">
  <div class="big">404</div>
  <h1>We could not find that page</h1>
  <p>The page may have moved or the address may be wrong. Try one of these instead.</p>
  <p><a class="btn" href="<?= url() ?>">Go to the home page</a> <a class="btn outline" href="<?= url('news.php') ?>">Latest news</a> <a class="btn outline" href="<?= url('contact.php') ?>">Contact us</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
