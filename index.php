<?php
require_once __DIR__ . '/includes/functions.php';
$page = 'home';
$pageTitle = 'Beranda';

$categories = db()->query("SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id=c.id AND p.is_active=1) cnt FROM categories c WHERE c.is_active=1 ORDER BY c.name")->fetchAll();

$featured = db()->query("SELECT p.*, b.name brand_name FROM products p LEFT JOIN brands b ON b.id=p.brand_id WHERE p.is_active=1 AND (p.label='BEST SELLER' OR p.promo_price IS NOT NULL) ORDER BY p.label='BEST SELLER' DESC LIMIT 8")->fetchAll();

$newest = db()->query("SELECT p.*, b.name brand_name FROM products p LEFT JOIN brands b ON b.id=p.brand_id WHERE p.is_active=1 ORDER BY p.created_at DESC, p.id DESC LIMIT 8")->fetchAll();

$catIcons = ['nugget'=>'fa-drumstick-bite','sosis'=>'fa-hotdog','bakso'=>'fa-bowl-food','dimsum'=>'fa-bowl-rice','kentang'=>'fa-utensils','seafood'=>'fa-fish','default'=>'fa-snowflake'];

require __DIR__ . '/includes/header.php';
?>
<div class="container my-4">

  <!-- HERO -->
  <?php if (fe_show('hero_show')): ?>
  <div class="hero p-4 p-md-5 mb-4">
    <div class="row align-items-center g-3">
      <div class="col-md-7">
        <?php if (fe('hero_badge')): ?><span class="badge bg-white text-primary mb-2 px-3 py-2 rounded-pill fw-bold"><i class="fa-solid fa-snowflake me-1"></i><?= e(fe('hero_badge')) ?></span><?php endif; ?>
        <h1 class="mb-2"><?= nl2br(e(fe('hero_title'))) ?></h1>
        <p class="mb-3 opacity-90"><?= e(fe('hero_subtitle')) ?></p>
        <a href="<?= e(nav_url(fe('hero_cta_link'))) ?>" class="btn btn-chipi btn-lg px-4" data-testid="hero-cta"><i class="fa-solid fa-bag-shopping me-1"></i><?= e(fe('hero_cta_text')) ?></a>
      </div>
      <div class="col-md-5 text-center">
        <img src="<?= asset('img/logo.png') ?>" class="hero-logo logo-glow img-fluid" alt="Chipi Frozen Food">
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- CATEGORIES -->
  <section id="kategori" class="mb-4">
    <h4 class="section-title brand-font">Kategori Produk</h4>
    <div class="row row-cols-3 row-cols-md-6 g-2 g-md-3">
      <?php foreach ($categories as $c): $ic = $catIcons[$c['slug']] ?? $catIcons['default']; ?>
        <div class="col">
          <a href="<?= url('customer/products.php?category=' . (int)$c['id']) ?>" class="cat-pill" data-testid="cat-<?= e($c['slug']) ?>">
            <span class="cat-icon"><i class="fa-solid <?= $ic ?>"></i></span>
            <span class="fw-bold small"><?= e($c['name']) ?></span>
            <span class="text-muted-chipi" style="font-size:.7rem"><?= (int)$c['cnt'] ?> produk</span>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- PRODUK PILIHAN -->
  <section class="mb-4">
    <div class="d-flex justify-content-between align-items-center">
      <h4 class="section-title brand-font mb-0">Produk Pilihan</h4>
      <a href="<?= url('customer/products.php') ?>" class="btn btn-outline-chipi btn-sm">Lihat Semua</a>
    </div>
    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-3 mt-1">
      <?php foreach ($featured as $p): ?>
        <div class="col"><?php require __DIR__ . '/includes/product_card.php'; ?></div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- PROMO BANNER -->
  <?php if (fe_show('promo_show')): ?>
  <div class="card p-4 mb-4" style="background:linear-gradient(120deg,#ff9d4d,#ff7a29);color:#fff">
    <div class="row align-items-center g-2">
      <div class="col-md-8">
        <h4 class="brand-font mb-1"><i class="fa-solid fa-tags me-2"></i><?= e(fe('promo_title')) ?></h4>
        <p class="mb-0"><?= e(fe('promo_text')) ?></p>
      </div>
      <div class="col-md-4 text-md-end">
        <a href="<?= e(nav_url(fe('promo_btn_link'))) ?>" class="btn btn-light fw-bold" style="color:#e2620f"><?= e(fe('promo_btn_text')) ?></a>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- PRODUK TERBARU -->
  <section class="mb-4">
    <h4 class="section-title brand-font">Produk Terbaru</h4>
    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-3">
      <?php foreach ($newest as $p): ?>
        <div class="col"><?php require __DIR__ . '/includes/product_card.php'; ?></div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- BENEFITS -->
  <?php if (fe_show('benefits_show')): ?>
  <section class="mb-2">
    <h4 class="section-title brand-font"><?= e(fe('benefits_title')) ?></h4>
    <div class="row row-cols-2 row-cols-md-4 g-3">
      <?php foreach (benefit_items() as $b): ?>
        <div class="col">
          <div class="benefit-card">
            <div class="benefit-icon" style="background:<?= e($b['color']) ?>"><i class="fa-solid <?= e($b['icon']) ?>"></i></div>
            <div class="fw-bold"><?= e($b['title']) ?></div>
            <div class="small text-muted-chipi"><?= e($b['desc']) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
