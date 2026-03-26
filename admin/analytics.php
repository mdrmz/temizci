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

// Ä°statistikleri topla
$totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalWorkers = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'worker'")->fetchColumn();
$totalHomeowners = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'homeowner'")->fetchColumn();
$totalListings = (int)$db->query("SELECT COUNT(*) FROM listings")->fetchColumn();
$openListings = (int)$db->query("SELECT COUNT(*) FROM listings WHERE status = 'open'")->fetchColumn();
$closedListings = (int)$db->query("SELECT COUNT(*) FROM listings WHERE status = 'closed'")->fetchColumn();
$totalOffers = (int)$db->query("SELECT COUNT(*) FROM offers")->fetchColumn();
$acceptedOffers = (int)$db->query("SELECT COUNT(*) FROM offers WHERE status = 'accepted'")->fetchColumn();
$listingCloseRate = $totalListings > 0 ? round(($closedListings / $totalListings) * 100, 1) : 0;

// Son 30 gÃ¼n aktivitesi
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

// En aktif kullanÄ±cÄ±lar
$topWorkers = $db->query("SELECT u.name, u.rating, u.review_count,
    (SELECT COUNT(*) FROM offers o JOIN listings l ON o.listing_id = l.id WHERE o.user_id = u.id AND o.status = 'accepted' AND l.status = 'closed') as completed
    FROM users u WHERE u.role = 'worker' ORDER BY completed DESC LIMIT 5")->fetchAll();

// Son kayÄ±t olan kullancÄ±lar
$recentUsers = $db->query("SELECT name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 10")->fetchAll();

// Gelir tahmini (kabul edilen tekliflerden)
$totalRevenue = $db->query("SELECT COALESCE(SUM(price), 0) FROM offers WHERE status = 'accepted'")->fetchColumn();

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
$supportTotal = (int) $db->query("SELECT COUNT(*) FROM xsupport_t1")->fetchColumn();
$supportClosed = (int) $db->query("SELECT COUNT(*) FROM xsupport_t1 WHERE status = 'closed'")->fetchColumn();
$supportCloseRate = $supportTotal > 0 ? round(($supportClosed / $supportTotal) * 100, 1) : 0;
$topDistricts = $db->query("
    SELECT COALESCE(NULLIF(h.district, ''), h.city, 'Bilinmiyor') AS area_name, COUNT(*) AS listing_count
    FROM listings l
    JOIN homes h ON h.id = l.home_id
    GROUP BY area_name
    ORDER BY listing_count DESC
    LIMIT 6
")->fetchAll();

$db->exec("
    CREATE TABLE IF NOT EXISTS offer_negotiations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        offer_id INT NOT NULL,
        actor_id INT NOT NULL,
        event_type ENUM('offer_sent','counter_sent','counter_accepted','counter_rejected','offer_accepted','offer_rejected') NOT NULL,
        note VARCHAR(500) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_offer_neg_offer (offer_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
$totalCounterOffers = (int) $db->query("SELECT COUNT(*) FROM offer_negotiations WHERE event_type = 'counter_sent'")->fetchColumn();
$totalCounterAccepted = (int) $db->query("SELECT COUNT(*) FROM offer_negotiations WHERE event_type = 'counter_accepted'")->fetchColumn();
$counterAcceptRate = $totalCounterOffers > 0 ? round(($totalCounterAccepted / $totalCounterOffers) * 100, 1) : 0;
$recentNegotiations = $db->query("
    SELECT n.event_type, n.note, n.created_at, u.name AS actor_name, l.title AS listing_title
    FROM offer_negotiations n
    JOIN users u ON u.id = n.actor_id
    JOIN offers o ON o.id = n.offer_id
    JOIN listings l ON l.id = o.listing_id
    ORDER BY n.created_at DESC
    LIMIT 8
")->fetchAll();

$initials = strtoupper(substr($user['name'], 0, 1));
$notifCount = getUnreadNotificationCount($user['id']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Analytics â€” <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=5.0">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <link rel="icon" href="/logo.png" type="image/png">
    <style>
        .analytics-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 16px; }
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
                <div class="page-title">ğŸ“Š Platform Ä°statistikleri</div>
                <div class="page-subtitle">Temizci Burada'nÄ±n genel durumu</div>

                <!-- Ana Ä°statistikler -->
                <div class="analytics-grid mt-4">
                    <div class="ana-card ana-card-grad">
                        <div class="ana-val"><?= number_format($totalUsers) ?></div>
                        <div class="ana-label">Toplam KullanÄ±cÄ±</div>
                        <div class="ana-change">ğŸ‘¤ <?= $totalHomeowners ?> ev sahibi Â· ğŸ§¹ <?= $totalWorkers ?> Ã§alÄ±ÅŸan</div>
                    </div>
                    <div class="ana-card">
                        <div class="ana-val" style="color:var(--primary);"><?= number_format($totalListings) ?></div>
                        <div class="ana-label">Toplam Ä°lan</div>
                        <div class="ana-change" style="color:var(--text-muted);">ğŸ“— <?= $openListings ?> aÃ§Ä±k Â· âœ… <?= $closedListings ?> tamamlanan</div>
                    </div>
                    <div class="ana-card">
                        <div class="ana-val" style="color:#10b981;"><?= number_format($totalOffers) ?></div>
                        <div class="ana-label">Toplam Teklif</div>
                        <div class="ana-change" style="color:var(--text-muted);">âœ… <?= $acceptedOffers ?> kabul edilen</div>
                    </div>
                    <div class="ana-card">
                        <div class="ana-val" style="color:#f59e0b;"><?= formatMoney($totalRevenue) ?></div>
                        <div class="ana-label">Toplam Ä°ÅŸ Hacmi</div>
                        <div class="ana-change" style="color:var(--text-muted);">Kabul edilen teklifler toplamÄ±</div>
                    </div>
                    <div class="ana-card">
                        <div class="ana-val" style="color:#06b6d4;"><?= number_format($listingCloseRate, 1) ?>%</div>
                        <div class="ana-label">Ilan Kapanis Orani</div>
                        <div class="ana-change" style="color:var(--text-muted);">Destek cozum: <?= number_format($supportCloseRate, 1) ?>%</div>
                    </div>
                    <div class="ana-card">
                        <div class="ana-val" style="color:#8b5cf6;"><?= number_format($counterAcceptRate, 1) ?>%</div>
                        <div class="ana-label">Pazarlik Kabul Orani</div>
                        <div class="ana-change" style="color:var(--text-muted);"><?= $totalCounterAccepted ?> / <?= $totalCounterOffers ?> karsi teklif kabul</div>
                    </div>
                </div>

                <!-- 30 GÃ¼n GrafiÄŸi -->
                <div class="card mt-4">
                    <div class="card-header"><div class="card-title">ğŸ“ˆ Son 30 GÃ¼n Aktivitesi</div></div>
                    <div class="card-body">
                        <div style="display:flex;gap:3px;align-items:flex-end;height:180px;overflow-x:auto;padding:0 4px;">
                            <?php foreach ($chartData as $day): ?>
                                <div style="flex:1;min-width:16px;display:flex;flex-direction:column;align-items:center;gap:2px;height:100%;justify-content:flex-end;">
                                    <div style="display:flex;gap:1px;align-items:flex-end;width:100%;justify-content:center;">
                                        <div style="width:5px;background:#6366f1;border-radius:2px 2px 0 0;height:<?= max(2, ($day['users']/$maxChart)*140) ?>px;" title="KullanÄ±cÄ±: <?= $day['users'] ?>"></div>
                                        <div style="width:5px;background:#10b981;border-radius:2px 2px 0 0;height:<?= max(2, ($day['listings']/$maxChart)*140) ?>px;" title="Ä°lan: <?= $day['listings'] ?>"></div>
                                        <div style="width:5px;background:#f59e0b;border-radius:2px 2px 0 0;height:<?= max(2, ($day['offers']/$maxChart)*140) ?>px;" title="Teklif: <?= $day['offers'] ?>"></div>
                                    </div>
                                    <div style="font-size:0.55rem;color:var(--text-muted);"><?= $day['label'] ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="display:flex;justify-content:center;gap:20px;margin-top:14px;">
                            <div style="display:flex;align-items:center;gap:6px;font-size:0.78rem;">
                                <span style="width:10px;height:10px;background:#6366f1;border-radius:3px;"></span> KullanÄ±cÄ±
                            </div>
                            <div style="display:flex;align-items:center;gap:6px;font-size:0.78rem;">
                                <span style="width:10px;height:10px;background:#10b981;border-radius:3px;"></span> Ä°lan
                            </div>
                            <div style="display:flex;align-items:center;gap:6px;font-size:0.78rem;">
                                <span style="width:10px;height:10px;background:#f59e0b;border-radius:3px;"></span> Teklif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid-2 mt-4" style="grid-template-columns:1fr 1fr;gap:24px;">
                    <!-- En Aktif Ã‡alÄ±ÅŸanlar -->
                    <div class="card">
                        <div class="card-header"><div class="card-title">ğŸ† En Aktif Ã‡alÄ±ÅŸanlar</div></div>
                        <div class="card-body" style="padding:0;">
                            <?php foreach ($topWorkers as $i => $tw): ?>
                                <div style="display:flex;align-items:center;gap:12px;padding:14px 20px;border-bottom:1px solid var(--border-light);">
                                    <div style="font-size:1.2rem;font-weight:800;color:var(--text-muted);width:24px;"><?= $i + 1 ?></div>
                                    <div style="flex:1;">
                                        <div style="font-weight:700;"><?= e($tw['name']) ?></div>
                                        <div style="font-size:0.78rem;color:var(--text-muted);">â­ <?= number_format($tw['rating'], 1) ?> Â· <?= $tw['review_count'] ?> deÄŸerlendirme</div>
                                    </div>
                                    <div style="font-weight:800;color:var(--primary);"><?= $tw['completed'] ?> iÅŸ</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Son KayÄ±t Olanlar -->
                    <div class="card">
                        <div class="card-header"><div class="card-title">ğŸ‘¤ Son KayÄ±t Olan KullanÄ±cÄ±lar</div></div>
                        <div class="card-body" style="padding:0;">
                            <?php foreach ($recentUsers as $ru): ?>
                                <div style="display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid var(--border-light);">
                                    <div class="avatar avatar-sm" style="background:var(--gradient);"><?= strtoupper(substr($ru['name'], 0, 1)) ?></div>
                                    <div style="flex:1;">
                                        <div style="font-weight:600;font-size:0.88rem;"><?= e($ru['name']) ?></div>
                                        <div style="font-size:0.72rem;color:var(--text-muted);"><?= e($ru['email']) ?></div>
                                    </div>
                                    <div style="font-size:0.75rem;color:var(--text-muted);"><?= $ru['role'] === 'worker' ? 'ğŸ§¹' : 'ğŸ ' ?> <?= timeAgo($ru['created_at']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header"><div class="card-title">En Yogun Bolgeler</div></div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($topDistricts)): ?>
                            <div style="padding:18px;color:var(--text-muted);">Veri bulunamadi.</div>
                        <?php else: ?>
                            <?php foreach ($topDistricts as $idx => $area): ?>
                                <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid var(--border-light);">
                                    <div style="display:flex;gap:10px;align-items:center;">
                                        <span style="font-weight:800;color:var(--text-muted);width:20px;"><?= $idx + 1 ?></span>
                                        <span style="font-weight:600;"><?= e($area['area_name']) ?></span>
                                    </div>
                                    <span class="badge"><?= (int) $area['listing_count'] ?> ilan</span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header"><div class="card-title">Son Pazarlik Hareketleri</div></div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($recentNegotiations)): ?>
                            <div style="padding:18px;color:var(--text-muted);">Henuz pazarlik hareketi yok.</div>
                        <?php else: ?>
                            <?php foreach ($recentNegotiations as $neg): ?>
                                <?php
                                    $negLabel = match($neg['event_type']) {
                                        'offer_sent' => 'Teklif gonderildi',
                                        'counter_sent' => 'Karsi teklif gonderildi',
                                        'counter_accepted' => 'Karsi teklif kabul edildi',
                                        'counter_rejected' => 'Karsi teklif reddedildi',
                                        'offer_accepted' => 'Teklif kabul edildi',
                                        'offer_rejected' => 'Teklif reddedildi',
                                        default => $neg['event_type']
                                    };
                                ?>
                                <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;padding:12px 18px;border-bottom:1px solid var(--border-light);">
                                    <div>
                                        <div style="font-weight:600;"><?= e($neg['listing_title']) ?></div>
                                        <div style="font-size:0.8rem;color:var(--text-muted);"><?= e($neg['actor_name']) ?> - <?= e($negLabel) ?></div>
                                        <?php if (!empty($neg['note'])): ?>
                                            <div style="font-size:0.77rem;color:var(--text-secondary);margin-top:2px;"><?= e(mb_substr($neg['note'], 0, 90)) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <span style="font-size:0.76rem;color:var(--text-muted);white-space:nowrap;"><?= timeAgo($neg['created_at']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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


