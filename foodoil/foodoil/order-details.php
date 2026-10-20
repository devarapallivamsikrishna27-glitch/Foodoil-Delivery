<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
$code = (string)($_GET['code'] ?? $_POST['code'] ?? '');

/* Users can only ever load their own orders */
$s = db()->prepare('SELECT * FROM orders WHERE order_code = ? AND user_id = ?');
$s->execute([$code, $user['id']]);
$order = $s->fetch();
if (!$order) {
    flash('error', 'We could not find that order.');
    redirect('my-orders.php');
}

if (is_post() && post('action') === 'cancel') {
    csrf_require();
    $pdo = db();
    $pdo->beginTransaction();
    $lock = $pdo->prepare('SELECT id, status FROM orders WHERE id = ? AND user_id = ? FOR UPDATE');
    $lock->execute([$order['id'], $user['id']]);
    $cur = $lock->fetch();
    if ($cur && $cur['status'] === 'Pending') {
        $pdo->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ?")->execute([$order['id']]);
        restock_order($pdo, (int)$order['id']);
        $pdo->commit();
        flash('success', 'Order #' . $order['order_code'] . ' has been cancelled.');
    } else {
        $pdo->rollBack();
        flash('error', 'This order can no longer be cancelled because it is already being processed.');
    }
    redirect('order-details.php?code=' . urlencode($order['order_code']));
}

$i = db()->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
$i->execute([$order['id']]);
$items = $i->fetchAll();

$steps = ['Pending', 'Confirmed', 'Out for Delivery', 'Delivered'];
$stepIcons = ['fa-receipt', 'fa-circle-check', 'fa-truck-fast', 'fa-house'];
$cur = array_search($order['status'], $steps, true);
$pageTitle = 'Order #' . $order['order_code'];
$activeNav = '';
include __DIR__ . '/includes/header.php';
?>
<section class="section">
  <div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('my-orders.php')) ?>">My Orders</a><i class="fa-solid fa-angle-right" aria-hidden="true"></i><span>#<?= e($order['order_code']) ?></span></nav>
    <div class="page-head">
      <h1>Order #<?= e($order['order_code']) ?></h1>
      <span class="status <?= e(status_class($order['status'])) ?>"><?= e($order['status']) ?></span>
    </div>
    <p class="muted">Placed on <?= e(date('d M Y, h:i A', strtotime($order['created_at']))) ?></p>

    <?php if ($order['status'] === 'Cancelled'): ?>
      <div class="alert alert-error"><i class="fa-solid fa-ban"></i><span>This order was cancelled.</span></div>
    <?php else: ?>
      <ol class="tracker" aria-label="Order progress">
        <?php foreach ($steps as $n => $label): ?>
          <li class="<?= $n < $cur ? 'done' : ($n === $cur ? 'current' : '') ?>"<?= $n === $cur ? ' aria-current="step"' : '' ?>>
            <span class="tracker-dot"><i class="fa-solid <?= e($stepIcons[$n]) ?>" aria-hidden="true"></i></span><span><?= e($label) ?></span>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>

    <div class="two-col">
      <div class="card">
        <h2 class="card-title">Items</h2>
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
          <b><?= e($order['customer_name']) ?></b><br><?= e($order['address']) ?><br>
          <?= e($order['city']) ?>, <?= e($order['state']) ?> - <?= e($order['pincode']) ?><br>Phone: <?= e($order['phone']) ?>
        </address>
        <p class="muted small mt-sm">Payment: <?= e(PAYMENT_LABELS[$order['payment_method']] ?? $order['payment_method']) ?></p>
        <?php if ($order['status'] === 'Pending'): ?>
          <form method="post" action="<?= e(url('order-details.php')) ?>" data-confirm="Cancel order #<?= e($order['order_code']) ?>? This cannot be undone.">
            <?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="code" value="<?= e($order['order_code']) ?>">
            <button type="submit" class="btn btn-danger-outline">Cancel Order</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
