<?php
// Force local credentials for CLI debugging
define('DB_HOST', 'localhost');
define('DB_NAME', 'temizlik_burda');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    echo "Connected to: " . DB_NAME . "\n";
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        try {
            // Use a simple query that should fail if the table is "missing from engine"
            $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
            echo "[OK] $table\n";
        } catch (PDOException $e) {
            echo "[ERROR] $table: " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
