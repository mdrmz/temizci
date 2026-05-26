<?php
require_once __DIR__ . '/../../api/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
    jsonError('Sadece GET desteklenir.', 405);

$id = (int) ($_GET['id'] ?? 0);
if (!$id)
    jsonError('İlan ID zorunludur.');

$db = getDB();
$viewer = null;

function optionalAuthenticate(PDO $db): ?array
{
    $token = getBearerToken();
    if (!$token) {
        return null;
    }

    $stmt = $db->prepare("
        SELECT u.*
        FROM api_tokens t
        JOIN users u ON t.user_id = u.id
        WHERE t.token = ? AND t.expires_at > NOW() AND u.is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user ?: null;
}

$viewer = optionalAuthenticate($db);

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

$ownerId = (int) ($listing['user_id'] ?? 0);
$viewerId = (int) ($viewer['id'] ?? 0);
$viewerIsOwner = $viewerId > 0 && $viewerId === $ownerId;
$viewerHasOffer = false;

if ($viewerId > 0 && !$viewerIsOwner) {
    $hasOfferStmt = $db->prepare("SELECT 1 FROM offers WHERE listing_id = ? AND user_id = ? LIMIT 1");
    $hasOfferStmt->execute([$id, $viewerId]);
    $viewerHasOffer = (bool) $hasOfferStmt->fetchColumn();
}

$offersScope = 'none';
if ($viewerIsOwner) {
    $offersScope = 'all';
} elseif ($viewerHasOffer) {
    $offersScope = 'mine';
}

$offers = [];
if ($offersScope !== 'none') {
    $offerWhere = "o.listing_id = ?";
    $offerParams = [$id];

    if ($offersScope === 'mine') {
        $offerWhere .= " AND o.user_id = ?";
        $offerParams[] = $viewerId;
    }

    $ostmt = $db->prepare("
        SELECT o.id, o.user_id, o.price, o.message, o.status, o.created_at,
               u.name AS worker_name, u.rating AS worker_rating, u.avatar AS worker_avatar
        FROM offers o
        JOIN users u ON o.user_id = u.id
        WHERE $offerWhere
        ORDER BY o.created_at DESC
    ");
    $ostmt->execute($offerParams);
    $offers = $ostmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($offers as &$o) {
        $avatar = trim((string) ($o['worker_avatar'] ?? ''));
        if ($avatar === '') {
            $o['worker_avatar_url'] = null;
        } elseif (strpos($avatar, '/') !== false) {
            $o['worker_avatar_url'] = APP_URL . '/uploads/' . ltrim($avatar, '/');
        } else {
            $o['worker_avatar_url'] = APP_URL . '/uploads/avatars/' . $avatar;
        }

        if ($viewerIsOwner) {
            $o['can_message'] = true;
            $o['message_contact_id'] = (int) ($o['user_id'] ?? 0);
        } elseif ($viewerHasOffer) {
            $o['can_message'] = $ownerId > 0 && $ownerId !== $viewerId;
            $o['message_contact_id'] = $o['can_message'] ? $ownerId : null;
        } else {
            $o['can_message'] = false;
            $o['message_contact_id'] = null;
        }
    }
    unset($o);
}

$listing['owner_id'] = $ownerId;
$listing['offers_scope'] = $offersScope;
$listing['viewer_has_offer'] = $viewerHasOffer;
$listing['viewer_is_owner'] = $viewerIsOwner;
$listing['viewer_can_message_owner'] = !$viewerIsOwner && $viewerHasOffer && $ownerId > 0;

// Görüntülenme sayısını artır
$db->prepare("UPDATE listings SET view_count = view_count + 1 WHERE id = ?")->execute([$id]);

jsonSuccess(['listing' => $listing, 'offers' => $offers, 'offers_scope' => $offersScope]);
