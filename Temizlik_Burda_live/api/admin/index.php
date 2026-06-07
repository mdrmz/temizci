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
$user = authenticate();
if (($user['role'] ?? '') !== 'admin') {
    jsonError('Bu işlem için admin yetkisi gerekir.', 403);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
    handleAdminGet($db);
}
if ($method === 'POST') {
    handleAdminPost($db, (int) ($user['id'] ?? 0));
}

jsonError('Desteklenmeyen method.', 405);

function handleAdminGet(PDO $db): void
{
    $mode = strtolower(trim((string) ($_GET['mode'] ?? 'overview')));
    if ($mode !== 'overview') {
        jsonError('Geçersiz admin modu.');
    }

    $users = $db->query("
        SELECT id, name, email, phone, role, customer_type, company_name, city, is_active, is_verified, rating, review_count, created_at
        FROM users
        ORDER BY id DESC
        LIMIT 120
    ")->fetchAll(PDO::FETCH_ASSOC);

    $categories = $db->query("
        SELECT c.id, c.name, c.slug, c.icon, COUNT(l.id) AS listing_count
        FROM categories c
        LEFT JOIN listings l ON l.category_id = c.id
        GROUP BY c.id, c.name, c.slug, c.icon
        ORDER BY c.name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $summary = [
        'users' => adminApiCount($db, 'SELECT COUNT(*) FROM users'),
        'active_users' => adminApiCount($db, 'SELECT COUNT(*) FROM users WHERE is_active = 1'),
        'workers' => adminApiCount($db, "SELECT COUNT(*) FROM users WHERE role = 'worker'"),
        'homeowners' => adminApiCount($db, "SELECT COUNT(*) FROM users WHERE role = 'homeowner'"),
        'categories' => adminApiCount($db, 'SELECT COUNT(*) FROM categories'),
    ];

    jsonSuccess([
        'users' => array_map('safeUser', $users),
        'categories' => array_map('normalizeAdminCategory', $categories),
        'summary' => $summary,
    ]);
}

function handleAdminPost(PDO $db, int $adminId): void
{
    $mode = strtolower(trim((string) ($_GET['mode'] ?? '')));
    $body = getJsonBody();

    if ($mode === 'user_update') {
        $userId = (int) ($body['user_id'] ?? 0);
        if ($userId <= 0) {
            jsonError('Kullanıcı seçilmedi.');
        }
        if ($userId === $adminId) {
            jsonError('Kendi admin hesabınızı bu panelden değiştiremezsiniz.');
        }

        $targetStmt = $db->prepare("SELECT id, role FROM users WHERE id = ? LIMIT 1");
        $targetStmt->execute([$userId]);
        $target = $targetStmt->fetch(PDO::FETCH_ASSOC);
        if (!$target) {
            jsonError('Kullanıcı bulunamadı.', 404);
        }
        if (($target['role'] ?? '') === 'admin') {
            jsonError('Başka admin hesabı bu panelden değiştirilemez.', 403);
        }

        $fields = [];
        $params = [];
        if (array_key_exists('is_active', $body)) {
            $fields[] = 'is_active = ?';
            $params[] = ((int) $body['is_active']) === 1 ? 1 : 0;
        }
        if (array_key_exists('role', $body)) {
            $role = (string) ($body['role'] ?? 'homeowner');
            if (!in_array($role, ['homeowner', 'worker'], true)) {
                jsonError('Geçersiz kullanıcı rolü.');
            }
            $fields[] = 'role = ?';
            $params[] = $role;
        }

        if (empty($fields)) {
            jsonError('Güncellenecek alan yok.');
        }
        $params[] = $userId;
        $db->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);

        $fresh = $db->prepare("
            SELECT id, name, email, phone, role, customer_type, company_name, city, is_active, is_verified, rating, review_count, created_at
            FROM users
            WHERE id = ?
        ");
        $fresh->execute([$userId]);
        jsonSuccess(['user' => safeUser($fresh->fetch(PDO::FETCH_ASSOC) ?: [])]);
    }

    if ($mode === 'category_save') {
        $id = (int) ($body['id'] ?? 0);
        $name = trim((string) ($body['name'] ?? ''));
        $slug = trim((string) ($body['slug'] ?? ''));
        $icon = trim((string) ($body['icon'] ?? ''));

        if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 100) {
            jsonError('Kategori adı 2 ile 100 karakter arasında olmalı.');
        }
        if (!preg_match('/^[a-z0-9-]{2,100}$/', $slug)) {
            jsonError('Slug küçük harf, rakam ve tire içermeli.');
        }
        if ($icon === '') {
            $icon = '📋';
        }

        try {
            if ($id > 0) {
                $db->prepare("UPDATE categories SET name = ?, slug = ?, icon = ? WHERE id = ?")
                    ->execute([$name, $slug, $icon, $id]);
            } else {
                $db->prepare("INSERT INTO categories (name, slug, icon) VALUES (?, ?, ?)")
                    ->execute([$name, $slug, $icon]);
                $id = (int) $db->lastInsertId();
            }
        } catch (PDOException $e) {
            jsonError('Kategori kaydedilemedi. Slug benzersiz olmalı.');
        }

        $stmt = $db->prepare("
            SELECT c.id, c.name, c.slug, c.icon, COUNT(l.id) AS listing_count
            FROM categories c
            LEFT JOIN listings l ON l.category_id = c.id
            WHERE c.id = ?
            GROUP BY c.id, c.name, c.slug, c.icon
        ");
        $stmt->execute([$id]);
        jsonSuccess(['category' => normalizeAdminCategory($stmt->fetch(PDO::FETCH_ASSOC) ?: [])]);
    }

    if ($mode === 'category_delete') {
        $id = (int) ($body['id'] ?? 0);
        if ($id <= 0) {
            jsonError('Kategori seçilmedi.');
        }
        try {
            $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        } catch (PDOException $e) {
            jsonError('Bu kategoriye bağlı ilan olduğu için silinemedi.');
        }
        jsonSuccess(['deleted_id' => $id]);
    }

    jsonError('Geçersiz admin işlemi.');
}

function adminApiCount(PDO $db, string $sql): int
{
    try {
        return (int) $db->query($sql)->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function normalizeAdminCategory(array $row): array
{
    return [
        'id' => (int) ($row['id'] ?? 0),
        'name' => (string) ($row['name'] ?? ''),
        'slug' => (string) ($row['slug'] ?? ''),
        'icon' => $row['icon'] ?? null,
        'listing_count' => (int) ($row['listing_count'] ?? 0),
    ];
}
