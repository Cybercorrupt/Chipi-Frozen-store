<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
require ROOT_PATH . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$pageTitle = 'Import / Export Produk';
$TMP = UPLOAD_PATH . '/tmp';
if (!is_dir($TMP)) @mkdir($TMP, 0775, true);

/**
 * Core engine: reads xlsx (+ optional zip), analyzes rows.
 * When $commit=true, writes to DB and extracts images.
 * Returns ['summary'=>[...], 'rows'=>[...]].
 */
function run_import(string $xlsx, ?string $zip, bool $commit): array
{
    $pdo = db();
    $spreadsheet = IOFactory::load($xlsx);
    $sheet = $spreadsheet->getActiveSheet();
    $data = $sheet->toArray(null, true, false, false);

    // header map
    $head = array_map(fn($h) => strtolower(trim((string)$h)), $data[0] ?? []);
    $col = function ($name) use ($head) { $i = array_search(strtolower($name), $head); return $i === false ? null : $i; };
    $map = [
        'sku'=>$col('SKU'),'name'=>$col('Product Name'),'category'=>$col('Category'),'brand'=>$col('Brand'),
        'price'=>$col('Price'),'promo'=>$col('Promo Price'),'stock'=>$col('Stock'),'unit'=>$col('Unit'),
        'weight'=>$col('Weight'),'desc'=>$col('Description'),'status'=>$col('Status'),'label'=>$col('Label'),'image'=>$col('Image Filename'),
    ];

    // zip entries
    $zipEntries = [];
    $za = null;
    if ($zip && class_exists('ZipArchive')) {
        $za = new ZipArchive();
        if ($za->open($zip) === true) {
            for ($i=0;$i<$za->numFiles;$i++) { $n = basename($za->getNameIndex($i)); if ($n!=='') $zipEntries[strtolower($n)] = $za->getNameIndex($i); }
        } else { $za = null; }
    }

    $val = function ($row, $key) use ($map) { $i = $map[$key]; return $i===null ? '' : trim((string)($row[$i] ?? '')); };

    $summary = ['total'=>0,'new'=>0,'update'=>0,'unchanged'=>0,'warning'=>0,'error'=>0];
    $rows = [];

    // lookup caches
    $catCache=[]; $brandCache=[];
    $getCat = function($name) use (&$catCache,$pdo,$commit){ $name=trim($name); if($name==='')return null; $k=strtolower($name); if(isset($catCache[$k]))return $catCache[$k];
        $s=$pdo->prepare('SELECT id FROM categories WHERE LOWER(name)=?'); $s->execute([$k]); $id=$s->fetchColumn();
        if(!$id && $commit){ $slug=trim(strtolower(preg_replace('/[^a-z0-9]+/i','-',$name)),'-'); $pdo->prepare('INSERT INTO categories (name,slug) VALUES (?,?)')->execute([$name,$slug]); $id=$pdo->lastInsertId(); }
        return $catCache[$k]=($id?:null); };
    $getBrand = function($name) use (&$brandCache,$pdo,$commit){ $name=trim($name); if($name==='')return null; $k=strtolower($name); if(isset($brandCache[$k]))return $brandCache[$k];
        $s=$pdo->prepare('SELECT id FROM brands WHERE LOWER(name)=?'); $s->execute([$k]); $id=$s->fetchColumn();
        if(!$id && $commit){ $pdo->prepare('INSERT INTO brands (name) VALUES (?)')->execute([$name]); $id=$pdo->lastInsertId(); }
        return $brandCache[$k]=($id?:null); };

    $total = count($data);
    for ($r = 1; $r < $total; $r++) {
        $row = $data[$r];
        if ($row === null) continue;
        // skip fully empty
        if (count(array_filter($row, fn($c)=>trim((string)$c)!=='')) === 0) continue;

        $summary['total']++;
        $sku = $val($row,'sku');
        $rec = ['sku'=>$sku,'name'=>$val($row,'name'),'status'=>'', 'note'=>''];

        if ($sku === '') { $rec['status']='error'; $rec['note']='SKU kosong'; $summary['error']++; $rows[]=$rec; continue; }

        $ex = $pdo->prepare('SELECT * FROM products WHERE sku=?'); $ex->execute([$sku]); $existing = $ex->fetch();

        $name = $val($row,'name');
        $price = $val($row,'price');
        if (!$existing && ($name==='' || $price==='' || (float)$price<=0)) {
            $rec['status']='error'; $rec['note']='Produk baru wajib ada Nama & Harga'; $summary['error']++; $rows[]=$rec; continue;
        }

        // image analysis
        $imgName = $val($row,'image');
        $imgAction = 'keep';
        if ($imgName !== '') {
            if (!$za) { $rec['note'] .= 'ZIP gambar tidak diunggah; gambar dilewati. '; $imgAction='missing_zip'; }
            elseif (!isset($zipEntries[strtolower($imgName)])) { $rec['note'] .= "Gambar '$imgName' tidak ada di ZIP. "; $imgAction='missing'; }
            else { $imgAction='import'; }
        }

        // build field values honoring blank=keep, [CLEAR]=clear
        $resolve = function($raw, $existingVal, $numeric=false) {
            $raw = trim((string)$raw);
            if ($raw === '') return $existingVal; // keep
            if (strtoupper($raw) === '[CLEAR]') return $numeric ? 0 : null;
            return $raw;
        };

        if ($existing) {
            $nName = $name!==''?$name:$existing['name'];
            $nPrice = $price!==''?(float)$price:(float)$existing['price'];
            $promoRaw=$val($row,'promo'); $nPromo = $promoRaw===''?$existing['promo_price']:(strtoupper($promoRaw)==='[CLEAR]'?null:(float)$promoRaw);
            $stockRaw=$val($row,'stock'); $nStock = $stockRaw===''?(int)$existing['stock_qty']:(int)$stockRaw;
            $nUnit = $resolve($val($row,'unit'),$existing['unit']);
            $nWeight = $resolve($val($row,'weight'),$existing['weight']);
            $nDesc = $resolve($val($row,'desc'),$existing['description']);
            $statusRaw=$val($row,'status'); $nActive = $statusRaw===''?(int)$existing['is_active']:(strtolower($statusRaw)==='active'?1:0);
            $labelRaw=strtoupper($val($row,'label')); $nLabel = $labelRaw===''?$existing['label']:(in_array($labelRaw,['NEW','PROMO','BEST SELLER'])?$labelRaw:'NONE');
            $catId = $val($row,'category')!==''?$getCat($val($row,'category')):$existing['category_id'];
            $brId  = $val($row,'brand')!==''?$getBrand($val($row,'brand')):$existing['brand_id'];

            // detect change
            $changed = ($nName!=$existing['name']) || ((float)$nPrice!=(float)$existing['price']) || ($nStock!=(int)$existing['stock_qty'])
                || ($nActive!=(int)$existing['is_active']) || ($nLabel!=$existing['label']) || ($imgAction==='import')
                || ($nUnit!=$existing['unit']) || ($nWeight!=$existing['weight']) || ((string)$nDesc!=(string)$existing['description'])
                || ((string)$nPromo!=(string)$existing['promo_price']) || ($catId!=$existing['category_id']) || ($brId!=$existing['brand_id']);

            if (!$changed) { $rec['status']='unchanged'; $summary['unchanged']++; }
            else { $rec['status']='update'; $summary['update']++; }
            if ($rec['note']!=='') $summary['warning']++;

            if ($commit && $changed) {
                $img = $existing['image'];
                if ($imgAction==='import') { $saved = extract_zip_image($za,$zipEntries[strtolower($imgName)],$sku); if($saved) $img=$saved; }
                $pdo->prepare('UPDATE products SET name=?,category_id=?,brand_id=?,price=?,promo_price=?,stock_qty=?,unit=?,weight=?,description=?,is_active=?,label=?,image=? WHERE id=?')
                    ->execute([$nName,$catId,$brId,$nPrice,$nPromo,$nStock,$nUnit,$nWeight,$nDesc,$nActive,$nLabel,$img,$existing['id']]);
            }
        } else {
            $rec['status']='new'; $summary['new']++;
            if ($rec['note']!=='') $summary['warning']++;
            if ($commit) {
                $img = null;
                if ($imgAction==='import') { $saved = extract_zip_image($za,$zipEntries[strtolower($imgName)],$sku); if($saved) $img=$saved; }
                $catId = $getCat($val($row,'category')); $brId = $getBrand($val($row,'brand'));
                $promoRaw=$val($row,'promo'); $promo = ($promoRaw===''||strtoupper($promoRaw)==='[CLEAR]')?null:(float)$promoRaw;
                $labelRaw=strtoupper($val($row,'label')); $label=in_array($labelRaw,['NEW','PROMO','BEST SELLER'])?$labelRaw:'NONE';
                $active = strtolower($val($row,'status'))==='inactive'?0:1;
                $pdo->prepare('INSERT INTO products (sku,name,category_id,brand_id,price,promo_price,stock_qty,unit,weight,description,image,label,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute([$sku,$name,$catId,$brId,(float)$price,$promo,(int)($val($row,'stock')?:0),$val($row,'unit')?:'pcs',$val($row,'weight'),$val($row,'desc'),$img,$label,$active]);
            }
        }
        $rows[] = $rec;
    }
    if ($za) $za->close();
    return ['summary'=>$summary,'rows'=>$rows];
}

function extract_zip_image(ZipArchive $za, string $entry, string $sku): ?string {
    $content = $za->getFromName($entry);
    if ($content === false) return null;
    $info = @getimagesizefromstring($content);
    $map = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!$info || !isset($map[$info['mime']])) return null; // invalid image -> skip
    if (!is_dir(PRODUCT_IMG_PATH)) @mkdir(PRODUCT_IMG_PATH,0775,true);
    $fname = preg_replace('/[^A-Za-z0-9_-]/','',$sku).'_'.time().'.'.$map[$info['mime']];
    file_put_contents(PRODUCT_IMG_PATH.'/'.$fname, $content);
    return $fname;
}

$preview = null; $summary = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $do = $_POST['do'] ?? '';

    if ($do === 'preview') {
        if (empty($_FILES['xlsx']['name']) || $_FILES['xlsx']['error']!==UPLOAD_ERR_OK) { flash('error','Pilih file Excel (.xlsx) terlebih dahulu.'); redirect('admin/import.php'); }
        $ext = strtolower(pathinfo($_FILES['xlsx']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext,['xlsx','xls'])) { flash('error','File harus berformat .xlsx.'); redirect('admin/import.php'); }
        $token = bin2hex(random_bytes(8));
        $dir = "$TMP/$token"; @mkdir($dir,0775,true);
        $xlsxPath = "$dir/products.$ext";
        move_uploaded_file($_FILES['xlsx']['tmp_name'], $xlsxPath);
        $zipPath = null;
        if (!empty($_FILES['zip']['name']) && $_FILES['zip']['error']===UPLOAD_ERR_OK) {
            if (strtolower(pathinfo($_FILES['zip']['name'],PATHINFO_EXTENSION))==='zip') { $zipPath="$dir/images.zip"; move_uploaded_file($_FILES['zip']['tmp_name'],$zipPath); }
        }
        try {
            $res = run_import($xlsxPath, $zipPath, false);
            $_SESSION['import_token'] = ['token'=>$token,'xlsx'=>$xlsxPath,'zip'=>$zipPath];
            $preview = $res['rows']; $summary = $res['summary'];
        } catch (Throwable $e) { flash('error','Gagal membaca file: '.$e->getMessage()); redirect('admin/import.php'); }
    }

    if ($do === 'process') {
        $tok = $_SESSION['import_token'] ?? null;
        if (!$tok || !is_file($tok['xlsx'])) { flash('error','Sesi import kedaluwarsa. Ulangi unggah.'); redirect('admin/import.php'); }
        try {
            $res = run_import($tok['xlsx'], $tok['zip'], true);
            $s = $res['summary'];
            flash('success', "Import selesai: {$s['new']} baru, {$s['update']} diperbarui, {$s['unchanged']} tanpa perubahan, {$s['error']} error.");
        } catch (Throwable $e) { flash('error','Gagal memproses import: '.$e->getMessage()); }
        // cleanup
        if (is_dir($TMP.'/'.$tok['token'])) { array_map('unlink', glob($TMP.'/'.$tok['token'].'/*')); @rmdir($TMP.'/'.$tok['token']); }
        unset($_SESSION['import_token']);
        redirect('admin/products.php');
    }

    if ($do === 'cancel') { unset($_SESSION['import_token']); redirect('admin/import.php'); }
}

require __DIR__ . '/includes/admin_header.php';
?>
<?php if ($preview === null): ?>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="card p-3 p-md-4">
      <h6 class="brand-font"><i class="fa-solid fa-file-import me-1"></i>Import Produk (Excel)</h6>
      <p class="small text-muted-chipi">Unggah <b>products.xlsx</b> dan opsional <b>product-images.zip</b>. Sistem mencocokkan gambar berdasarkan kolom <b>Image Filename</b>. SKU adalah identifier: SKU baru = tambah, SKU sudah ada = update. Sel kosong tidak menghapus data lama (gunakan <code>[CLEAR]</code> untuk mengosongkan).</p>
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?><input type="hidden" name="do" value="preview">
        <label class="form-label small fw-bold">File Excel (.xlsx)</label>
        <input class="form-control mb-3" type="file" name="xlsx" accept=".xlsx,.xls" required data-testid="import-xlsx">
        <label class="form-label small fw-bold">Gambar Produk (.zip) <span class="text-muted-chipi">— opsional</span></label>
        <input class="form-control mb-3" type="file" name="zip" accept=".zip" data-testid="import-zip">
        <button class="btn btn-chipi" data-testid="import-preview-btn"><i class="fa-solid fa-magnifying-glass me-1"></i>Preview Import</button>
      </form>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card p-3 p-md-4">
      <h6 class="brand-font"><i class="fa-solid fa-file-export me-1"></i>Template & Export</h6>
      <div class="d-grid gap-2">
        <a href="<?= url('admin/template.php') ?>" class="btn btn-outline-chipi" data-testid="download-template"><i class="fa-solid fa-download me-1"></i>Download Template</a>
        <a href="<?= url('admin/export.php?mode=all') ?>" class="btn btn-chipi-blue" data-testid="export-all"><i class="fa-solid fa-file-excel me-1"></i>Export Semua Produk</a>
      </div>
      <hr>
      <p class="small text-muted-chipi mb-1"><b>Kolom template:</b></p>
      <p class="small text-muted-chipi">SKU, Product Name, Category, Brand, Price, Promo Price, Stock, Unit, Weight, Description, Status, Label, Image Filename</p>
    </div>
  </div>
</div>
<?php else: ?>
<div class="card p-3 mb-3">
  <h6 class="brand-font">Preview Import</h6>
  <div class="row g-2 text-center mb-2">
    <div class="col"><div class="card p-2"><div class="fs-4 fw-bold"><?= $summary['total'] ?></div><div class="small text-muted-chipi">Total Baris</div></div></div>
    <div class="col"><div class="card p-2"><div class="fs-4 fw-bold text-success"><?= $summary['new'] ?></div><div class="small text-muted-chipi">Baru</div></div></div>
    <div class="col"><div class="card p-2"><div class="fs-4 fw-bold text-primary"><?= $summary['update'] ?></div><div class="small text-muted-chipi">Update</div></div></div>
    <div class="col"><div class="card p-2"><div class="fs-4 fw-bold text-secondary"><?= $summary['unchanged'] ?></div><div class="small text-muted-chipi">Tanpa Ubah</div></div></div>
    <div class="col"><div class="card p-2"><div class="fs-4 fw-bold text-warning"><?= $summary['warning'] ?></div><div class="small text-muted-chipi">Warning</div></div></div>
    <div class="col"><div class="card p-2"><div class="fs-4 fw-bold text-danger"><?= $summary['error'] ?></div><div class="small text-muted-chipi">Error</div></div></div>
  </div>
  <div class="table-responsive" style="max-height:340px">
    <table class="table table-sm table-chipi mb-0"><thead><tr><th>SKU</th><th>Nama</th><th>Aksi</th><th>Catatan</th></tr></thead><tbody>
      <?php foreach($preview as $row): 
        $bmap=['new'=>'success','update'=>'primary','unchanged'=>'secondary','error'=>'danger'];
        $b=$bmap[$row['status']]??'secondary'; ?>
        <tr><td class="small"><?= e($row['sku']) ?></td><td class="small"><?= e($row['name']) ?></td><td><span class="badge bg-<?= $b ?>"><?= strtoupper($row['status']) ?></span></td><td class="small text-warning"><?= e($row['note']) ?></td></tr>
      <?php endforeach; ?>
    </tbody></table>
  </div>
  <div class="d-flex gap-2 mt-3">
    <form method="post" data-confirm="Proses import sekarang?"><?= csrf_field() ?><input type="hidden" name="do" value="process"><button class="btn btn-chipi" data-testid="import-process-btn" <?= $summary['error']>0 && ($summary['new']+$summary['update'])===0?'disabled':'' ?>><i class="fa-solid fa-check me-1"></i>Proses Import</button></form>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="cancel"><button class="btn btn-outline-secondary">Batal</button></form>
  </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
