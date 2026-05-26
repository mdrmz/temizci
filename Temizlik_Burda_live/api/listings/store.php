<?php
require_once __DIR__ . '/../../api/helpers.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Sadece POST desteklenir.', 405);
}

$user = authenticate();
rateLimitByKey('listings_store:user:' . ((int) ($user['id'] ?? 0)), 18, 600, 'Cok hizli ilan aciyorsunuz. Lutfen biraz bekleyin.');
rateLimitByKey('listings_store:ip:' . getUserIP(), 40, 600, 'Bu agdan cok fazla ilan acma denemesi var. Kisa sure sonra tekrar deneyin.');

if (($user['role'] ?? '') !== 'homeowner') {
    jsonError('Sadece ev sahipleri ilan verebilir.', 403);
}

$input = getJsonBody();

$homeId = (int) ($input['home_id'] ?? 0);
$catSlug = trim((string) ($input['category_slug'] ?? ''));
$title = trim((string) ($input['title'] ?? ''));
$desc = trim((string) ($input['description'] ?? ''));
$budget = isset($input['budget']) ? (float) $input['budget'] : null;
$prefDate = trim((string) ($input['preferred_date'] ?? ''));
$prefTime = trim((string) ($input['preferred_time'] ?? ''));

if (!$homeId || $catSlug === '' || $title === '') {
    jsonError('Ev, kategori ve baslik zorunludur.');
}

$spamReason = detectListingSpamReason($title, $desc);
if ($spamReason !== null) {
    jsonError($spamReason);
}

$db = getDB();
ensureMarketplaceCategories();

// Ev kontrolu (kullaniciya ait mi?)
$hs = $db->prepare("SELECT id FROM homes WHERE id = ? AND user_id = ?");
$hs->execute([$homeId, $user['id']]);
if (!$hs->fetchColumn()) {
    jsonError('Gecersiz ev secimi.');
}

// Kategori bul
$cs = $db->prepare("SELECT id FROM categories WHERE slug = ?");
$cs->execute([$catSlug]);
$catId = $cs->fetchColumn();
if (!$catId) {
    jsonError('Gecersiz kategori.');
}

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
        $prefDate !== '' ? $prefDate : null,
        $prefTime !== '' ? $prefTime : null,
    ]);

    $listingId = (int) $db->lastInsertId();
    broadcastNewListingToWorkers($listingId);

    jsonSuccess([
        'message' => 'Ilan basariyla olusturuldu.',
        'listing_id' => $listingId,
    ], 201);
} catch (PDOException $e) {
    jsonError('Ilan olusturulamadi: ' . $e->getMessage(), 500);
}
