-- ============================================================
-- Temizci Burada — Kapsamlı Veritabanı Kurulum Dosyası (Full Setup)
-- Bu dosya tüm şema, tablo ve test verilerini içerir.
-- NOT: Mevcut veritabanını SİLİP (reset) baştan kurar.
-- ============================================================

DROP DATABASE IF EXISTS temizlik_burda;
CREATE DATABASE IF NOT EXISTS temizlik_burda CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE temizlik_burda;

-- 1. KULLANICILAR (users)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('homeowner','worker') DEFAULT 'homeowner',
    avatar VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    rating DECIMAL(3,2) DEFAULT 0.00,
    review_count INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    is_verified TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. EVLER (homes)
CREATE TABLE IF NOT EXISTS homes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    address TEXT NOT NULL,
    district VARCHAR(100),
    city VARCHAR(100) NOT NULL,
    room_config VARCHAR(20) NOT NULL COMMENT '2+1, 3+1, 4+2 vb.',
    floor INT DEFAULT 0,
    has_elevator TINYINT(1) DEFAULT 0,
    bathroom_count INT DEFAULT 1,
    sqm INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. KATEGORİLER (categories)
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (name, icon, slug) VALUES
('Ev Temizliği', '🧹', 'ev-temp-ev-temizligi'),
('Cam & Pencere', '🪟', 'cam-pencere'),
('Ütü & Çamaşır', '👕', 'utu-camasir'),
('Bulaşık', '🍽️', 'bulasik'),
('Bahçe', '🌿', 'bahce'),
('Koltuk Yıkama', '🛋️', 'koltuk-yikama'),
('Genel Temizlik', '✨', 'genel-temizlik'),
('Diğer', '📋', 'diger')
ON DUPLICATE KEY UPDATE name=name;

-- 4. İLANLAR (listings)
CREATE TABLE IF NOT EXISTS listings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    home_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    preferred_date DATE NOT NULL,
    preferred_time ENUM('sabah','ogle','aksam','esnek') DEFAULT 'esnek',
    owner_home TINYINT(1) DEFAULT 1 COMMENT 'Ev sahibi evde mi?',
    budget DECIMAL(10,2) DEFAULT NULL,
    status ENUM('open','in_progress','closed','cancelled') DEFAULT 'open',
    view_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (home_id) REFERENCES homes(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TEKLİFLER (offers)
CREATE TABLE IF NOT EXISTS offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    user_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending','accepted','rejected') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. YORUMLAR (reviews)
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    rating INT NOT NULL CHECK(rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. BİLDİRİMLER (notifications)
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    message VARCHAR(255) NOT NULL,
    link VARCHAR(255) NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. DESTEK TALEPLERİ (tickets & ticket_messages)
CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ticket_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    sender_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. FAVORİLER (favorites)
CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    listing_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_fav (user_id, listing_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. API TOKENS & SECURITY (api_tokens, login_attempts)
CREATE TABLE IF NOT EXISTS api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(150) NOT NULL,
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_email (ip_address, email),
    INDEX idx_attempted_at (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TEST VERİLERİ (SEED DATA)
-- ============================================================

INSERT INTO users (name, email, phone, password, role, city, bio, is_active) VALUES
('Ayşe Kara', 'evsahibi@test.com', '05001234567', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TnXb1zG3JmH5jP2yGkJcHd4mLNJe', 'homeowner', 'İstanbul', 'İstanbul Kadıköyde yaşıyorum.', 1),
('Mehmet Yılmaz', 'temizlikci@test.com', '05359876543', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TnXb1zG3JmH5jP2yGkJcHd4mLNJe', 'worker', 'İstanbul', '10 yıldır ev temizliği yapıyorum. Referanslarım mevcuttur.', 1)
ON DUPLICATE KEY UPDATE email=email;

INSERT INTO homes (user_id, title, address, district, city, room_config, floor, has_elevator, bathroom_count, sqm, notes)
SELECT id, '3+1 Dairem', 'Moda Caddesi No:15', 'Kadıköy', 'İstanbul', '3+1', 4, 1, 1, 110, 'Balkon var, köpek yok.'
FROM users WHERE email = 'evsahibi@test.com' LIMIT 1;
