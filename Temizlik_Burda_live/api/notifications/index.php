<?php
if (ob_get_level() === 0) {
    ob_start();
}
require_once __DIR__ . '/../../api/helpers.php';
if (ob_get_length() > 0) {
    ob_clean();
}

$db = getDB();
ensureNotificationsTable($db);
$user = authenticate();
$userId = (int) ($user['id'] ?? 0);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
    handleNotificationsGet($db, $userId);
}
if ($method === 'POST') {
    handleNotificationsPost($db, $userId);
}

jsonError('Desteklenmeyen method.', 405);

function handleNotificationsGet(PDO $db, int $userId): void
{
    $mode = strtolower(trim((string) ($_GET['mode'] ?? 'list')));
    if ($mode === 'unread_count') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        jsonSuccess(['count' => (int) $stmt->fetchColumn()]);
    }

    $limit = (int) ($_GET['limit'] ?? 40);
    if ($limit < 1) {
        $limit = 1;
    }
    if ($limit > 150) {
        $limit = 150;
    }

    $rowsStmt = $db->prepare("
        SELECT id, user_id, type, message, link, is_read, created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY id DESC
        LIMIT $limit
    ");
    $rowsStmt->execute([$userId]);
    $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

    $unreadStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $unreadStmt->execute([$userId]);
    $unread = (int) $unreadStmt->fetchColumn();

    jsonSuccess([
        'data' => array_map('normalizeNotificationRow', $rows),
        'unread_count' => $unread,
    ]);
}

function handleNotificationsPost(PDO $db, int $userId): void
{
    $mode = strtolower(trim((string) ($_GET['mode'] ?? $_POST['mode'] ?? 'mark_read')));
    if ($mode !== 'mark_read') {
        jsonError('Gecersiz bildirim islemi.');
    }

    $body = getJsonBody();
    $notificationId = max(0, (int) ($body['id'] ?? $body['notification_id'] ?? 0));
    $markAll = (bool) ($body['all'] ?? false);

    if ($markAll || $notificationId <= 0) {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
    } else {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND id = ?");
        $stmt->execute([$userId, $notificationId]);
    }

    $unreadStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $unreadStmt->execute([$userId]);

    jsonSuccess([
        'updated' => true,
        'unread_count' => (int) $unreadStmt->fetchColumn(),
    ]);
}

function normalizeNotificationRow(array $row): array
{
    return [
        'id' => (int) ($row['id'] ?? 0),
        'user_id' => (int) ($row['user_id'] ?? 0),
        'type' => (string) ($row['type'] ?? ''),
        'message' => (string) ($row['message'] ?? ''),
        'link' => $row['link'] ?? null,
        'is_read' => ((int) ($row['is_read'] ?? 0)) === 1,
        'created_at' => (string) ($row['created_at'] ?? ''),
    ];
}

function ensureNotificationsTable(PDO $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            link VARCHAR(255) DEFAULT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_notifications_user_read (user_id, is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}
