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

function ensureSessionVersionColumn(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        $db = getDB();
        $col = $db->query("SHOW COLUMNS FROM users LIKE 'session_version'")->fetch();
        if (!$col) {
            $db->exec("ALTER TABLE users ADD COLUMN session_version INT NOT NULL DEFAULT 0 AFTER is_active");
        }
    } catch (Throwable $e) {
        // If schema update fails, continue with existing behavior.
    }
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

    if (currentUser() === null) {
        header('Location: ' . APP_URL . $redirect);
        exit;
    }
}

// Mevcut kullanıcıyı getir
function currentUser(bool $forceRefresh = false): ?array
{
    if (!isLoggedIn())
        return null;

    ensureSessionVersionColumn();

    static $user = null;
    if ($forceRefresh) {
        $user = null;
    }

    if ($user === null) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }

    if (!$user) {
        logoutUser();
        return null;
    }

    $dbVersion = (int) ($user['session_version'] ?? 0);
    $sessionVersion = $_SESSION['session_version'] ?? null;
    if ($sessionVersion !== null && (int) $sessionVersion !== $dbVersion) {
        logoutUser();
        return null;
    }
    $_SESSION['session_version'] = $dbVersion;

    return $user;
}

// Oturum aç
function loginUser(int $userId, ?int $sessionVersion = null): void
{
    ensureSessionVersionColumn();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['logged_in_at'] = time();

    if ($sessionVersion === null) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT session_version FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $sessionVersion = (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            $sessionVersion = 0;
        }
    }
    $_SESSION['session_version'] = (int) $sessionVersion;
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
