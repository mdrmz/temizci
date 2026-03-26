<?php
// ============================================================
// Temizci Burada  -  Yapılandırma Dosyası
// ============================================================

//  -  -  -  Ortam Algılama  -  -  - 
// localhost'ta mı yoksa sunucuda mı çalışıyoruz?
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'])
        || in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'])
        || (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false);

// .env dosyası varsa yükle (production ortamında kullanılır)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue;
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

//  -  -  -  Uygulama  -  -  - 
define('APP_NAME', $_ENV['APP_NAME'] ?? 'Temizci Burada');
define('APP_VERSION', '1.0.0');

if ($isLocal) {
    // === LOCALHOST AYARLARI ===
    // URL'i otomatik algıla (klasör yapısına göre)
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    // includes/ veya alt klasörlerden çağrılıyorsa ana dizine dön
    $basePath = $scriptDir;
    // Eğer /includes, /api, /listings vb. alt klasördeyse üst dizini bul
    $subDirs = ['/includes', '/api', '/listings', '/homes', '/offers', '/admin', '/database', '/admin/includes'];
    foreach ($subDirs as $sub) {
        if (str_ends_with($basePath, $sub)) {
            $basePath = substr($basePath, 0, -strlen($sub));
            break;
        }
    }
    define('APP_URL', 'http://localhost' . rtrim($basePath, '/'));
    define('DB_HOST', 'localhost');
    define('DB_NAME', $_ENV['DB_NAME'] ?? 'temizlik_burda');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    $env = 'local';
} else {
    // === SUNUCU AYARLARI (.env'den okunur) ===
    define('APP_URL', $_ENV['APP_URL'] ?? 'https://temizciburada.com');
    define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
    define('DB_NAME', $_ENV['DB_NAME'] ?? 'temizlik_burda');
    define('DB_USER', $_ENV['DB_USER'] ?? 'temizci');
    define('DB_PASS', $_ENV['DB_PASS'] ?? '');
    $env = $_ENV['APP_ENV'] ?? 'production';
}

define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('DB_CHARSET', 'utf8mb4');

//  -  -  -  Oturum  -  -  - 
define('SESSION_LIFETIME', 7 * 24 * 60 * 60); // 7 gün
define('CSRF_TOKEN_NAME', '_csrf_token');

//  -  -  -  Ortam Modu  -  -  - 
define('DEBUG_MODE', $env === 'local');

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}

//  -  -  -  Güvenli Session Ayarları  -  -  - 
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
// HTTPS varsa cookie'yi sadece HTTPS üzerinden gönder
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', 1);
}

//  -  -  -  Timezone  -  -  - 
date_default_timezone_set('Europe/Istanbul');

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

