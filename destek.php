<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

$user = currentUser();
$db = getDB();

$db->exec("
    CREATE TABLE IF NOT EXISTS xsupport_t1 (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
        priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
        admin_note TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_xsupport_t1_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$ticketColumns = $db->query("SHOW COLUMNS FROM xsupport_t1")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('priority', $ticketColumns, true)) {
    $db->exec("ALTER TABLE xsupport_t1 ADD COLUMN priority ENUM('low', 'normal', 'high') DEFAULT 'normal' AFTER status");
}
if (!in_array('admin_note', $ticketColumns, true)) {
    $db->exec("ALTER TABLE xsupport_t1 ADD COLUMN admin_note TEXT NULL AFTER priority");
}

$db->exec("
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_ticket') {
    if (verifyCsrf()) {
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $priority = $_POST['priority'] ?? 'normal';
        if (!in_array($priority, ['low', 'normal', 'high'], true)) {
            $priority = 'normal';
        }
        
        if ($subject && $message) {
            $db->prepare("INSERT INTO xsupport_t1 (user_id, subject, priority) VALUES (?, ?, ?)")->execute([$user['id'], $subject, $priority]);
            $ticketId = $db->lastInsertId();
            
            $db->prepare("INSERT INTO xsupport_m1 (ticket_id, sender_id, message) VALUES (?, ?, ?)")->execute([$ticketId, $user['id'], $message]);
            
            setFlash('success', 'Destek talebiniz oluşturuldu. En kısa sürede yanıtlanacaktır.');
            redirect('destek');
        }
    }
}

// Talepleri getir
$stmt = $db->prepare("SELECT * FROM xsupport_t1 WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$tickets = $stmt->fetchAll();

$initials = strtoupper(substr($user['name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destek Merkezi  -  Temizci Burada</title>
    <link rel="stylesheet" href="assets/css/style.css?v=5.0">
    <link rel="stylesheet" href="assets/css/dark-mode.css">
    <link rel="icon" href="/logo.png" type="image/png">
</head>
<body>
    <div class="app-layout">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <?php $headerTitle = 'Destek Merkezi'; include 'includes/app-header.php'; ?>

            <div class="page-content">
                <?= flashHtml() ?>
                <div class="page-title">Destek Taleplerim</div>
                <div class="page-subtitle">Sorunlarınızı buradan bildirin, ekibimiz size yardımcı olsun.</div>

                <div class="grid-2 mt-4" style="grid-template-columns: 1fr 340px; gap:24px;">
                    
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Mevcut Talepler</div>
                        </div>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Konu</th>
                                        <th>Durum</th>
                                        <th>Öncelik</th>
                                        <th>Tarih</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($tickets)): ?>
                                        <tr><td colspan="6" style="text-align:center; padding:30px; color:var(--text-muted);">Henüz bir destek talebiniz yok.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach($tickets as $t): ?>
                                    <tr>
                                        <td>#<?= $t['id'] ?></td>
                                        <td style="font-weight:600;"><?= e($t['subject']) ?></td>
                                        <td>
                                            <span class="badge <?= $t['status'] === 'open' ? 'badge-open' : ($t['status'] == 'in_progress' ? 'badge-progress' : 'badge-closed') ?>">
                                                <?= $t['status'] === 'open' ? 'Açık' : ($t['status'] === 'in_progress' ? 'İşlemde' : 'Kapandı') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $t['priority'] === 'high' ? 'badge-closed' : ($t['priority'] === 'low' ? 'badge-open' : 'badge-progress') ?>">
                                                <?= $t['priority'] === 'high' ? 'Yüksek' : ($t['priority'] === 'low' ? 'Düşük' : 'Normal') ?>
                                            </span>
                                        </td>
                                        <td style="font-size:0.8rem;"><?= date('d.m.Y H:i', strtotime($t['created_at'])) ?></td>
                                        <td><a href="ticket_detail?id=<?= $t['id'] ?>" class="btn btn-ghost btn-sm">Görüntüle</a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Yeni Destek Talebi</div>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="create_ticket">
                                
                                <div class="form-group">
                                    <label class="form-label">Konu</label>
                                    <input type="text" name="subject" class="form-control" placeholder="Örn: Ödeme sorunu" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Mesajınız</label>
                                    <textarea name="message" class="form-control" rows="5" placeholder="Lütfen detaylıca açıklayın..." required></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Öncelik</label>
                                    <select name="priority" class="form-control">
                                        <option value="normal">Normal</option>
                                        <option value="high">Yüksek</option>
                                        <option value="low">Düşük</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Talebi Gönder</button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="assets/js/app.js?v=5.0"></script>
    <script src="assets/js/theme.js"></script>
</body>
</html>



