<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin();

$user = currentUser();
$db = getDB();

$stmt = $db->prepare("
    SELECT l.*, c.name AS cat_name, c.icon AS cat_icon, h.city, h.room_config,
           (SELECT COUNT(*) FROM offers WHERE listing_id=l.id) AS offer_count,
           (SELECT COUNT(*) FROM offers WHERE listing_id=l.id AND status='pending') AS pending_count
    FROM listings l
    JOIN categories c ON l.category_id=c.id
    JOIN homes h ON l.home_id=h.id
    WHERE l.user_id=?
    ORDER BY l.created_at DESC
");
$stmt->execute([$user['id']]);
$listings = $stmt->fetchAll();
$initials = strtoupper(substr($user['name'], 0, 1));

// İlan sil
if (isset($_GET['delete'])) {
    $delId = (int) $_GET['delete'];
    $db->prepare("UPDATE listings SET status='cancelled' WHERE id=? AND user_id=?")->execute([$delId, $user['id']]);
    setFlash('success', 'İlan iptal edildi.');
    redirect(APP_URL . '/listings/my_listings');
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İlanlarım  -  Temizci Burada</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=5.0">
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
            <?php $headerTitle = 'İlanlarım'; include '../includes/app-header.php'; ?>

            <div class="page-content">
                <?= flashHtml() ?>
                <div class="page-title"> İlanlarım</div>
                <div class="page-subtitle">Oluşturduğunuz tüm ilanları yönetin</div>

                <?php if (empty($listings)): ?>
                    <div class="card">
                        <div class="empty-state">
                            <div class="empty-state-icon"></div>
                            <h3>Henüz ilanınız yok</h3>
                            <p>İlk ilanınızı oluşturun ve hizmet teklifleri almaya başlayın!</p>
                            <a href="create" class="btn btn-primary btn-lg">+ İlan Oluştur</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>İlan</th>
                                        <th>Kategori</th>
                                        <th>Åehir</th>
                                        <th>Tarih</th>
                                        <th>Teklifler</th>
                                        <th>Durum</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listings as $l): ?>
                                        <tr>
                                            <td>
                                                <a href="detail?id=<?= $l['id'] ?>"
                                                    style="font-weight:600;color:var(--text-primary);">
                                                    <?= e(mb_substr($l['title'], 0, 50)) ?>...
                                                </a>
                                            </td>
                                            <td>
                                                <?= $l['cat_icon'] ?>
                                                <?= e($l['cat_name']) ?>
                                            </td>
                                            <td>
                                                <?= e($l['city']) ?>
                                            </td>
                                            <td>
                                                <?= date('d.m.Y', strtotime($l['preferred_date'])) ?>
                                            </td>
                                            <td>
                                                <a href="detail?id=<?= $l['id'] ?>"
                                                    style="font-weight:700;color:var(--primary);">
                                                    <?= $l['offer_count'] ?> teklif
                                                    <?php if ($l['pending_count']): ?>
                                                        <span class="badge badge-open" style="margin-left:4px;">
                                                            <?= $l['pending_count'] ?> bekleyen
                                                        </span>
                                                    <?php endif; ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?= statusBadge($l['status']) ?>
                                            </td>
                                            <td>
                                                <div style="display:flex;gap:6px;">
                                                    <a href="detail?id=<?= $l['id'] ?>"
                                                        class="btn btn-ghost btn-sm">Detay</a>
                                                    <?php if ($l['status'] === 'open'): ?>
                                                        <a href="my_listings?delete=<?= $l['id'] ?>"
                                                            class="btn btn-danger btn-sm"
                                                            data-confirm="Bu ilanı iptal etmek istiyor musunuz?">İptal</a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="../assets/js/app.js?v=5.0"></script>
    <script src="../assets/js/theme.js"></script>
</body>

</html>


