<?php
require_once __DIR__ . '/../../includes/functions.php';
$admin = require_admin();
$__self = basename($_SERVER['SCRIPT_NAME']);
$nav = [
  ['index.php','Dashboard','fa-gauge-high'],
  ['orders.php','Pesanan','fa-receipt'],
  ['products.php','Produk','fa-box'],
  ['import.php','Import / Export','fa-file-excel'],
  ['categories.php','Kategori','fa-tags'],
  ['brands.php','Brand','fa-copyright'],
  ['customers.php','Pelanggan','fa-users'],
  ['promos.php','Promo','fa-percent'],
  ['reports.php','Laporan','fa-chart-line'],
  ['settings.php','Pengaturan','fa-gear'],
  ['profile.php','Profil Admin','fa-id-badge'],
];
$activeGroup = ['product-form.php'=>'products.php','order-detail.php'=>'orders.php','export.php'=>'import.php','template.php'=>'import.php'];
$activeSelf = $activeGroup[$__self] ?? $__self;
?>
<!doctype html>
<html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= isset($pageTitle)?e($pageTitle).' · ':'' ?>Admin Chipi</title>
<link rel="icon" href="<?= asset('img/logo.png') ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<style>
  .admin-sidebar{background:<?= e(setting('adm_sidebar_color','#0e2a49')) ?>!important}
  .admin-topbar{background:<?= e(setting('adm_topbar_color','#ffffff')) ?>!important}
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>window.CHIPI_BASE='<?= BASE_URL ?>';window.CHIPI_CSRF='<?= csrf_token() ?>';</script>
</head>
<body class="admin-body">
<aside class="admin-sidebar">
  <div class="side-logo">
    <img src="<?= asset('img/logo.png') ?>" class="logo-glow" alt="Chipi">
    <div class="text-white small mt-1 fw-bold">Admin Panel</div>
  </div>
  <nav class="py-2">
    <?php foreach ($nav as $n): ?>
      <a href="<?= url('admin/'.$n[0]) ?>" class="<?= $activeSelf===$n[0]?'active':'' ?>"><i class="fa-solid <?= $n[2] ?> fa-fw"></i><?= $n[1] ?></a>
    <?php endforeach; ?>
    <a href="<?= url('admin/logout.php') ?>" class="mt-2 text-warning"><i class="fa-solid fa-right-from-bracket fa-fw"></i>Logout</a>
  </nav>
</aside>
<div class="sidebar-backdrop" onclick="toggleSidebar()"></div>
<div class="admin-main">
  <div class="admin-topbar">
    <button class="btn btn-light d-lg-none" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
    <h5 class="brand-font mb-0 flex-grow-1"><?= e($pageTitle ?? 'Dashboard') ?></h5>
    <?php $__notif = admin_notifications(); ?>
    <div class="dropdown">
      <button class="btn btn-light position-relative" type="button" id="notifBell" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" data-testid="notif-bell">
        <i class="fa-solid fa-bell"></i>
        <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" data-testid="notif-count" style="<?= $__notif['total'] ? '' : 'display:none' ?>"><?= $__notif['total'] ?></span>
      </button>
      <div class="dropdown-menu dropdown-menu-end shadow rounded-4 p-2" style="min-width:300px" data-testid="notif-menu">
        <h6 class="brand-font px-2 mb-1">Notifikasi</h6>
        <div id="notifItems">
        <?php if (!$__notif['items']): ?>
          <div class="text-center text-muted-chipi small py-3"><i class="fa-solid fa-check-circle d-block mb-1" style="font-size:1.5rem;color:#3ecf8e"></i>Tidak ada yang perlu tindakan.</div>
        <?php else: foreach ($__notif['items'] as $n): ?>
          <a href="<?= $n['url'] ?>" class="dropdown-item d-flex align-items-center gap-2 rounded-3 py-2" style="white-space:normal">
            <span style="width:34px;height:34px;border-radius:10px;background:<?= $n['color'] ?>1a;color:<?= $n['color'] ?>;display:flex;align-items:center;justify-content:center;flex:0 0 auto"><i class="fa-solid <?= $n['icon'] ?>"></i></span>
            <span class="small fw-semibold"><?= e($n['text']) ?></span>
          </a>
        <?php endforeach; endif; ?>
        </div>
      </div>
    </div>
    <script>
    (function(){
      const base=window.CHIPI_BASE||'';
      function render(d){
        const b=document.getElementById('notifBadge');
        if(d.total>0){b.textContent=d.total;b.style.display='';}else{b.style.display='none';}
        const wrap=document.getElementById('notifItems');
        if(!d.items||!d.items.length){wrap.innerHTML='<div class="text-center text-muted-chipi small py-3"><i class="fa-solid fa-check-circle d-block mb-1" style="font-size:1.5rem;color:#3ecf8e"></i>Tidak ada yang perlu tindakan.</div>';return;}
        wrap.innerHTML=d.items.map(function(n){return '<a href="'+n.url+'" class="dropdown-item d-flex align-items-center gap-2 rounded-3 py-2" style="white-space:normal"><span style="width:34px;height:34px;border-radius:10px;background:'+n.color+'1a;color:'+n.color+';display:flex;align-items:center;justify-content:center;flex:0 0 auto"><i class="fa-solid '+n.icon+'"></i></span><span class="small fw-semibold">'+n.text+'</span></a>';}).join('');
      }
      function poll(){fetch(base+'/admin/notif.php',{headers:{'X-Requested-With':'fetch'}}).then(function(r){return r.json();}).then(render).catch(function(){});}
      setInterval(poll,20000);
    })();
    </script>
    <a href="<?= url('index.php') ?>" target="_blank" class="btn btn-outline-chipi btn-sm"><i class="fa-solid fa-store me-1"></i>Lihat Toko</a>
    <span class="d-none d-md-inline small text-muted-chipi ms-2"><a href="<?= url('admin/profile.php') ?>" class="text-decoration-none text-muted-chipi"><i class="fa-solid fa-user-shield me-1"></i><?= e($admin['name']) ?></a></span>
  </div>
  <div class="p-3 p-md-4">
  <?php foreach (get_flashes() as $f): ?>
    <div class="alert alert-<?= $f['type']==='error'?'danger':e($f['type']) ?> alert-dismissible fade show rounded-4" data-testid="flash-message"><?= e($f['msg']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endforeach; ?>
