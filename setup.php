<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

$message = '';
$error = '';

function ensureCoreTables(PDO $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            email VARCHAR(150) NOT NULL,
            attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip_email (ip_address, email),
            INDEX idx_attempted_at (attempted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS api_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(255) UNIQUE NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            listing_id INT DEFAULT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_sender (sender_id),
            INDEX idx_receiver (receiver_id),
            INDEX idx_listing (listing_id),
            CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_messages_receiver FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_messages_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS availability (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            available_date DATE NOT NULL,
            time_slot ENUM('sabah','ogle','aksam','tum_gun') DEFAULT 'tum_gun',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY user_date_slot (user_id, available_date, time_slot),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS favorites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            listing_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_fav (user_id, listing_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    try {
        $db->exec("ALTER TABLE users MODIFY COLUMN role ENUM('homeowner','worker','admin') DEFAULT 'homeowner'");
    } catch (Exception $e) {
    }
    try {
        $db->exec("ALTER TABLE users ADD COLUMN session_version INT NOT NULL DEFAULT 0");
    } catch (Exception $e) {
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $db = getDB();
        ensureCoreTables($db);
        $action = $_POST['action'];

        if ($action === 'create_users') {
            $pass = password_hash('Test123!', PASSWORD_DEFAULT);

            $s = $db->prepare("SELECT id FROM users WHERE email = ?");
            $s->execute(['evsahibi@test.com']);
            if (!$s->fetch()) {
                $db->prepare("INSERT INTO users (name, email, phone, password, role, city, bio, is_active)
                    VALUES (?,?,?,?,?,?,?,1)")
                    ->execute(['Ayse Kara', 'evsahibi@test.com', '05001234567', $pass, 'homeowner', 'Istanbul', 'Test ev sahibi hesabi.']);
            }

            $s->execute(['temizlikci@test.com']);
            if (!$s->fetch()) {
                $db->prepare("INSERT INTO users (name, email, phone, password, role, city, bio, rating, review_count, is_active)
                    VALUES (?,?,?,?,?,?,?,4.80,12,1)")
                    ->execute(['Mehmet Yilmaz', 'temizlikci@test.com', '05359876543', $pass, 'worker', 'Istanbul', 'Test temizlikci hesabi.']);
            }

            $uid = (int) $db->query("SELECT id FROM users WHERE email='evsahibi@test.com'")->fetchColumn();
            $hid = $db->query("SELECT id FROM homes WHERE user_id=$uid LIMIT 1")->fetchColumn();
            if (!$hid) {
                $db->prepare("INSERT INTO homes (user_id,title,address,district,city,room_config,floor,has_elevator,bathroom_count,sqm,is_active)
                    VALUES (?,?,?,?,?,?,?,?,?,?,1)")
                    ->execute([$uid, '3+1 Dairem', 'Moda Cad. No:15', 'Kadikoy', 'Istanbul', '3+1', 4, 1, 1, 110]);
                $hid = $db->lastInsertId();
            }

            $lid = $db->query("SELECT id FROM listings WHERE user_id=$uid LIMIT 1")->fetchColumn();
            if (!$lid) {
                $db->prepare("INSERT INTO listings (user_id,home_id,category_id,title,description,preferred_date,preferred_time,budget,status)
                    VALUES (?,?,?,?,?,DATE_ADD(CURDATE(),INTERVAL 3 DAY),?,?,?)")
                    ->execute([$uid, $hid, 1, 'Bahar Temizligi - 3+1', 'Kadikoyde 110m2 daire.', 'sabah', 350, 'open']);
            }

            $message = 'Test kullanicilari hazirlandi.';
        }

        if ($action === 'create_admin') {
            $adminName = trim($_POST['admin_name'] ?? 'Site Admin');
            $adminEmail = trim($_POST['admin_email'] ?? '');
            $adminPass = $_POST['admin_pass'] ?? '';

            if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Gecerli bir admin e-postasi girin.');
            }
            if (strlen($adminPass) < 6) {
                throw new Exception('Admin sifresi en az 6 karakter olmali.');
            }

            $hash = password_hash($adminPass, PASSWORD_DEFAULT);
            $chk = $db->prepare("SELECT id FROM users WHERE email = ?");
            $chk->execute([$adminEmail]);
            $existingId = $chk->fetchColumn();

            if ($existingId) {
                $db->prepare("UPDATE users SET name=?, password=?, role='admin', is_active=1 WHERE id=?")
                    ->execute([$adminName, $hash, $existingId]);
                $message = 'Mevcut kullanici admin olarak guncellendi.';
            } else {
                $db->prepare("INSERT INTO users (name, email, phone, password, role, city, bio, is_active)
                    VALUES (?,?,?,?,?,?,?,1)")
                    ->execute([$adminName, $adminEmail, '', $hash, 'admin', 'Istanbul', 'Sistem yoneticisi']);
                $message = 'Yeni admin kullanici olusturuldu.';
            }
        }

        if ($action === 'test_api') {
            $email = $_POST['email'] ?? '';
            $pass = $_POST['pass'] ?? '';
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $u = $stmt->fetch();
            if ($u) {
                $valid = password_verify($pass, $u['password']);
                $message = $valid
                    ? "Kullanici bulundu, sifre dogru. Rol: {$u['role']}"
                    : "Kullanici bulundu ama sifre yanlis.";
            } else {
                $error = 'Bu e-posta ile kayitli kullanici bulunamadi.';
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$db2 = getDB();
ensureCoreTables($db2);
$users = $db2->query("SELECT id, name, email, role, city, is_active FROM users ORDER BY id DESC LIMIT 20")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Temizci Burada</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f5f6fb; padding: 24px; color: #1f2937; }
        .card { background: #fff; border-radius: 12px; padding: 18px; margin-bottom: 16px; box-shadow: 0 6px 18px rgba(0,0,0,.08); }
        h1 { margin-bottom: 6px; }
        h2 { margin-bottom: 12px; font-size: 1.05rem; }
        .ok { background: #e8f9ee; color: #135c2b; padding: 10px 12px; border-radius: 8px; margin-bottom: 12px; }
        .err { background: #feecec; color: #8c1d1d; padding: 10px 12px; border-radius: 8px; margin-bottom: 12px; }
        input { width: 100%; padding: 9px 10px; border: 1px solid #d5dae3; border-radius: 8px; margin-bottom: 10px; }
        .btn { border: none; padding: 10px 14px; border-radius: 8px; background: #1f4b66; color: #fff; font-weight: 700; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; font-size: .9rem; }
        th, td { border-bottom: 1px solid #eceff4; padding: 8px 10px; text-align: left; }
        th { background: #f7f9fc; }
    </style>
</head>
<body>
    <h1>Temizci Burada Setup</h1>
    <p style="margin:8px 0 18px;color:#6b7280;">Bu sayfa sadece gelistirme ortami icindir.</p>

    <?php if ($message): ?><div class="ok"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card">
        <h2>1) Test Kullanicilarini Olustur</h2>
        <form method="POST">
            <input type="hidden" name="action" value="create_users">
            <p style="margin-bottom:10px;font-size:.9rem;color:#6b7280;">
                Sifre: <strong>Test123!</strong><br>
                evsahibi@test.com / temizlikci@test.com
            </p>
            <button class="btn" type="submit">Olustur / Guncelle</button>
        </form>
    </div>

    <div class="card">
        <h2>2) Admin Hesabi Olustur</h2>
        <form method="POST">
            <input type="hidden" name="action" value="create_admin">
            <input type="text" name="admin_name" placeholder="Admin ad soyad" value="Site Admin">
            <input type="email" name="admin_email" placeholder="admin@temizciburada.com" value="admin@temizciburada.com">
            <input type="text" name="admin_pass" placeholder="Admin sifresi (min 6)" value="Admin123!">
            <button class="btn" type="submit">Admin Hazirla</button>
        </form>
    </div>

    <div class="card">
        <h2>3) Login Test</h2>
        <form method="POST">
            <input type="hidden" name="action" value="test_api">
            <input type="email" name="email" placeholder="E-posta" value="admin@temizciburada.com">
            <input type="text" name="pass" placeholder="Sifre" value="Admin123!">
            <button class="btn" type="submit">Test Et</button>
        </form>
    </div>

    <div class="card">
        <h2>4) Son Kullanicilar</h2>
        <table>
            <thead>
                <tr><th>#</th><th>Ad</th><th>Email</th><th>Rol</th><th>Sehir</th><th>Aktif</th></tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= (int) $u['id'] ?></td>
                        <td><?= htmlspecialchars($u['name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['role']) ?></td>
                        <td><?= htmlspecialchars((string) $u['city']) ?></td>
                        <td><?= (int) $u['is_active'] === 1 ? 'Evet' : 'Hayir' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
