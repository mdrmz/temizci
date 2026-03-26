<?php

date_default_timezone_set('Europe/Istanbul');

$pdo = new PDO(
    'mysql:host=localhost;dbname=temizlik_burda;charset=utf8mb4',
    'root',
    '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$results = [];

function addResult(array &$results, string $module, string $test, string $status, string $severity, string $detail): void
{
    $results[] = [
        'module' => $module,
        'test' => $test,
        'status' => $status,
        'severity' => $severity,
        'detail' => $detail,
    ];
}

function pass(array &$results, string $module, string $test, string $detail): void
{
    addResult($results, $module, $test, 'PASS', 'none', $detail);
}

function fail(array &$results, string $module, string $test, string $severity, string $detail): void
{
    addResult($results, $module, $test, 'FAIL', $severity, $detail);
}

try {
    $users = $pdo->query("SELECT email, role FROM users WHERE email IN ('qa.homeowner@temizlikburda.local','qa.worker@temizlikburda.local','qa.admin@temizlikburda.local')")->fetchAll(PDO::FETCH_ASSOC);
    if (count($users) === 3) {
        pass($results, 'Auth', 'QA users exist', '3 QA users found');
    } else {
        fail($results, 'Auth', 'QA users exist', 'blocker', 'Expected 3 QA users, found ' . count($users));
    }

    $listingCount = (int) $pdo->query("SELECT COUNT(*) FROM listings WHERE title IN ('QA Acik Ilan','QA Kapali Ilan')")->fetchColumn();
    $listingCount >= 2
        ? pass($results, 'Homeowner', 'Listings seeded', "Seeded listing count: {$listingCount}")
        : fail($results, 'Homeowner', 'Listings seeded', 'blocker', "Expected >=2 listings, found {$listingCount}");

    $offerCount = (int) $pdo->query("SELECT COUNT(*) FROM offers o JOIN users u ON u.id=o.user_id JOIN listings l ON l.id=o.listing_id WHERE u.email='qa.worker@temizlikburda.local' AND l.title='QA Acik Ilan'")->fetchColumn();
    $offerCount >= 1
        ? pass($results, 'Worker', 'Offer exists', "Offer count: {$offerCount}")
        : fail($results, 'Worker', 'Offer exists', 'blocker', 'No worker offer found for open listing');

    $favCount = (int) $pdo->query("SELECT COUNT(*) FROM fav_store_v2 f JOIN users u ON u.id=f.user_id JOIN listings l ON l.id=f.listing_id WHERE u.email='qa.worker@temizlikburda.local' AND l.title='QA Acik Ilan'")->fetchColumn();
    $favCount >= 1
        ? pass($results, 'Favorites', 'Favorite exists', "Favorite count: {$favCount}")
        : fail($results, 'Favorites', 'Favorite exists', 'major', 'No favorite found for worker/open listing');

    $selfFavCount = (int) $pdo->query("SELECT COUNT(*) FROM fav_store_v2 f JOIN listings l ON l.id=f.listing_id WHERE f.user_id=l.user_id")->fetchColumn();
    $selfFavCount === 0
        ? pass($results, 'Favorites', 'Negative: self-favorite blocked in data', 'No self-favorite rows')
        : fail($results, 'Favorites', 'Negative: self-favorite blocked in data', 'major', "Found {$selfFavCount} self-favorite rows");

    $msgStats = $pdo->query("SELECT SUM(CASE WHEN is_read=0 THEN 1 ELSE 0 END) unread_count, SUM(CASE WHEN is_read=1 THEN 1 ELSE 0 END) read_count FROM tb_chat_messages WHERE listing_id IN (SELECT id FROM listings WHERE title='QA Acik Ilan')")->fetch(PDO::FETCH_ASSOC) ?: ['unread_count'=>0,'read_count'=>0];
    $unreadCount = (int) $msgStats['unread_count'];
    $readCount = (int) $msgStats['read_count'];
    ($unreadCount >= 1 && $readCount >= 1)
        ? pass($results, 'Messages', 'Unread/read distribution', "read={$readCount}, unread={$unreadCount}")
        : fail($results, 'Messages', 'Unread/read distribution', 'major', "Expected both read and unread messages, got read={$readCount}, unread={$unreadCount}");

    $ticketCount = (int) $pdo->query("SELECT COUNT(*) FROM xsupport_t1 t JOIN users u ON u.id=t.user_id WHERE u.email='qa.homeowner@temizlikburda.local'")->fetchColumn();
    $ticketCount >= 1
        ? pass($results, 'Support', 'Ticket exists', "Ticket count: {$ticketCount}")
        : fail($results, 'Support', 'Ticket exists', 'blocker', 'No support ticket for homeowner');

    $supportMsgStats = $pdo->query("SELECT SUM(CASE WHEN u.role='admin' THEN 1 ELSE 0 END) admin_msgs, SUM(CASE WHEN u.role='homeowner' THEN 1 ELSE 0 END) homeowner_msgs FROM xsupport_m1 m JOIN users u ON u.id=m.sender_id")->fetch(PDO::FETCH_ASSOC) ?: ['admin_msgs'=>0,'homeowner_msgs'=>0];
    $adminMsgs = (int) $supportMsgStats['admin_msgs'];
    $homeownerMsgs = (int) $supportMsgStats['homeowner_msgs'];
    ($adminMsgs >= 1 && $homeownerMsgs >= 1)
        ? pass($results, 'Support', 'Admin response path', "admin_msgs={$adminMsgs}, homeowner_msgs={$homeownerMsgs}")
        : fail($results, 'Support', 'Admin response path', 'major', "Missing side in support thread: admin={$adminMsgs}, homeowner={$homeownerMsgs}");

    $favApiPath = __DIR__ . '/../api/favorites.php';
    $msgApiPath = __DIR__ . '/../api/messages.php';
    $favApi = is_file($favApiPath) ? (string) file_get_contents($favApiPath) : '';
    $msgApi = is_file($msgApiPath) ? (string) file_get_contents($msgApiPath) : '';

    if ($favApi !== '' && strpos($favApi, 'json_encode') !== false && strpos($favApi, 'isLoggedIn()') !== false) {
        pass($results, 'API', 'favorites.php contract (static)', 'Found auth guard and JSON response');
    } else {
        fail($results, 'API', 'favorites.php contract (static)', 'major', 'Missing auth guard or JSON response pattern');
    }

    if ($msgApi !== '' && strpos($msgApi, 'json_encode') !== false && strpos($msgApi, "action === 'unread_count'") !== false) {
        pass($results, 'API', 'messages.php contract (static)', 'Found unread_count path and JSON response');
    } else {
        fail($results, 'API', 'messages.php contract (static)', 'major', 'Missing unread_count path or JSON response pattern');
    }

    $integrityQueries = [
        'offers.user_id -> users.id' => "SELECT COUNT(*) FROM offers o LEFT JOIN users u ON u.id=o.user_id WHERE u.id IS NULL",
        'offers.listing_id -> listings.id' => "SELECT COUNT(*) FROM offers o LEFT JOIN listings l ON l.id=o.listing_id WHERE l.id IS NULL",
        'fav_store_v2.user_id -> users.id' => "SELECT COUNT(*) FROM fav_store_v2 f LEFT JOIN users u ON u.id=f.user_id WHERE u.id IS NULL",
        'fav_store_v2.listing_id -> listings.id' => "SELECT COUNT(*) FROM fav_store_v2 f LEFT JOIN listings l ON l.id=f.listing_id WHERE l.id IS NULL",
        'tb_chat_messages sender/receiver exist' => "SELECT COUNT(*) FROM tb_chat_messages m LEFT JOIN users s ON s.id=m.sender_id LEFT JOIN users r ON r.id=m.receiver_id WHERE s.id IS NULL OR r.id IS NULL",
        'xsupport_m1.ticket_id -> xsupport_t1.id' => "SELECT COUNT(*) FROM xsupport_m1 m LEFT JOIN xsupport_t1 t ON t.id=m.ticket_id WHERE t.id IS NULL",
    ];

    foreach ($integrityQueries as $label => $sql) {
        $count = (int) $pdo->query($sql)->fetchColumn();
        $count === 0
            ? pass($results, 'SQL', $label, 'No orphan rows')
            : fail($results, 'SQL', $label, 'blocker', "Orphan row count: {$count}");
    }
} catch (Throwable $e) {
    fail($results, 'System', 'Regression runner fatal', 'blocker', $e->getMessage());
}

$failures = array_values(array_filter($results, static fn($r) => $r['status'] === 'FAIL'));
$blockers = array_values(array_filter($failures, static fn($r) => $r['severity'] === 'blocker'));
$majors = array_values(array_filter($failures, static fn($r) => $r['severity'] === 'major'));
$goNoGo = (count($blockers) === 0 && count($majors) === 0) ? 'GO' : 'NO-GO';

$now = new DateTimeImmutable();
$stamp = $now->format('Ymd_His');
$reportDir = __DIR__ . '/../reports';
if (!is_dir($reportDir)) {
    mkdir($reportDir, 0777, true);
}
$reportPath = $reportDir . "/go_no_go_{$stamp}.md";

$lines = [];
$lines[] = '# Go/No-Go Regression Report';
$lines[] = '';
$lines[] = '- Generated: ' . $now->format('Y-m-d H:i:s');
$lines[] = '- Decision: **' . $goNoGo . '**';
$lines[] = '- Total checks: ' . count($results);
$lines[] = '- Failures: ' . count($failures) . ' (blocker=' . count($blockers) . ', major=' . count($majors) . ')';
$lines[] = '';
$lines[] = '## Results';
$lines[] = '';
$lines[] = '| Module | Test | Status | Severity | Detail |';
$lines[] = '|---|---|---|---|---|';
foreach ($results as $r) {
    $lines[] = '| ' . $r['module'] . ' | ' . $r['test'] . ' | ' . $r['status'] . ' | ' . $r['severity'] . ' | ' . str_replace('|', '/', $r['detail']) . ' |';
}
$lines[] = '';
$lines[] = '## Triage';
if (!$failures) {
    $lines[] = '- No findings.';
} else {
    foreach ($failures as $f) {
        $lines[] = '- [' . $f['severity'] . '] ' . $f['module'] . ' / ' . $f['test'] . ': ' . $f['detail'];
    }
}

file_put_contents($reportPath, implode(PHP_EOL, $lines) . PHP_EOL);

echo "REGRESSION_DONE\n";
echo "Decision: {$goNoGo}\n";
echo "Report: {$reportPath}\n";
