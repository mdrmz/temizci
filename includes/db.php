<?php
// ============================================================
// Temizci Burada  -  Veritabanı Bağlantısı (PDO)
// ============================================================

require_once __DIR__ . '/config.php';

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die('<div style="font-family:sans-serif;padding:20px;background:#fee;color:#c00;border:1px solid #c00;border-radius:8px;margin:20px">
                    <strong>Veritabanı Hatası:</strong> ' . htmlspecialchars($e->getMessage()) . '
                    <br><small>DB: ' . DB_NAME . ' | User: ' . DB_USER . ' | Host: ' . DB_HOST . '</small>
                    <br><small>Lütfen config.php içindeki veritabanı ayarlarını kontrol edin.</small>
                </div>');
            } else {
                die('Sistem hatası. Lütfen daha sonra tekrar deneyin.');
            }
        }
    }
    return $pdo;
}

