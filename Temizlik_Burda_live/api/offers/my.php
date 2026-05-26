<?php
require_once __DIR__ . '/../../api/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Sadece GET desteklenir.', 405);
}

$user = authenticate();
$db = getDB();

$stmt = $db->prepare("
    SELECT o.id, o.listing_id, o.price, o.message, o.status, o.created_at,
           l.title AS listing_title, l.status AS listing_status,
           l.user_id AS homeowner_id,
           h.city, h.district,
           u.name AS homeowner_name
    FROM offers o
    JOIN listings l ON o.listing_id = l.id
    JOIN homes h ON l.home_id = h.id
    JOIN users u ON l.user_id = u.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([(int) $user['id']]);
$offers = $stmt->fetchAll(PDO::FETCH_ASSOC);

jsonSuccess(['data' => $offers]);
