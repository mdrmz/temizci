<?php
declare(strict_types=1);

$pdo = new PDO(
    'mysql:host=localhost;dbname=temizlik_burda;charset=utf8mb4',
    'root',
    '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$queries = [
    "CREATE TABLE IF NOT EXISTS `tb_chat_messages` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `sender_id` INT NOT NULL,
        `receiver_id` INT NOT NULL,
        `listing_id` INT DEFAULT NULL,
        `message` TEXT NOT NULL,
        `is_read` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY `idx_sender` (`sender_id`),
        KEY `idx_receiver` (`receiver_id`),
        KEY `idx_listing` (`listing_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `xsupport_t1` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `subject` VARCHAR(255) NOT NULL,
        `status` ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY `idx_tickets_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `xsupport_m1` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `ticket_id` INT NOT NULL,
        `sender_id` INT NOT NULL,
        `message` TEXT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY `idx_ticket_id` (`ticket_id`),
        KEY `idx_sender_id` (`sender_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `fav_store_v2` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `listing_id` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_fav` (`user_id`, `listing_id`),
        KEY `idx_favorites_user` (`user_id`),
        KEY `idx_favorites_listing` (`listing_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

foreach ($queries as $query) {
    $pdo->exec($query);
}

$pdo->exec("DROP TABLE IF EXISTS `test_tmp_codex`");

echo "MIGRATION_APPLIED\n";

foreach (['fav_store_v2', 'tb_chat_messages', 'xsupport_t1', 'xsupport_m1'] as $table) {
    $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
    echo ($stmt->fetchColumn() ? "[OK] " : "[MISSING] ") . $table . PHP_EOL;
}
