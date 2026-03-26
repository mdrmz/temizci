<?php
/**
 * Temizci Burada - Veritabanı Onarım ve Sıfırlama Scripti
 * Bu script tüm tabloları siler ve schema.sql + seed.sql dosyalarını çalıştırır.
 */

// Host'a bağlan (Veritabanı seçmeden önce)
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'temizlik_burda';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    echo "1. Veritabanı sıfırlanıyor: $dbname...\n";
    $pdo->exec("DROP DATABASE IF EXISTS `$dbname` ");
    $pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname` ");
    echo "   [OK] Veritabanı yeniden oluşturuldu.\n\n";

    echo "2. Şema yükleniyor (schema.sql)...\n";
    $schemaFile = __DIR__ . '/database/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("schema.sql bulunamadı!");
    }
    $schemaSql = file_get_contents($schemaFile);
    
    // Agresif mod: CREATE TABLE'dan önce DROP TABLE ekle ve IF NOT EXISTS'i kaldır
    $schemaSql = preg_replace('/CREATE TABLE IF NOT EXISTS/i', 'CREATE TABLE', $schemaSql);
    
    // Tablo isimlerini bulup DROP ekleyelim
    preg_match_all('/CREATE TABLE\s+(`?\w+`?)/i', $schemaSql, $matches);
    foreach ($matches[1] as $tableName) {
        echo "      - Eskiden kalma tablo temizleniyor: $tableName\n";
        try {
            $pdo->exec("DROP TABLE IF EXISTS $tableName");
        } catch (Exception $e) {
            // Hata olsa da devam et (zaten engine'de yoksa hata verir ama biz siliyoruz)
        }
    }

    executeMultiSql($pdo, $schemaSql);
    echo "   [OK] Tablolar oluşturuldu.\n\n";

    echo "3. Test verileri yükleniyor (seed.sql)...\n";
    $seedFile = __DIR__ . '/database/seed.sql';
    if (file_exists($seedFile)) {
        $seedSql = file_get_contents($seedFile);
        executeMultiSql($pdo, $seedSql);
        echo "   [OK] Test verileri yüklendi.\n\n";
    } else {
        echo "   [!] seed.sql bulunamadı, atlanıyor.\n\n";
    }

    echo "Tebrikler! Veritabanı başarıyla onarıldı ve sıfırlandı.\n";

} catch (Exception $e) {
    echo "\n[HATA] İşlem başarısız oldu:\n";
    echo $e->getMessage() . "\n";
    exit(1);
}

/**
 * SQL metnini deyimlere bölüp tek tek çalıştırır.
 */
function executeMultiSql($pdo, $sql) {
    // UTF-8 BOM temizle (\xEF\xBB\xBF)
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
    
    // Yorumları temizle (isteğe bağlı ama güvenli)
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    $statements = explode(';', $sql);
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if ($stmt !== '') {
            try {
                // Sadece ilk 50 karakteri logla
                $short = substr($stmt, 0, 50) . (strlen($stmt) > 50 ? '...' : '');
                echo "      + Çalıştırılıyor: $short\n";
                $pdo->exec($stmt);
            } catch (Exception $e) {
                echo "      [!] HATA: " . $e->getMessage() . "\n";
                echo "      SQL: $stmt\n";
                throw $e;
            }
        }
    }
}
