<?php
require_once __DIR__ . '/../includes/functions.php';
$cust = require_customer();
$page = 'account'; $accPage = 'dashboard.php'; $pageTitle = 'Dashboard';

$total = db()->prepare('SELECT COUNT(*) FROM orders WHERE customer_id=?'); $total->execute([$cust['id']]); $total = (int)$total->fetchColumn();
$proc = db()->prepare("SELECT COUNT(*) FROM orders WHERE customer_id=? AND order_status IN ('Menunggu Konfirmasi','Dikonfirmasi','Diproses','Dikirim')"); $proc->execute([$cust['id']]); $proc = (int)$proc->fetchColumn();
$done = db()->prepare("SELECT COUNT(*) FROM orders WHERE customer_id=? AND order_status='Selesai'"); $done->execute([$cust['id']]); $done = (int)$done->fetchColumn();

$recent = db()->prepare('SELECT * FROM orders WHERE customer_id=? ORDER BY id DESC LIMIT 5');
$recent->execute([$cust['id']]);
$recent = $recent->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="container my-4">
  <div class="row g-3">
    <div class="col-lg-3"><?php require __DIR__ . '/../includes/account_nav.php'; ?></div>
    <div class="col-lg-9">
      <div class="card p-3 mb-3" style="background:linear-gradient(120deg,var(--chipi-blue),var(--chipi-blue-dark));color:#fff">
        <h4 class="brand-font mb-0">Halo, <?= e($cust['name']) ?>! 👋</h4>
        <p class="mb-0 opacity-90 small">Senang melihatmu kembali di Chipi Frozen Food.</p>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-4"><div class="card p-3 text-center"><div class="fs-3 fw-bold text-primary"><?= $total ?></div><div class="small text-muted-chipi">Total Pesanan</div></div></div>
        <div class="col-4"><div class="card p-3 text-center"><div class="fs-3 fw-bold" style="color:var(--chipi-orange)"><?= $proc ?></div><div class="small text-muted-chipi">Sedang Diproses</div></div></div>
        <div class="col-4"><div class="card p-3 text-center"><div class="fs-3 fw-bold text-success"><?= $done ?></div><div class="small text-muted-chipi">Selesai</div></div></div>
      </div>
      <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="brand-font mb-0">Pesanan Terakhir</h6>
          <a href="<?= url('customer/orders.php') ?>" class="small">Lihat semua</a>
        </div>
        <?php if (!$recent): ?>
          <div class="empty-state py-4"><i class="fa-solid fa-receipt d-block"></i>Belum ada pesanan.<div class="mt-2"><a href="<?= url('customer/products.php') ?>" class="btn btn-chipi btn-sm">Belanja Sekarang</a></div></div>
        <?php else: foreach ($recent as $o): ?>
          <a href="<?= url('customer/order-detail.php?id='.$o['id']) ?>" class="d-flex justify-content-between align-items-center py-2 border-bottom text-dark">
            <div>
              <div class="fw-bold small"><?= e($o['order_number']) ?></div>
              <div class="text-muted-chipi" style="font-size:.75rem"><?= date('d M Y H:i', strtotime($o['created_at'])) ?></div>
            </div>
            <div class="text-end">
              <span class="status-badge <?= status_class($o['order_status']) ?>"><?= e($o['order_status']) ?></span>
              <div class="fw-bold small mt-1"><?= rupiah($o['grand_total']) ?></div>
            </div>
          </a>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
