<?php
ob_start();
require_once __DIR__ . '/../../api/helpers.php';
require_once __DIR__ . '/../../includes/functions.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function ensureReportsTable(PDO $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS tb_reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reporter_id INT NOT NULL,
            listing_id INT NULL,
            reported_user_id INT NULL,
            reason VARCHAR(64) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('open','reviewing','resolved','rejected') NOT NULL DEFAULT 'open',
            admin_note TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_reports_reporter (reporter_id),
            KEY idx_reports_listing (listing_id),
            KEY idx_reports_user (reported_user_id),
            KEY idx_reports_status_created (status, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function isReportAdmin(array $user): bool
{
    return ($user['role'] ?? '') === 'admin';
}

function reportReasonLabel(string $reason): string
{
    return match ($reason) {
        'spam' => 'Spam veya sahte ilan',
        'fraud' => 'Dolandırıcılık şüphesi',
        'abuse' => 'Rahatsız edici davranış',
        'wrong_info' => 'Yanlış veya eksik bilgi',
        default => 'Diğer',
    };
}

$user = authenticate();
$db = getDB();
ensureReportsTable($db);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = strtolower(trim((string) ($_GET['mode'] ?? 'my')));
    $status = strtolower(trim((string) ($_GET['status'] ?? '')));
    $params = [];
    $where = [];

    if ($mode === 'all' && isReportAdmin($user)) {
        // Admin sees every report.
    } else {
        $where[] = 'r.reporter_id = ?';
        $params[] = (int) $user['id'];
        $mode = 'my';
    }

    if (in_array($status, ['open', 'reviewing', 'resolved', 'rejected'], true)) {
        $where[] = 'r.status = ?';
        $params[] = $status;
    }

    $sqlWhere = empty($where) ? '1=1' : implode(' AND ', $where);
    $stmt = $db->prepare("
        SELECT
            r.id,
            r.reporter_id,
            reporter.name AS reporter_name,
            r.listing_id,
            l.title AS listing_title,
            r.reported_user_id,
            reported.name AS reported_user_name,
            r.reason,
            r.message,
            r.status,
            r.admin_note,
            r.created_at,
            r.updated_at
        FROM tb_reports r
        JOIN users reporter ON reporter.id = r.reporter_id
        LEFT JOIN listings l ON l.id = r.listing_id
        LEFT JOIN users reported ON reported.id = r.reported_user_id
        WHERE $sqlWhere
        ORDER BY r.created_at DESC
        LIMIT 80
    ");
    $stmt->execute($params);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonSuccess([
        'scope' => $mode,
        'data' => $reports,
        'summary' => [
            'total' => count($reports),
            'open' => count(array_filter($reports, static fn(array $row): bool => ($row['status'] ?? '') === 'open')),
            'reviewing' => count(array_filter($reports, static fn(array $row): bool => ($row['status'] ?? '') === 'reviewing')),
            'resolved' => count(array_filter($reports, static fn(array $row): bool => ($row['status'] ?? '') === 'resolved')),
            'rejected' => count(array_filter($reports, static fn(array $row): bool => ($row['status'] ?? '') === 'rejected')),
        ],
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Sadece GET ve POST desteklenir.', 405);
}

$body = getJsonBody();
$mode = strtolower(trim((string) ($_GET['mode'] ?? $body['mode'] ?? 'create')));

if ($mode === 'update') {
    if (!isReportAdmin($user)) {
        jsonError('Bu islem icin admin yetkisi gerekir.', 403);
    }

    $reportId = (int) ($body['report_id'] ?? 0);
    $status = strtolower(trim((string) ($body['status'] ?? '')));
    $adminNote = trim((string) ($body['admin_note'] ?? ''));
    if ($reportId <= 0) {
        jsonError('Sikayet ID zorunludur.', 422);
    }
    if (!in_array($status, ['open', 'reviewing', 'resolved', 'rejected'], true)) {
        jsonError('Gecersiz sikayet durumu.', 422);
    }
    if (mb_strlen($adminNote) > 1200) {
        jsonError('Admin notu en fazla 1200 karakter olabilir.', 422);
    }

    $existing = $db->prepare("
        SELECT r.id, r.reporter_id, r.listing_id, r.reason, r.status
        FROM tb_reports r
        WHERE r.id = ?
        LIMIT 1
    ");
    $existing->execute([$reportId]);
    $report = $existing->fetch(PDO::FETCH_ASSOC);
    if (!$report) {
        jsonError('Sikayet bulunamadi.', 404);
    }

    $stmt = $db->prepare("
        UPDATE tb_reports
        SET status = ?, admin_note = ?
        WHERE id = ?
    ");
    $stmt->execute([$status, $adminNote !== '' ? $adminNote : null, $reportId]);

    createNotification(
        (int) $report['reporter_id'],
        'report_update',
        'Sikayetinizin durumu guncellendi: ' . $status,
        !empty($report['listing_id']) ? '/listings/' . (int) $report['listing_id'] : '/guvenlik',
    );

    $fetch = $db->prepare("
        SELECT
            r.id,
            r.reporter_id,
            reporter.name AS reporter_name,
            r.listing_id,
            l.title AS listing_title,
            r.reported_user_id,
            reported.name AS reported_user_name,
            r.reason,
            r.message,
            r.status,
            r.admin_note,
            r.created_at,
            r.updated_at
        FROM tb_reports r
        JOIN users reporter ON reporter.id = r.reporter_id
        LEFT JOIN listings l ON l.id = r.listing_id
        LEFT JOIN users reported ON reported.id = r.reported_user_id
        WHERE r.id = ?
        LIMIT 1
    ");
    $fetch->execute([$reportId]);

    jsonSuccess([
        'report' => $fetch->fetch(PDO::FETCH_ASSOC),
        'message' => 'Sikayet durumu guncellendi.',
    ]);
}

rateLimitByKey('reports_store:user:' . ((int) ($user['id'] ?? 0)), 10, 600, 'Cok hizli sikayet gonderiyorsunuz. Lutfen biraz bekleyin.');
rateLimitByKey('reports_store:ip:' . getUserIP(), 30, 600, 'Bu agdan cok fazla sikayet denemesi var. Kisa sure sonra tekrar deneyin.');

$listingId = (int) ($body['listing_id'] ?? 0);
$reportedUserId = (int) ($body['reported_user_id'] ?? 0);
$reason = strtolower(trim((string) ($body['reason'] ?? 'other')));
$message = trim((string) ($body['message'] ?? ''));
$allowedReasons = ['spam', 'fraud', 'abuse', 'wrong_info', 'other'];

if (!in_array($reason, $allowedReasons, true)) {
    $reason = 'other';
}
if ($listingId <= 0 && $reportedUserId <= 0) {
    jsonError('Ilan veya kullanici bilgisi zorunludur.', 422);
}
if ($message === '' || mb_strlen($message) < 10) {
    jsonError('Sikayet aciklamasi en az 10 karakter olmalidir.', 422);
}
if (mb_strlen($message) > 1200) {
    jsonError('Sikayet aciklamasi en fazla 1200 karakter olabilir.', 422);
}

$listing = null;
if ($listingId > 0) {
    $stmt = $db->prepare("SELECT id, user_id, title FROM listings WHERE id = ? LIMIT 1");
    $stmt->execute([$listingId]);
    $listing = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$listing) {
        jsonError('Ilan bulunamadi.', 404);
    }
    if ($reportedUserId <= 0) {
        $reportedUserId = (int) ($listing['user_id'] ?? 0);
    }
}

if ($reportedUserId > 0) {
    if ($reportedUserId === (int) $user['id']) {
        jsonError('Kendiniz hakkinda sikayet olusturamazsiniz.', 403);
    }
    $userStmt = $db->prepare("SELECT id FROM users WHERE id = ? AND is_active = 1 LIMIT 1");
    $userStmt->execute([$reportedUserId]);
    if (!$userStmt->fetchColumn()) {
        jsonError('Bildirilecek kullanici bulunamadi.', 404);
    }
}

$duplicate = $db->prepare("
    SELECT id
    FROM tb_reports
    WHERE reporter_id = ?
      AND COALESCE(listing_id, 0) = ?
      AND COALESCE(reported_user_id, 0) = ?
      AND status IN ('open','reviewing')
    LIMIT 1
");
$duplicate->execute([(int) $user['id'], $listingId, $reportedUserId]);
if ($duplicate->fetchColumn()) {
    jsonError('Bu konu icin acik bir sikayetiniz zaten var.', 409);
}

$stmt = $db->prepare("
    INSERT INTO tb_reports (reporter_id, listing_id, reported_user_id, reason, message)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([
    (int) $user['id'],
    $listingId > 0 ? $listingId : null,
    $reportedUserId > 0 ? $reportedUserId : null,
    $reason,
    $message,
]);
$reportId = (int) $db->lastInsertId();

$adminStmt = $db->query("SELECT id FROM users WHERE role = 'admin' AND is_active = 1");
$adminIds = array_map('intval', $adminStmt->fetchAll(PDO::FETCH_COLUMN));
foreach ($adminIds as $adminId) {
    createNotification(
        $adminId,
        'report',
        'Yeni sikayet: ' . reportReasonLabel($reason),
        $listingId > 0 ? '/listings/' . $listingId : '/destek',
    );
}

jsonSuccess([
    'report_id' => $reportId,
    'message' => 'Sikayetiniz alindi. Ekibimiz inceleyecek.',
], 201);
