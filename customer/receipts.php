<?php
require_once __DIR__ . '/../includes/functions.php';
$cust = require_customer();
$page = 'account'; $accPage = 'receipts.php'; $pageTitle = 'Nota Saya';

$st = db()->prepare("SELECT * FROM orders WHERE customer_id=? AND receipt_image IS NOT NULL AND receipt_image<>'' ORDER BY id DESC");
$st->execute([$cust['id']]);
$orders = $st->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="container my-4">
  <div class="row g-3">
    <div class="col-lg-3"><?php require __DIR__ . '/../includes/account_nav.php'; ?></div>
    <div class="col-lg-9">
      <div class="card p-3">
        <h5 class="brand-font">Nota Saya</h5>
        <?php if (!$orders): ?>
          <div class="empty-state"><i class="fa-solid fa-file-image d-block"></i>Belum ada nota. Nota muncul setelah pesanan dikonfirmasi.</div>
        <?php else: ?>
          <div class="row row-cols-1 row-cols-md-2 g-3">
            <?php foreach ($orders as $o): $has = file_exists(RECEIPT_PATH.'/'.$o['receipt_image']); ?>
              <div class="col">
                <div class="card p-3 h-100">
                  <div class="d-flex justify-content-between mb-2"><b><?= e($o['order_number']) ?></b><span class="status-badge <?= status_class($o['order_status']) ?>"><?= e($o['order_status']) ?></span></div>
                  <?php if ($has): ?><img src="<?= url('uploads/receipts/'.$o['receipt_image']) ?>" class="img-fluid rounded-3 border mb-2"><?php endif; ?>
                  <div class="d-flex gap-2">
                    <a href="<?= url('uploads/receipts/'.$o['receipt_image']) ?>" target="_blank" class="btn btn-outline-chipi btn-sm flex-fill"><i class="fa-solid fa-eye me-1"></i>Lihat</a>
                    <a href="<?= url('uploads/receipts/'.$o['receipt_image']) ?>" download="<?= e($o['order_number']) ?>.png" class="btn btn-chipi btn-sm flex-fill"><i class="fa-solid fa-download me-1"></i>Unduh</a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
