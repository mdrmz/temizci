<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/badges.php';
requireLogin();

$user = currentUser();
$db = getDB();
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Güvenlik hatası. Lütfen sayfayı yenile.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telegramId = trim($_POST['telegram_chat_id'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';

        if (empty($name))
            $errors[] = 'Ad Soyad zorunludur.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors[] = 'Geçerli e-posta girin.';

        if (empty($errors)) {
            // E-posta benzersiz mi kontrol et
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user['id']]);
            if ($stmt->fetch()) {
                $errors[] = 'Bu e-posta adresi zaten kullanılıyor.';
            } else {
                $updatePass = false;
                if (!empty($currentPassword) && !empty($newPassword)) {
                    if (password_verify($currentPassword, $user['password'])) {
                        $updatePass = true;
                    } else {
                        $errors[] = 'Mevcut şifrenizi yanlış girdiniz.';
                    }
                }

                if (empty($errors)) {
                    if ($updatePass) {
                        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
                        $stmt->execute([$name, $email, $hash, $user['id']]);
                        $success = 'Profiliniz ve şifreniz güncellendi.';
                    } else {
                        $stmt = $db->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                        $stmt->execute([$name, $email, $user['id']]);
                        $success = 'Profiliniz başarıyla güncellendi.';
                    }
                    // Telegram chat ID kaydet
                    try {
                        $db->prepare("UPDATE users SET telegram_chat_id = ? WHERE id = ?")->execute([$telegramId ?: null, $user['id']]);
                    } catch (Exception $e) {}
                    
                    // Rozetleri kontrol et
                    checkAndAwardBadges($user['id']);
                    
                    $user = currentUser(); // refresh session data visually
                }
            }
        }
    }
}

// TAKVİM KAYDETME
if (isset($_POST['save_calendar']) && $user['role'] === 'worker') {
    if (!verifyCsrf()) {
        $errors[] = 'Güvenlik hatası (Takvim).';
    } else {
        $dates = $_POST['available_dates'] ?? [];
        $slots = $_POST['time_slots'] ?? [];
        
        // Mevcut takvimi temizle (İleriye dönük basit bir yönetim)
        $db->prepare("DELETE FROM availability WHERE user_id = ?")->execute([$user['id']]);
        
        // Yenilerini ekle
        if (!empty($dates)) {
            $stmt = $db->prepare("INSERT INTO availability (user_id, available_date, time_slot) VALUES (?, ?, ?)");
            foreach ($dates as $i => $date) {
                if (!empty($date)) {
                    $slot = $slots[$i] ?? 'tum_gun';
                    try {
                        $stmt->execute([$user['id'], $date, $slot]);
                    } catch(Exception $e) {}
                }
            }
        }
        $success = 'Müsaitlik takviminiz güncellendi.';
    }
}

$initials = strtoupper(substr($user['name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim — Temizci Burada</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="assets/css/style.css?v=4.0">
    <link rel="stylesheet" href="assets/css/dark-mode.css">

    <!-- SEO & Favicon -->
    <link rel="icon" href="/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="/logo.png">
    <meta property="og:image" content="https://www.temizciburada.com/logo.png">
</head>

<body>
    <div class="app-layout">
        <!-- ======== SIDEBAR ======== -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- ======== ANA İÇERİK ======== -->
        <div class="main-content">
            <!-- Header -->
            <div class="app-header">
                <div style="display:flex;align-items:center;gap:14px;">
                    <button class="hamburger" id="hamburger" aria-label="Menü">
                        <span></span><span></span><span></span>
                    </button>
                    <div class="app-header-title">Profilim</div>
                </div>
                <div class="header-actions">
                    <div class="avatar avatar-sm" style="background:var(--gradient);">
                        <?= $initials ?>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="page-content">
                <div class="page-title">Profil Bilgilerim</div>
                <div class="page-subtitle">Hesap detaylarınızı güncelleyin</div>

                <div class="card mt-4" style="max-width: 600px;">
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="flash flash-error">
                                <?= e(implode(' ', $errors)) ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="flash"
                                style="background: #ecfdf5; color: #047857; border-color: #a7f3d0; border-radius: 12px; padding: 16px; margin-bottom: 24px;">
                                ✅ <?= e($success) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <?= csrfField() ?>

                            <div class="form-group">
                                <label class="form-label" for="name">Ad Soyad</label>
                                <input type="text" id="name" name="name" class="form-control"
                                    value="<?= e($user['name']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="email">E-posta Adresi</label>
                                <input type="email" id="email" name="email" class="form-control"
                                    value="<?= e($user['email']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="telegram_chat_id">🤖 Telegram Chat ID</label>
                                <input type="text" id="telegram_chat_id" name="telegram_chat_id" class="form-control"
                                    value="<?= e($user['telegram_chat_id'] ?? '') ?>" placeholder="Örn: 123456789">
                                <div class="form-hint">Telegram üzerinden bildirim almak için Chat ID'nizi girin. <a href="https://t.me/userinfobot" target="_blank" style="color:var(--primary);">@userinfobot</a>'tan öğrenebilirsiniz.</div>
                            </div>

                            <hr style="border:0; border-top:1px solid var(--border-light); margin: 32px 0;">

                            <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 6px;">Şifre Değiştir</h3>
                            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">Şifrenizi
                                değiştirmek istemiyorsanız boş bırakın.</p>

                            <div class="form-group">
                                <label class="form-label" for="current_password">Mevcut Şifre</label>
                                <input type="password" id="current_password" name="current_password"
                                    class="form-control" placeholder="••••••••">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="new_password">Yeni Şifre</label>
                                <input type="password" id="new_password" name="new_password" class="form-control"
                                    placeholder="••••••••">
                            </div>

                            <button type="submit" class="btn btn-primary mt-3">Değişiklikleri Kaydet</button>
                        </form>
                    </div>
                </div>

                <!-- ROZETLER -->
                <?php 
                $userBadges = getUserBadges($user['id']);
                // Referans kodu oluştur (yoksa)
                $refCode = $user['referral_code'] ?? null;
                if (!$refCode) {
                    try {
                        $refCode = strtoupper(substr(md5($user['id'] . $user['email']), 0, 8));
                        $db->prepare("UPDATE users SET referral_code = ? WHERE id = ?")->execute([$refCode, $user['id']]);
                    } catch (Exception $e) { $refCode = null; }
                }
                ?>
                
                <?php if (!empty($userBadges)): ?>
                <div class="card mt-4" style="max-width: 600px;">
                    <div class="card-header">
                        <div class="card-title">🏆 Rozetlerim</div>
                    </div>
                    <div class="card-body">
                        <div style="display:flex;flex-wrap:wrap;gap:8px;">
                            <?php foreach ($userBadges as $b): ?>
                                <?= badgeTag($b['badge_icon'], $b['badge_name']) ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- REFERANS KODU -->
                <?php if ($refCode): ?>
                <div class="card mt-4" style="max-width: 600px;">
                    <div class="card-header">
                        <div class="card-title">🎁 Davet Kodu</div>
                    </div>
                    <div class="card-body">
                        <p style="color:var(--text-muted);font-size:0.88rem;margin-bottom:16px;">Arkadaşlarınızı davet edin! Aşağıdaki kodu veya linki paylaşın:</p>
                        <div style="display:flex;gap:10px;align-items:center;">
                            <div style="flex:1;background:var(--bg);border:2px dashed var(--border);border-radius:12px;padding:14px 18px;font-family:monospace;font-size:1.1rem;font-weight:700;letter-spacing:2px;text-align:center;">
                                <?= $refCode ?>
                            </div>
                            <button onclick="copyRefLink()" class="btn btn-primary" style="white-space:nowrap;">
                                <span id="refCopyText">📋 Kopyala</span>
                            </button>
                        </div>
                        <div style="font-size:0.78rem;color:var(--text-muted);margin-top:10px;">
                            Davet linki: <code id="refLink"><?= APP_URL ?>/register?ref=<?= $refCode ?></code>
                        </div>
                    </div>
                </div>
                <script>
                function copyRefLink() {
                    navigator.clipboard.writeText(document.getElementById('refLink').textContent).then(() => {
                        document.getElementById('refCopyText').textContent = '✅ Kopyalandı!';
                        setTimeout(() => document.getElementById('refCopyText').textContent = '📋 Kopyala', 2000);
                    });
                }
                </script>
                <?php endif; ?>

                <!-- TAKVİM MODÜLÜ -->
                <?php if ($user['role'] === 'worker'): ?>
                <?php
                    // Mevcut müsaitlikleri çek
                    $availStmt = $db->prepare("SELECT * FROM availability WHERE user_id = ? ORDER BY available_date ASC");
                    $availStmt->execute([$user['id']]);
                    $availabilities = $availStmt->fetchAll();
                ?>
                <div class="card mt-4" style="max-width: 600px;">
                    <div class="card-header">
                        <div class="card-title">📅 Çalışma Takvimim</div>
                    </div>
                    <div class="card-body">
                        <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:15px;">Müsait olduğunuz günleri ve saat dilimlerini buradan ekleyebilirsiniz. Ev sahipleri ilan oluştururken size özel teklif sunabilir.</p>
                        
                        <form method="POST" action="">
                            <?= csrfField() ?>
                            <input type="hidden" name="save_calendar" value="1">
                            
                            <div id="calendar-rows">
                                <?php if(empty($availabilities)): ?>
                                    <div class="calendar-row" style="display:flex;gap:10px;margin-bottom:10px;align-items:center;">
                                        <input type="date" name="available_dates[]" class="form-control" required min="<?= date('Y-m-d') ?>">
                                        <select name="time_slots[]" class="form-control">
                                            <option value="tum_gun">Tüm Gün</option>
                                            <option value="sabah">Sabah</option>
                                            <option value="ogle">Öğle</option>
                                            <option value="aksam">Akşam</option>
                                        </select>
                                        <button type="button" class="btn btn-outline btn-sm" onclick="this.parentElement.remove()" style="padding:4px 8px;border-color:#e11d48;color:#e11d48;">X</button>
                                    </div>
                                <?php else: ?>
                                    <?php foreach($availabilities as $a): ?>
                                        <div class="calendar-row" style="display:flex;gap:10px;margin-bottom:10px;align-items:center;">
                                            <input type="date" name="available_dates[]" class="form-control" value="<?= $a['available_date'] ?>" required min="<?= date('Y-m-d') ?>">
                                            <select name="time_slots[]" class="form-control">
                                                <option value="tum_gun" <?= $a['time_slot'] == 'tum_gun' ? 'selected' : '' ?>>Tüm Gün</option>
                                                <option value="sabah" <?= $a['time_slot'] == 'sabah' ? 'selected' : '' ?>>Sabah</option>
                                                <option value="ogle" <?= $a['time_slot'] == 'ogle' ? 'selected' : '' ?>>Öğle</option>
                                                <option value="aksam" <?= $a['time_slot'] == 'aksam' ? 'selected' : '' ?>>Akşam</option>
                                            </select>
                                            <button type="button" class="btn btn-outline btn-sm" onclick="this.parentElement.remove()" style="padding:4px 8px;border-color:#e11d48;color:#e11d48;">X</button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <button type="button" class="btn btn-outline btn-sm mb-3" onclick="addCalendarRow()">+ Yeni Gün Ekle</button>
                            <br>
                            <button type="submit" class="btn btn-primary">Takvimi Kaydet</button>
                        </form>
                    </div>
                </div>
                <script>
                    function addCalendarRow() {
                        const row = document.createElement('div');
                        row.className = 'calendar-row';
                        row.style.cssText = 'display:flex;gap:10px;margin-bottom:10px;align-items:center;';
                        row.innerHTML = `
                            <input type="date" name="available_dates[]" class="form-control" required min="<?= date('Y-m-d') ?>">
                            <select name="time_slots[]" class="form-control">
                                <option value="tum_gun">Tüm Gün</option>
                                <option value="sabah">Sabah</option>
                                <option value="ogle">Öğle</option>
                                <option value="aksam">Akşam</option>
                            </select>
                            <button type="button" class="btn btn-outline btn-sm" onclick="this.parentElement.remove()" style="padding:4px 8px;border-color:#e11d48;color:#e11d48;">X</button>
                        `;
                        document.getElementById('calendar-rows').appendChild(row);
                    }
                </script>
                <?php endif; ?>

            </div>
        </div>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="assets/js/app.js?v=4.0"></script>
    <script src="assets/js/theme.js"></script>
</body>

</html>