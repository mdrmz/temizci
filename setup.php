<?php
// ============================================================
// Temizci Burada — Geliştirici Kurulum Sayfası
// Kullanım: http://localhost/Temizlik_Burda/setup.php
// Production'da bu dosyayı SİLİN!
// ============================================================

require_once 'includes/config.php';
require_once 'includes/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $db = getDB();

    if ($_POST['action'] === 'create_users') {
        try {
            // login_attempts tablosu – yoksa oluştur
            $db->exec("CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                email VARCHAR(150) NOT NULL,
                attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ip_email (ip_address, email),
                INDEX idx_attempted_at (attempted_at)
            )");

            // api_tokens tablosu – yoksa oluştur
            $db->exec("CREATE TABLE IF NOT EXISTS api_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(255) UNIQUE NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )");

            $pass = password_hash('Test123!', PASSWORD_DEFAULT);

            // Ev sahibi
            $s = $db->prepare("SELECT id FROM users WHERE email = ?");
            $s->execute(['evsahibi@test.com']);
            if (!$s->fetch()) {
                $db->prepare("INSERT INTO users (name, email, phone, password, role, city, bio, is_active)
                    VALUES (?,?,?,?,?,?,?,1)")
                    ->execute(['Ayşe Kara', 'evsahibi@test.com', '05001234567', $pass, 'homeowner', 'İstanbul', 'Test ev sahibi hesabı.']);
            }

            // Temizlikçi
            $s->execute(['temizlikci@test.com']);
            if (!$s->fetch()) {
                $db->prepare("INSERT INTO users (name, email, phone, password, role, city, bio, rating, review_count, is_active)
                    VALUES (?,?,?,?,?,?,?,4.80,12,1)")
                    ->execute(['Mehmet Yılmaz', 'temizlikci@test.com', '05359876543', $pass, 'worker', 'İstanbul', 'Test temizlikçi hesabı. 10 yıl deneyim.']);
            }

            // Ev sahibinin evi
            $uid = $db->query("SELECT id FROM users WHERE email='evsahibi@test.com'")->fetchColumn();
            $hid = $db->query("SELECT id FROM homes WHERE user_id=$uid LIMIT 1")->fetchColumn();
            if (!$hid) {
                $db->prepare("INSERT INTO homes (user_id,title,address,district,city,room_config,floor,has_elevator,bathroom_count,sqm,is_active)
                    VALUES (?,?,?,?,?,?,?,?,?,?,1)")
                    ->execute([$uid, '3+1 Dairem', 'Moda Cad. No:15', 'Kadıköy', 'İstanbul', '3+1', 4, 1, 1, 110]);
                $hid = $db->lastInsertId();
            }

            // Test ilan
            $lid = $db->query("SELECT id FROM listings WHERE user_id=$uid LIMIT 1")->fetchColumn();
            if (!$lid) {
                $db->prepare("INSERT INTO listings (user_id,home_id,category_id,title,description,preferred_date,preferred_time,budget,status)
                    VALUES (?,?,?,?,?,DATE_ADD(CURDATE(),INTERVAL 3 DAY),?,?,?)")
                    ->execute([$uid, $hid, 1, 'Bahar Temizliği - 3+1', 'Kadıköyde 110m2 daire.', null, 'sabah', 350, 'open']);
            }

            $message = '✅ Test kullanıcıları ve veriler oluşturuldu!';
        } catch (Exception $e) {
            $error = '❌ Hata: ' . $e->getMessage();
        }
    }

    if ($_POST['action'] === 'test_api') {
        $email = $_POST['email'] ?? '';
        $pass = $_POST['pass'] ?? '';
        $db2 = getDB();
        $stmt = $db2->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $valid = password_verify($pass, $user['password']);
            $message = $valid
                ? "✅ Kullanıcı bulundu ve şifre DOĞRU. Rol: {$user['role']}"
                : "❌ Kullanıcı bulundu ama şifre YANLIŞ.";
        } else {
            $error = "❌ {$email} adresiyle kayıtlı kullanıcı bulunamadı.";
        }
    }
}

$db2 = getDB();
$users = $db2->query("SELECT id, name, email, role, city, is_active FROM users ORDER BY id DESC LIMIT 10")->fetchAll();
$apUrl = 'http://10.0.2.2/Temizlik_Burda/api/auth/login.php';
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Setup — Temizci Burada</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        body {
            font-family: Inter, sans-serif;
            background: #f5f5ff;
            padding: 32px;
            color: #1a1a2e
        }

        .card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .08)
        }

        h1 {
            color: #6C63FF;
            margin-bottom: 4px
        }

        h2 {
            font-size: 1.1rem;
            margin-bottom: 14px;
            color: #374151
        }

        .btn {
            background: #6C63FF;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: .9rem;
            font-weight: 600
        }

        .btn-green {
            background: #00C9A7
        }

        .success {
            background: #dcfce7;
            color: #166534;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-weight: 600
        }

        .err {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: .85rem
        }

        th,
        td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb
        }

        th {
            background: #f9f9ff;
            font-weight: 700
        }

        input {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 8px 12px;
            width: 100%;
            margin-bottom: 10px
        }

        .warn {
            background: #fef3c7;
            color: #92400e;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: .85rem;
            margin-bottom: 16px
        }
    </style>

    <!-- SEO & Favicon -->
    <link rel="icon" href="/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="/logo.png">
    <meta property="og:image" content="https://www.temizciburada.com/logo.png">
</head>

<body>
    <h1>🧹 Temizci Burada — Geliştirici Setup</h1>
    <p style="color:#6b7280;margin:8px 0 24px">Bu sayfa sadece geliştirme ortamı içindir. Production'da silin.</p>

    <div class="warn">⚠️ Flutter URL: <code><?= $apUrl ?></code> — Emülatörde <code>10.0.2.2</code> = bilgisayarın
        localhost'u</div>

    <?php if ($message): ?>
        <div class="success">
            <?= $message ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="err">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>1. Test Kullanıcıları Oluştur</h2>
        <form method="POST">
            <input type="hidden" name="action" value="create_users">
            <p style="font-size:.9rem;color:#374151;margin-bottom:14px">
                Şifre: <strong>Test123!</strong><br>
                Ev sahibi: <strong>evsahibi@test.com</strong><br>
                Temizlikçi: <strong>temizlikci@test.com</strong>
            </p>
            <button type="submit" class="btn">Kullanıcıları Oluştur / Güncelle</button>
        </form>
    </div>

    <div class="card">
        <h2>2. Giriş Test Et (API debug)</h2>
        <form method="POST">
            <input type="hidden" name="action" value="test_api">
            <input type="email" name="email" placeholder="E-posta" value="evsahibi@test.com">
            <input type="text" name="pass" placeholder="Şifre" value="Test123!">
            <button type="submit" class="btn btn-green">Test Et</button>
        </form>
    </div>

    <div class="card">
        <h2>3. Mevcut Kullanıcılar</h2>
        <?php if (empty($users)): ?>
            <p style="color:#6b7280">Henüz kullanıcı yok.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Ad</th>
                        <th>E-posta</th>
                        <th>Rol</th>
                        <th>Şehir</th>
                        <th>Aktif</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <?= $u['id'] ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($u['name']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($u['email']) ?>
                            </td>
                            <td>
                                <?= $u['role'] ?>
                            </td>
                            <td>
                                <?= $u['city'] ?>
                            </td>
                            <td>
                                <?= $u['is_active'] ? '✅' : '❌' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>

</html>