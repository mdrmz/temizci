<?php
// API: Favorilere Ekle / Kaldır (Toggle)
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Giriş yapmalısınız.']);
    exit;
}

$user = currentUser();
$db = getDB();

// POST: Toggle Favori
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $listingId = (int)($data['listing_id'] ?? $_POST['listing_id'] ?? 0);
    
    if ($listingId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz ilan ID.']);
        exit;
    }

    // İlan var mı kontrol
    $check = $db->prepare("SELECT id FROM listings WHERE id = ?");
    $check->execute([$listingId]);
    if (!$check->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'İlan bulunamadı.']);
        exit;
    }

    // Zaten favoride mi?
    $fav = $db->prepare("SELECT id FROM favorites WHERE user_id = ? AND listing_id = ?");
    $fav->execute([$user['id'], $listingId]);
    
    if ($fav->fetch()) {
        // Kaldır
        $db->prepare("DELETE FROM favorites WHERE user_id = ? AND listing_id = ?")->execute([$user['id'], $listingId]);
        echo json_encode(['status' => 'success', 'action' => 'removed', 'message' => 'Favorilerden kaldırıldı.']);
    } else {
        // Ekle
        $db->prepare("INSERT INTO favorites (user_id, listing_id) VALUES (?, ?)")->execute([$user['id'], $listingId]);
        echo json_encode(['status' => 'success', 'action' => 'added', 'message' => 'Favorilere eklendi!']);
    }
    exit;
}

// GET: Favori sayısını kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $listingId = (int)($_GET['listing_id'] ?? 0);
    
    if ($listingId > 0) {
        $fav = $db->prepare("SELECT id FROM favorites WHERE user_id = ? AND listing_id = ?");
        $fav->execute([$user['id'], $listingId]);
        $isFav = (bool)$fav->fetch();
        echo json_encode(['status' => 'success', 'is_favorite' => $isFav]);
    } else {
        // Tüm favorileri getir
        $stmt = $db->prepare("SELECT listing_id FROM favorites WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['status' => 'success', 'favorites' => $ids]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek.']);
