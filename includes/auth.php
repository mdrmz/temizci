<?php
// ============================================================
// Temizci Burada — Auth Yardımcı Fonksiyonları
// ============================================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Oturum başlat
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(SESSION_LIFETIME);
    session_start();
}

// Giriş yapmış mı?
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Giriş zorunlu
function requireLogin(string $redirect = '/login.php'): void
{
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . $redirect);
        exit;
    }
}

// Mevcut kullanıcıyı getir
function currentUser(): ?array
{
    if (!isLoggedIn())
        return null;
    static $user = null;
    if ($user === null) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

// Oturum aç
function loginUser(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['logged_in_at'] = time();
}

// Oturumu kapat
function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

// CSRF token oluştur
function csrfToken(): string
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

// CSRF doğrula
function verifyCsrf(): bool
{
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    return hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token);
}

// CSRF gizli alan HTML'i
function csrfField(): string
{
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars(csrfToken()) . '">';
}
