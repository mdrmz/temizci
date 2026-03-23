<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin();

$user = currentUser();
$db = getDB();
$errors = [];

// Ev listesi — sadece kullanıcının evleri
$homesStmt = $db->prepare("SELECT id, title, room_config, city FROM homes WHERE user_id = ? AND is_active = 1");
$homesStmt->execute([$user['id']]);
$homes = $homesStmt->fetchAll();

$categories = getCategories();
$times = getPreferredTimes();
$preHomeId = (int) ($_GET['home_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Güvenlik hatası.';
    } else {
        $homeId = (int) ($_POST['home_id'] ?? 0);
        $catId = (int) ($_POST['category_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $date = $_POST['preferred_date'] ?? '';
        $time = $_POST['preferred_time'] ?? 'esnek';
        $owner = isset($_POST['owner_home']) ? 1 : 0;
        $budget = !empty($_POST['budget']) ? (float) $_POST['budget'] : null;
        $isRecurring = isset($_POST['is_recurring']) ? 1 : 0;

        if (!$homeId)
            $errors[] = 'Ev seçiniz.';
        if (!$catId)
            $errors[] = 'Kategori seçiniz.';
        if (empty($title))
            $errors[] = 'Başlık zorunludur.';
        if (strlen($title) < 5)
            $errors[] = 'Başlık en az 5 karakter olmalı.';
        if (empty($desc))
            $errors[] = 'Açıklama zorunludur.';
        if (empty($date))
            $errors[] = 'Tarih zorunludur.';
        if ($date < date('Y-m-d'))
            $errors[] = 'Geçmiş bir tarih seçemezsiniz.';

        // Ev sahibine mi ait?
        $homeCheck = $db->prepare("SELECT id FROM homes WHERE id = ? AND user_id = ?");
        $homeCheck->execute([$homeId, $user['id']]);
        if (!$homeCheck->fetch())
            $errors[] = 'Geçersiz ev seçimi.';

        if (empty($errors)) {
            $db->prepare("
                INSERT INTO listings (user_id, home_id, category_id, title, description, preferred_date, preferred_time, owner_home, budget, is_recurring)
                VALUES (?,?,?,?,?,?,?,?,?,?)
            ")->execute([$user['id'], $homeId, $catId, $title, $desc, $date, $time, $owner, $budget, $isRecurring]);

            $listingId = $db->lastInsertId();
            setFlash('success', 'İlan başarıyla oluşturuldu! 🎉');
            redirect(APP_URL . '/listings/detail.php?id=' . $listingId);
        }
    }
}

$notifCount = getUnreadNotificationCount($user['id']);
$initials = strtoupper(substr($user['name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İlan Oluştur — Temizci Burada</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="../assets/css/style.css?v=4.0">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">

    <!-- SEO & Favicon -->
    <link rel="icon" href="/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="/logo.png">
    <meta property="og:image" content="https://www.temizciburada.com/logo.png">
</head>

<body>
    <div class="app-layout">
        <?php include '../includes/sidebar.php'; ?>
        <div class="main-content">
            <?php $headerTitle = 'Yeni İlan'; include '../includes/app-header.php'; ?>

            <div class="page-content">
                <div class="container-sm">
                    <div class="page-title">✏️ Yeni İlan Oluştur</div>
                    <div class="page-subtitle">Temizlik veya günübirlik iş ilanı verin, teklifler almaya başlayın.</div>

                    <?php if (empty($homes)): ?>
                        <div class="flash flash-warning">
                            ⚠️ İlan oluşturmak için önce bir ev eklemelisiniz.
                            <a href="../homes/add" class="btn btn-primary btn-sm" style="margin-left:12px;">🏠 Ev
                                Ekle</a>
                        </div>
                    <?php else: ?>

                        <?php if (!empty($errors)): ?>
                            <div class="flash flash-error">❌
                                <?= e(implode('<br>', $errors)) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" class="card" data-validate>
                            <?= csrfField() ?>

                            <!-- Ev Seçimi -->
                            <div class="card-header">
                                <div class="card-title">🏠 Hangi Ev İçin?</div>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label">Ev Seç *</label>
                                    <div
                                        style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;">
                                        <?php foreach ($homes as $h): ?>
                                            <label style="cursor:pointer;">
                                                <input type="radio" name="home_id" value="<?= $h['id'] ?>" style="display:none;"
                                                    <?= (($_POST['home_id'] ?? $preHomeId) == $h['id']) ? 'checked' : '' ?>>
                                                <div class="home-pick-card"
                                                    style="padding:14px;border:2px solid var(--border);border-radius:var(--radius);transition:var(--transition);">
                                                    <div style="font-size:1.5rem;margin-bottom:6px;">🏠</div>
                                                    <div style="font-weight:700;font-size:0.9rem;">
                                                        <?= e($h['title']) ?>
                                                    </div>
                                                    <div style="font-size:0.78rem;color:var(--text-muted);">
                                                        <?= e($h['room_config']) ?> ·
                                                        <?= e($h['city']) ?>
                                                    </div>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- İlan Detayları -->
                            <div class="card-header" style="border-top:1px solid var(--border);">
                                <div class="card-title">📋 İlan Detayları</div>
                            </div>
                            <div class="card-body">

                                <div class="form-group">
                                    <label class="form-label" for="category_id">Hizmet Kategorisi *</label>
                                    <div
                                        style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;margin-bottom:4px;">
                                        <?php foreach ($categories as $cat): ?>
                                            <label style="cursor:pointer;text-align:center;">
                                                <input type="radio" name="category_id" value="<?= $cat['id'] ?>"
                                                    style="display:none;" <?= (($_POST['category_id'] ?? '') == $cat['id']) ? 'checked' : '' ?>>
                                                <div class="cat-pick"
                                                    style="padding:12px 10px;border:2px solid var(--border);border-radius:var(--radius-sm);transition:var(--transition);">
                                                    <div style="font-size:1.5rem;">
                                                        <?= $cat['icon'] ?>
                                                    </div>
                                                    <div style="font-size:0.78rem;font-weight:600;margin-top:4px;">
                                                        <?= e($cat['name']) ?>
                                                    </div>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="title">İlan Başlığı *</label>
                                    <input type="text" id="title" name="title" class="form-control"
                                        placeholder="Örn: 3+1 dairem için genel temizlik gerekiyor" required maxlength="200"
                                        value="<?= e($_POST['title'] ?? '') ?>">
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="description">Açıklama *</label>
                                    <textarea id="description" name="description" class="form-control" rows="4" required
                                        maxlength="1000"
                                        placeholder="Yapılmasını istediğiniz işi detaylı anlatın. Özel istekleriniz var mı? Mutfak, banyo, yatak odaları temizlenecek mi?..."><?= e($_POST['description'] ?? '') ?></textarea>
                                </div>

                                <div class="grid-2" style="gap:16px;">
                                    <div class="form-group">
                                        <label class="form-label" for="preferred_date">Tercih Edilen Tarih *</label>
                                        <input type="date" id="preferred_date" name="preferred_date" class="form-control"
                                            min="<?= date('Y-m-d') ?>" value="<?= e($_POST['preferred_date'] ?? '') ?>"
                                            required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="preferred_time">Saat Tercihi</label>
                                        <select id="preferred_time" name="preferred_time" class="form-control">
                                            <?php foreach ($times as $val => $label): ?>
                                                <option value="<?= e($val) ?>" <?= (($_POST['preferred_time'] ?? 'esnek') === $val) ? 'selected' : '' ?>>
                                                    <?= e($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="budget">Bütçe (₺) — Opsiyonel</label>
                                        <input type="number" id="budget" name="budget" class="form-control" placeholder="0"
                                            min="0" max="99999" step="1" value="<?= e($_POST['budget'] ?? '') ?>">
                                        <div class="form-hint">Bütçe belirtirseniz teklifler buna göre şekillenir.</div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Periyodik İlan mı?</label>
                                        <div class="toggle-wrapper" style="margin-top:10px;">
                                            <label class="toggle">
                                                <input type="checkbox" name="is_recurring" <?= isset($_POST['is_recurring']) ? 'checked' : '' ?>>
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span style="color:var(--text-secondary);font-size:0.88rem;">Evet, düzenli temizlik istiyorum</span>
                                        </div>
                                        <div class="form-hint">Örn: Her hafta veya 15 günde bir.</div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">İş Yapılırken Evde Olacak mısınız?</label>
                                        <div class="toggle-wrapper" style="margin-top:10px;">
                                            <label class="toggle">
                                                <input type="checkbox" name="owner_home" <?= isset($_POST['owner_home']) ? 'checked' : 'checked' ?>>
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span style="color:var(--text-secondary);font-size:0.88rem;">Evet, evde
                                                olacağım</span>
                                        </div>
                                        <div class="form-hint">Bu bilgi hizmet verenlerin karar vermesine yardımcı olur.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer" style="display:flex;gap:12px;justify-content:flex-end;">
                                <a href="my_listings" class="btn btn-ghost">İptal</a>
                                <button type="submit" class="btn btn-primary">🚀 İlanı Yayınla</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="../assets/js/app.js?v=4.0"></script>
    <script src="../assets/js/theme.js"></script>
    <script>
        // Radio kartlarını seçildiğinde vurgula
        document.querySelectorAll('[name="home_id"],[name="category_id"]').forEach(radio => {
            radio.addEventListener('change', function () {
                const siblings = document.querySelectorAll(`[name="${this.name}"]`);
                siblings.forEach(r => {
                    const card = r.nextElementSibling;
                    if (card) card.style.borderColor = r.checked ? 'var(--primary)' : 'var(--border)';
                });
            });
            if (radio.checked) {
                const card = radio.nextElementSibling;
                if (card) card.style.borderColor = 'var(--primary)';
            }
        });
    </script>
</body>

</html>