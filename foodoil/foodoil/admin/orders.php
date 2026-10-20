<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pdo = db();
$filter = (string)($_GET['status'] ?? '');
if ($filter !== '' && !in_array($filter, ORDER_STATUSES, true)) { $filter = ''; }
$search = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 30);

if (is_post() && post('action') === 'status') {
    csrf_require();
    $id = (int)post('id'); $new = post('status');
    $back = 'admin/orders.php' . (in_array(post('f'), ORDER_STATUSES, true) ? '?status=' . urlencode(post('f')) : '');
    if (!in_array($new, ORDER_STATUSES, true)) {
        flash('error', 'Choose a valid status.');
    } else {
        $pdo->beginTransaction();
        $lock = $pdo->prepare('SELECT id, order_code, status FROM orders WHERE id = ? FOR UPDATE');
        $lock->execute([$id]);
        $cur = $lock->fetch();
        if (!$cur) {
            $pdo->rollBack(); flash('error', 'Order not found.');
        } elseif (in_array($cur['status'], ['Delivered', 'Cancelled'], true)) {
            $pdo->rollBack(); flash('error', 'Delivered and cancelled orders cannot be changed.');
        } elseif ($cur['status'] === $new) {
            $pdo->rollBack(); flash('info', 'The order already has that status.');
        } else {
            $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$new, $id]);
            if ($new === 'Cancelled') { restock_order($pdo, $id); }
            $pdo->commit();
            flash('success', 'Order #' . $cur['order_code'] . ' is now "' . $new . '".');
        }
    }
    redirect($back);
}

$where = []; $params = [];
if ($filter !== '') { $where[] = 'status = ?'; $params[] = $filter; }
if ($search !== '') { $where[] = '(order_code LIKE ? OR customer_name LIKE ? OR phone LIKE ?)'; $like = '%' . addcslashes($search, '%_\\') . '%'; array_push($params, $like, $like, $like); }
$s = $pdo->prepare('SELECT * FROM orders' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id DESC LIMIT 200');
$s->execute($params);
$orders = $s->fetchAll();
$items = [];
if ($orders) {
    $ids = array_column($orders, 'id');
    $q = $pdo->prepare('SELECT order_id, product_name, quantity FROM order_items WHERE order_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ') ORDER BY id');
    $q->execute($ids);
    foreach ($q->fetchAll() as $r) { $items[$r['order_id']][] = $r; }
}
$adminTitle = 'Orders'; $adminActive = 'orders';
include __DIR__ . '/_header.php';
?>
<div class="panel">
  <div class="panel-head">
    <form class="filter-bar" method="get" action="<?= e(url('admin/orders.php')) ?>">
      <label class="sr-only" for="q">Search orders</label>
      <input id="q" type="search" name="q" value="<?= e($search) ?>" placeholder="Search order ID, name or phone">
      <label class="sr-only" for="status">Filter by status</label>
      <select id="status" name="status" data-autosubmit>
        <option value="">All statuses</option>
        <?php foreach (ORDER_STATUSES as $stt): ?><option <?= $filter === $stt ? 'selected' : '' ?>><?= e($stt) ?></option><?php endforeach; ?>
      </select>
      <button class="btn btn-primary btn-sm" type="submit">Search</button>
    </form>
  </div>
  <?php if ($orders): ?>
  <div class="table-wrap"><table class="data-table">
    <thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Update</th></tr></thead>
    <tbody>
    <?php foreach ($orders as $o): $final = in_array($o['status'], ['Delivered', 'Cancelled'], true); ?>
      <tr>
        <td><b>#<?= e($o['order_code']) ?></b><br><small class="muted"><?= e(date('d M Y, h:i A', strtotime($o['created_at']))) ?></small></td>
        <td><?= e($o['customer_name']) ?><br><small class="muted"><?= e($o['phone']) ?> &middot; <?= e($o['city']) ?>, <?= e($o['pincode']) ?></small>
            <details class="addr"><summary>Address</summary><?= e($o['address']) ?>, <?= e($o['city']) ?>, <?= e($o['state']) ?> - <?= e($o['pincode']) ?></details></td>
        <td><?php foreach ($items[$o['id']] ?? [] as $it): ?><div class="small"><?= (int)$it['quantity'] ?> &times; <?= e($it['product_name']) ?></div><?php endforeach; ?></td>
        <td><?= e(money($o['total'])) ?></td>
        <td><?= e(PAYMENT_LABELS[$o['payment_method']] ?? $o['payment_method']) ?></td>
        <td><span class="status <?= e(status_class($o['status'])) ?>"><?= e($o['status']) ?></span></td>
        <td>
          <?php if ($final): ?><span class="muted small">Final</span><?php else: ?>
          <form method="post" action="<?= e(url('admin/orders.php')) ?>" class="inline-form" data-confirm="Update order #<?= e($o['order_code']) ?>?">
            <?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int)$o['id'] ?>"><input type="hidden" name="f" value="<?= e($filter) ?>">
            <label class="sr-only" for="s<?= (int)$o['id'] ?>">New status for order <?= e($o['order_code']) ?></label>
            <select id="s<?= (int)$o['id'] ?>" name="status"><?php foreach (ORDER_STATUSES as $stt): ?><option <?= $o['status'] === $stt ? 'selected' : '' ?>><?= e($stt) ?></option><?php endforeach; ?></select>
            <button class="btn btn-primary btn-sm" type="submit">Save</button>
          </form><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody></table></div>
  <?php else: ?><p class="muted pad">No orders match your search.</p><?php endif; ?>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
