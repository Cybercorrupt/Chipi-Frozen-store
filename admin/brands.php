<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pageTitle = 'Brand';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $do = $_POST['do'] ?? '';
    if ($do === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $active = isset($_POST['is_active'])?1:0;
        if ($name) {
            if ($id) db()->prepare('UPDATE brands SET name=?,is_active=? WHERE id=?')->execute([$name,$active,$id]);
            else db()->prepare('INSERT INTO brands (name,is_active) VALUES (?,?)')->execute([$name,$active]);
            flash('success','Brand disimpan.');
        }
    } elseif ($do === 'delete') {
        db()->prepare('DELETE FROM brands WHERE id=?')->execute([(int)$_POST['id']]);
        flash('success','Brand dihapus.');
    }
    redirect('admin/brands.php');
}
$rows = db()->query("SELECT b.*, (SELECT COUNT(*) FROM products p WHERE p.brand_id=b.id) cnt FROM brands b ORDER BY b.name")->fetchAll();
require __DIR__ . '/includes/admin_header.php';
?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card p-3">
      <h6 class="brand-font" id="formTitle">Tambah Brand</h6>
      <form method="post" id="brForm">
        <?= csrf_field() ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" id="brId">
        <label class="form-label small fw-bold">Nama</label><input class="form-control mb-2" name="name" id="brName" required data-testid="brand-name">
        <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_active" id="brActive" checked><label class="form-check-label" for="brActive">Aktif</label></div>
        <button class="btn btn-chipi btn-sm" data-testid="brand-save">Simpan</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('brForm').reset();document.getElementById('brId').value='';document.getElementById('formTitle').textContent='Tambah Brand';">Reset</button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card p-0"><div class="table-responsive"><table class="table table-chipi mb-0"><thead><tr><th>Nama</th><th>Produk</th><th>Status</th><th></th></tr></thead><tbody>
      <?php foreach($rows as $r): ?>
        <tr><td class="fw-bold"><?= e($r['name']) ?></td><td><?= (int)$r['cnt'] ?></td><td><span class="badge <?= $r['is_active']?'bg-success':'bg-secondary' ?>"><?= $r['is_active']?'Aktif':'Nonaktif' ?></span></td>
        <td class="text-nowrap">
          <button class="btn btn-outline-chipi btn-sm" onclick='editBr(<?= json_encode($r) ?>)'><i class="fa-solid fa-pen"></i></button>
          <form method="post" class="d-inline" data-confirm="Hapus brand ini?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-trash"></i></button></form>
        </td></tr>
      <?php endforeach; ?>
    </tbody></table></div></div>
  </div>
</div>
<script>function editBr(r){document.getElementById('brId').value=r.id;document.getElementById('brName').value=r.name;document.getElementById('brActive').checked=r.is_active==1;document.getElementById('formTitle').textContent='Edit Brand';window.scrollTo(0,0);}</script>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
