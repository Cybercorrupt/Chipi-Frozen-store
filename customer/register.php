<?php
require_once __DIR__ . '/../includes/functions.php';
if (current_customer()) redirect('customer/dashboard.php');
$pageTitle = 'Daftar';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $wa   = trim($_POST['whatsapp'] ?? '');
    $email= trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    if ($name === '' || $wa === '' || strlen($pass) < 6) {
        $err = 'Nama, WhatsApp wajib diisi & password minimal 6 karakter.';
    } else {
        $chk = db()->prepare('SELECT id FROM customers WHERE whatsapp=? OR (email<>"" AND email=?)');
        $chk->execute([$wa, $email]);
        if ($chk->fetch()) { $err = 'No. WhatsApp atau email sudah terdaftar.'; }
        else {
            $ins = db()->prepare('INSERT INTO customers (name,whatsapp,email,password,status) VALUES (?,?,?,?,\'pending\')');
            $ins->execute([$name, $wa, $email ?: null, password_hash($pass, PASSWORD_BCRYPT)]);
            flash('success', 'Pendaftaran berhasil! Akun Anda menunggu konfirmasi admin.');
            redirect('customer/pending.php');
        }
    }
}
?>
<!doctype html><html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Daftar · Chipi Frozen Food</title>
<link rel="icon" href="<?= asset('img/logo.png') ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head><body class="auth-wrap">
<div class="auth-card">
  <a href="<?= url('index.php') ?>"><img src="<?= asset('img/logo.png') ?>" class="auth-logo logo-glow"></a>
  <h4 class="brand-font text-center mb-3">Daftar Akun Baru</h4>
  <?php if ($err): ?><div class="alert alert-danger py-2" data-testid="register-error"><?= e($err) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label class="form-label small fw-bold">Nama Lengkap</label>
    <input class="form-control mb-2" name="name" required value="<?= e($_POST['name'] ?? '') ?>" data-testid="reg-name">
    <label class="form-label small fw-bold">No. WhatsApp</label>
    <input class="form-control mb-2" name="whatsapp" required value="<?= e($_POST['whatsapp'] ?? '') ?>" placeholder="0812xxxx" data-testid="reg-whatsapp">
    <label class="form-label small fw-bold">Email <span class="text-muted-chipi">(opsional)</span></label>
    <input class="form-control mb-2" type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" data-testid="reg-email">
    <label class="form-label small fw-bold">Password</label>
    <input class="form-control mb-3" type="password" name="password" required minlength="6" data-testid="reg-password">
    <button class="btn btn-chipi w-100 mb-2" data-testid="register-submit">Daftar</button>
  </form>
  <p class="text-center small mb-0">Sudah punya akun? <a href="<?= url('customer/login.php') ?>" class="fw-bold" style="color:var(--chipi-orange-dark)">Masuk</a></p>
</div>
</body></html>
