<?php
require_once __DIR__ . '/../includes/functions.php';
$page = 'products';

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare("SELECT p.*, b.name brand_name, c.name category_name, c.id cat_id FROM products p LEFT JOIN brands b ON b.id=p.brand_id LEFT JOIN categories c ON c.id=p.category_id WHERE p.id=? AND p.is_active=1");
$st->execute([$id]);
$p = $st->fetch();
if (!$p) { http_response_code(404); flash('error','Produk tidak ditemukan.'); redirect('customer/products.php'); }
$pageTitle = $p['name'];

$ss = stock_status((int)$p['stock_qty']);
$isPromo = has_promo($p);
$price = effective_price($p);
$out = (int)$p['stock_qty'] <= 0;

$related = [];
if ($p['cat_id']) {
    $rs = db()->prepare("SELECT p.*, b.name brand_name FROM products p LEFT JOIN brands b ON b.id=p.brand_id WHERE p.category_id=? AND p.id<>? AND p.is_active=1 ORDER BY RAND() LIMIT 4");
    $rs->execute([$p['cat_id'], $p['id']]);
    $related = $rs->fetchAll();
}
require __DIR__ . '/../includes/header.php';
?>
<div class="container my-4">
  <nav class="small mb-3"><a href="<?= url('customer/products.php') ?>" class="text-muted-chipi"><i class="fa-solid fa-arrow-left me-1"></i>Kembali ke produk</a></nav>
  <div class="card p-3 p-md-4">
    <div class="row g-4">
      <div class="col-md-5">
        <div class="product-thumb rounded-4">
          <?php if ($p['label'] && $p['label']!=='NONE'): ?><span class="plabel <?= $p['label']==='BEST SELLER'?'BEST':e($p['label']) ?>"><?= e($p['label']) ?></span><?php endif; ?>
          <img src="<?= product_image_url($p['image']) ?>" alt="<?= e($p['name']) ?>">
        </div>
      </div>
      <div class="col-md-7">
        <div class="text-muted-chipi small mb-1"><?= e($p['brand_name']) ?> · <?= e($p['category_name']) ?></div>
        <h3 class="brand-font mb-1" data-testid="product-detail-name"><?= e($p['name']) ?></h3>
        <div class="text-muted-chipi small mb-2">SKU: <?= e($p['sku']) ?></div>
        <div class="mb-2">
          <?php if ($isPromo): ?>
            <span class="price-now fs-3"><?= rupiah($price) ?></span>
            <span class="price-old ms-2"><?= rupiah($p['price']) ?></span>
          <?php else: ?>
            <span class="price-now fs-3"><?= rupiah($price) ?></span>
          <?php endif; ?>
        </div>
        <span class="stock-badge <?= $ss['class'] ?>"><?= $ss['label'] ?></span>
        <div class="row g-2 mt-3 mb-1 small">
          <div class="col-6"><i class="fa-solid fa-weight-hanging text-primary me-1"></i>Berat: <b><?= e($p['weight'] ?: '-') ?></b></div>
          <div class="col-6"><i class="fa-solid fa-box text-primary me-1"></i>Satuan: <b><?= e($p['unit']) ?></b></div>
        </div>
        <p class="text-muted-chipi mt-2"><?= nl2br(e($p['description'])) ?></p>

        <?php if (!$out): ?>
        <div data-card class="d-flex align-items-center gap-3 mt-3 flex-wrap">
          <div class="qty-stepper">
            <button type="button" data-qty-dec>&minus;</button>
            <input class="qty-input" type="text" value="1" inputmode="numeric" readonly>
            <button type="button" data-qty-inc>+</button>
          </div>
          <button class="btn btn-outline-chipi" data-add-cart="<?= (int)$p['id'] ?>" data-testid="detail-add-cart"><i class="fa-solid fa-cart-plus me-1"></i>Tambah ke Keranjang</button>
          <a href="<?= url('customer/buy-now.php?id='.(int)$p['id']) ?>" class="btn btn-chipi" data-testid="detail-buy-now"><i class="fa-solid fa-bolt me-1"></i>Beli Sekarang</a>
        </div>
        <?php else: ?>
          <button class="btn btn-secondary mt-3" disabled>Stok Habis</button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($related): ?>
  <section class="mt-4">
    <h5 class="section-title brand-font">Produk Terkait</h5>
    <div class="row row-cols-2 row-cols-md-4 g-3">
      <?php foreach ($related as $p): ?><div class="col"><?php require __DIR__ . '/../includes/product_card.php'; ?></div><?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
