<?php
/** Shared admin layout (top). Every admin page includes this AFTER calling require_admin(). */
require_once __DIR__ . '/../includes/functions.php';
$admin = require_admin();
$adminTitle  = $adminTitle  ?? 'Dashboard';
$adminActive = $adminActive ?? 'dashboard';
$nav = [
    'dashboard' => ['Dashboard', 'dashboard.php', 'fa-gauge'],
    'orders'    => ['Orders',    'orders.php',    'fa-box'],
    'products'  => ['Products',  'products.php',  'fa-bottle-droplet'],
    'users'     => ['Users',     'users.php',     'fa-users'],
    'reports'   => ['Reports',   'reports.php',   'fa-chart-column'],
    'messages'  => ['Messages',  'messages.php',  'fa-envelope'],
];
$css = function (string $f): string { $t = @filemtime(__DIR__ . '/../assets/css/' . $f); return url('assets/css/' . $f) . ($t ? '?v=' . $t : ''); };
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($adminTitle) ?> | FoodOil Admin</title>
<link rel="icon" type="image/svg+xml" href="<?= e(url('assets/images/favicon.svg')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= e($css('style.css')) ?>">
<link rel="stylesheet" href="<?= e($css('admin.css')) ?>">
<script>window.FOODOIL = <?= json_encode(['base' => BASE_URL, 'csrf' => csrf_token()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
</head>
<body class="admin-body">
<a class="skip-link" href="#main">Skip to main content</a>
<div class="admin-shell">
  <aside class="admin-side" id="admin-side">
    <div class="admin-brand">FoodOil Admin</div>
    <nav aria-label="Admin navigation">
      <?php foreach ($nav as $key => [$label, $href, $icon]): ?>
        <a href="<?= e(url('admin/' . $href)) ?>" class="<?= $adminActive === $key ? 'active' : '' ?>" <?= $adminActive === $key ? 'aria-current="page"' : '' ?>><i class="fa-solid <?= e($icon) ?>" aria-hidden="true"></i> <?= e($label) ?></a>
      <?php endforeach; ?>
      <a href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-store" aria-hidden="true"></i> View Store</a>
    </nav>
    <a class="admin-logout" href="<?= e(url('logout.php?t=' . csrf_token())) ?>"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Logout</a>
  </aside>
  <div class="admin-main">
    <header class="admin-top">
      <button type="button" class="icon-btn admin-menu-btn" onclick="document.getElementById('admin-side').classList.toggle('open')" aria-label="Toggle menu"><i class="fa-solid fa-bars"></i></button>
      <h1><?= e($adminTitle) ?></h1>
      <span class="admin-user"><i class="fa-solid fa-user-shield" aria-hidden="true"></i> <?= e($admin['name']) ?></span>
    </header>
    <main id="main" class="admin-content">
      <?php render_flash(); ?>
