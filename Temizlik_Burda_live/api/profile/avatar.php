<?php
require_once __DIR__ . '/../../api/helpers.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Sadece POST desteklenir.', 405);
}

$user = authenticate();
$db = getDB();

function cleanupAvatarFile(string $avatar): void
{
    $avatar = trim($avatar);
    if ($avatar === '') {
        return;
    }

    $normalized = str_replace('\\', '/', $avatar);
    $base = basename($normalized);
    $candidates = [
        rtrim(UPLOAD_PATH, '/\\') . '/avatars/' . $base,
        rtrim(UPLOAD_PATH, '/\\') . '/' . $base,
    ];

    if (strpos($normalized, '..') === false) {
        $candidates[] = rtrim(UPLOAD_PATH, '/\\') . '/' . ltrim($normalized, '/');
    }

    $seen = [];
    foreach ($candidates as $path) {
        if (isset($seen[$path])) {
            continue;
        }
        $seen[$path] = true;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

$removeAvatar = (int) ($_POST['remove'] ?? 0) === 1;

if ($removeAvatar) {
    $oldAvatar = trim((string) ($user['avatar'] ?? ''));
    if ($oldAvatar !== '') {
        cleanupAvatarFile($oldAvatar);
    }

    $db->prepare("UPDATE users SET avatar = NULL WHERE id = ?")->execute([(int) $user['id']]);
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([(int) $user['id']]);
    jsonSuccess(['user' => safeUser($stmt->fetch(PDO::FETCH_ASSOC))]);
}

if (!isset($_FILES['avatar'])) {
    jsonError('Profil fotosu dosyasi bulunamadi.');
}

$uploadedPath = uploadFile($_FILES['avatar'], 'avatars');
if ($uploadedPath === false) {
    $error = getUploadError();
    jsonError($error !== '' ? $error : 'Profil fotosu yuklenemedi.');
}

$storedAvatar = basename(str_replace('\\', '/', (string) $uploadedPath));
if ($storedAvatar === '') {
    jsonError('Yuklenen dosya adi gecersiz.');
}

$oldAvatar = trim((string) ($user['avatar'] ?? ''));
$db->prepare("UPDATE users SET avatar = ? WHERE id = ?")->execute([$storedAvatar, (int) $user['id']]);

if ($oldAvatar !== '' && $oldAvatar !== $storedAvatar) {
    cleanupAvatarFile($oldAvatar);
}

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([(int) $user['id']]);
jsonSuccess(['user' => safeUser($stmt->fetch(PDO::FETCH_ASSOC))]);

