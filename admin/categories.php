<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pageTitle = 'Kategori';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $do = $_POST['do'] ?? '';
    if ($do === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        if ($slug === '') $slug = strtolower(preg_replace('/[^a-z0-9]+/i','-',$name));
        $slug = trim($slug,'-');
        $active = isset($_POST['is_active'])?1:0;
        if ($name) {
            if ($id) db()->prepare('UPDATE categories SET name=?,slug=?,is_active=? WHERE id=?')->execute([$name,$slug,$active,$id]);
            else db()->prepare('INSERT INTO categories (name,slug,is_active) VALUES (?,?,?)')->execute([$name,$slug,$active]);
            flash('success','Kategori disimpan.');
        }
    } elseif ($do === 'delete') {
        db()->prepare('DELETE FROM categories WHERE id=?')->execute([(int)$_POST['id']]);
        flash('success','Kategori dihapus.');
    }
    redirect('admin/categories.php');
}
$rows = db()->query("SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id=c.id) cnt FROM categories c ORDER BY c.name")->fetchAll();
require __DIR__ . '/includes/admin_header.php';
?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card p-3">
      <h6 class="brand-font" id="formTitle">Tambah Kategori</h6>
      <form method="post" id="catForm">
        <?= csrf_field() ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" id="catId">
        <label class="form-label small fw-bold">Nama</label><input class="form-control mb-2" name="name" id="catName" required data-testid="cat-name">
        <label class="form-label small fw-bold">Slug <span class="text-muted-chipi">(opsional)</span></label><input class="form-control mb-2" name="slug" id="catSlug">
        <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_active" id="catActive" checked><label class="form-check-label" for="catActive">Aktif</label></div>
        <button class="btn btn-chipi btn-sm" data-testid="cat-save">Simpan</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetCat()">Reset</button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card p-0"><div class="table-responsive"><table class="table table-chipi mb-0"><thead><tr><th>Nama</th><th>Slug</th><th>Produk</th><th>Status</th><th></th></tr></thead><tbody>
      <?php foreach($rows as $r): ?>
        <tr><td class="fw-bold"><?= e($r['name']) ?></td><td class="small text-muted-chipi"><?= e($r['slug']) ?></td><td><?= (int)$r['cnt'] ?></td><td><span class="badge <?= $r['is_active']?'bg-success':'bg-secondary' ?>"><?= $r['is_active']?'Aktif':'Nonaktif' ?></span></td>
        <td class="text-nowrap">
          <button class="btn btn-outline-chipi btn-sm" onclick='editCat(<?= json_encode($r) ?>)'><i class="fa-solid fa-pen"></i></button>
          <form method="post" class="d-inline" data-confirm="Hapus kategori ini?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-trash"></i></button></form>
        </td></tr>
      <?php endforeach; ?>
    </tbody></table></div></div>
  </div>
</div>
<script>
function editCat(r){document.getElementById('catId').value=r.id;document.getElementById('catName').value=r.name;document.getElementById('catSlug').value=r.slug;document.getElementById('catActive').checked=r.is_active==1;document.getElementById('formTitle').textContent='Edit Kategori';window.scrollTo(0,0);}
function resetCat(){document.getElementById('catForm').reset();document.getElementById('catId').value='';document.getElementById('formTitle').textContent='Tambah Kategori';}
</script>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
