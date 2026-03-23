<?php
require_once __DIR__ . '/../../api/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    jsonError('Sadece POST desteklenir.', 405);

$user = authenticate();
$body = getJsonBody();

$listingId = (int) ($body['listing_id'] ?? 0);
$price = (float) ($body['price'] ?? 0);
$message = trim($body['message'] ?? '');

if (!$listingId)
    jsonError('İlan ID zorunludur.');
if ($price <= 0)
    jsonError('Geçerli bir fiyat girin.');
if (strlen($message) < 10)
    jsonError('Mesaj en az 10 karakter olmalı.');
if ($user['role'] !== 'worker')
    jsonError('Sadece hizmet verenler teklif verebilir.', 403);

$db = getDB();

// İlan açık mı?
$ls = $db->prepare("SELECT * FROM listings WHERE id = ? AND status = 'open'");
$ls->execute([$listingId]);
$listing = $ls->fetch();
if (!$listing)
    jsonError('İlan bulunamadı veya aktif değil.', 404);

// Kendi ilanına teklif veremesin
if ($listing['user_id'] == $user['id'])
    jsonError('Kendi ilanınıza teklif veremezsiniz.', 403);

// Daha önce teklif vermiş mi?
$ex = $db->prepare("SELECT id FROM offers WHERE listing_id = ? AND user_id = ?");
$ex->execute([$listingId, $user['id']]);
if ($ex->fetch())
    jsonError('Bu ilana zaten teklif verdiniz.');

$db->prepare("INSERT INTO offers (listing_id, user_id, price, message) VALUES (?,?,?,?)")
    ->execute([$listingId, $user['id'], $price, $message]);

$offerId = $db->lastInsertId();

// Bildirim gönder — ilan sahibine
$notifMsg = $user['name'] . ' ilanınıza ' . number_format($price, 0, ',', '.') . ' ₺ teklif verdi.';
$db->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?,?,?,?)")
    ->execute([$listing['user_id'], 'new_offer', $notifMsg, '/listings/detail.php?id=' . $listingId]);

jsonSuccess(['offer_id' => (int) $offerId], 201);
