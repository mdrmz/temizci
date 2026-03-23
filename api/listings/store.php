<?php
require_once __DIR__ . '/../../api/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    jsonError('Sadece POST desteklenir.', 405);

$user = requireAuth();

if ($user['role'] !== 'homeowner')
    jsonError('Sadece ev sahipleri ilan verebilir.', 403);

$input = jsonInput();

$homeId = (int) ($input['home_id'] ?? 0);
$catSlug = trim($input['category_slug'] ?? '');
$title = trim($input['title'] ?? '');
$desc = trim($input['description'] ?? '');
$budget = isset($input['budget']) ? (float) $input['budget'] : null;
$prefDate = trim($input['preferred_date'] ?? '');
$prefTime = trim($input['preferred_time'] ?? '');

if (!$homeId || !$catSlug || !$title) {
    jsonError('Ev, kategori ve başlık zorunludur.');
}

$db = getDB();

// Ev kontrolü (Kullanıcıya mı ait?)
$hs = $db->prepare("SELECT id FROM homes WHERE id = ? AND user_id = ?");
$hs->execute([$homeId, $user['id']]);
if (!$hs->fetchColumn()) {
    jsonError('Geçersiz ev seçimi.');
}

// Kategori bul
$cs = $db->prepare("SELECT id FROM categories WHERE slug = ?");
$cs->execute([$catSlug]);
$catId = $cs->fetchColumn();
if (!$catId)
    jsonError('Geçersiz kategori.');

// İlan Ekle
$stmt = $db->prepare("
    INSERT INTO listings (user_id, home_id, category_id, title, description, budget, preferred_date, preferred_time)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

try {
    $stmt->execute([
        $user['id'],
        $homeId,
        $catId,
        $title,
        $desc,
        $budget,
        $prefDate ?: null,
        $prefTime ?: null
    ]);

    jsonSuccess([
        'message' => 'İlan tbaşarıyla oluşturuldu.',
        'listing_id' => $db->lastInsertId()
    ], 201);
} catch (PDOException $e) {
    jsonError('İlan oluşturulamadı: ' . $e->getMessage(), 500);
}
