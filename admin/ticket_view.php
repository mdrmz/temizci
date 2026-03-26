<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);

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

$ticketColumns = $db->query("SHOW COLUMNS FROM xsupport_t1")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('priority', $ticketColumns, true)) {
    $db->exec("ALTER TABLE xsupport_t1 ADD COLUMN priority ENUM('low', 'normal', 'high') DEFAULT 'normal' AFTER status");
}
if (!in_array('admin_note', $ticketColumns, true)) {
    $db->exec("ALTER TABLE xsupport_t1 ADD COLUMN admin_note TEXT NULL AFTER priority");
}

$stmt = $db->prepare("
    SELECT t.*, u.name AS user_name
    FROM xsupport_t1 t
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
");
$stmt->execute([$id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    redirect('tickets.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    if (verifyCsrf()) {
        $message = trim($_POST['message']);
        $newStatus = $_POST['status'] ?? $ticket['status'];
        $newPriority = $_POST['priority'] ?? ($ticket['priority'] ?? 'normal');
        if (!in_array($newPriority, ['low', 'normal', 'high'], true)) {
            $newPriority = 'normal';
        }
        $adminNote = trim($_POST['admin_note'] ?? '');
        if ($message !== '') {
            $db->prepare("INSERT INTO xsupport_m1 (ticket_id, sender_id, message) VALUES (?, ?, ?)")
               ->execute([$id, $user['id'], $message]);
            $db->prepare("UPDATE xsupport_t1 SET status = ?, priority = ?, admin_note = ? WHERE id = ?")
               ->execute([$newStatus, $newPriority, $adminNote !== '' ? $adminNote : null, $id]);
            setFlash('success', 'Yanit gonderildi ve durum guncellendi.');
            redirect('ticket_view.php?id=' . $id);
        }
    }
}

$msgStmt = $db->prepare("
    SELECT tm.*, u.name AS sender_name, u.role
    FROM xsupport_m1 tm
    JOIN users u ON tm.sender_id = u.id
    WHERE tm.ticket_id = ?
    ORDER BY tm.created_at ASC
");
$msgStmt->execute([$id]);
$messages = $msgStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Talep #<?= (int) $id ?> - Admin Paneli</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=5.0">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <link rel="icon" href="/logo.png" type="image/png">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        <div class="main-content">
            <?php $headerTitle = 'Talep Detayi'; include __DIR__ . '/includes/header.php'; ?>

            <div class="page-content" style="max-width: 860px; margin: 0 auto;">
                <?= flashHtml() ?>

                <div class="mb-4">
                    <a href="tickets.php" class="btn btn-outline btn-sm">Taleplere Geri Don</a>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <div>
                            <div style="font-size:0.8rem;color:var(--text-muted);">Konu</div>
                            <div style="font-weight:800;font-size:1.1rem;"><?= e($ticket['subject']) ?></div>
                        </div>
                        <span class="badge <?= $ticket['status'] === 'open' ? 'badge-open' : ($ticket['status'] === 'in_progress' ? 'badge-progress' : 'badge-closed') ?>">
                            <?= $ticket['status'] === 'open' ? 'Acik' : ($ticket['status'] === 'in_progress' ? 'Islemde' : 'Kapali') ?>
                        </span>
                        <span class="badge <?= ($ticket['priority'] ?? 'normal') === 'high' ? 'badge-closed' : ((($ticket['priority'] ?? 'normal') === 'low') ? 'badge-open' : 'badge-progress') ?>" style="margin-left:6px;">
                            <?= ($ticket['priority'] ?? 'normal') === 'high' ? 'Yuksek' : ((($ticket['priority'] ?? 'normal') === 'low') ? 'Dusuk' : 'Normal') ?>
                        </span>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body" style="display:flex;flex-direction:column;gap:14px;">
                        <?php foreach ($messages as $m): ?>
                            <div style="max-width:84%;<?= $m['role'] === 'admin' ? 'align-self:flex-end;' : 'align-self:flex-start;' ?>">
                                <div style="font-size:0.75rem;color:var(--text-muted);margin-bottom:5px;">
                                    <?= e($m['sender_name']) ?> (<?= $m['role'] === 'admin' ? 'Admin' : 'Musteri' ?>) - <?= date('d.m.Y H:i', strtotime($m['created_at'])) ?>
                                </div>
                                <div style="padding:13px 14px;border-radius:12px;<?= $m['role'] === 'admin' ? 'background:var(--primary);color:#fff;' : 'background:#fff;border:1px solid var(--border);' ?>">
                                    <?= nl2br(e($m['message'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Yanit Gonder</div>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?= csrfField() ?>
                            <div class="form-group">
                                <label class="form-label">Mesaj</label>
                                <textarea name="message" class="form-control" rows="5" required placeholder="Yanitinizi yazin..."></textarea>
                            </div>
                            <div class="grid-2" style="align-items:end;">
                                <div class="form-group">
                                    <label class="form-label">Durum</label>
                                    <select name="status" class="form-control">
                                        <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Acik</option>
                                        <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>Islemde</option>
                                        <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Kapali</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Oncelik</label>
                                    <select name="priority" class="form-control">
                                        <option value="high" <?= ($ticket['priority'] ?? 'normal') === 'high' ? 'selected' : '' ?>>Yuksek</option>
                                        <option value="normal" <?= ($ticket['priority'] ?? 'normal') === 'normal' ? 'selected' : '' ?>>Normal</option>
                                        <option value="low" <?= ($ticket['priority'] ?? 'normal') === 'low' ? 'selected' : '' ?>>Dusuk</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Admin Notu (ic not)</label>
                                <textarea name="admin_note" class="form-control" rows="3" placeholder="Kisa takip notu..."><?= e($ticket['admin_note'] ?? '') ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Yanitla ve Guncelle</button>
                        </form>
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
