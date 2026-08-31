<?php
/** Customer account side navigation. Expects $accPage. */
$items = [
  ['dashboard.php','Dashboard','fa-gauge'],
  ['profile.php','Profil','fa-user'],
  ['address.php','Alamat','fa-location-dot'],
  ['orders.php','Pesanan Saya','fa-receipt'],
  ['receipts.php','Nota Saya','fa-file-image'],
];
?>
<div class="card p-2 mb-3">
  <div class="list-group list-group-flush">
    <?php foreach ($items as $it): ?>
      <a href="<?= url('customer/'.$it[0]) ?>" class="list-group-item list-group-item-action border-0 rounded-3 <?= ($accPage??'')===$it[0]?'active':'' ?>" style="<?= ($accPage??'')===$it[0]?'background:var(--chipi-blue);color:#fff':'' ?>">
        <i class="fa-solid <?= $it[2] ?> me-2"></i><?= $it[1] ?>
      </a>
    <?php endforeach; ?>
    <a href="<?= url('customer/logout.php') ?>" class="list-group-item list-group-item-action border-0 rounded-3 text-danger" data-testid="logout-link"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a>
  </div>
</div>
