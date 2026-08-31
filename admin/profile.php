<?php
require_once __DIR__ . '/../includes/functions.php';
$admin = require_admin();
$pageTitle = 'Profil Admin';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cur   = $_POST['current_password'] ?? '';
    $new   = $_POST['new_password'] ?? '';

    if ($name === '' || $email === '') {
        $err = 'Nama dan email wajib diisi.';
    } else {
        // email unique among admins
        $chk = db()->prepare('SELECT id FROM admins WHERE email=? AND id<>?');
        $chk->execute([$email, $admin['id']]);
        if ($chk->fetch()) {
            $err = 'Email sudah digunakan admin lain.';
        } elseif ($new !== '') {
            if (!password_verify($cur, $admin['password'])) {
                $err = 'Password saat ini salah.';
            } elseif (strlen($new) < 6) {
                $err = 'Password baru minimal 6 karakter.';
            } else {
                db()->prepare('UPDATE admins SET name=?,email=?,password=? WHERE id=?')
                   ->execute([$name, $email, password_hash($new, PASSWORD_BCRYPT), $admin['id']]);
                flash('success', 'Profil & password berhasil diperbarui.');
                redirect('admin/profile.php');
            }
        } else {
            db()->prepare('UPDATE admins SET name=?,email=? WHERE id=?')->execute([$name, $email, $admin['id']]);
            flash('success', 'Profil berhasil diperbarui.');
            redirect('admin/profile.php');
        }
    }
    $admin = array_merge($admin, ['name' => $name, 'email' => $email]);
}
require __DIR__ . '/includes/admin_header.php';
?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card p-4 text-center">
      <div class="mx-auto mb-2" style="width:96px;height:96px;border-radius:50%;background:linear-gradient(135deg,var(--chipi-blue),var(--chipi-navy));display:flex;align-items:center;justify-content:center;color:#fff;font-size:2.2rem"><i class="fa-solid fa-user-shield"></i></div>
      <h6 class="brand-font mb-0" data-testid="admin-profile-name"><?= e($admin['name']) ?></h6>
      <div class="small text-muted-chipi"><?= e($admin['email']) ?></div>
      <span class="badge bg-primary mt-2">Administrator</span>
      <div class="small text-muted-chipi mt-3">Bergabung: <?= !empty($admin['created_at']) ? date('d M Y', strtotime($admin['created_at'])) : '-' ?></div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card p-3 p-md-4">
      <h5 class="brand-font">Edit Profil</h5>
      <?php if ($err): ?><div class="alert alert-danger py-2" data-testid="admin-profile-error"><?= e($err) ?></div><?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label small fw-bold">Nama</label><input class="form-control" name="name" value="<?= e($admin['name']) ?>" required data-testid="admin-profile-name-input"></div>
          <div class="col-md-6"><label class="form-label small fw-bold">Email</label><input class="form-control" type="email" name="email" value="<?= e($admin['email']) ?>" required data-testid="admin-profile-email-input"></div>
        </div>
        <hr class="my-4">
        <h6 class="brand-font">Ubah Password <span class="text-muted-chipi small fw-normal">(opsional)</span></h6>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label small fw-bold">Password Saat Ini</label><input class="form-control" type="password" name="current_password" data-testid="admin-current-pass"></div>
          <div class="col-md-6"><label class="form-label small fw-bold">Password Baru</label><input class="form-control" type="password" name="new_password" minlength="6" data-testid="admin-new-pass"></div>
        </div>
        <button class="btn btn-chipi mt-3" data-testid="admin-profile-save"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan</button>
      </form>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
