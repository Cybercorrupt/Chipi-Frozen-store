<?php
require_once __DIR__ . '/../includes/functions.php';
if (current_admin()) redirect('admin/index.php');
$pageTitle = 'Login Admin';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $st = db()->prepare('SELECT * FROM admins WHERE email=?');
    $st->execute([$email]);
    $a = $st->fetch();
    if ($a && password_verify($pass, $a['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $a['id'];
        redirect('admin/index.php');
    } else { $err = 'Email atau password admin salah.'; }
}
?>
<!doctype html><html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login Admin · Chipi</title>
<link rel="icon" href="<?= asset('img/logo.png') ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head><body class="auth-wrap" style="background:linear-gradient(135deg,#0f2c4d,#1e88d6)">
<div class="auth-card">
  <img src="<?= asset('img/logo.png') ?>" class="auth-logo logo-glow">
  <h4 class="brand-font text-center mb-1">Admin Panel</h4>
  <p class="text-center text-muted-chipi small mb-3">Chipi Frozen Food</p>
  <?php if ($err): ?><div class="alert alert-danger py-2" data-testid="admin-login-error"><?= e($err) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label class="form-label small fw-bold">Email</label>
    <input class="form-control mb-3" type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" data-testid="admin-login-email">
    <label class="form-label small fw-bold">Password</label>
    <input class="form-control mb-3" type="password" name="password" required data-testid="admin-login-password">
    <button class="btn btn-chipi-blue w-100" data-testid="admin-login-submit">Masuk</button>
  </form>
</div>
</body></html>
