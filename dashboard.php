<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

$user = currentUser();
$db = getDB();

// İstatistikler
$totalListings = $db->prepare("SELECT COUNT(*) FROM listings WHERE user_id = ?");
$totalListings->execute([$user['id']]);
$listingCount = $totalListings->fetchColumn();

$openListings = $db->prepare("SELECT COUNT(*) FROM listings WHERE user_id = ? AND status='open'");
$openListings->execute([$user['id']]);
$openCount = $openListings->fetchColumn();

$totalOffers = $db->prepare("SELECT COUNT(*) FROM offers WHERE user_id = ?");
$totalOffers->execute([$user['id']]);
$offerCount = $totalOffers->fetchColumn();

$totalHomes = $db->prepare("SELECT COUNT(*) FROM homes WHERE user_id = ?");
$totalHomes->execute([$user['id']]);
$homeCount = $totalHomes->fetchColumn();

// Son ilanlar
$recentListings = $db->prepare("
    SELECT l.*, c.name AS cat_name, c.icon AS cat_icon, h.city, h.room_config,
           (SELECT COUNT(*) FROM offers WHERE listing_id = l.id) AS offer_count
    FROM listings l
    JOIN categories c ON l.category_id = c.id
    JOIN homes h ON l.home_id = h.id
    WHERE l.user_id = ?
    ORDER BY l.created_at DESC LIMIT 5
");
$recentListings->execute([$user['id']]);
$listings = $recentListings->fetchAll();

// Gelen teklifler (ilanlarıma gelen)
$incomingOffers = $db->prepare("
    SELECT o.*, u.name AS worker_name, u.rating, u.review_count,
           l.title AS listing_title, l.id AS listing_id
    FROM offers o
    JOIN users u ON o.user_id = u.id
    JOIN listings l ON o.listing_id = l.id
    WHERE l.user_id = ? AND o.status = 'pending'
    ORDER BY o.created_at DESC LIMIT 5
");
$incomingOffers->execute([$user['id']]);
$offers = $incomingOffers->fetchAll();

$notifCount = getUnreadNotificationCount($user['id']);
$initials = strtoupper(substr($user['name'], 0, 1));

// Son 7 gün aktivite verileri
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $dayLabel = date('d M', strtotime($date));
    
    $lStmt = $db->prepare("SELECT COUNT(*) FROM listings WHERE user_id = ? AND DATE(created_at) = ?");
    $lStmt->execute([$user['id'], $date]);
    $lCount = (int)$lStmt->fetchColumn();
    
    $oStmt = $db->prepare("SELECT COUNT(*) FROM offers o JOIN listings l ON o.listing_id = l.id WHERE l.user_id = ? AND DATE(o.created_at) = ?");
    $oStmt->execute([$user['id'], $date]);
    $oCount = (int)$oStmt->fetchColumn();
    
    $chartData[] = ['label' => $dayLabel, 'listings' => $lCount, 'offers' => $oCount];
}
$maxVal = max(1, max(array_column($chartData, 'listings')), max(array_column($chartData, 'offers')));

// Favori sayısı (tablo yoksa hata vermeden 0 döner)
$favCount = 0;
try {
    $favStmt = $db->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
    $favStmt->execute([$user['id']]);
    $favCount = (int)$favStmt->fetchColumn();
} catch (Exception $e) {
    $favCount = 0;
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Temizci Burada</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="assets/css/style.css?v=4.0">
    <link rel="stylesheet" href="assets/css/dark-mode.css">

    <!-- SEO & Favicon -->
    <link rel="icon" href="/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="/logo.png">
    <meta property="og:image" content="https://www.temizciburada.com/logo.png">
</head>

<body>
    <div class="app-layout">

        <!-- ======== SIDEBAR ======== -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- ======== ANA İÇERİK ======== -->
        <div class="main-content">
            <?php $headerTitle = 'Dashboard'; include 'includes/app-header.php'; ?>

            <div class="page-content">
                <?= flashHtml() ?>

                <div class="page-title">Genel Bakış</div>
                <div class="page-subtitle">Sistemdeki son aktiviteleriniz ve özet verileriniz.</div>

                <!-- STATS -->
                <div class="grid-4 mt-4">
                    <div class="card stat-card">
                        <div class="stat-label">Toplam İlanım</div>
                        <div class="stat-value"><?= $listingCount ?></div>
                        <div class="stat-footer">Yayında Olan: <?= $openCount ?></div>
                    </div>
                    <div class="card stat-card">
                        <div class="stat-label">Gelen Teklifler</div>
                        <div class="stat-value"><?= $offerCount ?></div>
                        <div class="stat-footer">Tüm zamanlar</div>
                    </div>
                    <div class="card stat-card">
                        <div class="stat-label">Kayıtlı Evlerim</div>
                        <div class="stat-value"><?= $homeCount ?></div>
                        <div class="stat-footer"><a href="homes/list" style="color:var(--primary);">Tümünü Gör</a></div>
                    </div>
                    <div class="card stat-card">
                        <div class="stat-label">Favorilerim</div>
                        <div class="stat-value"><?= $favCount ?></div>
                        <div class="stat-footer"><a href="favorites" style="color:var(--primary);">Tümünü Gör</a></div>
                    </div>
                </div>

                <!-- AKTİVİTE GRAFİĞİ -->
                <div class="card mt-4">
                    <div class="card-header">
                        <div class="card-title">📊 Son 7 Gün Aktivitesi</div>
                    </div>
                    <div class="card-body">
                        <div style="display:flex;gap:6px;align-items:flex-end;height:160px;padding:0 10px;">
                            <?php foreach ($chartData as $day): ?>
                                <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;height:100%;justify-content:flex-end;">
                                    <div style="display:flex;gap:3px;align-items:flex-end;width:100%;justify-content:center;min-height:0;flex:1;">
                                        <div style="width:14px;background:linear-gradient(to top, #6366f1, #818cf8);border-radius:4px 4px 0 0;height:<?= $maxVal > 0 ? max(4, ($day['listings']/$maxVal)*120) : 4 ?>px;transition:height 0.5s;" title="İlan: <?= $day['listings'] ?>"></div>
                                        <div style="width:14px;background:linear-gradient(to top, #10b981, #34d399);border-radius:4px 4px 0 0;height:<?= $maxVal > 0 ? max(4, ($day['offers']/$maxVal)*120) : 4 ?>px;transition:height 0.5s;" title="Teklif: <?= $day['offers'] ?>"></div>
                                    </div>
                                    <div style="font-size:0.65rem;color:var(--text-muted);white-space:nowrap;"><?= $day['label'] ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="display:flex;justify-content:center;gap:20px;margin-top:14px;">
                            <div style="display:flex;align-items:center;gap:6px;font-size:0.78rem;">
                                <span style="width:10px;height:10px;background:#6366f1;border-radius:3px;display:inline-block;"></span> İlanlarım
                            </div>
                            <div style="display:flex;align-items:center;gap:6px;font-size:0.78rem;">
                                <span style="width:10px;height:10px;background:#10b981;border-radius:3px;display:inline-block;"></span> Gelen Teklifler
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid-2 mt-4" style="grid-template-columns: 1fr 1fr; gap:24px;">
                    
                    <!-- SON İLANLARIM -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Son İlanlarım</div>
                            <a href="listings/my_listings" class="btn btn-ghost btn-sm">Tümünü Gör</a>
                        </div>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>İlan</th>
                                        <th>Şehir</th>
                                        <th>Teklif</th>
                                        <th>Durum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($listings)): ?>
                                        <tr><td colspan="4" class="text-center" style="padding:30px; color:var(--text-muted);">Henüz ilan oluşturmadınız.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach($listings as $l): ?>
                                    <tr>
                                        <td style="font-weight:600;"><?= e($l['title']) ?></td>
                                        <td><?= e($l['city']) ?></td>
                                        <td><?= $l['offer_count'] ?></td>
                                        <td><?= statusBadge($l['status']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- GELEN TEKLİFLER -->
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Bekleyen Teklifler</div>
                            <a href="offers/my_offers" class="btn btn-ghost btn-sm">Tümünü Gör</a>
                        </div>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Hizmet Veren</th>
                                        <th>İlan</th>
                                        <th>Teklif</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($offers)): ?>
                                        <tr><td colspan="4" class="text-center" style="padding:30px; color:var(--text-muted);">Yeni teklif bulunmuyor.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach($offers as $o): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:600;"><?= e($o['worker_name']) ?></div>
                                            <div style="font-size:0.75rem; color:var(--text-muted);"><?= starRating($o['rating']) ?></div>
                                        </td>
                                        <td style="font-size:0.85rem; max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= e($o['listing_title']) ?></td>
                                        <td style="font-weight:700; color:var(--primary);"><?= formatMoney($o['price']) ?></td>
                                        <td><a href="listings/detail?id=<?= $o['listing_id'] ?>" class="btn btn-primary btn-sm">İncele</a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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
