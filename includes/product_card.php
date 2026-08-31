<?php
/** Reusable product card. Expects $p (product row). */
$ss = stock_status((int)$p['stock_qty']);
$isPromo = has_promo($p);
$price = effective_price($p);
$out = (int)$p['stock_qty'] <= 0;
$label = $p['label'] ?? 'NONE';
?>
<div class="card product-card" data-card data-testid="product-card-<?= e($p['sku']) ?>">
  <a href="<?= url('customer/product.php?id=' . (int)$p['id']) ?>" class="product-thumb d-block">
    <?php if ($label && $label !== 'NONE'): ?>
      <span class="plabel <?= $label === 'BEST SELLER' ? 'BEST' : e($label) ?>"><?= e($label) ?></span>
    <?php endif; ?>
    <img src="<?= product_image_url($p['image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
  </a>
  <div class="product-body">
    <div class="product-brand"><?= e($p['brand_name'] ?? '') ?></div>
    <a href="<?= url('customer/product.php?id=' . (int)$p['id']) ?>" class="product-name text-dark"><?= e($p['name']) ?></a>
    <div class="mt-1 mb-2">
      <?php if ($isPromo): ?>
        <span class="price-now"><?= rupiah($price) ?></span>
        <span class="price-old ms-1"><?= rupiah($p['price']) ?></span>
      <?php else: ?>
        <span class="price-now"><?= rupiah($price) ?></span>
      <?php endif; ?>
    </div>
    <span class="stock-badge <?= $ss['class'] ?> mb-2 align-self-start"><?= $ss['label'] ?></span>
    <div class="mt-auto">
      <?php if ($out): ?>
        <button class="btn btn-secondary btn-sm w-100" disabled>Stok Habis</button>
      <?php else: ?>
        <div class="d-flex align-items-center gap-2 mb-2">
          <div class="qty-stepper">
            <button type="button" data-qty-dec>&minus;</button>
            <input class="qty-input" type="text" value="1" inputmode="numeric" readonly>
            <button type="button" data-qty-inc>+</button>
          </div>
        </div>
        <button class="btn btn-chipi btn-sm w-100" data-add-cart="<?= (int)$p['id'] ?>" data-testid="add-cart-<?= e($p['sku']) ?>">
          <i class="fa-solid fa-cart-plus"></i> Keranjang
        </button>
      <?php endif; ?>
    </div>
  </div>
</div>
