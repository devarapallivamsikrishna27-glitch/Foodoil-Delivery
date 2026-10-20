<?php
require_once __DIR__ . '/includes/functions.php';
if (current_user()) { redirect(is_admin() ? 'admin/dashboard.php' : 'my-orders.php'); }

$next   = safe_next($_GET['next'] ?? ($_POST['next'] ?? ''));
$errors = [];
$ident  = '';

if (is_post()) {
    csrf_require();
    $ident = post('identifier');
    $pass  = (string)($_POST['password'] ?? '');

    if (!empty($_SESSION['lock_until']) && $_SESSION['lock_until'] > time()) {
        $errors[] = 'Too many failed attempts. Please wait a few minutes and try again.';
    } elseif ($ident === '' || $pass === '') {
        $errors[] = 'Enter your email or phone number and your password.';
    } else {
        $isEmail = filter_var($ident, FILTER_VALIDATE_EMAIL) !== false;
        $col     = $isEmail ? 'email' : 'phone';
        $val     = $isEmail ? strtolower($ident) : preg_replace('/\D/', '', $ident);
        $s = db()->prepare("SELECT id, password_hash, role FROM users WHERE $col = ? LIMIT 1");
        $s->execute([$val]);
        $row  = $s->fetch();
        // Always run password_verify so response time does not reveal whether the account exists
        $hash = $row['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        $ok   = password_verify($pass, $hash);
        if ($row && $ok) {
            if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($pass, PASSWORD_DEFAULT), $row['id']]);
            }
            login_user((int)$row['id']);
            flash('success', 'Welcome back! You are now logged in.');
            if ($row['role'] === 'admin' && $next === '') { redirect('admin/dashboard.php'); }
            redirect($next !== '' ? $next : 'index.php');
        }
        $_SESSION['login_fail'] = ($_SESSION['login_fail'] ?? 0) + 1;
        if ($_SESSION['login_fail'] >= 5) { $_SESSION['lock_until'] = time() + 300; $_SESSION['login_fail'] = 0; }
        $errors[] = 'Incorrect email/phone or password.';
    }
}
$pageTitle = 'Login';
$noChrome  = true;
$bodyClass = 'auth-page';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-split">
  <div class="auth-art" style="background-image:url('<?= e(url('assets/images/login-art.svg')) ?>')">
    <div class="auth-art-copy">
      <h2>Good Food<br>Better Life</h2>
      <p>Join FoodOil and get fresh oils delivered at your doorstep.</p>
    </div>
  </div>
  <div class="auth-form-side">
    <div class="auth-card">
      <a class="auth-back" href="<?= e(url('index.php')) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to store</a>
      <h1>Login to FoodOil</h1>
      <p class="muted">Enter your credentials to continue.</p>
      <?php render_flash(); ?>
      <?php foreach ($errors as $er): ?><div class="alert alert-error" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($er) ?></span></div><?php endforeach; ?>
      <form method="post" action="<?= e(url('login.php')) ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <div class="field"><label for="identifier">Email or phone number</label>
          <input type="text" id="identifier" name="identifier" value="<?= e($ident) ?>" placeholder="you@example.com" autocomplete="username" required autofocus></div>
        <div class="field"><label for="password">Password</label>
          <div class="pw-wrap"><input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
            <button type="button" class="pw-toggle" data-toggle-password="#password" aria-label="Show password"><i class="fa-regular fa-eye"></i></button></div>
          <a class="forgot-link" href="<?= e(url('forgot-password.php')) ?>">Forgot Password?</a></div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">Login</button>
      </form>
      <p class="auth-switch">Don't have an account? <a href="<?= e(url('register.php' . ($next ? '?next=' . urlencode($next) : ''))) ?>">Sign Up</a></p>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
