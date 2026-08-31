<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin/products.php');
csrf_check();

$action = $_POST['action'] ?? '';
$ids = array_map('intval', array_merge($_POST['ids'] ?? [], $_POST['ids_m'] ?? []));
$ids = array_values(array_unique(array_filter($ids)));

// select all filtered -> rebuild id list from filter
if (($_POST['select_all_filtered'] ?? '0') === '1') {
    parse_str($_POST['filter_qs'] ?? '', $f);
    $where = ['1=1']; $params = [];
    if (!empty($f['q'])) { $where[]='(name LIKE ? OR sku LIKE ?)'; $params[]="%{$f['q']}%"; $params[]="%{$f['q']}%"; }
    if (!empty($f['category'])) { $where[]='category_id=?'; $params[]=(int)$f['category']; }
    if (!empty($f['brand'])) { $where[]='brand_id=?'; $params[]=(int)$f['brand']; }
    if (($f['status'] ?? '')==='active') $where[]='is_active=1';
    if (($f['status'] ?? '')==='inactive') $where[]='is_active=0';
    if (!empty($f['low'])) $where[]='stock_qty<=10';
    $st = db()->prepare('SELECT id FROM products WHERE '.implode(' AND ',$where));
    $st->execute($params);
    $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
}

if (!$ids) { flash('error','Tidak ada produk yang dipilih.'); redirect('admin/products.php'); }
$in = implode(',', array_fill(0, count($ids), '?'));
$pdo = db();

switch ($action) {
    case 'set_active':
        $pdo->prepare("UPDATE products SET is_active=1 WHERE id IN ($in)")->execute($ids);
        flash('success', count($ids).' produk diaktifkan.'); break;
    case 'set_inactive':
        $pdo->prepare("UPDATE products SET is_active=0 WHERE id IN ($in)")->execute($ids);
        flash('success', count($ids).' produk dinonaktifkan.'); break;
    case 'change_category':
        $v=(int)($_POST['value_category']??0);
        $st=$pdo->prepare("UPDATE products SET category_id=? WHERE id IN ($in)"); $st->execute(array_merge([$v],$ids));
        flash('success','Kategori diperbarui.'); break;
    case 'change_brand':
        $v=(int)($_POST['value_brand']??0);
        $st=$pdo->prepare("UPDATE products SET brand_id=? WHERE id IN ($in)"); $st->execute(array_merge([$v],$ids));
        flash('success','Brand diperbarui.'); break;
    case 'change_label':
        $v=$_POST['value_label']??'NONE'; if(!in_array($v,['NONE','NEW','PROMO','BEST SELLER']))$v='NONE';
        $st=$pdo->prepare("UPDATE products SET label=? WHERE id IN ($in)"); $st->execute(array_merge([$v],$ids));
        flash('success','Label diperbarui.'); break;
    case 'update_stock':
        $v=(int)($_POST['value_stock']??0);
        $st=$pdo->prepare("UPDATE products SET stock_qty=? WHERE id IN ($in)"); $st->execute(array_merge([$v],$ids));
        flash('success','Stok diperbarui.'); break;
    case 'update_price':
        $mode=$_POST['price_mode']??'set'; $val=(float)($_POST['price_value']??0);
        $expr = match($mode){
            'inc_amount'=>"price + $val",
            'dec_amount'=>"GREATEST(0, price - $val)",
            'inc_pct'=>"ROUND(price * (1 + $val/100))",
            'dec_pct'=>"GREATEST(0, ROUND(price * (1 - $val/100)))",
            default=>"$val",
        };
        $pdo->prepare("UPDATE products SET price = $expr WHERE id IN ($in)")->execute($ids);
        flash('success','Harga '.count($ids).' produk diperbarui.'); break;
    case 'delete':
        // soft delete products that exist in order history
        $chk=$pdo->prepare("SELECT DISTINCT product_id FROM order_items WHERE product_id IN ($in)");
        $chk->execute($ids);
        $used=array_map('intval',$chk->fetchAll(PDO::FETCH_COLUMN));
        $toDelete=array_diff($ids,$used);
        if($used){ $inu=implode(',',array_fill(0,count($used),'?')); $pdo->prepare("UPDATE products SET is_active=0 WHERE id IN ($inu)")->execute(array_values($used)); }
        if($toDelete){ $ind=implode(',',array_fill(0,count($toDelete),'?')); $pdo->prepare("DELETE FROM products WHERE id IN ($ind)")->execute(array_values($toDelete)); }
        flash('success', count($toDelete).' produk dihapus, '.count($used).' dinonaktifkan (ada di riwayat order).'); break;
    default:
        flash('error','Aksi tidak dikenal.');
}
redirect('admin/products.php?'.($_POST['filter_qs'] ?? ''));
