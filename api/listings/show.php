<?php
require_once __DIR__ . '/../../api/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
    jsonError('Sadece GET desteklenir.', 405);

$id = (int) ($_GET['id'] ?? 0);
if (!$id)
    jsonError('İlan ID zorunludur.');

$db = getDB();
$stmt = $db->prepare("
    SELECT l.*, c.name AS cat_name, c.icon AS cat_icon, c.slug AS cat_slug,
           u.name AS owner_name, u.rating AS owner_rating, u.review_count AS owner_reviews,
           h.city, h.district, h.room_config, h.sqm, h.bathroom_count, h.has_elevator,
           h.floor, h.notes AS home_notes, h.photo AS home_photo
    FROM listings l
    JOIN categories c ON l.category_id = c.id
    JOIN users u ON l.user_id = u.id
    JOIN homes h ON l.home_id = h.id
    WHERE l.id = ?
");
$stmt->execute([$id]);
$listing = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$listing)
    jsonError('İlan bulunamadı.', 404);

$listing['home_photo_url'] = $listing['home_photo']
    ? APP_URL . '/uploads/homes/' . $listing['home_photo'] : null;

// Teklifler (sadece ilan sahibi görebilir — ama şimdi herkese açık bırakıyoruz)
$ostmt = $db->prepare("
    SELECT o.id, o.price, o.message, o.status, o.created_at,
           u.name AS worker_name, u.rating AS worker_rating, u.avatar AS worker_avatar
    FROM offers o
    JOIN users u ON o.user_id = u.id
    WHERE o.listing_id = ?
    ORDER BY o.created_at DESC
");
$ostmt->execute([$id]);
$offers = $ostmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($offers as &$o) {
    $o['worker_avatar_url'] = $o['worker_avatar']
        ? APP_URL . '/uploads/avatars/' . $o['worker_avatar'] : null;
}
unset($o);

// Görüntülenme sayısını artır
$db->prepare("UPDATE listings SET view_count = view_count + 1 WHERE id = ?")->execute([$id]);

jsonSuccess(['listing' => $listing, 'offers' => $offers]);
