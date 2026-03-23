<?php
require_once __DIR__ . '/../../api/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    jsonError('Sadece POST desteklenir.', 405);

$body = getJsonBody();
$email = trim($body['email'] ?? '');
$password = $body['password'] ?? '';
$ip = getUserIP();

if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    jsonError('Geçerli bir e-posta girin.');
if (empty($password))
    jsonError('Şifre zorunludur.');

// Brute force kontrolü
if (!checkLoginAttempts($ip, $email)) {
    $secs = getLoginLockoutSeconds($ip, $email);
    jsonError('Çok fazla başarısız deneme. ' . ceil($secs / 60) . ' dakika bekleyin.', 429);
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password'])) {
    recordFailedLogin($ip, $email);
    jsonError('E-posta veya şifre hatalı.', 401);
}

clearLoginAttempts($ip, $email);
$token = createApiToken((int) $user['id']);

jsonSuccess(['token' => $token, 'user' => safeUser($user)]);
