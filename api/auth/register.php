<?php
require_once __DIR__ . '/../api/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    jsonError('Sadece POST desteklenir.', 405);

$body = getJsonBody();
$name = trim($body['name'] ?? '');
$email = trim($body['email'] ?? '');
$password = $body['password'] ?? '';
$phone = trim($body['phone'] ?? '');
$role = in_array($body['role'] ?? '', ['homeowner', 'worker']) ? $body['role'] : 'homeowner';
$city = trim($body['city'] ?? '');

// Validasyon
if (strlen($name) < 2)
    jsonError('Ad en az 2 karakter olmalı.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    jsonError('Geçerli bir e-posta girin.');
if (strlen($password) < 6)
    jsonError('Şifre en az 6 karakter olmalı.');

$db = getDB();

// E-posta tekrarı
$stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch())
    jsonError('Bu e-posta adresi zaten kayıtlı.');

// Kayıt
$hash = password_hash($password, PASSWORD_DEFAULT);
$db->prepare("INSERT INTO users (name, email, phone, password, role, city) VALUES (?,?,?,?,?,?)")
    ->execute([$name, $email, $phone, $hash, $role, $city]);
$userId = (int) $db->lastInsertId();

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$token = createApiToken($userId);

jsonSuccess(['token' => $token, 'user' => safeUser($user)], 201);
