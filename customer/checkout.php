<?php
require_once __DIR__ . '/../includes/functions.php';
$cust = require_customer();
$page = 'cart';
$pageTitle = 'Checkout';

$d = cart_detail();
if (!$d['items']) { flash('warning', 'Keranjang kosong.'); redirect('customer/products.php'); }

// addresses (multi) — default selected
$addresses = customer_addresses($cust['id']);
$addr = $addresses[0] ?? null; // default is first (ordered is_default DESC)

$methods = shipping_methods(true);
if (!$methods) $methods = [['name'=>'Delivery','cost'=>(float)setting('shipping_cost',0),'active'=>1]];
$shipping = (float)$methods[0]['cost'];

/* ---- Apply / validate promo ---- */
$promoError = '';
$appliedPromo = $_SESSION['checkout_promo'] ?? null;
$discount = 0;
function calc_discount(?array $promo, float $subtotal): float {
    if (!$promo) return 0;
    if ($subtotal < (float)$promo['min_purchase']) return 0;
    if ($promo['discount_type'] === 'percentage') return round($subtotal * (float)$promo['discount_value'] / 100);
    return min((float)$promo['discount_value'], $subtotal);
}

if (($_POST['do'] ?? '') === 'promo') {
    csrf_check();
    $code = strtoupper(trim($_POST['promo_code'] ?? ''));
    if ($code === '') { unset($_SESSION['checkout_promo']); $appliedPromo = null; }
    else {
        $ps = db()->prepare("SELECT * FROM promos WHERE code=? AND is_active=1 AND (start_date IS NULL OR start_date<=CURDATE()) AND (end_date IS NULL OR end_date>=CURDATE())");
        $ps->execute([$code]);
        $promo = $ps->fetch();
        if (!$promo) { $promoError = 'Kode promo tidak valid atau kedaluwarsa.'; }
        elseif ($d['subtotal'] < (float)$promo['min_purchase']) { $promoError = 'Minimal belanja ' . rupiah($promo['min_purchase']) . ' untuk kode ini.'; }
        else { $_SESSION['checkout_promo'] = $promo; $appliedPromo = $promo; flash('success','Kode promo diterapkan.'); }
    }
}
$discount = calc_discount($appliedPromo, $d['subtotal']);
if ($appliedPromo && $discount <= 0 && !$promoError) { unset($_SESSION['checkout_promo']); $appliedPromo = null; }

$grand = $d['subtotal'] - $discount + $shipping;

/* ---- Place order ---- */
if (($_POST['do'] ?? '') === 'place') {
    csrf_check();
    // resolve chosen address (validate ownership), fallback to default
    $addrId = (int)($_POST['address_id'] ?? 0);
    if ($addrId) {
        foreach ($addresses as $a) { if ((int)$a['id'] === $addrId) { $addr = $a; break; } }
    }
    if (!$addr) { flash('error','Lengkapi alamat pengiriman terlebih dahulu.'); redirect('customer/address.php?redirect=checkout'); }

    $payment  = in_array($_POST['payment'] ?? '', ['Transfer','COD','Bayar di Toko']) ? $_POST['payment'] : 'Transfer';
    $methodName = $_POST['delivery'] ?? '';
    $chosen = null;
    foreach ($methods as $m) { if ($m['name'] === $methodName) { $chosen = $m; break; } }
    if (!$chosen) $chosen = $methods[0];
    $delivery = $chosen['name'];
    $notes    = trim($_POST['notes'] ?? '');
    $ship     = (float)$chosen['cost'];
    $grandFinal = $d['subtotal'] - $discount + $ship;

    $pdo = db();
    try {
        $pdo->beginTransaction();
        // re-check stock
        foreach ($d['items'] as $it) {
            if ((int)$it['product']['stock_qty'] < $it['qty']) throw new Exception('Stok ' . $it['product']['name'] . ' tidak mencukupi.');
        }
        $orderNo = generate_order_number();
        $addrText = $addr['address'] . ', ' . $addr['city'] . ($addr['postal_code'] ? ' ' . $addr['postal_code'] : '') . ($addr['notes'] ? ' (' . $addr['notes'] . ')' : '');

        $ins = $pdo->prepare("INSERT INTO orders (order_number,customer_id,customer_name,customer_phone,customer_address,subtotal,discount,shipping_cost,grand_total,payment_method,delivery_method,order_status,notes,promo_code) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $ins->execute([$orderNo, $cust['id'], $addr['recipient_name'], $addr['whatsapp'], $addrText, $d['subtotal'], $discount, $ship, $grandFinal, $payment, $delivery, 'Menunggu Konfirmasi', $notes, $appliedPromo['code'] ?? null]);
        $orderId = (int)$pdo->lastInsertId();

        $item = $pdo->prepare("INSERT INTO order_items (order_id,product_id,sku,product_name,qty,price,subtotal) VALUES (?,?,?,?,?,?,?)");
        $decr = $pdo->prepare("UPDATE products SET stock_qty = GREATEST(0, stock_qty - ?) WHERE id=?");
        foreach ($d['items'] as $it) {
            $p = $it['product'];
            $item->execute([$orderId, $p['id'], $p['sku'], $p['name'], $it['qty'], $it['price'], $it['line']]);
            $decr->execute([$it['qty'], $p['id']]);
        }
        $pdo->commit();
        cart_clear();
        unset($_SESSION['checkout_promo']);
        flash('success', 'Pesanan berhasil dibuat! Nomor pesanan: ' . $orderNo);
        redirect('customer/order-detail.php?id=' . $orderId);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('error', 'Gagal membuat pesanan: ' . $e->getMessage());
    }
}

require __DIR__ . '/../includes/header.php';
?>
<div class="container my-4">
  <h4 class="section-title brand-font">Checkout</h4>
  <div class="row g-3">
    <div class="col-lg-7">
      <!-- Address -->
      <div class="card p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="brand-font mb-0"><i class="fa-solid fa-location-dot text-primary me-1"></i>Alamat Pengiriman</h6>
          <a href="<?= url('customer/address.php?redirect=checkout') ?>" class="btn btn-outline-chipi btn-sm" data-testid="checkout-manage-address">Kelola Alamat</a>
        </div>
        <?php if ($addresses): ?>
          <div class="vstack gap-2" data-testid="checkout-address-list">
            <?php foreach ($addresses as $i => $a): ?>
              <label class="border rounded-3 p-2 d-flex gap-2 align-items-start" style="cursor:pointer" data-testid="checkout-address-<?= (int)$a['id'] ?>">
                <input type="radio" name="address_id" value="<?= (int)$a['id'] ?>" form="placeForm" class="mt-1" data-testid="checkout-address-radio-<?= (int)$a['id'] ?>" <?= $i===0?'checked':'' ?>>
                <div class="small">
                  <div class="mb-1">
                    <span class="badge" style="background:#0e2a49"><?= e($a['label']) ?></span>
                    <?php if ($a['is_default']): ?><span class="badge bg-success">Utama</span><?php endif; ?>
                  </div>
                  <b><?= e($a['recipient_name']) ?></b> · <?= e($a['whatsapp']) ?><br>
                  <span class="text-muted-chipi"><?= e($a['address']) ?>, <?= e($a['city']) ?> <?= e($a['postal_code']) ?><?= $a['notes'] ? ' — '.e($a['notes']) : '' ?></span>
                </div>
              </label>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="alert alert-warning mb-0">Belum ada alamat. <a href="<?= url('customer/address.php?redirect=checkout') ?>">Tambahkan alamat</a>.</div>
        <?php endif; ?>
      </div>

      <!-- Items -->
      <div class="card p-3 mb-3">
        <h6 class="brand-font"><i class="fa-solid fa-box me-1 text-primary"></i>Item Pesanan</h6>
        <?php foreach ($d['items'] as $it): $p=$it['product']; ?>
          <div class="d-flex justify-content-between py-2 border-bottom small">
            <div><?= e($p['name']) ?> <span class="text-muted-chipi">× <?= $it['qty'] ?></span></div>
            <b><?= rupiah($it['line']) ?></b>
          </div>
        <?php endforeach; ?>
      </div>

      <form method="post" id="placeForm">
        <?= csrf_field() ?>
        <input type="hidden" name="do" value="place">
        <div class="card p-3 mb-3">
          <h6 class="brand-font">Metode Pengiriman</h6>
          <div class="d-flex gap-2 flex-wrap">
            <?php foreach ($methods as $i => $m): ?>
              <label class="btn btn-outline-chipi flex-fill" data-testid="ship-option-<?= $i ?>">
                <input type="radio" name="delivery" value="<?= e($m['name']) ?>" <?= $i===0?'checked':'' ?> class="me-1" data-ship="<?= (float)$m['cost'] ?>">
                <?= e($m['name']) ?> <span class="small text-muted-chipi">· <?= (float)$m['cost']>0 ? rupiah($m['cost']) : 'Gratis' ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <h6 class="brand-font mt-3">Metode Pembayaran</h6>
          <div class="d-flex gap-2 flex-wrap">
            <label class="btn btn-outline-chipi flex-fill"><input type="radio" name="payment" value="Transfer" checked class="me-1 pay-radio">Transfer</label>
            <label class="btn btn-outline-chipi flex-fill"><input type="radio" name="payment" value="COD" class="me-1 pay-radio">COD</label>
            <label class="btn btn-outline-chipi flex-fill"><input type="radio" name="payment" value="Bayar di Toko" class="me-1 pay-radio">Bayar di Toko</label>
          </div>
          <?php if (setting('bank_name') || setting('bank_account')): ?>
          <div id="bankInfo" class="alert alert-info mt-3 mb-0 small" data-testid="bank-info">
            <b><i class="fa-solid fa-building-columns me-1"></i>Transfer ke rekening berikut:</b><br>
            <?= e(setting('bank_name')) ?> — <b id="bankAcct"><?= e(setting('bank_account')) ?></b>
            <button type="button" class="btn btn-sm btn-chipi-blue py-0 px-2 ms-1 align-baseline" id="copyBankBtn" data-acct="<?= e(setting('bank_account')) ?>" data-testid="copy-bank-btn"><i class="fa-regular fa-copy me-1"></i>Salin</button>
            <?= setting('bank_holder') ? '<br>a.n. '.e(setting('bank_holder')) : '' ?><br>
            <span class="text-muted-chipi">Unggah bukti transfer di halaman pesanan setelah checkout untuk dikonfirmasi admin.</span>
          </div>
          <?php endif; ?>
          <h6 class="brand-font mt-3">Catatan Pesanan</h6>
          <textarea class="form-control" name="notes" rows="2" placeholder="Contoh: tolong titip di satpam" data-testid="order-notes"></textarea>
        </div>
      </form>
    </div>

    <div class="col-lg-5">
      <div class="card p-3 sticky-lg-top" style="top:80px">
        <h6 class="brand-font">Ringkasan Pembayaran</h6>
        <form method="post" class="mb-2">
          <?= csrf_field() ?>
          <input type="hidden" name="do" value="promo">
          <div class="input-group input-group-sm">
            <input class="form-control" name="promo_code" placeholder="Kode promo" value="<?= e($appliedPromo['code'] ?? '') ?>" data-testid="promo-input">
            <button class="btn btn-chipi-blue" data-testid="promo-apply">Pakai</button>
          </div>
          <?php if ($promoError): ?><div class="text-danger small mt-1" data-testid="promo-error"><?= e($promoError) ?></div><?php endif; ?>
          <?php if ($appliedPromo && $discount>0): ?><div class="text-success small mt-1"><i class="fa-solid fa-check"></i> Promo <?= e($appliedPromo['code']) ?> aktif</div><?php endif; ?>
        </form>
        <div class="d-flex justify-content-between small mb-1"><span>Subtotal (<?= $d['count'] ?> item)</span><b><?= rupiah($d['subtotal']) ?></b></div>
        <div class="d-flex justify-content-between small mb-1"><span>Diskon</span><b class="text-success">- <?= rupiah($discount) ?></b></div>
        <div class="d-flex justify-content-between small mb-1"><span>Ongkir</span><b id="shipCell"><?= rupiah($shipping) ?></b></div>
        <hr>
        <div class="d-flex justify-content-between mb-3"><span class="fw-bold">Grand Total</span><b class="price-now fs-5" id="grandCell"><?= rupiah($grand) ?></b></div>
        <button type="submit" form="placeForm" class="btn btn-chipi w-100" data-testid="place-order-btn" <?= $addr?'':'disabled' ?>><i class="fa-solid fa-circle-check me-1"></i>Buat Pesanan</button>
      </div>
    </div>
  </div>
</div>
<script>
const SUB=<?= json_encode($d['subtotal']) ?>, DISC=<?= json_encode($discount) ?>;
function fmt(n){return 'Rp'+n.toLocaleString('id-ID');}
document.querySelectorAll('input[name=delivery]').forEach(r=>r.addEventListener('change',function(){
  const ship=parseFloat(this.dataset.ship)||0;
  document.getElementById('shipCell').textContent=fmt(ship);
  document.getElementById('grandCell').textContent=fmt(SUB-DISC+ship);
}));
(function(){
  const bank=document.getElementById('bankInfo');
  if(!bank) return;
  function toggleBank(){
    const sel=document.querySelector('input[name=payment]:checked');
    bank.style.display=(sel && sel.value==='Transfer')?'':'none';
  }
  document.querySelectorAll('.pay-radio').forEach(r=>r.addEventListener('change',toggleBank));
  toggleBank();
  const cb=document.getElementById('copyBankBtn');
  if(cb) cb.addEventListener('click',function(){
    const acct=(this.dataset.acct||'').replace(/\s/g,'');
    const self=this;
    function done(){const o='<i class="fa-regular fa-copy me-1"></i>Salin';self.innerHTML='<i class="fa-solid fa-check me-1"></i>Tersalin';setTimeout(function(){self.innerHTML=o;},1500);}
    function fallback(){const t=document.createElement('textarea');t.value=acct;t.style.position='fixed';t.style.opacity='0';document.body.appendChild(t);t.focus();t.select();try{document.execCommand('copy');}catch(e){}document.body.removeChild(t);done();}
    if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(acct).then(done).catch(fallback);}
    else{fallback();}
  });
})();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
