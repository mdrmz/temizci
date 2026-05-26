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

$ga4MeasurementId = strtoupper(trim((string) ($_ENV['GA4_MEASUREMENT_ID'] ?? '')));
if (!preg_match('/^G-[A-Z0-9]+$/', $ga4MeasurementId)) {
    $ga4MeasurementId = '';
}
define('GA4_MEASUREMENT_ID', $ga4MeasurementId);

$clarityProjectId = trim((string) ($_ENV['CLARITY_PROJECT_ID'] ?? ''));
$clarityProjectId = preg_replace('/[^a-zA-Z0-9]/', '', $clarityProjectId) ?? '';
define('CLARITY_PROJECT_ID', $clarityProjectId);

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
define('MAX_FILE_SIZE', 12 * 1024 * 1024); // 12MB
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

$_tbRequestPathForHeader = strtolower((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
$_tbIsApiRequest = str_starts_with($_tbRequestPathForHeader, '/api/');

if (!headers_sent() && !$_tbIsApiRequest) {
    header('Content-Type: text/html; charset=UTF-8');
}
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

function tbBuildTrackingBootstrap(): string
{
    $ga4Id = GA4_MEASUREMENT_ID;
    $clarityId = CLARITY_PROJECT_ID;

    if ($ga4Id === '' && $clarityId === '') {
        return '';
    }

    $payload = json_encode(
        ['ga4' => $ga4Id, 'clarity' => $clarityId],
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
    );
    if (!is_string($payload) || $payload === '') {
        return '';
    }

    return <<<HTML
<script id="tb-tracking-bootstrap">
(function(cfg){
    if (!cfg || typeof cfg !== 'object') return;

    window.TB_TRACKING = cfg;

    if (cfg.ga4) {
        window.dataLayer = window.dataLayer || [];
        window.gtag = window.gtag || function(){ window.dataLayer.push(arguments); };

        var gaScript = document.createElement('script');
        gaScript.async = true;
        gaScript.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(cfg.ga4);
        document.head.appendChild(gaScript);

        window.gtag('js', new Date());
        window.gtag('config', cfg.ga4, { anonymize_ip: true });
    }

    if (cfg.clarity && !window.clarity) {
        (function(c,l,a,r,i,t,y){
            c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
            t=l.createElement(r);
            t.async=1;
            t.src='https://www.clarity.ms/tag/' + i;
            y=l.getElementsByTagName(r)[0];
            y.parentNode.insertBefore(t,y);
        })(window, document, 'clarity', 'script', cfg.clarity);
    }
})($payload);
</script>
HTML;
}

function tbInjectTrackingIntoHead(string $buffer): string
{
    if ($buffer === '' || stripos($buffer, '</head>') === false) {
        return $buffer;
    }
    if (stripos($buffer, 'id="tb-tracking-bootstrap"') !== false) {
        return $buffer;
    }

    $snippet = tbBuildTrackingBootstrap();
    if ($snippet === '') {
        return $buffer;
    }

    $result = preg_replace('/<\/head>/i', $snippet . PHP_EOL . '</head>', $buffer, 1);
    return is_string($result) ? $result : $buffer;
}

$requestPath = strtolower((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
$skipTrackingForPath = str_contains($requestPath, '/admin') || str_contains($requestPath, '/api');

if (
    !defined('TB_TRACKING_BUFFER_ENABLED') &&
    !$skipTrackingForPath &&
    (GA4_MEASUREMENT_ID !== '' || CLARITY_PROJECT_ID !== '') &&
    PHP_SAPI !== 'cli'
) {
    define('TB_TRACKING_BUFFER_ENABLED', true);
    ob_start('tbInjectTrackingIntoHead');
}

