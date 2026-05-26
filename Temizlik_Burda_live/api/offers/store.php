<?php
require_once __DIR__ . '/../../api/helpers.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Sadece POST desteklenir.', 405);
}

$user = authenticate();
rateLimitByKey('offers_store:user:' . ((int) ($user['id'] ?? 0)), 30, 600, 'Cok hizli teklif gonderiyorsunuz. Lutfen biraz bekleyin.');
rateLimitByKey('offers_store:ip:' . getUserIP(), 60, 600, 'Bu agdan cok fazla teklif denemesi var. Kisa sure sonra tekrar deneyin.');
$body = getJsonBody();

$listingId = (int) ($body['listing_id'] ?? 0);
$price = (float) ($body['price'] ?? 0);
$message = trim((string) ($body['message'] ?? ''));

if ($listingId <= 0) {
    jsonError('Ilan ID zorunludur.');
}
if ($price <= 0) {
    jsonError('Gecerli bir fiyat girin.');
}
$offerSpamReason = detectOfferSpamReason($message);
if ($offerSpamReason !== null) {
    jsonError($offerSpamReason);
}
if (($user['role'] ?? '') !== 'worker') {
    jsonError('Sadece hizmet verenler teklif verebilir.', 403);
}

$db = getDB();

// Ilan acik mi?
$ls = $db->prepare("SELECT * FROM listings WHERE id = ? AND status = 'open'");
$ls->execute([$listingId]);
$listing = $ls->fetch();
if (!$listing) {
    jsonError('Ilan bulunamadi veya aktif degil.', 404);
}

// Kendi ilanina teklif veremesin
if ((int) $listing['user_id'] === (int) $user['id']) {
    jsonError('Kendi ilaniniza teklif veremezsiniz.', 403);
}

// Daha once teklif vermis mi?
$ex = $db->prepare("SELECT id FROM offers WHERE listing_id = ? AND user_id = ?");
$ex->execute([$listingId, $user['id']]);
if ($ex->fetch()) {
    jsonError('Bu ilana zaten teklif verdiniz.');
}

$db->prepare("INSERT INTO offers (listing_id, user_id, price, message) VALUES (?,?,?,?)")
    ->execute([$listingId, $user['id'], $price, $message]);

$offerId = (int) $db->lastInsertId();

// Bildirim gonder - ilan sahibine
$notifMsg = (string) ($user['name'] ?? 'Hizmet veren') . ' ilaniniza ' . number_format($price, 0, ',', '.') . ' TL teklif verdi.';
$db->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?,?,?,?)")
    ->execute([(int) $listing['user_id'], 'new_offer', $notifMsg, '/listings/detail.php?id=' . $listingId]);

jsonSuccess(['offer_id' => $offerId], 201);
