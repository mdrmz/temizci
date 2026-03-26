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
        die("Dosya yedekleme hatasÄ± oluÅŸtu.");
    }
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yedekleme ve DÄ±ÅŸa Aktar â€” Admin Paneli</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=5.0">
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
                    <div class="page-subtitle">TÃ¼m verilerinizi ve dosyalarÄ±nÄ±zÄ± yerel bilgisayarÄ±nÄ±za indirmek iÃ§in aÅŸaÄŸÄ±daki araÃ§larÄ± kullanÄ±n.</div>
                </div>

                <div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                    
                    <!-- Database Backup -->
                    <div class="card" style="border-radius: 20px; border: 1px solid var(--border-light); padding: 30px; display: flex; flex-direction: column; align-items: center; text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 20px;">ğŸ—„ï¸</div>
                        <h3 style="font-weight: 800; margin-bottom: 10px;">VeritabanÄ±nÄ± Yedekle</h3>
                        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 24px;">TÃ¼m kullanÄ±cÄ±lar, ilanlar, mesajlar ve kategorileri iÃ§eren SQL formatÄ±nda bir dosya oluÅŸturur.</p>
                        <a href="?action=export_db" class="btn btn-primary btn-block" style="padding: 12px; font-weight: 700;">VeritabanÄ±nÄ± Ä°ndir (.sql)</a>
                    </div>

                    <!-- Files Backup -->
                    <div class="card" style="border-radius: 20px; border: 1px solid var(--border-light); padding: 30px; display: flex; flex-direction: column; align-items: center; text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 20px;">ğŸ“</div>
                        <h3 style="font-weight: 800; margin-bottom: 10px;">DosyalarÄ± Yedekle</h3>
                        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 24px;">Sitenin tÃ¼m kaynak kodlarÄ±nÄ± ve yÃ¼klenen resimleri iÃ§eren sÄ±kÄ±ÅŸtÄ±rÄ±lmÄ±ÅŸ bir ZIP arÅŸivi oluÅŸturur.</p>
                        <a href="?action=export_files" class="btn btn-outline btn-block" style="padding: 12px; font-weight: 700; border-color: var(--primary); color: var(--primary);">DosyalarÄ± Ä°ndir (.zip)</a>
                    </div>

                </div>

                <div class="alert alert-info mt-4" style="border-radius: 16px; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); color: #3b82f6; padding: 20px;">
                    <div style="display: flex; gap: 12px;">
                        <span style="font-size: 1.2rem;">ğŸ’¡</span>
                        <div style="font-size: 0.9rem; line-height: 1.5;">
                            <strong>Ä°pucu:</strong> BÃ¼yÃ¼k dosya yedeklemeleri sunucu performansÄ±na baÄŸlÄ± olarak birkaÃ§ saniye sÃ¼rebilir. Ä°ndirme baÅŸlamadan Ã¶nce lÃ¼tfen bekleyin.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="../assets/js/app.js?v=5.0"></script>
    <script src="../assets/js/theme.js"></script>
</body>
</html>


