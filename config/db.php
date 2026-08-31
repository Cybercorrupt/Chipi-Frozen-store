<?php
require_once __DIR__ . '/config.php';

/**
 * Returns a shared PDO connection (singleton).
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            // Align MySQL session time zone with the app time zone (Asia/Jakarta = +07:00)
            // so NOW()/CURRENT_TIMESTAMP columns and DATE() comparisons match PHP date().
            $pdo->exec("SET time_zone = '+07:00'");
        } catch (PDOException $e) {
            http_response_code(500);
            die('Koneksi database gagal. Pastikan MySQL berjalan dan konfigurasi di config/config.php benar.<br><small>' . htmlspecialchars($e->getMessage()) . '</small>');
        }
    }
    return $pdo;
}
