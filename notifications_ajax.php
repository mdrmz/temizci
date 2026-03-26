<?php
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

// POST: Okundu İşaretle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!empty($input['action'])) {
        if ($input['action'] === 'mark_read') {
            // Tümünü okundu yap
            $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user['id']]);
            echo json_encode(['success' => true, 'message' => 'Tüm bildirimler okundu.']);
            exit;
        }
        if ($input['action'] === 'mark_single' && !empty($input['id'])) {
            // Tek bildirimi okundu yap
            $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")->execute([(int)$input['id'], $user['id']]);
            echo json_encode(['success' => true]);
            exit;
        }
    }
}

// GET: Bildirimleri getir
$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 15");
$stmt->execute([$user['id']]);
$notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// timeAgo formatla
foreach ($notifs as &$n) {
    $n['time_ago'] = timeAgo($n['created_at']);
    $typeIcons = [
        'new_offer' => '📩',
        'offer_accepted' => '✅',
        'offer_rejected' => '❌',
        'review' => '⭐',
        'message' => '💬',
        'favorite_added' => '❤️',
        'favorite_update' => '🔔',
        'counter_offer' => '💸',
        'counter_accepted' => '✅',
        'counter_rejected' => '❌',
        'completion_requested' => '📷',
        'completion_confirmed' => '🏁',
        'system' => 'ℹ️',
    ];
    $n['icon'] = $typeIcons[$n['type']] ?? '🔔';
}
unset($n);

$countStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$countStmt->execute([$user['id']]);
$unreadCount = (int)$countStmt->fetchColumn();

echo json_encode([
    'success' => true,
    'unread_count' => $unreadCount,
    'notifications' => $notifs
]);

