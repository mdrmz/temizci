<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin();

$user = currentUser();
$db = getDB();
$errors = [];
$success = '';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM homes WHERE id = ? AND user_id = ? AND is_active = 1");
$stmt->execute([$id, $user['id']]);
$home = $stmt->fetch();

if (!$home) {
    setFlash('error', 'Ev bulunamadÃ„Â±.');
    redirect(APP_URL . '/homes/list');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'GÃƒÂ¼venlik hatasÃ„Â±.';
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
            $errors[] = 'Ev baÃ…Å¸lÃ„Â±Ã„Å¸Ã„Â± zorunludur.';
        if (empty($address))
            $errors[] = 'Adres zorunludur.';
        if (empty($city))
            $errors[] = 'Ãƒâ€¦Ã‚Âehir zorunludur.';

        $photoPath = $home['photo'];
        if (!empty($_FILES['photo']['name'])) {
            $newPhoto = uploadFile($_FILES['photo'], 'homes');
            if ($newPhoto) {
                // Eskiyi sil? (Opsiyonel)
                $photoPath = $newPhoto;
            } else {
                $uploadReason = getUploadError();
                $errors[] = 'Fotoğraf yüklenemedi.' . ($uploadReason !== '' ? ' ' . $uploadReason : '');
            }
        }

        if (empty($errors)) {
            $stmt = $db->prepare("
                UPDATE homes SET 
                    title = ?, address = ?, district = ?, city = ?, 
                    room_config = ?, floor = ?, has_elevator = ?, 
                    bathroom_count = ?, sqm = ?, notes = ?, photo = ?
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([
                $title,
                $address,
                $district,
                $city,
                $room,
                $floor,
                $elevator,
                $bathroom,
                $sqm ?: null,
                $notes,
                $photoPath,
                $id,
                $user['id']
            ]);

            setFlash('success', 'Ev bilgileri guncellendi!');
            redirect(APP_URL . '/homes/list');
        }
    }
}

$roomConfigs = getRoomConfigs();
$cities = getCities();
$initials = strtoupper(substr($user['name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evi DÃƒÂ¼zenle  -  Temizci Burada</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=5.9">
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
            <?php $headerTitle = 'Evi DÃƒÂ¼zenle'; include '../includes/app-header.php'; ?>

            <div class="page-content">
                <div class="container-sm">
                    <div class="page-title"> Evi DÃƒÂ¼zenle</div>
                    <div class="page-subtitle">
                        <?= e($home['title']) ?> bilgilerini gÃƒÂ¼ncelleyin.
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="flash flash-error">
                            <?= e(implode('<br>', $errors)) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="card">
                        <?= csrfField() ?>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label">Mevcut / Yeni FotoÃ„Å¸raf</label>
                                <?php if ($home['photo']): ?>
                                    <div style="margin-bottom:15px;">
                                        <img src="<?= UPLOAD_URL . e($home['photo']) ?>"
                                            style="width:120px;height:80px;object-fit:cover;border-radius:8px;border:1px solid var(--border);">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="photo" class="form-control" accept="image/*">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="title">Ev BaÃ…Å¸lÃ„Â±Ã„Å¸Ã„Â± *</label>
                                <input type="text" id="title" name="title" class="form-control"
                                    value="<?= e($home['title']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="address">Adres *</label>
                                <textarea id="address" name="address" class="form-control" rows="2"
                                    required><?= e($home['address']) ?></textarea>
                            </div>

                            <div class="grid-2" style="gap:16px;">
                                <div class="form-group">
                                    <label class="form-label" for="district">Ã„Â°lÃƒÂ§e</label>
                                    <input type="text" id="district" name="district" class="form-control"
                                        value="<?= e($home['district']) ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="city">Ãƒâ€¦Ã‚Âehir *</label>
                                    <select id="city" name="city" class="form-control" required>
                                        <?php foreach ($cities as $c): ?>
                                            <option value="<?= e($c) ?>" <?= ($home['city'] === $c) ? 'selected' : '' ?>>
                                                <?= e($c) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="room_config">Oda YapÃ„Â±sÃ„Â±</label>
                                    <select id="room_config" name="room_config" class="form-control">
                                        <?php foreach ($roomConfigs as $r): ?>
                                            <option value="<?= e($r) ?>" <?= ($home['room_config'] === $r) ? 'selected' : '' ?>
                                                >
                                                <?= e($r) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="sqm">Metrekare</label>
                                    <input type="number" id="sqm" name="sqm" class="form-control"
                                        value="<?= e($home['sqm'] ?? '') ?>">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary mt-4"> GÃƒÂ¼ncellemeleri Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/app.js?v=5.8"></script>
    <script src="../assets/js/theme.js"></script>
</body>

</html>




