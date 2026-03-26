<?php
require_once __DIR__ . '/../../api/helpers.php';

$user = authenticate();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    jsonSuccess(['user' => safeUser($user)]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
    $body = getJsonBody();
    $fields = [];
    $params = [];

    if (!empty($body['name'])) {
        $fields[] = 'name = ?';
        $params[] = trim((string) $body['name']);
    }
    if (!empty($body['phone'])) {
        $fields[] = 'phone = ?';
        $params[] = trim((string) $body['phone']);
    }
    if (!empty($body['city'])) {
        $fields[] = 'city = ?';
        $params[] = trim((string) $body['city']);
    }
    if (isset($body['bio'])) {
        $fields[] = 'bio = ?';
        $params[] = trim((string) $body['bio']);
    }

    $currentPassword = trim((string) ($body['current_password'] ?? ''));
    $newPassword = trim((string) ($body['new_password'] ?? ''));
    if (($currentPassword === '') xor ($newPassword === '')) {
        jsonError('Şifre değiştirmek için mevcut şifre ve yeni şifre birlikte gönderilmelidir.');
    }

    // Şifre değiştirme (web ile hizalı: min 8 + mevcut şifre doğrulama)
    if ($newPassword !== '') {
        if (strlen($newPassword) < 8) {
            jsonError('Yeni şifre en az 8 karakter olmalıdır.');
        }
        if (!password_verify($currentPassword, $user['password'])) {
            jsonError('Mevcut şifre yanlış.');
        }
        $fields[] = 'password = ?';
        $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    if (!empty($fields)) {
        $params[] = $user['id'];
        $db->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?")
            ->execute($params);
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    jsonSuccess(['user' => safeUser($stmt->fetch(PDO::FETCH_ASSOC))]);
}

jsonError('Desteklenmeyen method.', 405);
