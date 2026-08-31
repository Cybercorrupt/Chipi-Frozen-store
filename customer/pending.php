<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Menunggu Konfirmasi';
$waAdmin = preg_replace('/[^0-9]/', '', setting('whatsapp_admin'));
$waMsg = rawurlencode("Halo Admin Chipi Frozen Food, saya baru saja mendaftar akun dan ingin menanyakan status konfirmasi pendaftaran saya. Terima kasih.");
$waLink = 'https://wa.me/' . $waAdmin . '?text=' . $waMsg;
?>
<!doctype html><html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Menunggu Konfirmasi · Chipi Frozen Food</title>
<link rel="icon" href="<?= asset('img/logo.png') ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head><body class="auth-wrap">
<div class="auth-card text-center">
  <img src="<?= asset('img/logo.png') ?>" class="auth-logo logo-glow">
  <?php foreach (get_flashes() as $f): ?>
    <div class="alert alert-<?= $f['type']==='error'?'danger':e($f['type']) ?> py-2" data-testid="flash-message"><?= e($f['msg']) ?></div>
  <?php endforeach; ?>
  <div class="mx-auto mb-3" style="width:74px;height:74px;border-radius:50%;background:#fff3e0;display:flex;align-items:center;justify-content:center;color:#c9660b;font-size:2rem"><i class="fa-solid fa-hourglass-half"></i></div>
  <h4 class="brand-font mb-2">Menunggu Konfirmasi Admin</h4>
  <p class="text-muted-chipi small mb-4">Terima kasih telah mendaftar di <b>Chipi Frozen Food</b>. Akun Anda sedang ditinjau oleh admin. Anda akan dapat masuk setelah pendaftaran disetujui.</p>
  <a href="<?= e($waLink) ?>" target="_blank" class="btn btn-success w-100 mb-2" data-testid="pending-wa-btn"><i class="fa-brands fa-whatsapp me-1"></i>Hubungi Admin via WhatsApp</a>
  <a href="<?= url('customer/login.php') ?>" class="btn btn-outline-chipi w-100 mb-2">Coba Masuk</a>
  <a href="<?= url('index.php') ?>" class="text-muted-chipi small">← Kembali ke beranda</a>
</div>
</body></html>
