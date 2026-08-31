<?php
require_once __DIR__ . '/../config/db.php';

/* ---------------- Output / URL helpers ---------------- */
function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function asset(string $path = ''): string { return BASE_URL . '/assets/' . ltrim($path, '/'); }
function redirect(string $path): void { header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path))); exit; }

function rupiah($n): string { return 'Rp' . number_format((float)$n, 0, ',', '.'); }

/* ---------------- Flash messages ---------------- */
function flash(string $type, string $msg): void { $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg]; }
function get_flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

/* ---------------- CSRF ---------------- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void {
    $t = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(419);
        die('Sesi kedaluwarsa / token CSRF tidak valid. Silakan muat ulang halaman.');
    }
}

/* ---------------- Settings ---------------- */
function settings(): array {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT skey, svalue FROM settings') as $r) { $cache[$r['skey']] = $r['svalue']; }
    }
    return $cache;
}
function setting(string $k, $default = ''): string {
    $s = settings();
    return $s[$k] ?? $default;
}

/**
 * Shipping/delivery methods (configurable via admin settings).
 * Returns array of ['name'=>string,'cost'=>float,'active'=>int].
 * Pass true to get only active methods.
 */
function shipping_methods(bool $activeOnly = true): array {
    $raw = setting('shipping_methods', '');
    $list = $raw ? json_decode($raw, true) : null;
    if (!is_array($list) || !$list) {
        $list = [
            ['name' => 'Delivery', 'cost' => (float)setting('shipping_cost', 0), 'active' => 1],
            ['name' => 'Pickup di Toko', 'cost' => 0, 'active' => 1],
        ];
    }
    $out = [];
    foreach ($list as $m) {
        $name = trim((string)($m['name'] ?? ''));
        if ($name === '') continue;
        $item = ['name' => $name, 'cost' => (float)($m['cost'] ?? 0), 'active' => (int)($m['active'] ?? 1)];
        if ($activeOnly && !$item['active']) continue;
        $out[] = $item;
    }
    return $out;
}

/* ---------------- Frontend content (configurable via admin settings) ---------------- */

/** Resolve a stored menu URL: external (http...) as-is, else through url(). */
function nav_url(string $u): string {
    $u = trim($u);
    if ($u === '') return url('index.php');
    if (preg_match('#^(https?:)?//#i', $u) || str_starts_with($u, 'mailto:') || str_starts_with($u, 'tel:')) return $u;
    return url($u);
}

/** Default frontend content values (used when a setting is empty). */
function fe_defaults(): array {
    return [
        'hero_show'      => '1',
        'hero_badge'     => 'Frozen Food Berkualitas',
        'hero_title'     => "Frozen Food Favorit,\nTinggal Masak! 🍢",
        'hero_subtitle'  => 'Nugget, sosis, bakso, dimsum & seafood beku pilihan. Praktis, higienis, dan harga terjangkau — langsung diantar ke rumah Anda.',
        'hero_cta_text'  => 'Belanja Sekarang',
        'hero_cta_link'  => 'customer/products.php',
        'promo_show'     => '1',
        'promo_title'    => 'Promo Spesial Chipi!',
        'promo_text'     => 'Pakai kode CHIPI10 untuk diskon 10% (min. belanja Rp50.000).',
        'promo_btn_text' => 'Lihat Produk Promo',
        'promo_btn_link' => 'customer/products.php?promo=1',
        'benefits_show'  => '1',
        'benefits_title' => 'Kenapa Chipi?',
        'footer_text'    => 'Frozen Food Favorit, Tinggal Masak!',
    ];
}

/** Get a frontend content setting, falling back to its built-in default. */
function fe(string $key): string {
    $val = setting($key, '__NULL__');
    if ($val === '__NULL__' || $val === '') {
        $d = fe_defaults();
        return $d[$key] ?? '';
    }
    return $val;
}

/** Is a toggle-able frontend section visible? Defaults to shown. */
function fe_show(string $key): bool {
    $val = setting($key, '1');
    return $val === '' ? true : ($val === '1' || $val === 1);
}

function default_benefits(): array {
    return [
        ['icon' => 'fa-award',       'color' => '#38b6ff', 'title' => 'Produk Berkualitas', 'desc' => 'Beku higienis & terjaga kesegarannya'],
        ['icon' => 'fa-bolt',        'color' => '#ff7a29', 'title' => 'Praktis',            'desc' => 'Tinggal goreng atau kukus, siap saji'],
        ['icon' => 'fa-tag',         'color' => '#1b8a4b', 'title' => 'Harga Terjangkau',   'desc' => 'Harga ramah untuk keluarga'],
        ['icon' => 'fa-truck-fast',  'color' => '#6a3fd6', 'title' => 'Mudah Dipesan',      'desc' => 'Order online, diantar ke rumah'],
    ];
}
function benefit_items(): array {
    $raw = setting('benefits_items', '');
    $list = $raw ? json_decode($raw, true) : null;
    if (!is_array($list) || !$list) return default_benefits();
    $out = [];
    foreach ($list as $b) {
        $title = trim((string)($b['title'] ?? ''));
        if ($title === '') continue;
        $out[] = [
            'icon'  => trim((string)($b['icon'] ?? 'fa-star')) ?: 'fa-star',
            'color' => trim((string)($b['color'] ?? '#38b6ff')) ?: '#38b6ff',
            'title' => $title,
            'desc'  => trim((string)($b['desc'] ?? '')),
        ];
    }
    return $out ?: default_benefits();
}

function default_nav(string $which): array {
    if ($which === 'footer') {
        return [
            ['label' => 'Home',         'url' => 'index.php'],
            ['label' => 'Produk',       'url' => 'customer/products.php'],
            ['label' => 'Keranjang',    'url' => 'customer/cart.php'],
            ['label' => 'Pesanan Saya', 'url' => 'customer/orders.php'],
        ];
    }
    return [
        ['label' => 'Home',     'url' => 'index.php'],
        ['label' => 'Produk',   'url' => 'customer/products.php'],
        ['label' => 'Kategori', 'url' => 'customer/products.php#kategori'],
    ];
}
function nav_links(string $which): array {
    $key = $which === 'footer' ? 'nav_footer' : 'nav_header';
    $raw = setting($key, '');
    $list = $raw ? json_decode($raw, true) : null;
    if (!is_array($list) || !$list) return default_nav($which);
    $out = [];
    foreach ($list as $l) {
        $label = trim((string)($l['label'] ?? ''));
        if ($label === '') continue;
        $out[] = ['label' => $label, 'url' => trim((string)($l['url'] ?? 'index.php'))];
    }
    return $out ?: default_nav($which);
}


/* ---------------- Stock status (customer facing) ---------------- */
function stock_status(int $qty): array {
    if ($qty <= 0)   return ['label' => 'Habis',        'class' => 'stock-out'];
    if ($qty <= 10)  return ['label' => 'Stok Menipis', 'class' => 'stock-low'];
    return ['label' => 'Tersedia', 'class' => 'stock-ok'];
}

/* ---------------- Price helpers ---------------- */
function effective_price(array $p): float {
    $promo = $p['promo_price'] ?? null;
    if ($promo !== null && (float)$promo > 0 && (float)$promo < (float)$p['price']) return (float)$promo;
    return (float)$p['price'];
}
function has_promo(array $p): bool {
    return $p['promo_price'] !== null && (float)$p['promo_price'] > 0 && (float)$p['promo_price'] < (float)$p['price'];
}

/* ---------------- Product image ---------------- */
function product_image_url(?string $img): string {
    if ($img && file_exists(PRODUCT_IMG_PATH . '/' . $img)) return url('uploads/products/' . $img);
    return asset('img/placeholder.svg');
}

/* ---------------- Auth: Customer ---------------- */
function current_customer(): ?array {
    if (empty($_SESSION['customer_id'])) return null;
    static $c = null;
    if ($c === null) {
        $st = db()->prepare('SELECT * FROM customers WHERE id = ?');
        $st->execute([$_SESSION['customer_id']]);
        $c = $st->fetch() ?: null;
    }
    return $c;
}
function require_customer(): array {
    $c = current_customer();
    if (!$c) { flash('warning', 'Silakan login terlebih dahulu.'); redirect('customer/login.php'); }
    return $c;
}

/* ---------------- Auth: Admin ---------------- */
function current_admin(): ?array {
    if (empty($_SESSION['admin_id'])) return null;
    static $a = null;
    if ($a === null) {
        $st = db()->prepare('SELECT * FROM admins WHERE id = ?');
        $st->execute([$_SESSION['admin_id']]);
        $a = $st->fetch() ?: null;
    }
    return $a;
}
function require_admin(): array {
    $a = current_admin();
    if (!$a) { redirect('admin/login.php'); }
    return $a;
}

/* ---------------- Cart (session based) ---------------- */
function cart(): array { return $_SESSION['cart'] ?? []; }
function cart_count(): int { return array_sum(array_map(fn($i) => $i['qty'], cart())); }
function cart_add(int $productId, int $qty = 1): void {
    $cart = cart();
    $cart[$productId] = ['qty' => ($cart[$productId]['qty'] ?? 0) + $qty];
    $_SESSION['cart'] = $cart;
}
function cart_set(int $productId, int $qty): void {
    $cart = cart();
    if ($qty <= 0) unset($cart[$productId]);
    else $cart[$productId] = ['qty' => $qty];
    $_SESSION['cart'] = $cart;
}
function cart_remove(int $productId): void { $c = cart(); unset($c[$productId]); $_SESSION['cart'] = $c; }
function cart_clear(): void { unset($_SESSION['cart']); }

/**
 * Returns cart with full product rows + computed totals.
 */
function cart_detail(): array {
    $cart = cart();
    if (!$cart) return ['items' => [], 'subtotal' => 0, 'count' => 0];
    $ids = array_map('intval', array_keys($cart));
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $st  = db()->prepare("SELECT * FROM products WHERE id IN ($in)");
    $st->execute($ids);
    $items = []; $subtotal = 0; $count = 0;
    foreach ($st->fetchAll() as $p) {
        $qty   = (int)$cart[$p['id']]['qty'];
        $price = effective_price($p);
        $line  = $price * $qty;
        $subtotal += $line; $count += $qty;
        $items[] = ['product' => $p, 'qty' => $qty, 'price' => $price, 'line' => $line];
    }
    return ['items' => $items, 'subtotal' => $subtotal, 'count' => $count];
}

/**
 * Smart Repeat Order analysis.
 * Compares an old order's items against current products (price + stock)
 * and returns per-item status so the customer can review before re-ordering.
 */
function reorder_analyze(int $orderId, int $customerId): ?array {
    $st = db()->prepare('SELECT * FROM orders WHERE id=? AND customer_id=?');
    $st->execute([$orderId, $customerId]);
    $order = $st->fetch();
    if (!$order) return null;

    $its = db()->prepare('SELECT * FROM order_items WHERE order_id=?');
    $its->execute([$orderId]);

    $rows = []; $anyAvailable = false; $hasWarning = false;
    foreach ($its->fetchAll() as $it) {
        $row = [
            'name'     => $it['product_name'],
            'sku'      => $it['sku'],
            'old_qty'  => (int)$it['qty'],
            'old_price'=> (float)$it['price'],
            'image'    => product_image_url(null),
        ];
        $p = null;
        if (!empty($it['product_id'])) {
            $q = db()->prepare('SELECT * FROM products WHERE id=?'); $q->execute([(int)$it['product_id']]); $p = $q->fetch();
        }
        if (!$p && !empty($it['sku'])) {
            $q = db()->prepare('SELECT * FROM products WHERE sku=?'); $q->execute([$it['sku']]); $p = $q->fetch();
        }

        if (!$p || !$p['is_active']) {
            $row['status'] = 'unavailable'; $row['available'] = false; $row['add_qty'] = 0;
            $hasWarning = true; $rows[] = $row; continue;
        }

        $row['product_id'] = (int)$p['id'];
        $row['image']      = product_image_url($p['image']);
        $row['new_price']  = effective_price($p);
        $stock             = (int)$p['stock_qty'];

        if ($stock <= 0) {
            $row['status'] = 'out_of_stock'; $row['available'] = false; $row['add_qty'] = 0;
            $hasWarning = true; $rows[] = $row; continue;
        }

        $addQty = min($row['old_qty'], $stock);
        $row['add_qty'] = $addQty; $row['stock'] = $stock; $row['available'] = true;
        $anyAvailable = true;
        $notes = [];
        if ($addQty < $row['old_qty']) { $notes[] = 'stock_limited'; $hasWarning = true; }
        if (abs($row['new_price'] - $row['old_price']) >= 0.01) { $notes[] = $row['new_price'] > $row['old_price'] ? 'price_up' : 'price_down'; $hasWarning = true; }
        $row['status'] = $notes ? $notes[0] : 'ok';
        $row['notes']  = $notes;
        $rows[] = $row;
    }

    return ['order' => $order, 'items' => $rows, 'any_available' => $anyAvailable, 'has_warning' => $hasWarning];
}


/* ---------------- Customer addresses (multi) ---------------- */
function customer_addresses(int $customerId): array {
    $st = db()->prepare('SELECT * FROM addresses WHERE customer_id=? ORDER BY is_default DESC, id ASC');
    $st->execute([$customerId]);
    return $st->fetchAll();
}
function default_address(int $customerId): ?array {
    $st = db()->prepare('SELECT * FROM addresses WHERE customer_id=? ORDER BY is_default DESC, id ASC LIMIT 1');
    $st->execute([$customerId]);
    $a = $st->fetch();
    return $a ?: null;
}
function set_default_address(int $customerId, int $addrId): void {
    db()->prepare('UPDATE addresses SET is_default=0 WHERE customer_id=?')->execute([$customerId]);
    db()->prepare('UPDATE addresses SET is_default=1 WHERE id=? AND customer_id=?')->execute([$addrId, $customerId]);
}
function address_labels(): array { return ['Rumah','Kantor','Toko','Keluarga','Lainnya']; }

/* ---------------- Order number ---------------- */
function generate_order_number(): string {
    $prefix = 'CHF-' . date('Ymd') . '-';
    $st = db()->prepare("SELECT order_number FROM orders WHERE order_number LIKE ? ORDER BY id DESC LIMIT 1");
    $st->execute([$prefix . '%']);
    $last = $st->fetchColumn();
    $seq = $last ? ((int)substr($last, -4)) + 1 : 1;
    return $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
}

/* ---------------- Status badge ---------------- */
function status_class(string $status): string {
    return match ($status) {
        'Menunggu Konfirmasi' => 'badge-wait',
        'Dikonfirmasi'        => 'badge-confirm',
        'Diproses'            => 'badge-process',
        'Dikirim'             => 'badge-ship',
        'Selesai'             => 'badge-done',
        'Dibatalkan'          => 'badge-cancel',
        default               => 'badge-secondary',
    };
}

function order_statuses(): array {
    return ['Menunggu Konfirmasi','Dikonfirmasi','Diproses','Dikirim','Selesai','Dibatalkan'];
}

/** Payment status -> Indonesian label + bootstrap badge class */
function payment_label(string $status): array {
    return match ($status) {
        'paid'    => ['label' => 'Lunas',                          'class' => 'bg-success'],
        'pending' => ['label' => 'Menunggu Konfirmasi Pembayaran', 'class' => 'bg-warning text-dark'],
        default   => ['label' => 'Belum Dibayar',                  'class' => 'bg-secondary'],
    };
}

/** Default WhatsApp message templates (used if setting is empty). */
function default_template(string $key): string {
    return match ($key) {
        'tpl_order_confirm' => "Halo {name},\n\nPesanan Anda di Chipi Frozen Food sudah kami konfirmasi.\n\nNomor Pesanan:\n{order_number}\n\nTotal:\n{total}\n\nTerima kasih telah berbelanja di Chipi Frozen Food.",
        'tpl_reg_approve'   => "Halo {name},\n\nKabar baik! Pendaftaran akun Anda di Chipi Frozen Food telah DISETUJUI.\n\nSilakan login dan mulai belanja frozen food favorit Anda. Terima kasih!",
        'tpl_reg_reject'    => "Halo {name},\n\nMohon maaf, pendaftaran akun Anda di Chipi Frozen Food belum dapat kami setujui saat ini.\n\nSilakan hubungi kami untuk informasi lebih lanjut. Terima kasih.",
        default             => '',
    };
}

/** Fill a message template with {placeholder} values. */
function render_template(string $key, array $vars): string {
    $tpl = trim(setting($key, ''));
    if ($tpl === '') $tpl = default_template($key);
    foreach ($vars as $k => $v) { $tpl = str_replace('{'.$k.'}', (string)$v, $tpl); }
    return $tpl;
}

/** Admin notification items (needs attention). Returns ['items'=>[], 'total'=>int]. */
function admin_notifications(): array {
    $pdo = db();
    $newOrders   = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status='Menunggu Konfirmasi'")->fetchColumn();
    $payProof    = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status='pending'")->fetchColumn();
    $newCust     = (int)$pdo->query("SELECT COUNT(*) FROM customers WHERE status='pending'")->fetchColumn();
    $lowStock    = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock_qty<=10 AND is_active=1")->fetchColumn();

    $items = [];
    if ($newCust)   $items[] = ['icon'=>'fa-user-plus','color'=>'#6a3fd6','text'=>$newCust.' pendaftaran menunggu persetujuan','url'=>url('admin/customers.php?status=pending'),'count'=>$newCust];
    if ($newOrders) $items[] = ['icon'=>'fa-cart-shopping','color'=>'#ff7a29','text'=>$newOrders.' pesanan menunggu konfirmasi','url'=>url('admin/orders.php?status='.rawurlencode('Menunggu Konfirmasi')),'count'=>$newOrders];
    if ($payProof)  $items[] = ['icon'=>'fa-money-check-dollar','color'=>'#1b8a4b','text'=>$payProof.' pembayaran menunggu verifikasi','url'=>url('admin/orders.php'),'count'=>$payProof];
    if ($lowStock)  $items[] = ['icon'=>'fa-triangle-exclamation','color'=>'#c0392b','text'=>$lowStock.' produk stok menipis','url'=>url('admin/products.php?low=1'),'count'=>$lowStock];

    return ['items'=>$items, 'total'=>$newCust + $newOrders + $payProof + $lowStock];
}
