<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    redirect(APP_URL . '/listings/browse');
}

// Hizmet veren bilgileri
$stmt = $db->prepare("SELECT id, name, role, city, bio, avatar, rating, review_count, is_verified, created_at FROM users WHERE id = ?");
$stmt->execute([$id]);
$worker = $stmt->fetch();

if (!$worker) {
    redirect(APP_URL . '/listings/browse');
}

// Tamamladığı iş sayısı
$jobStmt = $db->prepare("
    SELECT COUNT(*) FROM offers o 
    JOIN listings l ON o.listing_id = l.id 
    WHERE o.user_id = ? AND o.status = 'accepted' AND l.status = 'closed'
");
$jobStmt->execute([$id]);
$completedJobs = (int)$jobStmt->fetchColumn();

// Aktif teklif sayısı
$activeStmt = $db->prepare("SELECT COUNT(*) FROM offers WHERE user_id = ? AND status = 'pending'");
$activeStmt->execute([$id]);
$activeOffers = (int)$activeStmt->fetchColumn();

// Yorumlar
$reviewStmt = $db->prepare("
    SELECT r.*, u.name AS reviewer_name, l.title AS listing_title
    FROM reviews r
    JOIN users u ON r.reviewer_id = u.id
    JOIN listings l ON r.listing_id = l.id
    WHERE r.reviewee_id = ?
    ORDER BY r.created_at DESC
    LIMIT 20
");
$reviewStmt->execute([$id]);
$reviews = $reviewStmt->fetchAll();

// Müsaitlik takvimi
$availStmt = $db->prepare("SELECT available_date, time_slot FROM availability WHERE user_id = ? AND available_date >= CURDATE() ORDER BY available_date ASC LIMIT 14");
$availStmt->execute([$id]);
$availability = $availStmt->fetchAll();

$isLoggedIn = isLoggedIn();
$user = $isLoggedIn ? currentUser() : null;
$initials = strtoupper(substr($worker['name'], 0, 1));
$memberSince = date('M Y', strtotime($worker['created_at']));

$timeSlotLabels = [
    'tum_gun' => 'Tüm Gün',
    'sabah' => '☀️ Sabah',
    'ogle' => '🌤️ Öğle',
    'aksam' => '🌙 Akşam',
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($worker['name']) ?> — Hizmet Veren Profili | Temizci Burada</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="assets/css/style.css?v=4.0">
    <link rel="stylesheet" href="assets/css/dark-mode.css">
    <link rel="icon" href="/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="/logo.png">
    <style>
        .profile-hero {
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            padding: 60px 0 80px;
            text-align: center;
            color: #fff;
            position: relative;
        }
        .profile-avatar {
            width: 100px; height: 100px; border-radius: 50%;
            background: var(--gradient); display: flex; align-items: center; justify-content: center;
            font-size: 2.5rem; font-weight: 800; color: #fff;
            margin: 0 auto 16px; border: 4px solid rgba(255,255,255,0.2);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        .profile-name { font-size: 1.6rem; font-weight: 800; margin-bottom: 4px; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .profile-meta { opacity: 0.7; font-size: 0.88rem; }
        .profile-stats { display: flex; gap: 24px; justify-content: center; margin-top: 24px; flex-wrap: wrap; }
        .profile-stat { text-align: center; }
        .profile-stat-val { font-size: 1.4rem; font-weight: 800; }
        .profile-stat-lbl { font-size: 0.75rem; opacity: 0.6; }
        .profile-body { max-width: 800px; margin: -40px auto 40px; padding: 0 20px; position: relative; z-index: 1; }
        .review-item { padding: 18px 0; border-bottom: 1px solid var(--border-light); }
        .review-item:last-child { border-bottom: none; }
        .avail-tag { display: inline-flex; gap: 6px; align-items: center; background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); color: #059669; padding: 6px 14px; border-radius: 20px; font-size: 0.82rem; font-weight: 600; margin: 4px; }
    </style>
</head>
<body>

    <?php if ($isLoggedIn): ?>
        <div class="app-layout">
            <?php include 'includes/sidebar.php'; ?>
            <div class="main-content">
                <?php $headerTitle = 'Profil'; include 'includes/app-header.php'; ?>
                <div class="page-content" style="padding:0;">
    <?php else: ?>
        <nav class="navbar scrolled" style="background:rgba(255,255,255,0.95);">
            <div class="navbar-inner container">
                <a href="index" class="navbar-logo">
                    <div class="logo-icon" style="width:36px;height:36px;border-radius:8px;overflow:hidden;display:flex;align-items:center;justify-content:center;">
                        <img src="logo.png" alt="Logo" style="width:100%;height:100%;object-fit:cover;">
                    </div>
                    <span><span>Temizci Burada</span></span>
                </a>
                <div class="navbar-actions">
                    <a href="login" class="btn btn-outline btn-sm">Giriş Yap</a>
                    <a href="register" class="btn btn-primary btn-sm">Kayıt Ol</a>
                </div>
            </div>
        </nav>
    <?php endif; ?>

    <!-- Hero -->
    <div class="profile-hero">
        <div class="profile-avatar"><?= $initials ?></div>
        <div class="profile-name">
            <?= e($worker['name']) ?>
            <?php if ($worker['is_verified']): ?>
                <span title="Doğrulanmış Profil" style="font-size:1.2rem;">✅</span>
            <?php endif; ?>
        </div>
        <div class="profile-meta">
            <?= $worker['city'] ? '📍 ' . e($worker['city']) . ' · ' : '' ?>
            🗓️ <?= $memberSince ?>'den beri üye ·
            <?= $worker['role'] === 'worker' ? '🧹 Hizmet Veren' : '🏠 Ev Sahibi' ?>
        </div>
        
        <div class="profile-stats">
            <div class="profile-stat">
                <div class="profile-stat-val"><?= number_format($worker['rating'], 1) ?> ⭐</div>
                <div class="profile-stat-lbl">Puan</div>
            </div>
            <div class="profile-stat">
                <div class="profile-stat-val"><?= $worker['review_count'] ?></div>
                <div class="profile-stat-lbl">Değerlendirme</div>
            </div>
            <div class="profile-stat">
                <div class="profile-stat-val"><?= $completedJobs ?></div>
                <div class="profile-stat-lbl">Tamamlanan İş</div>
            </div>
            <div class="profile-stat">
                <div class="profile-stat-val"><?= $activeOffers ?></div>
                <div class="profile-stat-lbl">Aktif Teklif</div>
            </div>
        </div>
    </div>

    <!-- Body -->
    <div class="profile-body">
        <?php if ($worker['bio']): ?>
        <div class="card mb-4">
            <div class="card-header">
                <div class="card-title">📝 Hakkında</div>
            </div>
            <div class="card-body">
                <p style="color:var(--text-secondary);line-height:1.7;"><?= nl2br(e($worker['bio'])) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Müsaitlik -->
        <?php if (!empty($availability)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <div class="card-title">📅 Müsait Günler</div>
            </div>
            <div class="card-body">
                <div style="display:flex;flex-wrap:wrap;gap:4px;">
                    <?php foreach ($availability as $a): ?>
                        <span class="avail-tag">
                            📅 <?= date('d M', strtotime($a['available_date'])) ?>
                            — <?= $timeSlotLabels[$a['time_slot']] ?? $a['time_slot'] ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Mesaj Gönder -->
        <?php if ($isLoggedIn && $user['id'] != $id): ?>
        <div class="card mb-4">
            <div class="card-body" style="text-align:center;padding:24px;">
                <a href="<?= APP_URL ?>/messages?uid=<?= $id ?>" class="btn btn-primary btn-lg" style="gap:8px;">
                    💬 Mesaj Gönder
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Yorumlar -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">⭐ Değerlendirmeler (<?= count($reviews) ?>)</div>
            </div>
            <?php if (empty($reviews)): ?>
                <div class="empty-state" style="padding:40px;">
                    <div class="empty-state-icon" style="font-size:2.5rem;">⭐</div>
                    <h3>Henüz değerlendirme yok</h3>
                    <p style="color:var(--text-muted);">Bu kullanıcı henüz değerlendirilmemiş.</p>
                </div>
            <?php else: ?>
                <div class="card-body" style="padding:0;">
                    <?php foreach ($reviews as $rev): ?>
                        <div class="review-item" style="padding:18px 22px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div class="avatar avatar-sm" style="background:var(--gradient);"><?= strtoupper(substr($rev['reviewer_name'], 0, 1)) ?></div>
                                    <div>
                                        <div style="font-weight:700;font-size:0.9rem;"><?= e($rev['reviewer_name']) ?></div>
                                        <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($rev['listing_title']) ?></div>
                                    </div>
                                </div>
                                <div style="text-align:right;">
                                    <div><?= starRating($rev['rating']) ?></div>
                                    <div style="font-size:0.72rem;color:var(--text-muted);"><?= timeAgo($rev['created_at']) ?></div>
                                </div>
                            </div>
                            <?php if ($rev['comment']): ?>
                                <p style="font-size:0.88rem;color:var(--text-secondary);line-height:1.6;font-style:italic;margin:0;padding-left:46px;">
                                    "<?= e($rev['comment']) ?>"
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($isLoggedIn): ?>
            </div>
        </div>
        </div>
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <?php else: ?>
        <?php include 'includes/footer.php'; ?>
    <?php endif; ?>

    <script src="assets/js/app.js?v=4.0"></script>
    <script src="assets/js/theme.js"></script>
</body>
</html>
