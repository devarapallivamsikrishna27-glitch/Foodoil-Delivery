<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
$errors = [];
if (is_post()) {
    csrf_require();
    $cur  = (string)($_POST['current'] ?? '');
    $new  = (string)($_POST['new'] ?? '');
    $new2 = (string)($_POST['new2'] ?? '');
    $s = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $s->execute([$user['id']]);
    $hash = (string)$s->fetchColumn();
    if (!password_verify($cur, $hash)) { $errors['current'] = 'Your current password is incorrect.'; }
    if (strlen($new) < 8 || strlen($new) > 72 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) { $errors['new'] = 'Use 8 to 72 characters with letters and numbers.'; }
    if ($new !== $new2) { $errors['new2'] = 'Passwords do not match.'; }
    if (!$errors) {
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        session_regenerate_id(true);
        flash('success', 'Your password has been changed.');
        redirect('change-password.php');
    }
}
$fe = fn($k) => isset($errors[$k]) ? '<small class="field-error show">' . e($errors[$k]) . '</small>' : '';
$pageTitle = 'Change Password';
$activeNav = '';
include __DIR__ . '/includes/header.php';
?>
<section class="section">
  <div class="container narrow">
    <div class="page-head"><h1>Change Password</h1></div>
    <form class="card" method="post" action="<?= e(url('change-password.php')) ?>" novalidate>
      <?= csrf_field() ?>
      <div class="field"><label for="current">Current password</label><input type="password" id="current" name="current" autocomplete="current-password" required><?= $fe('current') ?></div>
      <div class="field"><label for="new">New password</label><input type="password" id="new" name="new" autocomplete="new-password" minlength="8" required><?= $fe('new') ?></div>
      <div class="field"><label for="new2">Confirm new password</label><input type="password" id="new2" name="new2" autocomplete="new-password" required><?= $fe('new2') ?></div>
      <button type="submit" class="btn btn-primary">Save Password</button>
    </form>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
