<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pageTitle = 'Dashboard';
$pdo = db();

$today = date('Y-m-d');
$ordersToday = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)='$today'")->fetchColumn();
$waiting     = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status='Menunggu Konfirmasi'")->fetchColumn();
$processing  = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status IN ('Dikonfirmasi','Diproses')")->fetchColumn();
$readyShip   = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status='Dikirim'")->fetchColumn();
$salesToday  = (float)$pdo->query("SELECT COALESCE(SUM(grand_total),0) FROM orders WHERE DATE(created_at)='$today' AND order_status<>'Dibatalkan'")->fetchColumn();
$lowStock    = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock_qty<=10 AND is_active=1")->fetchColumn();

// sales trend 7 days
$labels = []; $sales = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('d/m', strtotime($d));
    $sales[] = (float)$pdo->query("SELECT COALESCE(SUM(grand_total),0) FROM orders WHERE DATE(created_at)='$d' AND order_status<>'Dibatalkan'")->fetchColumn();
}

// status distribution
$statusData = [];
foreach (['Menunggu Konfirmasi','Dikonfirmasi','Diproses','Dikirim','Selesai'] as $s) {
    $c = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE order_status=?"); $c->execute([$s]);
    $statusData[] = (int)$c->fetchColumn();
}

$recent = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 6")->fetchAll();
$lowList = $pdo->query("SELECT * FROM products WHERE stock_qty<=10 AND is_active=1 ORDER BY stock_qty ASC LIMIT 6")->fetchAll();

require __DIR__ . '/includes/admin_header.php';
?>
<!-- KPI -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg"><div class="kpi-card kpi-blue"><div class="d-flex justify-content-between"><div><div class="kpi-val"><?= $ordersToday ?></div><div class="small">Pesanan Hari Ini</div></div><i class="fa-solid fa-bag-shopping kpi-icon"></i></div></div></div>
  <div class="col-6 col-lg"><div class="kpi-card kpi-orange"><div class="d-flex justify-content-between"><div><div class="kpi-val"><?= $waiting ?></div><div class="small">Menunggu Konfirmasi</div></div><i class="fa-solid fa-hourglass-half kpi-icon"></i></div></div></div>
  <div class="col-6 col-lg"><div class="kpi-card kpi-navy"><div class="d-flex justify-content-between"><div><div class="kpi-val"><?= $processing ?></div><div class="small">Sedang Diproses</div></div><i class="fa-solid fa-gears kpi-icon"></i></div></div></div>
  <div class="col-6 col-lg"><div class="kpi-card kpi-green"><div class="d-flex justify-content-between"><div><div class="kpi-val" style="font-size:1.2rem"><?= rupiah($salesToday) ?></div><div class="small">Penjualan Hari Ini</div></div><i class="fa-solid fa-money-bill-wave kpi-icon"></i></div></div></div>
  <div class="col-6 col-lg"><div class="kpi-card kpi-red"><div class="d-flex justify-content-between"><div><div class="kpi-val"><?= $lowStock ?></div><div class="small">Stok Menipis</div></div><i class="fa-solid fa-triangle-exclamation kpi-icon"></i></div></div></div>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
  <div class="col-lg-8"><div class="card p-3"><h6 class="brand-font">Trend Penjualan (7 Hari)</h6><canvas id="salesChart" height="110"></canvas></div></div>
  <div class="col-lg-4"><div class="card p-3"><h6 class="brand-font">Status Pesanan</h6><div style="position:relative;height:260px"><canvas id="statusChart"></canvas></div></div></div>
</div>

<!-- Operational -->
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card p-3">
      <h6 class="brand-font">Pesanan Perlu Tindakan</h6>
      <a href="<?= url('admin/orders.php?status='.urlencode('Menunggu Konfirmasi')) ?>" class="d-flex justify-content-between align-items-center py-2 border-bottom text-dark"><span><i class="fa-solid fa-hourglass-half text-warning me-2"></i>Menunggu Konfirmasi</span><span class="badge bg-warning text-dark"><?= $waiting ?></span></a>
      <a href="<?= url('admin/orders.php?status='.urlencode('Diproses')) ?>" class="d-flex justify-content-between align-items-center py-2 border-bottom text-dark"><span><i class="fa-solid fa-gears text-primary me-2"></i>Sedang Diproses</span><span class="badge bg-primary"><?= $processing ?></span></a>
      <a href="<?= url('admin/orders.php?status='.urlencode('Dikirim')) ?>" class="d-flex justify-content-between align-items-center py-2 border-bottom text-dark"><span><i class="fa-solid fa-truck text-info me-2"></i>Sedang Dikirim</span><span class="badge bg-info"><?= $readyShip ?></span></a>
      <a href="<?= url('admin/products.php?low=1') ?>" class="d-flex justify-content-between align-items-center py-2 text-dark"><span><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>Stok Menipis</span><span class="badge bg-danger"><?= $lowStock ?></span></a>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card p-3">
      <h6 class="brand-font">Pesanan Terbaru</h6>
      <?php if(!$recent): ?><div class="empty-state py-3"><i class="fa-solid fa-inbox d-block"></i>Belum ada pesanan.</div><?php else: foreach($recent as $o): ?>
        <a href="<?= url('admin/order-detail.php?id='.$o['id']) ?>" class="d-flex justify-content-between align-items-center py-2 border-bottom text-dark">
          <div><div class="fw-bold small"><?= e($o['order_number']) ?></div><div class="text-muted-chipi" style="font-size:.72rem"><?= e($o['customer_name']) ?></div></div>
          <div class="text-end"><span class="status-badge <?= status_class($o['order_status']) ?>" style="font-size:.65rem"><?= e($o['order_status']) ?></span><div class="small fw-bold"><?= rupiah($o['grand_total']) ?></div></div>
        </a>
      <?php endforeach; endif; ?>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card p-3">
      <h6 class="brand-font">Stok Menipis</h6>
      <?php if(!$lowList): ?><div class="empty-state py-3"><i class="fa-solid fa-boxes-stacked d-block"></i>Semua stok aman.</div><?php else: foreach($lowList as $p): ?>
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <div class="small"><b><?= e($p['name']) ?></b><br><span class="text-muted-chipi" style="font-size:.72rem"><?= e($p['sku']) ?></span></div>
          <span class="stock-badge <?= $p['stock_qty']<=0?'stock-out':'stock-low' ?>"><?= (int)$p['stock_qty'] ?> <?= e($p['unit']) ?></span>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<script>
new Chart(document.getElementById('salesChart'),{
  type:'line',
  data:{labels:<?= json_encode($labels) ?>,datasets:[{label:'Penjualan',data:<?= json_encode($sales) ?>,borderColor:'#ff7a29',backgroundColor:'rgba(255,122,41,.12)',fill:true,tension:.35,borderWidth:3,pointBackgroundColor:'#ff7a29'}]},
  options:{plugins:{legend:{display:false}},scales:{y:{ticks:{callback:v=>'Rp'+(v/1000)+'k'}}}}
});
new Chart(document.getElementById('statusChart'),{
  type:'doughnut',
  data:{labels:['Menunggu','Dikonfirmasi','Diproses','Dikirim','Selesai'],datasets:[{data:<?= json_encode($statusData) ?>,backgroundColor:['#ffb547','#38b6ff','#6a3fd6','#0d8ba1','#1b8a4b']}]},
  options:{maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{size:10}}}},cutout:'62%'}
});
</script>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
