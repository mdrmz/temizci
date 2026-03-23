<?php
require_once __DIR__ . '/../../api/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
    jsonError('Sadece GET desteklenir.', 405);

$db = getDB();
$search = trim($_GET['q'] ?? '');
$catSlug = trim($_GET['cat'] ?? '');
$city = trim($_GET['city'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = ["l.status = 'open'"];
$params = [];

if ($search) {
    $where[] = "(l.title LIKE ? OR l.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($catSlug) {
    $cs = $db->prepare("SELECT id FROM categories WHERE slug = ?");
    $cs->execute([$catSlug]);
    $catId = $cs->fetchColumn();
    if ($catId) {
        $where[] = "l.category_id = ?";
        $params[] = $catId;
    }
}
if ($city) {
    $where[] = "h.city = ?";
    $params[] = $city;
}

$w = implode(' AND ', $where);

$total = (int) $db->prepare("SELECT COUNT(*) FROM listings l JOIN homes h ON l.home_id=h.id WHERE $w")
    ->execute($params) || 0;
$cstmt = $db->prepare("SELECT COUNT(*) FROM listings l JOIN homes h ON l.home_id=h.id WHERE $w");
$cstmt->execute($params);
$total = (int) $cstmt->fetchColumn();

$stmt = $db->prepare("
    SELECT l.id, l.title, l.description, l.preferred_date, l.preferred_time,
           l.budget, l.status, l.view_count, l.created_at,
           c.name AS cat_name, c.icon AS cat_icon, c.slug AS cat_slug,
           u.name AS owner_name,
           h.city, h.district, h.room_config, h.sqm,
           h.photo AS home_photo,
           (SELECT COUNT(*) FROM offers WHERE listing_id = l.id) AS offer_count
    FROM listings l
    JOIN categories c ON l.category_id = c.id
    JOIN users u ON l.user_id = u.id
    JOIN homes h ON l.home_id = h.id
    WHERE $w
    ORDER BY l.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fotoğraf URL'si ekle
foreach ($listings as &$l) {
    $l['home_photo_url'] = $l['home_photo']
        ? APP_URL . '/uploads/homes/' . $l['home_photo'] : null;
}
unset($l);

jsonSuccess([
    'data' => $listings,
    'pagination' => [
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'pages' => (int) ceil($total / $perPage),
    ],
]);
