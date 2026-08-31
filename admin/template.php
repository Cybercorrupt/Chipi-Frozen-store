<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
require ROOT_PATH . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$headers = ['SKU','Product Name','Category','Brand','Price','Promo Price','Stock','Unit','Weight','Description','Status','Label','Image Filename'];

$ss = new Spreadsheet();
$sheet = $ss->getActiveSheet();
$sheet->setTitle('Produk');
$sheet->fromArray($headers, null, 'A1');
foreach (range('A','M') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
    $sheet->getStyle($col.'1')->getFont()->setBold(true);
    $sheet->getStyle($col.'1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('38B6FF');
    $sheet->getStyle($col.'1')->getFont()->getColor()->setRGB('FFFFFF');
}
// example row
$sheet->fromArray(['CHF999','Contoh Produk','Nugget','Chipi','40000','35000','25','pack','500 g','Deskripsi contoh','Active','NEW','CHF999.jpg'], null, 'A2');

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="template-produk-chipi.xlsx"');
header('Cache-Control: max-age=0');
(new Xlsx($ss))->save('php://output');
exit;
