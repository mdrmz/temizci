<?php
require_once __DIR__ . '/../../api/helpers.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Sadece POST desteklenir.', 405);
}

rateLimitByKey('register:ip:' . getUserIP(), 8, 60, 'Kayit islem limiti asildi. Lutfen 1 dakika sonra tekrar deneyin.');

$body = getJsonBody();
$name = trim((string) ($body['name'] ?? ''));
$email = trim((string) ($body['email'] ?? ''));
$password = (string) ($body['password'] ?? '');
$phone = normalizePhone(trim((string) ($body['phone'] ?? '')));
$role = in_array($body['role'] ?? '', ['homeowner', 'worker'], true) ? $body['role'] : 'homeowner';
$city = trim((string) ($body['city'] ?? ''));

// Validasyon
if (mb_strlen($name, 'UTF-8') < 2) {
    jsonError('Ad en az 2 karakter olmali.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonError('Gecerli bir e-posta girin.');
}
if ($passwordError = tbValidatePasswordPolicy($password, $email, $name)) {
    jsonError($passwordError);
}
if ($role === 'worker' && $phone === '') {
    jsonError('Hizmet veren kaydinda telefon zorunludur.');
}
if ($phone !== '' && !isValidTrPhone($phone)) {
    jsonError('Telefon numarasi gecersiz. Ornek: 05xx xxx xx xx');
}

$db = getDB();

// E-posta tekrar kontrolu
$stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    jsonError('Bu e-posta adresi zaten kayitli.');
}

// Kayit
$hash = password_hash($password, PASSWORD_DEFAULT);
$db->prepare("INSERT INTO users (name, email, phone, password, role, city) VALUES (?,?,?,?,?,?)")
    ->execute([$name, $email, $phone, $hash, $role, $city]);
$userId = (int) $db->lastInsertId();

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$token = createApiToken($userId);

jsonSuccess(['token' => $token, 'user' => safeUser($user)], 201);
