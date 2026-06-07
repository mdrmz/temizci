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
ensureSellerApplicationsTable($db);
$user = authenticate();
$userId = (int) ($user['id'] ?? 0);
$isAdmin = (($user['role'] ?? '') === 'admin');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
    handleSellerGet($db, $userId, $isAdmin);
}

if ($method === 'POST') {
    handleSellerPost($db, $user, $isAdmin);
}

jsonError('Desteklenmeyen method.', 405);

function handleSellerGet(PDO $db, int $userId, bool $isAdmin): void
{
    $mode = strtolower(trim((string) ($_GET['mode'] ?? 'my')));
    $scope = ($isAdmin && $mode === 'all') ? 'all' : 'my';
    $statusFilter = strtolower(trim((string) ($_GET['status'] ?? '')));

    $limit = (int) ($_GET['limit'] ?? 60);
    if ($limit < 1) {
        $limit = 1;
    }
    if ($limit > 200) {
        $limit = 200;
    }

    $allowedStatuses = ['pending', 'under_review', 'approved', 'rejected', 'suspended'];
    $where = [];
    $params = [];
    if ($scope === 'my') {
        $where[] = 'sa.user_id = ?';
        $params[] = $userId;
    }
    if ($statusFilter !== '' && in_array($statusFilter, $allowedStatuses, true)) {
        $where[] = 'sa.verification_status = ?';
        $params[] = $statusFilter;
    }
    $whereSql = empty($where) ? '1=1' : implode(' AND ', $where);

    $stmt = $db->prepare("
        SELECT
            sa.*,
            u.name AS user_name,
            u.email AS user_email
        FROM tb_seller_applications sa
        JOIN users u ON u.id = sa.user_id
        WHERE $whereSql
        ORDER BY sa.id DESC
        LIMIT $limit
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $summaryStmt = $db->prepare("
        SELECT
            COUNT(*) AS total,
            COALESCE(SUM(CASE WHEN sa.verification_status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_count,
            COALESCE(SUM(CASE WHEN sa.verification_status = 'under_review' THEN 1 ELSE 0 END), 0) AS under_review_count,
            COALESCE(SUM(CASE WHEN sa.verification_status = 'approved' THEN 1 ELSE 0 END), 0) AS approved_count,
            COALESCE(SUM(CASE WHEN sa.verification_status = 'rejected' THEN 1 ELSE 0 END), 0) AS rejected_count,
            COALESCE(SUM(CASE WHEN sa.verification_status = 'suspended' THEN 1 ELSE 0 END), 0) AS suspended_count
        FROM tb_seller_applications sa
        WHERE $whereSql
    ");
    $summaryStmt->execute($params);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    jsonSuccess([
        'scope' => $scope,
        'data' => array_map('normalizeSellerApplication', $rows),
        'summary' => [
            'total' => (int) ($summary['total'] ?? 0),
            'pending' => (int) ($summary['pending_count'] ?? 0),
            'under_review' => (int) ($summary['under_review_count'] ?? 0),
            'approved' => (int) ($summary['approved_count'] ?? 0),
            'rejected' => (int) ($summary['rejected_count'] ?? 0),
            'suspended' => (int) ($summary['suspended_count'] ?? 0),
        ],
    ]);
}

function handleSellerPost(PDO $db, array $user, bool $isAdmin): void
{
    $mode = strtolower(trim((string) ($_GET['mode'] ?? $_POST['mode'] ?? 'apply')));
    if ($mode === 'apply') {
        handleSellerApply($db, $user);
    }
    if ($mode === 'review') {
        if (!$isAdmin) {
            jsonError('Bu islem yalnizca adminler icindir.', 403);
        }
        handleSellerReview($db, $user);
    }

    jsonError('Gecersiz satıcı islemi.');
}

function handleSellerApply(PDO $db, array $user): void
{
    $userId = (int) ($user['id'] ?? 0);
    rateLimitByKey('seller_apply:user:' . $userId, 5, 86400, 'Bugun icin basvuru limitine ulastiniz.');

    $body = getJsonBody();
    $accountType = strtolower(trim((string) ($body['account_type'] ?? 'individual')));
    if ($accountType !== 'individual' && $accountType !== 'company') {
        $accountType = 'individual';
    }

    $fullName = trim((string) ($body['full_name'] ?? $user['name'] ?? ''));
    $companyName = trim((string) ($body['company_name'] ?? ''));
    $taxNumber = trim((string) ($body['tax_number'] ?? ''));
    $taxOffice = trim((string) ($body['tax_office'] ?? ''));
    $identityNumber = trim((string) ($body['identity_number'] ?? ''));
    $city = trim((string) ($body['city'] ?? $user['city'] ?? ''));
    $district = trim((string) ($body['district'] ?? ''));
    $address = trim((string) ($body['address'] ?? ''));
    $phoneRaw = trim((string) ($body['phone'] ?? $user['phone'] ?? ''));
    $phone = normalizeSellerPhone($phoneRaw);
    $email = mb_strtolower(trim((string) ($body['email'] ?? $user['email'] ?? '')), 'UTF-8');
    $portfolioText = trim((string) ($body['portfolio_text'] ?? ''));
    $categories = normalizeSellerCategories($body['service_categories'] ?? []);

    if (mb_strlen($fullName, 'UTF-8') < 2 || mb_strlen($fullName, 'UTF-8') > 120) {
        jsonError('Ad soyad alani 2 ile 120 karakter arasinda olmali.');
    }
    if ($accountType === 'company' && mb_strlen($companyName, 'UTF-8') < 2) {
        jsonError('Sirket tipi secildiginde firma adi zorunludur.');
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonError('Gecerli bir e-posta girin.');
    }
    if (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
        jsonError('Gecerli bir telefon numarasi girin.');
    }
    if (mb_strlen($portfolioText, 'UTF-8') > 3000) {
        jsonError('Portfoy notu en fazla 3000 karakter olabilir.');
    }
    if (mb_strlen($identityNumber, 'UTF-8') > 40 || mb_strlen($taxNumber, 'UTF-8') > 40) {
        jsonError('Kimlik/vergi alanlari cok uzun.');
    }
    if (mb_strlen($taxOffice, 'UTF-8') > 120 || mb_strlen($city, 'UTF-8') > 120 || mb_strlen($district, 'UTF-8') > 120) {
        jsonError('Konum/vergi dairesi alanlarinda karakter limiti asildi.');
    }
    if (mb_strlen($address, 'UTF-8') > 500) {
        jsonError('Adres alani en fazla 500 karakter olabilir.');
    }

    $pendingStmt = $db->prepare("
        SELECT id
        FROM tb_seller_applications
        WHERE user_id = ?
          AND verification_status IN ('pending', 'under_review')
        LIMIT 1
    ");
    $pendingStmt->execute([$userId]);
    if ($pendingStmt->fetchColumn()) {
        jsonError('Degerlendirmede olan bir satıcı basvurunuz zaten var.');
    }

    $db->beginTransaction();
    try {
        $insert = $db->prepare("
            INSERT INTO tb_seller_applications (
                user_id,
                account_type,
                full_name,
                company_name,
                tax_number,
                tax_office,
                identity_number,
                city,
                district,
                address,
                phone,
                email,
                service_categories,
                portfolio_text,
                verification_status,
                admin_note
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NULL)
        ");
        $insert->execute([
            $userId,
            $accountType,
            $fullName,
            $companyName !== '' ? $companyName : null,
            $taxNumber !== '' ? $taxNumber : null,
            $taxOffice !== '' ? $taxOffice : null,
            $identityNumber !== '' ? $identityNumber : null,
            $city !== '' ? $city : null,
            $district !== '' ? $district : null,
            $address !== '' ? $address : null,
            $phone,
            $email,
            json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $portfolioText !== '' ? $portfolioText : null,
        ]);

        $applicationId = (int) $db->lastInsertId();
        $db->commit();

        createNotification(
            $userId,
            'seller_application',
            'Satıcı basvurunuz alindi ve incelemeye alindi.',
            APP_URL . '/satici'
        );
        notifyAdminsForSeller(
            $db,
            $userId,
            'Yeni satıcı basvurusu geldi: ' . $fullName,
            APP_URL . '/admin/users.php'
        );

        jsonSuccess([
            'application_id' => $applicationId,
            'message' => 'Satıcı basvurunuz alindi.',
        ], 201);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        jsonError('Satıcı basvurusu kaydedilemedi.', 500);
    }
}

function handleSellerReview(PDO $db, array $user): void
{
    $body = getJsonBody();
    $applicationId = max(0, (int) ($body['application_id'] ?? $body['id'] ?? 0));
    $status = strtolower(trim((string) ($body['status'] ?? '')));
    $adminNote = trim((string) ($body['admin_note'] ?? ''));

    if ($applicationId <= 0) {
        jsonError('Basvuru secilmedi.');
    }

    $allowedStatuses = ['pending', 'under_review', 'approved', 'rejected', 'suspended'];
    if (!in_array($status, $allowedStatuses, true)) {
        jsonError('Gecerli bir durum secin.');
    }
    if (mb_strlen($adminNote, 'UTF-8') > 2000) {
        jsonError('Admin notu en fazla 2000 karakter olabilir.');
    }

    $stmt = $db->prepare("SELECT * FROM tb_seller_applications WHERE id = ? LIMIT 1");
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$application) {
        jsonError('Basvuru bulunamadi.', 404);
    }

    $db->beginTransaction();
    try {
        $update = $db->prepare("
            UPDATE tb_seller_applications
            SET verification_status = ?, admin_note = ?, reviewed_by = ?, reviewed_at = NOW()
            WHERE id = ?
        ");
        $update->execute([
            $status,
            $adminNote !== '' ? $adminNote : null,
            (int) ($user['id'] ?? 0),
            $applicationId,
        ]);

        if ($status === 'approved') {
            $db->prepare("UPDATE users SET role = 'worker' WHERE id = ? AND role <> 'admin'")
                ->execute([(int) ($application['user_id'] ?? 0)]);
        }
        $db->commit();

        createNotification(
            (int) ($application['user_id'] ?? 0),
            'seller_review',
            'Satıcı basvurunuz guncellendi: ' . sellerStatusLabel($status),
            APP_URL . '/satici'
        );

        $freshStmt = $db->prepare("SELECT sa.*, u.name AS user_name, u.email AS user_email FROM tb_seller_applications sa JOIN users u ON u.id = sa.user_id WHERE sa.id = ? LIMIT 1");
        $freshStmt->execute([$applicationId]);
        $fresh = $freshStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        jsonSuccess([
            'application' => normalizeSellerApplication($fresh),
        ]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        jsonError('Satıcı basvurusu guncellenemedi.', 500);
    }
}

function normalizeSellerApplication(array $row): array
{
    $categories = [];
    $rawCategories = (string) ($row['service_categories'] ?? '[]');
    try {
        $decoded = json_decode($rawCategories, true, 512, JSON_THROW_ON_ERROR);
        if (is_array($decoded)) {
            foreach ($decoded as $category) {
                $value = trim((string) $category);
                if ($value !== '') {
                    $categories[] = $value;
                }
            }
        }
    } catch (Throwable $e) {
        $categories = [];
    }

    return [
        'id' => (int) ($row['id'] ?? 0),
        'user_id' => (int) ($row['user_id'] ?? 0),
        'user_name' => (string) ($row['user_name'] ?? ''),
        'user_email' => (string) ($row['user_email'] ?? ''),
        'account_type' => (string) ($row['account_type'] ?? 'individual'),
        'full_name' => (string) ($row['full_name'] ?? ''),
        'company_name' => $row['company_name'] ?? null,
        'tax_number' => $row['tax_number'] ?? null,
        'tax_office' => $row['tax_office'] ?? null,
        'identity_number' => $row['identity_number'] ?? null,
        'city' => $row['city'] ?? null,
        'district' => $row['district'] ?? null,
        'address' => $row['address'] ?? null,
        'phone' => (string) ($row['phone'] ?? ''),
        'email' => (string) ($row['email'] ?? ''),
        'service_categories' => $categories,
        'portfolio_text' => $row['portfolio_text'] ?? null,
        'verification_status' => (string) ($row['verification_status'] ?? 'pending'),
        'admin_note' => $row['admin_note'] ?? null,
        'created_at' => (string) ($row['created_at'] ?? ''),
        'updated_at' => (string) ($row['updated_at'] ?? ''),
        'reviewed_at' => $row['reviewed_at'] ?? null,
        'reviewed_by' => isset($row['reviewed_by']) ? (int) $row['reviewed_by'] : null,
    ];
}

function normalizeSellerCategories(mixed $input): array
{
    $items = [];
    if (is_array($input)) {
        $items = $input;
    } elseif (is_string($input)) {
        $items = preg_split('/[,;\n]+/', $input) ?: [];
    }

    $normalized = [];
    foreach ($items as $item) {
        $value = trim((string) $item);
        if ($value === '') {
            continue;
        }
        if (mb_strlen($value, 'UTF-8') > 60) {
            continue;
        }
        $normalized[] = $value;
    }

    $normalized = array_values(array_unique($normalized));
    if (count($normalized) > 12) {
        $normalized = array_slice($normalized, 0, 12);
    }
    return $normalized;
}

function normalizeSellerPhone(string $raw): string
{
    $normalized = preg_replace('/[^0-9+]/', '', $raw) ?? '';
    if ($normalized === '') {
        return '';
    }
    if ($normalized[0] !== '+' && substr($normalized, 0, 2) === '00') {
        $normalized = '+' . substr($normalized, 2);
    }
    return $normalized;
}

function sellerStatusLabel(string $status): string
{
    if ($status === 'approved') {
        return 'Onaylandi';
    }
    if ($status === 'rejected') {
        return 'Reddedildi';
    }
    if ($status === 'suspended') {
        return 'Askida';
    }
    if ($status === 'under_review') {
        return 'Incelemede';
    }
    return 'Beklemede';
}

function notifyAdminsForSeller(PDO $db, int $excludeUserId, string $message, string $link): void
{
    $stmt = $db->query("SELECT id FROM users WHERE role = 'admin' AND is_active = 1");
    $adminIds = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    foreach ($adminIds as $adminIdRaw) {
        $adminId = (int) $adminIdRaw;
        if ($adminId <= 0 || $adminId === $excludeUserId) {
            continue;
        }
        createNotification($adminId, 'seller_alert', $message, $link);
    }
}

function ensureSellerApplicationsTable(PDO $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS tb_seller_applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            account_type ENUM('individual', 'company') NOT NULL DEFAULT 'individual',
            full_name VARCHAR(120) NOT NULL,
            company_name VARCHAR(150) NULL,
            tax_number VARCHAR(40) NULL,
            tax_office VARCHAR(120) NULL,
            identity_number VARCHAR(40) NULL,
            city VARCHAR(120) NULL,
            district VARCHAR(120) NULL,
            address VARCHAR(500) NULL,
            phone VARCHAR(32) NOT NULL,
            email VARCHAR(190) NOT NULL,
            service_categories TEXT NULL,
            portfolio_text TEXT NULL,
            verification_status ENUM('pending', 'under_review', 'approved', 'rejected', 'suspended') NOT NULL DEFAULT 'pending',
            admin_note TEXT NULL,
            reviewed_by INT NULL,
            reviewed_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_seller_user (user_id),
            INDEX idx_seller_status_created (verification_status, created_at),
            INDEX idx_seller_reviewed_by (reviewed_by),
            CONSTRAINT fk_seller_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_seller_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}
