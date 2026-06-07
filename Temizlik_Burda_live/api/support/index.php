<?php
if (ob_get_level() === 0) {
    ob_start();
}
require_once __DIR__ . '/../../api/helpers.php';
require_once __DIR__ . '/../../includes/functions.php';
if (ob_get_length() > 0) {
    ob_clean();
}

$db = getDB();
ensureSupportTables($db);
$user = authenticate();
$userId = (int) ($user['id'] ?? 0);
$isAdmin = (($user['role'] ?? '') === 'admin');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
    handleGet($db, $userId, $isAdmin);
}

if ($method === 'POST') {
    handlePost($db, $user, $isAdmin);
}

jsonError('Desteklenmeyen method.', 405);

function handleGet(PDO $db, int $userId, bool $isAdmin): void
{
    $mode = strtolower(trim((string) ($_GET['mode'] ?? 'list')));
    if ($mode === 'detail') {
        $ticketId = max(0, (int) ($_GET['ticket_id'] ?? $_GET['id'] ?? 0));
        if ($ticketId <= 0) {
            jsonError('Destek talebi secilmedi.');
        }

        $ticket = fetchSupportTicket($db, $ticketId);
        if (!$ticket) {
            jsonError('Destek talebi bulunamadi.', 404);
        }
        if (!$isAdmin && (int) ($ticket['user_id'] ?? 0) !== $userId) {
            jsonError('Bu destek talebini goruntuleme yetkiniz yok.', 403);
        }

        $msgStmt = $db->prepare("
            SELECT
                m.id,
                m.ticket_id,
                m.sender_id,
                m.message,
                m.created_at,
                u.name AS sender_name,
                u.role AS sender_role
            FROM xsupport_m1 m
            JOIN users u ON u.id = m.sender_id
            WHERE m.ticket_id = ?
            ORDER BY m.created_at ASC, m.id ASC
        ");
        $msgStmt->execute([$ticketId]);
        $messages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);

        jsonSuccess([
            'ticket' => normalizeSupportTicket($ticket),
            'messages' => $messages,
        ]);
    }

    if ($mode !== 'list' && $mode !== 'all') {
        jsonError('Gecersiz destek listesi modu.');
    }

    $scope = ($isAdmin && $mode === 'all') ? 'all' : 'my';
    $limit = (int) ($_GET['limit'] ?? 40);
    if ($limit < 1) {
        $limit = 1;
    }
    if ($limit > 120) {
        $limit = 120;
    }

    $status = strtolower(trim((string) ($_GET['status'] ?? '')));
    $allowedStatuses = ['open', 'in_progress', 'closed'];
    $where = [];
    $params = [];

    if ($scope === 'my') {
        $where[] = 't.user_id = ?';
        $params[] = $userId;
    }
    if ($status !== '' && in_array($status, $allowedStatuses, true)) {
        $where[] = 't.status = ?';
        $params[] = $status;
    }

    $whereSql = empty($where) ? '1=1' : implode(' AND ', $where);
    $listSql = "
        SELECT
            t.id,
            t.user_id,
            t.subject,
            t.status,
            t.priority,
            t.admin_note,
            t.created_at,
            u.name AS user_name,
            (
                SELECT MAX(m.created_at)
                FROM xsupport_m1 m
                WHERE m.ticket_id = t.id
            ) AS last_message_at,
            (
                SELECT COUNT(*)
                FROM xsupport_m1 m
                WHERE m.ticket_id = t.id
            ) AS message_count
        FROM xsupport_t1 t
        JOIN users u ON u.id = t.user_id
        WHERE $whereSql
        ORDER BY COALESCE(last_message_at, t.created_at) DESC, t.id DESC
        LIMIT $limit
    ";
    $stmt = $db->prepare($listSql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $summarySql = "
        SELECT
            COUNT(*) AS total,
            COALESCE(SUM(CASE WHEN t.status = 'open' THEN 1 ELSE 0 END), 0) AS open_count,
            COALESCE(SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END), 0) AS in_progress_count,
            COALESCE(SUM(CASE WHEN t.status = 'closed' THEN 1 ELSE 0 END), 0) AS closed_count
        FROM xsupport_t1 t
        WHERE $whereSql
    ";
    $summaryStmt = $db->prepare($summarySql);
    $summaryStmt->execute($params);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    jsonSuccess([
        'scope' => $scope,
        'data' => array_map('normalizeSupportTicket', $rows),
        'summary' => [
            'total' => (int) ($summary['total'] ?? 0),
            'open' => (int) ($summary['open_count'] ?? 0),
            'in_progress' => (int) ($summary['in_progress_count'] ?? 0),
            'closed' => (int) ($summary['closed_count'] ?? 0),
        ],
    ]);
}

function handlePost(PDO $db, array $user, bool $isAdmin): void
{
    $mode = strtolower(trim((string) ($_GET['mode'] ?? $_POST['mode'] ?? 'create')));
    if ($mode === 'create') {
        handleCreateTicket($db, $user, $isAdmin);
    }
    if ($mode === 'reply') {
        handleReplyTicket($db, $user, $isAdmin);
    }
    if ($mode === 'update') {
        if (!$isAdmin) {
            jsonError('Bu islem yalnizca adminler icindir.', 403);
        }
        handleUpdateTicket($db, $user);
    }

    jsonError('Gecersiz destek islemi.');
}

function handleCreateTicket(PDO $db, array $user, bool $isAdmin): void
{
    $userId = (int) ($user['id'] ?? 0);
    rateLimitByKey('support_create:user:' . $userId, 12, 3600, 'Kisa surede cok fazla destek talebi actiniz. Lutfen sonra tekrar deneyin.');

    $body = getJsonBody();
    $subject = trim((string) ($body['subject'] ?? ''));
    $message = trim((string) ($body['message'] ?? ''));
    $priority = normalizeSupportPriority((string) ($body['priority'] ?? 'normal'));

    if (mb_strlen($subject, 'UTF-8') < 3 || mb_strlen($subject, 'UTF-8') > 180) {
        jsonError('Konu alani 3 ile 180 karakter arasinda olmali.');
    }
    if (mb_strlen($message, 'UTF-8') < 5 || mb_strlen($message, 'UTF-8') > 4000) {
        jsonError('Destek mesaji 5 ile 4000 karakter arasinda olmali.');
    }

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("
            INSERT INTO xsupport_t1 (user_id, subject, status, priority, admin_note)
            VALUES (?, ?, 'open', ?, NULL)
        ");
        $stmt->execute([$userId, $subject, $priority]);
        $ticketId = (int) $db->lastInsertId();

        $msgStmt = $db->prepare("
            INSERT INTO xsupport_m1 (ticket_id, sender_id, message)
            VALUES (?, ?, ?)
        ");
        $msgStmt->execute([$ticketId, $userId, $message]);
        $messageId = (int) $db->lastInsertId();

        $db->commit();

        $ticketLink = APP_URL . '/destek?ticket_id=' . $ticketId;
        createNotification($userId, 'support_opened', 'Destek talebiniz alindi: ' . $subject, $ticketLink);
        notifyAdminsForSupport($db, $userId, 'Yeni destek talebi: ' . $subject, APP_URL . '/admin/ticket_view.php?id=' . $ticketId);

        jsonSuccess([
            'ticket_id' => $ticketId,
            'message_id' => $messageId,
            'message' => 'Destek talebiniz olusturuldu.',
        ], 201);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        jsonError('Destek talebi olusturulamadi.', 500);
    }
}

function handleReplyTicket(PDO $db, array $user, bool $isAdmin): void
{
    $userId = (int) ($user['id'] ?? 0);
    rateLimitByKey('support_reply:user:' . $userId, 100, 3600, 'Cok hizli mesaj gonderiyorsunuz. Lutfen biraz bekleyin.');

    $body = getJsonBody();
    $ticketId = max(0, (int) ($body['ticket_id'] ?? $body['id'] ?? 0));
    $message = trim((string) ($body['message'] ?? ''));
    $nextStatus = strtolower(trim((string) ($body['status'] ?? '')));

    if ($ticketId <= 0) {
        jsonError('Destek talebi secilmedi.');
    }
    if (mb_strlen($message, 'UTF-8') < 2 || mb_strlen($message, 'UTF-8') > 4000) {
        jsonError('Mesaj 2 ile 4000 karakter arasinda olmali.');
    }

    $ticket = fetchSupportTicket($db, $ticketId);
    if (!$ticket) {
        jsonError('Destek talebi bulunamadi.', 404);
    }
    $ownerId = (int) ($ticket['user_id'] ?? 0);
    if (!$isAdmin && $ownerId !== $userId) {
        jsonError('Bu destek talebine yanit yazma yetkiniz yok.', 403);
    }
    if (!$isAdmin && strtolower((string) ($ticket['status'] ?? '')) === 'closed') {
        jsonError('Kapatilmis destek talebine tekrar mesaj gonderemezsiniz.');
    }

    $db->beginTransaction();
    try {
        $msgStmt = $db->prepare("
            INSERT INTO xsupport_m1 (ticket_id, sender_id, message)
            VALUES (?, ?, ?)
        ");
        $msgStmt->execute([$ticketId, $userId, $message]);
        $messageId = (int) $db->lastInsertId();

        if ($isAdmin) {
            $status = normalizeSupportStatus($nextStatus, (string) ($ticket['status'] ?? 'in_progress'));
            $db->prepare("UPDATE xsupport_t1 SET status = ? WHERE id = ?")
                ->execute([$status, $ticketId]);
        } else {
            $db->prepare("
                UPDATE xsupport_t1
                SET status = CASE WHEN status = 'open' THEN 'in_progress' ELSE status END
                WHERE id = ?
            ")->execute([$ticketId]);
        }

        $db->commit();

        if ($isAdmin) {
            createNotification(
                $ownerId,
                'support_reply',
                'Destek talebinize ekipten yanit geldi: ' . (string) ($ticket['subject'] ?? 'Destek'),
                APP_URL . '/destek?ticket_id=' . $ticketId
            );
        } else {
            notifyAdminsForSupport(
                $db,
                $ownerId,
                'Destek talebine yeni mesaj eklendi: ' . (string) ($ticket['subject'] ?? 'Destek'),
                APP_URL . '/admin/ticket_view.php?id=' . $ticketId
            );
        }

        $freshTicket = fetchSupportTicket($db, $ticketId);
        jsonSuccess([
            'ticket' => $freshTicket ? normalizeSupportTicket($freshTicket) : null,
            'new_message' => [
                'id' => $messageId,
                'ticket_id' => $ticketId,
                'sender_id' => $userId,
                'sender_name' => (string) ($user['name'] ?? 'Kullanici'),
                'sender_role' => (string) ($user['role'] ?? ''),
                'message' => $message,
            ],
        ], 201);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        jsonError('Mesaj gonderilemedi.', 500);
    }
}

function handleUpdateTicket(PDO $db, array $user): void
{
    $body = getJsonBody();
    $ticketId = max(0, (int) ($body['ticket_id'] ?? $body['id'] ?? 0));
    if ($ticketId <= 0) {
        jsonError('Destek talebi secilmedi.');
    }

    $ticket = fetchSupportTicket($db, $ticketId);
    if (!$ticket) {
        jsonError('Destek talebi bulunamadi.', 404);
    }

    $fields = [];
    $params = [];

    if (isset($body['status'])) {
        $fields[] = 'status = ?';
        $params[] = normalizeSupportStatus((string) $body['status'], (string) ($ticket['status'] ?? 'open'));
    }
    if (isset($body['priority'])) {
        $fields[] = 'priority = ?';
        $params[] = normalizeSupportPriority((string) $body['priority']);
    }
    if (array_key_exists('admin_note', $body)) {
        $adminNote = trim((string) ($body['admin_note'] ?? ''));
        if (mb_strlen($adminNote, 'UTF-8') > 3000) {
            jsonError('Admin notu en fazla 3000 karakter olabilir.');
        }
        $fields[] = 'admin_note = ?';
        $params[] = ($adminNote !== '') ? $adminNote : null;
    }

    if (empty($fields)) {
        jsonError('Guncellenecek bir alan bulunamadi.');
    }

    $params[] = $ticketId;
    $sql = "UPDATE xsupport_t1 SET " . implode(', ', $fields) . " WHERE id = ?";
    $db->prepare($sql)->execute($params);

    $freshTicket = fetchSupportTicket($db, $ticketId);
    createNotification(
        (int) ($ticket['user_id'] ?? 0),
        'support_updated',
        'Destek talebiniz guncellendi: ' . (string) ($ticket['subject'] ?? 'Destek'),
        APP_URL . '/destek?ticket_id=' . $ticketId
    );

    jsonSuccess([
        'ticket' => $freshTicket ? normalizeSupportTicket($freshTicket) : null,
        'updated_by' => (int) ($user['id'] ?? 0),
    ]);
}

function fetchSupportTicket(PDO $db, int $ticketId): ?array
{
    $stmt = $db->prepare("
        SELECT
            t.id,
            t.user_id,
            t.subject,
            t.status,
            t.priority,
            t.admin_note,
            t.created_at,
            u.name AS user_name,
            (
                SELECT MAX(m.created_at)
                FROM xsupport_m1 m
                WHERE m.ticket_id = t.id
            ) AS last_message_at,
            (
                SELECT COUNT(*)
                FROM xsupport_m1 m
                WHERE m.ticket_id = t.id
            ) AS message_count
        FROM xsupport_t1 t
        JOIN users u ON u.id = t.user_id
        WHERE t.id = ?
        LIMIT 1
    ");
    $stmt->execute([$ticketId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
}

function normalizeSupportTicket(array $ticket): array
{
    return [
        'id' => (int) ($ticket['id'] ?? 0),
        'user_id' => (int) ($ticket['user_id'] ?? 0),
        'user_name' => (string) ($ticket['user_name'] ?? ''),
        'subject' => (string) ($ticket['subject'] ?? ''),
        'status' => (string) ($ticket['status'] ?? 'open'),
        'priority' => (string) ($ticket['priority'] ?? 'normal'),
        'admin_note' => $ticket['admin_note'] ?? null,
        'created_at' => (string) ($ticket['created_at'] ?? ''),
        'last_message_at' => (string) ($ticket['last_message_at'] ?? ($ticket['created_at'] ?? '')),
        'message_count' => (int) ($ticket['message_count'] ?? 0),
    ];
}

function normalizeSupportPriority(string $priority): string
{
    $normalized = strtolower(trim($priority));
    if ($normalized !== 'low' && $normalized !== 'normal' && $normalized !== 'high') {
        return 'normal';
    }
    return $normalized;
}

function normalizeSupportStatus(string $status, string $fallback = 'open'): string
{
    $normalized = strtolower(trim($status));
    if ($normalized === 'open' || $normalized === 'in_progress' || $normalized === 'closed') {
        return $normalized;
    }
    $base = strtolower(trim($fallback));
    if ($base === 'open' || $base === 'in_progress' || $base === 'closed') {
        return $base;
    }
    return 'open';
}

function notifyAdminsForSupport(PDO $db, int $excludeUserId, string $message, string $link): void
{
    $stmt = $db->query("SELECT id FROM users WHERE role = 'admin' AND is_active = 1");
    $adminIds = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    foreach ($adminIds as $adminIdRaw) {
        $adminId = (int) $adminIdRaw;
        if ($adminId <= 0 || $adminId === $excludeUserId) {
            continue;
        }
        createNotification($adminId, 'support_alert', $message, $link);
    }
}

function ensureSupportTables(PDO $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS xsupport_t1 (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            subject VARCHAR(255) NOT NULL,
            status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_xsupport_t1_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $ticketColumns = $db->query("SHOW COLUMNS FROM xsupport_t1")->fetchAll(PDO::FETCH_COLUMN) ?: [];
    if (!in_array('priority', $ticketColumns, true)) {
        $db->exec("ALTER TABLE xsupport_t1 ADD COLUMN priority ENUM('low', 'normal', 'high') DEFAULT 'normal' AFTER status");
    }
    if (!in_array('admin_note', $ticketColumns, true)) {
        $db->exec("ALTER TABLE xsupport_t1 ADD COLUMN admin_note TEXT NULL AFTER priority");
    }

    $db->exec("
        CREATE TABLE IF NOT EXISTS xsupport_m1 (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT NOT NULL,
            sender_id INT NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_xsupport_m1_ticket (ticket_id),
            KEY idx_xsupport_m1_sender (sender_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}
