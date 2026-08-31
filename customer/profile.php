<?php
require_once __DIR__ . '/../includes/functions.php';
$cust = require_customer();
$page = 'account'; $accPage = 'profile.php'; $pageTitle = 'Profil';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $wa   = trim($_POST['whatsapp'] ?? '');
    $email= trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    if ($name && $wa) {
        if ($pass !== '') {
            db()->prepare('UPDATE customers SET name=?,whatsapp=?,email=?,password=? WHERE id=?')
               ->execute([$name,$wa,$email ?: null, password_hash($pass, PASSWORD_BCRYPT), $cust['id']]);
        } else {
            db()->prepare('UPDATE customers SET name=?,whatsapp=?,email=? WHERE id=?')
               ->execute([$name,$wa,$email ?: null, $cust['id']]);
        }
        flash('success','Profil berhasil diperbarui.');
        redirect('customer/profile.php');
    } else { $msg = 'Nama & WhatsApp wajib diisi.'; }
}
require __DIR__ . '/../includes/header.php';
?>
<div class="container my-4">
  <div class="row g-3">
    <div class="col-lg-3"><?php require __DIR__ . '/../includes/account_nav.php'; ?></div>
    <div class="col-lg-9">
      <div class="card p-3 p-md-4">
        <h5 class="brand-font">Profil Saya</h5>
        <?php if ($msg): ?><div class="alert alert-danger py-2"><?= e($msg) ?></div><?php endif; ?>
        <form method="post">
          <?= csrf_field() ?>
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label small fw-bold">Nama</label><input class="form-control" name="name" value="<?= e($cust['name']) ?>" required data-testid="profile-name"></div>
            <div class="col-md-6"><label class="form-label small fw-bold">No. WhatsApp</label><input class="form-control" name="whatsapp" value="<?= e($cust['whatsapp']) ?>" required data-testid="profile-wa"></div>
            <div class="col-md-6"><label class="form-label small fw-bold">Email</label><input class="form-control" type="email" name="email" value="<?= e($cust['email']) ?>" data-testid="profile-email"></div>
            <div class="col-md-6"><label class="form-label small fw-bold">Password Baru <span class="text-muted-chipi">(kosongkan bila tidak diubah)</span></label><input class="form-control" type="password" name="password" minlength="6"></div>
          </div>
          <button class="btn btn-chipi mt-3" data-testid="profile-save">Simpan Perubahan</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
