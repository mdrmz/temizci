<?php
// ============================================================
// Temizci Burada — Mesaj AJAX API (Gerçek Zamanlı Polling)
// ============================================================
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user = currentUser();
$db = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// GET: Yeni mesajları kontrol et (polling)
if ($action === 'poll') {
    $otherId = (int)($_GET['uid'] ?? 0);
    $lastId = (int)($_GET['last_id'] ?? 0);
    
    if (!$otherId) {
        echo json_encode(['success' => false]);
        exit;
    }

    $stmt = $db->prepare("
        SELECT m.*, u.name AS sender_name 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id
        WHERE m.id > ? 
        AND ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$lastId, $user['id'], $otherId, $otherId, $user['id']]);
    $messages = $stmt->fetchAll();

    // Okunmamışları okundu yap
    if (!empty($messages)) {
        $db->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0")
           ->execute([$otherId, $user['id']]);
    }

    // Mesajları formatla
    foreach ($messages as &$m) {
        $m['is_mine'] = ($m['sender_id'] == $user['id']);
        $m['time'] = date('H:i', strtotime($m['created_at']));
    }

    echo json_encode(['success' => true, 'messages' => $messages]);
    exit;
}

// POST: Mesaj gönder
if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $receiverId = (int)($input['receiver_id'] ?? 0);
    $message = trim($input['message'] ?? '');

    if (!$receiverId || empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Geçersiz veri.']);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$user['id'], $receiverId, $message]);
    $msgId = $db->lastInsertId();

    // Bildirim oluştur
    createNotification($receiverId, 'message', $user['name'] . ' size bir mesaj gönderdi.', APP_URL . '/messages?uid=' . $user['id']);

    // Telegram bildirimi (varsa)
    try {
        require_once __DIR__ . '/includes/telegram.php';
        telegramNotifyNewMessage($receiverId, $user['name'], mb_substr($message, 0, 100));
    } catch (Exception $e) {}

    // E-posta bildirimi (varsa)
    try {
        require_once __DIR__ . '/includes/mailer.php';
        notifyNewMessage($receiverId, $user['name']);
    } catch (Exception $e) {}

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

// GET: Okunmamış mesaj sayısı
if ($action === 'unread_count') {
    $stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$user['id']]);
    echo json_encode(['success' => true, 'count' => (int)$stmt->fetchColumn()]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Geçersiz istek.']);
