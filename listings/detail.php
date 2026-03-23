<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE)
    session_start();

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    redirect(APP_URL . '/listings/browse');
}

// İlan + ilgili bilgiler
$stmt = $db->prepare("
    SELECT l.*, c.name AS cat_name, c.icon AS cat_icon,
           u.id AS owner_id, u.name AS owner_name, u.rating AS owner_rating, u.review_count, u.city AS owner_city, u.is_verified AS owner_verified,
           h.title AS home_title, h.room_config, h.floor, h.has_elevator,
           h.bathroom_count, h.sqm, h.notes AS home_notes, h.photo AS home_photo,
           h.district, h.city AS home_city
    FROM listings l
    JOIN categories c ON l.category_id = c.id
    JOIN users u ON l.user_id = u.id
    JOIN homes h ON l.home_id = h.id
    WHERE l.id = ?
");
$stmt->execute([$id]);
$listing = $stmt->fetch();

if (!$listing) {
    redirect(APP_URL . '/listings/browse');
}

// Görüntüleme sayısını artır
incrementViewCount($id);

$isLoggedIn = isLoggedIn();
$user = $isLoggedIn ? currentUser() : null;
$isOwner = $user && $user['id'] == $listing['owner_id'];
$isWorker = $user && $user['role'] === 'worker';

// Favoride mi kontrolü
$isFavorite = false;
if ($isLoggedIn) {
    try {
        $favChk = $db->prepare("SELECT id FROM favorites WHERE user_id = ? AND listing_id = ?");
        $favChk->execute([$user['id'], $id]);
        $isFavorite = (bool)$favChk->fetch();
    } catch (Exception $e) {
        $isFavorite = false;
    }
}

// Kullanıcı zaten teklif verdi mi?
$hasOffer = false;
if ($isLoggedIn && !$isOwner) {
    $chk = $db->prepare("SELECT id FROM offers WHERE listing_id = ? AND user_id = ?");
    $chk->execute([$id, $user['id']]);
    $hasOffer = (bool) $chk->fetch();
}

// Deu011ferlendirmeyi getir (eu011fer tamamlandu0131ysa)
$review = null;
if ($listing['status'] === 'closed') {
    $revStmt = $db->prepare("SELECT * FROM reviews WHERE listing_id = ?");
    $revStmt->execute([$id]);
    $review = $revStmt->fetch();
}

// Teklifleri getir
$offersStmt = $db->prepare("
    SELECT o.*, u.name AS worker_name, u.rating, u.review_count, u.city AS worker_city, u.is_verified AS worker_verified
    FROM offers o
    JOIN users u ON o.user_id = u.id
    WHERE o.listing_id = ?
    ORDER BY o.created_at DESC
");
$offersStmt->execute([$id]);
$offers = $offersStmt->fetchAll();

// Teklif ver
$errors = [];
if ($isLoggedIn && !$isOwner && !$hasOffer && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Güvenlik hatası.';
    } else {
        $price = (float) ($_POST['price'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        if ($price <= 0)
            $errors[] = 'Geçerli bir fiyat girin.';
        if (strlen($message) < 10)
            $errors[] = 'Mesaj en az 10 karakter olmalı.';

        if (empty($errors)) {
            $db->prepare("INSERT INTO offers (listing_id, user_id, price, message) VALUES (?,?,?,?)")
                ->execute([$id, $user['id'], $price, $message]);
            // İlan sahibine bildirim
            createNotification(
                $listing['owner_id'],
                'new_offer',
                $user['name'] . ' ilana teklif verdi: ' . formatMoney($price),
                '/listings/detail.php?id=' . $id
            );
            setFlash('success', 'Teklifiniz gönderildi! 🎉');
            redirect(APP_URL . '/listings/detail.php?id=' . $id);
        }
    }
}

// Teklif kabul / reddet (sadece ilan sahibi)
if ($isOwner && isset($_GET['action'], $_GET['offer_id'])) {
    $ofId = (int) $_GET['offer_id'];
    $action = $_GET['action'];
    if (in_array($action, ['accept', 'reject'])) {
        $newStatus = $action === 'accept' ? 'accepted' : 'rejected';
        $db->prepare("UPDATE offers SET status = ? WHERE id = ? AND listing_id = ?")
            ->execute([$newStatus, $ofId, $id]);
        if ($action === 'accept') {
            $db->prepare("UPDATE listings SET status = 'in_progress' WHERE id = ?")->execute([$id]);
            // Para kazanan işçiye bildirim
            $offerRow = $db->prepare("SELECT user_id FROM offers WHERE id = ?");
            $offerRow->execute([$ofId]);
            $wId = $offerRow->fetchColumn();
            if ($wId)
                createNotification($wId, 'offer_accepted', 'Teklifiniz kabul edildi!', '/listings/detail.php?id=' . $id);
        }
        setFlash('success', $action === 'accept' ? '✅ Teklif kabul edildi!' : '❌ Teklif reddedildi.');
        redirect(APP_URL . '/listings/detail.php?id=' . $id);
    }
}

// İşi Tamamla ve Değerlendir (sadece ilan sahibi)
if ($isOwner && $listing['status'] === 'in_progress' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_job') {
    if (!verifyCsrf()) {
        $errors[] = 'Güvenlik hatası.';
    } else {
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        
        if ($rating < 1 || $rating > 5) $errors[] = 'Puan 1 ile 5 arasında olmalıdır.';
        
        if (empty($errors)) {
            // İşi tamamla
            $db->prepare("UPDATE listings SET status = 'closed' WHERE id = ?")->execute([$id]);
            
            // Kabul edilen teklifi ve işçiyi bul
            $accOffer = $db->prepare("SELECT user_id FROM offers WHERE listing_id = ? AND status = 'accepted' LIMIT 1");
            $accOffer->execute([$id]);
            $workerId = $accOffer->fetchColumn();
            
            if ($workerId) {
                // Yorum kaydet
                $db->prepare("INSERT INTO reviews (listing_id, reviewer_id, reviewee_id, rating, comment) VALUES (?, ?, ?, ?, ?)")
                   ->execute([$id, $user['id'], $workerId, $rating, $comment]);
                
                // İşçinin puan ortalamasını güncelle
                $db->prepare("UPDATE users SET 
                              rating = (SELECT AVG(rating) FROM reviews WHERE reviewee_id = ?),
                              review_count = (SELECT COUNT(*) FROM reviews WHERE reviewee_id = ?)
                              WHERE id = ?")
                   ->execute([$workerId, $workerId, $workerId]);
                
                // İşçiye bildirim
                createNotification($workerId, 'review', 'İlan tamamlandı ve size yeni bir değerlendirme yapıldı!', '/listings/detail.php?id='.$id);
            }
            
            setFlash('success', '✅ İş tamamlandı ve değerlendirmeniz kaydedildi. Teşekkürler!');
            redirect(APP_URL . '/listings/detail.php?id=' . $id);
        }
    }
}

$notifCount = $isLoggedIn ? getUnreadNotificationCount($user['id']) : 0;
$initials = $isLoggedIn ? strtoupper(substr($user['name'], 0, 1)) : '';
$times = getPreferredTimes();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= e($listing['title']) ?> — Temizci Burada
    </title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="../assets/css/style.css?v=4.0">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">

    <!-- SEO & Favicon -->
    <link rel="icon" href="/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="/logo.png">
    <meta property="og:image" content="https://www.temizciburada.com/logo.png">
</head>

<body>
    <?php if ($isLoggedIn): ?>
        <div class="app-layout">
            <?php include '../includes/sidebar.php'; ?>
            <div class="main-content">
                <?php $headerTitle = 'İlan Detayı'; include '../includes/app-header.php'; ?>

            <div class="page-content">
                <?php else: ?>
                    <nav class="navbar scrolled" style="background:rgba(255,255,255,0.95);">
                        <div class="navbar-inner container">
                            <a href="../index" class="navbar-logo">
                                <div class="logo-icon">🧹</div><span><span>Temizci Burada</span></span>
                            </a>
                            <div class="navbar-actions"><a href="../login" class="btn btn-outline btn-sm">Giriş
                                    Yap</a><a href="../register" class="btn btn-primary btn-sm">Kayıt Ol</a></div>
                        </div>
                    </nav>
                    <div style="padding:calc(var(--header-h)+24px) 0 60px;">
                        <div class="container">
                        <?php endif; ?>

                        <?= flashHtml() ?>

                        <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;">

                            <!-- Sol: İlan bilgileri -->
                            <div>
                                <!-- Hero -->
                                <div class="card mb-4" style="overflow:hidden;">
                                    <?php if ($listing['home_photo']): ?>
                                        <img src="<?= UPLOAD_URL . e($listing['home_photo']) ?>" alt="Ev Fotoğrafı"
                                            style="width:100%;height:240px;object-fit:cover;">
                                    <?php else: ?>
                                        <div
                                            style="width:100%;height:200px;background:var(--gradient);display:flex;align-items:center;justify-content:center;font-size:4rem;">
                                            <?= $listing['cat_icon'] ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <div
                                            style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
                                            <div>
                                                <div class="listing-cat" style="margin-bottom:10px;">
                                                    <?= $listing['cat_icon'] ?>
                                                    <?= e($listing['cat_name']) ?>
                                                </div>
                                                <h1 style="font-size:1.4rem;font-weight:800;margin-bottom:8px;">
                                                    <?= e($listing['title']) ?>
                                                </h1>
                                                <div
                                                    style="display:flex;gap:14px;flex-wrap:wrap;font-size:0.85rem;color:var(--text-muted);">
                                                    <span>📍
                                                        <?= e($listing['district'] ?: $listing['home_city']) ?>,
                                                        <?= e($listing['home_city']) ?>
                                                    </span>
                                                    <span>📅
                                                        <?= date('d M Y', strtotime($listing['preferred_date'])) ?>
                                                    </span>
                                                    <span>⏰
                                                        <?= $times[$listing['preferred_time']] ?? $listing['preferred_time'] ?>
                                                    </span>
                                                    <span>👁
                                                        <?= $listing['view_count'] ?> görüntülenme
                                                    </span>
                                                </div>
                                            </div>
                                            <div style="text-align:right;">
                                                <div style="display:flex;gap:8px;justify-content:flex-end;margin-bottom:8px;">
                                                    <?php if ($isLoggedIn): ?>
                                                    <button id="favBtn" onclick="toggleFavorite(<?= $id ?>)" title="Favorilere Ekle"
                                                        style="background:<?= $isFavorite ? 'rgba(239,68,68,0.1)' : 'var(--bg)' ?>;border:1px solid <?= $isFavorite ? '#ef4444' : 'var(--border)' ?>;border-radius:10px;width:40px;height:40px;cursor:pointer;font-size:1.15rem;display:flex;align-items:center;justify-content:center;transition:all 0.3s;">
                                                        <?= $isFavorite ? '❤️' : '🤍' ?>
                                                    </button>
                                                    <?php endif; ?>
                                                    <div style="position:relative;">
                                                        <button id="shareBtn" onclick="toggleShareMenu()" title="Paylaş"
                                                            style="background:var(--bg);border:1px solid var(--border);border-radius:10px;width:40px;height:40px;cursor:pointer;font-size:1.15rem;display:flex;align-items:center;justify-content:center;transition:all 0.2s;">
                                                            📤
                                                        </button>
                                                        <div id="shareMenu" style="display:none;position:absolute;right:0;top:46px;background:#fff;border:1px solid var(--border);border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,0.12);padding:8px;z-index:100;min-width:200px;">
                                                            <a href="https://wa.me/?text=<?= urlencode($listing['title'] . ' — ' . APP_URL . '/listings/detail?id=' . $id) ?>" target="_blank"
                                                                style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;text-decoration:none;color:var(--text);font-size:0.88rem;font-weight:500;transition:background 0.2s;"
                                                                onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
                                                                <span style="font-size:1.2rem;">💬</span> WhatsApp
                                                            </a>
                                                            <a href="https://twitter.com/intent/tweet?text=<?= urlencode($listing['title'] . ' — Temizci Burada') ?>&url=<?= urlencode(APP_URL . '/listings/detail?id=' . $id) ?>" target="_blank"
                                                                style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;text-decoration:none;color:var(--text);font-size:0.88rem;font-weight:500;transition:background 0.2s;"
                                                                onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
                                                                <span style="font-size:1.2rem;">🐦</span> Twitter / X
                                                            </a>
                                                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(APP_URL . '/listings/detail?id=' . $id) ?>" target="_blank"
                                                                style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;text-decoration:none;color:var(--text);font-size:0.88rem;font-weight:500;transition:background 0.2s;"
                                                                onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
                                                                <span style="font-size:1.2rem;">📘</span> Facebook
                                                            </a>
                                                            <div style="border-top:1px solid var(--border);margin:4px 0;"></div>
                                                            <button onclick="copyLink()" 
                                                                style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;background:none;border:none;color:var(--text);font-size:0.88rem;font-weight:500;width:100%;cursor:pointer;transition:background 0.2s;"
                                                                onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
                                                                <span style="font-size:1.2rem;">📋</span> <span id="copyText">Linki Kopyala</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?= statusBadge($listing['status']) ?>
                                                <?php if($listing['is_recurring']): ?>
                                                    <span class="badge" style="background:rgba(16,185,129,0.1);color:#10b981;border:1px solid #10b981;">🔄 Periyodik İlan</span>
                                                <?php endif; ?>
                                                <?php if ($listing['budget']): ?>
                                                    <div
                                                        style="font-weight:800;font-size:1.3rem;color:var(--secondary);margin-top:6px;">
                                                        <?= formatMoney($listing['budget']) ?>
                                                    </div>
                                                    <div style="font-size:0.78rem;color:var(--text-muted);">Bütçe</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <p style="color:var(--text-secondary);line-height:1.7;">
                                            <?= nl2br(e($listing['description'])) ?>
                                        </p>
                                    </div>
                                </div>

                                <!-- Ev Bilgileri -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <div class="card-title">🏠 Ev Bilgileri</div>
                                    </div>
                                    <div class="card-body">
                                        <div
                                            style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:14px;">
                                            <div
                                                style="background:var(--bg);border-radius:var(--radius-sm);padding:14px;text-align:center;">
                                                <div style="font-size:1.5rem;">🛏️</div>
                                                <div style="font-weight:700;font-size:0.95rem;margin-top:4px;">
                                                    <?= e($listing['room_config']) ?>
                                                </div>
                                                <div style="font-size:0.75rem;color:var(--text-muted);">Oda Yapısı</div>
                                            </div>
                                            <div
                                                style="background:var(--bg);border-radius:var(--radius-sm);padding:14px;text-align:center;">
                                                <div style="font-size:1.5rem;">🛁</div>
                                                <div style="font-weight:700;font-size:0.95rem;margin-top:4px;">
                                                    <?= $listing['bathroom_count'] ?>
                                                </div>
                                                <div style="font-size:0.75rem;color:var(--text-muted);">Banyo</div>
                                            </div>
                                            <?php if ($listing['floor']): ?>
                                                <div
                                                    style="background:var(--bg);border-radius:var(--radius-sm);padding:14px;text-align:center;">
                                                    <div style="font-size:1.5rem;">🏢</div>
                                                    <div style="font-weight:700;font-size:0.95rem;margin-top:4px;">
                                                        <?= $listing['floor'] ?>. Kat
                                                    </div>
                                                    <div style="font-size:0.75rem;color:var(--text-muted);">Kat</div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($listing['sqm']): ?>
                                                <div
                                                    style="background:var(--bg);border-radius:var(--radius-sm);padding:14px;text-align:center;">
                                                    <div style="font-size:1.5rem;">📐</div>
                                                    <div style="font-weight:700;font-size:0.95rem;margin-top:4px;">
                                                        <?= $listing['sqm'] ?>m²
                                                    </div>
                                                    <div style="font-size:0.75rem;color:var(--text-muted);">Alan</div>
                                                </div>
                                            <?php endif; ?>
                                            <div
                                                style="background:var(--bg);border-radius:var(--radius-sm);padding:14px;text-align:center;">
                                                <div style="font-size:1.5rem;">
                                                    <?= $listing['has_elevator'] ? '✅' : '❌' ?>
                                                </div>
                                                <div style="font-weight:700;font-size:0.95rem;margin-top:4px;">
                                                    <?= $listing['has_elevator'] ? 'Var' : 'Yok' ?>
                                                </div>
                                                <div style="font-size:0.75rem;color:var(--text-muted);">Asansör</div>
                                            </div>
                                            <div
                                                style="background:var(--bg);border-radius:var(--radius-sm);padding:14px;text-align:center;">
                                                <div style="font-size:1.5rem;">
                                                    <?= $listing['owner_home'] ? '🏠' : '🚪' ?>
                                                </div>
                                                <div style="font-weight:700;font-size:0.95rem;margin-top:4px;">
                                                    <?= $listing['owner_home'] ? 'Evde' : 'Evde yok' ?>
                                                </div>
                                                <div style="font-size:0.75rem;color:var(--text-muted);">İş sırasında
                                                </div>
                                            </div>
                                        </div>
                                        <?php if ($listing['home_notes']): ?>
                                            <div
                                                style="margin-top:16px;padding:14px;background:rgba(245,158,11,0.06);border-radius:var(--radius-sm);border-left:3px solid #f59e0b;">
                                                <div
                                                    style="font-weight:600;font-size:0.85rem;color:#92400e;margin-bottom:4px;">
                                                    📝 Ev Notları</div>
                                                <p style="font-size:0.85rem;color:var(--text-secondary);">
                                                    <?= nl2br(e($listing['home_notes'])) ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Teklifler -->
                                <div class="card">
                                    <div class="card-header">
                                        <div class="card-title">💬 Teklifler (
                                            <?= count($offers) ?>)
                                        </div>
                                    </div>
                                    <?php if (empty($offers)): ?>
                                        <div class="empty-state" style="padding:40px;">
                                            <div class="empty-state-icon" style="font-size:2.5rem;">💬</div>
                                            <h3>Henüz teklif yok</h3>
                                            <p>Bu ilan için ilk teklifi veren siz olun!</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($offers as $offer): ?>
                                            <div style="padding:18px 22px;border-bottom:1px solid var(--border-light);">
                                                <div
                                                    style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                                                    <div style="display:flex;align-items:center;gap:12px;flex:1;">
                                                        <div class="avatar avatar-md"
                                                            style="background:var(--gradient);flex-shrink:0;">
                                                            <?= strtoupper(substr($offer['worker_name'], 0, 1)) ?>
                                                        </div>
                                                        <div>
                                                            <div style="font-weight:700;margin-bottom:3px;display:flex;align-items:center;gap:6px;">
                                                                <a href="<?= APP_URL ?>/worker_profile?id=<?= $offer['user_id'] ?>" style="color:inherit;text-decoration:none;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='inherit'"><?= e($offer['worker_name']) ?></a>
                                                                <?php if($offer['worker_verified']): ?>
                                                                    <span title="Doğrulanmış Profil" style="color:#10b981;font-size:1rem;">✅</span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div style="font-size:0.78rem;color:var(--text-muted);">
                                                                <?= $offer['review_count'] > 0 ? starRating($offer['rating']) . ' ' . number_format($offer['rating'], 1) . ' (' . $offer['review_count'] . ' değerlendirme)' : 'Yeni üye' ?>
                                                                <?= $offer['worker_city'] ? ' · ' . e($offer['worker_city']) : '' ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div style="text-align:right;flex-shrink:0;">
                                                        <div style="font-weight:800;font-size:1.1rem;color:var(--secondary);">
                                                            <?= formatMoney($offer['price']) ?>
                                                        </div>
                                                        <div style="font-size:0.75rem;color:var(--text-muted);">
                                                            <?= timeAgo($offer['created_at']) ?>
                                                        </div>
                                                        <?= offerStatusBadge($offer['status']) ?>
                                                    </div>
                                                </div>
                                                <p
                                                    style="margin-top:12px;font-size:0.88rem;color:var(--text-secondary);line-height:1.6;padding-left:56px;">
                                                    <?= nl2br(e($offer['message'])) ?>
                                                </p>
                                                <?php if ($isOwner && $offer['status'] === 'pending' && $listing['status'] === 'open'): ?>
                                                    <div style="display:flex;gap:8px;padding-left:56px;margin-top:10px;">
                                                        <a href="detail?id=<?= $id ?>&action=accept&offer_id=<?= $offer['id'] ?>"
                                                            class="btn btn-secondary btn-sm"
                                                            data-confirm="Bu teklifi kabul etmek istediğinizden emin misiniz?">✅ Kabul Et</a>
                                                        <a href="../messages.php?uid=<?= $offer['user_id'] ?>" class="btn btn-outline btn-sm">💬 Mesaj Gönder</a>
                                                        <a href="detail?id=<?= $id ?>&action=reject&offer_id=<?= $offer['id'] ?>"
                                                            class="btn btn-ghost btn-sm"
                                                            data-confirm="Bu teklifi reddetmek istiyor musunuz?">❌ Reddet</a>
                                                    </div>
                                                <?php elseif ($isOwner): ?>
                                                    <div style="display:flex;gap:8px;padding-left:56px;margin-top:10px;">
                                                        <a href="../messages.php?uid=<?= $offer['user_id'] ?>" class="btn btn-outline btn-sm">💬 Mesaj Gönder</a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Sağ: Teklif Ver Formu + İlan Sahibi -->
                            <div>
                                <!-- İlan Sahibi -->
                                <div class="card mb-4">
                                    <div class="card-body" style="text-align:center;">
                                        <div class="avatar avatar-lg"
                                            style="background:var(--gradient);margin:0 auto 12px;">
                                            <?= strtoupper(substr($listing['owner_name'], 0, 1)) ?>
                                        </div>
                                        <div style="font-weight:700;margin-bottom:4px;display:flex;align-items:center;justify-content:center;gap:6px;">
                                            <?= e($listing['owner_name']) ?>
                                            <?php if($listing['owner_verified']): ?>
                                                <span title="Doğrulanmış Profil" style="color:#10b981;font-size:1.1rem;">✅</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size:0.82rem;color:var(--text-muted);margin-bottom:8px;">🏠 Ev
                                            Sahibi
                                            <?= $listing['owner_city'] ? ' · ' . e($listing['owner_city']) : '' ?>
                                        </div>
                                        <?php if ($listing['review_count'] > 0): ?>
                                            <div>
                                                <?= starRating($listing['owner_rating']) ?>
                                            </div>
                                            <div style="font-size:0.78rem;color:var(--text-muted);">
                                                <?= number_format($listing['owner_rating'], 1) ?> (
                                                <?= $listing['review_count'] ?> yorum)
                                            </div>
                                        <?php else: ?>
                                            <div style="font-size:0.78rem;color:var(--text-muted);">Henüz değerlendirme yok
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Teklif Ver -->
                                <?php if (!$isLoggedIn): ?>
                                    <div class="card">
                                        <div class="card-body" style="text-align:center;">
                                            <div style="font-size:2rem;margin-bottom:12px;">🔐</div>
                                            <h3 style="margin-bottom:8px;">Teklif vermek için giriş yapın</h3>
                                            <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:16px;">
                                                Ücretsiz kayıt olun ve hemen teklif verin!</p>
                                            <a href="../login" class="btn btn-primary btn-block mb-2">Giriş Yap</a>
                                            <a href="../register" class="btn btn-outline btn-block">Kayıt Ol</a>
                                        </div>
                                    </div>
                                <?php elseif ($isOwner && $listing['status'] === 'in_progress'): ?>
                                    <!-- İşi Tamamlama Formu -->
                                    <div class="card">
                                        <div class="card-header">
                                            <div class="card-title">✅ İşi Tamamla</div>
                                        </div>
                                        <div class="card-body">
                                            <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:16px;">
                                                Hizmet tamamlandıysa işi kapatabilir ve hizmet vereni değerlendirebilirsiniz.
                                            </p>
                                            <form method="POST">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="complete_job">
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Puanınız (1-5)</label>
                                                    <div class="rating-input" style="display:flex;gap:10px;justify-content:center;margin:10px 0;">
                                                        <?php for($i=1; $i<=5; $i++): ?>
                                                            <label style="cursor:pointer;">
                                                                <input type="radio" name="rating" value="<?= $i ?>" required style="display:none;" onclick="updateRatingUI(<?= $i ?>)">
                                                                <span class="star-input" id="star-<?= $i ?>" style="font-size:2rem;color:var(--text-muted); transition:var(--transition);">★</span>
                                                            </label>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">Yorumunuz</label>
                                                    <textarea name="comment" class="form-control" rows="3" placeholder="Hizmetten memnun kaldınız mı?"></textarea>
                                                </div>

                                                <button type="submit" class="btn btn-secondary btn-block">Tamamla ve Değerlendir</button>
                                            </form>
                                        </div>
                                    </div>

                                    <script>
                                    function updateRatingUI(rating) {
                                        for(let i=1; i<=5; i++) {
                                            document.getElementById('star-'+i).style.color = (i <= rating) ? '#f59e0b' : 'var(--text-muted)';
                                        }
                                    }
                                    </script>

                                <?php elseif ($isOwner): ?>
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="flash flash-info" style="margin-bottom:12px;">ℹ️ Bu sizin
                                                ilanınızdır.</div>
                                            <a href="../listings/my_listings" class="btn btn-ghost btn-block mb-2">📋
                                                İlanlarım</a>
                                            <a href="browse" class="btn btn-outline btn-block">🔍 Diğer İlanlar</a>
                                        </div>
                                    </div>
                                <?php elseif ($hasOffer): ?>
                                    <div class="card">
                                        <div class="card-body" style="text-align:center;">
                                            <div style="font-size:2rem;margin-bottom:8px;">✅</div>
                                            <div style="font-weight:700;">Teklifiniz gönderildi!</div>
                                            <p style="font-size:0.85rem;color:var(--text-muted);margin-top:8px;">İlan sahibi
                                                teklifinizi inceleyecek.</p>
                                        </div>
                                    </div>
                                <?php elseif ($listing['status'] === 'closed'): ?>
                                    <div class="card">
                                        <div class="card-body" style="text-align:center;">
                                            <div style="font-size:2rem;margin-bottom:8px;">✅</div>
                                            <div style="font-weight:700;margin-bottom:12px;">Bu iş tamamlandı</div>
                                            
                                            <?php if ($review): ?>
                                                <div style="background:var(--bg);padding:14px;border-radius:var(--radius-sm);text-align:left;">
                                                    <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                                                        <span style="font-weight:700;">Değerlendirme</span>
                                                        <span><?= starRating($review['rating']) ?></span>
                                                    </div>
                                                    <p style="font-size:0.85rem;font-style:italic;color:var(--text-secondary);">
                                                        "<?= e($review['comment']) ?>"
                                                    </p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="card">
                                        <div class="card-body" style="text-align:center;">
                                            <div style="font-size:2rem;margin-bottom:8px;">🔒</div>
                                            <div style="font-weight:700;">Bu ilan artık aktif değil</div>
                                        </div>
                                    </div>
                                        <div class="card-body">
                                            <?php if (!empty($errors)): ?>
                                                <div class="flash flash-error">❌
                                                    <?= e(implode('<br>', $errors)) ?>
                                                </div>
                                            <?php endif; ?>
                                            <form method="POST" data-validate>
                                                <?= csrfField() ?>
                                                <div class="form-group">
                                                    <label class="form-label" for="price">Teklif Fiyatı (₺) *</label>
                                                    <input type="number" id="price" name="price" class="form-control"
                                                        placeholder="350" min="1" max="99999" required
                                                        value="<?= e($_POST['price'] ?? '') ?>">
                                                    <?php if ($listing['budget']): ?>
                                                        <div class="form-hint">İlan bütçesi:
                                                            <?= formatMoney($listing['budget']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label" for="message">Mesajınız *</label>
                                                    <textarea id="message" name="message" class="form-control" rows="4"
                                                        required minlength="10" maxlength="600"
                                                        placeholder="Kendinizi tanıtın, deneyimlerinizden bahsedin, işi nasıl yapacağınızı açıklayın..."><?= e($_POST['message'] ?? '') ?></textarea>
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-block btn-lg">🚀 Teklifi
                                                    Gönder</button>
                                            </form>
                                        </div>
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
            </div>
        </div>
    <?php endif; ?>

    <script src="../assets/js/app.js?v=4.0"></script>
    <script src="../assets/js/theme.js"></script>
    <style>
        @media(max-width:768px) {
            div[style*="grid-template-columns:1fr 360px"] {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
    <script>
    // ===== Favori Toggle =====
    function toggleFavorite(listingId) {
        const btn = document.getElementById('favBtn');
        btn.style.transform = 'scale(1.3)';
        setTimeout(() => btn.style.transform = 'scale(1)', 200);

        fetch('<?= APP_URL ?>/api/favorites', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ listing_id: listingId })
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                if (data.action === 'added') {
                    btn.innerHTML = '❤️';
                    btn.style.background = 'rgba(239,68,68,0.1)';
                    btn.style.borderColor = '#ef4444';
                } else {
                    btn.innerHTML = '🤍';
                    btn.style.background = 'var(--bg)';
                    btn.style.borderColor = 'var(--border)';
                }
            }
        });
    }

    // ===== Paylaş Menü Toggle =====
    function toggleShareMenu() {
        const menu = document.getElementById('shareMenu');
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }
    document.addEventListener('click', function(e) {
        const menu = document.getElementById('shareMenu');
        const btn = document.getElementById('shareBtn');
        if (menu && btn && !btn.contains(e.target) && !menu.contains(e.target)) {
            menu.style.display = 'none';
        }
    });

    // ===== Link Kopyala =====
    function copyLink() {
        const url = '<?= APP_URL ?>/listings/detail?id=<?= $id ?>';
        navigator.clipboard.writeText(url).then(() => {
            document.getElementById('copyText').textContent = '✅ Kopyalandı!';
            setTimeout(() => document.getElementById('copyText').textContent = 'Linki Kopyala', 2000);
        });
    }
    </script>
</body>

</html>