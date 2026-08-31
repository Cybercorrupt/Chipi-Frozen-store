<footer class="footer-chipi pt-4 pb-5">
  <div class="container">
    <div class="row gy-3">
      <div class="col-md-5">
        <img src="<?= asset('img/logo.png') ?>" class="logo-glow mb-2" style="height:70px" alt="Chipi">
        <p class="small mb-1"><?= e(setting('footer_text', 'Frozen Food Favorit, Tinggal Masak!')) ?></p>
        <p class="small mb-0"><i class="fa-solid fa-location-dot me-1"></i><?= e(setting('address')) ?></p>
        <p class="small"><i class="fa-solid fa-clock me-1"></i><?= e(setting('opening_hours')) ?></p>
      </div>
      <div class="col-6 col-md-3">
        <h6 class="brand-font text-white">Menu</h6>
        <ul class="list-unstyled small">
          <?php foreach (nav_links('footer') as $nl): ?>
            <li><a href="<?= e(nav_url($nl['url'])) ?>"><?= e($nl['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="col-6 col-md-4">
        <h6 class="brand-font text-white">Hubungi Kami</h6>
        <a href="https://wa.me/<?= e(setting('whatsapp_admin')) ?>" class="btn btn-chipi btn-sm" target="_blank"><i class="fa-brands fa-whatsapp me-1"></i>Chat Admin</a>
        <?php if (fe_show('social_show')):
          $socials = [
            ['social_instagram','fa-instagram','#e1306c','https://instagram.com/'],
            ['social_facebook','fa-facebook-f','#1877f2','https://facebook.com/'],
            ['social_tiktok','fa-tiktok','#000','https://tiktok.com/@'],
            ['social_whatsapp','fa-whatsapp','#25d366','https://wa.me/'],
          ];
          $hasSocial = false; foreach ($socials as $sc) { if (setting($sc[0],'')) { $hasSocial = true; break; } }
          if ($hasSocial): ?>
          <div class="d-flex gap-2 mt-3" data-testid="footer-socials">
            <?php foreach ($socials as $sc):
              $val = trim(setting($sc[0],''));
              if ($val === '') continue;
              $href = preg_match('#^https?://#i', $val) ? $val : $sc[3] . ltrim($val, '@/'); ?>
              <a href="<?= e($href) ?>" target="_blank" rel="noopener" class="social-ico d-inline-flex align-items-center justify-content-center" style="width:36px;height:36px;border-radius:50%;background:<?= $sc[2] ?>;color:#fff" data-testid="social-<?= e($sc[0]) ?>"><i class="fa-brands <?= $sc[1] ?>"></i></a>
            <?php endforeach; ?>
          </div>
        <?php endif; endif; ?>
      </div>
    </div>
    <hr class="border-secondary opacity-25">
    <p class="small text-center mb-0 opacity-75">&copy; <?= date('Y') ?> <?= e(setting('store_name', APP_NAME)) ?>. All rights reserved.</p>
  </div>
</footer>

<!-- Mobile bottom nav -->
<nav class="bottom-nav">
  <a href="<?= url('index.php') ?>" class="<?= $__page === 'home' ? 'active' : '' ?>" data-testid="bnav-home"><i class="fa-solid fa-house"></i>Home</a>
  <a href="<?= url('customer/products.php') ?>" class="<?= $__page === 'products' ? 'active' : '' ?>" data-testid="bnav-products"><i class="fa-solid fa-store"></i>Produk</a>
  <a href="<?= url('customer/cart.php') ?>" class="<?= $__page === 'cart' ? 'active' : '' ?>" data-testid="bnav-cart"><i class="fa-solid fa-cart-shopping"></i>Cart<?php if ($__cartCount): ?><span class="bn-badge" data-cart-count><?= $__cartCount ?></span><?php endif; ?></a>
  <a href="<?= url('customer/orders.php') ?>" class="<?= $__page === 'orders' ? 'active' : '' ?>" data-testid="bnav-orders"><i class="fa-solid fa-receipt"></i>Pesanan</a>
  <a href="<?= url($__cust ? 'customer/dashboard.php' : 'customer/login.php') ?>" class="<?= $__page === 'account' ? 'active' : '' ?>" data-testid="bnav-account"><i class="fa-solid fa-user"></i>Akun</a>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
