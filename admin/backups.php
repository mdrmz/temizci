<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/config.php';

// Export Database (SQL)
if (isset($_GET['action']) && $_GET['action'] === 'export_db') {
    if (!verifyCsrf() && !isset($_GET['token'])) { // Basic check
         die('CSRF token missing');
    }

    $dbName = $_ENV['DB_NAME'];
    $dbUser = $_ENV['DB_USER'];
    $dbPass = $_ENV['DB_PASS'];
    $filename = 'db_backup_' . date('Y-m-d_H-i-s') . '.sql';

    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $command = "mysqldump --user=" . escapeshellarg($dbUser) . 
               " --password=" . escapeshellarg($dbPass) . 
               " " . escapeshellarg($dbName);
    
    passthru($command);
    exit;
}

// Export Files (ZIP)
if (isset($_GET['action']) && $_GET['action'] === 'export_files') {
    $tempZip = '/tmp/website_backup_' . time() . '.zip';
    $webRoot = realpath(__DIR__ . '/../');
    
    // Create ZIP using system command
    $cmd = "zip -r " . escapeshellarg($tempZip) . " " . escapeshellarg($webRoot) . " -x '*/node_modules/*' '*/.git/*' '*/.gemini/*'";
    exec($cmd);

    if (file_exists($tempZip)) {
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="website_files_' . date('Y-m-d') . '.zip"');
        header('Content-Length: ' . filesize($tempZip));
        readfile($tempZip);
        unlink($tempZip); // Delete temp file
        exit;
    } else {
        die("Dosya yedekleme hatası oluştu.");
    }
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yedekleme ve Dışa Aktar — Admin Paneli</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="../assets/css/style.css?v=4.0">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <link rel="icon" href="/logo.png" type="image/png">
</head>
<body>
    <div class="app-layout">
        
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="main-content">
            <?php $headerTitle = 'Yedekleme Merkezi'; include __DIR__ . '/includes/header.php'; ?>

            <div class="page-content">
                <?= flashHtml() ?>
                
                <div class="page-header" style="margin-bottom: 2rem;">
                    <div class="page-title">Sistem Yedekleme</div>
                    <div class="page-subtitle">Tüm verilerinizi ve dosyalarınızı yerel bilgisayarınıza indirmek için aşağıdaki araçları kullanın.</div>
                </div>

                <div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                    
                    <!-- Database Backup -->
                    <div class="card" style="border-radius: 20px; border: 1px solid var(--border-light); padding: 30px; display: flex; flex-direction: column; align-items: center; text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 20px;">🗄️</div>
                        <h3 style="font-weight: 800; margin-bottom: 10px;">Veritabanını Yedekle</h3>
                        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 24px;">Tüm kullanıcılar, ilanlar, mesajlar ve kategorileri içeren SQL formatında bir dosya oluşturur.</p>
                        <a href="?action=export_db" class="btn btn-primary btn-block" style="padding: 12px; font-weight: 700;">Veritabanını İndir (.sql)</a>
                    </div>

                    <!-- Files Backup -->
                    <div class="card" style="border-radius: 20px; border: 1px solid var(--border-light); padding: 30px; display: flex; flex-direction: column; align-items: center; text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 20px;">📁</div>
                        <h3 style="font-weight: 800; margin-bottom: 10px;">Dosyaları Yedekle</h3>
                        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 24px;">Sitenin tüm kaynak kodlarını ve yüklenen resimleri içeren sıkıştırılmış bir ZIP arşivi oluşturur.</p>
                        <a href="?action=export_files" class="btn btn-outline btn-block" style="padding: 12px; font-weight: 700; border-color: var(--primary); color: var(--primary);">Dosyaları İndir (.zip)</a>
                    </div>

                </div>

                <div class="alert alert-info mt-4" style="border-radius: 16px; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); color: #3b82f6; padding: 20px;">
                    <div style="display: flex; gap: 12px;">
                        <span style="font-size: 1.2rem;">💡</span>
                        <div style="font-size: 0.9rem; line-height: 1.5;">
                            <strong>İpucu:</strong> Büyük dosya yedeklemeleri sunucu performansına bağlı olarak birkaç saniye sürebilir. İndirme başlamadan önce lütfen bekleyin.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="../assets/js/app.js?v=4.0"></script>
    <script src="../assets/js/theme.js"></script>
</body>
</html>
