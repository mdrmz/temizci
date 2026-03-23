<?php
require_once __DIR__ . '/../../api/helpers.php';

$user = authenticate();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT h.*, u.name AS owner_name
        FROM homes h
        JOIN users u ON h.user_id = u.id
        WHERE h.user_id = ? AND h.is_active = 1
        ORDER BY h.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $homes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($homes as &$h) {
        $h['photo_url'] = $h['photo'] ? APP_URL . '/uploads/homes/' . $h['photo'] : null;
    }
    jsonSuccess(['data' => $homes]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if it's multipart or raw JSON
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    if (strpos($contentType, 'multipart/form-data') !== false || strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
        $body = $_POST;
    } else {
        $body = getJsonBody();
    }

    $title = trim($body['title'] ?? 'Evim');
    $address = trim($body['address'] ?? 'Adres girilmedi');
    $city = trim($body['city'] ?? '');
    $roomCfg = trim($body['room_config'] ?? '');

    if (!$city)
        jsonError('Şehir zorunludur.');
    if (!$roomCfg)
        jsonError('Oda yapısı zorunludur.');

    $district = trim($body['district'] ?? '');
    $floor = (int) ($body['floor'] ?? 0);
    $elevator = (int) (!empty($body['has_elevator']));
    $bathroom = (int) ($body['bathroom_count'] ?? 1);
    $sqm = !empty($body['sqm']) ? (int) $body['sqm'] : null;
    $notes = trim($body['notes'] ?? '');

    $photoName = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array(strtolower($ext), $allowed)) {
            $photoName = uniqid('home_') . '.' . $ext;
            $uploadDir = __DIR__ . '/../../uploads/homes/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0777, true);
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photoName)) {
                $photoName = null;
            }
        }
    }

    $db = getDB();
    $db->prepare("
        INSERT INTO homes (user_id, title, address, district, city, room_config, floor, has_elevator, bathroom_count, sqm, notes, photo)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
    ")->execute([$user['id'], $title, $address, $district, $city, $roomCfg, $floor, $elevator, $bathroom, $sqm, $notes, $photoName]);

    jsonSuccess(['home_id' => (int) $db->lastInsertId(), 'photo' => $photoName], 201);
}
jsonError('Desteklenmeyen method.', 405);
