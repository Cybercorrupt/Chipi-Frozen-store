<?php
/**
 * Chipi Frozen Food - Global Configuration
 * -----------------------------------------
 * XAMPP users: set DB_USER = 'root' and DB_PASS = '' (default XAMPP).
 */

// ---------- Database ----------
define('DB_HOST', getenv('CHIPI_DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('CHIPI_DB_NAME') ?: 'chipi_frozen_food');
define('DB_USER', getenv('CHIPI_DB_USER') ?: 'chipi');
define('DB_PASS', getenv('CHIPI_DB_PASS') ?: 'chipi123');
define('DB_CHARSET', 'utf8mb4');

// ---------- Paths / URL ----------
// BASE_URL auto-detects the project folder. Works on XAMPP (e.g. /chipi) and root.
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
// Strip trailing /admin or /customer segment to get project root URL.
$scriptDir = preg_replace('#/(admin|customer|public)(/.*)?$#', '', $scriptDir);
if ($scriptDir === '' || $scriptDir === '/') { $scriptDir = ''; }
define('BASE_URL', rtrim($scriptDir, '/'));

define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('RECEIPT_PATH', UPLOAD_PATH . '/receipts');
define('PRODUCT_IMG_PATH', UPLOAD_PATH . '/products');
define('BANNER_PATH', UPLOAD_PATH . '/banners');
define('PROOF_PATH', UPLOAD_PATH . '/proofs');

define('APP_NAME', 'Chipi Frozen Food');

// ---------- Session ----------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
