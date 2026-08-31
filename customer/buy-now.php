<?php
require_once __DIR__ . '/../includes/functions.php';
$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT id, stock_qty, is_active FROM products WHERE id=?');
$st->execute([$id]);
$p = $st->fetch();
if ($p && $p['is_active'] && (int)$p['stock_qty'] > 0) {
    cart_add($id, 1);
    redirect('customer/checkout.php');
}
flash('error', 'Produk tidak tersedia.');
redirect('customer/products.php');
