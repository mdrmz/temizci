<?php
require_once __DIR__ . '/../../api/helpers.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = authenticate();
$db = getDB();

ensureChatTables($db);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
    $mode = strtolower(trim((string) ($_GET['mode'] ?? 'threads')));

    if ($mode === 'threads') {
        handleThreads($db, (int) $user['id']);
    }

    if ($mode === 'conversation') {
        handleConversation($db, (int) $user['id']);
    }

    if ($mode === 'unread_count') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM tb_chat_messages WHERE receiver_id = ? AND is_read = 0 AND listing_id IS NOT NULL");
        $stmt->execute([(int) $user['id']]);
        jsonSuccess(['count' => (int) $stmt->fetchColumn()]);
    }

    jsonError('Gecersiz istek modu.');
}

if ($method === 'POST') {
    $mode = strtolower(trim((string) ($_GET['mode'] ?? $_POST['mode'] ?? 'send')));
    if ($mode !== 'send') {
        jsonError('Gecersiz istek modu.');
    }
    $userId = (int) ($user['id'] ?? 0);
    rateLimitByKey('messages_send:user:' . $userId, 45, 60, 'Mesaj gonderim hiziniz cok yuksek. Lutfen biraz bekleyin.');
    rateLimitByKey('messages_send:ip:' . getUserIP(), 140, 60, 'Bu agdan cok fazla mesaj denemesi var. Kisa sure sonra tekrar deneyin.');
    handleSend($db, $user);
}

jsonError('Desteklenmeyen method.', 405);

function ensureChatTables(PDO $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS tb_chat_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            listing_id INT DEFAULT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_sender (sender_id),
            INDEX idx_receiver (receiver_id),
            INDEX idx_listing (listing_id),
            INDEX idx_pair_time (sender_id, receiver_id, created_at),
            INDEX idx_receiver_read (receiver_id, is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS tb_chat_typing (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            contact_id INT NOT NULL,
            is_typing TINYINT(1) NOT NULL DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_typing_pair (user_id, contact_id),
            KEY idx_typing_contact (contact_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function toPositiveInt(mixed $value): int
{
    $number = (int) $value;
    return $number > 0 ? $number : 0;
}

function hasOfferForListing(PDO $db, int $listingId, int $userId): bool
{
    $stmt = $db->prepare("SELECT 1 FROM offers WHERE listing_id = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$listingId, $userId]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Returns listing and contact metadata when actor has permission to chat.
 */
function validateListingConversation(PDO $db, int $actorId, int $contactId, int $listingId): array
{
    if ($listingId <= 0) {
        jsonError('Ilan bilgisi zorunludur.');
    }
    if ($contactId <= 0) {
        jsonError('Iletisim kurulacak kullanici secilmedi.');
    }
    if ($actorId === $contactId) {
        jsonError('Kendinize mesaj gonderemezsiniz.');
    }

    $listingStmt = $db->prepare("
        SELECT l.id, l.user_id, l.title
        FROM listings l
        WHERE l.id = ?
        LIMIT 1
    ");
    $listingStmt->execute([$listingId]);
    $listing = $listingStmt->fetch(PDO::FETCH_ASSOC);
    if (!$listing) {
        jsonError('Ilan bulunamadi.', 404);
    }

    $contactStmt = $db->prepare("
        SELECT id, name, role, avatar, is_active
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
    $contactStmt->execute([$contactId]);
    $contact = $contactStmt->fetch(PDO::FETCH_ASSOC);
    if (!$contact || (int) ($contact['is_active'] ?? 0) !== 1) {
        jsonError('Kullanici bulunamadi.', 404);
    }

    $ownerId = (int) $listing['user_id'];
    $actorIsOwner = $actorId === $ownerId;
    $contactIsOwner = $contactId === $ownerId;

    if ($actorIsOwner) {
        if ($contactIsOwner || !hasOfferForListing($db, $listingId, $contactId)) {
            jsonError('Bu kullaniciyla bu ilan uzerinden mesajlasamazsiniz.', 403);
        }
    } else {
        if (!$contactIsOwner || !hasOfferForListing($db, $listingId, $actorId)) {
            jsonError('Bu ilan icin mesajlasma yetkiniz yok.', 403);
        }
    }

    $avatar = trim((string) ($contact['avatar'] ?? ''));
    $avatarUrl = null;
    if ($avatar !== '') {
        if (strpos($avatar, '/') !== false) {
            $avatarUrl = APP_URL . '/uploads/' . ltrim($avatar, '/');
        } else {
            $avatarUrl = APP_URL . '/uploads/avatars/' . $avatar;
        }
    }

    return [
        'listing_id' => (int) $listing['id'],
        'listing_title' => (string) ($listing['title'] ?? ''),
        'contact_id' => (int) $contact['id'],
        'contact_name' => (string) ($contact['name'] ?? ''),
        'contact_role' => (string) ($contact['role'] ?? ''),
        'contact_avatar_url' => $avatarUrl,
    ];
}

function detectChatSpamReason(string $message): ?string
{
    $normalized = trim($message);
    if ($normalized === '') {
        return 'Mesaj bos olamaz.';
    }
    if (mb_strlen($normalized, 'UTF-8') > 1200) {
        return 'Mesaj en fazla 1200 karakter olabilir.';
    }
    if (preg_match('/https?:\/\/|www\.|t\.me|telegram|whatsapp|discord/i', $normalized)) {
        return 'Mesaj icinde dis baglanti paylasamazsiniz.';
    }
    if (preg_match('/\+?\d[\d\s\-\(\)]{8,}\d/u', $normalized)) {
        return 'Mesaj icinde telefon numarasi paylasamazsiniz.';
    }
    return null;
}

function handleThreads(PDO $db, int $userId): void
{
    $scanLimit = 600;
    $stmt = $db->prepare("
        SELECT id, sender_id, receiver_id, listing_id, message, is_read, created_at
        FROM tb_chat_messages
        WHERE (sender_id = ? OR receiver_id = ?)
          AND listing_id IS NOT NULL
        ORDER BY id DESC
        LIMIT " . $scanLimit . "
    ");
    $stmt->execute([$userId, $userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        jsonSuccess(['data' => [], 'unread_count' => 0]);
    }

    $unreadStmt = $db->prepare("
        SELECT listing_id, sender_id, COUNT(*) AS cnt
        FROM tb_chat_messages
        WHERE receiver_id = ?
          AND is_read = 0
          AND listing_id IS NOT NULL
        GROUP BY listing_id, sender_id
    ");
    $unreadStmt->execute([$userId]);
    $unreadRows = $unreadStmt->fetchAll(PDO::FETCH_ASSOC);
    $unreadMap = [];
    $totalUnread = 0;
    foreach ($unreadRows as $row) {
        $key = ((int) $row['listing_id']) . '_' . ((int) $row['sender_id']);
        $count = (int) $row['cnt'];
        $unreadMap[$key] = $count;
        $totalUnread += $count;
    }

    $threads = [];
    $contactIds = [];
    $listingIds = [];

    foreach ($rows as $row) {
        $listingId = (int) ($row['listing_id'] ?? 0);
        if ($listingId <= 0) {
            continue;
        }

        $senderId = (int) $row['sender_id'];
        $receiverId = (int) $row['receiver_id'];
        $contactId = $senderId === $userId ? $receiverId : $senderId;
        if ($contactId <= 0) {
            continue;
        }

        $key = $listingId . '_' . $contactId;
        if (isset($threads[$key])) {
            continue;
        }

        $threads[$key] = [
            'thread_key' => $key,
            'listing_id' => $listingId,
            'contact_id' => $contactId,
            'last_message' => (string) ($row['message'] ?? ''),
            'last_message_at' => (string) ($row['created_at'] ?? ''),
            'last_message_from_me' => $senderId === $userId,
            'unread_count' => (int) ($unreadMap[$key] ?? 0),
        ];

        $contactIds[$contactId] = true;
        $listingIds[$listingId] = true;
    }

    $contactMap = [];
    if (!empty($contactIds)) {
        $ids = array_map('intval', array_keys($contactIds));
        $sql = "
            SELECT id, name, avatar
            FROM users
            WHERE id IN (" . implode(',', $ids) . ")
        ";
        $urows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($urows as $u) {
            $avatar = trim((string) ($u['avatar'] ?? ''));
            if ($avatar === '') {
                $avatarUrl = null;
            } elseif (strpos($avatar, '/') !== false) {
                $avatarUrl = APP_URL . '/uploads/' . ltrim($avatar, '/');
            } else {
                $avatarUrl = APP_URL . '/uploads/avatars/' . $avatar;
            }
            $contactMap[(int) $u['id']] = [
                'name' => (string) ($u['name'] ?? 'Kullanici'),
                'avatar_url' => $avatarUrl,
            ];
        }
    }

    $listingMap = [];
    if (!empty($listingIds)) {
        $ids = array_map('intval', array_keys($listingIds));
        $sql = "
            SELECT id, title, status
            FROM listings
            WHERE id IN (" . implode(',', $ids) . ")
        ";
        $lrows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($lrows as $l) {
            $listingMap[(int) $l['id']] = [
                'title' => (string) ($l['title'] ?? 'Ilan'),
                'status' => (string) ($l['status'] ?? ''),
            ];
        }
    }

    $data = [];
    foreach ($threads as $thread) {
        $contact = $contactMap[(int) $thread['contact_id']] ?? ['name' => 'Kullanici', 'avatar_url' => null];
        $listing = $listingMap[(int) $thread['listing_id']] ?? ['title' => 'Ilan', 'status' => ''];

        $thread['contact_name'] = $contact['name'];
        $thread['contact_avatar_url'] = $contact['avatar_url'];
        $thread['listing_title'] = $listing['title'];
        $thread['listing_status'] = $listing['status'];
        $data[] = $thread;
    }

    jsonSuccess(['data' => array_values($data), 'unread_count' => $totalUnread]);
}

function handleConversation(PDO $db, int $userId): void
{
    $listingId = toPositiveInt($_GET['listing_id'] ?? 0);
    $contactId = toPositiveInt($_GET['contact_id'] ?? ($_GET['user_id'] ?? 0));
    $beforeId = toPositiveInt($_GET['before_id'] ?? 0);

    $meta = validateListingConversation($db, $userId, $contactId, $listingId);

    $limit = 50;
    if ($beforeId > 0) {
        $stmt = $db->prepare("
            SELECT id, sender_id, receiver_id, listing_id, message, is_read, created_at
            FROM tb_chat_messages
            WHERE listing_id = ?
              AND id < ?
              AND (
                (sender_id = ? AND receiver_id = ?)
                OR
                (sender_id = ? AND receiver_id = ?)
              )
            ORDER BY id DESC
            LIMIT " . $limit . "
        ");
        $stmt->execute([$listingId, $beforeId, $userId, $contactId, $contactId, $userId]);
    } else {
        $stmt = $db->prepare("
            SELECT id, sender_id, receiver_id, listing_id, message, is_read, created_at
            FROM tb_chat_messages
            WHERE listing_id = ?
              AND (
                (sender_id = ? AND receiver_id = ?)
                OR
                (sender_id = ? AND receiver_id = ?)
              )
            ORDER BY id DESC
            LIMIT " . $limit . "
        ");
        $stmt->execute([$listingId, $userId, $contactId, $contactId, $userId]);
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hasMore = count($rows) === $limit;
    $rows = array_reverse($rows);

    $db->prepare("
        UPDATE tb_chat_messages
        SET is_read = 1
        WHERE listing_id = ?
          AND sender_id = ?
          AND receiver_id = ?
          AND is_read = 0
    ")->execute([$listingId, $contactId, $userId]);

    $messages = [];
    foreach ($rows as $row) {
        $messages[] = [
            'id' => (int) $row['id'],
            'sender_id' => (int) $row['sender_id'],
            'receiver_id' => (int) $row['receiver_id'],
            'listing_id' => (int) $row['listing_id'],
            'message' => (string) ($row['message'] ?? ''),
            'is_read' => (int) ($row['is_read'] ?? 0),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'is_mine' => (int) $row['sender_id'] === $userId,
        ];
    }

    jsonSuccess([
        'data' => $messages,
        'meta' => $meta,
        'has_more' => $hasMore,
    ]);
}

function handleSend(PDO $db, array $user): void
{
    $input = getJsonBody();
    if (empty($input) && !empty($_POST)) {
        $input = $_POST;
    }

    $senderId = (int) ($user['id'] ?? 0);
    $listingId = toPositiveInt($input['listing_id'] ?? 0);
    $receiverId = toPositiveInt($input['receiver_id'] ?? 0);
    $message = trim((string) ($input['message'] ?? ''));

    $spamReason = detectChatSpamReason($message);
    if ($spamReason !== null) {
        jsonError($spamReason);
    }

    $meta = validateListingConversation($db, $senderId, $receiverId, $listingId);

    $rateStmt = $db->prepare("
        SELECT COUNT(*)
        FROM tb_chat_messages
        WHERE sender_id = ?
          AND created_at > (NOW() - INTERVAL 20 SECOND)
    ");
    $rateStmt->execute([$senderId]);
    $sentRecently = (int) $rateStmt->fetchColumn();
    if ($sentRecently >= 15) {
        jsonError('Cok hizli mesaj gonderiyorsunuz. Lutfen birkac saniye bekleyin.', 429);
    }

    $insert = $db->prepare("
        INSERT INTO tb_chat_messages (sender_id, receiver_id, listing_id, message, is_read)
        VALUES (?, ?, ?, ?, 0)
    ");
    $insert->execute([$senderId, $receiverId, $listingId, $message]);
    $messageId = (int) $db->lastInsertId();

    $senderName = trim((string) ($user['name'] ?? 'Kullanici'));
    $link = '/messages?listing_id=' . $listingId . '&user_id=' . $senderId;
    createNotification($receiverId, 'new_message', $senderName . ' size mesaj gonderdi.', $link);

    jsonSuccess([
        'message' => [
            'id' => $messageId,
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'listing_id' => $listingId,
            'message' => $message,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'is_mine' => true,
        ],
        'meta' => $meta,
    ], 201);
}
