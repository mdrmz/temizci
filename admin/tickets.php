<?php
require_once __DIR__ . '/includes/auth.php'; // Protects this file for admins only
require_once __DIR__ . '/../includes/db.php';

$db = getDB();

// Fetch tickets
$stmt = $db->query("SELECT t.*, u.name AS user_name FROM tickets t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC");
$tickets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destek Yönetimi — Admin Paneli</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="../assets/css/style.css?v=4.0">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <link rel="icon" href="/logo.png" type="image/png">
</head>
<body>
    <div class="app-layout">
        
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="main-content">
            <?php $headerTitle = 'Destek Talepleri'; include __DIR__ . '/includes/header.php'; ?>

            <div class="page-content">
                <div class="page-header" style="margin-bottom: 2rem;">
                    <div class="page-title">Müşteri Destek Talepleri</div>
                    <div class="page-subtitle">Kullanıcılardan gelen yardım çağrılarını buradan yanıtlayabilirsiniz.</div>
                </div>

                <div class="card" style="border-radius: 16px; overflow: hidden; border: 1px solid var(--border-light);">
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Kullanıcı</th>
                                    <th>Konu</th>
                                    <th>Durum</th>
                                    <th>Tarih</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($tickets as $t): ?>
                                <tr>
                                    <td style="font-weight:600;"><?= e($t['user_name']) ?></td>
                                    <td><?= e($t['subject']) ?></td>
                                    <td>
                                        <span class="badge <?= $t['status'] === 'open' ? 'badge-open' : ($t['status'] == 'in_progress' ? 'badge-progress' : 'badge-closed') ?>" style="font-size: 0.75rem; padding: 4px 10px;">
                                            <?php 
                                            if($t['status'] === 'open') echo 'Açık';
                                            elseif($t['status'] === 'in_progress') echo 'İşlemde';
                                            else echo 'Kapalı';
                                            ?>
                                        </span>
                                    </td>
                                    <td style="font-size: 0.85rem; color: var(--text-muted);"><?= date('d.m.Y H:i', strtotime($t['created_at'])) ?></td>
                                    <td>
                                        <a href="ticket_view.php?id=<?= $t['id'] ?>" class="btn btn-primary btn-sm" style="padding: 6px 14px; font-weight: 600; border-radius: 8px;">Yanıtla</a>
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
    <script src="../assets/js/app.js?v=4.0"></script>
    <script src="../assets/js/theme.js"></script>
</body>
</html>
