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

try {
    $userCols = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('notif_in_app', $userCols, true)) {
        $db->exec("ALTER TABLE users ADD COLUMN notif_in_app TINYINT(1) NOT NULL DEFAULT 1 AFTER referral_code");
    }
    if (!in_array('notif_email', $userCols, true)) {
        $db->exec("ALTER TABLE users ADD COLUMN notif_email TINYINT(1) NOT NULL DEFAULT 1 AFTER notif_in_app");
    }
    if (!in_array('notif_telegram', $userCols, true)) {
        $db->exec("ALTER TABLE users ADD COLUMN notif_telegram TINYINT(1) NOT NULL DEFAULT 1 AFTER notif_email");
    }
} catch (Exception $e) {}

try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS worker_service_packages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            worker_id INT NOT NULL,
            title VARCHAR(120) NOT NULL,
            description VARCHAR(400) NULL,
            base_price DECIMAL(10,2) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_worker_pkg_worker (worker_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'logout_other_sessions') {
        if (!verifyCsrf()) {
            $errors[] = 'GÃ¼venlik hatasÄ±. LÃ¼tfen sayfayÄ± yenileyin.';
        } else {
            $db->prepare("UPDATE users SET session_version = COALESCE(session_version, 0) + 1 WHERE id = ?")
                ->execute([$user['id']]);

            $stmt = $db->prepare("SELECT session_version FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $_SESSION['session_version'] = (int) $stmt->fetchColumn();

            $user = currentUser(true);
            $success = 'DiÄŸer cihazlardaki oturumlar kapatÄ±ldÄ±.';
        }
    } elseif ($action === 'save_profile' || ($action === '' && !isset($_POST['save_calendar']))) {
        if (!verifyCsrf()) {
            $errors[] = 'GÃ¼venlik hatasÄ±. LÃ¼tfen sayfayÄ± yenileyin.';
        } else {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $telegramId = trim($_POST['telegram_chat_id'] ?? '');
            $notifInApp = isset($_POST['notif_in_app']) ? 1 : 0;
            $notifEmail = isset($_POST['notif_email']) ? 1 : 0;
            $notifTelegram = isset($_POST['notif_telegram']) ? 1 : 0;
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $newAvatar = $user['avatar'] ?? null;
            $avatarChanged = false;

            if ($name === '') {
                $errors[] = 'Ad Soyad zorunludur.';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'GeÃ§erli e-posta girin.';
            }

            if (isset($_FILES['avatar']) && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $uploaded = uploadFile($_FILES['avatar'], 'avatars');
                if ($uploaded === false) {
                    $errors[] = 'Profil fotoÄŸrafÄ± yÃ¼klenemedi. JPG/PNG/WEBP/GIF ve en fazla 5MB olmalÄ±.';
                } else {
                    $newAvatar = $uploaded;
                    $avatarChanged = true;
                }
            }

            $hasCurrent = $currentPassword !== '';
            $hasNew = $newPassword !== '';
            if ($hasCurrent xor $hasNew) {
                $errors[] = 'Åifre deÄŸiÅŸtirmek iÃ§in mevcut ÅŸifre ve yeni ÅŸifre birlikte girilmelidir.';
            }

            if (empty($errors)) {
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $user['id']]);
                if ($stmt->fetch()) {
                    $errors[] = 'Bu e-posta adresi zaten kullanÄ±lÄ±yor.';
                } else {
                    $updatePass = false;
                    if ($hasCurrent && $hasNew) {
                        if (strlen($newPassword) < 8) {
                            $errors[] = 'Yeni ÅŸifre en az 8 karakter olmalÄ±dÄ±r.';
                        } elseif (password_verify($currentPassword, $user['password'])) {
                            $updatePass = true;
                        } else {
                            $errors[] = 'Mevcut ÅŸifrenizi yanlÄ±ÅŸ girdiniz.';
                        }
                    }

                    if (empty($errors)) {
                        if ($updatePass) {
                            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, avatar = ?, password = ?, notif_in_app = ?, notif_email = ?, notif_telegram = ? WHERE id = ?");
                            $stmt->execute([$name, $email, $newAvatar, $hash, $notifInApp, $notifEmail, $notifTelegram, $user['id']]);
                            $success = 'Profiliniz ve ÅŸifreniz gÃ¼ncellendi.';
                        } else {
                            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, avatar = ?, notif_in_app = ?, notif_email = ?, notif_telegram = ? WHERE id = ?");
                            $stmt->execute([$name, $email, $newAvatar, $notifInApp, $notifEmail, $notifTelegram, $user['id']]);
                            $success = 'Profiliniz gÃ¼ncellendi.';
                        }

                        try {
                            $db->prepare("UPDATE users SET telegram_chat_id = ? WHERE id = ?")
                                ->execute([$telegramId ?: null, $user['id']]);
                        } catch (Exception $e) {
                        }

                        if ($avatarChanged && !empty($user['avatar']) && $user['avatar'] !== $newAvatar) {
                            $oldPath = UPLOAD_PATH . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $user['avatar']);
                            if (is_file($oldPath)) {
                                @unlink($oldPath);
                            }
                        }

                        checkAndAwardBadges($user['id']);
                        $user = currentUser(true);
                    }
                }
            }
        }
    } elseif (isset($_POST['save_calendar']) && $user['role'] === 'worker') {
        if (!verifyCsrf()) {
            $errors[] = 'GÃ¼venlik hatasÄ± (Takvim).';
        } else {
            $dates = $_POST['available_dates'] ?? [];
            $slots = $_POST['time_slots'] ?? [];

            $db->prepare("DELETE FROM availability WHERE user_id = ?")->execute([$user['id']]);
            if (!empty($dates)) {
                $stmt = $db->prepare("INSERT INTO availability (user_id, available_date, time_slot) VALUES (?, ?, ?)");
                foreach ($dates as $i => $date) {
                    if (!empty($date)) {
                        $slot = $slots[$i] ?? 'tum_gun';
                        try {
                            $stmt->execute([$user['id'], $date, $slot]);
                        } catch (Exception $e) {
                        }
                    }
                }
            }
            $success = 'MÃ¼saitlik takviminiz gÃ¼ncellendi.';
            $user = currentUser(true);
        }
    } elseif ($action === 'add_package' && $user['role'] === 'worker') {
        if (!verifyCsrf()) {
            $errors[] = 'GÃ¼venlik hatasÄ± (Paket).';
        } else {
            $pkgTitle = trim($_POST['pkg_title'] ?? '');
            $pkgDesc = trim($_POST['pkg_desc'] ?? '');
            $pkgPrice = (float) ($_POST['pkg_price'] ?? 0);
            if ($pkgTitle === '' || $pkgPrice <= 0) {
                $errors[] = 'Paket basligi ve gecerli fiyat zorunlu.';
            } else {
                $db->prepare("INSERT INTO worker_service_packages (worker_id, title, description, base_price) VALUES (?, ?, ?, ?)")
                    ->execute([$user['id'], $pkgTitle, $pkgDesc !== '' ? $pkgDesc : null, $pkgPrice]);
                $success = 'Hizmet paketi eklendi.';
            }
        }
    } elseif ($action === 'delete_package' && $user['role'] === 'worker') {
        if (!verifyCsrf()) {
            $errors[] = 'GÃ¼venlik hatasÄ± (Paket Sil).';
        } else {
            $pkgId = (int) ($_POST['pkg_id'] ?? 0);
            if ($pkgId > 0) {
                $db->prepare("DELETE FROM worker_service_packages WHERE id = ? AND worker_id = ?")
                    ->execute([$pkgId, $user['id']]);
                $success = 'Hizmet paketi silindi.';
            }
        }
    }
}

$initials = strtoupper(substr($user['name'], 0, 1));
$avatarUrl = !empty($user['avatar']) ? (UPLOAD_URL . e($user['avatar'])) : '';
$trustChecks = [
    'avatar' => !empty($user['avatar']),
    'bio' => !empty(trim((string) ($user['bio'] ?? ''))),
    'city' => !empty(trim((string) ($user['city'] ?? ''))),
    'verified' => !empty($user['is_verified']),
    'reviews' => (int) ($user['review_count'] ?? 0) >= 3,
];
$trustScore = (int) round((array_sum(array_map(fn($v) => $v ? 1 : 0, $trustChecks)) / max(1, count($trustChecks))) * 100);
$trustLevel = $trustScore >= 80 ? 'Yuksek Guven' : ($trustScore >= 50 ? 'Orta Guven' : 'Gelisimde');

$userBadges = getUserBadges($user['id']);
$refCode = $user['referral_code'] ?? null;
if (!$refCode) {
    try {
        $refCode = strtoupper(substr(md5($user['id'] . $user['email']), 0, 8));
        $db->prepare("UPDATE users SET referral_code = ? WHERE id = ?")->execute([$refCode, $user['id']]);
    } catch (Exception $e) {
        $refCode = null;
    }
}

$availabilities = [];
if ($user['role'] === 'worker') {
    $availStmt = $db->prepare("SELECT * FROM availability WHERE user_id = ? ORDER BY available_date ASC");
    $availStmt->execute([$user['id']]);
    $availabilities = $availStmt->fetchAll();
}

$servicePackages = [];
if ($user['role'] === 'worker') {
    $pkgStmt = $db->prepare("SELECT * FROM worker_service_packages WHERE worker_id = ? ORDER BY created_at DESC");
    $pkgStmt->execute([$user['id']]);
    $servicePackages = $pkgStmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim - Temizci Burada</title>
    <link rel="stylesheet" href="assets/css/style.css?v=5.0">
    <link rel="stylesheet" href="assets/css/dark-mode.css">
    <link rel="icon" href="/logo.png" type="image/png">
</head>
<body>
    <div class="app-layout">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <?php $headerTitle = 'Profilim'; include 'includes/app-header.php'; ?>
            <div class="page-content">
                <div class="profile-shell">
                    <div class="page-title">Profil Bilgilerim</div>
                    <div class="page-subtitle">Hesap detaylarÄ±nÄ±zÄ± gÃ¼ncelleyin.</div>
                    <div class="card mt-4">
                        <div class="card-header"><div class="card-title">Guven Profili</div></div>
                        <div class="card-body">
                            <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
                                <div>
                                    <div style="font-size:1.35rem;font-weight:800;"><?= $trustScore ?>%</div>
                                    <div style="font-size:0.82rem;color:var(--text-muted);"><?= $trustLevel ?></div>
                                </div>
                                <div style="flex:1;min-width:220px;background:var(--bg);height:10px;border-radius:999px;overflow:hidden;">
                                    <div style="height:100%;width:<?= $trustScore ?>%;background:linear-gradient(90deg,#14b8a6,#0ea5e9);"></div>
                                </div>
                            </div>
                            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px;">
                                <span class="badge <?= $trustChecks['avatar'] ? 'badge-open' : 'badge-closed' ?>">Avatar</span>
                                <span class="badge <?= $trustChecks['bio'] ? 'badge-open' : 'badge-closed' ?>">Bio</span>
                                <span class="badge <?= $trustChecks['city'] ? 'badge-open' : 'badge-closed' ?>">Sehir</span>
                                <span class="badge <?= $trustChecks['verified'] ? 'badge-open' : 'badge-closed' ?>">Dogrulama</span>
                                <span class="badge <?= $trustChecks['reviews'] ? 'badge-open' : 'badge-closed' ?>">3+ Yorum</span>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="flash flash-error"><?= e(implode(' ', $errors)) ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="flash flash-success"><?= e($success) ?></div>
                    <?php endif; ?>

                    <div class="card mt-4">
                        <div class="card-header"><div class="card-title">Temel Bilgiler</div></div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="save_profile">

                                <div class="form-group" style="display:flex;align-items:center;gap:14px;">
                                    <div class="avatar avatar-xl">
                                        <?php if ($avatarUrl): ?>
                                            <img src="<?= $avatarUrl ?>" alt="Profil FotoÄŸrafÄ±" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                                        <?php else: ?>
                                            <?= e($initials) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div style="flex:1;">
                                        <label class="form-label" for="avatar">Profil FotoÄŸrafÄ±</label>
                                        <input type="file" id="avatar" name="avatar" class="form-control" accept="image/png,image/jpeg,image/webp,image/gif">
                                        <div class="form-hint">JPG, PNG, WEBP veya GIF (maksimum 5MB)</div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="name">Ad Soyad</label>
                                    <input type="text" id="name" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="email">E-posta Adresi</label>
                                    <input type="email" id="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="telegram_chat_id">Telegram Chat ID</label>
                                    <input type="text" id="telegram_chat_id" name="telegram_chat_id" class="form-control" value="<?= e($user['telegram_chat_id'] ?? '') ?>" placeholder="Orn: 123456789">
                                    <div class="form-hint">Bildirim almak iÃ§in Chat ID girin. Referans bot: <a href="https://t.me/userinfobot" target="_blank">@userinfobot</a></div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Bildirim Tercihleri</label>
                                    <label style="display:flex;align-items:center;gap:8px;margin:8px 0;">
                                        <input type="checkbox" name="notif_in_app" value="1" <?= (int) ($user['notif_in_app'] ?? 1) === 1 ? 'checked' : '' ?>>
                                        Uygulama ici bildirim
                                    </label>
                                    <label style="display:flex;align-items:center;gap:8px;margin:8px 0;">
                                        <input type="checkbox" name="notif_email" value="1" <?= (int) ($user['notif_email'] ?? 1) === 1 ? 'checked' : '' ?>>
                                        E-posta bildirimi
                                    </label>
                                    <label style="display:flex;align-items:center;gap:8px;margin:8px 0;">
                                        <input type="checkbox" name="notif_telegram" value="1" <?= (int) ($user['notif_telegram'] ?? 1) === 1 ? 'checked' : '' ?>>
                                        Telegram bildirimi
                                    </label>
                                </div>

                                <div class="divider">Åifre GÃ¼ncelleme</div>
                                <p class="section-note mb-3">Åifrenizi deÄŸiÅŸtirmek istemiyorsanÄ±z aÅŸaÄŸÄ±daki alanlarÄ± boÅŸ bÄ±rakÄ±n.</p>
                                <div class="form-group">
                                    <label class="form-label" for="current_password">Mevcut Åifre</label>
                                    <input type="password" id="current_password" name="current_password" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="new_password">Yeni Åifre</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control">
                                </div>
                                <button type="submit" class="btn btn-primary mt-3">DeÄŸiÅŸiklikleri Kaydet</button>
                            </form>

                            <form method="POST" style="margin-top:12px;">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="logout_other_sessions">
                                <button type="submit" class="btn btn-outline">DiÄŸer cihazlardan Ã§Ä±kÄ±ÅŸ yap</button>
                            </form>
                        </div>
                    </div>

                    <?php if (!empty($userBadges)): ?>
                        <div class="card mt-4">
                            <div class="card-header"><div class="card-title">Rozetlerim</div></div>
                            <div class="card-body"><div style="display:flex;flex-wrap:wrap;gap:8px;"><?php foreach ($userBadges as $b): ?><?= badgeTag($b['badge_icon'], $b['badge_name']) ?><?php endforeach; ?></div></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($refCode): ?>
                        <div class="card mt-4">
                            <div class="card-header"><div class="card-title">Davet Kodu</div></div>
                            <div class="card-body">
                                <p class="section-note mb-3">ArkadaÅŸlarÄ±nÄ±zÄ± davet etmek iÃ§in bu kodu veya baÄŸlantÄ±yÄ± paylaÅŸÄ±n.</p>
                                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                                    <div style="flex:1;background:var(--bg);border:2px dashed var(--border);border-radius:12px;padding:14px 18px;font-family:monospace;font-size:1.1rem;font-weight:700;letter-spacing:2px;text-align:center;"><?= e($refCode) ?></div>
                                    <button onclick="copyRefLink()" class="btn btn-primary" type="button"><span id="refCopyText">Kopyala</span></button>
                                </div>
                                <div class="form-hint mt-2">Davet linki: <code id="refLink"><?= APP_URL ?>/register?ref=<?= e($refCode) ?></code></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($user['role'] === 'worker'): ?>
                        <div class="card mt-4">
                            <div class="card-header"><div class="card-title">Hizmet Paketlerim</div></div>
                            <div class="card-body">
                                <form method="POST" class="mb-3">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="add_package">
                                    <div class="grid-2" style="gap:10px;">
                                        <div class="form-group">
                                            <label class="form-label">Paket Basligi</label>
                                            <input type="text" name="pkg_title" class="form-control" placeholder="Orn: 2+1 Detayli Temizlik" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Baslangic Fiyati (TL)</label>
                                            <input type="number" name="pkg_price" class="form-control" min="1" step="1" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Aciklama</label>
                                        <textarea name="pkg_desc" class="form-control" rows="2" placeholder="Paketin icerigi..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">Paket Ekle</button>
                                </form>
                                <?php if (empty($servicePackages)): ?>
                                    <div style="font-size:0.85rem;color:var(--text-muted);">Henuz paket tanimlamadiniz.</div>
                                <?php else: ?>
                                    <?php foreach ($servicePackages as $pkg): ?>
                                        <div style="padding:10px;border:1px solid var(--border);border-radius:10px;margin-bottom:8px;">
                                            <div style="display:flex;justify-content:space-between;gap:10px;">
                                                <div>
                                                    <div style="font-weight:700;"><?= e($pkg['title']) ?></div>
                                                    <div style="font-size:0.82rem;color:var(--text-muted);"><?= e($pkg['description'] ?? '') ?></div>
                                                </div>
                                                <div style="text-align:right;">
                                                    <div style="font-weight:800;"><?= formatMoney((float) $pkg['base_price']) ?></div>
                                                    <form method="POST" style="margin-top:6px;">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="action" value="delete_package">
                                                        <input type="hidden" name="pkg_id" value="<?= (int) $pkg['id'] ?>">
                                                        <button class="btn btn-ghost btn-sm" type="submit">Sil</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card mt-4">
                            <div class="card-header"><div class="card-title">Ã‡alÄ±ÅŸma Takvimim</div></div>
                            <div class="card-body">
                                <p class="section-note mb-3">MÃ¼sait olduÄŸunuz gÃ¼nleri ve saat dilimlerini ekleyin.</p>
                                <form method="POST">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="save_calendar">
                                    <input type="hidden" name="save_calendar" value="1">
                                    <div id="calendar-rows">
                                        <?php if (empty($availabilities)): ?>
                                            <div class="calendar-row" style="display:flex;gap:10px;margin-bottom:10px;align-items:center;">
                                                <input type="date" name="available_dates[]" class="form-control" required min="<?= date('Y-m-d') ?>">
                                                <select name="time_slots[]" class="form-control">
                                                    <option value="tum_gun">TÃ¼m GÃ¼n</option>
                                                    <option value="sabah">Sabah</option>
                                                    <option value="ogle">Ã–ÄŸle</option>
                                                    <option value="aksam">AkÅŸam</option>
                                                </select>
                                                <button type="button" class="btn btn-outline btn-sm" onclick="this.parentElement.remove()">Sil</button>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($availabilities as $a): ?>
                                                <div class="calendar-row" style="display:flex;gap:10px;margin-bottom:10px;align-items:center;">
                                                    <input type="date" name="available_dates[]" class="form-control" value="<?= e($a['available_date']) ?>" required min="<?= date('Y-m-d') ?>">
                                                    <select name="time_slots[]" class="form-control">
                                                        <option value="tum_gun" <?= $a['time_slot'] === 'tum_gun' ? 'selected' : '' ?>>TÃ¼m GÃ¼n</option>
                                                        <option value="sabah" <?= $a['time_slot'] === 'sabah' ? 'selected' : '' ?>>Sabah</option>
                                                        <option value="ogle" <?= $a['time_slot'] === 'ogle' ? 'selected' : '' ?>>Ã–ÄŸle</option>
                                                        <option value="aksam" <?= $a['time_slot'] === 'aksam' ? 'selected' : '' ?>>AkÅŸam</option>
                                                    </select>
                                                    <button type="button" class="btn btn-outline btn-sm" onclick="this.parentElement.remove()">Sil</button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="btn btn-outline btn-sm mb-3" onclick="addCalendarRow()">Yeni GÃ¼n Ekle</button><br>
                                    <button type="submit" class="btn btn-primary">Takvimi Kaydet</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="assets/js/app.js?v=5.0"></script>
    <script src="assets/js/theme.js"></script>
    <script>
        function copyRefLink() {
            navigator.clipboard.writeText(document.getElementById('refLink').textContent).then(() => {
                const label = document.getElementById('refCopyText');
                label.textContent = 'KopyalandÄ±';
                setTimeout(() => { label.textContent = 'Kopyala'; }, 2000);
            });
        }

        function addCalendarRow() {
            const row = document.createElement('div');
            row.className = 'calendar-row';
            row.style.cssText = 'display:flex;gap:10px;margin-bottom:10px;align-items:center;';
            row.innerHTML = `
                <input type="date" name="available_dates[]" class="form-control" required min="<?= date('Y-m-d') ?>">
                <select name="time_slots[]" class="form-control">
                    <option value="tum_gun">TÃ¼m GÃ¼n</option>
                    <option value="sabah">Sabah</option>
                    <option value="ogle">Ã–ÄŸle</option>
                    <option value="aksam">AkÅŸam</option>
                </select>
                <button type="button" class="btn btn-outline btn-sm" onclick="this.parentElement.remove()">Sil</button>
            `;
            document.getElementById('calendar-rows').appendChild(row);
        }
    </script>
</body>
</html>

