<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'About Us';
$activeNav = 'about';
$values = [
    ['fa-droplet', 'Pure by default', 'We list oils that are free from added colours and preservatives, so you know what goes into your kitchen.'],
    ['fa-truck-fast', 'Delivered to your door', 'Order from home and get oils delivered in sealed bottles and cans, with free delivery on larger orders.'],
    ['fa-tags', 'Fair prices', 'Family packs and 5 litre cans help you save compared with buying small bottles again and again.'],
    ['fa-heart', 'Healthy choices', 'From sunflower to sesame, pick the oil that suits your recipes and your family.'],
];
include __DIR__ . '/includes/header.php';
?>
<section class="page-banner"><div class="container"><h1>About FoodOil</h1><p>Healthy living starts with quality ingredients.</p></div></section>
<section class="section">
  <div class="container narrow">
    <h2>Our story</h2>
    <p>FoodOil is an online store for cooking oils. We started with a simple idea: buying good oil should not mean carrying heavy cans home from the market. Choose from sunflower, groundnut, coconut, mustard, olive, rice bran and sesame oil, and we bring it to your home.</p>
    <p>FoodOil is a college full-stack project built with HTML, CSS, JavaScript, PHP and MySQL. Product names, prices and reviews are sample data used to demonstrate how an e-commerce site works.</p>
  </div>
</section>
<section class="section section-grey">
  <div class="container">
    <div class="section-head"><h2>What we stand for</h2></div>
    <div class="value-grid">
      <?php foreach ($values as [$icon, $title, $text]): ?>
        <div class="value-card"><span class="feature-icon"><i class="fa-solid <?= e($icon) ?>" aria-hidden="true"></i></span><h3><?= e($title) ?></h3><p class="muted"><?= e($text) ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<section class="section">
  <div class="container narrow center">
    <h2>Ready to order?</h2>
    <p class="muted">Browse the catalogue and add your favourite oils to the cart.</p>
    <a class="btn btn-primary btn-lg" href="<?= e(url('products.php')) ?>">Shop Now</a>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
