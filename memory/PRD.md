# Chipi Frozen Food — PRD

## Problem Statement
Lightweight, mobile-first mini e-commerce for "Chipi Frozen Food" using **Native PHP + MySQL + Bootstrap 5** (XAMPP compatible, no Node/React/Laravel). Indonesian UI. MVP focus on core store operations.

## Tech Stack
Native PHP 8, MySQL/MariaDB (PDO prepared statements), Bootstrap 5, Chart.js, Font Awesome, PhpSpreadsheet (Excel), PHP GD (receipt PNG). Session auth + CSRF + bcrypt password hashing.

## User Personas
- **Customer**: browses, orders frozen food, tracks status, downloads receipt.
- **Admin**: manages orders/products/catalog, confirms orders (auto receipt), bulk & Excel operations.

## Architecture / Folders
`/admin /customer /includes /config /assets /uploads(products,receipts,banners) /database /vendor index.php`
- `config/config.php` (DB creds + BASE_URL auto-detect), `config/db.php` (PDO singleton).
- `includes/functions.php` (helpers, auth, cart, order#), `includes/receipt.php` (GD PNG), header/footer/product_card/account_nav partials.
- DB: admins, customers, addresses, categories, brands, products, orders, order_items, promos, settings. Order snapshot stored in order_items.

## Implemented (2026-08-11) — MVP COMPLETE, tested 100%
- P1 DB schema + seed + session auth (customer & admin) + CSRF ✓
- P2 Product catalog, search/filter/sort, categories, product detail + related ✓
- P3 Cart (AJAX qty/remove/clear, sticky mobile bar) + checkout (Delivery/Pickup, Transfer/COD/Bayar di Toko, promo code, shipping) ✓
- P4 Customer dashboard/profile/address(1 main)/orders(timeline)/receipts ✓
- P5 Admin order management (list/search/filter/detail/status/cancel) ✓
- P6 Confirm order → auto PNG receipt (GD, Chipi blue/orange, logo) stored + linked ✓
- P7 WhatsApp wa.me button with prefilled message ✓
- P8 Admin product CRUD + image upload/validation ✓
- P9 Bulk selection (page + all-filtered) + bulk set active/inactive/category/brand/label/price(set/±amt/±%)/stock/delete(soft if in orders) ✓
- P10 Excel import (SKU create/update, blank=keep, [CLEAR], preview counts, ZIP image match, bad image skipped) + export all/selected/filtered + template ✓
- P11 Dashboard 5 KPIs + 2 charts (sales line 7d, status donut) + operational area ✓
- P12 Promo CRUD, settings (logo/banner upload, colors, shipping), simple reports (ranges + top 5) ✓
- Responsive: mobile bottom nav, 2/3/4-5 col grids, admin mobile cards + drawer sidebar ✓
- Branding: logo with `.logo-glow`; 12 seeded products with real images.

## Update (2026-08-12)
- Tema disempurnakan (soft tapi tegas: bg lembut, kartu ber-border, aksen firm) + logo hero beranimasi (float/sway, hormati prefers-reduced-motion).
- Pengaturan **Jenis Pengiriman & Ongkir** (JSON, dinamis) → checkout memakai metode aktif; `orders.delivery_method` jadi VARCHAR.
- **Profil Admin** (edit nama/email/password) + **Export Laporan** XLSX (Ringkasan + Daftar Pesanan) mengikuti filter periode.
- **Watermark nota otomatis** (PAID/NOT PAID/CANCELED) + badge status di bawah grand total dihapus.
- **Bukti Transfer**: kolom `payment_status`(unpaid/pending/paid) + `payment_proof`. Customer upload bukti (→pending), admin **Konfirmasi Pembayaran** (→paid) yang otomatis regenerasi nota jadi PAID. Folder `/uploads/proofs`.
- **Approval registrasi**: kolom `customers.status`(pending/active/rejected). Registrasi baru → pending (halaman `pending.php` + tombol WA admin), login diblok sampai disetujui. Admin approve/tolak di halaman Pelanggan (filter status + badge).
- **Notifikasi admin**: lonceng di topbar (dropdown) menampilkan pendaftaran menunggu, pesanan menunggu konfirmasi, pembayaran menunggu verifikasi, dan stok menipis — via `admin_notifications()`.

## Update (2026-06 — verified)
- **Notifikasi realtime admin**: lonceng topbar auto-refresh tiap 20 dtk via `admin/notif.php` (JSON dari `admin_notifications()`). Badge & dropdown terverifikasi (pesanan menunggu, verifikasi bayar, pendaftaran, stok menipis).
- **Template pesan WA dinamis**: `tpl_order_confirm`/`tpl_reg_approve`/`tpl_reg_reject` di Pengaturan; `render_template()` pakai `default_template()` sebagai fallback bila kosong. Placeholder `{name} {order_number} {total}`.
- **Warna tampilan dinamis**: `fe_header_color`, `fe_footer_color`, `adm_sidebar_color`, `adm_topbar_color` disuntik ke `<style>` header frontend & admin.
- **Info Rekening Bank di checkout**: blok rekening (bank_name/account/holder) tampil hanya saat metode "Transfer" dipilih (toggle JS pada `.pay-radio`), tersembunyi untuk COD/Bayar di Toko.
- Semua diverifikasi via screenshot (admin dashboard/settings, checkout Transfer & COD) + curl notif.php.

## Update (2026-06 — Storefront dapat diatur dari admin + Settings bertab)
- **Pengaturan admin dirombak jadi 10 tab nav-pills vertikal per fungsi**: Umum & Toko, Tampilan & Warna, Header & Menu, Hero Beranda, Banner Promo, Kenapa Chipi?, Footer, Rekening Bank, Template Pesan, Pengiriman & Ongkir. Satu form → satu tombol Simpan menyimpan semua tab sekaligus (pane tersembunyi tetap ikut submit). Pengaturan lama TIDAK diduplikasi, hanya dikelompokkan ulang.
- **Konten frontend kini dapat diedit** (sebelumnya hardcoded): Hero (badge/judul/subjudul/teks+tautan tombol), Banner Promo (judul/teks/tombol), bagian "Kenapa Chipi?" (judul + item ikon/warna/judul/deskripsi, bisa tambah/hapus), serta Menu navigasi header & footer (label+URL, list dinamis). Tiap bagian hero/promo/benefits punya **toggle Tampilkan/Sembunyikan**.
- Helper di `functions.php`: `fe()`, `fe_show()`, `fe_defaults()`, `benefit_items()`/`default_benefits()`, `nav_links()`/`default_nav()`, `nav_url()`. Frontend: `index.php` (hero/promo/benefits terbungkus `fe_show`), `includes/header.php` & `footer.php` (menu dari `nav_links`). Diuji 100% (test_reports/iteration_4.json).

## Update (2026-06 — Multi-alamat customer)
- **Multi-alamat**: tabel `addresses` kini punya `label` (Rumah/Kantor/Toko/Keluarga/Lainnya) & `is_default`; UNIQUE(customer_id) dilepas → `idx_addr_cust`. `customer/address.php` jadi CRUD penuh (tambah/ubah/hapus + Jadikan Utama), tepat satu alamat default (alamat pertama auto-default; hapus default → promosi berikutnya). Checkout menampilkan pemilih radio semua alamat (default terpilih) dan pesanan memakai alamat yang dipilih. Helper: `customer_addresses()`, `default_address()`, `set_default_address()`, `address_labels()`. Diuji 100% (test_reports/iteration_3.json).

## Update (2026-06 — Smart Repeat Order + env recovery)
- **Repeat Order pintar**: `customer/repeat-order.php` + `reorder_analyze()` (includes/functions.php ~L163). Dari pesanan lama, sistem cek harga & stok terbaru per item dan beri status: Siap / Harga naik / Harga turun / Stok terbatas (qty di-cap ke sisa stok) / Stok habis / Tidak tersedia lagi. Tombol "Pesan Ulang" di daftar pesanan & detail pesanan. Submit menambahkan HANYA item tersedia ke keranjang (re-cek server-side saat submit) lalu redirect ke cart. Diuji 100% (test_reports/iteration_2.json).
- **ENV RECOVERY (PENTING)**: Pod pernah ter-reset di sesi ini → semua paket apt (PHP, MariaDB, mysql client) terhapus (hanya `/app` persist). Dipasang ulang PHP 8.2 + MariaDB 10.11, DB dibuat ulang & schema.sql diimpor. MariaDB dijalankan via `mysqld_safe` (bukan systemd). **Jika pod reset lagi, jalankan: `bash /app/scripts/restore_env.sh`** untuk memulihkan seluruh environment otomatis.

## Demo Accounts
- Admin: `admin@chipi.id` / `admin123`
- Customer: `budi@mail.com` (or `081234567890`) / `customer123`

## Deliverable
Full source in `/app` (vendor included). Import `database/schema.sql` in phpMyAdmin, set `config/config.php` DB creds (XAMPP: root / empty), open `/index.php` and `/admin/login.php`. See README.md.

## Backlog (Phase 2 — explicitly out of MVP)
Payment gateway, WhatsApp Cloud API auto-send, PDF invoices, multi-address, product variants/reviews, loyalty, advanced analytics, PWA.

## Update (2026-06 — Imported into fresh Emergent pod + bug fixes)
- Repo re-imported into a clean base pod and run AS-IS as PHP 8.2 + MariaDB 10.11. PHP served on port 3000 via supervisor `frontend` program → `/app/scripts/serve.sh` (self-healing: reinstalls via restore_env.sh + starts MariaDB + imports schema if missing) with `/app/scripts/router.php`. composer vendor (PhpSpreadsheet) reinstalled. Demo data seeded (1 admin, 1 customer, 12 products).
- **Bug fix (HIGH) timezone**: `config/db.php` now runs `SET time_zone='+07:00'` on connect so MySQL NOW()/created_at align with PHP Asia/Jakarta. Fixes dashboard "Pesanan/Penjualan Hari Ini" showing 0 and order-number/date mismatch. Verified (iteration_7).
- **Bug fix (MEDIUM) empty template textareas**: Settings → Template Pesan now pre-fills default templates when settings rows empty. Verified.
- **Minor UI fix**: dashboard doughnut "Status Pesanan" chart constrained to 260px (was oversized square).
- Full e2e re-verified 100% frontend (iteration_6 & iteration_7). NOTE: PHP+MySQL stack is NOT deployable via Emergent's standard deploy pipeline.
