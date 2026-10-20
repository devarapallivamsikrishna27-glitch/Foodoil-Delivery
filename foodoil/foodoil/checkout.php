<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
$pageTitle = 'Checkout';
$activeNav = '';
$extraScripts = ['checkout.js'];

/* Pre-fill from the customer's most recent order, if any */
$s = db()->prepare('SELECT customer_name, phone, address, city, state, pincode FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 1');
$s->execute([$user['id']]);
$last = $s->fetch() ?: [];
$v = [
    'name'    => $last['customer_name'] ?? $user['name'],
    'phone'   => $last['phone'] ?? ($user['phone'] ?? ''),
    'address' => $last['address'] ?? '',
    'city'    => $last['city'] ?? '',
    'state'   => $last['state'] ?? '',
    'pincode' => $last['pincode'] ?? '',
];
include __DIR__ . '/includes/header.php';
?>
<section class="section">
  <div class="container">
    <div class="page-head"><h1>Checkout</h1></div>
    <div id="checkout-empty" hidden>
      <div class="empty-state">
        <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
        <h3>Your cart is empty</h3>
        <p>Add some oils to your cart before checking out.</p>
        <a class="btn btn-primary" href="<?= e(url('products.php')) ?>">Browse Products</a>
      </div>
    </div>

    <form id="checkout-form" class="checkout-grid" novalidate>
      <div class="checkout-main">
        <div id="checkout-alert" role="alert"></div>

        <fieldset class="card">
          <legend class="card-title">1. Delivery Address</legend>
          <div class="form-grid">
            <div class="field span-2"><label for="name">Full name</label>
              <input type="text" id="name" name="name" value="<?= e($v['name']) ?>" autocomplete="name" maxlength="100" required>
              <small class="field-error" data-error-for="name"></small></div>
            <div class="field"><label for="phone">Phone number</label>
              <input type="tel" id="phone" name="phone" value="<?= e($v['phone']) ?>" autocomplete="tel-national" inputmode="numeric" maxlength="10" placeholder="10-digit mobile number" required>
              <small class="field-error" data-error-for="phone"></small></div>
            <div class="field"><label for="pincode">PIN code</label>
              <input type="text" id="pincode" name="pincode" value="<?= e($v['pincode']) ?>" autocomplete="postal-code" inputmode="numeric" maxlength="6" placeholder="6-digit PIN" required>
              <small class="field-error" data-error-for="pincode"></small></div>
            <div class="field span-2"><label for="address">Full delivery address</label>
              <textarea id="address" name="address" rows="3" maxlength="255" autocomplete="street-address" placeholder="House / flat no., street, area, landmark" required><?= e($v['address']) ?></textarea>
              <small class="field-error" data-error-for="address"></small></div>
            <div class="field"><label for="city">City</label>
              <input type="text" id="city" name="city" value="<?= e($v['city']) ?>" autocomplete="address-level2" maxlength="60" required>
              <small class="field-error" data-error-for="city"></small></div>
            <div class="field"><label for="state">State</label>
              <select id="state" name="state" autocomplete="address-level1" required>
                <option value="">Select state</option>
                <?php foreach (INDIAN_STATES as $st): ?>
                  <option value="<?= e($st) ?>" <?= $v['state'] === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                <?php endforeach; ?>
              </select>
              <small class="field-error" data-error-for="state"></small></div>
          </div>
        </fieldset>

        <fieldset class="card">
          <legend class="card-title">2. Payment Method</legend>
          <div class="pay-list" role="radiogroup" aria-label="Payment method">
            <label class="pay-option selected">
              <input type="radio" name="payment" value="COD" checked>
              <span class="pay-radio" aria-hidden="true"></span>
              <span class="pay-label"><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i> Cash on Delivery</span>
            </label>
            <label class="pay-option">
              <input type="radio" name="payment" value="UPI">
              <span class="pay-radio" aria-hidden="true"></span>
              <span class="pay-label"><i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i> UPI (PhonePe / GPay) <em class="demo-tag">Demo</em></span>
              <span class="pay-icons" aria-hidden="true"><span class="pay-dot pay-dot-purple">P</span><span class="pay-dot pay-dot-blue">G</span></span>
            </label>
            <label class="pay-option">
              <input type="radio" name="payment" value="CARD">
              <span class="pay-radio" aria-hidden="true"></span>
              <span class="pay-label"><i class="fa-regular fa-credit-card" aria-hidden="true"></i> Credit / Debit Card <em class="demo-tag">Demo</em></span>
            </label>
          </div>
          <p class="pay-note" id="pay-note" hidden><i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            This is a demo option. No online payment is processed and no card or UPI details are collected. Your order is placed and you pay when it is delivered.</p>
        </fieldset>
      </div>

      <aside class="checkout-side">
        <div class="card summary-card">
          <h2 class="card-title">Order Summary</h2>
          <div id="summary-body" aria-live="polite"><div class="loading"><span class="spinner" aria-hidden="true"></span> Loading...</div></div>
          <button type="submit" class="btn btn-primary btn-block btn-lg" id="place-order" disabled>Place Order</button>
          <p class="muted small center mt-sm"><i class="fa-solid fa-lock" aria-hidden="true"></i> Totals are re-checked on our server when you place the order.</p>
        </div>
      </aside>
    </form>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
