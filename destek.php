<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

$user = currentUser();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_ticket') {
    if (verifyCsrf()) {
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        if ($subject && $message) {
            $db->prepare("INSERT INTO tickets (user_id, subject) VALUES (?, ?)")->execute([$user['id'], $subject]);
            $ticketId = $db->lastInsertId();
            
            $db->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message) VALUES (?, ?, ?)")->execute([$ticketId, $user['id'], $message]);
            
            setFlash('success', 'Destek talebiniz oluşturuldu. En kısa sürede yanıtlanacaktır.');
            redirect('destek');
        }
    }
}

// Talepleri getir
$stmt = $db->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$tickets = $stmt->fetchAll();

$initials = strtoupper(substr($user['name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destek Merkezi — Temizci Burada</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="assets/css/style.css?v=4.0">
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
                                        <th>Tarih</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($tickets)): ?>
                                        <tr><td colspan="5" style="text-align:center; padding:30px; color:var(--text-muted);">Henüz bir destek talebiniz yok.</td></tr>
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
                                <button type="submit" class="btn btn-primary btn-block">Talebi Gönder</button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="assets/js/app.js?v=4.0"></script>
    <script src="assets/js/theme.js"></script>
</body>
</html>
