<?php
require_once __DIR__ . '/../includes/functions.php';
$page = 'cart';
$pageTitle = 'Keranjang';

$d = cart_detail();
$__m = shipping_methods(true);
$shipping = $__m ? (float)$__m[0]['cost'] : (float)setting('shipping_cost', 0);
require __DIR__ . '/../includes/header.php';
?>
<div class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="section-title brand-font mb-0">Keranjang Belanja</h4>
    <?php if ($d['items']): ?>
      <form method="post" action="<?= url('customer/cart-action.php?action=clear') ?>" onsubmit="return false;">
        <button type="button" class="btn btn-outline-danger btn-sm" id="clearCartBtn" data-testid="clear-cart"><i class="fa-solid fa-trash me-1"></i>Kosongkan</button>
      </form>
    <?php endif; ?>
  </div>

  <?php if (!$d['items']): ?>
    <div class="empty-state">
      <i class="fa-solid fa-cart-shopping d-block"></i>
      Keranjang masih kosong.
      <div class="mt-3"><a href="<?= url('customer/products.php') ?>" class="btn btn-chipi">Mulai Belanja</a></div>
    </div>
  <?php else: ?>
    <div class="row g-3">
      <div class="col-lg-8">
        <div class="card p-2 p-md-3">
          <?php foreach ($d['items'] as $it): $p = $it['product']; ?>
            <div class="d-flex gap-3 py-3 border-bottom cart-row" data-id="<?= (int)$p['id'] ?>">
              <img src="<?= product_image_url($p['image']) ?>" class="prod-thumb-sm" style="width:64px;height:64px" alt="">
              <div class="flex-grow-1">
                <a href="<?= url('customer/product.php?id='.(int)$p['id']) ?>" class="fw-bold text-dark small d-block"><?= e($p['name']) ?></a>
                <div class="text-muted-chipi" style="font-size:.78rem"><?= e($p['sku']) ?></div>
                <div class="price-now"><?= rupiah($it['price']) ?></div>
              </div>
              <div class="text-end">
                <div class="qty-stepper mb-2">
                  <button type="button" class="cart-dec">&minus;</button>
                  <input class="cart-qty" type="text" value="<?= $it['qty'] ?>" readonly style="width:36px;text-align:center;border:none">
                  <button type="button" class="cart-inc">+</button>
                </div>
                <div class="fw-bold line-total"><?= rupiah($it['line']) ?></div>
                <button class="btn btn-link btn-sm text-danger p-0 cart-del"><i class="fa-solid fa-trash"></i></button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card p-3 sticky-lg-top" style="top:80px">
          <h6 class="brand-font">Ringkasan</h6>
          <div class="d-flex justify-content-between small mb-1"><span>Subtotal</span><b id="sumSubtotal"><?= rupiah($d['subtotal']) ?></b></div>
          <div class="d-flex justify-content-between small mb-1"><span>Estimasi Ongkir</span><b><?= rupiah($shipping) ?></b></div>
          <hr>
          <div class="d-flex justify-content-between mb-3"><span class="fw-bold">Total</span><b class="price-now" id="sumTotal"><?= rupiah($d['subtotal'] + $shipping) ?></b></div>
          <a href="<?= url('customer/checkout.php') ?>" class="btn btn-chipi w-100" data-testid="checkout-btn">Checkout <i class="fa-solid fa-arrow-right ms-1"></i></a>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- sticky mobile bar -->
<?php if ($d['items']): ?>
<div class="sticky-cart-bar">
  <div><b><?= $d['count'] ?></b> Produk<br><span class="price-now" id="barTotal"><?= rupiah($d['subtotal'] + $shipping) ?></span></div>
  <a href="<?= url('customer/checkout.php') ?>" class="btn btn-chipi px-4">Checkout</a>
</div>
<?php endif; ?>

<script>
const SHIP = <?= json_encode($shipping) ?>;
function post(action, body){ body.append('csrf', window.CHIPI_CSRF); return fetch(window.CHIPI_BASE+'/customer/cart-action.php?action='+action,{method:'POST',body}).then(r=>r.json()); }
function refreshTotals(sub){
  document.getElementById('sumSubtotal').textContent = sub;
}
document.querySelectorAll('.cart-row').forEach(function(row){
  const id = row.dataset.id;
  const qty = row.querySelector('.cart-qty');
  const upd = function(newQty){
    const fd=new FormData(); fd.append('product_id',id); fd.append('qty',newQty);
    post('update',fd).then(d=>{ if(d.ok) location.reload(); });
  };
  row.querySelector('.cart-inc').onclick=()=>upd(parseInt(qty.value)+1);
  row.querySelector('.cart-dec').onclick=()=>upd(Math.max(0,parseInt(qty.value)-1));
  row.querySelector('.cart-del').onclick=function(){ if(!confirm('Hapus produk ini?'))return; const fd=new FormData(); fd.append('product_id',id); post('remove',fd).then(()=>location.reload()); };
});
const clr=document.getElementById('clearCartBtn');
if(clr) clr.onclick=function(){ if(!confirm('Kosongkan seluruh keranjang?'))return; post('clear',new FormData()).then(()=>location.reload()); };
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
