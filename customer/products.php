<?php
require_once __DIR__ . '/../includes/functions.php';
$page = 'products';
$pageTitle = 'Produk';

$q        = trim($_GET['q'] ?? '');
$category = (int)($_GET['category'] ?? 0);
$brand    = (int)($_GET['brand'] ?? 0);
$avail    = $_GET['avail'] ?? '';
$promo    = isset($_GET['promo']) && $_GET['promo'] == '1';
$sort     = $_GET['sort'] ?? 'newest';

$where = ['p.is_active = 1'];
$params = [];
if ($q !== '') { $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR b.name LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($category) { $where[] = 'p.category_id = ?'; $params[] = $category; }
if ($brand)    { $where[] = 'p.brand_id = ?'; $params[] = $brand; }
if ($promo)    { $where[] = 'p.promo_price IS NOT NULL AND p.promo_price > 0'; }
if ($avail === 'in') { $where[] = 'p.stock_qty > 0'; }
if ($avail === 'out'){ $where[] = 'p.stock_qty <= 0'; }

$order = match ($sort) {
    'price_asc'  => 'effp ASC',
    'price_desc' => 'effp DESC',
    'name'       => 'p.name ASC',
    default      => 'p.created_at DESC, p.id DESC',
};

$sql = "SELECT p.*, b.name brand_name, c.name category_name,
        (CASE WHEN p.promo_price IS NOT NULL AND p.promo_price>0 AND p.promo_price<p.price THEN p.promo_price ELSE p.price END) effp
        FROM products p
        LEFT JOIN brands b ON b.id=p.brand_id
        LEFT JOIN categories c ON c.id=p.category_id
        WHERE " . implode(' AND ', $where) . " ORDER BY $order";
$st = db()->prepare($sql);
$st->execute($params);
$products = $st->fetchAll();

$categories = db()->query("SELECT * FROM categories WHERE is_active=1 ORDER BY name")->fetchAll();
$brands     = db()->query("SELECT * FROM brands WHERE is_active=1 ORDER BY name")->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="container my-4">
  <h4 class="section-title brand-font">Semua Produk</h4>

  <form method="get" class="card p-3 mb-3" id="kategori">
    <div class="row g-2">
      <div class="col-12 col-md-4">
        <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari nama / SKU / brand" data-testid="filter-search">
      </div>
      <div class="col-6 col-md-2">
        <select class="form-select" name="category" data-testid="filter-category">
          <option value="0">Semua Kategori</option>
          <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= $category==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <select class="form-select" name="brand" data-testid="filter-brand">
          <option value="0">Semua Brand</option>
          <?php foreach ($brands as $b): ?><option value="<?= $b['id'] ?>" <?= $brand==$b['id']?'selected':'' ?>><?= e($b['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <select class="form-select" name="avail" data-testid="filter-avail">
          <option value="">Semua Stok</option>
          <option value="in" <?= $avail==='in'?'selected':'' ?>>Tersedia</option>
          <option value="out" <?= $avail==='out'?'selected':'' ?>>Habis</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <select class="form-select" name="sort" data-testid="filter-sort">
          <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Terbaru</option>
          <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Harga Termurah</option>
          <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Harga Tertinggi</option>
          <option value="name" <?= $sort==='name'?'selected':'' ?>>Nama A-Z</option>
        </select>
      </div>
      <div class="col-12 d-flex gap-2">
        <label class="form-check ms-1 d-flex align-items-center gap-1">
          <input type="checkbox" class="form-check-input" name="promo" value="1" <?= $promo?'checked':'' ?>> <span class="small">Promo saja</span>
        </label>
        <button class="btn btn-chipi-blue btn-sm ms-auto px-4" data-testid="filter-apply"><i class="fa-solid fa-filter me-1"></i>Terapkan</button>
        <a href="<?= url('customer/products.php') ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
      </div>
    </div>
  </form>

  <p class="text-muted-chipi small"><?= count($products) ?> produk ditemukan<?= $q !== '' ? ' untuk "' . e($q) . '"' : '' ?></p>

  <?php if (!$products): ?>
    <div class="empty-state"><i class="fa-solid fa-box-open d-block"></i>Tidak ada produk yang cocok.</div>
  <?php else: ?>
    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-3" data-testid="product-grid">
      <?php foreach ($products as $p): ?>
        <div class="col"><?php require __DIR__ . '/../includes/product_card.php'; ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
