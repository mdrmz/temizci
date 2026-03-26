<?php
declare(strict_types=1);

/**
 * Idempotent QA seed for Temizlik Burda.
 * Safe to run repeatedly, does not reset the database.
 */

$pdo = new PDO(
    'mysql:host=localhost;dbname=temizlik_burda;charset=utf8mb4',
    'root',
    '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$passwordPlain = 'Test123!';
$passwordHash = password_hash($passwordPlain, PASSWORD_DEFAULT);

try {
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('homeowner','worker','admin') DEFAULT 'homeowner'");
} catch (Throwable $e) {
    // keep current role enum if not possible
}

$pdo->exec("
    CREATE TABLE IF NOT EXISTS fav_store_v2 (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        listing_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_fav_store_v2 (user_id, listing_id),
        KEY idx_fav_store_user (user_id),
        KEY idx_fav_store_listing (listing_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS tb_chat_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        listing_id INT DEFAULT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_sender (sender_id),
        KEY idx_receiver (receiver_id),
        KEY idx_listing (listing_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS xsupport_t1 (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_xsupport_t1_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS xsupport_m1 (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        sender_id INT NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_xsupport_m1_ticket (ticket_id),
        KEY idx_xsupport_m1_sender (sender_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$categories = [
    ['Ev Temizligi', 'ET', 'ev-temizligi'],
    ['Cam ve Pencere', 'CP', 'cam-pencere'],
    ['Utu ve Camasir', 'UC', 'utu-camasir'],
    ['Genel Temizlik', 'GT', 'genel-temizlik'],
];

$catIdBySlug = [];
$selectCategory = $pdo->prepare("SELECT id FROM categories WHERE slug = ? LIMIT 1");
$insertCategory = $pdo->prepare("INSERT INTO categories (name, icon, slug) VALUES (?, ?, ?)");

foreach ($categories as [$name, $icon, $slug]) {
    $selectCategory->execute([$slug]);
    $id = (int) $selectCategory->fetchColumn();
    if ($id === 0) {
        $insertCategory->execute([$name, $icon, $slug]);
        $id = (int) $pdo->lastInsertId();
    }
    $catIdBySlug[$slug] = $id;
}

function upsertUser(PDO $pdo, array $data): int
{
    $select = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $select->execute([$data['email']]);
    $id = (int) $select->fetchColumn();

    if ($id > 0) {
        $update = $pdo->prepare("
            UPDATE users
            SET name = ?, phone = ?, password = ?, role = ?, city = ?, bio = ?, is_active = 1
            WHERE id = ?
        ");
        $update->execute([
            $data['name'],
            $data['phone'],
            $data['password'],
            $data['role'],
            $data['city'],
            $data['bio'],
            $id,
        ]);
        return $id;
    }

    $insert = $pdo->prepare("
        INSERT INTO users (name, email, phone, password, role, city, bio, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1)
    ");
    $insert->execute([
        $data['name'],
        $data['email'],
        $data['phone'],
        $data['password'],
        $data['role'],
        $data['city'],
        $data['bio'],
    ]);

    return (int) $pdo->lastInsertId();
}

$homeownerId = upsertUser($pdo, [
    'name' => 'QA Homeowner',
    'email' => 'qa.homeowner@temizlikburda.local',
    'phone' => '05000000001',
    'password' => $passwordHash,
    'role' => 'homeowner',
    'city' => 'Istanbul',
    'bio' => 'QA homeowner account',
]);

$workerId = upsertUser($pdo, [
    'name' => 'QA Worker',
    'email' => 'qa.worker@temizlikburda.local',
    'phone' => '05000000002',
    'password' => $passwordHash,
    'role' => 'worker',
    'city' => 'Istanbul',
    'bio' => 'QA worker account',
]);

$adminId = upsertUser($pdo, [
    'name' => 'QA Admin',
    'email' => 'qa.admin@temizlikburda.local',
    'phone' => '05000000003',
    'password' => $passwordHash,
    'role' => 'admin',
    'city' => 'Istanbul',
    'bio' => 'QA admin account',
]);

$homeSelect = $pdo->prepare("SELECT id FROM homes WHERE user_id = ? AND title = ? LIMIT 1");
$homeInsert = $pdo->prepare("
    INSERT INTO homes (user_id, title, address, district, city, room_config, floor, has_elevator, bathroom_count, sqm, notes, is_active)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
");

$homeTitle = 'QA Home 3+1';
$homeSelect->execute([$homeownerId, $homeTitle]);
$homeId = (int) $homeSelect->fetchColumn();
if ($homeId === 0) {
    $homeInsert->execute([
        $homeownerId,
        $homeTitle,
        'QA Mahallesi Test Sokak No:1',
        'Kadikoy',
        'Istanbul',
        '3+1',
        2,
        1,
        1,
        120,
        'QA seeded home',
    ]);
    $homeId = (int) $pdo->lastInsertId();
}

$listingSelect = $pdo->prepare("SELECT id FROM listings WHERE user_id = ? AND title = ? LIMIT 1");
$listingInsert = $pdo->prepare("
    INSERT INTO listings (user_id, home_id, category_id, title, description, preferred_date, preferred_time, budget, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$today = new DateTimeImmutable('today');

$listingData = [
    [
        'title' => 'QA Acik Ilan',
        'status' => 'open',
        'cat' => $catIdBySlug['genel-temizlik'] ?? 1,
        'date' => $today->modify('+2 days')->format('Y-m-d'),
        'time' => 'sabah',
        'budget' => 750,
    ],
    [
        'title' => 'QA Kapali Ilan',
        'status' => 'closed',
        'cat' => $catIdBySlug['ev-temizligi'] ?? 1,
        'date' => $today->modify('-4 days')->format('Y-m-d'),
        'time' => 'ogle',
        'budget' => 650,
    ],
];

$listingIds = [];
foreach ($listingData as $row) {
    $listingSelect->execute([$homeownerId, $row['title']]);
    $listingId = (int) $listingSelect->fetchColumn();

    if ($listingId === 0) {
        $listingInsert->execute([
            $homeownerId,
            $homeId,
            $row['cat'],
            $row['title'],
            'QA seeded listing for full regression',
            $row['date'],
            $row['time'],
            $row['budget'],
            $row['status'],
        ]);
        $listingId = (int) $pdo->lastInsertId();
    } else {
        $updateListing = $pdo->prepare("
            UPDATE listings
            SET status = ?, preferred_date = ?, preferred_time = ?, budget = ?
            WHERE id = ?
        ");
        $updateListing->execute([$row['status'], $row['date'], $row['time'], $row['budget'], $listingId]);
    }

    $listingIds[$row['title']] = $listingId;
}

$openListingId = $listingIds['QA Acik Ilan'];
$closedListingId = $listingIds['QA Kapali Ilan'];

$offerSelect = $pdo->prepare("SELECT id FROM offers WHERE listing_id = ? AND user_id = ? LIMIT 1");
$offerUpsert = $pdo->prepare("
    INSERT INTO offers (listing_id, user_id, price, message, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?)
");

$offerSelect->execute([$openListingId, $workerId]);
$offerId = (int) $offerSelect->fetchColumn();
if ($offerId === 0) {
    $offerUpsert->execute([
        $openListingId,
        $workerId,
        700,
        'QA teklif: yarin sabah uygun.',
        'pending',
        $today->format('Y-m-d') . ' 09:00:00',
    ]);
}

$favSelect = $pdo->prepare("SELECT id FROM fav_store_v2 WHERE user_id = ? AND listing_id = ? LIMIT 1");
$favInsert = $pdo->prepare("INSERT INTO fav_store_v2 (user_id, listing_id, created_at) VALUES (?, ?, ?)");

$favSelect->execute([$workerId, $openListingId]);
if (!(int) $favSelect->fetchColumn()) {
    $favInsert->execute([$workerId, $openListingId, $today->format('Y-m-d') . ' 10:00:00']);
}

$msgCheck = $pdo->prepare("SELECT COUNT(*) FROM tb_chat_messages WHERE sender_id = ? AND receiver_id = ? AND listing_id = ?");
$msgInsert = $pdo->prepare("
    INSERT INTO tb_chat_messages (sender_id, receiver_id, listing_id, message, is_read, created_at)
    VALUES (?, ?, ?, ?, ?, ?)
");

$msgCheck->execute([$homeownerId, $workerId, $openListingId]);
if ((int) $msgCheck->fetchColumn() === 0) {
    $msgInsert->execute([$homeownerId, $workerId, $openListingId, 'Merhaba, yarin 09:00 uygun musunuz?', 1, $today->format('Y-m-d') . ' 11:00:00']);
    $msgInsert->execute([$workerId, $homeownerId, $openListingId, 'Evet uygunum, detaylari bekliyorum.', 0, $today->format('Y-m-d') . ' 11:05:00']);
}

$ticketSelect = $pdo->prepare("SELECT id FROM xsupport_t1 WHERE user_id = ? AND subject = ? LIMIT 1");
$ticketInsert = $pdo->prepare("INSERT INTO xsupport_t1 (user_id, subject, status, created_at) VALUES (?, ?, ?, ?)");
$ticketSubject = 'QA Destek Talebi';

$ticketSelect->execute([$homeownerId, $ticketSubject]);
$ticketId = (int) $ticketSelect->fetchColumn();
if ($ticketId === 0) {
    $ticketInsert->execute([$homeownerId, $ticketSubject, 'open', $today->format('Y-m-d') . ' 12:00:00']);
    $ticketId = (int) $pdo->lastInsertId();
}

$ticketMsgCheck = $pdo->prepare("SELECT COUNT(*) FROM xsupport_m1 WHERE ticket_id = ?");
$ticketMsgInsert = $pdo->prepare("INSERT INTO xsupport_m1 (ticket_id, sender_id, message, created_at) VALUES (?, ?, ?, ?)");

$ticketMsgCheck->execute([$ticketId]);
if ((int) $ticketMsgCheck->fetchColumn() === 0) {
    $ticketMsgInsert->execute([$ticketId, $homeownerId, 'QA: destek test mesaji', $today->format('Y-m-d') . ' 12:01:00']);
    $ticketMsgInsert->execute([$ticketId, $adminId, 'QA: destek geri donus', $today->format('Y-m-d') . ' 12:05:00']);
}

$summary = [
    'users' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE email LIKE 'qa.%@temizlikburda.local'")->fetchColumn(),
    'homes' => (int) $pdo->query("SELECT COUNT(*) FROM homes WHERE title = 'QA Home 3+1'")->fetchColumn(),
    'listings' => (int) $pdo->query("SELECT COUNT(*) FROM listings WHERE title IN ('QA Acik Ilan','QA Kapali Ilan')")->fetchColumn(),
    'offers' => (int) $pdo->query("SELECT COUNT(*) FROM offers WHERE listing_id IN ($openListingId, $closedListingId)")->fetchColumn(),
    'favorites' => (int) $pdo->query("SELECT COUNT(*) FROM fav_store_v2 WHERE listing_id = $openListingId")->fetchColumn(),
    'messages' => (int) $pdo->query("SELECT COUNT(*) FROM tb_chat_messages WHERE listing_id = $openListingId")->fetchColumn(),
    'support_tickets' => (int) $pdo->query("SELECT COUNT(*) FROM xsupport_t1 WHERE id = $ticketId")->fetchColumn(),
    'support_messages' => (int) $pdo->query("SELECT COUNT(*) FROM xsupport_m1 WHERE ticket_id = $ticketId")->fetchColumn(),
];

echo "SEED_OK\n";
echo "QA credentials:\n";
echo " - homeowner: qa.homeowner@temizlikburda.local / {$passwordPlain}\n";
echo " - worker: qa.worker@temizlikburda.local / {$passwordPlain}\n";
echo " - admin: qa.admin@temizlikburda.local / {$passwordPlain}\n";
echo "Summary:\n";
foreach ($summary as $k => $v) {
    echo " - {$k}: {$v}\n";
}
