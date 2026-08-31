<?php
require_once __DIR__ . '/../includes/functions.php';
$cust = require_customer();
$page = 'orders';

$id = (int)($_GET['id'] ?? 0);
$data = reorder_analyze($id, $cust['id']);
if (!$data) { http_response_code(404); flash('error', 'Pesanan tidak ditemukan.'); redirect('customer/orders.php'); }

// ---- Confirm: add available items to cart ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'add_cart') {
    csrf_check();
    $fresh = reorder_analyze($id, $cust['id']); // re-check (authoritative) at submit time
    $added = 0; $skipped = 0;
    foreach ($fresh['items'] as $it) {
        if (!empty($it['available']) && (int)$it['add_qty'] > 0) {
            cart_add((int)$it['product_id'], (int)$it['add_qty']);
            $added++;
        } else { $skipped++; }
    }
    if ($added > 0) {
        $msg = "$added produk ditambahkan ke keranjang.";
        if ($skipped > 0) $msg .= " $skipped produk dilewati (habis/tidak tersedia).";
        flash('success', $msg);
        redirect('customer/cart.php');
    } else {
        flash('error', 'Tidak ada produk yang bisa ditambahkan. Semua produk habis atau tidak tersedia.');
        redirect('customer/repeat-order.php?id=' . $id);
    }
}

$o = $data['order'];
$pageTitle = 'Pesan Ulang · ' . $o['order_number'];

function reorder_badge(array $it): string {
    switch ($it['status']) {
        case 'unavailable':   return '<span class="badge bg-secondary"><i class="fa-solid fa-ban me-1"></i>Tidak tersedia lagi</span>';
        case 'out_of_stock':  return '<span class="badge bg-danger"><i class="fa-solid fa-box-open me-1"></i>Stok habis</span>';
        case 'stock_limited': return '<span class="badge bg-warning text-dark"><i class="fa-solid fa-triangle-exclamation me-1"></i>Stok terbatas</span>';
        case 'price_up':      return '<span class="badge bg-warning text-dark"><i class="fa-solid fa-arrow-trend-up me-1"></i>Harga naik</span>';
        case 'price_down':    return '<span class="badge" style="background:#1b8a4b"><i class="fa-solid fa-arrow-trend-down me-1"></i>Harga turun</span>';
        default:              return '<span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>Siap</span>';
    }
}

require __DIR__ . '/../includes/header.php';
?>
<div class="container my-4">
  <a href="<?= url('customer/order-detail.php?id=' . $id) ?>" class="small text-muted-chipi" data-testid="reorder-back"><i class="fa-solid fa-arrow-left me-1"></i>Kembali ke Pesanan</a>
  <div class="card p-3 p-md-4 mt-2" data-testid="reorder-review">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
      <div>
        <h5 class="brand-font mb-0"><i class="fa-solid fa-rotate-right me-2 text-primary"></i>Pesan Ulang</h5>
        <div class="text-muted-chipi small">Dari pesanan <b><?= e($o['order_number']) ?></b> · <?= date('d M Y', strtotime($o['created_at'])) ?></div>
      </div>
    </div>

    <?php if ($data['has_warning']): ?>
      <div class="alert alert-warning py-2 small mt-3 mb-0" data-testid="reorder-warning">
        <i class="fa-solid fa-triangle-exclamation me-1"></i>Beberapa produk mengalami perubahan harga, stok terbatas, atau sudah tidak tersedia. Periksa detail di bawah sebelum menambahkan ke keranjang.
      </div>
    <?php else: ?>
      <div class="alert alert-success py-2 small mt-3 mb-0" data-testid="reorder-ok">
        <i class="fa-solid fa-circle-check me-1"></i>Semua produk masih tersedia dengan harga yang sama. Siap dipesan ulang!
      </div>
    <?php endif; ?>

    <div class="mt-3">
      <?php foreach ($data['items'] as $it): ?>
        <div class="d-flex gap-3 py-3 border-bottom <?= empty($it['available']) ? 'opacity-75' : '' ?>" data-testid="reorder-item">
          <img src="<?= e($it['image']) ?>" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:12px" class="border flex-shrink-0">
          <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
              <div>
                <div class="fw-bold small"><?= e($it['name']) ?></div>
                <div class="text-muted-chipi" style="font-size:.72rem"><?= e($it['sku']) ?></div>
              </div>
              <?= reorder_badge($it) ?>
            </div>
            <div class="small mt-1">
              <?php if (!empty($it['available'])): ?>
                <?php if (in_array('price_up', $it['notes'] ?? []) || in_array('price_down', $it['notes'] ?? [])): ?>
                  Harga: <span class="text-muted-chipi text-decoration-line-through"><?= rupiah($it['old_price']) ?></span>
                  <b class="price-now ms-1"><?= rupiah($it['new_price']) ?></b>
                <?php else: ?>
                  Harga: <b><?= rupiah($it['new_price']) ?></b>
                <?php endif; ?>
                <span class="text-muted-chipi ms-2">·</span>
                <?php if (in_array('stock_limited', $it['notes'] ?? [])): ?>
                  <span class="text-warning-emphasis ms-2">Dipesan <?= $it['old_qty'] ?>, ditambahkan <b><?= $it['add_qty'] ?></b> (sisa stok)</span>
                <?php else: ?>
                  <span class="ms-2">Jumlah: <b><?= $it['add_qty'] ?></b></span>
                <?php endif; ?>
              <?php else: ?>
                <span class="text-muted-chipi">Sebelumnya dipesan <?= $it['old_qty'] ?> × <?= rupiah($it['old_price']) ?> — tidak dapat ditambahkan.</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="d-flex gap-2 mt-3 flex-wrap">
      <?php if ($data['any_available']): ?>
        <form method="post" class="d-inline">
          <?= csrf_field() ?>
          <input type="hidden" name="do" value="add_cart">
          <button class="btn btn-chipi" data-testid="reorder-add-cart"><i class="fa-solid fa-cart-plus me-1"></i>Tambah Semua yang Tersedia ke Keranjang</button>
        </form>
      <?php else: ?>
        <button class="btn btn-chipi" disabled data-testid="reorder-add-cart-disabled"><i class="fa-solid fa-cart-plus me-1"></i>Tidak Ada Produk Tersedia</button>
      <?php endif; ?>
      <a href="<?= url('customer/products.php') ?>" class="btn btn-outline-chipi"><i class="fa-solid fa-store me-1"></i>Lihat Katalog</a>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
