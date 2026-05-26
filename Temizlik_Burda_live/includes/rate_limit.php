<?php
// ============================================================
// Temizci Burada  -  Siber Güvenlik: Rate Limiter + Brute Force
// ============================================================

require_once __DIR__ . '/db.php';

/**
 * login_attempts tablosunu yoksa oluştur
 */
function ensureLoginAttemptsTable(): void
{
    try {
        getDB()->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            email VARCHAR(150) NOT NULL,
            attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip_email (ip_address, email),
            INDEX idx_attempted_at (attempted_at)
        )");
    } catch (Throwable $e) {
        // Tablo oluşturulamazsa sessizce geç
    }
}

/**
 * Brute force koruması: 5 başarısız giriş â†’ 15 dakika kilit
 */
function checkLoginAttempts(string $ip, string $email): bool
{
    ensureLoginAttemptsTable();
    try {
        $stmt = getDB()->prepare("
            SELECT COUNT(*) FROM login_attempts
            WHERE ip_address = ? AND email = ?
              AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$ip, $email]);
        return (int) $stmt->fetchColumn() < 5;
    } catch (Throwable $e) {
        return true; // Tablo yoksa girişe izin ver
    }
}

/**
 * Başarısız girişi kaydet
 */
function recordFailedLogin(string $ip, string $email): void
{
    try {
        getDB()->prepare("INSERT INTO login_attempts (ip_address, email) VALUES (?, ?)")
            ->execute([$ip, $email]);
    } catch (Throwable $e) {
    }
}

/**
 * Başarılı girişte denemeleri temizle
 */
function clearLoginAttempts(string $ip, string $email): void
{
    try {
        getDB()->prepare("DELETE FROM login_attempts WHERE ip_address = ? AND email = ?")
            ->execute([$ip, $email]);
    } catch (Throwable $e) {
    }
}

/**
 * Kalan bekleme süresini saniye cinsinden döndür
 */
function getLoginLockoutSeconds(string $ip, string $email): int
{
    try {
        $stmt = getDB()->prepare("
            SELECT MAX(attempted_at) FROM login_attempts
            WHERE ip_address = ? AND email = ?
              AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$ip, $email]);
        $lastAttempt = $stmt->fetchColumn();
        if (!$lastAttempt)
            return 0;
        return max(0, 900 - (time() - strtotime($lastAttempt)));
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * Key bazli rate limiter.
 * Ayni IP'de farkli endpointler icin farkli limitler tanimlamaya yarar.
 */
function rateLimitByKey(string $rawKey, int $maxRequests = 60, int $window = 60, ?string $errorMessage = null): void
{
    $bucket = 'rl_' . md5($rawKey);
    $cacheFile = sys_get_temp_dir() . '/' . $bucket . '.json';

    $data = ['count' => 0, 'reset_at' => time() + $window];
    if (file_exists($cacheFile)) {
        $decoded = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($decoded) && isset($decoded['count'], $decoded['reset_at'])) {
            $data = [
                'count' => (int) $decoded['count'],
                'reset_at' => (int) $decoded['reset_at'],
            ];
        }
    }

    if (time() > (int) $data['reset_at']) {
        $data = ['count' => 0, 'reset_at' => time() + $window];
    }

    $data['count'] = ((int) $data['count']) + 1;
    file_put_contents($cacheFile, json_encode($data), LOCK_EX);

    if ((int) $data['count'] > $maxRequests) {
        http_response_code(429);
        $retryAfter = max(1, ((int) $data['reset_at']) - time());
        header('Retry-After: ' . $retryAfter);
        echo json_encode([
            'success' => false,
            'error' => $errorMessage ?? ('Cok fazla istek. ' . $retryAfter . ' saniye sonra tekrar deneyin.'),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * IP bazli genel rate limiter (dakikada max istek)
 */
function rateLimit(int $maxRequests = 60, int $window = 60): void
{
    $ip = getUserIP();
    rateLimitByKey('ip:' . $ip, $maxRequests, $window);
}

/**
 * Kullanıcı IP'sini al (proxy arkasında da çalışır)
 */
function getUserIP(): string
{
    foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

