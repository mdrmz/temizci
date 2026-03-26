-- Feature schema sync (idempotent)
-- Generated: 2026-03-26 17:15:00 Europe/Istanbul
-- Database: temizlik_burda

ALTER TABLE listings
    ADD COLUMN IF NOT EXISTS is_recurring TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE offers
    ADD COLUMN IF NOT EXISTS counter_price DECIMAL(10,2) NULL,
    ADD COLUMN IF NOT EXISTS counter_note VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS counter_status ENUM('none','pending','accepted','rejected') NOT NULL DEFAULT 'none';

CREATE TABLE IF NOT EXISTS offer_negotiations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    offer_id INT NOT NULL,
    actor_id INT NOT NULL,
    event_type ENUM('offer_sent','counter_sent','counter_accepted','counter_rejected','offer_accepted','offer_rejected') NOT NULL,
    note VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_offer_neg_offer (offer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS notif_in_app TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS notif_email TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS notif_telegram TINYINT(1) NOT NULL DEFAULT 1;

CREATE TABLE IF NOT EXISTS worker_service_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    title VARCHAR(120) NOT NULL,
    description VARCHAR(400) NULL,
    base_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_worker_pkg_worker (worker_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS listing_completion_proofs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL UNIQUE,
    accepted_worker_id INT NOT NULL,
    worker_confirmed TINYINT(1) NOT NULL DEFAULT 0,
    owner_confirmed TINYINT(1) NOT NULL DEFAULT 0,
    completion_code VARCHAR(6) NULL,
    proof_photo VARCHAR(255) NULL,
    worker_note TEXT NULL,
    owner_note TEXT NULL,
    worker_confirmed_at DATETIME NULL,
    owner_confirmed_at DATETIME NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE listing_completion_proofs
    ADD COLUMN IF NOT EXISTS completion_code VARCHAR(6) NULL;

CREATE TABLE IF NOT EXISTS listing_completion_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    completion_id INT NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_completion_photos_completion (completion_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tb_chat_typing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    contact_id INT NOT NULL,
    is_typing TINYINT(1) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_typing_pair (user_id, contact_id),
    KEY idx_typing_contact (contact_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
