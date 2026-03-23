<?php
// ============================================================
// Temizci Burada — Schema Update (Yeni Tablo ve Kolonlar)
// ============================================================

require_once __DIR__ . '/../includes/db.php';

echo "<h2>Veritabanı Güncellemesi Başlıyor...</h2>";

try {
    $db = getDB();

    // 1. messages tablosunu oluştur
    $sql_messages = "
    CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        listing_id INT DEFAULT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (sender_id),
        INDEX (receiver_id),
        INDEX (listing_id),
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $db->exec($sql_messages);
    echo "<p>✅ 'messages' tablosu oluşturuldu veya zaten var.</p>";

    // 2. availability tablosunu oluştur
    $sql_availability = "
    CREATE TABLE IF NOT EXISTS availability (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        available_date DATE NOT NULL,
        time_slot ENUM('sabah', 'ogle', 'aksam', 'tum_gun') DEFAULT 'tum_gun',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_date_slot (user_id, available_date, time_slot),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql_availability);
    echo "<p>✅ 'availability' tablosu oluşturuldu veya zaten var.</p>";

    // 3. favorites tablosunu oluştur
    $sql_favorites = "
    CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        listing_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_fav (user_id, listing_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql_favorites);
    echo "<p>✅ 'favorites' tablosu oluşturuldu veya zaten var.</p>";

    // 4. listings tablosuna is_recurring ekle
    try {
        $db->exec("ALTER TABLE listings ADD COLUMN is_recurring TINYINT(1) DEFAULT 0 AFTER budget;");
        echo "<p>✅ 'listings' tablosuna 'is_recurring' eklendi.</p>";
    } catch (PDOException $e) {
        // Kolon zaten var hatası 1060 ise yoksay, yoksa ekrana bas
        if ($e->errorInfo[1] !== 1060) {
            echo "<p>⚠️ Hata (Kolon Ekleme - listings): " . $e->getMessage() . "</p>";
        } else {
            echo "<p>ℹ️ 'listings' tablosunda 'is_recurring' zaten var.</p>";
        }
    }

    // 4. users tablosuna is_verified ekle
    try {
        $db->exec("ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0 AFTER status;");
        echo "<p>✅ 'users' tablosuna 'is_verified' eklendi.</p>";
    } catch (PDOException $e) {
        if ($e->errorInfo[1] !== 1060) {
            echo "<p>⚠️ Hata (Kolon Ekleme - users): " . $e->getMessage() . "</p>";
        } else {
            echo "<p>ℹ️ 'users' tablosunda 'is_verified' zaten var.</p>";
        }
    }

    // 5. users tablosuna telegram_chat_id ekle
    try {
        $db->exec("ALTER TABLE users ADD COLUMN telegram_chat_id VARCHAR(50) DEFAULT NULL AFTER email;");
        echo "<p>✅ 'users' tablosuna 'telegram_chat_id' eklendi.</p>";
    } catch (PDOException $e) {
        if ($e->errorInfo[1] !== 1060) {
            echo "<p>⚠️ Hata: " . $e->getMessage() . "</p>";
        } else {
            echo "<p>ℹ️ 'telegram_chat_id' zaten var.</p>";
        }
    }

    // 6. users tablosuna referans sistemi kolonları ekle
    try {
        $db->exec("ALTER TABLE users ADD COLUMN referral_code VARCHAR(20) DEFAULT NULL AFTER telegram_chat_id;");
        echo "<p>✅ 'users' tablosuna 'referral_code' eklendi.</p>";
    } catch (PDOException $e) {
        if ($e->errorInfo[1] !== 1060) echo "<p>⚠️ " . $e->getMessage() . "</p>";
        else echo "<p>ℹ️ 'referral_code' zaten var.</p>";
    }

    try {
        $db->exec("ALTER TABLE users ADD COLUMN referred_by INT DEFAULT NULL AFTER referral_code;");
        echo "<p>✅ 'users' tablosuna 'referred_by' eklendi.</p>";
    } catch (PDOException $e) {
        if ($e->errorInfo[1] !== 1060) echo "<p>⚠️ " . $e->getMessage() . "</p>";
        else echo "<p>ℹ️ 'referred_by' zaten var.</p>";
    }

    // 7. badges tablosu oluştur
    $sql_badges = "
    CREATE TABLE IF NOT EXISTS badges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        badge_type VARCHAR(50) NOT NULL,
        badge_name VARCHAR(100) NOT NULL,
        badge_icon VARCHAR(10) NOT NULL DEFAULT '🏆',
        earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_badge (user_id, badge_type),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql_badges);
    echo "<p>✅ 'badges' tablosu oluşturuldu veya zaten var.</p>";

    echo "<h3>🎉 Tüm güncellemeler başarıyla tamamlandı!</h3>";

} catch (PDOException $e) {
    echo "<h2>❌ Kritik Veritabanı Hatası:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
