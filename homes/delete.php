<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin();

$user = currentUser();
$db = getDB();
$id = (int) ($_GET['id'] ?? 0);

if ($id) {
    // Sadece sahibi silebilir
    $stmt = $db->prepare("SELECT photo FROM homes WHERE id=? AND user_id=?");
    $stmt->execute([$id, $user['id']]);
    $home = $stmt->fetch();

    if ($home) {
        // Fotoğrafı sil
        if ($home['photo'] && file_exists(UPLOAD_PATH . $home['photo'])) {
            @unlink(UPLOAD_PATH . $home['photo']);
        }
        $db->prepare("UPDATE homes SET is_active=0 WHERE id=? AND user_id=?")->execute([$id, $user['id']]);
        setFlash('success', 'Ev kaydı silindi.');
    } else {
        setFlash('error', 'İzin verilmeyen işlem.');
    }
}

redirect(APP_URL . '/homes/list');

