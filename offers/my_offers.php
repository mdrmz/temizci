<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin();

$user = currentUser();
$db = getDB();

// Verilen teklifler (worker için) VEYA alınan teklifler (homeowner için)
if ($user['role'] === 'worker') {
    $stmt = $db->prepare("
        SELECT o.*, l.title AS listing_title, l.id AS listing_id, l.user_id AS listing_user_id, l.preferred_date,
               h.city, h.room_config, c.name AS cat_name, c.icon AS cat_icon
        FROM offers o
        JOIN listings l ON o.listing_id = l.id
        JOIN homes h ON l.home_id = h.id
        JOIN categories c ON l.category_id = c.id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$user['id']]);
} else {
    $stmt = $db->prepare("
        SELECT o.*, u.id AS worker_id, u.name AS worker_name, u.rating, u.review_count,
               l.title AS listing_title, l.id AS listing_id, l.user_id AS listing_user_id, l.preferred_date,
               h.city, h.room_config, c.name AS cat_name, c.icon AS cat_icon
        FROM offers o
        JOIN listings l ON o.listing_id = l.id
        JOIN homes h ON l.home_id = h.id
        JOIN categories c ON l.category_id = c.id
        JOIN users u ON o.user_id = u.id
        WHERE l.user_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$user['id']]);
}
$offers = $stmt->fetchAll();
$initials = strtoupper(substr($user['name'], 0, 1));
$isWorker = $user['role'] === 'worker';
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tekliflerim — Temizci Burada</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="../assets/css/style.css?v=4.0">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">

    <!-- SEO & Favicon -->
    <link rel="icon" href="/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="/logo.png">
    <meta property="og:image" content="https://www.temizciburada.com/logo.png">
</head>

<body>
    <div class="app-layout">
        <?php include '../includes/sidebar.php'; ?>
        <div class="main-content">
            <?php $headerTitle = 'Tekliflerim'; include '../includes/app-header.php'; ?>

            <div class="page-content">
                <?= flashHtml() ?>
                <div class="page-title">💬
                    <?= $isWorker ? 'Verdiğim Teklifler' : 'İlanlarıma Gelen Teklifler' ?>
                </div>
                <div class="page-subtitle">
                    <?= count($offers) ?> teklif
                </div>

                <?php if (empty($offers)): ?>
                    <div class="card">
                        <div class="empty-state">
                            <div class="empty-state-icon">💬</div>
                            <h3>Henüz
                                <?= $isWorker ? 'teklif vermediniz' : 'teklif almadınız' ?>
                            </h3>
                            <p>
                                <?= $isWorker ? 'Açık ilanları inceleyin ve teklif verin.' : 'İlan oluşturun ve teklif almaya başlayın.' ?>
                            </p>
                            <a href="<?= $isWorker ? '../listings/browse.php' : '../listings/create.php' ?>"
                                class="btn btn-primary">
                                <?= $isWorker ? '🔍 İlanları Gez' : '+ İlan Oluştur' ?>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php
                    $grouped = [];
                    foreach ($offers as $o) {
                        $grouped[$o['status']][] = $o;
                    }
                    $statusOrder = ['pending', 'accepted', 'rejected'];
                    $statusLabels = ['pending' => '⏳ Bekleyenler', 'accepted' => '✅ Kabul Edilenler', 'rejected' => '❌ Reddedilenler'];
                    ?>
                    <?php foreach ($statusOrder as $st):
                        if (!isset($grouped[$st]))
                            continue; ?>
                        <div style="margin-bottom:28px;">
                            <h2 style="font-size:1rem;font-weight:700;margin-bottom:14px;color:var(--text-secondary);">
                                <?= $statusLabels[$st] ?> (
                                <?= count($grouped[$st]) ?>)
                            </h2>
                            <div class="card">
                                <?php foreach ($grouped[$st] as $offer): ?>
                                    <div style="padding:18px 22px;border-bottom:1px solid var(--border-light);">
                                        <div
                                            style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                                            <div>
                                                <div style="font-size:0.78rem;color:var(--text-muted);margin-bottom:4px;">
                                                    <?= $offer['cat_icon'] ?>
                                                    <?= e($offer['cat_name']) ?> · 📍
                                                    <?= e($offer['city']) ?> · 🏠
                                                    <?= e($offer['room_config']) ?>
                                                </div>
                                                <a href="../listings/detail?id=<?= $offer['listing_id'] ?>"
                                                    style="font-weight:700;font-size:0.95rem;color:var(--text-primary);">
                                                    <?= e($offer['listing_title']) ?>
                                                </a>
                                                <?php if (!$isWorker && isset($offer['worker_name'])): ?>
                                                    <div style="margin-top:6px;display:flex;align-items:center;gap:8px;">
                                                        <div class="avatar avatar-sm" style="background:var(--gradient);">
                                                            <?= strtoupper(substr($offer['worker_name'], 0, 1)) ?>
                                                        </div>
                                                        <span style="font-weight:600;font-size:0.88rem;">
                                                            <?= e($offer['worker_name']) ?>
                                                        </span>
                                                        <?php if ($offer['review_count'] > 0): ?>
                                                            <span style="font-size:0.78rem;color:var(--text-muted);">
                                                                <?= starRating($offer['rating']) ?>
                                                                <?= number_format($offer['rating'], 1) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <p
                                                    style="font-size:0.84rem;color:var(--text-secondary);margin-top:8px;line-height:1.5;">
                                                    <?= e(mb_substr($offer['message'], 0, 150)) ?>...
                                                </p>
                                            </div>
                                            <div style="text-align:right;flex-shrink:0;">
                                                <div style="font-weight:800;font-size:1.1rem;color:var(--secondary);">
                                                    <?= formatMoney($offer['price']) ?>
                                                </div>
                                                <div style="font-size:0.75rem;color:var(--text-muted);">
                                                    <?= timeAgo($offer['created_at']) ?>
                                                </div>
                                                <?= offerStatusBadge($offer['status']) ?>
                                                <div style="margin-top:8px;">
                                                    <a href="../listings/detail?id=<?= $offer['listing_id'] ?>"
                                                        class="btn btn-ghost btn-sm">İlana Git →</a>
                                                    <a href="../messages.php?uid=<?= $isWorker ? $offer['listing_user_id'] : $offer['worker_id'] ?>" class="btn btn-outline btn-sm" style="margin-left: 6px;">✉️ Mesaj</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="../assets/js/app.js?v=4.0"></script>
    <script src="../assets/js/theme.js"></script>
</body>

</html>