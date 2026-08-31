<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$p = ['id'=>0,'sku'=>'','name'=>'','category_id'=>'','brand_id'=>'','price'=>'','promo_price'=>'','stock_qty'=>0,'unit'=>'pcs','weight'=>'','description'=>'','image'=>'','label'=>'NONE','is_active'=>1];
if ($id) {
    $st = db()->prepare('SELECT * FROM products WHERE id=?'); $st->execute([$id]);
    $p = $st->fetch() ?: $p;
}
$pageTitle = $id ? 'Edit Produk' : 'Tambah Produk';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $sku = trim($_POST['sku'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $promo = $_POST['promo_price']!=='' ? (float)$_POST['promo_price'] : null;
    if ($sku==='' || $name==='' || $price<=0) { $err='SKU, nama, dan harga wajib diisi.'; }
    else {
        // unique sku
        $chk = db()->prepare('SELECT id FROM products WHERE sku=? AND id<>?'); $chk->execute([$sku,$id]);
        if ($chk->fetch()) { $err='SKU sudah digunakan produk lain.'; }
        else {
            $image = $p['image'];
            if (!empty($_FILES['image']['name']) && $_FILES['image']['error']===UPLOAD_ERR_OK) {
                $up = handle_image_upload($_FILES['image'], $sku);
                if ($up['ok']) $image = $up['file']; else $err = $up['msg'];
            }
            if (!$err) {
                $data = [$sku,$name,(int)($_POST['category_id']?:0)?:null,(int)($_POST['brand_id']?:0)?:null,$price,$promo,(int)($_POST['stock_qty']??0),trim($_POST['unit']??'pcs'),trim($_POST['weight']??''),trim($_POST['description']??''),$image,$_POST['label']??'NONE',isset($_POST['is_active'])?1:0];
                if ($id) {
                    db()->prepare('UPDATE products SET sku=?,name=?,category_id=?,brand_id=?,price=?,promo_price=?,stock_qty=?,unit=?,weight=?,description=?,image=?,label=?,is_active=? WHERE id=?')->execute(array_merge($data,[$id]));
                    flash('success','Produk diperbarui.');
                } else {
                    db()->prepare('INSERT INTO products (sku,name,category_id,brand_id,price,promo_price,stock_qty,unit,weight,description,image,label,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($data);
                    flash('success','Produk ditambahkan.');
                }
                redirect('admin/products.php');
            }
        }
    }
    $p = array_merge($p, $_POST);
}

function handle_image_upload(array $file, string $sku): array {
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    $info = @getimagesize($file['tmp_name']);
    if (!$info || !isset($allowed[$info['mime']])) return ['ok'=>false,'msg'=>'File gambar tidak valid (hanya JPG/PNG/WEBP).'];
    if ($file['size'] > 3*1024*1024) return ['ok'=>false,'msg'=>'Ukuran gambar maksimal 3MB.'];
    if (!is_dir(PRODUCT_IMG_PATH)) @mkdir(PRODUCT_IMG_PATH,0775,true);
    $fname = preg_replace('/[^A-Za-z0-9_-]/','',$sku).'_'.time().'.'.$allowed[$info['mime']];
    if (!move_uploaded_file($file['tmp_name'], PRODUCT_IMG_PATH.'/'.$fname)) return ['ok'=>false,'msg'=>'Gagal menyimpan gambar.'];
    return ['ok'=>true,'file'=>$fname];
}

$cats = db()->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$brands = db()->query("SELECT * FROM brands ORDER BY name")->fetchAll();
require __DIR__ . '/includes/admin_header.php';
?>
<a href="<?= url('admin/products.php') ?>" class="small text-muted-chipi"><i class="fa-solid fa-arrow-left me-1"></i>Kembali</a>
<div class="card p-3 p-md-4 mt-2">
  <h5 class="brand-font"><?= $pageTitle ?></h5>
  <?php if($err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-8">
        <div class="row g-3">
          <div class="col-md-4"><label class="form-label small fw-bold">SKU</label><input class="form-control" name="sku" value="<?= e($p['sku']) ?>" required data-testid="form-sku"></div>
          <div class="col-md-8"><label class="form-label small fw-bold">Nama Produk</label><input class="form-control" name="name" value="<?= e($p['name']) ?>" required data-testid="form-name"></div>
          <div class="col-md-6"><label class="form-label small fw-bold">Kategori</label><select class="form-select" name="category_id"><option value="">-</option><?php foreach($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $p['category_id']==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
          <div class="col-md-6"><label class="form-label small fw-bold">Brand</label><select class="form-select" name="brand_id"><option value="">-</option><?php foreach($brands as $b): ?><option value="<?= $b['id'] ?>" <?= $p['brand_id']==$b['id']?'selected':'' ?>><?= e($b['name']) ?></option><?php endforeach; ?></select></div>
          <div class="col-md-4"><label class="form-label small fw-bold">Harga</label><input class="form-control" type="number" step="1" name="price" value="<?= e($p['price']) ?>" required data-testid="form-price"></div>
          <div class="col-md-4"><label class="form-label small fw-bold">Harga Promo</label><input class="form-control" type="number" step="1" name="promo_price" value="<?= e($p['promo_price']) ?>"></div>
          <div class="col-md-4"><label class="form-label small fw-bold">Stok</label><input class="form-control" type="number" name="stock_qty" value="<?= e($p['stock_qty']) ?>" data-testid="form-stock"></div>
          <div class="col-md-4"><label class="form-label small fw-bold">Satuan</label><input class="form-control" name="unit" value="<?= e($p['unit']) ?>"></div>
          <div class="col-md-4"><label class="form-label small fw-bold">Berat</label><input class="form-control" name="weight" value="<?= e($p['weight']) ?>" placeholder="500 g"></div>
          <div class="col-md-4"><label class="form-label small fw-bold">Label</label><select class="form-select" name="label"><?php foreach(['NONE','NEW','PROMO','BEST SELLER'] as $l): ?><option value="<?= $l ?>" <?= $p['label']===$l?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
          <div class="col-12"><label class="form-label small fw-bold">Deskripsi</label><textarea class="form-control" name="description" rows="3"><?= e($p['description']) ?></textarea></div>
          <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?= $p['is_active']?'checked':'' ?>><label class="form-check-label" for="isActive">Produk Aktif</label></div></div>
        </div>
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-bold">Gambar Produk</label>
        <div class="border rounded-3 p-2 text-center mb-2"><img src="<?= product_image_url($p['image']) ?>" class="img-fluid rounded" style="max-height:180px"></div>
        <input class="form-control" type="file" name="image" accept="image/*" data-testid="form-image">
        <small class="text-muted-chipi">JPG/PNG/WEBP, maks 3MB.</small>
      </div>
    </div>
    <div class="d-flex gap-2 mt-3">
      <button class="btn btn-chipi" data-testid="form-save"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan</button>
      <a href="<?= url('admin/products.php') ?>" class="btn btn-outline-secondary">Batal</a>
    </div>
  </form>
  <?php if($id): ?>
    <form method="post" action="<?= url('admin/product-action.php') ?>" class="mt-3" data-confirm="Hapus produk ini?">
      <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="ids[]" value="<?= $id ?>">
      <button class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-trash me-1"></i>Hapus Produk</button>
    </form>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
