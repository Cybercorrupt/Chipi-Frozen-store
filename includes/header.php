<?php
require_once __DIR__ . '/functions.php';
$__cust = current_customer();
$__cartCount = cart_count();
$__page = $page ?? '';
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= isset($pageTitle) ? e($pageTitle) . ' · ' : '' ?><?= e(setting('site_title', '') ?: setting('store_name', APP_NAME)) ?></title>
<?php if (setting('meta_description', '')): ?><meta name="description" content="<?= e(setting('meta_description')) ?>"><?php endif; ?>
<link rel="icon" href="<?= setting('favicon','') ? asset('img/'.setting('favicon')) : asset('img/logo.png') ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<style>
  .navbar-chipi{background:<?= e(setting('fe_header_color','#38b6ff')) ?>!important}
  .footer-chipi{background:<?= e(setting('fe_footer_color','#0e2a49')) ?>!important}
</style>
<script>window.CHIPI_BASE='<?= BASE_URL ?>';window.CHIPI_CSRF='<?= csrf_token() ?>';</script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-chipi sticky-top py-2">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="<?= url('index.php') ?>" data-testid="nav-logo">
      <img src="<?= asset('img/logo.png') ?>" class="logo-glow" alt="Chipi Frozen Food">
    </a>
    <div class="d-flex align-items-center gap-2 ms-lg-3 order-lg-3">
      <a href="<?= url('customer/cart.php') ?>" class="btn btn-light btn-sm position-relative" data-testid="nav-cart">
        <i class="fa-solid fa-cart-shopping"></i>
        <span class="nav-cart-badge" data-cart-count <?= $__cartCount ? '' : 'style="display:none"' ?>><?= $__cartCount ?></span>
      </a>
      <?php if ($__cust): ?>
        <a href="<?= url('customer/dashboard.php') ?>" class="btn btn-light btn-sm" data-testid="nav-account"><i class="fa-solid fa-user"></i></a>
      <?php else: ?>
        <a href="<?= url('customer/login.php') ?>" class="btn btn-chipi btn-sm" data-testid="nav-login"><i class="fa-solid fa-right-to-bracket me-1"></i>Masuk</a>
      <?php endif; ?>
      <button class="navbar-toggler border-0 text-white ms-1" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
        <i class="fa-solid fa-bars"></i>
      </button>
    </div>
    <div class="collapse navbar-collapse order-lg-2" id="navMain">
      <ul class="navbar-nav ms-lg-4 me-lg-auto mb-2 mb-lg-0">
        <?php foreach (nav_links('header') as $nl): ?>
          <li class="nav-item"><a class="nav-link" href="<?= e(nav_url($nl['url'])) ?>"><?= e($nl['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <form class="d-flex nav-search me-lg-3 mt-2 mt-lg-0" role="search" action="<?= url('customer/products.php') ?>" method="get">
        <div class="input-group">
          <input class="form-control rounded-start-pill" name="q" placeholder="Cari produk / SKU / brand..." value="<?= e($_GET['q'] ?? '') ?>" data-testid="nav-search-input">
          <button class="btn btn-chipi rounded-end-pill" type="submit" data-testid="nav-search-btn"><i class="fa-solid fa-magnifying-glass"></i></button>
        </div>
      </form>
    </div>
  </div>
</nav>

<?php foreach (get_flashes() as $f): ?>
  <div class="container mt-3">
    <div class="alert alert-<?= $f['type'] === 'error' ? 'danger' : e($f['type']) ?> alert-dismissible fade show rounded-4" role="alert" data-testid="flash-message">
      <?= e($f['msg']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  </div>
<?php endforeach; ?>
