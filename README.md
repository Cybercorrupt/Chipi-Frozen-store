# Chipi Frozen Food 🐮❄️

Mini e-commerce frozen food (Native PHP + MySQL + Bootstrap 5). Mobile-first, XAMPP-ready.

## Fitur Utama
- **Customer**: register/login, katalog produk (search/filter/sort), detail produk, keranjang, checkout (Delivery/Pickup, Transfer/COD/Bayar di Toko, kode promo), profil, alamat, riwayat pesanan + timeline status, nota.
- **Admin**: dashboard (KPI + 2 chart), manajemen pesanan, **konfirmasi pesanan → nota PNG otomatis**, tombol WhatsApp (wa.me), CRUD produk, **bulk action** (aktif/nonaktif/kategori/brand/label/harga/stok/hapus), **Import/Export Excel + ZIP gambar** (preview), kategori, brand, pelanggan, promo, laporan, pengaturan.

## Teknologi
Native PHP 8, MySQL/MariaDB (PDO), Bootstrap 5, Chart.js, Font Awesome, PhpSpreadsheet (Excel), PHP GD (nota).

## Cara Menjalankan di XAMPP
1. Salin seluruh folder ini ke `htdocs/chipi` (atau root `htdocs`).
2. Jalankan **Apache** & **MySQL** dari XAMPP Control Panel.
3. Buat database & tabel + seed:
   - Buka phpMyAdmin → **Import** → pilih `database/schema.sql` → **Go**.
   - (Ini membuat database `chipi_frozen_food` beserta data contoh.)
4. Sesuaikan koneksi DB di `config/config.php` bila perlu. **Default XAMPP**: user `root`, password kosong:
   ```php
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```
5. Buka di browser:
   - Toko: `http://localhost/chipi/index.php`
   - Admin: `http://localhost/chipi/admin/login.php`
6. Pastikan folder `uploads/` writable (produk, nota, banner).
7. `vendor/` (PhpSpreadsheet) sudah disertakan. Jika hilang, jalankan `composer install`.

## Akun Demo
| Peran    | Login                        | Password      |
|----------|------------------------------|---------------|
| Admin    | `admin@chipi.id`             | `admin123`    |
| Customer | `budi@mail.com` / `081234567890` | `customer123` |

## Import Produk (Excel)
Kolom: `SKU, Product Name, Category, Brand, Price, Promo Price, Stock, Unit, Weight, Description, Status, Label, Image Filename`.
- SKU = identifier: baru → **create**, sudah ada → **update**.
- Sel kosong **tidak** menghapus data lama; gunakan `[CLEAR]` untuk mengosongkan.
- Gambar via `product-images.zip` (nama file cocok dengan kolom Image Filename). Gambar rusak dilewati tanpa menggagalkan seluruh import.

## Struktur Folder
```
/admin      /customer   /includes   /config
/assets     /uploads    /database   /vendor   index.php
```
