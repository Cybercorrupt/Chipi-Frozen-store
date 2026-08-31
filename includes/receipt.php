<?php
require_once __DIR__ . '/functions.php';

/**
 * Generate a PNG order receipt using PHP GD.
 * Returns the saved filename (e.g. CHF-20260811-0001.png) or null on failure.
 */
function generate_receipt(int $orderId): ?string
{
    $ost = db()->prepare('SELECT * FROM orders WHERE id=?');
    $ost->execute([$orderId]);
    $o = $ost->fetch();
    if (!$o) return null;

    $ist = db()->prepare('SELECT * FROM order_items WHERE order_id=?');
    $ist->execute([$orderId]);
    $items = $ist->fetchAll();

    if (!function_exists('imagecreatetruecolor')) return null;

    $fontR = __DIR__ . '/../assets/fonts/DejaVuSans.ttf';
    $fontB = __DIR__ . '/../assets/fonts/DejaVuSans-Bold.ttf';
    $ttf   = is_file($fontR) && is_file($fontB);

    $W = 720;
    $pad = 40;
    // dynamic height
    $H = 470 + (count($items) * 46) + 320;

    $im = imagecreatetruecolor($W, $H);

    // colors
    $white  = imagecolorallocate($im, 255, 255, 255);
    $blue   = imagecolorallocate($im, 56, 182, 255);
    $navy   = imagecolorallocate($im, 15, 44, 77);
    $orange = imagecolorallocate($im, 255, 122, 41);
    $ink    = imagecolorallocate($im, 25, 40, 55);
    $muted  = imagecolorallocate($im, 120, 135, 150);
    $line   = imagecolorallocate($im, 225, 234, 243);
    $lightb = imagecolorallocate($im, 235, 246, 255);

    imagefilledrectangle($im, 0, 0, $W, $H, $white);
    // top blue band
    imagefilledrectangle($im, 0, 0, $W, 120, $blue);

    // helper
    $text = function ($size, $x, $y, $color, $str, $bold = false) use ($im, $fontR, $fontB, $ttf) {
        if ($ttf) {
            imagettftext($im, $size, 0, $x, $y, $color, $bold ? $fontB : $fontR, $str);
        } else {
            imagestring($im, 4, $x, $y - 12, $str, $color);
        }
    };
    $rtext = function ($size, $rx, $y, $color, $str, $bold = false) use ($im, $fontR, $fontB, $ttf) {
        if ($ttf) {
            $bb = imagettfbbox($size, 0, $bold ? $fontB : $fontR, $str);
            $w = $bb[2] - $bb[0];
            imagettftext($im, $size, 0, $rx - $w, $y, $color, $bold ? $fontB : $fontR, $str);
        } else {
            imagestring($im, 4, $rx - strlen($str) * 8, $y - 12, $str, $color);
        }
    };

    // logo
    $logoFile = __DIR__ . '/../assets/img/logo.png';
    if (is_file($logoFile)) {
        $logo = @imagecreatefrompng($logoFile);
        if ($logo) {
            $lw = imagesx($logo); $lh = imagesy($logo);
            $target = 92; $nw = (int)($lw * $target / $lh);
            imagecopyresampled($im, $logo, $pad, 14, 0, 0, $nw, $target, $lw, $lh);
            imagedestroy($logo);
        }
    }
    $text(17, $pad + 118, 50, $white, 'Chipi Frozen Food', true);
    $text(10, $pad + 118, 74, $white, 'Frozen Food Favorit, Tinggal Masak!');
    $rtext(15, $W - $pad, 100, $white, 'NOTA PEMESANAN', true);

    $y = 165;
    // order meta
    $text(13, $pad, $y, $ink, 'No. Pesanan', true);
    $rtext(13, $W - $pad, $y, $orange, $o['order_number'], true); $y += 26;
    $text(11, $pad, $y, $muted, 'Tanggal');
    $rtext(11, $W - $pad, $y, $ink, date('d M Y H:i', strtotime($o['created_at']))); $y += 24;
    imageline($im, $pad, $y, $W - $pad, $y, $line); $y += 30;

    // customer
    $text(12, $pad, $y, $navy, 'PELANGGAN', true); $y += 22;
    $text(11, $pad, $y, $ink, $o['customer_name'] . '  |  ' . $o['customer_phone']); $y += 20;
    // wrap address
    $addr = $o['customer_address'];
    foreach (str_split_words($addr, 70) as $lnrow) { $text(10, $pad, $y, $muted, $lnrow); $y += 17; }
    $y += 14;

    // items header
    imagefilledrectangle($im, $pad, $y - 18, $W - $pad, $y + 8, $lightb);
    $text(11, $pad + 8, $y, $navy, 'PRODUK', true);
    $text(11, $W - 285, $y, $navy, 'QTY', true);
    $text(11, $W - 235, $y, $navy, 'HARGA', true);
    $rtext(11, $W - $pad - 8, $y, $navy, 'TOTAL', true);
    $y += 30;

    foreach ($items as $it) {
        $name = mb_strlen($it['product_name']) > 30 ? mb_substr($it['product_name'], 0, 29) . '…' : $it['product_name'];
        $text(11, $pad + 8, $y, $ink, $name);
        $text(9, $pad + 8, $y + 16, $muted, $it['sku']);
        $text(11, $W - 283, $y, $ink, (string)$it['qty']);
        $text(10, $W - 235, $y, $ink, 'Rp' . number_format($it['price'], 0, ',', '.'));
        $rtext(11, $W - $pad - 8, $y, $ink, 'Rp' . number_format($it['subtotal'], 0, ',', '.'), true);
        $y += 46;
        imageline($im, $pad, $y - 20, $W - $pad, $y - 20, $line);
    }

    $y += 6;
    $sx = $W - 320;
    $rowsum = function ($label, $val, $bold = false, $color = null) use (&$y, $text, $rtext, $sx, $W, $pad, $ink, $muted) {
        $text(11, $sx, $y, $bold ? $ink : $muted, $label, $bold);
        $rtext(11, $W - $pad, $y, $color ?? $ink, $val, $bold);
        $y += 24;
    };
    $rowsum('Subtotal', 'Rp' . number_format($o['subtotal'], 0, ',', '.'));
    $rowsum('Diskon', '- Rp' . number_format($o['discount'], 0, ',', '.'));
    $rowsum('Ongkir', 'Rp' . number_format($o['shipping_cost'], 0, ',', '.'));
    imageline($im, $sx, $y - 6, $W - $pad, $y - 6, $line); $y += 10;

    // grand total box
    imagefilledrectangle($im, $sx - 10, $y - 20, $W - $pad + 10, $y + 14, $orange);
    $text(13, $sx, $y, $white, 'GRAND TOTAL', true);
    $rtext(13, $W - $pad, $y, $white, 'Rp' . number_format($o['grand_total'], 0, ',', '.'), true);
    $y += 46;

    $text(11, $pad, $y, $muted, 'Pembayaran:');
    $text(11, $pad + 110, $y, $ink, $o['payment_method'] . ' (' . $o['delivery_method'] . ')', true);
    $y += 40;

    imageline($im, $pad, $y, $W - $pad, $y, $line); $y += 30;
    $text(11, $pad, $y, $ink, 'Terima kasih telah berbelanja di Chipi Frozen Food.', true); $y += 20;
    $text(10, $pad, $y, $muted, 'Simpan nota ini sebagai bukti pesanan.');

    // ---- Diagonal watermark by payment status ----
    if ($ttf) {
        $ps  = payment_status($o);
        $wm  = imagecolorallocatealpha($im, $ps['rgb'][0], $ps['rgb'][1], $ps['rgb'][2], 102);
        $wsz = 62; $wan = 28;
        $wbb = imagettfbbox($wsz, $wan, $fontB, $ps['label']);
        $wtw = max($wbb[2], $wbb[4]) - min($wbb[0], $wbb[6]);
        $wth = max($wbb[1], $wbb[3]) - min($wbb[5], $wbb[7]);
        $wx  = (int)(($W - $wtw) / 2);
        $wy  = (int)(($H + $wth) / 2) + 20;
        imagettftext($im, $wsz, $wan, $wx, $wy, $wm, $fontB, $ps['label']);
    }

    if (!is_dir(RECEIPT_PATH)) @mkdir(RECEIPT_PATH, 0775, true);
    $filename = $o['order_number'] . '.png';
    imagepng($im, RECEIPT_PATH . '/' . $filename);
    imagedestroy($im);

    db()->prepare('UPDATE orders SET receipt_image=? WHERE id=?')->execute([$filename, $orderId]);
    return $filename;
}

/**
 * Map order -> payment/watermark state (automatic + admin confirmed).
 *  Dibatalkan            -> CANCELED (red)
 *  payment_status=paid   -> PAID (green)
 *  otherwise             -> NOT PAID (orange)
 */
function payment_status(array $o): array
{
    if (($o['order_status'] ?? '') === 'Dibatalkan') return ['label' => 'CANCELED', 'rgb' => [192, 57, 43]];
    if (($o['payment_status'] ?? 'unpaid') === 'paid') return ['label' => 'PAID', 'rgb' => [27, 138, 75]];
    return ['label' => 'NOT PAID', 'rgb' => [217, 119, 6]];
}

/** naive word wrap into rows of max chars */
function str_split_words(string $str, int $max): array
{
    $words = explode(' ', $str);
    $rows = []; $cur = '';
    foreach ($words as $w) {
        if (mb_strlen($cur . ' ' . $w) > $max) { $rows[] = trim($cur); $cur = $w; }
        else { $cur .= ' ' . $w; }
    }
    if (trim($cur) !== '') $rows[] = trim($cur);
    return $rows ?: [''];
}
