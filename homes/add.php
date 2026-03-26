<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin();

$user = currentUser();
$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Güvenlik hatası.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $room = $_POST['room_config'] ?? '';
        $floor = (int) ($_POST['floor'] ?? 0);
        $elevator = isset($_POST['has_elevator']) ? 1 : 0;
        $bathroom = (int) ($_POST['bathroom_count'] ?? 1);
        $sqm = (int) ($_POST['sqm'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if (empty($title))
            $errors[] = 'Ev başlığı zorunludur.';
        if (empty($address))
            $errors[] = 'Adres zorunludur.';
        if (empty($city))
            $errors[] = 'Åehir zorunludur.';
        if (empty($room))
            $errors[] = 'Oda yapısı seçiniz.';

        // Fotoğraf yükle
        $photoPath = null;
        if (!empty($_FILES['photo']['name'])) {
            $photoPath = uploadFile($_FILES['photo'], 'homes');
            if (!$photoPath)
                $errors[] = 'Fotoğraf yüklenemedi. (Maks 5MB, JPG/PNG/WEBP)';
        }

        if (empty($errors)) {
            $db->prepare("
                INSERT INTO homes (user_id, title, address, district, city, room_config, floor, has_elevator, bathroom_count, sqm, notes, photo)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
            ")->execute([$user['id'], $title, $address, $district, $city, $room, $floor, $elevator, $bathroom, $sqm ?: null, $notes, $photoPath]);

            setFlash('success', 'Ev başarıyla eklendi!  ');
            redirect(APP_URL . '/homes/list');
        }
    }
}

$roomConfigs = getRoomConfigs();
$cities = getCities();
$notifCount = getUnreadNotificationCount($user['id']);
$initials = strtoupper(substr($user['name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ev Ekle  -  Temizci Burada</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=5.0">
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
            <?php $headerTitle = 'Ev Ekle'; include '../includes/app-header.php'; ?>

            <div class="page-content">
                <div class="container-sm">
                    <div class="page-title">  Yeni Ev Ekle</div>
                    <div class="page-subtitle">Ev bilgilerinizi girin. Daha sonra ilanlarınızda bu evi
                        kullanabilirsiniz.</div>

                    <?php if (!empty($errors)): ?>
                        <div class="flash flash-error">
                            <?= e(implode('<br>', $errors)) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="card" data-validate>
                        <?= csrfField() ?>
                        <div class="card-header">
                            <div class="card-title"> Temel Bilgiler</div>
                        </div>
                        <div class="card-body">

                            <!-- Fotoğraf Yükle -->
                            <div class="form-group">
                                <label class="form-label">Ev Fotoğrafı</label>
                                <div class="photo-upload-area">
                                    <input type="file" id="photoInput" name="photo" accept="image/*">
                                    <div class="upload-icon"></div>
                                    <div class="upload-text">Fotoğraf yüklemek için tıklayın veya sürükleyin</div>
                                    <div class="upload-hint">JPG, PNG veya WEBP · Maks. 5MB</div>
                                </div>
                                <img id="photoPreview" class="photo-preview" alt="Önizleme">
                            </div>

                            <div class="grid-2" style="gap:16px;">
                                <div class="form-group" style="grid-column:span 2;">
                                    <label class="form-label" for="title">Ev Başlığı / Lakap *</label>
                                    <input type="text" id="title" name="title" class="form-control"
                                        placeholder="Örn: Kadıköy Dairem, Annem'in Evi..."
                                        value="<?= e($_POST['title'] ?? '') ?>" required>
                                    <div class="form-hint">Bu isim sadece sizin için görünecek, ilanlarınızda seçim
                                        yaparken kullanılacak.</div>
                                </div>

                                <div class="form-group" style="grid-column:span 2;">
                                    <label class="form-label" for="address">Adres *</label>
                                    <textarea id="address" name="address" class="form-control" rows="2"
                                        placeholder="Mahalle, Sokak, Bina No..."
                                        required><?= e($_POST['address'] ?? '') ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="district">İlçe</label>
                                    <input type="text" id="district" name="district" class="form-control"
                                        placeholder="Kadıköy" value="<?= e($_POST['district'] ?? '') ?>">
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="city">Åehir *</label>
                                    <select id="city" name="city" class="form-control" required>
                                        <option value="">Åehir seçin</option>
                                        <?php foreach ($cities as $c): ?>
                                            <option value="<?= e($c) ?>" <?= (($_POST['city'] ?? '') === $c) ? 'selected' : '' ?>>
                                                <?= e($c) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                        </div>

                        <div class="card-header" style="border-top:1px solid var(--border);">
                            <div class="card-title"> Ev Özellikleri</div>
                        </div>
                        <div class="card-body">
                            <div class="grid-2" style="gap:16px;">

                                <div class="form-group">
                                    <label class="form-label" for="room_config">Oda Yapısı *</label>
                                    <select id="room_config" name="room_config" class="form-control" required>
                                        <option value="">Seçiniz</option>
                                        <?php foreach ($roomConfigs as $r): ?>
                                            <option value="<?= e($r) ?>" <?= (($_POST['room_config'] ?? '') === $r) ? 'selected' : '' ?>>
                                                <?= e($r) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="bathroom_count">Banyo Sayısı</label>
                                    <select id="bathroom_count" name="bathroom_count" class="form-control">
                                        <?php for ($i = 1; $i <= 4; $i++): ?>
                                            <option value="<?= $i ?>" <?= (($_POST['bathroom_count'] ?? 1) == $i) ? 'selected' : '' ?>>
                                                <?= $i ?> Banyo
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="floor">Kat</label>
                                    <input type="number" id="floor" name="floor" class="form-control" placeholder="3"
                                        min="0" max="50" value="<?= e($_POST['floor'] ?? '') ?>">
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="sqm">Metrekare (m²)</label>
                                    <input type="number" id="sqm" name="sqm" class="form-control" placeholder="120"
                                        min="10" max="1000" value="<?= e($_POST['sqm'] ?? '') ?>">
                                </div>

                                <div class="form-group" style="grid-column:span 2;">
                                    <label class="form-label">Asansör</label>
                                    <div class="toggle-wrapper">
                                        <label class="toggle">
                                            <input type="checkbox" name="has_elevator" <?= isset($_POST['has_elevator']) ? 'checked' : '' ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                        <span style="color:var(--text-secondary);font-size:0.88rem;">Binada asansör
                                            var</span>
                                    </div>
                                </div>

                                <div class="form-group" style="grid-column:span 2;">
                                    <label class="form-label" for="notes">Özel Notlar</label>
                                    <textarea id="notes" name="notes" class="form-control" rows="3"
                                        placeholder="Temizlik için bilmesi gereken özel durumlar... (ör: köpek var, hassas parke, vb.)"
                                        maxlength="500"><?= e($_POST['notes'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer" style="display:flex;gap:12px;justify-content:flex-end;">
                            <a href="list" class="btn btn-ghost">İptal</a>
                            <button type="submit" class="btn btn-primary">  Evi Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="../assets/js/app.js?v=5.0"></script>
    <script src="../assets/js/theme.js"></script>
</body>

</html>


