<?php
require_once __DIR__ . '/../includes/functions.php';
$cust = require_customer();
$page = 'account'; $accPage = 'address.php'; $pageTitle = 'Alamat';
$redirectTo = ($_GET['redirect'] ?? '') === 'checkout' ? 'customer/address.php?redirect=checkout' : 'customer/address.php';
$backToCheckout = ($_GET['redirect'] ?? '') === 'checkout';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $do = $_POST['do'] ?? 'save';

    if ($do === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $row = db()->prepare('SELECT * FROM addresses WHERE id=? AND customer_id=?');
        $row->execute([$id, $cust['id']]);
        $target = $row->fetch();
        if ($target) {
            db()->prepare('DELETE FROM addresses WHERE id=? AND customer_id=?')->execute([$id, $cust['id']]);
            // promote a new default if we deleted the default one
            if ($target['is_default']) {
                $next = default_address($cust['id']);
                if ($next) db()->prepare('UPDATE addresses SET is_default=1 WHERE id=?')->execute([$next['id']]);
            }
            flash('success', 'Alamat dihapus.');
        }
        redirect($redirectTo);
    }

    if ($do === 'set_default') {
        set_default_address($cust['id'], (int)($_POST['id'] ?? 0));
        flash('success', 'Alamat utama diperbarui.');
        redirect($redirectTo);
    }

    // save (add or edit)
    $id        = (int)($_POST['id'] ?? 0);
    $label     = in_array($_POST['label'] ?? '', address_labels()) ? $_POST['label'] : 'Rumah';
    $recipient = trim($_POST['recipient_name'] ?? '');
    $wa        = trim($_POST['whatsapp'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $city      = trim($_POST['city'] ?? '');
    $postal    = trim($_POST['postal_code'] ?? '');
    $notes     = trim($_POST['notes'] ?? '');
    $makeDef   = isset($_POST['make_default']);

    if ($recipient && $wa && $address && $city) {
        $count = (int)db()->query('SELECT COUNT(*) FROM addresses WHERE customer_id=' . (int)$cust['id'])->fetchColumn();
        if ($id > 0) {
            $own = db()->prepare('SELECT id FROM addresses WHERE id=? AND customer_id=?');
            $own->execute([$id, $cust['id']]);
            if ($own->fetch()) {
                db()->prepare('UPDATE addresses SET label=?,recipient_name=?,whatsapp=?,address=?,city=?,postal_code=?,notes=? WHERE id=? AND customer_id=?')
                    ->execute([$label, $recipient, $wa, $address, $city, $postal, $notes, $id, $cust['id']]);
                if ($makeDef) set_default_address($cust['id'], $id);
                flash('success', 'Alamat diperbarui.');
            }
        } else {
            $isDef = ($count === 0 || $makeDef) ? 1 : 0;
            if ($isDef) db()->prepare('UPDATE addresses SET is_default=0 WHERE customer_id=?')->execute([$cust['id']]);
            db()->prepare('INSERT INTO addresses (customer_id,label,recipient_name,whatsapp,address,city,postal_code,notes,is_default) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute([$cust['id'], $label, $recipient, $wa, $address, $city, $postal, $notes, $isDef]);
            flash('success', 'Alamat baru ditambahkan.');
        }
        redirect($redirectTo);
    } else {
        flash('error', 'Lengkapi label, nama penerima, WhatsApp, alamat, dan kota.');
    }
}

$addresses = customer_addresses($cust['id']);
$editId = (int)($_GET['edit'] ?? 0);
$editing = null;
if ($editId) { foreach ($addresses as $a) { if ((int)$a['id'] === $editId) { $editing = $a; break; } } }
$showForm = $editing || isset($_GET['add']) || !$addresses;

require __DIR__ . '/../includes/header.php';
?>
<div class="container my-4">
  <div class="row g-3">
    <div class="col-lg-3"><?php require __DIR__ . '/../includes/account_nav.php'; ?></div>
    <div class="col-lg-9">
      <div class="card p-3 p-md-4 mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div>
            <h5 class="brand-font mb-0">Alamat Saya</h5>
            <p class="text-muted-chipi small mb-0">Simpan beberapa alamat (rumah, kantor, toko, keluarga) dan pilih satu sebagai utama.</p>
          </div>
          <a href="<?= url('customer/address.php' . ($backToCheckout ? '?redirect=checkout&add=1' : '?add=1')) ?>#addr-form" class="btn btn-chipi btn-sm" data-testid="addr-add-new"><i class="fa-solid fa-plus me-1"></i>Tambah Alamat</a>
        </div>

        <?php if ($addresses): ?>
          <div class="row g-3 mt-1">
            <?php foreach ($addresses as $a): ?>
              <div class="col-md-6">
                <div class="border rounded-3 p-3 h-100 <?= $a['is_default'] ? 'border-2' : '' ?>" style="<?= $a['is_default'] ? 'border-color:var(--chipi-blue,#38b6ff)' : '' ?>" data-testid="addr-card-<?= (int)$a['id'] ?>">
                  <div class="d-flex justify-content-between align-items-start mb-1">
                    <span class="badge" style="background:#0e2a49"><i class="fa-solid fa-tag me-1"></i><?= e($a['label']) ?></span>
                    <?php if ($a['is_default']): ?><span class="badge bg-success" data-testid="addr-default-badge-<?= (int)$a['id'] ?>"><i class="fa-solid fa-star me-1"></i>Utama</span><?php endif; ?>
                  </div>
                  <div class="fw-bold small"><?= e($a['recipient_name']) ?> · <?= e($a['whatsapp']) ?></div>
                  <div class="text-muted-chipi small"><?= e($a['address']) ?>, <?= e($a['city']) ?> <?= e($a['postal_code']) ?></div>
                  <?php if ($a['notes']): ?><div class="text-muted-chipi small">Catatan: <?= e($a['notes']) ?></div><?php endif; ?>
                  <div class="d-flex gap-2 mt-2 flex-wrap">
                    <?php if (!$a['is_default']): ?>
                      <form method="post" class="d-inline">
                        <?= csrf_field() ?><input type="hidden" name="do" value="set_default"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                        <button class="btn btn-outline-chipi btn-sm" data-testid="addr-setdefault-<?= (int)$a['id'] ?>"><i class="fa-regular fa-star me-1"></i>Jadikan Utama</button>
                      </form>
                    <?php endif; ?>
                    <a href="<?= url('customer/address.php' . ($backToCheckout ? '?redirect=checkout&' : '?') . 'edit=' . (int)$a['id']) ?>#addr-form" class="btn btn-outline-chipi btn-sm" data-testid="addr-edit-<?= (int)$a['id'] ?>"><i class="fa-solid fa-pen me-1"></i>Ubah</a>
                    <?php if (count($addresses) > 1): ?>
                      <form method="post" class="d-inline" onsubmit="return confirm('Hapus alamat ini?');">
                        <?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                        <button class="btn btn-outline-danger btn-sm" data-testid="addr-delete-<?= (int)$a['id'] ?>"><i class="fa-solid fa-trash"></i></button>
                      </form>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="alert alert-warning mt-3 mb-0">Belum ada alamat. Tambahkan alamat pertama Anda di bawah.</div>
        <?php endif; ?>
      </div>

      <div class="card p-3 p-md-4 <?= $showForm ? '' : 'd-none' ?>" id="addr-form" data-testid="addr-form">
        <h6 class="brand-font"><?= $editing ? 'Ubah Alamat' : 'Tambah Alamat Baru' ?></h6>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="do" value="save">
          <input type="hidden" name="id" value="<?= $editing ? (int)$editing['id'] : 0 ?>">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">Label Alamat</label>
              <select class="form-select" name="label" data-testid="addr-label">
                <?php foreach (address_labels() as $lb): ?>
                  <option value="<?= e($lb) ?>" <?= ($editing['label'] ?? 'Rumah') === $lb ? 'selected' : '' ?>><?= e($lb) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label small fw-bold">Nama Penerima</label><input class="form-control" name="recipient_name" value="<?= e($editing['recipient_name'] ?? $cust['name']) ?>" required data-testid="addr-recipient"></div>
            <div class="col-md-6"><label class="form-label small fw-bold">No. WhatsApp</label><input class="form-control" name="whatsapp" value="<?= e($editing['whatsapp'] ?? $cust['whatsapp']) ?>" required data-testid="addr-wa"></div>
            <div class="col-md-6"><label class="form-label small fw-bold">Kota / Area</label><input class="form-control" name="city" value="<?= e($editing['city'] ?? '') ?>" required data-testid="addr-city"></div>
            <div class="col-12"><label class="form-label small fw-bold">Alamat Lengkap</label><textarea class="form-control" name="address" rows="2" required data-testid="addr-address"><?= e($editing['address'] ?? '') ?></textarea></div>
            <div class="col-md-6"><label class="form-label small fw-bold">Kode Pos <span class="text-muted-chipi">(opsional)</span></label><input class="form-control" name="postal_code" value="<?= e($editing['postal_code'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label small fw-bold">Catatan / Patokan <span class="text-muted-chipi">(opsional)</span></label><input class="form-control" name="notes" value="<?= e($editing['notes'] ?? '') ?>" placeholder="Rumah pagar hijau, sebelah warung"></div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="make_default" id="make_default" value="1" <?= (!empty($editing['is_default']) || !$addresses) ? 'checked' : '' ?> <?= !empty($editing['is_default']) ? 'disabled' : '' ?> data-testid="addr-make-default">
                <label class="form-check-label small" for="make_default">Jadikan alamat utama</label>
              </div>
            </div>
          </div>
          <div class="d-flex gap-2 mt-3">
            <button class="btn btn-chipi" data-testid="addr-save"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan Alamat</button>
            <a href="<?= url($backToCheckout ? 'customer/checkout.php' : 'customer/address.php') ?>" class="btn btn-outline-chipi" data-testid="addr-cancel">Batal</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php if ($showForm): ?>
<script>document.getElementById('addr-form')?.scrollIntoView({behavior:'smooth'});</script>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
