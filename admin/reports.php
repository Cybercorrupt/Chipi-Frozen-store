<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pageTitle = 'Laporan';

$range = $_GET['range'] ?? '7';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$today = date('Y-m-d');
if ($range === 'today') { $from = $to = $today; }
elseif ($range === 'month') { $from = date('Y-m-01'); $to = $today; }
elseif ($range === 'custom' && $from && $to) { /* use as is */ }
else { $range = '7'; $from = date('Y-m-d', strtotime('-6 days')); $to = $today; }

$pdo = db();
$p = [$from.' 00:00:00', $to.' 23:59:59'];
// helper query
function q1($sql,$p){ $s=db()->prepare($sql); $s->execute($p); return $s->fetchColumn(); }
$totalOrders = (int)q1("SELECT COUNT(*) FROM orders WHERE created_at BETWEEN ? AND ?", $p);
$totalSales = (float)q1("SELECT COALESCE(SUM(grand_total),0) FROM orders WHERE created_at BETWEEN ? AND ? AND order_status<>'Dibatalkan'", $p);
$completed = (int)q1("SELECT COUNT(*) FROM orders WHERE created_at BETWEEN ? AND ? AND order_status='Selesai'", $p);
$cancelled = (int)q1("SELECT COUNT(*) FROM orders WHERE created_at BETWEEN ? AND ? AND order_status='Dibatalkan'", $p);
$productsSold = (int)q1("SELECT COALESCE(SUM(oi.qty),0) FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.created_at BETWEEN ? AND ? AND o.order_status<>'Dibatalkan'", $p);

$topSt = $pdo->prepare("SELECT oi.product_name, SUM(oi.qty) qty, SUM(oi.subtotal) total FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.created_at BETWEEN ? AND ? AND o.order_status<>'Dibatalkan' GROUP BY oi.product_name ORDER BY qty DESC LIMIT 5");
$topSt->execute($p);
$top = $topSt->fetchAll();
require __DIR__ . '/includes/admin_header.php';
?>
<form method="get" class="card p-3 mb-3">
  <div class="d-flex gap-2 flex-wrap align-items-end">
    <div><label class="form-label small fw-bold">Periode</label>
      <select class="form-select" name="range" onchange="document.getElementById('customBox').style.display=this.value==='custom'?'flex':'none'">
        <option value="today" <?= $range==='today'?'selected':'' ?>>Hari Ini</option>
        <option value="7" <?= $range==='7'?'selected':'' ?>>7 Hari</option>
        <option value="month" <?= $range==='month'?'selected':'' ?>>Bulan Ini</option>
        <option value="custom" <?= $range==='custom'?'selected':'' ?>>Custom</option>
      </select>
    </div>
    <div id="customBox" class="gap-2" style="display:<?= $range==='custom'?'flex':'none' ?>">
      <div><label class="form-label small fw-bold">Dari</label><input type="date" class="form-control" name="from" value="<?= e($from) ?>"></div>
      <div><label class="form-label small fw-bold">Sampai</label><input type="date" class="form-control" name="to" value="<?= e($to) ?>"></div>
    </div>
    <button class="btn btn-chipi-blue"><i class="fa-solid fa-magnifying-glass me-1"></i>Tampilkan</button>
    <a href="<?= url('admin/report-export.php?'.http_build_query(['range'=>$range,'from'=>$from,'to'=>$to])) ?>" class="btn btn-chipi" data-testid="report-export"><i class="fa-solid fa-file-excel me-1"></i>Export Excel</a>
  </div>
</form>

<div class="row g-3 mb-3">
  <div class="col-6 col-md"><div class="kpi-card kpi-blue"><div class="kpi-val"><?= $totalOrders ?></div><div class="small">Total Pesanan</div></div></div>
  <div class="col-6 col-md"><div class="kpi-card kpi-green"><div class="kpi-val" style="font-size:1.2rem"><?= rupiah($totalSales) ?></div><div class="small">Total Penjualan</div></div></div>
  <div class="col-6 col-md"><div class="kpi-card kpi-navy"><div class="kpi-val"><?= $productsSold ?></div><div class="small">Produk Terjual</div></div></div>
  <div class="col-6 col-md"><div class="kpi-card kpi-orange"><div class="kpi-val"><?= $completed ?></div><div class="small">Pesanan Selesai</div></div></div>
  <div class="col-6 col-md"><div class="kpi-card kpi-red"><div class="kpi-val"><?= $cancelled ?></div><div class="small">Dibatalkan</div></div></div>
</div>

<div class="card p-3">
  <h6 class="brand-font">Top 5 Produk Terlaris</h6>
  <?php if(!$top): ?><div class="empty-state py-3"><i class="fa-solid fa-chart-simple d-block"></i>Belum ada penjualan pada periode ini.</div>
  <?php else: ?><div class="table-responsive"><table class="table table-chipi mb-0"><thead><tr><th>#</th><th>Produk</th><th class="text-center">Qty Terjual</th><th class="text-end">Total</th></tr></thead><tbody>
    <?php foreach($top as $i=>$t): ?><tr><td><?= $i+1 ?></td><td class="fw-bold"><?= e($t['product_name']) ?></td><td class="text-center"><?= (int)$t['qty'] ?></td><td class="text-end fw-bold"><?= rupiah($t['total']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?>
</div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
