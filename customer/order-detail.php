<?php
require_once __DIR__ . '/../includes/functions.php';
$cust = require_customer();
$page = 'orders';

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM orders WHERE id=? AND customer_id=?');
$st->execute([$id, $cust['id']]);
$o = $st->fetch();
if (!$o) { http_response_code(404); flash('error','Pesanan tidak ditemukan.'); redirect('customer/orders.php'); }

// ---- Upload bukti transfer (butuh konfirmasi admin) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'upload_proof') {
    csrf_check();
    if ($o['payment_method'] === 'Transfer' && $o['order_status'] !== 'Dibatalkan' && $o['payment_status'] !== 'paid') {
        if (!empty($_FILES['proof']['name']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
            $info = @getimagesize($_FILES['proof']['tmp_name']);
            $map = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            if ($info && isset($map[$info['mime']]) && $_FILES['proof']['size'] <= 3*1024*1024) {
                if (!is_dir(PROOF_PATH)) @mkdir(PROOF_PATH, 0775, true);
                if ($o['payment_proof'] && file_exists(PROOF_PATH.'/'.$o['payment_proof'])) @unlink(PROOF_PATH.'/'.$o['payment_proof']);
                $fn = $o['order_number'].'_'.time().'.'.$map[$info['mime']];
                move_uploaded_file($_FILES['proof']['tmp_name'], PROOF_PATH.'/'.$fn);
                db()->prepare("UPDATE orders SET payment_proof=?, payment_status='pending' WHERE id=?")->execute([$fn, $id]);
                flash('success', 'Bukti transfer terkirim. Menunggu konfirmasi admin.');
            } else { flash('error', 'File tidak valid (JPG/PNG/WEBP, maks 3MB).'); }
        } else { flash('error', 'Pilih file bukti transfer terlebih dahulu.'); }
    }
    redirect('customer/order-detail.php?id='.$id);
}
$pageTitle = $o['order_number'];

$items = db()->prepare('SELECT * FROM order_items WHERE order_id=?'); $items->execute([$id]); $items = $items->fetchAll();

$flow = ['Menunggu Konfirmasi','Dikonfirmasi','Diproses','Dikirim','Selesai'];
$cancelled = $o['order_status'] === 'Dibatalkan';
$currentIdx = array_search($o['order_status'], $flow);
require __DIR__ . '/../includes/header.php';
?>
<div class="container my-4">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <a href="<?= url('customer/orders.php') ?>" class="small text-muted-chipi"><i class="fa-solid fa-arrow-left me-1"></i>Kembali</a>
    <a href="<?= url('customer/repeat-order.php?id=' . $id) ?>" class="btn btn-chipi btn-sm" data-testid="reorder-btn"><i class="fa-solid fa-rotate-right me-1"></i>Pesan Ulang</a>
  </div>
  <div class="row g-3 mt-1">
    <div class="col-lg-8">
      <div class="card p-3 mb-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
          <div>
            <h5 class="brand-font mb-0"><?= e($o['order_number']) ?></h5>
            <div class="text-muted-chipi small"><?= date('d M Y H:i', strtotime($o['created_at'])) ?></div>
          </div>
          <span class="status-badge <?= status_class($o['order_status']) ?>" data-testid="order-status"><?= e($o['order_status']) ?></span>
        </div>
        <hr>
        <h6 class="brand-font">Status Pesanan</h6>
        <?php if ($cancelled): ?>
          <div class="alert alert-danger py-2 mb-0"><i class="fa-solid fa-circle-xmark me-1"></i>Pesanan dibatalkan.</div>
        <?php else: ?>
          <ul class="timeline">
            <?php foreach ($flow as $i => $s): ?>
              <li class="<?= $i <= $currentIdx ? 'done' : '' ?>"><?= e($s) ?><?php if ($s==='Dikonfirmasi' && $o['confirmed_at']): ?> <span class="text-muted-chipi small">· <?= date('d M H:i', strtotime($o['confirmed_at'])) ?></span><?php endif; ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <div class="card p-3">
        <h6 class="brand-font">Item Pesanan</h6>
        <?php foreach ($items as $it): ?>
          <div class="d-flex justify-content-between py-2 border-bottom small">
            <div><?= e($it['product_name']) ?> <span class="text-muted-chipi">× <?= $it['qty'] ?></span><br><span class="text-muted-chipi" style="font-size:.72rem"><?= e($it['sku']) ?> · <?= rupiah($it['price']) ?></span></div>
            <b><?= rupiah($it['subtotal']) ?></b>
          </div>
        <?php endforeach; ?>
        <div class="mt-3">
          <div class="d-flex justify-content-between small"><span>Subtotal</span><span><?= rupiah($o['subtotal']) ?></span></div>
          <div class="d-flex justify-content-between small"><span>Diskon</span><span class="text-success">- <?= rupiah($o['discount']) ?></span></div>
          <div class="d-flex justify-content-between small"><span>Ongkir</span><span><?= rupiah($o['shipping_cost']) ?></span></div>
          <div class="d-flex justify-content-between fw-bold mt-1"><span>Grand Total</span><span class="price-now"><?= rupiah($o['grand_total']) ?></span></div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card p-3 mb-3">
        <h6 class="brand-font">Pengiriman</h6>
        <div class="small"><b><?= e($o['customer_name']) ?></b> · <?= e($o['customer_phone']) ?></div>
        <div class="small text-muted-chipi"><?= e($o['customer_address']) ?></div>
        <hr class="my-2">
        <div class="small">Metode: <b><?= e($o['delivery_method']) ?></b></div>
        <div class="small">Pembayaran: <b><?= e($o['payment_method']) ?></b></div>
        <?php if ($o['notes']): ?><div class="small">Catatan: <?= e($o['notes']) ?></div><?php endif; ?>
      </div>

      <?php $pl = payment_label($o['payment_status']); ?>
      <div class="card p-3 mb-3" data-testid="payment-card">
        <h6 class="brand-font">Pembayaran</h6>
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="small">Status</span>
          <span class="badge <?= $pl['class'] ?>" data-testid="cust-payment-status"><?= $pl['label'] ?></span>
        </div>
        <?php if ($o['payment_method'] === 'Transfer' && $o['order_status'] !== 'Dibatalkan'): ?>
          <?php if ($o['payment_status'] === 'paid'): ?>
            <div class="alert alert-success py-2 small mb-0"><i class="fa-solid fa-circle-check me-1"></i>Pembayaran telah dikonfirmasi admin.</div>
          <?php else: ?>
            <?php if ($o['payment_status'] === 'pending'): ?>
              <div class="alert alert-warning py-2 small"><i class="fa-solid fa-hourglass-half me-1"></i>Bukti transfer sedang diperiksa admin.</div>
            <?php else: ?>
              <p class="small text-muted-chipi mb-2">Sudah transfer? Unggah bukti pembayaran, lalu admin akan mengonfirmasi.</p>
            <?php endif; ?>
            <?php if ($o['payment_proof'] && file_exists(PROOF_PATH.'/'.$o['payment_proof'])): ?>
              <img src="<?= url('uploads/proofs/'.$o['payment_proof']) ?>" class="img-fluid rounded-3 border mb-2" alt="Bukti Transfer">
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data">
              <?= csrf_field() ?><input type="hidden" name="do" value="upload_proof">
              <input class="form-control form-control-sm mb-2" type="file" name="proof" accept="image/*" required data-testid="proof-file">
              <button class="btn btn-chipi btn-sm w-100" data-testid="proof-upload"><i class="fa-solid fa-upload me-1"></i><?= $o['payment_proof'] ? 'Ganti Bukti Transfer' : 'Kirim Bukti Transfer' ?></button>
            </form>
          <?php endif; ?>
        <?php else: ?>
          <p class="small text-muted-chipi mb-0">Metode: <b><?= e($o['payment_method']) ?></b>.</p>
        <?php endif; ?>
      </div>

      <div class="card p-3">
        <h6 class="brand-font">Nota Pesanan</h6>
        <?php if ($o['receipt_image'] && file_exists(RECEIPT_PATH.'/'.$o['receipt_image'])): ?>
          <img src="<?= url('uploads/receipts/'.$o['receipt_image']) ?>" class="img-fluid rounded-3 border mb-2" alt="Nota">
          <div class="d-grid gap-2">
            <a href="<?= url('uploads/receipts/'.$o['receipt_image']) ?>" target="_blank" class="btn btn-outline-chipi btn-sm" data-testid="view-receipt"><i class="fa-solid fa-eye me-1"></i>Lihat Nota</a>
            <a href="<?= url('uploads/receipts/'.$o['receipt_image']) ?>" download="<?= e($o['order_number']) ?>.png" class="btn btn-chipi btn-sm" data-testid="download-receipt"><i class="fa-solid fa-download me-1"></i>Download Nota</a>
          </div>
        <?php else: ?>
          <p class="text-muted-chipi small mb-0"><i class="fa-solid fa-clock me-1"></i>Nota akan tersedia setelah pesanan dikonfirmasi admin.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
