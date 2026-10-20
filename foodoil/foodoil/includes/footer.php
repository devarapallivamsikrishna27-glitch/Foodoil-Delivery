<?php
/** Shared page footer. Optional: $extraScripts = ['checkout.js', ...] */
$noChrome     = $noChrome ?? false;
$extraScripts = $extraScripts ?? [];
$jsV = function (string $f): string {
    $t = @filemtime(__DIR__ . '/../assets/js/' . $f);
    return url('assets/js/' . $f) . ($t ? '?v=' . $t : '');
};
?>
</main>
<?php if (!$noChrome): ?>
<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <a class="logo logo-light" href="<?= e(url('index.php')) ?>" aria-label="FoodOil home">
        <svg class="logo-drop" viewBox="0 0 32 32" aria-hidden="true"><path d="M16 2C16 2 5 15 5 21.5a11 11 0 0 0 22 0C27 15 16 2 16 2Z" fill="#fff"/><path d="M16 25c-4 0-6-2.6-6-5.6 4-.2 6.4 1.2 6 5.6Zm0 0c4 0 6-2.6 6-5.6-4-.2-6.4 1.2-6 5.6Z" fill="#0969DA"/></svg>
        <span class="logo-text"><span class="logo-name"><b>Food</b><i>Oil</i></span></span>
      </a>
      <p class="footer-about">Pure, healthy cooking oils from trusted brands, delivered to your doorstep.</p>
    </div>
    <div>
      <h4>Quick Links</h4>
      <ul>
        <li><a href="<?= e(url('index.php')) ?>">Home</a></li>
        <li><a href="<?= e(url('products.php')) ?>">Products</a></li>
        <li><a href="<?= e(url('about.php')) ?>">About Us</a></li>
        <li><a href="<?= e(url('contact.php')) ?>">Contact Us</a></li>
        <li><a href="<?= e(url('my-orders.php')) ?>">My Orders</a></li>
      </ul>
    </div>
    <div>
      <h4>Categories</h4>
      <ul>
        <?php foreach (array_slice(array_keys(CATEGORIES), 0, 5) as $c): ?>
          <li><a href="<?= e(url('products.php?cat=' . urlencode($c))) ?>"><?= e($c) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div>
      <h4>Contact</h4>
      <ul class="footer-contact">
        <li><i class="fa-solid fa-location-dot"></i> 12 Market Road, Sample City, India</li>
        <li><i class="fa-solid fa-phone"></i> +91 90000 00000</li>
        <li><i class="fa-solid fa-envelope"></i> support@foodoil.example</li>
        <li><i class="fa-regular fa-clock"></i> Mon-Sat, 9 AM - 7 PM</li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="container">
      <span>&copy; <?= date('Y') ?> FoodOil. College project.</span>
      <span>Products, prices and reviews are sample data for demonstration.</span>
    </div>
  </div>
</footer>
<?php endif; ?>
<div class="toast-wrap" id="toast-wrap" role="status" aria-live="polite"></div>
<script src="<?= e($jsV('cart.js')) ?>"></script>
<script src="<?= e($jsV('app.js')) ?>"></script>
<?php foreach ($extraScripts as $s): ?>
<script src="<?= e($jsV($s)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
