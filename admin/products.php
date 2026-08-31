<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pageTitle = 'Produk';

$q = trim($_GET['q'] ?? '');
$cat = (int)($_GET['category'] ?? 0);
$br = (int)($_GET['brand'] ?? 0);
$statusF = $_GET['status'] ?? '';
$low = isset($_GET['low']);
$sort = $_GET['sort'] ?? 'newest';

$where = ['1=1']; $params = [];
if ($q !== '') { $where[]='(p.name LIKE ? OR p.sku LIKE ?)'; $params[]="%$q%"; $params[]="%$q%"; }
if ($cat) { $where[]='p.category_id=?'; $params[]=$cat; }
if ($br) { $where[]='p.brand_id=?'; $params[]=$br; }
if ($statusF==='active') $where[]='p.is_active=1';
if ($statusF==='inactive') $where[]='p.is_active=0';
if ($low) $where[]='p.stock_qty<=10';
$order = match($sort){'price_asc'=>'p.price ASC','price_desc'=>'p.price DESC','name'=>'p.name ASC','stock'=>'p.stock_qty ASC',default=>'p.id DESC'};

$sql = "SELECT p.*, c.name category_name, b.name brand_name FROM products p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN brands b ON b.id=p.brand_id WHERE ".implode(' AND ',$where)." ORDER BY $order";
$st = db()->prepare($sql); $st->execute($params);
$products = $st->fetchAll();

$cats = db()->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$brands = db()->query("SELECT * FROM brands ORDER BY name")->fetchAll();
$qs = http_build_query(array_filter(['q'=>$q,'category'=>$cat,'brand'=>$br,'status'=>$statusF,'low'=>$low?1:null,'sort'=>$sort]));

require __DIR__ . '/includes/admin_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div class="d-flex gap-2 flex-wrap">
    <a href="<?= url('admin/product-form.php') ?>" class="btn btn-chipi" data-testid="add-product-btn"><i class="fa-solid fa-plus me-1"></i>Tambah Produk</a>
    <a href="<?= url('admin/import.php') ?>" class="btn btn-outline-chipi"><i class="fa-solid fa-file-import me-1"></i>Import</a>
    <a href="<?= url('admin/export.php?mode=filtered&filter_qs='.urlencode($qs)) ?>" class="btn btn-outline-chipi"><i class="fa-solid fa-file-export me-1"></i>Export Hasil Filter</a>
  </div>
</div>

<form method="get" class="card p-3 mb-3">
  <div class="row g-2">
    <div class="col-md-4"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari nama / SKU" data-testid="prod-search"></div>
    <div class="col-6 col-md-2"><select class="form-select" name="category"><option value="0">Semua Kategori</option><?php foreach($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $cat==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-6 col-md-2"><select class="form-select" name="brand"><option value="0">Semua Brand</option><?php foreach($brands as $b): ?><option value="<?= $b['id'] ?>" <?= $br==$b['id']?'selected':'' ?>><?= e($b['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-6 col-md-2"><select class="form-select" name="status"><option value="">Semua Status</option><option value="active" <?= $statusF==='active'?'selected':'' ?>>Aktif</option><option value="inactive" <?= $statusF==='inactive'?'selected':'' ?>>Nonaktif</option></select></div>
    <div class="col-6 col-md-2"><select class="form-select" name="sort"><option value="newest" <?= $sort==='newest'?'selected':'' ?>>Terbaru</option><option value="name" <?= $sort==='name'?'selected':'' ?>>Nama A-Z</option><option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Harga ↑</option><option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Harga ↓</option><option value="stock" <?= $sort==='stock'?'selected':'' ?>>Stok ↑</option></select></div>
    <div class="col-12"><button class="btn btn-chipi-blue btn-sm px-4"><i class="fa-solid fa-filter me-1"></i>Terapkan</button> <a href="<?= url('admin/products.php') ?>" class="btn btn-outline-secondary btn-sm">Reset</a></div>
  </div>
</form>

<form method="post" action="<?= url('admin/product-action.php') ?>" id="bulkForm">
  <?= csrf_field() ?>
  <input type="hidden" name="filter_qs" value="<?= e($qs) ?>">
  <input type="hidden" name="select_all_filtered" id="selAllFiltered" value="0">
  <input type="hidden" name="action" id="bulkAction" value="">

  <div class="card p-2 mb-2" id="bulkBar" data-testid="bulk-bar" style="display:none">
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <span class="fw-bold ms-2"><span id="selCount">0</span> dipilih</span>
      <a href="#" class="small" id="selectFilteredLink">Pilih semua <b><?= count($products) ?></b> produk hasil filter</a>
      <div class="ms-auto d-flex gap-1 flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-success" onclick="submitBulk('set_active')">Aktifkan</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="submitBulk('set_inactive')">Nonaktifkan</button>
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#mCat">Kategori</button>
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#mBrand">Brand</button>
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#mLabel">Label</button>
        <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#mPrice">Harga</button>
        <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#mStock">Stok</button>
        <button type="button" class="btn btn-sm btn-outline-info" onclick="exportSelected()">Export Dipilih</button>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="if(confirm('Hapus produk terpilih? Produk yang ada di riwayat order akan dinonaktifkan.'))submitBulk('delete')">Hapus</button>
      </div>
    </div>
  </div>

  <!-- Desktop table -->
  <div class="card p-0 admin-table-wrap">
    <div class="table-responsive">
      <table class="table table-chipi align-middle mb-0">
        <thead><tr>
          <th style="width:36px"><input type="checkbox" id="checkAll" class="form-check-input"></th>
          <th>Produk</th><th>SKU</th><th>Kategori</th><th>Brand</th><th>Harga</th><th>Stok</th><th>Status</th><th></th>
        </tr></thead>
        <tbody>
          <?php if(!$products): ?><tr><td colspan="9" class="text-center py-4 text-muted-chipi">Tidak ada produk.</td></tr>
          <?php else: foreach($products as $p): ?>
            <tr data-testid="prod-row-<?= e($p['sku']) ?>">
              <td><input type="checkbox" name="ids[]" value="<?= $p['id'] ?>" class="form-check-input rowchk"></td>
              <td class="d-flex align-items-center gap-2"><img src="<?= product_image_url($p['image']) ?>" class="prod-thumb-sm"><span class="fw-bold small"><?= e($p['name']) ?></span></td>
              <td class="small"><?= e($p['sku']) ?></td>
              <td class="small"><?= e($p['category_name']) ?></td>
              <td class="small"><?= e($p['brand_name']) ?></td>
              <td class="small"><?php if(has_promo($p)): ?><span class="price-now"><?= rupiah($p['promo_price']) ?></span><br><span class="price-old"><?= rupiah($p['price']) ?></span><?php else: ?><?= rupiah($p['price']) ?><?php endif; ?></td>
              <td><?php $ss=stock_status((int)$p['stock_qty']); ?><span class="stock-badge <?= $ss['class'] ?>"><?= (int)$p['stock_qty'] ?></span></td>
              <td><span class="badge <?= $p['is_active']?'bg-success':'bg-secondary' ?>"><?= $p['is_active']?'Aktif':'Nonaktif' ?></span></td>
              <td><a href="<?= url('admin/product-form.php?id='.$p['id']) ?>" class="btn btn-outline-chipi btn-sm" data-testid="edit-prod-<?= e($p['sku']) ?>"><i class="fa-solid fa-pen"></i></a></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Mobile cards -->
  <div class="admin-prod-card">
    <?php foreach($products as $p): $ss=stock_status((int)$p['stock_qty']); ?>
      <div class="card p-3 mb-2">
        <div class="d-flex gap-2">
          <input type="checkbox" name="ids_m[]" value="<?= $p['id'] ?>" class="form-check-input rowchk-m mt-1">
          <img src="<?= product_image_url($p['image']) ?>" class="prod-thumb-sm" style="width:56px;height:56px">
          <div class="flex-grow-1">
            <div class="fw-bold small"><?= e($p['name']) ?></div>
            <div class="text-muted-chipi" style="font-size:.72rem"><?= e($p['sku']) ?> · <?= e($p['category_name']) ?></div>
            <div class="d-flex justify-content-between align-items-center mt-1">
              <span class="price-now small"><?= rupiah(effective_price($p)) ?></span>
              <span class="stock-badge <?= $ss['class'] ?>">Stok: <?= (int)$p['stock_qty'] ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-1">
              <span class="badge <?= $p['is_active']?'bg-success':'bg-secondary' ?>"><?= $p['is_active']?'Aktif':'Nonaktif' ?></span>
              <a href="<?= url('admin/product-form.php?id='.$p['id']) ?>" class="btn btn-outline-chipi btn-sm">Edit</a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Modals -->
  <div class="modal fade" id="mCat"><div class="modal-dialog"><div class="modal-content rounded-4"><div class="modal-header"><h6 class="brand-font mb-0">Ubah Kategori</h6><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><select class="form-select" name="value_category"><?php foreach($cats as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div><div class="modal-footer"><button type="button" class="btn btn-chipi" onclick="submitBulk('change_category')">Terapkan</button></div></div></div></div>
  <div class="modal fade" id="mBrand"><div class="modal-dialog"><div class="modal-content rounded-4"><div class="modal-header"><h6 class="brand-font mb-0">Ubah Brand</h6><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><select class="form-select" name="value_brand"><?php foreach($brands as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?></select></div><div class="modal-footer"><button type="button" class="btn btn-chipi" onclick="submitBulk('change_brand')">Terapkan</button></div></div></div></div>
  <div class="modal fade" id="mLabel"><div class="modal-dialog"><div class="modal-content rounded-4"><div class="modal-header"><h6 class="brand-font mb-0">Ubah Label</h6><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><select class="form-select" name="value_label"><option value="NONE">NONE</option><option value="NEW">NEW</option><option value="PROMO">PROMO</option><option value="BEST SELLER">BEST SELLER</option></select></div><div class="modal-footer"><button type="button" class="btn btn-chipi" onclick="submitBulk('change_label')">Terapkan</button></div></div></div></div>
  <div class="modal fade" id="mPrice"><div class="modal-dialog"><div class="modal-content rounded-4"><div class="modal-header"><h6 class="brand-font mb-0">Update Harga Massal</h6><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">
    <select class="form-select mb-2" name="price_mode"><option value="set">Set harga tetap</option><option value="inc_amount">Naik (nominal)</option><option value="dec_amount">Turun (nominal)</option><option value="inc_pct">Naik (persen %)</option><option value="dec_pct">Turun (persen %)</option></select>
    <input class="form-control" type="number" step="0.01" name="price_value" placeholder="Nilai (mis. 5000 atau 5)">
    <p class="small text-muted-chipi mt-2 mb-0">Contoh: Naik 5% pada produk terpilih.</p>
  </div><div class="modal-footer"><button type="button" class="btn btn-chipi" onclick="if(confirm('Terapkan perubahan harga?'))submitBulk('update_price')">Terapkan</button></div></div></div></div>
  <div class="modal fade" id="mStock"><div class="modal-dialog"><div class="modal-content rounded-4"><div class="modal-header"><h6 class="brand-font mb-0">Update Stok Massal</h6><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input class="form-control" type="number" name="value_stock" placeholder="Jumlah stok baru"></div><div class="modal-footer"><button type="button" class="btn btn-chipi" onclick="submitBulk('update_stock')">Terapkan</button></div></div></div></div>
</form>

<script>
const checkAll=document.getElementById('checkAll');
const rowchks=()=>document.querySelectorAll('.rowchk, .rowchk-m');
function updBar(){
  const n=[...rowchks()].filter(c=>c.checked).length;
  document.getElementById('selCount').textContent=n;
  document.getElementById('bulkBar').style.display=n>0?'block':'none';
  if(n===0) document.getElementById('selAllFiltered').value='0';
}
if(checkAll) checkAll.addEventListener('change',function(){document.querySelectorAll('.rowchk').forEach(c=>c.checked=checkAll.checked);updBar();});
rowchks().forEach(c=>c.addEventListener('change',updBar));
document.getElementById('selectFilteredLink').addEventListener('click',function(e){e.preventDefault();document.getElementById('selAllFiltered').value='1';document.querySelectorAll('.rowchk,.rowchk-m').forEach(c=>c.checked=true);if(checkAll)checkAll.checked=true;updBar();chipiToast('Semua produk hasil filter dipilih');});
function submitBulk(action){
  const any=[...rowchks()].some(c=>c.checked);
  if(!any){chipiToast('Pilih produk terlebih dahulu','error');return;}
  document.getElementById('bulkAction').value=action;
  document.getElementById('bulkForm').submit();
}
function exportSelected(){
  const ids=[...rowchks()].filter(c=>c.checked).map(c=>c.value);
  if(!ids.length){chipiToast('Pilih produk terlebih dahulu','error');return;}
  window.location=window.CHIPI_BASE+'/admin/export.php?mode=selected&ids='+ids.join(',');
}
</script>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
