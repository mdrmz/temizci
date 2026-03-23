<?php
require_once __DIR__ . '/includes/auth.php'; // Protects this file for admins only
require_once __DIR__ . '/../includes/db.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT t.*, u.name AS user_name FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
$stmt->execute([$id]);
$ticket = $stmt->fetch();

if (!$ticket) redirect('tickets.php');

// Send response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    if (verifyCsrf()) {
        $message = trim($_POST['message']);
        $newStatus = $_POST['status'] ?? $ticket['status'];
        
        if ($message) {
            $db->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message) VALUES (?, ?, ?)")->execute([$id, $user['id'], $message]);
            $db->prepare("UPDATE tickets SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
            
            setFlash('success', 'Yanıt gönderildi ve durum güncellendi.');
            redirect('ticket_view.php?id=' . $id);
        }
    }
}

$msgStmt = $db->prepare("SELECT tm.*, u.name AS sender_name, u.role FROM ticket_messages tm JOIN users u ON tm.sender_id = u.id WHERE tm.ticket_id = ? ORDER BY tm.created_at ASC");
$msgStmt->execute([$id]);
$messages = $msgStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Talep #<?= $id ?> — Admin Paneli</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="../assets/css/style.css?v=4.0">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <link rel="icon" href="/logo.png" type="image/png">
</head>
<body>
    <div class="app-layout">
        
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="main-content">
            <?php $headerTitle = 'Talep Detayı'; include __DIR__ . '/includes/header.php'; ?>

            <div class="page-content" style="max-width: 900px; margin: 0 auto;">
                <?= flashHtml() ?>
                
                <div class="mb-4">
                    <a href="tickets.php" class="btn btn-outline btn-sm" style="padding: 8px 16px; border-radius: 8px;">
                        ← Taleplere Geri Dön
                    </a>
                </div>

                <div class="card mb-4" style="border-radius: 16px; border: 1px solid var(--border-light); background: var(--bg-card);">
                    <div class="card-header" style="padding: 20px; border-bottom: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">KONU:</div>
                            <div style="font-weight: 800; font-size: 1.2rem;"><?= e($ticket['subject']) ?></div>
                        </div>
                        <span class="badge <?= $ticket['status'] === 'open' ? 'badge-open' : ($ticket['status'] == 'in_progress' ? 'badge-progress' : 'badge-closed') ?>" style="padding: 6px 12px;">
                            <?= $ticket['status'] === 'open' ? 'Açık' : ($ticket['status'] == 'in_progress' ? 'İşlemde' : 'Kapalı') ?>
                        </span>
                    </div>
                </div>

                <div class="ticket-chat mb-4" style="display: flex; flex-direction: column; gap: 20px;">
                    <?php foreach($messages as $m): ?>
                        <div style="max-width: 85%; <?= $m['role'] === 'admin' ? 'align-self: flex-end;' : 'align-self: flex-start;' ?>">
                            <div style="display: flex; flex-direction: column; <?= $m['role'] === 'admin' ? 'align-items: flex-end;' : 'align-items: flex-start;' ?>">
                                <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; padding: 0 4px;">
                                    <?= e($m['sender_name']) ?> (<?= $m['role'] === 'admin' ? 'Admin' : 'Müşteri' ?>) • <?= date('d.m.Y H:i', strtotime($m['created_at'])) ?>
                                </div>
                                <div style="padding: 16px; border-radius: 18px; <?= $m['role'] === 'admin' ? 'background: var(--gradient); color: white; border-bottom-right-radius: 4px;' : 'background: var(--bg-white); border: 1px solid var(--border-light); color: var(--text-primary); border-bottom-left-radius: 4px;' ?> box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                                    <?= nl2br(e($m['message'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="card" style="border-radius: 20px; overflow: hidden; border: 1px solid var(--border-light); box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                    <div class="card-header" style="background: rgba(0,0,0,0.01); padding: 15px 20px;">
                        <div class="card-title" style="font-size: 0.95rem;">Yanıt Gönder</div>
                    </div>
                    <div class="card-body" style="padding: 24px;">
                        <form method="POST">
                            <?= csrfField() ?>
                            <div class="form-group mb-4">
                                <label class="form-label">Mesajınız</label>
                                <textarea name="message" class="form-control" rows="5" required placeholder="Kullanıcıya iletmek istediğiniz yanıtı yazın..." style="border-radius: 12px; transition: all 0.3s; border-color: var(--border);"></textarea>
                            </div>
                            <div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: end;">
                                <div class="form-group">
                                    <label class="form-label">Talebin Yeni Durumu</label>
                                    <select name="status" class="form-control" style="border-radius: 12px; height: 48px;">
                                        <option value="open" <?= $ticket['status'] == 'open' ? 'selected' : '' ?>>Açık (Open)</option>
                                        <option value="in_progress" <?= $ticket['status'] == 'in_progress' ? 'selected' : '' ?>>İşlemde (In Progress)</option>
                                        <option value="closed" <?= $ticket['status'] == 'closed' ? 'selected' : '' ?>>Kapalı (Closed)</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary" style="height: 48px; border-radius: 12px; font-weight: 700; width: 100%;">Yanıtla ve Durumu Güncelle</button>
                            </div>
                        </form>
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
