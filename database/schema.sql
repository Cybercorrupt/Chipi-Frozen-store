-- =====================================================
-- Chipi Frozen Food - Database Schema + Seed
-- MySQL / MariaDB (XAMPP compatible)
-- =====================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `chipi_frozen_food` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `chipi_frozen_food`;

DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `brands`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `addresses`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `promos`;
DROP TABLE IF EXISTS `settings`;

-- ---------- Admins ----------
CREATE TABLE `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Customers ----------
CREATE TABLE `customers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `whatsapp` VARCHAR(30) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `status` ENUM('pending','active','rejected') DEFAULT 'pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Addresses (one main per customer) ----------
CREATE TABLE `addresses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `label` VARCHAR(30) NOT NULL DEFAULT 'Rumah',
  `recipient_name` VARCHAR(100) NOT NULL,
  `whatsapp` VARCHAR(30) NOT NULL,
  `address` TEXT NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `postal_code` VARCHAR(15) DEFAULT NULL,
  `notes` VARCHAR(255) DEFAULT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  KEY `idx_addr_cust` (`customer_id`),
  CONSTRAINT `fk_addr_cust` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Categories ----------
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL UNIQUE,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Brands ----------
CREATE TABLE `brands` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Products ----------
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sku` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(200) NOT NULL,
  `category_id` INT DEFAULT NULL,
  `brand_id` INT DEFAULT NULL,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `promo_price` DECIMAL(12,2) DEFAULT NULL,
  `stock_qty` INT NOT NULL DEFAULT 0,
  `unit` VARCHAR(30) DEFAULT 'pcs',
  `weight` VARCHAR(30) DEFAULT NULL,
  `description` TEXT,
  `image` VARCHAR(255) DEFAULT NULL,
  `label` ENUM('NONE','NEW','PROMO','BEST SELLER') DEFAULT 'NONE',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_cat` (`category_id`),
  KEY `idx_brand` (`brand_id`),
  CONSTRAINT `fk_prod_cat` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_prod_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- Orders ----------
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(30) NOT NULL UNIQUE,
  `customer_id` INT DEFAULT NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_phone` VARCHAR(30) NOT NULL,
  `customer_address` TEXT,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `discount` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `shipping_cost` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `grand_total` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `payment_method` ENUM('Transfer','COD','Bayar di Toko') DEFAULT 'Transfer',
  `payment_status` ENUM('unpaid','pending','paid') DEFAULT 'unpaid',
  `payment_proof` VARCHAR(255) DEFAULT NULL,
  `delivery_method` VARCHAR(50) DEFAULT 'Delivery',
  `order_status` ENUM('Menunggu Konfirmasi','Dikonfirmasi','Diproses','Dikirim','Selesai','Dibatalkan') DEFAULT 'Menunggu Konfirmasi',
  `notes` TEXT,
  `promo_code` VARCHAR(50) DEFAULT NULL,
  `receipt_image` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `confirmed_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_cust` (`customer_id`),
  CONSTRAINT `fk_order_cust` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- Order Items (snapshot) ----------
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT DEFAULT NULL,
  `sku` VARCHAR(50) DEFAULT NULL,
  `product_name` VARCHAR(200) NOT NULL,
  `qty` INT NOT NULL DEFAULT 1,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0,
  KEY `idx_order` (`order_id`),
  CONSTRAINT `fk_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Promos ----------
CREATE TABLE `promos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `discount_type` ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `discount_value` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `min_purchase` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Settings (key-value) ----------
CREATE TABLE `settings` (
  `skey` VARCHAR(60) PRIMARY KEY,
  `svalue` TEXT
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- SEED DATA
-- =====================================================
-- Admin login: admin@chipi.id / admin123
INSERT INTO `admins` (`name`,`email`,`password`) VALUES
('Admin Chipi','admin@chipi.id','$2y$10$aL.isqI/0Y32cniKdQizKeV60ODVksOgA9YdkDx7rIjD1iEWC49km');

-- Customer login: budi@mail.com / customer123
INSERT INTO `customers` (`name`,`whatsapp`,`email`,`password`,`is_active`,`status`) VALUES
('Budi Santoso','081234567890','budi@mail.com','$2y$10$J7SEH.3jq5aZkJsaAglsNu3/fPYIoGsAlv5uEJD5eyx3xMoiLAhpq',1,'active');

INSERT INTO `addresses` (`customer_id`,`label`,`recipient_name`,`whatsapp`,`address`,`city`,`postal_code`,`notes`,`is_default`) VALUES
(1,'Rumah','Budi Santoso','081234567890','Jl. Melati No. 12, RT 03 RW 05','Jakarta Selatan','12345','Rumah cat biru pagar hitam',1),
(1,'Kantor','Budi Santoso','081234567890','Gedung Cyber 2 Lt. 5, Jl. HR Rasuna Said','Jakarta Selatan','12950','Resepsi lantai 5',0),
(1,'Toko','Toko Berkah Jaya','081298765432','Ruko Green Ville Blok A No. 3','Jakarta Barat','11510','Buka 08.00-20.00',0);

INSERT INTO `categories` (`name`,`slug`,`is_active`) VALUES
('Nugget','nugget',1),
('Sosis','sosis',1),
('Bakso','bakso',1),
('Dimsum','dimsum',1),
('Kentang','kentang',1),
('Seafood','seafood',1);

INSERT INTO `brands` (`name`,`is_active`) VALUES
('Chipi','1'),('Fiesta','1'),('So Good','1'),('Champ','1'),('Kanzler','1');

INSERT INTO `products`
(`sku`,`name`,`category_id`,`brand_id`,`price`,`promo_price`,`stock_qty`,`unit`,`weight`,`description`,`image`,`label`,`is_active`) VALUES
('CHF001','Chicken Nugget Original 500g',1,1,42000,38000,45,'pack','500 g','Nugget ayam renyah favorit keluarga, tinggal goreng.',NULL,'BEST SELLER',1),
('CHF002','Chicken Nugget Stik 250g',1,2,25000,NULL,30,'pack','250 g','Nugget stik praktis untuk cemilan anak.',NULL,'NEW',1),
('CHF003','Sosis Sapi Premium 500g',2,3,38000,33000,20,'pack','500 g','Sosis sapi juicy, cocok untuk bakar & goreng.',NULL,'PROMO',1),
('CHF004','Sosis Ayam 375g',2,4,22000,NULL,8,'pack','375 g','Sosis ayam lembut untuk sarapan cepat.',NULL,'NONE',1),
('CHF005','Bakso Sapi Halus 500g',3,3,35000,NULL,50,'pack','500 g','Bakso sapi kenyal untuk kuah bakso rumahan.',NULL,'BEST SELLER',1),
('CHF006','Bakso Urat 500g',3,1,40000,36000,12,'pack','500 g','Bakso urat berserat, gurih dan mantap.',NULL,'PROMO',1),
('CHF007','Dimsum Ayam Isi 10',4,1,28000,NULL,0,'pack','300 g','Dimsum ayam siap kukus, isi 10 pcs.',NULL,'NONE',1),
('CHF008','Dimsum Udang Isi 6',4,2,32000,NULL,6,'pack','250 g','Dimsum udang premium, lembut dan gurih.',NULL,'NEW',1),
('CHF009','Kentang Goreng Shoestring 1kg',5,4,30000,27000,40,'pack','1 kg','Kentang goreng renyah ala restoran.',NULL,'PROMO',1),
('CHF010','Kentang Wedges 1kg',5,4,34000,NULL,25,'pack','1 kg','Wedges tebal berbumbu, praktis di-oven.',NULL,'NONE',1),
('CHF011','Udang Tempura Isi 8',6,5,45000,NULL,15,'pack','200 g','Udang tempura crispy, tinggal goreng.',NULL,'NEW',1),
('CHF012','Fish Ball Seafood 500g',6,1,27000,NULL,3,'pack','500 g','Bola ikan kenyal untuk suki & hotpot.',NULL,'NONE',1);

INSERT INTO `promos` (`code`,`discount_type`,`discount_value`,`min_purchase`,`start_date`,`end_date`,`is_active`) VALUES
('CHIPI10','percentage',10,50000,'2026-01-01','2026-12-31',1),
('HEMAT15K','fixed',15000,100000,'2026-01-01','2026-12-31',1);

INSERT INTO `settings` (`skey`,`svalue`) VALUES
('store_name','Chipi Frozen Food'),
('logo','logo.png'),
('whatsapp_admin','6281200000000'),
('address','Jl. Frozen Raya No. 1, Jakarta'),
('opening_hours','Setiap hari 08.00 - 21.00'),
('min_order','25000'),
('shipping_cost','10000'),
('footer_text','Chipi Frozen Food - Frozen Food Favorit, Tinggal Masak!'),
('primary_color','#38b6ff'),
('secondary_color','#ff7a29'),
('shipping_methods','[{"name":"Delivery","cost":10000,"active":1},{"name":"Pickup di Toko","cost":0,"active":1}]'),
('bank_name','BCA'),
('bank_account','1234567890'),
('bank_holder','Chipi Frozen Food'),
('site_title','Chipi Frozen Food — Frozen Food Favorit, Tinggal Masak'),
('meta_description','Toko frozen food online: nugget, sosis, bakso, dimsum & seafood beku pilihan. Praktis, higienis, harga terjangkau, diantar ke rumah.'),
('social_show','1'),
('social_instagram','chipifrozenfood'),
('social_facebook','chipifrozenfood'),
('social_tiktok','chipifrozenfood'),
('social_whatsapp','6281200000000'),
('banner','');

-- Product images (bundled in /uploads/products)
UPDATE `products` SET `image`=CONCAT(`sku`,'.jpg');
