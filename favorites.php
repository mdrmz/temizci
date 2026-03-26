<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

$user = currentUser();
$db = getDB();
$filterStatus = $_GET['status'] ?? '';

$db->exec("
    CREATE TABLE IF NOT EXISTS fav_store_v2 (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        listing_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_fav_store_v2 (user_id, listing_id),
        KEY idx_fav_store_user (user_id),
        KEY idx_fav_store_listing (listing_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$whereStatus = '';
$params = [$user['id']];
if (in_array($filterStatus, ['open', 'in_progress', 'closed', 'cancelled'], true)) {
    $whereStatus = ' AND l.status = ?';
    $params[] = $filterStatus;
}

// Favorileri çek
$stmt = $db->prepare("
    SELECT l.*, c.name AS cat_name, c.icon AS cat_icon,
           u.name AS owner_name,
           h.city, h.room_config, h.photo AS home_photo,
           (SELECT COUNT(*) FROM offers o WHERE o.listing_id = l.id) AS offer_count,
           f.created_at AS fav_date
    FROM fav_store_v2 f
    JOIN listings l ON f.listing_id = l.id
    JOIN categories c ON l.category_id = c.id
    JOIN users u ON l.user_id = u.id
    JOIN homes h ON l.home_id = h.id
    WHERE f.user_id = ?
    $whereStatus
    ORDER BY f.created_at DESC
");
$stmt->execute($params);
$favorites = $stmt->fetchAll();

$initials = strtoupper(substr($user['name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favorilerim  -  <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=5.0">
    <link rel="stylesheet" href="assets/css/dark-mode.css">
    <link rel="icon" href="/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="/logo.png">
</head>
<body>
    <div class="app-layout">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php $headerTitle = 'Favorilerim'; include 'includes/app-header.php'; ?>

            <div class="page-content">
                <?= flashHtml() ?>

                <div class="page-title"> Favorilerim</div>
                <div class="page-subtitle">Kaydettiğiniz ilanları buradan takip edin</div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;">
                    <a href="favorites.php" class="btn btn-sm <?= $filterStatus === '' ? 'btn-primary' : 'btn-outline' ?>">Tum</a>
                    <a href="favorites.php?status=open" class="btn btn-sm <?= $filterStatus === 'open' ? 'btn-primary' : 'btn-outline' ?>">Acik</a>
                    <a href="favorites.php?status=in_progress" class="btn btn-sm <?= $filterStatus === 'in_progress' ? 'btn-primary' : 'btn-outline' ?>">Devam Eden</a>
                    <a href="favorites.php?status=closed" class="btn btn-sm <?= $filterStatus === 'closed' ? 'btn-primary' : 'btn-outline' ?>">Tamamlanan</a>
                </div>

                <?php if (empty($favorites)): ?>
                    <div class="card mt-4">
                        <div class="empty-state" style="padding:60px 20px;">
                            <div class="empty-state-icon" style="font-size:3rem;"></div>
                            <h3>Henüz favoriniz yok</h3>
                            <p style="color:var(--text-muted);margin-bottom:20px;">İlanları incelerken kalp ikonuna tıklayarak favorilere ekleyebilirsiniz.</p>
                            <a href="listings/browse" class="btn btn-primary"> İlanları Keşfet</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="grid-3 mt-4">
                        <?php foreach ($favorites as $fav): ?>
                            <div class="card listing-card" id="fav-card-<?= $fav['id'] ?>">
                                <?php if (!empty($fav['home_photo'])): ?>
                                    <div class="card-img-placeholder" style="background: url('<?= APP_URL ?>/uploads/homes/<?= $fav['home_photo'] ?>') center/cover no-repeat; position: relative; border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
                                        <div style="position: absolute; inset: 0; background: linear-gradient(to bottom, transparent, rgba(0,0,0,0.8)); border-radius: var(--radius-lg) var(--radius-lg) 0 0;"></div>
                                        <span style="position: absolute; bottom: 15px; left: 15px; background: var(--gradient); padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; display:flex; gap: 8px; align-items:center;">
                                            <span><?= $fav['cat_icon'] ?></span> <?= e($fav['cat_name']) ?>
                                        </span>
                                        <button class="fav-remove-btn" onclick="removeFavorite(<?= $fav['id'] ?>)" 
                                            style="position:absolute;top:12px;right:12px;background:rgba(0,0,0,0.5);border:none;border-radius:50%;width:36px;height:36px;cursor:pointer;font-size:1.1rem;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);transition:all 0.2s;"
                                            title="Favoriden Kaldır"></button>
                                    </div>
                                    <div class="card-content">
                                <?php else: ?>
                                    <div class="card-img-placeholder" style="position:relative;">
                                        <?= $fav['cat_icon'] ?>
                                        <button class="fav-remove-btn" onclick="removeFavorite(<?= $fav['id'] ?>)"
                                            style="position:absolute;top:12px;right:12px;background:rgba(255,255,255,0.9);border:none;border-radius:50%;width:36px;height:36px;cursor:pointer;font-size:1.1rem;display:flex;align-items:center;justify-content:center;transition:all 0.2s;"
                                            title="Favoriden Kaldır"></button>
                                    </div>
                                    <div class="card-content">
                                        <div class="listing-cat">
                                            <?= $fav['cat_icon'] ?> <?= e($fav['cat_name']) ?>
                                        </div>
                                <?php endif; ?>
                                    <div class="listing-title"><?= e($fav['title']) ?></div>
                                    <div style="margin:6px 0 10px;display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                                        <?= statusBadge($fav['status']) ?>
                                        <span class="badge"><?= (int) $fav['offer_count'] ?> teklif</span>
                                    </div>
                                    <div class="listing-meta">
                                        <span> <?= e($fav['city']) ?></span>
                                        <span>  <?= e($fav['room_config']) ?></span>
                                        <span> <?= date('d M', strtotime($fav['preferred_date'])) ?></span>
                                    </div>
                                    <div class="listing-footer">
                                        <?php if ($fav['budget']): ?>
                                            <span class="listing-budget"><?= formatMoney($fav['budget']) ?></span>
                                        <?php else: ?>
                                            <span style="color:var(--text-muted);font-size:0.85rem;">Bütçe belirsiz</span>
                                        <?php endif; ?>
                                        <a href="listings/detail?id=<?= $fav['id'] ?>" class="btn btn-primary btn-sm">Detay</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="assets/js/app.js?v=5.0"></script>
    <script src="assets/js/theme.js"></script>
    <script>
    function removeFavorite(listingId) {
        fetch('<?= APP_URL ?>/api/favorites', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ listing_id: listingId })
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success' && data.action === 'removed') {
                const card = document.getElementById('fav-card-' + listingId);
                if (card) {
                    card.style.transition = 'opacity 0.3s, transform 0.3s';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.9)';
                    setTimeout(() => card.remove(), 300);
                }
            }
        });
    }
    </script>
</body>
</html>



