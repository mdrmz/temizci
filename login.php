<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/rate_limit.php';

if (isLoggedIn())
    redirect(APP_URL . '/dashboard');

$errors = [];
$lockoutSecs = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Güvenlik hatası. Lütfen tekrar deneyin.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        $ip = getUserIP();

        // === Brute Force Kontrolü ===
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Geçerli bir e-posta adresi girin.';
        } elseif (!checkLoginAttempts($ip, $email)) {
            $lockoutSecs = getLoginLockoutSeconds($ip, $email);
            $minutes = ceil($lockoutSecs / 60);
            $errors[] = "Çok fazla başarısız giriş denemesi. Lütfen {$minutes} dakika bekleyin.";
        } else {
            if (empty($password))
                $errors[] = 'Şifre zorunludur.';

            if (empty($errors)) {
                $db = getDB();
                $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    clearLoginAttempts($ip, $email); // Başarılı → temizle
                    loginUser($user['id']);
                    if ($remember) {
                        session_set_cookie_params(SESSION_LIFETIME);
                    }
                    setFlash('success', 'Hoş geldiniz, ' . $user['name'] . '!');
                    redirect(APP_URL . '/dashboard');
                } else {
                    recordFailedLogin($ip, $email); // Başarısız → kaydet
                    $db2 = getDB();
                    $stmt2 = $db2->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND email = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
                    $stmt2->execute([$ip, $email]);
                    $tries = (int) $stmt2->fetchColumn();
                    $left = 5 - $tries;
                    $errors[] = 'E-posta veya şifre hatalı.' . ($left > 0 ? " ({$left} deneme hakkınız kaldı)" : '');
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap — Temizci Burada</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="assets/css/style.css?v=4.0">
    <link rel="stylesheet" href="assets/css/dark-mode.css">

    <!-- SEO & Favicon -->
    <link rel="icon" href="/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="/logo.png">
    <meta property="og:image" content="https://www.temizciburada.com/logo.png">
</head>

<body>
    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-logo">
                <div class="logo-icon">🧹</div>
                <h1>Tekrar Hoş Geldiniz</h1>
                <p>Hesabınıza giriş yapın</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="flash flash-error">❌
                    <?= e(implode(' ', $errors)) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" data-validate>
                <?= csrfField() ?>
                <div class="form-group">
                    <label class="form-label" for="email">E-posta Adresi</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="ornek@mail.com"
                        value="<?= e($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Şifre</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••"
                        required>
                </div>
                <div class="flex-between mb-3">
                    <label class="toggle-wrapper" style="cursor:pointer;">
                        <label class="toggle">
                            <input type="checkbox" name="remember">
                            <span class="toggle-slider"></span>
                        </label>
                        <span style="font-size:0.85rem;color:var(--text-secondary);">Beni hatırla</span>
                    </label>
                    <a href="#" style="font-size:0.85rem;">Şifremi unuttum?</a>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg">Giriş Yap</button>
            </form>

            <div class="divider">veya</div>
            <div class="auth-footer">
                Hesabınız yok mu? <a href="register">Ücretsiz Kayıt Ol</a>
            </div>
            <div class="text-center mt-3">
                <a href="index" style="font-size:0.82rem;color:var(--text-muted);">← Ana Sayfaya Dön</a>
            </div>
        </div>
    </div>
    <script src="assets/js/app.js?v=4.0"></script>
    <script src="assets/js/theme.js"></script>
</body>

</html>