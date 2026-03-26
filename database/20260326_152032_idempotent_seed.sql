-- Idempotent QA seed (non-destructive)
-- Generated: 2026-03-26 15:20:32 Europe/Istanbul
USE `temizlik_burda`;

CREATE TABLE IF NOT EXISTS `fav_store_v2` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `listing_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_fav_store_v2` (`user_id`, `listing_id`),
    KEY `idx_fav_store_user` (`user_id`),
    KEY `idx_fav_store_listing` (`listing_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tb_chat_messages` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `xsupport_t1` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `status` ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_xsupport_t1_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `xsupport_m1` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL,
    `sender_id` INT NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_xsupport_m1_ticket` (`ticket_id`),
    KEY `idx_xsupport_m1_sender` (`sender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (`name`,`email`,`phone`,`password`,`role`,`city`,`bio`,`is_active`)
VALUES
('QA Homeowner','qa.homeowner@temizlikburda.local','05000000001','$2y$10$Qw6Dk8F4E0rh0B0OVp9X0OLMn2PQ4j8KQ6B6Q6A00F1ca6v8A0dAu','homeowner','Istanbul','QA homeowner account',1),
('QA Worker','qa.worker@temizlikburda.local','05000000002','$2y$10$Qw6Dk8F4E0rh0B0OVp9X0OLMn2PQ4j8KQ6B6Q6A00F1ca6v8A0dAu','worker','Istanbul','QA worker account',1),
('QA Admin','qa.admin@temizlikburda.local','05000000003','$2y$10$Qw6Dk8F4E0rh0B0OVp9X0OLMn2PQ4j8KQ6B6Q6A00F1ca6v8A0dAu','admin','Istanbul','QA admin account',1)
ON DUPLICATE KEY UPDATE
`name`=VALUES(`name`),
`phone`=VALUES(`phone`),
`role`=VALUES(`role`),
`city`=VALUES(`city`),
`bio`=VALUES(`bio`),
`is_active`=1;
