<?php
require_once __DIR__ . '/includes/auth.php'; // Protects this file for admins only
require_once __DIR__ . '/../includes/db.php';

$db = getDB();

// Fetch overall stats
$usersSt = $db->query("SELECT COUNT(*) as c FROM users")->fetch();
$usersCount = $usersSt['c'];

$listingsSt = $db->query("SELECT COUNT(*) as c FROM listings")->fetch();
$listingsCount = $listingsSt['c'];

$offersSt = $db->query("SELECT COUNT(*) as c FROM offers")->fetch();
$offersCount = $offersSt['c'];

$catsSt = $db->query("SELECT COUNT(*) as c FROM categories")->fetch();
$catsCount = $catsSt['c'];

// Recent users
$recentUsers = $db->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli — Temizci Burada</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="../assets/css/style.css?v=4.0">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <link rel="icon" href="/logo.png" type="image/png">
</head>
<body>
    <div class="app-layout">
        
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="main-content">
            <?php $headerTitle = 'Genel Bakış'; include __DIR__ . '/includes/header.php'; ?>

            <div class="page-content">
                <div class="page-header" style="margin-bottom: 2rem;">
                    <div class="page-title">Sistem İstatistikleri</div>
                    <div class="page-subtitle">Sitenin güncel performansını ve yeni kullanıcıları buradan takip edebilirsiniz.</div>
                </div>

                <div class="grid-4 mb-4">
                    <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
                        <div style="display:flex; justify-content:space-between; align-items:start;">
                            <div>
                                <div class="stat-label">Toplam Kullanıcı</div>
                                <div class="stat-value"><?= number_format($usersCount) ?></div>
                            </div>
                            <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6; padding: 10px; border-radius: 12px; font-size: 1.2rem;">👥</div>
                        </div>
                    </div>
                    <div class="stat-card" style="border-left: 4px solid #14b8a6;">
                        <div style="display:flex; justify-content:space-between; align-items:start;">
                            <div>
                                <div class="stat-label">Açılan İlan</div>
                                <div class="stat-value"><?= number_format($listingsCount) ?></div>
                            </div>
                            <div class="stat-icon" style="background: rgba(20, 184, 166, 0.1); color: #14b8a6; padding: 10px; border-radius: 12px; font-size: 1.2rem;">📋</div>
                        </div>
                    </div>
                    <div class="stat-card" style="border-left: 4px solid #f59e0b;">
                        <div style="display:flex; justify-content:space-between; align-items:start;">
                            <div>
                                <div class="stat-label">Verilen Teklif</div>
                                <div class="stat-value"><?= number_format($offersCount) ?></div>
                            </div>
                            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; padding: 10px; border-radius: 12px; font-size: 1.2rem;">💬</div>
                        </div>
                    </div>
                    <div class="stat-card" style="border-left: 4px solid #ec4899;">
                        <div style="display:flex; justify-content:space-between; align-items:start;">
                            <div>
                                <div class="stat-label">Kategoriler</div>
                                <div class="stat-value"><?= number_format($catsCount) ?></div>
                            </div>
                            <div class="stat-icon" style="background: rgba(236, 72, 153, 0.1); color: #ec4899; padding: 10px; border-radius: 12px; font-size: 1.2rem;">🏷️</div>
                        </div>
                    </div>
                </div>

                <!-- Recent Users -->
                <div class="card" style="border-radius: 16px; overflow: hidden; border: 1px solid var(--border-light);">
                    <div class="card-header" style="padding: 20px; border-bottom: 1px solid var(--border-light); background: rgba(0,0,0,0.02);">
                        <div class="card-title" style="font-weight: 700; font-size: 1.1rem;">Son Kayıt Olan Kullanıcılar</div>
                    </div>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Kullanıcı</th>
                                    <th>E-Posta</th>
                                    <th>Rol</th>
                                    <th>Kayıt Tarihi</th>
                                    <th>Profil</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recentUsers as $ru): ?>
                                <tr>
                                    <td style="font-weight:600;"><?= e($ru['name']) ?></td>
                                    <td style="color: var(--text-muted);"><?= e($ru['email']) ?></td>
                                    <td>
                                        <span class="badge <?= $ru['role'] === 'worker' ? 'badge-progress' : 'badge-open' ?>" style="font-size: 0.75rem; padding: 4px 10px;">
                                            <?= $ru['role'] === 'worker' ? 'Hizmet Veren' : 'Ev Sahibi' ?>
                                        </span>
                                    </td>
                                    <td style="font-size: 0.85rem; color: var(--text-muted);"><?= date('d.m.Y H:i', strtotime($ru['created_at'])) ?></td>
                                    <td>
                                        <a href="users.php?id=<?= $ru['id'] ?>" class="btn btn-outline btn-sm" style="padding: 4px 10px; font-size: 0.75rem;">Yönet</a>
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
