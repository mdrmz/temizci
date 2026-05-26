<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

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

$filterStatus = $_GET['status'] ?? '';
$filterPriority = $_GET['priority'] ?? '';
$where = [];
$params = [];
if (in_array($filterStatus, ['open', 'in_progress', 'closed'], true)) {
    $where[] = 't.status = ?';
    $params[] = $filterStatus;
}
if (in_array($filterPriority, ['low', 'normal', 'high'], true)) {
    $where[] = 't.priority = ?';
    $params[] = $filterPriority;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $db->prepare("
    SELECT t.*, u.name AS user_name
    FROM xsupport_t1 t
    JOIN users u ON t.user_id = u.id
    $whereSql
    ORDER BY t.created_at DESC
");
$stmt->execute($params);
$tickets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destek Yonetimi - Admin Paneli</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=5.0">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <link rel="icon" href="/logo.png" type="image/png">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        <div class="main-content">
            <?php $headerTitle = 'Destek Talepleri'; include __DIR__ . '/includes/header.php'; ?>
            <div class="page-content">
                <div class="page-title">Musteri Destek Talepleri</div>
                <div class="page-subtitle">Kullanicilardan gelen talepleri yonetin.</div>

                <form method="GET" class="search-bar mt-4">
                    <div class="form-group" style="flex:1;min-width:220px;">
                        <label class="form-label">Durum</label>
                        <select name="status" class="form-control">
                            <option value="">Tumu</option>
                            <option value="open" <?= $filterStatus === 'open' ? 'selected' : '' ?>>Acik</option>
                            <option value="in_progress" <?= $filterStatus === 'in_progress' ? 'selected' : '' ?>>Islemde</option>
                            <option value="closed" <?= $filterStatus === 'closed' ? 'selected' : '' ?>>Kapali</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex:1;min-width:220px;">
                        <label class="form-label">Oncelik</label>
                        <select name="priority" class="form-control">
                            <option value="">Tumu</option>
                            <option value="high" <?= $filterPriority === 'high' ? 'selected' : '' ?>>Yuksek</option>
                            <option value="normal" <?= $filterPriority === 'normal' ? 'selected' : '' ?>>Normal</option>
                            <option value="low" <?= $filterPriority === 'low' ? 'selected' : '' ?>>Dusuk</option>
                        </select>
                    </div>
                    <div style="padding-top:22px;display:flex;gap:8px;">
                        <button class="btn btn-primary btn-sm" type="submit">Uygula</button>
                        <a href="tickets.php" class="btn btn-ghost btn-sm">Temizle</a>
                    </div>
                </form>

                <div class="card mt-4">
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Kullanici</th>
                                    <th>Konu</th>
                                    <th>Durum</th>
                                    <th>Oncelik</th>
                                    <th>Admin Notu</th>
                                    <th>Tarih</th>
                                    <th>Islem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tickets)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center" style="padding:28px;color:var(--text-muted);">Henuz destek talebi yok.</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($tickets as $t): ?>
                                    <tr>
                                        <td style="font-weight:600;"><?= e($t['user_name']) ?></td>
                                        <td><?= e($t['subject']) ?></td>
                                        <td>
                                            <span class="badge <?= $t['status'] === 'open' ? 'badge-open' : ($t['status'] === 'in_progress' ? 'badge-progress' : 'badge-closed') ?>">
                                                <?= $t['status'] === 'open' ? 'Acik' : ($t['status'] === 'in_progress' ? 'Islemde' : 'Kapali') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $t['priority'] === 'high' ? 'badge-closed' : ($t['priority'] === 'low' ? 'badge-open' : 'badge-progress') ?>">
                                                <?= $t['priority'] === 'high' ? 'Yuksek' : ($t['priority'] === 'low' ? 'Dusuk' : 'Normal') ?>
                                            </span>
                                        </td>
                                        <td style="font-size:0.82rem;color:var(--text-muted);">
                                            <?= !empty($t['admin_note']) ? e(mb_substr($t['admin_note'], 0, 60)) . (mb_strlen($t['admin_note']) > 60 ? '...' : '') : '-' ?>
                                        </td>
                                        <td style="font-size:0.85rem;color:var(--text-muted);"><?= date('d.m.Y H:i', strtotime($t['created_at'])) ?></td>
                                        <td>
                                            <a href="ticket_view.php?id=<?= (int) $t['id'] ?>" class="btn btn-primary btn-sm">Yanitla</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
