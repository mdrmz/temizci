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
        $params[] = trim($body['name']);
    }
    if (!empty($body['phone'])) {
        $fields[] = 'phone = ?';
        $params[] = trim($body['phone']);
    }
    if (!empty($body['city'])) {
        $fields[] = 'city = ?';
        $params[] = trim($body['city']);
    }
    if (isset($body['bio'])) {
        $fields[] = 'bio = ?';
        $params[] = trim($body['bio']);
    }

    // Şifre değişikliği
    if (!empty($body['new_password'])) {
        if (strlen($body['new_password']) < 6)
            jsonError('Yeni şifre en az 6 karakter olmalı.');
        $fields[] = 'password = ?';
        $params[] = password_hash($body['new_password'], PASSWORD_DEFAULT);
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
