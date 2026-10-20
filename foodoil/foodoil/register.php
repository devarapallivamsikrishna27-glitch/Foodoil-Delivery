<?php
require_once __DIR__ . '/includes/functions.php';
if (current_user()) { redirect('index.php'); }

$next = safe_next($_GET['next'] ?? ($_POST['next'] ?? ''));
$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => ''];

if (is_post()) {
    csrf_require();
    $old['name']  = post('name');
    $old['email'] = strtolower(post('email'));
    $old['phone'] = preg_replace('/\D/', '', post('phone'));
    $pass  = (string)($_POST['password'] ?? '');
    $pass2 = (string)($_POST['password2'] ?? '');

    if (mb_strlen($old['name']) < 2 || mb_strlen($old['name']) > 100) { $errors['name'] = 'Enter your full name (2 to 100 characters).'; }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 150) { $errors['email'] = 'Enter a valid email address.'; }
    if (!preg_match('/^[6-9]\d{9}$/', $old['phone'])) { $errors['phone'] = 'Enter a valid 10-digit Indian mobile number.'; }
    if (strlen($pass) < 8 || !preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass)) { $errors['password'] = 'Use at least 8 characters with letters and numbers.'; }
    elseif (strlen($pass) > 72) { $errors['password'] = 'Password can be at most 72 characters.'; }
    if ($pass !== $pass2) { $errors['password2'] = 'Passwords do not match.'; }

    if (!$errors) {
        $c = db()->prepare('SELECT email, phone FROM users WHERE email = ? OR phone = ?');
        $c->execute([$old['email'], $old['phone']]);
        foreach ($c->fetchAll() as $row) {
            if ($row['email'] === $old['email']) { $errors['email'] = 'An account with this email already exists.'; }
            if ($row['phone'] === $old['phone']) { $errors['phone'] = 'An account with this phone number already exists.'; }
        }
    }
    if (!$errors) {
        $ins = db()->prepare("INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'customer')");
        $ins->execute([$old['name'], $old['email'], $old['phone'], password_hash($pass, PASSWORD_DEFAULT)]);
        login_user((int)db()->lastInsertId());
        flash('success', 'Your account has been created. Welcome to FoodOil!');
        redirect($next !== '' ? $next : 'index.php');
    }
}
$pageTitle = 'Create Account';
$noChrome  = true;
$bodyClass = 'auth-page';
$fe = fn($k) => isset($errors[$k]) ? '<small class="field-error show">' . e($errors[$k]) . '</small>' : '';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-split">
  <div class="auth-art" style="background-image:url('<?= e(url('assets/images/login-art.svg')) ?>')">
    <div class="auth-art-copy"><h2>Good Food<br>Better Life</h2><p>Join FoodOil and get fresh oils delivered at your doorstep.</p></div>
  </div>
  <div class="auth-form-side">
    <div class="auth-card">
      <a class="auth-back" href="<?= e(url('index.php')) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to store</a>
      <h1>Create your account</h1>
      <p class="muted">It takes less than a minute.</p>
      <form method="post" action="<?= e(url('register.php')) ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <div class="field"><label for="name">Full name</label><input type="text" id="name" name="name" value="<?= e($old['name']) ?>" autocomplete="name" maxlength="100" required><?= $fe('name') ?></div>
        <div class="field"><label for="email">Email</label><input type="email" id="email" name="email" value="<?= e($old['email']) ?>" autocomplete="email" maxlength="150" placeholder="you@example.com" required><?= $fe('email') ?></div>
        <div class="field"><label for="phone">Mobile number</label><input type="tel" id="phone" name="phone" value="<?= e($old['phone']) ?>" autocomplete="tel-national" inputmode="numeric" maxlength="10" placeholder="10-digit number" required><?= $fe('phone') ?></div>
        <div class="field"><label for="password">Password</label>
          <div class="pw-wrap"><input type="password" id="password" name="password" autocomplete="new-password" minlength="8" required>
            <button type="button" class="pw-toggle" data-toggle-password="#password" aria-label="Show password"><i class="fa-regular fa-eye"></i></button></div>
          <small class="muted">At least 8 characters, with letters and numbers.</small><?= $fe('password') ?></div>
        <div class="field"><label for="password2">Confirm password</label><input type="password" id="password2" name="password2" autocomplete="new-password" required><?= $fe('password2') ?></div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">Create Account</button>
      </form>
      <p class="auth-switch">Already have an account? <a href="<?= e(url('login.php' . ($next ? '?next=' . urlencode($next) : ''))) ?>">Login</a></p>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
