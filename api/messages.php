<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user = currentUser();
$db = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$db->exec("\n    CREATE TABLE IF NOT EXISTS tb_chat_messages (\n        id INT AUTO_INCREMENT PRIMARY KEY,\n        sender_id INT NOT NULL,\n        receiver_id INT NOT NULL,\n        listing_id INT DEFAULT NULL,\n        message TEXT NOT NULL,\n        is_read TINYINT(1) DEFAULT 0,\n        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n        INDEX idx_sender (sender_id),\n        INDEX idx_receiver (receiver_id),\n        INDEX idx_listing (listing_id)\n    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n");

$db->exec("\n    CREATE TABLE IF NOT EXISTS tb_chat_typing (\n        id INT AUTO_INCREMENT PRIMARY KEY,\n        user_id INT NOT NULL,\n        contact_id INT NOT NULL,\n        is_typing TINYINT(1) NOT NULL DEFAULT 0,\n        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n        UNIQUE KEY uq_typing_pair (user_id, contact_id),\n        KEY idx_typing_contact (contact_id)\n    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n");

if ($action === 'poll') {
    $otherId = (int)($_GET['uid'] ?? 0);
    $lastId = (int)($_GET['last_id'] ?? 0);

    if (!$otherId) {
        echo json_encode(['success' => false]);
        exit;
    }

    $stmt = $db->prepare("\n        SELECT m.*, u.name AS sender_name\n        FROM tb_chat_messages m\n        JOIN users u ON m.sender_id = u.id\n        WHERE m.id > ?\n          AND ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))\n        ORDER BY m.created_at ASC\n    ");
    $stmt->execute([$lastId, $user['id'], $otherId, $otherId, $user['id']]);
    $messages = $stmt->fetchAll();

    if (!empty($messages)) {
        $db->prepare("UPDATE tb_chat_messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0")
            ->execute([$otherId, $user['id']]);
    }

    foreach ($messages as &$m) {
        $m['is_mine'] = ((int)$m['sender_id'] === (int)$user['id']);
        $m['time'] = date('H:i', strtotime($m['created_at']));
    }

    $typingStmt = $db->prepare("\n        SELECT is_typing\n        FROM tb_chat_typing\n        WHERE user_id = ? AND contact_id = ? AND updated_at >= (NOW() - INTERVAL 8 SECOND)\n        LIMIT 1\n    ");
    $typingStmt->execute([$otherId, $user['id']]);
    $typingRow = $typingStmt->fetch();
    $isTyping = (bool)($typingRow['is_typing'] ?? 0);

    echo json_encode(['success' => true, 'messages' => $messages, 'typing' => $isTyping]);
    exit;
}

if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $receiverId = (int)($input['receiver_id'] ?? 0);
    $message = trim($input['message'] ?? '');

    if (!$receiverId || $message === '') {
        echo json_encode(['success' => false, 'error' => 'Gecersiz veri.']);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO tb_chat_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$user['id'], $receiverId, $message]);
    $msgId = (int)$db->lastInsertId();

    createNotification($receiverId, 'message', $user['name'] . ' size bir mesaj gonderdi.', APP_URL . '/messages.php?uid=' . $user['id']);

    $notifyEmail = true;
    $notifyTelegram = true;
    try {
        $pref = $db->prepare("SELECT notif_email, notif_telegram FROM users WHERE id = ? LIMIT 1");
        $pref->execute([$receiverId]);
        $pr = $pref->fetch();
        if ($pr) {
            $notifyEmail = ((int)($pr['notif_email'] ?? 1) === 1);
            $notifyTelegram = ((int)($pr['notif_telegram'] ?? 1) === 1);
        }
    } catch (Exception $e) {}

    if ($notifyTelegram) {
        try {
            require_once __DIR__ . '/../includes/telegram.php';
            telegramNotifyNewMessage($receiverId, $user['name'], mb_substr($message, 0, 100));
        } catch (Exception $e) {}
    }

    if ($notifyEmail) {
        try {
            require_once __DIR__ . '/../includes/mailer.php';
            notifyNewMessage($receiverId, $user['name']);
        } catch (Exception $e) {}
    }

    echo json_encode([
        'success' => true,
        'message' => [
            'id' => $msgId,
            'message' => $message,
            'is_mine' => true,
            'time' => date('H:i'),
            'sender_name' => $user['name'],
        ]
    ]);
    exit;
}

if ($action === 'typing_set' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $contactId = (int)($input['contact_id'] ?? 0);
    $isTyping = !empty($input['is_typing']) ? 1 : 0;

    if (!$contactId) {
        echo json_encode(['success' => false, 'error' => 'Gecersiz kullanici']);
        exit;
    }

    $stmt = $db->prepare("\n        INSERT INTO tb_chat_typing (user_id, contact_id, is_typing)\n        VALUES (?, ?, ?)\n        ON DUPLICATE KEY UPDATE is_typing = VALUES(is_typing), updated_at = CURRENT_TIMESTAMP\n    ");
    $stmt->execute([$user['id'], $contactId, $isTyping]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'unread_count') {
    $stmt = $db->prepare("SELECT COUNT(*) FROM tb_chat_messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$user['id']]);
    echo json_encode(['success' => true, 'count' => (int)$stmt->fetchColumn()]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Gecersiz istek.']);
