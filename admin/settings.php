<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pageTitle = 'Pengaturan';

// Scalar text settings (saved directly from same-named inputs)
$keys = [
    'store_name','whatsapp_admin','address','opening_hours','min_order','shipping_cost','footer_text',
    'primary_color','secondary_color','fe_header_color','fe_footer_color','adm_sidebar_color','adm_topbar_color',
    'bank_name','bank_account','bank_holder',
    'tpl_order_confirm','tpl_reg_approve','tpl_reg_reject',
    'hero_badge','hero_title','hero_subtitle','hero_cta_text','hero_cta_link',
    'promo_title','promo_text','promo_btn_text','promo_btn_link',
    'benefits_title',
    'site_title','meta_description','social_instagram','social_facebook','social_tiktok','social_whatsapp',
];
$boolKeys = ['hero_show','promo_show','benefits_show','social_show'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $upd = db()->prepare('INSERT INTO settings (skey,svalue) VALUES (?,?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)');
    foreach ($keys as $k) { if (!array_key_exists($k, $_POST)) continue; $upd->execute([$k, trim($_POST[$k])]); }
    foreach ($boolKeys as $k) { $upd->execute([$k, isset($_POST[$k]) ? '1' : '0']); }

    // Shipping methods
    $names = $_POST['sm_name'] ?? []; $costs = $_POST['sm_cost'] ?? []; $acts = $_POST['sm_active'] ?? [];
    $methods = [];
    foreach ($names as $i => $nm) {
        $nm = trim($nm);
        if ($nm === '') continue;
        $methods[] = ['name' => $nm, 'cost' => (float)($costs[$i] ?? 0), 'active' => isset($acts[$i]) ? 1 : 0];
    }
    if ($methods) $upd->execute(['shipping_methods', json_encode($methods, JSON_UNESCAPED_UNICODE)]);

    // Benefits ("Kenapa Chipi?")
    $bi_icon = $_POST['bi_icon'] ?? []; $bi_color = $_POST['bi_color'] ?? []; $bi_title = $_POST['bi_title'] ?? []; $bi_desc = $_POST['bi_desc'] ?? [];
    $benefits = [];
    foreach ($bi_title as $i => $t) {
        $t = trim($t);
        if ($t === '') continue;
        $benefits[] = [
            'icon'  => trim($bi_icon[$i] ?? 'fa-star') ?: 'fa-star',
            'color' => trim($bi_color[$i] ?? '#38b6ff') ?: '#38b6ff',
            'title' => $t,
            'desc'  => trim($bi_desc[$i] ?? ''),
        ];
    }
    if (isset($_POST['bi_title'])) $upd->execute(['benefits_items', json_encode($benefits, JSON_UNESCAPED_UNICODE)]);

    // Navigation menus (header & footer)
    foreach (['nav_header' => ['nh_label','nh_url'], 'nav_footer' => ['nf_label','nf_url']] as $navKey => $fields) {
        [$lf, $uf] = $fields;
        $labels = $_POST[$lf] ?? []; $urls = $_POST[$uf] ?? [];
        $links = [];
        foreach ($labels as $i => $lb) {
            $lb = trim($lb);
            if ($lb === '') continue;
            $links[] = ['label' => $lb, 'url' => trim($urls[$i] ?? 'index.php') ?: 'index.php'];
        }
        if (isset($_POST[$lf])) $upd->execute([$navKey, json_encode($links, JSON_UNESCAPED_UNICODE)]);
    }

    // Logo / banner / favicon upload
    foreach (['logo'=>'logo','banner'=>'banner','favicon'=>'favicon'] as $field=>$key) {
        if (!empty($_FILES[$field]['name']) && $_FILES[$field]['error']===UPLOAD_ERR_OK) {
            $info = @getimagesize($_FILES[$field]['tmp_name']);
            $map = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            if ($info && isset($map[$info['mime']])) {
                if ($field==='logo')   { $dir=ROOT_PATH.'/assets/img'; $fname='logo.png'; $val='logo.png'; }
                elseif ($field==='favicon') { $dir=ROOT_PATH.'/assets/img'; $fname='favicon.'.$map[$info['mime']]; $val=$fname; }
                else { $dir=BANNER_PATH; if(!is_dir($dir))@mkdir($dir,0775,true); $fname='banner_'.time().'.'.$map[$info['mime']]; $val=$fname; }
                if (move_uploaded_file($_FILES[$field]['tmp_name'], $dir.'/'.$fname)) $upd->execute([$key,$val]);
            }
        }
    }

    flash('success','Pengaturan disimpan.');
    redirect('admin/settings.php' . (isset($_POST['_tab']) ? '?tab='.urlencode($_POST['_tab']) : ''));
}

$s  = settings();
$sm = shipping_methods(false);
$benefits = benefit_items();
$navH = nav_links('header');
$navF = nav_links('footer');
$activeTab = preg_replace('/[^a-z]/','', $_GET['tab'] ?? 'umum');
$tabs = [
    'umum'      => ['Umum & Toko', 'fa-store'],
    'tampilan'  => ['Tampilan & Warna', 'fa-palette'],
    'beranda'   => ['Konten Beranda', 'fa-house'],
    'layout'    => ['Header & Footer', 'fa-bars'],
    'sosial'    => ['Sosial & SEO', 'fa-share-nodes'],
    'checkout'  => ['Pembayaran & Pengiriman', 'fa-credit-card'],
    'template'  => ['Template Pesan', 'fa-comment-dots'],
];
if (!isset($tabs[$activeTab])) $activeTab = 'umum';

require __DIR__ . '/includes/admin_header.php';
?>
<div class="row g-3">
  <div class="col-lg-3">
    <div class="card p-2">
      <div class="nav flex-column nav-pills" id="settingsTabs" role="tablist">
        <?php foreach ($tabs as $tk => $tv): ?>
          <button class="nav-link text-start <?= $activeTab===$tk?'active':'' ?>" id="pill-<?= $tk ?>" data-bs-toggle="pill" data-bs-target="#tab-<?= $tk ?>" type="button" role="tab" data-testid="settings-tab-<?= $tk ?>">
            <i class="fa-solid <?= $tv[1] ?> me-2"></i><?= e($tv[0]) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-9">
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="_tab" id="_tab" value="<?= e($activeTab) ?>">
      <div class="tab-content">

        <!-- UMUM -->
        <div class="tab-pane fade <?= $activeTab==='umum'?'show active':'' ?>" id="tab-umum" role="tabpanel">
          <div class="card p-3 p-md-4">
            <h6 class="brand-font mb-3"><i class="fa-solid fa-store me-2 text-primary"></i>Umum & Toko</h6>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label small fw-bold">Nama Toko</label><input class="form-control" name="store_name" value="<?= e($s['store_name']??'') ?>" data-testid="set-store-name"></div>
              <div class="col-md-6"><label class="form-label small fw-bold">WhatsApp Admin</label><input class="form-control" name="whatsapp_admin" value="<?= e($s['whatsapp_admin']??'') ?>" placeholder="62812xxxx"></div>
              <div class="col-12"><label class="form-label small fw-bold">Alamat</label><input class="form-control" name="address" value="<?= e($s['address']??'') ?>"></div>
              <div class="col-md-6"><label class="form-label small fw-bold">Jam Buka</label><input class="form-control" name="opening_hours" value="<?= e($s['opening_hours']??'') ?>"></div>
              <div class="col-md-3"><label class="form-label small fw-bold">Min. Order</label><input class="form-control" type="number" name="min_order" value="<?= e($s['min_order']??'0') ?>"></div>
              <div class="col-md-3"><label class="form-label small fw-bold">Ongkir Default</label><input class="form-control" type="number" name="shipping_cost" value="<?= e($s['shipping_cost']??'0') ?>" data-testid="set-shipping"></div>
              <div class="col-md-6"><label class="form-label small fw-bold">Logo (PNG disarankan)</label><input class="form-control" type="file" name="logo" accept="image/*"><div class="mt-2"><img src="<?= asset('img/logo.png') ?>" style="height:60px" class="logo-glow"></div></div>
              <div class="col-md-6"><label class="form-label small fw-bold">Banner Homepage</label><input class="form-control" type="file" name="banner" accept="image/*"><?php if(!empty($s['banner']) && file_exists(BANNER_PATH.'/'.$s['banner'])): ?><div class="mt-2"><img src="<?= url('uploads/banners/'.$s['banner']) ?>" style="max-height:60px"></div><?php endif; ?></div>
            </div>
          </div>
        </div>

        <!-- TAMPILAN -->
        <div class="tab-pane fade <?= $activeTab==='tampilan'?'show active':'' ?>" id="tab-tampilan" role="tabpanel">
          <div class="card p-3 p-md-4">
            <h6 class="brand-font mb-1"><i class="fa-solid fa-palette me-2 text-primary"></i>Tampilan & Warna</h6>
            <p class="small text-muted-chipi">Warna primer/sekunder toko serta warna header & footer (frontend) dan sidebar & topbar (admin).</p>
            <div class="row g-3">
              <div class="col-6 col-md-3"><label class="form-label small fw-bold">Warna Primer</label><input class="form-control form-control-color" type="color" name="primary_color" value="<?= e($s['primary_color']??'#38b6ff') ?>"></div>
              <div class="col-6 col-md-3"><label class="form-label small fw-bold">Warna Sekunder</label><input class="form-control form-control-color" type="color" name="secondary_color" value="<?= e($s['secondary_color']??'#ff7a29') ?>"></div>
              <div class="col-6 col-md-3"><label class="form-label small fw-bold">Header Toko</label><input class="form-control form-control-color" type="color" name="fe_header_color" value="<?= e($s['fe_header_color'] ?? '#38b6ff') ?>" data-testid="color-fe-header"></div>
              <div class="col-6 col-md-3"><label class="form-label small fw-bold">Footer Toko</label><input class="form-control form-control-color" type="color" name="fe_footer_color" value="<?= e($s['fe_footer_color'] ?? '#0e2a49') ?>" data-testid="color-fe-footer"></div>
              <div class="col-6 col-md-3"><label class="form-label small fw-bold">Sidebar Admin</label><input class="form-control form-control-color" type="color" name="adm_sidebar_color" value="<?= e($s['adm_sidebar_color'] ?? '#0e2a49') ?>" data-testid="color-adm-sidebar"></div>
              <div class="col-6 col-md-3"><label class="form-label small fw-bold">Topbar Admin</label><input class="form-control form-control-color" type="color" name="adm_topbar_color" value="<?= e($s['adm_topbar_color'] ?? '#ffffff') ?>" data-testid="color-adm-topbar"></div>
            </div>
          </div>
        </div>

        <!-- BERANDA (hero + promo + benefits) -->
        <div class="tab-pane fade <?= $activeTab==='beranda'?'show active':'' ?>" id="tab-beranda" role="tabpanel">
          <div class="card p-3 p-md-4 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="brand-font mb-0"><i class="fa-solid fa-image me-2 text-primary"></i>Hero Beranda</h6>
              <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="hero_show" id="hero_show" value="1" <?= fe_show('hero_show')?'checked':'' ?> data-testid="toggle-hero"><label class="form-check-label small" for="hero_show">Tampilkan</label></div>
            </div>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label small fw-bold">Badge</label><input class="form-control" name="hero_badge" value="<?= e(fe('hero_badge')) ?>" data-testid="hero-badge"></div>
              <div class="col-md-6"><label class="form-label small fw-bold">Teks Tombol (CTA)</label><input class="form-control" name="hero_cta_text" value="<?= e(fe('hero_cta_text')) ?>"></div>
              <div class="col-12"><label class="form-label small fw-bold">Judul <span class="text-muted-chipi">(Enter untuk baris baru)</span></label><textarea class="form-control" name="hero_title" rows="2" data-testid="hero-title"><?= e(fe('hero_title')) ?></textarea></div>
              <div class="col-12"><label class="form-label small fw-bold">Subjudul</label><textarea class="form-control" name="hero_subtitle" rows="2"><?= e(fe('hero_subtitle')) ?></textarea></div>
              <div class="col-12"><label class="form-label small fw-bold">Tautan Tombol</label><input class="form-control" name="hero_cta_link" value="<?= e(fe('hero_cta_link')) ?>" placeholder="customer/products.php"></div>
            </div>
          </div>

          <div class="card p-3 p-md-4 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="brand-font mb-0"><i class="fa-solid fa-tags me-2 text-primary"></i>Banner Promo</h6>
              <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="promo_show" id="promo_show" value="1" <?= fe_show('promo_show')?'checked':'' ?> data-testid="toggle-promo"><label class="form-check-label small" for="promo_show">Tampilkan</label></div>
            </div>
            <div class="row g-3">
              <div class="col-md-8"><label class="form-label small fw-bold">Judul</label><input class="form-control" name="promo_title" value="<?= e(fe('promo_title')) ?>" data-testid="promo-title"></div>
              <div class="col-md-4"><label class="form-label small fw-bold">Teks Tombol</label><input class="form-control" name="promo_btn_text" value="<?= e(fe('promo_btn_text')) ?>"></div>
              <div class="col-12"><label class="form-label small fw-bold">Teks Promo</label><textarea class="form-control" name="promo_text" rows="2"><?= e(fe('promo_text')) ?></textarea></div>
              <div class="col-12"><label class="form-label small fw-bold">Tautan Tombol</label><input class="form-control" name="promo_btn_link" value="<?= e(fe('promo_btn_link')) ?>" placeholder="customer/products.php?promo=1"></div>
            </div>
          </div>

          <div class="card p-3 p-md-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="brand-font mb-0"><i class="fa-solid fa-star me-2 text-primary"></i>Bagian "Kenapa Chipi?"</h6>
              <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="benefits_show" id="benefits_show" value="1" <?= fe_show('benefits_show')?'checked':'' ?> data-testid="toggle-benefits"><label class="form-check-label small" for="benefits_show">Tampilkan</label></div>
            </div>
            <div class="mb-3"><label class="form-label small fw-bold">Judul Bagian</label><input class="form-control" name="benefits_title" value="<?= e(fe('benefits_title')) ?>"></div>
            <p class="small text-muted-chipi">Ikon memakai nama Font Awesome (mis. <code>fa-award</code>, <code>fa-bolt</code>). Kosongkan judul untuk menghapus item.</p>
            <div id="benefitRows" class="vstack gap-2">
              <?php foreach ($benefits as $b): ?>
              <div class="row g-2 align-items-center benefit-row">
                <div class="col-3 col-md-2"><input class="form-control form-control-sm" name="bi_icon[]" value="<?= e($b['icon']) ?>" placeholder="fa-star"></div>
                <div class="col-3 col-md-1"><input class="form-control form-control-color form-control-sm" type="color" name="bi_color[]" value="<?= e($b['color']) ?>"></div>
                <div class="col-6 col-md-3"><input class="form-control form-control-sm" name="bi_title[]" value="<?= e($b['title']) ?>" placeholder="Judul"></div>
                <div class="col-10 col-md-5"><input class="form-control form-control-sm" name="bi_desc[]" value="<?= e($b['desc']) ?>" placeholder="Deskripsi"></div>
                <div class="col-2 col-md-1"><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.benefit-row').remove()"><i class="fa-solid fa-trash"></i></button></div>
              </div>
              <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-outline-chipi btn-sm mt-2" onclick="addBenefitRow()" data-testid="add-benefit"><i class="fa-solid fa-plus me-1"></i>Tambah Keunggulan</button>
          </div>
        </div>

        <!-- LAYOUT (menu header + footer + footer text) -->
        <div class="tab-pane fade <?= $activeTab==='layout'?'show active':'' ?>" id="tab-layout" role="tabpanel">
          <div class="card p-3 p-md-4 mb-3">
            <h6 class="brand-font mb-1"><i class="fa-solid fa-bars me-2 text-primary"></i>Menu Navigasi</h6>
            <p class="small text-muted-chipi">Tautan menu di header dan footer. URL bisa path internal (mis. <code>customer/products.php</code>) atau tautan penuh (https://...).</p>
            <div class="row g-4">
              <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-2"><b class="small">Menu Header</b><button type="button" class="btn btn-outline-chipi btn-sm" onclick="addNavRow('navHeaderRows','nh')" data-testid="add-nav-header"><i class="fa-solid fa-plus me-1"></i>Tambah</button></div>
                <div id="navHeaderRows" class="vstack gap-2">
                  <?php foreach ($navH as $l): ?>
                  <div class="row g-2 nav-row">
                    <div class="col-5"><input class="form-control form-control-sm" name="nh_label[]" value="<?= e($l['label']) ?>" placeholder="Label"></div>
                    <div class="col-6"><input class="form-control form-control-sm" name="nh_url[]" value="<?= e($l['url']) ?>" placeholder="URL/path"></div>
                    <div class="col-1"><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.nav-row').remove()"><i class="fa-solid fa-trash"></i></button></div>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-2"><b class="small">Menu Footer</b><button type="button" class="btn btn-outline-chipi btn-sm" onclick="addNavRow('navFooterRows','nf')" data-testid="add-nav-footer"><i class="fa-solid fa-plus me-1"></i>Tambah</button></div>
                <div id="navFooterRows" class="vstack gap-2">
                  <?php foreach ($navF as $l): ?>
                  <div class="row g-2 nav-row">
                    <div class="col-5"><input class="form-control form-control-sm" name="nf_label[]" value="<?= e($l['label']) ?>" placeholder="Label"></div>
                    <div class="col-6"><input class="form-control form-control-sm" name="nf_url[]" value="<?= e($l['url']) ?>" placeholder="URL/path"></div>
                    <div class="col-1"><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.nav-row').remove()"><i class="fa-solid fa-trash"></i></button></div>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
          <div class="card p-3 p-md-4">
            <h6 class="brand-font mb-1"><i class="fa-solid fa-shoe-prints me-2 text-primary"></i>Footer</h6>
            <p class="small text-muted-chipi">Alamat, jam buka, dan WhatsApp diambil dari tab <b>Umum & Toko</b>.</p>
            <div class="col-12"><label class="form-label small fw-bold">Teks Footer (tagline)</label><input class="form-control" name="footer_text" value="<?= e($s['footer_text'] ?? '') ?>" placeholder="Frozen Food Favorit, Tinggal Masak!" data-testid="footer-text"></div>
          </div>
        </div>

        <!-- SOSIAL & SEO -->
        <div class="tab-pane fade <?= $activeTab==='sosial'?'show active':'' ?>" id="tab-sosial" role="tabpanel">
          <div class="card p-3 p-md-4 mb-3">
            <h6 class="brand-font mb-1"><i class="fa-solid fa-magnifying-glass me-2 text-primary"></i>SEO Situs</h6>
            <p class="small text-muted-chipi">Judul & deskripsi untuk tab browser dan mesin pencari, serta favicon (ikon tab).</p>
            <div class="row g-3">
              <div class="col-md-8"><label class="form-label small fw-bold">Judul Situs <span class="text-muted-chipi">(kosong = pakai Nama Toko)</span></label><input class="form-control" name="site_title" value="<?= e($s['site_title'] ?? '') ?>" placeholder="Chipi Frozen Food — Frozen Food Favorit" data-testid="seo-title"></div>
              <div class="col-md-4"><label class="form-label small fw-bold">Favicon (PNG/ICO)</label><input class="form-control" type="file" name="favicon" accept="image/*"><?php if(!empty($s['favicon']) && file_exists(ROOT_PATH.'/assets/img/'.$s['favicon'])): ?><div class="mt-2"><img src="<?= asset('img/'.$s['favicon']) ?>" style="height:32px"></div><?php endif; ?></div>
              <div class="col-12"><label class="form-label small fw-bold">Deskripsi Meta</label><textarea class="form-control" name="meta_description" rows="2" placeholder="Toko frozen food online: nugget, sosis, bakso, dimsum & seafood beku. Praktis, higienis, harga terjangkau." data-testid="seo-desc"><?= e($s['meta_description'] ?? '') ?></textarea></div>
            </div>
          </div>
          <div class="card p-3 p-md-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="brand-font mb-0"><i class="fa-solid fa-share-nodes me-2 text-primary"></i>Media Sosial</h6>
              <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="social_show" id="social_show" value="1" <?= fe_show('social_show')?'checked':'' ?> data-testid="toggle-social"><label class="form-check-label small" for="social_show">Tampilkan</label></div>
            </div>
            <p class="small text-muted-chipi">Isi username atau URL penuh. Jika toggle nonaktif, ikon sosial tidak muncul di footer.</p>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label small fw-bold"><i class="fa-brands fa-instagram me-1"></i>Instagram</label><input class="form-control" name="social_instagram" value="<?= e($s['social_instagram'] ?? '') ?>" placeholder="chipifrozenfood" data-testid="social-instagram"></div>
              <div class="col-md-6"><label class="form-label small fw-bold"><i class="fa-brands fa-facebook me-1"></i>Facebook</label><input class="form-control" name="social_facebook" value="<?= e($s['social_facebook'] ?? '') ?>" placeholder="chipifrozenfood" data-testid="social-facebook"></div>
              <div class="col-md-6"><label class="form-label small fw-bold"><i class="fa-brands fa-tiktok me-1"></i>TikTok</label><input class="form-control" name="social_tiktok" value="<?= e($s['social_tiktok'] ?? '') ?>" placeholder="chipifrozenfood" data-testid="social-tiktok"></div>
              <div class="col-md-6"><label class="form-label small fw-bold"><i class="fa-brands fa-whatsapp me-1"></i>WhatsApp</label><input class="form-control" name="social_whatsapp" value="<?= e($s['social_whatsapp'] ?? '') ?>" placeholder="6281234567890" data-testid="social-whatsapp"></div>
            </div>
          </div>
        </div>

        <!-- CHECKOUT (bank + shipping) -->
        <div class="tab-pane fade <?= $activeTab==='checkout'?'show active':'' ?>" id="tab-checkout" role="tabpanel">
          <div class="card p-3 p-md-4 mb-3">
            <h6 class="brand-font mb-1"><i class="fa-solid fa-building-columns me-2 text-primary"></i>Info Rekening Bank <span class="small text-muted-chipi fw-normal">(pembayaran Transfer)</span></h6>
            <div class="row g-3">
              <div class="col-md-4"><label class="form-label small fw-bold">Nama Bank</label><input class="form-control" name="bank_name" value="<?= e($s['bank_name'] ?? '') ?>" placeholder="BCA" data-testid="bank-name"></div>
              <div class="col-md-4"><label class="form-label small fw-bold">No. Rekening</label><input class="form-control" name="bank_account" value="<?= e($s['bank_account'] ?? '') ?>" placeholder="1234567890" data-testid="bank-account"></div>
              <div class="col-md-4"><label class="form-label small fw-bold">Atas Nama</label><input class="form-control" name="bank_holder" value="<?= e($s['bank_holder'] ?? '') ?>" placeholder="Chipi Frozen Food" data-testid="bank-holder"></div>
            </div>
          </div>
          <div class="card p-3 p-md-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="brand-font mb-0"><i class="fa-solid fa-truck me-2 text-primary"></i>Jenis Pengiriman & Ongkir</h6>
              <button type="button" class="btn btn-outline-chipi btn-sm" onclick="addShipRow()" data-testid="add-ship-method"><i class="fa-solid fa-plus me-1"></i>Tambah Metode</button>
            </div>
            <p class="small text-muted-chipi">Pilihan pengiriman saat checkout beserta ongkirnya. Ongkir 0 = gratis (mis. Pickup).</p>
            <div id="shipRows" class="vstack gap-2">
              <?php foreach ($sm as $m): ?>
              <div class="row g-2 align-items-center ship-row">
                <div class="col-6 col-md-5"><input class="form-control" name="sm_name[]" value="<?= e($m['name']) ?>" placeholder="Nama metode" data-testid="ship-name"></div>
                <div class="col-4 col-md-4"><div class="input-group"><span class="input-group-text">Rp</span><input class="form-control" type="number" min="0" name="sm_cost[]" value="<?= (int)$m['cost'] ?>" placeholder="Ongkir" data-testid="ship-cost"></div></div>
                <div class="col-2 col-md-2"><div class="form-check"><input class="form-check-input" type="checkbox" name="sm_active[]" value="1" <?= $m['active']?'checked':'' ?>><label class="form-check-label small">Aktif</label></div></div>
                <div class="col-12 col-md-1 text-md-end"><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.ship-row').remove()"><i class="fa-solid fa-trash"></i></button></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- TEMPLATE -->
        <div class="tab-pane fade <?= $activeTab==='template'?'show active':'' ?>" id="tab-template" role="tabpanel">
          <div class="card p-3 p-md-4">
            <h6 class="brand-font mb-1"><i class="fa-solid fa-comment-dots me-2 text-primary"></i>Template Pesan WhatsApp</h6>
            <p class="small text-muted-chipi">Placeholder: <code>{name}</code>, <code>{order_number}</code>, <code>{total}</code> (khusus konfirmasi pesanan).</p>
            <div class="mb-3"><label class="form-label small fw-bold">Konfirmasi Pesanan</label><textarea class="form-control" name="tpl_order_confirm" rows="4" data-testid="tpl-order"><?= e(($s['tpl_order_confirm'] ?? '') !== '' ? $s['tpl_order_confirm'] : default_template('tpl_order_confirm')) ?></textarea></div>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label small fw-bold">Registrasi Disetujui</label><textarea class="form-control" name="tpl_reg_approve" rows="4" data-testid="tpl-approve"><?= e(($s['tpl_reg_approve'] ?? '') !== '' ? $s['tpl_reg_approve'] : default_template('tpl_reg_approve')) ?></textarea></div>
              <div class="col-md-6"><label class="form-label small fw-bold">Registrasi Ditolak</label><textarea class="form-control" name="tpl_reg_reject" rows="4" data-testid="tpl-reject"><?= e(($s['tpl_reg_reject'] ?? '') !== '' ? $s['tpl_reg_reject'] : default_template('tpl_reg_reject')) ?></textarea></div>
            </div>
          </div>
        </div>

      </div>

      <div class="d-flex mt-3">
        <button class="btn btn-chipi" data-testid="set-save"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan Pengaturan</button>
      </div>
    </form>
  </div>
</div>

<script>
document.querySelectorAll('#settingsTabs [data-bs-toggle="pill"]').forEach(function(btn){
  btn.addEventListener('shown.bs.tab', function(e){ document.getElementById('_tab').value = e.target.id.replace('pill-',''); });
});
function addShipRow(){
  var wrap=document.getElementById('shipRows');var div=document.createElement('div');div.className='row g-2 align-items-center ship-row';
  div.innerHTML='<div class="col-6 col-md-5"><input class="form-control" name="sm_name[]" placeholder="Nama metode"></div><div class="col-4 col-md-4"><div class="input-group"><span class="input-group-text">Rp</span><input class="form-control" type="number" min="0" name="sm_cost[]" value="0"></div></div><div class="col-2 col-md-2"><div class="form-check"><input class="form-check-input" type="checkbox" name="sm_active[]" value="1" checked><label class="form-check-label small">Aktif</label></div></div><div class="col-12 col-md-1 text-md-end"><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest(\'.ship-row\').remove()"><i class="fa-solid fa-trash"></i></button></div>';
  wrap.appendChild(div);
}
function addBenefitRow(){
  var wrap=document.getElementById('benefitRows');var div=document.createElement('div');div.className='row g-2 align-items-center benefit-row';
  div.innerHTML='<div class="col-3 col-md-2"><input class="form-control form-control-sm" name="bi_icon[]" placeholder="fa-star"></div><div class="col-3 col-md-1"><input class="form-control form-control-color form-control-sm" type="color" name="bi_color[]" value="#38b6ff"></div><div class="col-6 col-md-3"><input class="form-control form-control-sm" name="bi_title[]" placeholder="Judul"></div><div class="col-10 col-md-5"><input class="form-control form-control-sm" name="bi_desc[]" placeholder="Deskripsi"></div><div class="col-2 col-md-1"><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest(\'.benefit-row\').remove()"><i class="fa-solid fa-trash"></i></button></div>';
  wrap.appendChild(div);
}
function addNavRow(wrapId, prefix){
  var wrap=document.getElementById(wrapId);var div=document.createElement('div');div.className='row g-2 nav-row';
  div.innerHTML='<div class="col-5"><input class="form-control form-control-sm" name="'+prefix+'_label[]" placeholder="Label"></div><div class="col-6"><input class="form-control form-control-sm" name="'+prefix+'_url[]" placeholder="URL/path"></div><div class="col-1"><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest(\'.nav-row\').remove()"><i class="fa-solid fa-trash"></i></button></div>';
  wrap.appendChild(div);
}
</script>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
