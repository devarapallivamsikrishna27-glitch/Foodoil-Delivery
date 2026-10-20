<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Pure Cooking Oils Delivered to Your Home';
$activeNav = 'home';

$popular = db()->query('SELECT * FROM products WHERE is_active = 1 ORDER BY popularity DESC, id ASC LIMIT 4')->fetchAll();

$features = [
    ['fa-droplet',     '100% Pure<br>&amp; Natural'],
    ['fa-truck-fast',  'Home<br>Delivery'],
    ['fa-tags',        'Best<br>Prices'],
    ['fa-bottle-water', 'Wide<br>Variety'],
];
$reviews = [
    ['Anita R.',   'Hyderabad', 5, 'The oil is fresh and the bottle arrived sealed and on time. Ordering was simple and I could track it easily.'],
    ['Suresh K.',  'Vijayawada', 5, 'Great prices on 5 litre cans. Cash on delivery made it easy for my parents to order too.'],
    ['Priya M.',   'Chennai',    4, 'Good variety of oils in one place. Coconut and sesame oil quality was excellent.'],
];
include __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="container hero-grid">
    <div class="hero-copy">
      <h1>Pure Cooking Oils<br>Delivered to Your Home</h1>
      <p class="hero-sub">Healthy Living Starts with<br>Quality Ingredients</p>
      <a class="btn btn-primary btn-lg" href="<?= e(url('products.php')) ?>">Shop Now <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
    </div>
    <div class="hero-art"><img src="<?= e(url('assets/images/hero.svg')) ?>" alt="Bottles of cooking oil with a sunflower on a wooden table"></div>
  </div>
</section>

<section class="section features">
  <div class="container feature-grid">
    <?php foreach ($features as [$icon, $label]): ?>
      <div class="feature-card">
        <span class="feature-icon"><i class="fa-solid <?= e($icon) ?>" aria-hidden="true"></i></span>
        <p><?= $label ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <h2>Popular Products</h2>
      <a class="link-arrow" href="<?= e(url('products.php')) ?>">View all products <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
    </div>
    <div class="product-grid grid-4">
      <?php foreach ($popular as $p) { render_product_card($p); } ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="promo-banner">
      <div>
        <h2>Free home delivery on orders above <?= e(money(FREE_DELIVERY_ABOVE)) ?></h2>
        <p>Stock up on 5 litre cans and family packs. Pay by Cash on Delivery.</p>
      </div>
      <a class="btn btn-white" href="<?= e(url('products.php')) ?>">Start Shopping</a>
    </div>
  </div>
</section>

<section class="section section-grey">
  <div class="container">
    <div class="section-head"><h2>What Our Customers Say</h2><span class="muted small">Sample reviews for demonstration</span></div>
    <div class="review-grid">
      <?php foreach ($reviews as [$name, $city, $stars, $text]): ?>
        <figure class="review-card">
          <?= stars((float)$stars) ?>
          <blockquote><?= e($text) ?></blockquote>
          <figcaption><span class="avatar" aria-hidden="true"><?= e(mb_substr($name, 0, 1)) ?></span><span><b><?= e($name) ?></b><br><small class="muted"><?= e($city) ?></small></span></figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
