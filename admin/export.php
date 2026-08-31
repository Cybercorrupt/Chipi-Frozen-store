<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
require ROOT_PATH . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$mode = $_GET['mode'] ?? 'all'; // all | filtered | selected
$sql = "SELECT p.*, c.name category_name, b.name brand_name FROM products p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN brands b ON b.id=p.brand_id";
$params = [];

if ($mode === 'selected' && !empty($_GET['ids'])) {
    $ids = array_map('intval', explode(',', $_GET['ids']));
    $in = implode(',', array_fill(0, count($ids), '?'));
    $sql .= " WHERE p.id IN ($in)"; $params = $ids;
} elseif ($mode === 'filtered') {
    parse_str($_GET['filter_qs'] ?? '', $f);
    $where = [];
    if (!empty($f['q'])) { $where[]='(p.name LIKE ? OR p.sku LIKE ?)'; $params[]="%{$f['q']}%"; $params[]="%{$f['q']}%"; }
    if (!empty($f['category'])) { $where[]='p.category_id=?'; $params[]=(int)$f['category']; }
    if (!empty($f['brand'])) { $where[]='p.brand_id=?'; $params[]=(int)$f['brand']; }
    if (($f['status'] ?? '')==='active') $where[]='p.is_active=1';
    if (($f['status'] ?? '')==='inactive') $where[]='p.is_active=0';
    if (!empty($f['low'])) $where[]='p.stock_qty<=10';
    if ($where) $sql .= ' WHERE '.implode(' AND ',$where);
}
$sql .= ' ORDER BY p.id DESC';
$st = db()->prepare($sql); $st->execute($params);
$rows = $st->fetchAll();

$ss = new Spreadsheet();
$sheet = $ss->getActiveSheet();
$sheet->setTitle('Produk');
$headers = ['SKU','Product Name','Category','Brand','Price','Promo Price','Stock','Unit','Weight','Description','Status','Label','Image Filename'];
$sheet->fromArray($headers, null, 'A1');
foreach (range('A','M') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
    $sheet->getStyle($col.'1')->getFont()->setBold(true);
    $sheet->getStyle($col.'1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('38B6FF');
    $sheet->getStyle($col.'1')->getFont()->getColor()->setRGB('FFFFFF');
}
$r = 2;
foreach ($rows as $p) {
    $sheet->fromArray([
        $p['sku'], $p['name'], $p['category_name'], $p['brand_name'],
        (float)$p['price'], $p['promo_price']!==null?(float)$p['promo_price']:'',
        (int)$p['stock_qty'], $p['unit'], $p['weight'], $p['description'],
        $p['is_active']?'Active':'Inactive', $p['label']==='NONE'?'':$p['label'], $p['image'],
    ], null, 'A'.$r);
    $r++;
}
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="produk-chipi-'.date('Ymd-His').'.xlsx"');
header('Cache-Control: max-age=0');
(new Xlsx($ss))->save('php://output');
exit;
