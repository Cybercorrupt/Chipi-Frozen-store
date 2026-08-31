<?php
require_once __DIR__ . '/../includes/functions.php';
if (current_customer()) redirect('customer/dashboard.php');
$pageTitle = 'Masuk';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $login = trim($_POST['login'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $st = db()->prepare('SELECT * FROM customers WHERE email=? OR whatsapp=? LIMIT 1');
    $st->execute([$login, $login]);
    $c = $st->fetch();
    if ($c && password_verify($pass, $c['password'])) {
        if (($c['status'] ?? 'active') === 'rejected') {
            $err = 'Maaf, pendaftaran akun Anda ditolak. Silakan hubungi admin.';
        } elseif (($c['status'] ?? 'active') === 'pending') {
            redirect('customer/pending.php');
        } elseif (!$c['is_active']) {
            $err = 'Akun Anda dinonaktifkan. Hubungi admin.';
        } else {
            session_regenerate_id(true);
            $_SESSION['customer_id'] = $c['id'];
            flash('success', 'Selamat datang, ' . $c['name'] . '!');
            redirect('customer/dashboard.php');
        }
    } else { $err = 'Email/No. WhatsApp atau password salah.'; }
}
?>
<!doctype html><html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Masuk · Chipi Frozen Food</title>
<link rel="icon" href="<?= asset('img/logo.png') ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head><body class="auth-wrap">
<div class="auth-card">
  <a href="<?= url('index.php') ?>"><img src="<?= asset('img/logo.png') ?>" class="auth-logo logo-glow"></a>
  <h4 class="brand-font text-center mb-1">Masuk ke Akun</h4>
  <p class="text-center text-muted-chipi small mb-3">Belanja frozen food favoritmu</p>
  <?php if ($err): ?><div class="alert alert-danger py-2" data-testid="login-error"><?= e($err) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label class="form-label small fw-bold">Email / No. WhatsApp</label>
    <input class="form-control mb-3" name="login" required value="<?= e($_POST['login'] ?? '') ?>" data-testid="login-username">
    <label class="form-label small fw-bold">Password</label>
    <input class="form-control mb-3" type="password" name="password" required data-testid="login-password">
    <button class="btn btn-chipi w-100 mb-2" data-testid="login-submit">Masuk</button>
  </form>
  <p class="text-center small mb-0">Belum punya akun? <a href="<?= url('customer/register.php') ?>" class="fw-bold" style="color:var(--chipi-orange-dark)">Daftar</a></p>
  <p class="text-center small mt-2 mb-0"><a href="<?= url('index.php') ?>" class="text-muted-chipi">← Kembali ke beranda</a></p>
</div>
</body></html>
