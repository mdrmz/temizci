<?php
require_once __DIR__ . '/../../api/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
    jsonError('Sadece GET desteklenir.', 405);

$db = getDB();
$stmt = $db->query("SELECT * FROM categories ORDER BY id");
jsonSuccess(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
