<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
require ROOT_PATH . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// ---- same range logic as reports.php ----
$range = $_GET['range'] ?? '7';
$from = $_GET['from'] ?? ''; $to = $_GET['to'] ?? '';
$today = date('Y-m-d');
if ($range === 'today') { $from = $to = $today; }
elseif ($range === 'month') { $from = date('Y-m-01'); $to = $today; }
elseif ($range === 'custom' && $from && $to) { /* as is */ }
else { $range = '7'; $from = date('Y-m-d', strtotime('-6 days')); $to = $today; }

function rq($sql, $p){ $s = db()->prepare($sql); $s->execute($p); return $s->fetchColumn(); }
$p = [$from.' 00:00:00', $to.' 23:59:59'];

$totalOrders = (int)rq("SELECT COUNT(*) FROM orders WHERE created_at BETWEEN ? AND ?", $p);
$totalSales  = (float)rq("SELECT COALESCE(SUM(grand_total),0) FROM orders WHERE created_at BETWEEN ? AND ? AND order_status<>'Dibatalkan'", $p);
$completed   = (int)rq("SELECT COUNT(*) FROM orders WHERE created_at BETWEEN ? AND ? AND order_status='Selesai'", $p);
$cancelled   = (int)rq("SELECT COUNT(*) FROM orders WHERE created_at BETWEEN ? AND ? AND order_status='Dibatalkan'", $p);
$productsSold= (int)rq("SELECT COALESCE(SUM(oi.qty),0) FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.created_at BETWEEN ? AND ? AND o.order_status<>'Dibatalkan'", $p);

$topSt = db()->prepare("SELECT oi.product_name, oi.sku, SUM(oi.qty) qty, SUM(oi.subtotal) total FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.created_at BETWEEN ? AND ? AND o.order_status<>'Dibatalkan' GROUP BY oi.product_name, oi.sku ORDER BY qty DESC LIMIT 5");
$topSt->execute($p); $top = $topSt->fetchAll();

$ordSt = db()->prepare("SELECT order_number, customer_name, order_status, payment_method, grand_total, created_at FROM orders WHERE created_at BETWEEN ? AND ? ORDER BY id DESC");
$ordSt->execute($p); $orders = $ordSt->fetchAll();

$ss = new Spreadsheet();

// ---- Sheet 1: Ringkasan ----
$s = $ss->getActiveSheet(); $s->setTitle('Ringkasan');
$s->setCellValue('A1', 'LAPORAN CHIPI FROZEN FOOD');
$s->mergeCells('A1:D1');
$s->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('0E2A49');
$s->setCellValue('A2', 'Periode: '.$from.' s/d '.$to);
$s->mergeCells('A2:D2');
$rows = [
    ['Total Pesanan', $totalOrders],
    ['Total Penjualan', $totalSales],
    ['Produk Terjual', $productsSold],
    ['Pesanan Selesai', $completed],
    ['Pesanan Dibatalkan', $cancelled],
];
$r = 4;
foreach ($rows as $row) {
    $s->setCellValue("A$r", $row[0]);
    $s->setCellValue("B$r", $row[1]);
    $s->getStyle("A$r")->getFont()->setBold(true);
    $r++;
}
$s->getStyle('B5')->getNumberFormat()->setFormatCode('#,##0');

$r += 1;
$s->setCellValue("A$r", 'TOP 5 PRODUK TERLARIS');
$s->getStyle("A$r")->getFont()->setBold(true)->setSize(12);
$r++;
$hdr = $r;
$s->fromArray(['#','SKU','Produk','Qty Terjual','Total'], null, "A$r"); $r++;
foreach ($top as $i => $t) {
    $s->fromArray([$i+1, $t['sku'], $t['product_name'], (int)$t['qty'], (float)$t['total']], null, "A$r"); $r++;
}
foreach (['A','B','C','D','E'] as $c) {
    $s->getColumnDimension($c)->setAutoSize(true);
    $s->getStyle($c.$hdr)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
    $s->getStyle($c.$hdr)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('38B6FF');
}

// ---- Sheet 2: Daftar Pesanan ----
$s2 = $ss->createSheet(); $s2->setTitle('Daftar Pesanan');
$s2->fromArray(['No. Pesanan','Pelanggan','Status','Pembayaran','Grand Total','Tanggal'], null, 'A1');
$rr = 2;
foreach ($orders as $o) {
    $s2->fromArray([$o['order_number'],$o['customer_name'],$o['order_status'],$o['payment_method'],(float)$o['grand_total'],date('Y-m-d H:i',strtotime($o['created_at']))], null, "A$rr");
    $rr++;
}
foreach (range('A','F') as $c) {
    $s2->getColumnDimension($c)->setAutoSize(true);
    $s2->getStyle($c.'1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
    $s2->getStyle($c.'1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0E2A49');
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="laporan-chipi-'.$from.'_sd_'.$to.'.xlsx"');
header('Cache-Control: max-age=0');
(new Xlsx($ss))->save('php://output');
exit;
