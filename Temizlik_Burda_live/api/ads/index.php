<?php
if (ob_get_level() === 0) {
    ob_start();
}
require_once __DIR__ . '/../../api/helpers.php';
require_once __DIR__ . '/../../includes/functions.php';
if (ob_get_length() > 0) {
    ob_clean();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    jsonError('Sadece GET desteklenir.', 405);
}

$db = getDB();
ensureAdLeadsTable($db);
$user = authenticate();

$isAdmin = (($user['role'] ?? '') === 'admin');
$mode = strtolower(trim((string) ($_GET['mode'] ?? 'my')));
$limit = (int) ($_GET['limit'] ?? 50);
if ($limit < 1) {
    $limit = 1;
}
if ($limit > 200) {
    $limit = 200;
}

$scope = ($isAdmin && $mode === 'all') ? 'all' : 'my';
if ($scope === 'all') {
    $where = '1=1';
    $params = [];
} else {
    $where = 'al.user_id = ?';
    $params = [(int) ($user['id'] ?? 0)];
}

$stmt = $db->prepare("
    SELECT
        al.id,
        al.package_key,
        al.package_name,
        al.company_name,
        al.contact_name,
        al.email,
        al.phone,
        al.city,
        al.monthly_budget,
        al.note,
        al.preferred_channel,
        al.status,
        al.created_at,
        al.updated_at
    FROM tb_ad_leads al
    WHERE $where
    ORDER BY al.id DESC
    LIMIT $limit
");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$summaryStmt = $db->prepare("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END), 0) AS new_count,
        COALESCE(SUM(CASE WHEN status = 'contacted' THEN 1 ELSE 0 END), 0) AS contacted_count,
        COALESCE(SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END), 0) AS converted_count,
        COALESCE(SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END), 0) AS closed_count
    FROM tb_ad_leads al
    WHERE $where
");
$summaryStmt->execute($params);
$summaryRow = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

jsonSuccess([
    'scope' => $scope,
    'data' => $rows,
    'summary' => [
        'total' => (int) ($summaryRow['total'] ?? 0),
        'new' => (int) ($summaryRow['new_count'] ?? 0),
        'contacted' => (int) ($summaryRow['contacted_count'] ?? 0),
        'converted' => (int) ($summaryRow['converted_count'] ?? 0),
        'closed' => (int) ($summaryRow['closed_count'] ?? 0),
    ],
]);

function ensureAdLeadsTable(PDO $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS tb_ad_leads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            package_key VARCHAR(24) NOT NULL,
            package_name VARCHAR(80) NOT NULL,
            company_name VARCHAR(150) NOT NULL,
            contact_name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NULL,
            phone VARCHAR(32) NULL,
            city VARCHAR(120) NULL,
            monthly_budget DECIMAL(12,2) NULL,
            note TEXT NULL,
            preferred_channel ENUM('whatsapp', 'email') DEFAULT 'whatsapp',
            draft_text TEXT NULL,
            source_url VARCHAR(255) NULL,
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            status ENUM('new', 'contacted', 'converted', 'closed') DEFAULT 'new',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_ad_lead_status_created (status, created_at),
            INDEX idx_ad_lead_email (email),
            INDEX idx_ad_lead_phone (phone),
            INDEX idx_ad_lead_user (user_id),
            CONSTRAINT fk_ad_leads_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}
