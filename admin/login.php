<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// If already logged in as admin, redirect to dashboard
$user = currentUser();
if ($user && $user['role'] === 'admin') {
    header('Location: /admin/index');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCsrf()) {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email && $password) {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $u = $stmt->fetch();

            if ($u && password_verify($password, $u['password'])) {
                if ($u['role'] === 'admin') {
                    loginUser($u['id']);
                    setFlash('success', 'Yönetici girişi başarılı. Hoş geldiniz.');
                    header('Location: /admin/index');
                    exit;
                } else {
                    $error = 'Bu alana sadece yöneticiler erişebilir.';
                }
            } else {
                $error = 'E-posta veya şifre hatalı.';
            }
        } else {
            $error = 'Lütfen tüm alanları doldurun.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetici Girişi — Temizci Burada</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="../assets/css/style.css?v=4.0">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <link rel="icon" href="/logo.png" type="image/png">
    <style>
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-body);
            padding: 20px;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: var(--bg-card);
            border-radius: 24px;
            padding: 40px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-light);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo img {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            margin-bottom: 12px;
        }
        .login-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 8px;
            text-align: center;
        }
        .login-subtitle {
            font-size: 0.9rem;
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-card">
            <div class="login-logo">
                <img src="/logo.png" alt="Logo">
                <div class="login-title">Yönetici Paneli</div>
                <div class="login-subtitle">Temizci Burada sistem yönetimi için giriş yapın.</div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error" style="margin-bottom: 20px; padding: 12px; border-radius: 12px; background: rgba(239, 68, 68, 0.1); color: #ef4444; font-size: 0.85rem; border: 1px solid rgba(239, 68, 68, 0.2);">
                    ⚠️ <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <?= csrfField() ?>
                <div class="form-group mb-4">
                    <label class="form-label">E-Posta Adresi</label>
                    <input type="email" name="email" class="form-control" required placeholder="admin@temizciburada.com" style="height: 50px; border-radius: 12px;">
                </div>
                <div class="form-group mb-4">
                    <label class="form-label">Şifre</label>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••" style="height: 50px; border-radius: 12px;">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block" style="height: 50px; border-radius: 12px; font-weight: 800; font-size: 1rem; margin-top: 10px;">
                    Giriş Yap
                </button>
            </form>

            <div style="margin-top: 30px; text-align: center;">
                <a href="/" style="font-size: 0.85rem; color: var(--text-muted); text-decoration: none;">← Siteye Geri Dön</a>
            </div>
        </div>
    </div>

    <script src="../assets/js/theme.js"></script>
</body>
</html>
