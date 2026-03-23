<?php
require_once __DIR__ . '/../../api/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
    jsonError('Sadece GET desteklenir.', 405);

$user = requireAuth();

$db = getDB();

$stmt = $db->prepare("
    SELECT l.id, l.title, l.description, l.preferred_date, l.preferred_time,
           l.budget, l.status, l.view_count, l.created_at,
           c.name AS cat_name, c.icon AS cat_icon, c.slug AS cat_slug,
           h.city, h.district, h.room_config, h.sqm,
           h.photo AS home_photo,
           (SELECT COUNT(*) FROM offers WHERE listing_id = l.id) AS offer_count
    FROM listings l
    JOIN categories c ON l.category_id = c.id
    JOIN homes h ON l.home_id = h.id
    WHERE l.user_id = ?
    ORDER BY l.created_at DESC
");
$stmt->execute([$user['id']]);
$listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fotoğraflara mutlak URL ekle (Gerekliyse)
foreach ($listings as &$l) {
    if ($l['home_photo']) {
        $l['home_photo_url'] = APP_URL . '/uploads/homes/' . $l['home_photo'];
    } else {
        $l['home_photo_url'] = null;
    }
}
unset($l);

jsonSuccess(['data' => $listings]);
