<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Join GUCO';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass = $_POST['password'] ?? '';
    $year = (int) ($_POST['graduation_year'] ?? 0);
    $error = null;
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter your name and a valid email.';
    } elseif (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($year < 1990 || $year > (int) date('Y') + 5) {
        $error = 'Enter a valid graduation year.';
    } else {
        $s = db()->prepare('SELECT 1 FROM users WHERE email = ?');
        $s->execute([$email]);
        if ($s->fetch()) {
            $error = 'That email is already registered.';
        }
    }
    if ($error) {
        flash($error, 'error');
    } else {
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO users (full_name, email, password_hash) VALUES (?,?,?)')
            ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
        $pdo->prepare('INSERT INTO member_profiles (user_id, phone, faculty, course, graduation_year) VALUES (?,?,?,?,?)')
            ->execute([$pdo->lastInsertId(), trim($_POST['phone'] ?? ''), trim($_POST['faculty'] ?? ''), trim($_POST['course'] ?? ''), $year]);
        $pdo->commit();
        flash('Registration received. An admin will approve your membership shortly; you can log in once approved.');
        redirect('login.php');
    }
}
require __DIR__ . '/includes/header.php';
?>
<h1>Join the alumni network</h1>
<form method="post" class="stack">
  <?= csrf_field() ?>
  <label>Full name <input name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>"></label>
  <label>Email <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
  <label>Password (min 8 characters) <input type="password" name="password" required minlength="8"></label>
  <label>Phone <input name="phone" value="<?= e($_POST['phone'] ?? '') ?>"></label>
  <label>Faculty <input name="faculty" value="<?= e($_POST['faculty'] ?? '') ?>"></label>
  <label>Course <input name="course" value="<?= e($_POST['course'] ?? '') ?>"></label>
  <label>Graduation year <input type="number" name="graduation_year" required value="<?= e($_POST['graduation_year'] ?? '') ?>"></label>
  <button>Register</button>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
