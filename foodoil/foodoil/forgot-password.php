<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Forgot Password';
$noChrome  = true;
$bodyClass = 'auth-page';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-split">
  <div class="auth-art" style="background-image:url('<?= e(url('assets/images/login-art.svg')) ?>')">
    <div class="auth-art-copy"><h2>Good Food<br>Better Life</h2><p>Join FoodOil and get fresh oils delivered at your doorstep.</p></div>
  </div>
  <div class="auth-form-side">
    <div class="auth-card">
      <a class="auth-back" href="<?= e(url('login.php')) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to login</a>
      <h1>Forgot your password?</h1>
      <p class="muted">Password reset emails need an email service (SMTP), which is not configured in this demo project.</p>
      <div class="alert alert-info"><i class="fa-solid fa-circle-info" aria-hidden="true"></i>
        <span>Ask the store administrator to reset your password. They will give you a temporary password that you can use to log in.</span></div>
      <p>Admin contact: <b>support@foodoil.example</b></p>
      <p class="muted small">Developers: connect PHPMailer (or your host's mail service) and add a token-based reset flow to enable emailed reset links.</p>
      <a class="btn btn-primary btn-block" href="<?= e(url('login.php')) ?>">Return to Login</a>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
