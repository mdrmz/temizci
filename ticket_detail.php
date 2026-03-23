<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

$user = currentUser();
$db = getDB();
$id = (int)($_GET['id'] ?? 0);

// Bileti getir (kullanıcıya ait mi kontrol et)
$stmt = $db->prepare("SELECT * FROM tickets WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user['id']]);
$ticket = $stmt->fetch();

if (!$ticket) redirect(APP_URL . '/destek.php');

// Yanıt gönder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $ticket['status'] !== 'closed') {
    if (verifyCsrf()) {
        $message = trim($_POST['message']);
        if ($message) {
            $db->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message) VALUES (?, ?, ?)")->execute([$id, $user['id'], $message]);
            setFlash('success', 'Yanıtınız eklendi.');
            redirect('ticket_detail?id=' . $id);
        }
    }
}

// Mesajları getir
$msgStmt = $db->prepare("SELECT tm.*, u.name AS sender_name, u.role FROM ticket_messages tm JOIN users u ON tm.sender_id = u.id WHERE tm.ticket_id = ? ORDER BY tm.created_at ASC");
$msgStmt->execute([$id]);
$messages = $msgStmt->fetchAll();

$initials = strtoupper(substr($user['name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destek Talebi #<?= $id ?> — Temizci Burada</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="assets/css/style.css?v=4.0">
    <link rel="stylesheet" href="assets/css/dark-mode.css">
    <link rel="icon" href="/logo.png" type="image/png">
</head>
<body>
    <div class="app-layout">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main-content">
            <?php $headerTitle = 'Talep Detayı'; include 'includes/app-header.php'; ?>

            <div class="page-content" style="max-width:800px; margin:0 auto;">
                <?= flashHtml() ?>
                <div style="margin-bottom:24px; display:flex; align-items:center; justify-content:space-between;">
                    <div>
                        <a href="destek" style="color:var(--text-muted); font-size:0.85rem; text-decoration:none;">← Geri Dön</a>
                        <h1 class="page-title" style="margin-top:10px;">#<?= $id ?> - <?= e($ticket['subject']) ?></h1>
                    </div>
                    <div>
                        <span class="badge <?= $ticket['status'] === 'open' ? 'badge-open' : ($ticket['status'] == 'in_progress' ? 'badge-progress' : 'badge-closed') ?>">
                            <?= $ticket['status'] === 'open' ? 'Açık' : ($ticket['status'] === 'in_progress' ? 'İşlemde' : 'Kapandı') ?>
                        </span>
                    </div>
                </div>

                <div class="ticket-chat" style="display:flex; flex-direction:column; gap:20px; margin-bottom:40px;">
                    <?php foreach($messages as $m): ?>
                        <div class="ticket-message" style="max-width:85%; <?= $m['sender_id'] == $user['id'] ? 'align-self: flex-end;' : 'align-self: flex-start;' ?>">
                            <div style="font-size:0.75rem; color:var(--text-muted); margin-bottom:6px; display:flex; gap:8px; <?= $m['sender_id'] == $user['id'] ? 'justify-content: flex-end;' : '' ?>">
                                <strong><?= e($m['sender_name']) ?></strong>
                                <?php if($m['role'] === 'admin'): ?> <span class="badge badge-progress" style="font-size:0.6rem; padding:2px 4px;">DESTEK</span> <?php endif; ?>
                                <span><?= date('d M H:i', strtotime($m['created_at'])) ?></span>
                            </div>
                            <div style="padding:16px; border-radius:12px; background: <?= $m['sender_id'] == $user['id'] ? 'var(--primary); color:white;' : 'var(--card-bg); border:1px solid var(--border-light);' ?>; box-shadow:var(--shadow-sm); line-height:1.6;">
                                <?= nl2br(e($m['message'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if($ticket['status'] !== 'closed'): ?>
                    <div class="card">
                        <div class="card-body">
                            <form method="POST">
                                <?= csrfField() ?>
                                <div class="form-group">
                                    <label class="form-label">Yanıtınız</label>
                                    <textarea name="message" class="form-control" rows="4" placeholder="Mesajınızı yazın..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Yanıtı Gönder</button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="flash flash-info text-center">Bu destek talebi kapatılmıştır. Yeni bir sorunuz varsa lütfen yeni talep oluşturun.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="assets/js/app.js?v=4.0"></script>
    <script src="assets/js/theme.js"></script>
</body>
</html>
