<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'My Cart';
$activeNav = '';
$extraScripts = [];
include __DIR__ . '/includes/header.php';
?>
<section class="section">
  <div class="container">
    <div class="page-head"><h1>My Cart</h1><span class="muted" id="cart-items-label"></span></div>
    <div id="cart-root" aria-live="polite">
      <div class="loading"><span class="spinner" aria-hidden="true"></span> Loading your cart...</div>
    </div>
    <noscript><div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span>JavaScript is required to use the shopping cart.</span></div></noscript>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
