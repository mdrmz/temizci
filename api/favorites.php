<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Giris yapmalisiniz.']);
    exit;
}

$user = currentUser();
$db = getDB();

$db->exec("\
    CREATE TABLE IF NOT EXISTS fav_store_v2 (\
        id INT AUTO_INCREMENT PRIMARY KEY,\
        user_id INT NOT NULL,\
        listing_id INT NOT NULL,\
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\
        UNIQUE KEY uq_fav_store_v2 (user_id, listing_id),\
        KEY idx_fav_store_user (user_id),\
        KEY idx_fav_store_listing (listing_id)\
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $listingId = (int)($data['listing_id'] ?? $_POST['listing_id'] ?? 0);

    if ($listingId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Gecersiz ilan ID.']);
        exit;
    }

    $check = $db->prepare("SELECT id, user_id, title FROM listings WHERE id = ?");
    $check->execute([$listingId]);
    $listing = $check->fetch();

    if (!$listing) {
        echo json_encode(['status' => 'error', 'message' => 'Ilan bulunamadi.']);
        exit;
    }

    $fav = $db->prepare("SELECT id FROM fav_store_v2 WHERE user_id = ? AND listing_id = ?");
    $fav->execute([$user['id'], $listingId]);

    if ($fav->fetch()) {
        $db->prepare("DELETE FROM fav_store_v2 WHERE user_id = ? AND listing_id = ?")->execute([$user['id'], $listingId]);
        echo json_encode(['status' => 'success', 'action' => 'removed', 'message' => 'Favorilerden kaldirildi.']);
    } else {
        $db->prepare("INSERT INTO fav_store_v2 (user_id, listing_id) VALUES (?, ?)")->execute([$user['id'], $listingId]);

        if ((int) $listing['user_id'] !== (int) $user['id']) {
            createNotification(
                (int) $listing['user_id'],
                'favorite_added',
                $user['name'] . ' ilaninizi favorilere ekledi: ' . $listing['title'],
                APP_URL . '/listings/detail.php?id=' . $listingId
            );
        }

        echo json_encode(['status' => 'success', 'action' => 'added', 'message' => 'Favorilere eklendi!']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $listingId = (int)($_GET['listing_id'] ?? 0);

    if ($listingId > 0) {
        $fav = $db->prepare("SELECT id FROM fav_store_v2 WHERE user_id = ? AND listing_id = ?");
        $fav->execute([$user['id'], $listingId]);
        $isFav = (bool)$fav->fetch();
        echo json_encode(['status' => 'success', 'is_favorite' => $isFav]);
    } else {
        $stmt = $db->prepare("SELECT listing_id FROM fav_store_v2 WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['status' => 'success', 'favorites' => $ids]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Gecersiz istek.']);
