<?php
require_once __DIR__ . '/../includes/functions.php';
$cust = require_customer();
$page = 'orders'; $accPage = 'orders.php'; $pageTitle = 'Pesanan Saya';

$filter = $_GET['status'] ?? '';
$sql = 'SELECT * FROM orders WHERE customer_id=?';
$params = [$cust['id']];
if ($filter && in_array($filter, order_statuses())) { $sql .= ' AND order_status=?'; $params[] = $filter; }
$sql .= ' ORDER BY id DESC';
$st = db()->prepare($sql); $st->execute($params);
$orders = $st->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="container my-4">
  <div class="row g-3">
    <div class="col-lg-3"><?php require __DIR__ . '/../includes/account_nav.php'; ?></div>
    <div class="col-lg-9">
      <div class="card p-3">
        <h5 class="brand-font">Pesanan Saya</h5>
        <div class="d-flex gap-2 overflow-auto pb-2 mb-2">
          <a href="?" class="btn btn-sm <?= $filter===''?'btn-chipi-blue':'btn-outline-chipi' ?>">Semua</a>
          <?php foreach (order_statuses() as $s): ?>
            <a href="?status=<?= urlencode($s) ?>" class="btn btn-sm text-nowrap <?= $filter===$s?'btn-chipi-blue':'btn-outline-chipi' ?>"><?= e($s) ?></a>
          <?php endforeach; ?>
        </div>
        <?php if (!$orders): ?>
          <div class="empty-state"><i class="fa-solid fa-box-open d-block"></i>Belum ada pesanan.</div>
        <?php else: foreach ($orders as $o): ?>
          <div class="d-flex justify-content-between align-items-center py-3 border-bottom gap-2" data-testid="order-row-<?= e($o['order_number']) ?>">
            <a href="<?= url('customer/order-detail.php?id='.$o['id']) ?>" class="d-flex justify-content-between align-items-center flex-grow-1 text-dark text-decoration-none gap-2">
              <div>
                <div class="fw-bold"><?= e($o['order_number']) ?></div>
                <div class="text-muted-chipi small"><?= date('d M Y H:i', strtotime($o['created_at'])) ?> · <?= e($o['payment_method']) ?></div>
              </div>
              <div class="text-end">
                <span class="status-badge <?= status_class($o['order_status']) ?>"><?= e($o['order_status']) ?></span>
                <div class="fw-bold mt-1"><?= rupiah($o['grand_total']) ?></div>
              </div>
            </a>
            <a href="<?= url('customer/repeat-order.php?id='.$o['id']) ?>" class="btn btn-outline-chipi btn-sm text-nowrap flex-shrink-0" data-testid="reorder-btn-<?= e($o['order_number']) ?>" title="Pesan Ulang"><i class="fa-solid fa-rotate-right"></i><span class="d-none d-md-inline ms-1">Pesan Ulang</span></a>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
