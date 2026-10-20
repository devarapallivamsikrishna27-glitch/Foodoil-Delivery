<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
$pageTitle = 'My Orders';
$activeNav = '';

$s = db()->prepare("SELECT o.id, o.order_code, o.total, o.status, o.created_at,
        (SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE order_id = o.id) AS qty,
        (SELECT GROUP_CONCAT(product_name ORDER BY id SEPARATOR '||') FROM order_items WHERE order_id = o.id) AS names
    FROM orders o WHERE o.user_id = ? ORDER BY o.id DESC");
$s->execute([$user['id']]);
$orders = $s->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<section class="section">
  <div class="container">
    <div class="page-head"><h1>My Orders</h1><span class="muted"><?= count($orders) ?> order<?= count($orders) === 1 ? '' : 's' ?></span></div>
    <?php if ($orders): ?>
      <div class="table-wrap">
        <table class="data-table responsive-table">
          <thead><tr><th>Order ID</th><th>Date</th><th>Products</th><th>Total</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
          <?php foreach ($orders as $o):
              $names = $o['names'] !== null ? explode('||', $o['names']) : [];
              $shown = array_slice($names, 0, 2);
              $more  = count($names) - count($shown); ?>
            <tr>
              <td data-label="Order ID"><b>#<?= e($o['order_code']) ?></b></td>
              <td data-label="Date"><?= e(date('d M Y', strtotime($o['created_at']))) ?></td>
              <td data-label="Products"><?= e(implode(', ', $shown)) ?><?= $more > 0 ? ' <span class="muted">+' . $more . ' more</span>' : '' ?><br><small class="muted"><?= (int)$o['qty'] ?> item<?= (int)$o['qty'] === 1 ? '' : 's' ?></small></td>
              <td data-label="Total"><?= e(money($o['total'])) ?></td>
              <td data-label="Status"><span class="status <?= e(status_class($o['status'])) ?>"><?= e($o['status']) ?></span></td>
              <td data-label="Action"><a class="btn btn-outline btn-sm" href="<?= e(url('order-details.php?code=' . urlencode($o['order_code']))) ?>">View</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <i class="fa-solid fa-box-open" aria-hidden="true"></i>
        <h3>No orders yet</h3>
        <p>When you place an order, it will appear here so you can track it.</p>
        <a class="btn btn-primary" href="<?= e(url('products.php')) ?>">Start Shopping</a>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
