<?php
require_once __DIR__ . '/../../api/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Sadece POST desteklenir.', 405);
}

rateLimitByKey('web_vitals:ip:' . getUserIP(), 180, 60, 'Telemetri istek limiti asildi.');

$body = getJsonBody();
$name = strtoupper(trim((string) ($body['name'] ?? '')));
$id = substr(trim((string) ($body['id'] ?? '')), 0, 120);
$rating = strtolower(trim((string) ($body['rating'] ?? '')));
$value = isset($body['value']) ? (float) $body['value'] : 0.0;
$delta = isset($body['delta']) ? (float) $body['delta'] : 0.0;
$path = substr(trim((string) ($body['path'] ?? '')), 0, 220);
$navigationType = substr(trim((string) ($body['navigationType'] ?? '')), 0, 40);
$userAgent = substr(trim((string) ($body['userAgent'] ?? '')), 0, 260);

$allowedNames = ['CLS', 'LCP', 'INP', 'FCP', 'TTFB'];
if (!in_array($name, $allowedNames, true)) {
    jsonError('Gecersiz metric tipi.');
}

if (!in_array($rating, ['good', 'needs-improvement', 'poor', ''], true)) {
    $rating = '';
}

$logDir = __DIR__ . '/../../logs/metrics';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}

$record = [
    'recorded_at' => date('c'),
    'ip' => getUserIP(),
    'name' => $name,
    'id' => $id,
    'value' => round($value, 4),
    'delta' => round($delta, 4),
    'rating' => $rating,
    'path' => $path,
    'navigation_type' => $navigationType,
    'ua' => $userAgent,
];

$logFile = $logDir . '/web-vitals-' . date('Y-m-d') . '.log';
@file_put_contents(
    $logFile,
    json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
    FILE_APPEND | LOCK_EX
);

jsonSuccess(['received' => true], 202);
