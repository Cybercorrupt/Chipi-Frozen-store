<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pageTitle = 'Pelanggan';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $cid = (int)($_POST['id'] ?? 0);
    $do = $_POST['do'] ?? '';

    // fetch target customer (for notification message)
    $cst = db()->prepare('SELECT * FROM customers WHERE id=?'); $cst->execute([$cid]); $target = $cst->fetch();

    if ($do === 'toggle') {
        db()->prepare('UPDATE customers SET is_active=1-is_active WHERE id=?')->execute([$cid]);
        flash('success','Status aktif pelanggan diperbarui.');
    } elseif ($do === 'approve') {
        db()->prepare("UPDATE customers SET status='active', is_active=1 WHERE id=?")->execute([$cid]);
        flash('success','Pendaftaran pelanggan disetujui.');
        if ($target) $_SESSION['wa_notify'] = build_customer_notify($target, 'approve');
    } elseif ($do === 'reject') {
        db()->prepare("UPDATE customers SET status='rejected' WHERE id=?")->execute([$cid]);
        flash('success','Pendaftaran pelanggan ditolak.');
        if ($target) $_SESSION['wa_notify'] = build_customer_notify($target, 'reject');
    } elseif ($do === 'delete') {
        db()->prepare('DELETE FROM customers WHERE id=?')->execute([$cid]);
        flash('success','Pelanggan dihapus. Riwayat pesanan tetap tersimpan.');
    }
    redirect('admin/customers.php' . (!empty($_POST['back']) ? '?status='.urlencode($_POST['back']) : ''));
}

/** Build WhatsApp + mailto notification links for a customer decision. */
function build_customer_notify(array $c, string $action): array {
    $wa = preg_replace('/[^0-9]/', '', $c['whatsapp']);
    if (str_starts_with($wa, '0')) $wa = '62' . substr($wa, 1);
    if ($action === 'approve') {
        $subject = 'Pendaftaran Disetujui - Chipi Frozen Food';
        $msg = render_template('tpl_reg_approve', ['name' => $c['name']]);
    } else {
        $subject = 'Status Pendaftaran - Chipi Frozen Food';
        $msg = render_template('tpl_reg_reject', ['name' => $c['name']]);
    }
    return [
        'name'   => $c['name'],
        'action' => $action,
        'wa'     => 'https://wa.me/' . $wa . '?text=' . rawurlencode($msg),
        'mailto' => !empty($c['email']) ? 'mailto:' . rawurlencode($c['email']) . '?subject=' . rawurlencode($subject) . '&body=' . rawurlencode($msg) : '',
    ];
}

$q = trim($_GET['q'] ?? '');
$statusF = $_GET['status'] ?? '';
$where = []; $params = [];
if ($q !== '') { $where[] = '(c.name LIKE ? OR c.whatsapp LIKE ? OR c.email LIKE ?)'; $params=["%$q%","%$q%","%$q%"]; }
if (in_array($statusF, ['pending','active','rejected'])) { $where[] = 'c.status=?'; $params[] = $statusF; }
$sql = "SELECT c.*,
        (SELECT COUNT(*) FROM orders o WHERE o.customer_id=c.id) total_orders,
        (SELECT COALESCE(SUM(o.grand_total),0) FROM orders o WHERE o.customer_id=c.id AND o.order_status<>'Dibatalkan') total_purchase,
        (SELECT MAX(o.created_at) FROM orders o WHERE o.customer_id=c.id) last_order
        FROM customers c";
if ($where) $sql .= ' WHERE '.implode(' AND ',$where);
$sql .= ' ORDER BY (c.status=\'pending\') DESC, c.id DESC';
$st = db()->prepare($sql); $st->execute($params);
$rows = $st->fetchAll();

$pendingCount = (int)db()->query("SELECT COUNT(*) FROM customers WHERE status='pending'")->fetchColumn();
$statusBadge = ['pending'=>['Menunggu','bg-warning text-dark'],'active'=>['Aktif','bg-success'],'rejected'=>['Ditolak','bg-danger']];
require __DIR__ . '/includes/admin_header.php';
$notify = $_SESSION['wa_notify'] ?? null; unset($_SESSION['wa_notify']);
?>
<?php if ($notify): ?>
  <div class="card p-3 mb-3 border-start border-4 <?= $notify['action']==='approve'?'border-success':'border-danger' ?>" data-testid="notify-banner">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <div class="flex-grow-1">
        <b>Kirim notifikasi ke <?= e($notify['name']) ?></b>
        <div class="small text-muted-chipi">Pesan <?= $notify['action']==='approve'?'persetujuan':'penolakan' ?> otomatis sudah disiapkan. Klik untuk mengirim.</div>
      </div>
      <a href="<?= e($notify['wa']) ?>" target="_blank" id="autoWa" class="btn btn-success" data-testid="notify-wa"><i class="fa-brands fa-whatsapp me-1"></i>Kirim WhatsApp</a>
      <?php if ($notify['mailto']): ?><a href="<?= e($notify['mailto']) ?>" class="btn btn-outline-chipi" data-testid="notify-email"><i class="fa-solid fa-envelope me-1"></i>Kirim Email</a><?php endif; ?>
    </div>
  </div>
  <script>
    // Otomatis buka WhatsApp untuk mengirim notifikasi ke customer
    try { window.open(<?= json_encode($notify['wa']) ?>, '_blank'); } catch(e) {}
  </script>
<?php endif; ?>
<form method="get" class="card p-3 mb-3">
  <div class="row g-2 align-items-center">
    <div class="col-md-6"><div class="input-group"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari nama / WhatsApp / email" data-testid="cust-search"><button class="btn btn-chipi-blue">Cari</button></div></div>
    <div class="col-md-6 d-flex gap-2 flex-wrap justify-content-md-end">
      <a href="?" class="btn btn-sm <?= $statusF===''?'btn-chipi-blue':'btn-outline-chipi' ?>">Semua</a>
      <a href="?status=pending" class="btn btn-sm position-relative <?= $statusF==='pending'?'btn-chipi-blue':'btn-outline-chipi' ?>">Menunggu <?php if($pendingCount): ?><span class="badge bg-danger"><?= $pendingCount ?></span><?php endif; ?></a>
      <a href="?status=active" class="btn btn-sm <?= $statusF==='active'?'btn-chipi-blue':'btn-outline-chipi' ?>">Aktif</a>
      <a href="?status=rejected" class="btn btn-sm <?= $statusF==='rejected'?'btn-chipi-blue':'btn-outline-chipi' ?>">Ditolak</a>
    </div>
  </div>
</form>
<div class="card p-0"><div class="table-responsive"><table class="table table-chipi align-middle mb-0"><thead><tr><th>Nama</th><th>WhatsApp</th><th>Order</th><th>Belanja</th><th>Status</th><th>Aktif</th><th>Aksi</th></tr></thead><tbody>
  <?php if(!$rows): ?><tr><td colspan="7" class="text-center py-4 text-muted-chipi">Tidak ada pelanggan.</td></tr>
  <?php else: foreach($rows as $r): $sb=$statusBadge[$r['status']]??['?','bg-secondary']; ?>
    <tr data-testid="cust-row-<?= (int)$r['id'] ?>">
      <td class="fw-bold"><?= e($r['name']) ?><br><span class="small text-muted-chipi"><?= e($r['email']) ?></span></td>
      <td><a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$r['whatsapp']) ?>" target="_blank" class="text-decoration-none"><?= e($r['whatsapp']) ?> <i class="fa-brands fa-whatsapp text-success"></i></a></td>
      <td><?= (int)$r['total_orders'] ?></td>
      <td class="fw-bold"><?= rupiah($r['total_purchase']) ?></td>
      <td><span class="badge <?= $sb[1] ?>" data-testid="cust-status-<?= (int)$r['id'] ?>"><?= $sb[0] ?></span></td>
      <td><span class="badge <?= $r['is_active']?'bg-success':'bg-secondary' ?>"><?= $r['is_active']?'Aktif':'Nonaktif' ?></span></td>
      <td class="text-nowrap">
        <?php if($r['status']==='pending'): ?>
          <form method="post" class="d-inline" data-confirm="Setujui pendaftaran <?= e($r['name']) ?>?"><?= csrf_field() ?><input type="hidden" name="do" value="approve"><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="back" value="<?= e($statusF) ?>"><button class="btn btn-success btn-sm" data-testid="approve-cust-<?= (int)$r['id'] ?>"><i class="fa-solid fa-check me-1"></i>Setujui</button></form>
          <form method="post" class="d-inline" data-confirm="Tolak pendaftaran <?= e($r['name']) ?>?"><?= csrf_field() ?><input type="hidden" name="do" value="reject"><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="back" value="<?= e($statusF) ?>"><button class="btn btn-outline-danger btn-sm" data-testid="reject-cust-<?= (int)$r['id'] ?>"><i class="fa-solid fa-xmark"></i></button></form>
        <?php else: ?>
          <form method="post" class="d-inline" data-confirm="Ubah status aktif pelanggan ini?"><?= csrf_field() ?><input type="hidden" name="do" value="toggle"><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="back" value="<?= e($statusF) ?>"><button class="btn btn-sm <?= $r['is_active']?'btn-outline-secondary':'btn-outline-success' ?>"><?= $r['is_active']?'Nonaktifkan':'Aktifkan' ?></button></form>
          <?php if($r['status']==='rejected'): ?>
            <form method="post" class="d-inline" data-confirm="Setujui ulang pelanggan ini?"><?= csrf_field() ?><input type="hidden" name="do" value="approve"><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="back" value="<?= e($statusF) ?>"><button class="btn btn-success btn-sm"><i class="fa-solid fa-check"></i></button></form>
          <?php endif; ?>
        <?php endif; ?>
        <form method="post" class="d-inline" data-confirm="Hapus pelanggan &quot;<?= e($r['name']) ?>&quot;? Tindakan ini permanen (riwayat pesanan tetap tersimpan)."><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="back" value="<?= e($statusF) ?>"><button class="btn btn-outline-danger btn-sm" data-testid="delete-cust-<?= (int)$r['id'] ?>"><i class="fa-solid fa-trash"></i></button></form>
      </td>
    </tr>
  <?php endforeach; endif; ?>
</tbody></table></div></div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
