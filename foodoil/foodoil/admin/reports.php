<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pdo = db();

/* Sales for the last 14 days (cancelled orders excluded) */
$byDay = [];
foreach ($pdo->query("SELECT DATE(created_at) AS d, COUNT(*) AS c, COALESCE(SUM(total), 0) AS r FROM orders
        WHERE status <> 'Cancelled' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY DATE(created_at)")->fetchAll() as $r) {
    $byDay[$r['d']] = $r;
}
$series = []; $maxRev = 0;
for ($i = 13; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i day"));
    $rev = (float)($byDay[$day]['r'] ?? 0);
    $series[] = ['label' => date('d M', strtotime($day)), 'rev' => $rev, 'orders' => (int)($byDay[$day]['c'] ?? 0)];
    $maxRev = max($maxRev, $rev);
}
$byStatus = $pdo->query('SELECT status, COUNT(*) AS c FROM orders GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);
$top = $pdo->query("SELECT oi.product_name, SUM(oi.quantity) AS qty, SUM(oi.subtotal) AS rev FROM order_items oi
        JOIN orders o ON o.id = oi.order_id WHERE o.status <> 'Cancelled' GROUP BY oi.product_name ORDER BY qty DESC LIMIT 5")->fetchAll();
$cats = $pdo->query("SELECT COALESCE(p.category, 'Removed products') AS cat, SUM(oi.subtotal) AS rev, SUM(oi.quantity) AS qty FROM order_items oi
        JOIN orders o ON o.id = oi.order_id LEFT JOIN products p ON p.id = oi.product_id WHERE o.status <> 'Cancelled' GROUP BY COALESCE(p.category, 'Removed products') ORDER BY rev DESC")->fetchAll();
$low = $pdo->query('SELECT name, volume, stock FROM products WHERE is_active = 1 AND stock <= 10 ORDER BY stock, name')->fetchAll();
$totalCatRev = array_sum(array_column($cats, 'rev')) ?: 1;

$adminTitle = 'Reports'; $adminActive = 'reports';
include __DIR__ . '/_header.php';
?>
<div class="panel">
  <div class="panel-head"><h2>Revenue, last 14 days</h2><span class="muted small">Excludes cancelled orders</span></div>
  <div class="bar-chart" role="img" aria-label="Daily revenue for the last 14 days">
    <?php foreach ($series as $b): $h = $maxRev > 0 ? max(2, round($b['rev'] / $maxRev * 100)) : 2; ?>
      <div class="bar-col" title="<?= e($b['label'] . ': ' . money($b['rev']) . ' from ' . $b['orders'] . ' order(s)') ?>">
        <span class="bar-val"><?= $b['rev'] > 0 ? e(money($b['rev'])) : '' ?></span>
        <span class="bar" style="height:<?= (int)$h ?>%"></span>
        <span class="bar-label"><?= e($b['label']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="report-grid">
  <div class="panel">
    <div class="panel-head"><h2>Orders by status</h2></div>
    <ul class="report-list">
      <?php foreach (ORDER_STATUSES as $stt): ?>
        <li><span class="status <?= e(status_class($stt)) ?>"><?= e($stt) ?></span><b><?= (int)($byStatus[$stt] ?? 0) ?></b></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <div class="panel">
    <div class="panel-head"><h2>Top selling products</h2></div>
    <?php if ($top): ?><ul class="report-list">
      <?php foreach ($top as $t): ?><li><span><?= e($t['product_name']) ?></span><b><?= (int)$t['qty'] ?> sold &middot; <?= e(money($t['rev'])) ?></b></li><?php endforeach; ?>
    </ul><?php else: ?><p class="muted pad">No sales yet.</p><?php endif; ?>
  </div>
  <div class="panel">
    <div class="panel-head"><h2>Revenue by category</h2></div>
    <?php if ($cats): ?><ul class="report-list">
      <?php foreach ($cats as $c): $pct = round($c['rev'] / $totalCatRev * 100); ?>
        <li class="with-bar"><span><?= e($c['cat']) ?></span><b><?= e(money($c['rev'])) ?></b><span class="meter"><span style="width:<?= (int)$pct ?>%"></span></span></li>
      <?php endforeach; ?>
    </ul><?php else: ?><p class="muted pad">No sales yet.</p><?php endif; ?>
  </div>
  <div class="panel">
    <div class="panel-head"><h2>Low stock (10 or fewer)</h2></div>
    <?php if ($low): ?><ul class="report-list">
      <?php foreach ($low as $l): ?><li><span><?= e($l['name'] . ' (' . $l['volume'] . ')') ?></span><b class="<?= (int)$l['stock'] === 0 ? 'text-red' : '' ?>"><?= (int)$l['stock'] === 0 ? 'Out of stock' : (int)$l['stock'] . ' left' ?></b></li><?php endforeach; ?>
    </ul><?php else: ?><p class="muted pad">All products are well stocked.</p><?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
