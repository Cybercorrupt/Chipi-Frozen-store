<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// All cart actions are POST + CSRF protected
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok' => false, 'msg' => 'Metode tidak valid']); exit; }
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { echo json_encode(['ok' => false, 'msg' => 'Token tidak valid']); exit; }

switch ($action) {
    case 'add':
        $id  = (int)($_POST['product_id'] ?? 0);
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        $st  = db()->prepare('SELECT id, stock_qty, is_active FROM products WHERE id = ?');
        $st->execute([$id]);
        $p = $st->fetch();
        if (!$p || !$p['is_active']) { echo json_encode(['ok' => false, 'msg' => 'Produk tidak ditemukan']); exit; }
        if ((int)$p['stock_qty'] <= 0) { echo json_encode(['ok' => false, 'msg' => 'Stok habis']); exit; }
        cart_add($id, $qty);
        echo json_encode(['ok' => true, 'count' => cart_count()]);
        break;

    case 'update':
        cart_set((int)($_POST['product_id'] ?? 0), (int)($_POST['qty'] ?? 0));
        $d = cart_detail();
        echo json_encode(['ok' => true, 'count' => $d['count'], 'subtotal' => $d['subtotal'], 'subtotal_fmt' => rupiah($d['subtotal'])]);
        break;

    case 'remove':
        cart_remove((int)($_POST['product_id'] ?? 0));
        $d = cart_detail();
        echo json_encode(['ok' => true, 'count' => $d['count'], 'subtotal_fmt' => rupiah($d['subtotal'])]);
        break;

    case 'clear':
        cart_clear();
        echo json_encode(['ok' => true, 'count' => 0]);
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Aksi tidak dikenal']);
}
