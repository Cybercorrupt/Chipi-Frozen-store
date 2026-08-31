<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pageTitle = 'Promo';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $do = $_POST['do'] ?? '';
    if ($do === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $type = ($_POST['discount_type'] ?? 'percentage')==='fixed'?'fixed':'percentage';
        $val = (float)($_POST['discount_value'] ?? 0);
        $min = (float)($_POST['min_purchase'] ?? 0);
        $sd = $_POST['start_date'] ?: null; $ed = $_POST['end_date'] ?: null;
        $active = isset($_POST['is_active'])?1:0;
        if ($code) {
            if ($id) db()->prepare('UPDATE promos SET code=?,discount_type=?,discount_value=?,min_purchase=?,start_date=?,end_date=?,is_active=? WHERE id=?')->execute([$code,$type,$val,$min,$sd,$ed,$active,$id]);
            else db()->prepare('INSERT INTO promos (code,discount_type,discount_value,min_purchase,start_date,end_date,is_active) VALUES (?,?,?,?,?,?,?)')->execute([$code,$type,$val,$min,$sd,$ed,$active]);
            flash('success','Promo disimpan.');
        }
    } elseif ($do === 'delete') {
        db()->prepare('DELETE FROM promos WHERE id=?')->execute([(int)$_POST['id']]);
        flash('success','Promo dihapus.');
    }
    redirect('admin/promos.php');
}
$rows = db()->query("SELECT * FROM promos ORDER BY id DESC")->fetchAll();
require __DIR__ . '/includes/admin_header.php';
?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card p-3">
      <h6 class="brand-font" id="formTitle">Tambah Promo</h6>
      <form method="post" id="pForm">
        <?= csrf_field() ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" id="pId">
        <label class="form-label small fw-bold">Kode</label><input class="form-control mb-2 text-uppercase" name="code" id="pCode" required data-testid="promo-code">
        <label class="form-label small fw-bold">Tipe Diskon</label><select class="form-select mb-2" name="discount_type" id="pType"><option value="percentage">Persentase (%)</option><option value="fixed">Nominal (Rp)</option></select>
        <label class="form-label small fw-bold">Nilai Diskon</label><input class="form-control mb-2" type="number" step="0.01" name="discount_value" id="pVal" required>
        <label class="form-label small fw-bold">Min. Belanja</label><input class="form-control mb-2" type="number" name="min_purchase" id="pMin" value="0">
        <div class="row"><div class="col-6"><label class="form-label small fw-bold">Mulai</label><input class="form-control mb-2" type="date" name="start_date" id="pSd"></div><div class="col-6"><label class="form-label small fw-bold">Selesai</label><input class="form-control mb-2" type="date" name="end_date" id="pEd"></div></div>
        <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_active" id="pActive" checked><label class="form-check-label" for="pActive">Aktif</label></div>
        <button class="btn btn-chipi btn-sm" data-testid="promo-save">Simpan</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('pForm').reset();document.getElementById('pId').value='';document.getElementById('formTitle').textContent='Tambah Promo';">Reset</button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card p-0"><div class="table-responsive"><table class="table table-chipi mb-0"><thead><tr><th>Kode</th><th>Diskon</th><th>Min</th><th>Periode</th><th>Status</th><th></th></tr></thead><tbody>
      <?php foreach($rows as $r): ?>
        <tr><td class="fw-bold"><?= e($r['code']) ?></td><td><?= $r['discount_type']==='percentage'?((float)$r['discount_value'].'%'):rupiah($r['discount_value']) ?></td><td><?= rupiah($r['min_purchase']) ?></td><td class="small"><?= e($r['start_date']) ?><br><?= e($r['end_date']) ?></td><td><span class="badge <?= $r['is_active']?'bg-success':'bg-secondary' ?>"><?= $r['is_active']?'Aktif':'Nonaktif' ?></span></td>
        <td class="text-nowrap"><button class="btn btn-outline-chipi btn-sm" onclick='editP(<?= json_encode($r) ?>)'><i class="fa-solid fa-pen"></i></button>
        <form method="post" class="d-inline" data-confirm="Hapus promo?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-trash"></i></button></form></td></tr>
      <?php endforeach; ?>
    </tbody></table></div></div>
  </div>
</div>
<script>function editP(r){document.getElementById('pId').value=r.id;document.getElementById('pCode').value=r.code;document.getElementById('pType').value=r.discount_type;document.getElementById('pVal').value=r.discount_value;document.getElementById('pMin').value=r.min_purchase;document.getElementById('pSd').value=r.start_date;document.getElementById('pEd').value=r.end_date;document.getElementById('pActive').checked=r.is_active==1;document.getElementById('formTitle').textContent='Edit Promo';window.scrollTo(0,0);}</script>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
