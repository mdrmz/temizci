<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin();

$user = currentUser();
if ($user['role'] !== 'admin') {
    redirect(APP_URL . '/dashboard');
}

$db = getDB();

// İstatistikleri topla
$totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalWorkers = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'worker'")->fetchColumn();
$totalHomeowners = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'homeowner'")->fetchColumn();
$totalListings = (int)$db->query("SELECT COUNT(*) FROM listings")->fetchColumn();
$openListings = (int)$db->query("SELECT COUNT(*) FROM listings WHERE status = 'open'")->fetchColumn();
$closedListings = (int)$db->query("SELECT COUNT(*) FROM listings WHERE status = 'closed'")->fetchColumn();
$totalOffers = (int)$db->query("SELECT COUNT(*) FROM offers")->fetchColumn();
$acceptedOffers = (int)$db->query("SELECT COUNT(*) FROM offers WHERE status = 'accepted'")->fetchColumn();

// Son 30 gün aktivitesi
$chartData = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $dayLabel = date('d', strtotime($date));
    
    $newUsers = (int)$db->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) = ?")->execute([$date]) ? (int)$db->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) = ?")->execute([$date]) : 0;
    $uStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) = ?");
    $uStmt->execute([$date]);
    $newUsers = (int)$uStmt->fetchColumn();
    
    $lStmt = $db->prepare("SELECT COUNT(*) FROM listings WHERE DATE(created_at) = ?");
    $lStmt->execute([$date]);
    $newListings = (int)$lStmt->fetchColumn();
    
    $oStmt = $db->prepare("SELECT COUNT(*) FROM offers WHERE DATE(created_at) = ?");
    $oStmt->execute([$date]);
    $newOffers = (int)$oStmt->fetchColumn();
    
    $chartData[] = ['label' => $dayLabel, 'users' => $newUsers, 'listings' => $newListings, 'offers' => $newOffers];
}
$maxChart = max(1, max(array_column($chartData, 'users')), max(array_column($chartData, 'listings')), max(array_column($chartData, 'offers')));

// En aktif kullanıcılar
$topWorkers = $db->query("SELECT u.name, u.rating, u.review_count,
    (SELECT COUNT(*) FROM offers o JOIN listings l ON o.listing_id = l.id WHERE o.user_id = u.id AND o.status = 'accepted' AND l.status = 'closed') as completed
    FROM users u WHERE u.role = 'worker' ORDER BY completed DESC LIMIT 5")->fetchAll();

// Son kayıt olan kullancılar
$recentUsers = $db->query("SELECT name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 10")->fetchAll();

// Gelir tahmini (kabul edilen tekliflerden)
$totalRevenue = $db->query("SELECT COALESCE(SUM(price), 0) FROM offers WHERE status = 'accepted'")->fetchColumn();

$initials = strtoupper(substr($user['name'], 0, 1));
$notifCount = getUnreadNotificationCount($user['id']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Analytics — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="../assets/css/style.css?v=4.0">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <link rel="icon" href="/logo.png" type="image/png">
    <style>
        .analytics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
        .ana-card { background: #fff; border-radius: 16px; padding: 24px; border: 1px solid var(--border-light); }
        .ana-card-grad { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; border: none; }
        .ana-val { font-size: 2rem; font-weight: 800; }
        .ana-label { font-size: 0.82rem; opacity: 0.7; margin-top: 4px; }
        .ana-change { font-size: 0.78rem; margin-top: 8px; }
        @media(max-width:768px) { .analytics-grid { grid-template-columns: 1fr 1fr; } }
        @media(max-width:480px) { .analytics-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="app-layout">
        <?php include '../includes/sidebar.php'; ?>
        <div class="main-content">
            <?php $headerTitle = 'Admin Analytics'; include '../includes/app-header.php'; ?>
            <div class="page-content">
                <div class="page-title">📊 Platform İstatistikleri</div>
                <div class="page-subtitle">Temizci Burada'nın genel durumu</div>

                <!-- Ana İstatistikler -->
                <div class="analytics-grid mt-4">
                    <div class="ana-card ana-card-grad">
                        <div class="ana-val"><?= number_format($totalUsers) ?></div>
                        <div class="ana-label">Toplam Kullanıcı</div>
                        <div class="ana-change">👤 <?= $totalHomeowners ?> ev sahibi · 🧹 <?= $totalWorkers ?> çalışan</div>
                    </div>
                    <div class="ana-card">
                        <div class="ana-val" style="color:var(--primary);"><?= number_format($totalListings) ?></div>
                        <div class="ana-label">Toplam İlan</div>
                        <div class="ana-change" style="color:var(--text-muted);">📗 <?= $openListings ?> açık · ✅ <?= $closedListings ?> tamamlanan</div>
                    </div>
                    <div class="ana-card">
                        <div class="ana-val" style="color:#10b981;"><?= number_format($totalOffers) ?></div>
                        <div class="ana-label">Toplam Teklif</div>
                        <div class="ana-change" style="color:var(--text-muted);">✅ <?= $acceptedOffers ?> kabul edilen</div>
                    </div>
                    <div class="ana-card">
                        <div class="ana-val" style="color:#f59e0b;"><?= formatMoney($totalRevenue) ?></div>
                        <div class="ana-label">Toplam İş Hacmi</div>
                        <div class="ana-change" style="color:var(--text-muted);">Kabul edilen teklifler toplamı</div>
                    </div>
                </div>

                <!-- 30 Gün Grafiği -->
                <div class="card mt-4">
                    <div class="card-header"><div class="card-title">📈 Son 30 Gün Aktivitesi</div></div>
                    <div class="card-body">
                        <div style="display:flex;gap:3px;align-items:flex-end;height:180px;overflow-x:auto;padding:0 4px;">
                            <?php foreach ($chartData as $day): ?>
                                <div style="flex:1;min-width:16px;display:flex;flex-direction:column;align-items:center;gap:2px;height:100%;justify-content:flex-end;">
                                    <div style="display:flex;gap:1px;align-items:flex-end;width:100%;justify-content:center;">
                                        <div style="width:5px;background:#6366f1;border-radius:2px 2px 0 0;height:<?= max(2, ($day['users']/$maxChart)*140) ?>px;" title="Kullanıcı: <?= $day['users'] ?>"></div>
                                        <div style="width:5px;background:#10b981;border-radius:2px 2px 0 0;height:<?= max(2, ($day['listings']/$maxChart)*140) ?>px;" title="İlan: <?= $day['listings'] ?>"></div>
                                        <div style="width:5px;background:#f59e0b;border-radius:2px 2px 0 0;height:<?= max(2, ($day['offers']/$maxChart)*140) ?>px;" title="Teklif: <?= $day['offers'] ?>"></div>
                                    </div>
                                    <div style="font-size:0.55rem;color:var(--text-muted);"><?= $day['label'] ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="display:flex;justify-content:center;gap:20px;margin-top:14px;">
                            <div style="display:flex;align-items:center;gap:6px;font-size:0.78rem;">
                                <span style="width:10px;height:10px;background:#6366f1;border-radius:3px;"></span> Kullanıcı
                            </div>
                            <div style="display:flex;align-items:center;gap:6px;font-size:0.78rem;">
                                <span style="width:10px;height:10px;background:#10b981;border-radius:3px;"></span> İlan
                            </div>
                            <div style="display:flex;align-items:center;gap:6px;font-size:0.78rem;">
                                <span style="width:10px;height:10px;background:#f59e0b;border-radius:3px;"></span> Teklif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid-2 mt-4" style="grid-template-columns:1fr 1fr;gap:24px;">
                    <!-- En Aktif Çalışanlar -->
                    <div class="card">
                        <div class="card-header"><div class="card-title">🏆 En Aktif Çalışanlar</div></div>
                        <div class="card-body" style="padding:0;">
                            <?php foreach ($topWorkers as $i => $tw): ?>
                                <div style="display:flex;align-items:center;gap:12px;padding:14px 20px;border-bottom:1px solid var(--border-light);">
                                    <div style="font-size:1.2rem;font-weight:800;color:var(--text-muted);width:24px;"><?= $i + 1 ?></div>
                                    <div style="flex:1;">
                                        <div style="font-weight:700;"><?= e($tw['name']) ?></div>
                                        <div style="font-size:0.78rem;color:var(--text-muted);">⭐ <?= number_format($tw['rating'], 1) ?> · <?= $tw['review_count'] ?> değerlendirme</div>
                                    </div>
                                    <div style="font-weight:800;color:var(--primary);"><?= $tw['completed'] ?> iş</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Son Kayıt Olanlar -->
                    <div class="card">
                        <div class="card-header"><div class="card-title">👤 Son Kayıt Olan Kullanıcılar</div></div>
                        <div class="card-body" style="padding:0;">
                            <?php foreach ($recentUsers as $ru): ?>
                                <div style="display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid var(--border-light);">
                                    <div class="avatar avatar-sm" style="background:var(--gradient);"><?= strtoupper(substr($ru['name'], 0, 1)) ?></div>
                                    <div style="flex:1;">
                                        <div style="font-weight:600;font-size:0.88rem;"><?= e($ru['name']) ?></div>
                                        <div style="font-size:0.72rem;color:var(--text-muted);"><?= e($ru['email']) ?></div>
                                    </div>
                                    <div style="font-size:0.75rem;color:var(--text-muted);"><?= $ru['role'] === 'worker' ? '🧹' : '🏠' ?> <?= timeAgo($ru['created_at']) ?></div>
                                </div>
                            <?php endforeach; ?>
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
