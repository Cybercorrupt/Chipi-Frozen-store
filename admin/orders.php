<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pageTitle = 'Pesanan';

$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$where = ['1=1']; $params = [];
if ($q !== '') { $where[] = '(order_number LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?)'; $params[]="%$q%"; $params[]="%$q%"; $params[]="%$q%"; }
if ($status && in_array($status, order_statuses())) { $where[] = 'order_status=?'; $params[] = $status; }
$sql = 'SELECT * FROM orders WHERE '.implode(' AND ',$where).' ORDER BY id DESC';
$st = db()->prepare($sql); $st->execute($params);
$orders = $st->fetchAll();

require __DIR__ . '/includes/admin_header.php';
?>
<form method="get" class="card p-3 mb-3">
  <div class="row g-2">
    <div class="col-md-5"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari no. pesanan / nama / no. HP" data-testid="order-search"></div>
    <div class="col-md-4">
      <select class="form-select" name="status" data-testid="order-status-filter">
        <option value="">Semua Status</option>
        <?php foreach(order_statuses() as $s): ?><option value="<?= e($s) ?>" <?= $status===$s?'selected':'' ?>><?= e($s) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 d-flex gap-2"><button class="btn btn-chipi-blue flex-fill">Filter</button><a href="<?= url('admin/orders.php') ?>" class="btn btn-outline-secondary">Reset</a></div>
  </div>
</form>

<div class="card p-0">
  <div class="table-responsive">
    <table class="table table-chipi align-middle mb-0">
      <thead><tr><th>No. Pesanan</th><th>Pelanggan</th><th>Total</th><th>Bayar</th><th>Status</th><th>Tanggal</th><th></th></tr></thead>
      <tbody>
        <?php if(!$orders): ?><tr><td colspan="7" class="text-center py-4 text-muted-chipi">Tidak ada pesanan.</td></tr>
        <?php else: foreach($orders as $o): ?>
          <tr data-testid="admin-order-<?= e($o['order_number']) ?>">
            <td class="fw-bold"><?= e($o['order_number']) ?></td>
            <td><?= e($o['customer_name']) ?><br><span class="text-muted-chipi small"><?= e($o['customer_phone']) ?></span></td>
            <td class="fw-bold"><?= rupiah($o['grand_total']) ?></td>
            <td><span class="small"><?= e($o['payment_method']) ?></span></td>
            <td><span class="status-badge <?= status_class($o['order_status']) ?>"><?= e($o['order_status']) ?></span></td>
            <td class="small text-muted-chipi"><?= date('d/m/y H:i', strtotime($o['created_at'])) ?></td>
            <td><a href="<?= url('admin/order-detail.php?id='.$o['id']) ?>" class="btn btn-chipi btn-sm" data-testid="open-order-<?= e($o['order_number']) ?>"><i class="fa-solid fa-eye"></i></a></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
