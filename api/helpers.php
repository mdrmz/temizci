<?php
// ============================================================
// Temizci Burada — REST API Yardımcı Fonksiyonlar
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/rate_limit.php';

// Genel rate limiter — dakikada 120 istek
rateLimit(120, 60);

/**
 * Başarılı JSON yanıt
 */
function jsonSuccess(array $data = [], int $code = 200): void
{
    http_response_code($code);
    echo json_encode(['success' => true, ...$data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Hata JSON yanıtı
 */
function jsonError(string $message, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * POST body'yi JSON olarak parse et
 */
function getJsonBody(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Authorization header'dan Bearer token al
 */
function getBearerToken(): ?string
{
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
        return $m[1];
    }
    return null;
}

/**
 * Token doğrula, user döndür. Geçersizse jsonError ile dur.
 */
function authenticate(): array
{
    $token = getBearerToken();
    if (!$token)
        jsonError('Yetkilendirme başlığı eksik.', 401);

    $db = getDB();
    $stmt = $db->prepare("
        SELECT u.* FROM api_tokens t
        JOIN users u ON t.user_id = u.id
        WHERE t.token = ? AND t.expires_at > NOW() AND u.is_active = 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user)
        jsonError('Oturum süresi dolmuş veya geçersiz token.', 401);
    return $user;
}

/**
 * Yeni API token oluştur (30 gün geçerli)
 */
function createApiToken(int $userId): string
{
    $token = bin2hex(random_bytes(32));
    $db = getDB();
    // Eski tokenları temizle
    $db->prepare("DELETE FROM api_tokens WHERE user_id = ? AND expires_at < NOW()")
        ->execute([$userId]);
    $db->prepare("INSERT INTO api_tokens (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))")
        ->execute([$userId, $token]);
    return $token;
}

/**
 * Kullanıcı datasını güvenli şekilde döndür (şifre vb. gizle)
 */
function safeUser(array $user): array
{
    unset($user['password']);
    $user['avatar_url'] = $user['avatar']
        ? APP_URL . '/uploads/avatars/' . $user['avatar']
        : null;
    return $user;
}
