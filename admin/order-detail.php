<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/receipt.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM orders WHERE id=?'); $st->execute([$id]);
$o = $st->fetch();
if (!$o) { flash('error','Pesanan tidak ditemukan.'); redirect('admin/orders.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $do = $_POST['do'] ?? '';
    if ($do === 'confirm' && $o['order_status'] === 'Menunggu Konfirmasi') {
        db()->prepare("UPDATE orders SET order_status='Dikonfirmasi', confirmed_at=NOW() WHERE id=?")->execute([$id]);
        $fn = generate_receipt($id);
        flash('success', $fn ? 'Pesanan dikonfirmasi & nota berhasil dibuat.' : 'Pesanan dikonfirmasi. (Gagal membuat gambar nota - cek ekstensi GD)');
        redirect('admin/order-detail.php?id='.$id);
    }
    if ($do === 'status') {
        $ns = $_POST['order_status'] ?? '';
        if (in_array($ns, order_statuses())) {
            db()->prepare('UPDATE orders SET order_status=? WHERE id=?')->execute([$ns, $id]);
            if (in_array($ns, ['Dikonfirmasi','Diproses','Dikirim','Selesai']) && !$o['confirmed_at']) {
                db()->prepare('UPDATE orders SET confirmed_at=NOW() WHERE id=?')->execute([$id]);
            }
            // (re)generate receipt so the watermark (PAID / NOT PAID / CANCELED) follows status
            if (!empty($o['receipt_image']) || in_array($ns, ['Dikonfirmasi','Diproses','Dikirim','Selesai'])) {
                generate_receipt($id);
            }
            flash('success','Status pesanan diperbarui menjadi '.$ns.'.');
        }
        redirect('admin/order-detail.php?id='.$id);
    }
    if ($do === 'cancel') {
        db()->prepare("UPDATE orders SET order_status='Dibatalkan' WHERE id=?")->execute([$id]);
        // update watermark to CANCELED if a receipt already exists
        if (!empty($o['receipt_image'])) generate_receipt($id);
        flash('success','Pesanan dibatalkan.');
        redirect('admin/order-detail.php?id='.$id);
    }
    if ($do === 'confirm_payment') {
        db()->prepare("UPDATE orders SET payment_status='paid' WHERE id=?")->execute([$id]);
        if (!empty($o['receipt_image']) || $o['confirmed_at']) generate_receipt($id);
        flash('success','Pembayaran dikonfirmasi. Nota diperbarui menjadi LUNAS (PAID).');
        redirect('admin/order-detail.php?id='.$id);
    }
    if ($do === 'unpay') {
        db()->prepare("UPDATE orders SET payment_status='unpaid' WHERE id=?")->execute([$id]);
        if (!empty($o['receipt_image'])) generate_receipt($id);
        flash('success','Pembayaran ditandai belum lunas.');
        redirect('admin/order-detail.php?id='.$id);
    }
}

$st = db()->prepare('SELECT * FROM orders WHERE id=?'); $st->execute([$id]); $o = $st->fetch();
$items = db()->prepare('SELECT * FROM order_items WHERE order_id=?'); $items->execute([$id]); $items = $items->fetchAll();
$pageTitle = 'Pesanan '.$o['order_number'];

// WhatsApp message
$waPhone = preg_replace('/[^0-9]/', '', $o['customer_phone']);
if (str_starts_with($waPhone, '0')) $waPhone = '62'.substr($waPhone,1);
$waMsg = render_template('tpl_order_confirm', [
    'name'         => $o['customer_name'],
    'order_number' => $o['order_number'],
    'total'        => rupiah($o['grand_total']),
]);
$waLink = 'https://wa.me/'.$waPhone.'?text='.rawurlencode($waMsg);

require __DIR__ . '/includes/admin_header.php';
?>
<a href="<?= url('admin/orders.php') ?>" class="small text-muted-chipi"><i class="fa-solid fa-arrow-left me-1"></i>Kembali</a>
<div class="row g-3 mt-1">
  <div class="col-lg-8">
    <div class="card p-3 mb-3">
      <div class="d-flex justify-content-between align-items-start flex-wrap">
        <div><h5 class="brand-font mb-0" data-testid="admin-order-number"><?= e($o['order_number']) ?></h5><div class="text-muted-chipi small"><?= date('d M Y H:i', strtotime($o['created_at'])) ?></div></div>
        <span class="status-badge <?= status_class($o['order_status']) ?>" data-testid="admin-order-status"><?= e($o['order_status']) ?></span>
      </div>
      <hr>
      <div class="row">
        <div class="col-md-6">
          <h6 class="brand-font">Pelanggan</h6>
          <div class="small"><b><?= e($o['customer_name']) ?></b></div>
          <div class="small text-muted-chipi"><?= e($o['customer_phone']) ?></div>
          <div class="small text-muted-chipi"><?= e($o['customer_address']) ?></div>
        </div>
        <div class="col-md-6">
          <h6 class="brand-font">Pesanan</h6>
          <div class="small">Pengiriman: <b><?= e($o['delivery_method']) ?></b></div>
          <div class="small">Pembayaran: <b><?= e($o['payment_method']) ?></b></div>
          <?php if($o['promo_code']): ?><div class="small">Promo: <b><?= e($o['promo_code']) ?></b></div><?php endif; ?>
          <?php if($o['notes']): ?><div class="small">Catatan: <?= e($o['notes']) ?></div><?php endif; ?>
        </div>
      </div>
    </div>

    <div class="card p-3">
      <h6 class="brand-font">Item Pesanan</h6>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead><tr><th>Produk</th><th>SKU</th><th class="text-center">Qty</th><th class="text-end">Harga</th><th class="text-end">Subtotal</th></tr></thead>
          <tbody>
          <?php foreach($items as $it): ?>
            <tr><td><?= e($it['product_name']) ?></td><td class="small text-muted-chipi"><?= e($it['sku']) ?></td><td class="text-center"><?= $it['qty'] ?></td><td class="text-end"><?= rupiah($it['price']) ?></td><td class="text-end fw-bold"><?= rupiah($it['subtotal']) ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="d-flex justify-content-end">
        <div style="min-width:260px">
          <div class="d-flex justify-content-between small"><span>Subtotal</span><span><?= rupiah($o['subtotal']) ?></span></div>
          <div class="d-flex justify-content-between small"><span>Diskon</span><span class="text-success">- <?= rupiah($o['discount']) ?></span></div>
          <div class="d-flex justify-content-between small"><span>Ongkir</span><span><?= rupiah($o['shipping_cost']) ?></span></div>
          <hr class="my-1">
          <div class="d-flex justify-content-between fw-bold"><span>Grand Total</span><span class="price-now"><?= rupiah($o['grand_total']) ?></span></div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card p-3 mb-3">
      <h6 class="brand-font">Aksi Pesanan</h6>
      <?php if ($o['order_status'] === 'Menunggu Konfirmasi'): ?>
        <form method="post" data-confirm="Konfirmasi pesanan ini? Nota akan dibuat otomatis.">
          <?= csrf_field() ?><input type="hidden" name="do" value="confirm">
          <button class="btn btn-chipi w-100 mb-2" data-testid="confirm-order-btn"><i class="fa-solid fa-circle-check me-1"></i>KONFIRMASI PESANAN</button>
        </form>
      <?php endif; ?>
      <form method="post" class="mb-2">
        <?= csrf_field() ?><input type="hidden" name="do" value="status">
        <label class="form-label small fw-bold">Ubah Status</label>
        <div class="input-group">
          <select class="form-select" name="order_status" data-testid="status-select">
            <?php foreach(order_statuses() as $s): ?><option value="<?= e($s) ?>" <?= $o['order_status']===$s?'selected':'' ?>><?= e($s) ?></option><?php endforeach; ?>
          </select>
          <button class="btn btn-chipi-blue" data-testid="update-status-btn">Simpan</button>
        </div>
      </form>
      <a href="<?= e($waLink) ?>" target="_blank" class="btn btn-success w-100 mb-2" data-testid="wa-btn"><i class="fa-brands fa-whatsapp me-1"></i>Kirim WhatsApp</a>
      <?php if ($o['order_status'] !== 'Dibatalkan' && $o['order_status'] !== 'Selesai'): ?>
        <form method="post" data-confirm="Batalkan pesanan ini?">
          <?= csrf_field() ?><input type="hidden" name="do" value="cancel">
          <button class="btn btn-outline-danger w-100" data-testid="cancel-order-btn"><i class="fa-solid fa-ban me-1"></i>Batalkan Pesanan</button>
        </form>
      <?php endif; ?>
    </div>

    <?php $pl = payment_label($o['payment_status']); ?>
    <div class="card p-3 mb-3" data-testid="admin-payment-card">
      <h6 class="brand-font">Pembayaran</h6>
      <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="small">Metode: <b><?= e($o['payment_method']) ?></b></span>
        <span class="badge <?= $pl['class'] ?>" data-testid="admin-payment-status"><?= $pl['label'] ?></span>
      </div>
      <?php if ($o['payment_proof'] && file_exists(PROOF_PATH.'/'.$o['payment_proof'])): ?>
        <p class="small text-muted-chipi mb-1">Bukti transfer dari pelanggan:</p>
        <a href="<?= url('uploads/proofs/'.$o['payment_proof']) ?>" target="_blank"><img src="<?= url('uploads/proofs/'.$o['payment_proof']) ?>" class="img-fluid rounded-3 border mb-2" data-testid="admin-proof-img" alt="Bukti Transfer"></a>
      <?php else: ?>
        <p class="small text-muted-chipi">Belum ada bukti transfer diunggah pelanggan.</p>
      <?php endif; ?>
      <?php if ($o['order_status'] !== 'Dibatalkan'): ?>
        <?php if ($o['payment_status'] !== 'paid'): ?>
          <form method="post" data-confirm="Konfirmasi pembayaran pesanan ini sebagai LUNAS? Nota akan diperbarui.">
            <?= csrf_field() ?><input type="hidden" name="do" value="confirm_payment">
            <button class="btn btn-success w-100" data-testid="confirm-payment-btn"><i class="fa-solid fa-money-check-dollar me-1"></i>Konfirmasi Pembayaran (Lunas)</button>
          </form>
        <?php else: ?>
          <div class="alert alert-success py-2 small mb-2"><i class="fa-solid fa-circle-check me-1"></i>Pembayaran sudah dikonfirmasi.</div>
          <form method="post" data-confirm="Tandai pembayaran sebagai belum lunas?">
            <?= csrf_field() ?><input type="hidden" name="do" value="unpay">
            <button class="btn btn-outline-secondary btn-sm w-100" data-testid="unpay-btn">Tandai Belum Lunas</button>
          </form>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <div class="card p-3">
      <h6 class="brand-font">Nota Pesanan</h6>
      <?php if ($o['receipt_image'] && file_exists(RECEIPT_PATH.'/'.$o['receipt_image'])): ?>
        <img src="<?= url('uploads/receipts/'.$o['receipt_image'].'?v='.filemtime(RECEIPT_PATH.'/'.$o['receipt_image'])) ?>" class="img-fluid rounded-3 border mb-2" data-testid="admin-receipt-img">
        <div class="d-grid gap-2">
          <a href="<?= url('uploads/receipts/'.$o['receipt_image']) ?>" target="_blank" class="btn btn-outline-chipi btn-sm"><i class="fa-solid fa-eye me-1"></i>Lihat Nota</a>
          <a href="<?= url('uploads/receipts/'.$o['receipt_image']) ?>" download="<?= e($o['order_number']) ?>.png" class="btn btn-chipi btn-sm"><i class="fa-solid fa-download me-1"></i>Download Nota</a>
        </div>
        <p class="text-muted-chipi small mt-2 mb-0">Unduh nota lalu lampirkan manual saat mengirim WhatsApp ke pelanggan.</p>
      <?php else: ?>
        <p class="text-muted-chipi small mb-0">Nota belum dibuat. Klik <b>KONFIRMASI PESANAN</b> untuk membuat nota otomatis.</p>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
