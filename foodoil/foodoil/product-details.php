<?php
require_once __DIR__ . '/includes/functions.php';
$activeNav = 'products';
$id = (int)($_GET['id'] ?? 0);
$s = db()->prepare('SELECT * FROM products WHERE id = ? AND is_active = 1');
$s->execute([$id]);
$p = $s->fetch();
if (!$p) {
    flash('error', 'That product could not be found.');
    redirect('products.php');
}
$pageTitle = $p['name'] . ' ' . $p['volume'];
$stock = (int)$p['stock'];
[$sc, $sl] = stock_info($stock);
$disc = discount_percent($p);
$maxQty = max(1, min($stock, MAX_QTY));

$r = db()->prepare('SELECT * FROM products WHERE is_active = 1 AND id <> ? ORDER BY (category = ?) DESC, popularity DESC LIMIT 4');
$r->execute([$id, $p['category']]);
$related = $r->fetchAll();

$benefits = [
    ['fa-droplet', '100% Pure'], ['fa-ban', 'No Preservatives'], ['fa-heart', 'Healthy Choice'], ['fa-truck-fast', 'Fast Delivery'],
];
include __DIR__ . '/includes/header.php';
?>
<section class="section">
  <div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?= e(url('index.php')) ?>">Home</a><i class="fa-solid fa-angle-right" aria-hidden="true"></i>
      <a href="<?= e(url('products.php?cat=' . urlencode($p['category']))) ?>"><?= e($p['category']) ?></a><i class="fa-solid fa-angle-right" aria-hidden="true"></i>
      <span><?= e($p['volume']) ?></span>
    </nav>

    <div class="detail-grid">
      <div class="detail-image"><img src="<?= e(product_image($p)) ?>" alt="<?= e($p['name'] . ' ' . $p['volume']) ?>"></div>
      <div class="detail-info">
        <h1><?= e($p['name']) ?></h1>
        <p class="muted"><?= e($p['volume']) ?> &middot; <?= e($p['category']) ?></p>
        <p class="detail-price"><?= e(money($p['price'])) ?>
          <?php if ($disc > 0): ?><s><?= e(money($p['mrp'])) ?></s><span class="badge-green"><?= $disc ?>% OFF</span><?php endif; ?>
        </p>
        <?php if ((int)$p['reviews'] > 0): ?>
          <p class="rating-row"><?= stars((float)$p['rating']) ?> <b><?= e(number_format((float)$p['rating'], 1)) ?></b> <span class="muted">(<?= (int)$p['reviews'] ?> sample reviews)</span></p>
        <?php endif; ?>
        <p class="detail-desc"><?= e($p['description']) ?></p>
        <p class="stock stock-<?= $sc ?>"><span class="dot"></span><?= e($sl) ?></p>

        <?php if ($stock > 0): ?>
          <div class="qty-row">
            <label for="qty">Quantity:</label>
            <div class="qty-box" data-qty-box data-max="<?= $maxQty ?>">
              <button type="button" data-qty-minus aria-label="Decrease quantity">&minus;</button>
              <input type="number" id="qty" value="1" min="1" max="<?= $maxQty ?>" inputmode="numeric">
              <button type="button" data-qty-plus aria-label="Increase quantity">+</button>
            </div>
            <?php if ($stock <= MAX_QTY): ?><span class="muted small">Max <?= $maxQty ?> available</span><?php endif; ?>
          </div>
          <div class="detail-actions">
            <button type="button" class="btn btn-primary btn-lg" data-add-to-cart data-id="<?= (int)$p['id'] ?>" data-stock="<?= $stock ?>" data-name="<?= e($p['name']) ?>" data-qty-input="#qty">Add to Cart</button>
            <button type="button" class="btn btn-outline btn-lg" data-buy-now data-id="<?= (int)$p['id'] ?>" data-stock="<?= $stock ?>" data-name="<?= e($p['name']) ?>" data-qty-input="#qty">Buy Now</button>
          </div>
        <?php else: ?>
          <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span>This product is currently out of stock.</span></div>
          <a class="btn btn-outline" href="<?= e(url('products.php?cat=' . urlencode($p['category']))) ?>">See other <?= e($p['category']) ?> options</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="benefit-row">
      <?php foreach ($benefits as [$icon, $label]): ?>
        <div class="benefit"><span class="feature-icon sm"><i class="fa-solid <?= e($icon) ?>" aria-hidden="true"></i></span><span><?= e($label) ?></span></div>
      <?php endforeach; ?>
    </div>

    <?php if ($related): ?>
      <div class="section-head mt"><h2>You may also like</h2></div>
      <div class="product-grid grid-4"><?php foreach ($related as $rp) { render_product_card($rp); } ?></div>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
