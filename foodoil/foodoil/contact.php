<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Contact Us';
$activeNav = 'contact';
$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => '', 'message' => ''];
$u = current_user();
if ($u) { $old['name'] = $u['name']; $old['email'] = $u['email']; $old['phone'] = (string)($u['phone'] ?? ''); }

if (is_post()) {
    csrf_require();
    $old = ['name' => post('name'), 'email' => strtolower(post('email')), 'phone' => preg_replace('/\D/', '', post('phone')), 'message' => post('message')];
    if (mb_strlen($old['name']) < 2 || mb_strlen($old['name']) > 100) { $errors['name'] = 'Enter your name.'; }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Enter a valid email address.'; }
    if ($old['phone'] !== '' && !preg_match('/^[6-9]\d{9}$/', $old['phone'])) { $errors['phone'] = 'Enter a 10-digit mobile number or leave it empty.'; }
    if (mb_strlen($old['message']) < 10 || mb_strlen($old['message']) > 1000) { $errors['message'] = 'Write a message between 10 and 1000 characters.'; }
    if (!$errors) {
        db()->prepare('INSERT INTO contact_messages (name, email, phone, message) VALUES (?, ?, ?, ?)')
            ->execute([$old['name'], $old['email'], $old['phone'] !== '' ? $old['phone'] : null, $old['message']]);
        flash('success', 'Thank you! Your message has been sent. We will get back to you soon.');
        redirect('contact.php');
    }
}
$fe = fn($k) => isset($errors[$k]) ? '<small class="field-error show">' . e($errors[$k]) . '</small>' : '';
include __DIR__ . '/includes/header.php';
?>
<section class="page-banner"><div class="container"><h1>Contact Us</h1><p>Questions about an order or a product? We're happy to help.</p></div></section>
<section class="section">
  <div class="container two-col">
    <div class="card">
      <h2 class="card-title">Send us a message</h2>
      <form method="post" action="<?= e(url('contact.php')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="field"><label for="name">Your name</label><input type="text" id="name" name="name" value="<?= e($old['name']) ?>" maxlength="100" required><?= $fe('name') ?></div>
        <div class="field"><label for="email">Email</label><input type="email" id="email" name="email" value="<?= e($old['email']) ?>" maxlength="150" required><?= $fe('email') ?></div>
        <div class="field"><label for="phone">Phone (optional)</label><input type="tel" id="phone" name="phone" value="<?= e($old['phone']) ?>" maxlength="10" inputmode="numeric"><?= $fe('phone') ?></div>
        <div class="field"><label for="message">Message</label><textarea id="message" name="message" rows="5" maxlength="1000" required><?= e($old['message']) ?></textarea><?= $fe('message') ?></div>
        <button type="submit" class="btn btn-primary">Send Message</button>
      </form>
    </div>
    <div class="card">
      <h2 class="card-title">Store details</h2>
      <ul class="contact-list">
        <li><i class="fa-solid fa-location-dot" aria-hidden="true"></i><span>12 Market Road, Sample City, India</span></li>
        <li><i class="fa-solid fa-phone" aria-hidden="true"></i><span>+91 90000 00000</span></li>
        <li><i class="fa-solid fa-envelope" aria-hidden="true"></i><span>support@foodoil.example</span></li>
        <li><i class="fa-regular fa-clock" aria-hidden="true"></i><span>Monday to Saturday, 9 AM - 7 PM</span></li>
      </ul>
      <p class="muted small">The address, phone number and email above are placeholders for this demo project. Messages sent through this form are saved in the database and shown in the admin panel.</p>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
