<?php
/**
 * Shared page header.
 * Set before including:  $pageTitle (string), $activeNav ('home'|'products'|'about'|'contact'),
 *                        $noChrome (bool, true for login/register), $bodyClass (string)
 */
require_once __DIR__ . '/functions.php';
$pageTitle  = $pageTitle  ?? 'Pure Cooking Oils Delivered to Your Home';
$activeNav  = $activeNav  ?? '';
$noChrome   = $noChrome   ?? false;
$bodyClass  = $bodyClass  ?? '';
$user       = current_user();
$cssV       = function (string $f): string {
    $t = @filemtime(__DIR__ . '/../assets/css/' . $f);
    return url('assets/css/' . $f) . ($t ? '?v=' . $t : '');
};
$navItems = ['home' => ['Home', 'index.php'], 'products' => ['Products', 'products.php'], 'about' => ['About', 'about.php'], 'contact' => ['Contact', 'contact.php']];
$dropIcon = '<svg class="logo-drop" viewBox="0 0 32 32" aria-hidden="true"><path d="M16 2C16 2 5 15 5 21.5a11 11 0 0 0 22 0C27 15 16 2 16 2Z" fill="#0969DA"/><path d="M16 2C16 2 5 15 5 21.5a11 11 0 0 0 5 9.2C8 24 12 12 16 2Z" fill="#fff" opacity=".18"/><path d="M16 25c-4 0-6-2.6-6-5.6 4-.2 6.4 1.2 6 5.6Zm0 0c4 0 6-2.6 6-5.6-4-.2-6.4 1.2-6 5.6Z" fill="#fff"/></svg>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title><?= e($pageTitle) ?> | FoodOil</title>
<meta name="description" content="FoodOil delivers pure, healthy cooking oils to your doorstep.">
<link rel="icon" type="image/svg+xml" href="<?= e(url('assets/images/favicon.svg')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= e($cssV('style.css')) ?>">
<link rel="stylesheet" href="<?= e($cssV('responsive.css')) ?>">
<script>
window.FOODOIL = <?= json_encode([
    'base' => BASE_URL, 'csrf' => csrf_token(), 'deliveryFee' => DELIVERY_FEE,
    'freeAbove' => FREE_DELIVERY_ABOVE, 'maxQty' => MAX_QTY, 'loggedIn' => (bool)$user,
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
</head>
<body class="<?= e($bodyClass) ?>">
<a class="skip-link" href="#main">Skip to main content</a>
<?php if (!$noChrome): ?>
<header class="site-header" id="site-header">
  <div class="container header-row">
    <a class="logo" href="<?= e(url('index.php')) ?>" aria-label="FoodOil home">
      <?= $dropIcon ?>
      <span class="logo-text"><span class="logo-name"><b>Food</b><i>Oil</i></span><span class="logo-tag">Pure | Healthy | Delivered</span></span>
    </a>

    <nav class="main-nav" id="main-nav" aria-label="Main navigation">
      <?php foreach ($navItems as $key => [$label, $href]): ?>
        <a href="<?= e(url($href)) ?>" class="<?= $activeNav === $key ? 'active' : '' ?>" <?= $activeNav === $key ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="header-actions">
      <button type="button" class="icon-btn" id="search-toggle" aria-label="Search products" aria-expanded="false" aria-controls="search-panel"><i class="fa-solid fa-magnifying-glass"></i></button>
      <a class="icon-btn cart-link" href="<?= e(url('cart.php')) ?>" aria-label="Shopping cart"><i class="fa-solid fa-cart-shopping"></i><span class="cart-badge" id="cart-count" hidden>0</span></a>
      <?php if ($user): ?>
        <details class="user-menu">
          <summary class="icon-btn" aria-label="Account menu"><i class="fa-solid fa-user"></i></summary>
          <div class="user-dropdown">
            <p class="user-hello">Hi, <?= e(explode(' ', $user['name'])[0]) ?></p>
            <a href="<?= e(url('my-orders.php')) ?>"><i class="fa-solid fa-box"></i> My Orders</a>
            <?php if ($user['role'] === 'admin'): ?><a href="<?= e(url('admin/dashboard.php')) ?>"><i class="fa-solid fa-gauge"></i> Admin Dashboard</a><?php endif; ?>
            <a href="<?= e(url('change-password.php')) ?>"><i class="fa-solid fa-key"></i> Change Password</a>
            <a href="<?= e(url('logout.php?t=' . csrf_token())) ?>"><i class="fa-solid fa-right-from-bracket"></i> Log out</a>
          </div>
        </details>
      <?php else: ?>
        <a class="icon-btn" href="<?= e(url('login.php')) ?>" aria-label="Log in or sign up"><i class="fa-solid fa-user"></i></a>
      <?php endif; ?>
      <button type="button" class="icon-btn menu-toggle" id="menu-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="main-nav"><i class="fa-solid fa-bars"></i></button>
    </div>
  </div>

  <div class="search-panel" id="search-panel" hidden>
    <div class="container">
      <form action="<?= e(url('products.php')) ?>" method="get" role="search" class="search-form">
        <label class="sr-only" for="header-search">Search cooking oils</label>
        <input type="search" id="header-search" name="q" placeholder="Search cooking oils..." autocomplete="off" maxlength="60">
        <button type="submit" class="btn btn-primary">Search</button>
      </form>
      <ul class="search-suggest" id="search-suggest" aria-live="polite"></ul>
    </div>
  </div>
</header>
<?php endif; ?>
<main id="main">
<?php if (!$noChrome): ?>
  <div class="container flash-wrap"><?php render_flash(); ?></div>
<?php endif; ?>
