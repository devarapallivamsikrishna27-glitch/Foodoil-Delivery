<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pdo = db();
$st = $pdo->query("SELECT
    (SELECT COUNT(*) FROM orders) AS orders_total,
    (SELECT COALESCE(SUM(total), 0) FROM orders WHERE status <> 'Cancelled') AS revenue,
    (SELECT COUNT(*) FROM users WHERE role = 'customer') AS users_total,
    (SELECT COUNT(*) FROM products WHERE is_active = 1) AS products_total,
    (SELECT COUNT(*) FROM orders WHERE status = 'Pending') AS pending,
    (SELECT COUNT(*) FROM products WHERE is_active = 1 AND stock <= 10) AS low_stock")->fetch();
$recent = $pdo->query('SELECT order_code, customer_name, total, status, created_at FROM orders ORDER BY id DESC LIMIT 8')->fetchAll();

$adminTitle = 'Dashboard'; $adminActive = 'dashboard';
include __DIR__ . '/_header.php';
?>
<div class="stat-grid">
  <div class="stat-card blue"><span class="stat-label">Total Orders</span><strong><?= (int)$st['orders_total'] ?></strong><small><?= (int)$st['pending'] ?> pending</small></div>
  <div class="stat-card green"><span class="stat-label">Total Revenue</span><strong><?= e(money($st['revenue'])) ?></strong><small>Excludes cancelled orders</small></div>
  <div class="stat-card orange"><span class="stat-label">Total Users</span><strong><?= (int)$st['users_total'] ?></strong><small>Registered customers</small></div>
  <div class="stat-card purple"><span class="stat-label">Total Products</span><strong><?= (int)$st['products_total'] ?></strong><small><?= (int)$st['low_stock'] ?> low or out of stock</small></div>
</div>

<div class="panel">
  <div class="panel-head"><h2>Recent Orders</h2><a class="link-arrow" href="<?= e(url('admin/orders.php')) ?>">Manage orders <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
  <?php if ($recent): ?>
  <div class="table-wrap"><table class="data-table">
    <thead><tr><th>Order ID</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
    <tbody>
    <?php foreach ($recent as $o): ?>
      <tr><td><b>#<?= e($o['order_code']) ?></b></td><td><?= e($o['customer_name']) ?></td><td><?= e(money($o['total'])) ?></td>
          <td><span class="status <?= e(status_class($o['status'])) ?>"><?= e($o['status']) ?></span></td><td><?= e(date('d M Y', strtotime($o['created_at']))) ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
  <?php else: ?><p class="muted pad">No orders yet.</p><?php endif; ?>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
