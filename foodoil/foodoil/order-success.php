<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
$code = (string)($_GET['code'] ?? '');
$s = db()->prepare('SELECT * FROM orders WHERE order_code = ? AND user_id = ?');
$s->execute([$code, $user['id']]);
$order = $s->fetch();
if (!$order) {
    flash('error', 'We could not find that order.');
    redirect('my-orders.php');
}
$i = db()->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
$i->execute([$order['id']]);
$items = $i->fetchAll();

$pageTitle = 'Order Placed';
$activeNav = '';
include __DIR__ . '/includes/header.php';
?>
<section class="section">
  <div class="container narrow">
    <div class="success-hero">
      <span class="success-icon" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
      <h1>Order Placed Successfully!</h1>
      <p>Thank you for choosing FoodOil.</p>
      <p>Your order <b class="order-code">#<?= e($order['order_code']) ?></b> has been placed and will be delivered soon.</p>
      <?php if ($order['payment_method'] !== 'COD'): ?>
        <p class="muted small">You chose a demo payment option. No money was charged &mdash; please pay when the order is delivered.</p>
      <?php endif; ?>
      <div class="success-actions">
        <a class="btn btn-primary btn-lg" href="<?= e(url('order-details.php?code=' . urlencode($order['order_code']))) ?>">Track Order</a>
        <a class="btn btn-outline btn-lg" href="<?= e(url('products.php')) ?>">Continue Shopping</a>
      </div>
    </div>

    <div class="two-col">
      <div class="card">
        <h2 class="card-title">Order Summary</h2>
        <ul class="summary-lines">
          <?php foreach ($items as $it): ?>
            <li><img src="<?= e(url('assets/images/' . ($it['image'] ?: 'products/default.svg'))) ?>" alt="">
              <div><b><?= e($it['product_name']) ?></b><br><small class="muted"><?= e(money($it['unit_price'])) ?> &times; <?= (int)$it['quantity'] ?></small></div>
              <span><?= e(money($it['subtotal'])) ?></span></li>
          <?php endforeach; ?>
        </ul>
        <dl class="totals">
          <div><dt>Subtotal</dt><dd><?= e(money($order['subtotal'])) ?></dd></div>
          <div><dt>Delivery Charge</dt><dd><?= (float)$order['delivery_fee'] > 0 ? e(money($order['delivery_fee'])) : 'Free' ?></dd></div>
          <div class="grand"><dt>Total</dt><dd><?= e(money($order['total'])) ?></dd></div>
        </dl>
      </div>
      <div class="card">
        <h2 class="card-title">Delivery Address</h2>
        <address class="address-block">
          <b><?= e($order['customer_name']) ?></b><br>
          <?= e($order['address']) ?><br>
          <?= e($order['city']) ?>, <?= e($order['state']) ?> - <?= e($order['pincode']) ?><br>
          Phone: <?= e($order['phone']) ?>
        </address>
        <p class="muted small mt-sm">Payment: <?= e(PAYMENT_LABELS[$order['payment_method']] ?? $order['payment_method']) ?></p>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
