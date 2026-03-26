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

// Eski kayıtlarda/sütun eksikliği durumlarında uyarı almamak için varsayılan değer.
$listing['is_recurring'] = (int) ($listing['is_recurring'] ?? 0);

// Görüntüleme sayısını artır
incrementViewCount($id);

$isLoggedIn = isLoggedIn();
$user = $isLoggedIn ? currentUser() : null;
$isOwner = $user && $user['id'] == $listing['owner_id'];
$isWorker = $user && $user['role'] === 'worker';

// Teklif pazarlik alanlari yoksa ekle (idempotent).
try {
    $offerCols = $db->query("SHOW COLUMNS FROM offers")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('counter_price', $offerCols, true)) {
        $db->exec("ALTER TABLE offers ADD COLUMN counter_price DECIMAL(10,2) NULL AFTER price");
    }
    if (!in_array('counter_note', $offerCols, true)) {
        $db->exec("ALTER TABLE offers ADD COLUMN counter_note VARCHAR(500) NULL AFTER counter_price");
    }
    if (!in_array('counter_status', $offerCols, true)) {
        $db->exec("ALTER TABLE offers ADD COLUMN counter_status ENUM('none','pending','accepted','rejected') NOT NULL DEFAULT 'none' AFTER counter_note");
    }
} catch (Exception $e) {}

try {
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
} catch (Exception $e) {}

// Worker ayni tarih/saatte baska kabul edilmis isi var mi?
$workerHasScheduleConflict = function (int $workerId) use ($db, $listing, $id): bool {
    $q = $db->prepare("
        SELECT 1
        FROM offers o
        JOIN listings l2 ON l2.id = o.listing_id
        WHERE o.user_id = ?
          AND o.status = 'accepted'
          AND l2.id != ?
          AND l2.status IN ('open', 'in_progress')
          AND l2.preferred_date = ?
          AND l2.preferred_time = ?
        LIMIT 1
    ");
    $q->execute([$workerId, $id, $listing['preferred_date'], $listing['preferred_time']]);
    return (bool) $q->fetchColumn();
};

// Favoride mi kontrolü
$isFavorite = false;
if ($isLoggedIn) {
    try {
        $favChk = $db->prepare("SELECT id FROM fav_store_v2 WHERE user_id = ? AND listing_id = ?");
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

try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS listing_completion_proofs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            listing_id INT NOT NULL UNIQUE,
            accepted_worker_id INT NOT NULL,
            worker_confirmed TINYINT(1) NOT NULL DEFAULT 0,
            owner_confirmed TINYINT(1) NOT NULL DEFAULT 0,
            completion_code VARCHAR(6) NULL,
            proof_photo VARCHAR(255) NULL,
            worker_note TEXT NULL,
            owner_note TEXT NULL,
            worker_confirmed_at DATETIME NULL,
            owner_confirmed_at DATETIME NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $cols = $db->query("SHOW COLUMNS FROM listing_completion_proofs")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('completion_code', $cols, true)) {
        $db->exec("ALTER TABLE listing_completion_proofs ADD COLUMN completion_code VARCHAR(6) NULL AFTER owner_confirmed");
    }
    $db->exec("
        CREATE TABLE IF NOT EXISTS listing_completion_photos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            completion_id INT NOT NULL,
            photo_path VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_completion_photos_completion (completion_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (Exception $e) {}

$acceptedOfferStmt = $db->prepare("SELECT id, user_id, price FROM offers WHERE listing_id = ? AND status = 'accepted' LIMIT 1");
$acceptedOfferStmt->execute([$id]);
$acceptedOffer = $acceptedOfferStmt->fetch();
$acceptedWorkerId = (int) ($acceptedOffer['user_id'] ?? 0);

$completionRow = null;
$completionPhotos = [];
if ($acceptedWorkerId > 0) {
    $cr = $db->prepare("SELECT * FROM listing_completion_proofs WHERE listing_id = ? LIMIT 1");
    $cr->execute([$id]);
    $completionRow = $cr->fetch();
    if ($completionRow) {
        $cp = $db->prepare("SELECT photo_path FROM listing_completion_photos WHERE completion_id = ? ORDER BY id DESC");
        $cp->execute([(int) $completionRow['id']]);
        $completionPhotos = array_map(
            static fn($r) => $r['photo_path'],
            $cp->fetchAll()
        );
    }
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
$offerPrices = array_column($offers, 'price');
$minOfferPrice = !empty($offerPrices) ? (float) min($offerPrices) : 0.0;
$maxOfferPrice = !empty($offerPrices) ? (float) max($offerPrices) : 0.0;
$offerTimeline = [];
if (!empty($offers)) {
    $offerIds = array_map('intval', array_column($offers, 'id'));
    $ph = implode(',', array_fill(0, count($offerIds), '?'));
    $logStmt = $db->prepare("
        SELECT n.*, u.name AS actor_name
        FROM offer_negotiations n
        JOIN users u ON u.id = n.actor_id
        WHERE n.offer_id IN ($ph)
        ORDER BY n.created_at DESC
    ");
    $logStmt->execute($offerIds);
    foreach ($logStmt->fetchAll() as $logRow) {
        $oid = (int) $logRow['offer_id'];
        if (!isset($offerTimeline[$oid])) {
            $offerTimeline[$oid] = [];
        }
        $offerTimeline[$oid][] = $logRow;
    }
}

// Teklif ver
$errors = [];
if ($isOwner && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_counter') {
    if (!verifyCsrf()) {
        $errors[] = 'Guvenlik hatasi.';
    } else {
        $offerId = (int) ($_POST['offer_id'] ?? 0);
        $counterPrice = (float) ($_POST['counter_price'] ?? 0);
        $counterNote = trim($_POST['counter_note'] ?? '');
        if ($offerId <= 0 || $counterPrice <= 0) {
            $errors[] = 'Gecerli karsi teklif giriniz.';
        } else {
            $chk = $db->prepare("SELECT id, user_id FROM offers WHERE id = ? AND listing_id = ? AND status = 'pending'");
            $chk->execute([$offerId, $id]);
            $row = $chk->fetch();
            if (!$row) {
                $errors[] = 'Teklif bulunamadi veya uygun degil.';
            } else {
                $db->prepare("UPDATE offers SET counter_price = ?, counter_note = ?, counter_status = 'pending' WHERE id = ?")
                    ->execute([$counterPrice, $counterNote !== '' ? $counterNote : null, $offerId]);
                $db->prepare("INSERT INTO offer_negotiations (offer_id, actor_id, event_type, note) VALUES (?, ?, 'counter_sent', ?)")
                    ->execute([$offerId, $user['id'], $counterNote !== '' ? $counterNote : null]);
                createNotification((int) $row['user_id'], 'counter_offer', 'Ilan sahibi teklifinize karsi teklif sundu.', '/listings/detail.php?id=' . $id);
                setFlash('success', 'Karsi teklif gonderildi.');
                redirect(APP_URL . '/listings/detail.php?id=' . $id);
            }
        }
    }
}

if (
    $isWorker &&
    $listing['status'] === 'in_progress' &&
    (int) ($user['id'] ?? 0) === $acceptedWorkerId &&
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'worker_confirm_completion'
) {
    if (!verifyCsrf()) {
        $errors[] = 'Guvenlik hatasi.';
    } else {
        $workerNote = trim($_POST['worker_note'] ?? '');
        $completionCode = trim($_POST['completion_code'] ?? '');
        if (!preg_match('/^\d{6}$/', $completionCode)) {
            $errors[] = '6 haneli guvenli onay kodu giriniz.';
        }
        $newProofPhoto = null;
        if (isset($_FILES['proof_photo']) && (int) ($_FILES['proof_photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $uploaded = uploadFile($_FILES['proof_photo'], 'proofs');
            if ($uploaded === false) {
                $errors[] = 'Kanit fotografi yuklenemedi. JPG/PNG/WEBP/GIF ve maksimum 5MB olmali.';
            } else {
                $newProofPhoto = $uploaded;
            }
        }
        $uploadedGallery = [];
        if (isset($_FILES['proof_photos']) && is_array($_FILES['proof_photos']['name'] ?? null)) {
            $totalFiles = count($_FILES['proof_photos']['name']);
            for ($i = 0; $i < $totalFiles; $i++) {
                $err = (int) ($_FILES['proof_photos']['error'][$i] ?? UPLOAD_ERR_NO_FILE);
                if ($err === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $single = [
                    'name' => $_FILES['proof_photos']['name'][$i] ?? '',
                    'type' => $_FILES['proof_photos']['type'][$i] ?? '',
                    'tmp_name' => $_FILES['proof_photos']['tmp_name'][$i] ?? '',
                    'error' => $err,
                    'size' => (int) ($_FILES['proof_photos']['size'][$i] ?? 0),
                ];
                $u = uploadFile($single, 'proofs');
                if ($u === false) {
                    $errors[] = 'Galeri fotograf(lar)i yuklenemedi. Dosyalari kontrol edin.';
                    break;
                }
                $uploadedGallery[] = $u;
            }
        }

        if (empty($completionRow) && $newProofPhoto === null && empty($uploadedGallery)) {
            $errors[] = 'Ilk tamamlanma onayi icin en az bir fotograf kaniti yukleyin.';
        }

        if (empty($errors)) {
            $proofToSave = $newProofPhoto ?? ($uploadedGallery[0] ?? ($completionRow['proof_photo'] ?? null));
            $up = $db->prepare("
                INSERT INTO listing_completion_proofs
                    (listing_id, accepted_worker_id, worker_confirmed, owner_confirmed, completion_code, proof_photo, worker_note, worker_confirmed_at)
                VALUES (?, ?, 1, 0, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    accepted_worker_id = VALUES(accepted_worker_id),
                    worker_confirmed = 1,
                    completion_code = VALUES(completion_code),
                    proof_photo = COALESCE(VALUES(proof_photo), proof_photo),
                    worker_note = VALUES(worker_note),
                    worker_confirmed_at = NOW()
            ");
            $up->execute([$id, $acceptedWorkerId, $completionCode, $proofToSave, $workerNote !== '' ? $workerNote : null]);
            $cidStmt = $db->prepare("SELECT id FROM listing_completion_proofs WHERE listing_id = ? LIMIT 1");
            $cidStmt->execute([$id]);
            $completionId = (int) $cidStmt->fetchColumn();
            if ($completionId > 0 && !empty($uploadedGallery)) {
                $insPhoto = $db->prepare("INSERT INTO listing_completion_photos (completion_id, photo_path) VALUES (?, ?)");
                foreach ($uploadedGallery as $pPath) {
                    $insPhoto->execute([$completionId, $pPath]);
                }
            }
            createNotification($listing['owner_id'], 'completion_requested', 'Is tamamlandi bildirimi ve kanit gonderildi.', '/listings/detail.php?id=' . $id);
            setFlash('success', 'Tamamlanma kaniti iletildi. Ev sahibinin onayi bekleniyor.');
            redirect(APP_URL . '/listings/detail.php?id=' . $id);
        }
    }
}

if ($isWorker && $_SERVER['REQUEST_METHOD'] === 'POST' && in_array(($_POST['action'] ?? ''), ['counter_accept', 'counter_reject'], true)) {
    if (!verifyCsrf()) {
        $errors[] = 'Guvenlik hatasi.';
    } else {
        $offerId = (int) ($_POST['offer_id'] ?? 0);
        $a = $_POST['action'];
        $chk = $db->prepare("SELECT * FROM offers WHERE id = ? AND listing_id = ? AND user_id = ? AND counter_status = 'pending'");
        $chk->execute([$offerId, $id, $user['id']]);
        $myOffer = $chk->fetch();
        if (!$myOffer) {
            $errors[] = 'Karsi teklif bulunamadi.';
        } else {
            if ($a === 'counter_accept') {
                if ($workerHasScheduleConflict((int) $user['id'])) {
                    $errors[] = 'Bu saatte baska kabul edilmis bir isin var. Karsi teklif kabul edilemedi.';
                } else {
                    $db->prepare("UPDATE offers SET price = COALESCE(counter_price, price), status = 'accepted', counter_status = 'accepted' WHERE id = ?")
                        ->execute([$offerId]);
                    $db->prepare("UPDATE listings SET status = 'in_progress' WHERE id = ?")->execute([$id]);
                    $db->prepare("INSERT INTO offer_negotiations (offer_id, actor_id, event_type) VALUES (?, ?, 'counter_accepted')")
                        ->execute([$offerId, $user['id']]);
                    createNotification($listing['owner_id'], 'counter_accepted', 'Karsi teklifiniz kabul edildi.', '/listings/detail.php?id=' . $id);
                    setFlash('success', 'Karsi teklif kabul edildi.');
                    redirect(APP_URL . '/listings/detail.php?id=' . $id);
                }
            } else {
                $db->prepare("UPDATE offers SET counter_status = 'rejected' WHERE id = ?")->execute([$offerId]);
                $db->prepare("INSERT INTO offer_negotiations (offer_id, actor_id, event_type) VALUES (?, ?, 'counter_rejected')")
                    ->execute([$offerId, $user['id']]);
                createNotification($listing['owner_id'], 'counter_rejected', 'Karsi teklifiniz reddedildi.', '/listings/detail.php?id=' . $id);
                setFlash('success', 'Karsi teklif reddedildi.');
                redirect(APP_URL . '/listings/detail.php?id=' . $id);
            }
        }
    }
}

if ($isLoggedIn && !$isOwner && !$hasOffer && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_offer') {
    if (!verifyCsrf()) {
        $errors[] = 'Güvenlik hatası.';
    } else {
        $price = (float) ($_POST['price'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        if ($price <= 0)
            $errors[] = 'Geçerli bir fiyat girin.';
        if (strlen($message) < 10)
            $errors[] = 'Mesaj en az 10 karakter olmalı.';

        // Worker takvim uygunsa teklif gonderebilsin.
        try {
            $availCnt = $db->prepare("SELECT COUNT(*) FROM availability WHERE user_id = ?");
            $availCnt->execute([$user['id']]);
            $hasAnyAvailability = ((int) $availCnt->fetchColumn()) > 0;
            if ($hasAnyAvailability) {
                $slot = $listing['preferred_time'] === 'esnek' ? 'tum_gun' : $listing['preferred_time'];
                $avail = $db->prepare("
                    SELECT 1 FROM availability
                    WHERE user_id = ? AND available_date = ?
                      AND (time_slot = 'tum_gun' OR time_slot = ?)
                    LIMIT 1
                ");
                $avail->execute([$user['id'], $listing['preferred_date'], $slot]);
                if (!$avail->fetch()) {
                    $errors[] = 'Takviminde bu tarih/saat musait gorunmuyor.';
                }
            }
        } catch (Exception $e) {}

        if (empty($errors)) {
            $db->prepare("INSERT INTO offers (listing_id, user_id, price, message) VALUES (?,?,?,?)")
                ->execute([$id, $user['id'], $price, $message]);
            $newOfferId = (int) $db->lastInsertId();
            $db->prepare("INSERT INTO offer_negotiations (offer_id, actor_id, event_type, note) VALUES (?, ?, 'offer_sent', ?)")
                ->execute([$newOfferId, $user['id'], $message]);
            // İlan sahibine bildirim
            createNotification(
                $listing['owner_id'],
                'new_offer',
                $user['name'] . ' ilana teklif verdi: ' . formatMoney($price),
                '/listings/detail.php?id=' . $id
            );

            // Favoriye alan kullanıcılara da ilan hareket bildirimi
            try {
                $favUsers = $db->prepare("SELECT user_id FROM fav_store_v2 WHERE listing_id = ? AND user_id != ?");
                $favUsers->execute([$id, $user['id']]);
                foreach ($favUsers->fetchAll(PDO::FETCH_COLUMN) as $favUserId) {
                    createNotification(
                        (int) $favUserId,
                        'favorite_update',
                        $listing['title'] . ' ilanina yeni teklif geldi.',
                        '/listings/detail.php?id=' . $id
                    );
                }
            } catch (Exception $e) {
                // Bildirim hatası ana akışı kesmemeli.
            }

            setFlash('success', 'Teklifiniz gönderildi!');
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
        if ($action === 'accept') {
            $workerIdStmt = $db->prepare("SELECT user_id FROM offers WHERE id = ? AND listing_id = ?");
            $workerIdStmt->execute([$ofId, $id]);
            $offerWorkerId = (int) $workerIdStmt->fetchColumn();
            if ($offerWorkerId > 0 && $workerHasScheduleConflict($offerWorkerId)) {
                setFlash('error', 'Bu temizlikci ayni saatte baska kabul edilmis bir iste gorunuyor.');
                redirect(APP_URL . '/listings/detail.php?id=' . $id);
            }
        }
        $db->prepare("UPDATE offers SET status = ? WHERE id = ? AND listing_id = ?")
            ->execute([$newStatus, $ofId, $id]);
        $db->prepare("INSERT INTO offer_negotiations (offer_id, actor_id, event_type) VALUES (?, ?, ?)")
            ->execute([$ofId, $user['id'], $action === 'accept' ? 'offer_accepted' : 'offer_rejected']);
        if ($action === 'accept') {
            $db->prepare("UPDATE listings SET status = 'in_progress' WHERE id = ?")->execute([$id]);
            // Para kazanan işçiye bildirim
            $offerRow = $db->prepare("SELECT user_id FROM offers WHERE id = ?");
            $offerRow->execute([$ofId]);
            $wId = $offerRow->fetchColumn();
            if ($wId)
                createNotification($wId, 'offer_accepted', 'Teklifiniz kabul edildi!', '/listings/detail.php?id=' . $id);
        }
        setFlash('success', $action === 'accept' ? ' Teklif kabul edildi!' : ' Teklif reddedildi.');
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
        $completionCodeVerify = trim($_POST['completion_code_verify'] ?? '');
        
        if ($rating < 1 || $rating > 5) $errors[] = 'Puan 1 ile 5 arasında olmalıdır.';
        
        if (empty($errors)) {
            if (empty($completionRow) || (int) ($completionRow['worker_confirmed'] ?? 0) !== 1) {
                $errors[] = 'Is kapatma icin once hizmet verenin kanitli tamamlanma onayi gerekli.';
            } elseif (($completionRow['completion_code'] ?? '') === '' || $completionCodeVerify !== (string) $completionRow['completion_code']) {
                $errors[] = 'Guvenli onay kodu eslesmiyor.';
            }
        }

        if (empty($errors)) {
            $db->prepare("
                INSERT INTO listing_completion_proofs
                    (listing_id, accepted_worker_id, worker_confirmed, owner_confirmed, owner_note, owner_confirmed_at)
                VALUES (?, ?, 0, 1, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    owner_confirmed = 1,
                    owner_note = VALUES(owner_note),
                    owner_confirmed_at = NOW()
            ")->execute([$id, $acceptedWorkerId, $comment !== '' ? $comment : null]);

            // İşi tamamla
            $db->prepare("UPDATE listings SET status = 'closed' WHERE id = ?")->execute([$id]);
            
            // Kabul edilen teklifi ve işçiyi bul
            $workerId = $acceptedWorkerId;
            
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
                createNotification($workerId, 'completion_confirmed', 'Ev sahibi isi tamamlandi olarak onayladi.', '/listings/detail.php?id=' . $id);
            }
            
            setFlash('success', ' İş tamamlandı ve değerlendirmeniz kaydedildi. Teşekkürler!');
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
        <?= e($listing['title']) ?>  -  Temizci Burada
    </title>
    <link rel="stylesheet" href="../assets/css/style.css?v=5.0">
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
                                <div class="logo-icon"></div><span><span>Temizci Burada</span></span>
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
                                                    <span>
                                                        <?= e($listing['district'] ?: $listing['home_city']) ?>,
                                                        <?= e($listing['home_city']) ?>
                                                    </span>
                                                    <span>
                                                        <?= date('d M Y', strtotime($listing['preferred_date'])) ?>
                                                    </span>
                                                    <span>⏰
                                                        <?= $times[$listing['preferred_time']] ?? $listing['preferred_time'] ?>
                                                    </span>
                                                    <span>
                                                        <?= $listing['view_count'] ?> görüntülenme
                                                    </span>
                                                </div>
                                            </div>
                                            <div style="text-align:right;">
                                                <div style="display:flex;gap:8px;justify-content:flex-end;margin-bottom:8px;">
                                                    <?php if ($isLoggedIn): ?>
                                                    <button id="favBtn" onclick="toggleFavorite(<?= $id ?>)" title="Favorilere Ekle"
                                                        style="background:<?= $isFavorite ? 'rgba(239,68,68,0.1)' : 'var(--bg)' ?>;border:1px solid <?= $isFavorite ? '#ef4444' : 'var(--border)' ?>;border-radius:10px;width:40px;height:40px;cursor:pointer;font-size:1.15rem;display:flex;align-items:center;justify-content:center;transition:all 0.3s;">
                                                        <?= $isFavorite ? '♥' : '♡' ?>
                                                    </button>
                                                    <?php endif; ?>
                                                    <div style="position:relative;">
                                                        <button id="shareBtn" onclick="toggleShareMenu()" title="Paylaş"
                                                            style="background:var(--bg);border:1px solid var(--border);border-radius:10px;width:40px;height:40px;cursor:pointer;font-size:1.15rem;display:flex;align-items:center;justify-content:center;transition:all 0.2s;">
                                                            
                                                        </button>
                                                        <div id="shareMenu" style="display:none;position:absolute;right:0;top:46px;background:#fff;border:1px solid var(--border);border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,0.12);padding:8px;z-index:100;min-width:200px;">
                                                            <a href="https://wa.me/?text=<?= urlencode($listing['title'] . '  -  ' . APP_URL . '/listings/detail?id=' . $id) ?>" target="_blank"
                                                                style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;text-decoration:none;color:var(--text);font-size:0.88rem;font-weight:500;transition:background 0.2s;"
                                                                onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
                                                                <span style="font-size:1.2rem;"></span> WhatsApp
                                                            </a>
                                                            <a href="https://twitter.com/intent/tweet?text=<?= urlencode($listing['title'] . '  -  Temizci Burada') ?>&url=<?= urlencode(APP_URL . '/listings/detail?id=' . $id) ?>" target="_blank"
                                                                style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;text-decoration:none;color:var(--text);font-size:0.88rem;font-weight:500;transition:background 0.2s;"
                                                                onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
                                                                <span style="font-size:1.2rem;"></span> Twitter / X
                                                            </a>
                                                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(APP_URL . '/listings/detail?id=' . $id) ?>" target="_blank"
                                                                style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;text-decoration:none;color:var(--text);font-size:0.88rem;font-weight:500;transition:background 0.2s;"
                                                                onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
                                                                <span style="font-size:1.2rem;"></span> Facebook
                                                            </a>
                                                            <div style="border-top:1px solid var(--border);margin:4px 0;"></div>
                                                            <button onclick="copyLink()" 
                                                                style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;background:none;border:none;color:var(--text);font-size:0.88rem;font-weight:500;width:100%;cursor:pointer;transition:background 0.2s;"
                                                                onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
                                                                <span style="font-size:1.2rem;"></span> <span id="copyText">Linki Kopyala</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?= statusBadge($listing['status']) ?>
                                                <?php if ($listing['is_recurring'] === 1): ?>
                                                    <span class="badge" style="background:rgba(16,185,129,0.1);color:#10b981;border:1px solid #10b981;"> Periyodik İlan</span>
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
                                        <div class="card-title">  Ev Bilgileri</div>
                                    </div>
                                    <div class="card-body">
                                        <div
                                            style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:14px;">
                                            <div
                                                style="background:var(--bg);border-radius:var(--radius-sm);padding:14px;text-align:center;">
                                                <div style="font-size:1.5rem;"></div>
                                                <div style="font-weight:700;font-size:0.95rem;margin-top:4px;">
                                                    <?= e($listing['room_config']) ?>
                                                </div>
                                                <div style="font-size:0.75rem;color:var(--text-muted);">Oda Yapısı</div>
                                            </div>
                                            <div
                                                style="background:var(--bg);border-radius:var(--radius-sm);padding:14px;text-align:center;">
                                                <div style="font-size:1.5rem;"></div>
                                                <div style="font-weight:700;font-size:0.95rem;margin-top:4px;">
                                                    <?= $listing['bathroom_count'] ?>
                                                </div>
                                                <div style="font-size:0.75rem;color:var(--text-muted);">Banyo</div>
                                            </div>
                                            <?php if ($listing['floor']): ?>
                                                <div
                                                    style="background:var(--bg);border-radius:var(--radius-sm);padding:14px;text-align:center;">
                                                    <div style="font-size:1.5rem;"></div>
                                                    <div style="font-weight:700;font-size:0.95rem;margin-top:4px;">
                                                        <?= $listing['floor'] ?>. Kat
                                                    </div>
                                                    <div style="font-size:0.75rem;color:var(--text-muted);">Kat</div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($listing['sqm']): ?>
                                                <div
                                                    style="background:var(--bg);border-radius:var(--radius-sm);padding:14px;text-align:center;">
                                                    <div style="font-size:1.5rem;"></div>
                                                    <div style="font-weight:700;font-size:0.95rem;margin-top:4px;">
                                                        <?= $listing['sqm'] ?>m²
                                                    </div>
                                                    <div style="font-size:0.75rem;color:var(--text-muted);">Alan</div>
                                                </div>
                                            <?php endif; ?>
                                            <div
                                                style="background:var(--bg);border-radius:var(--radius-sm);padding:14px;text-align:center;">
                                                <div style="font-size:1.5rem;">
                                                    <?= $listing['has_elevator'] ? '✓' : '✗' ?>
                                                </div>
                                                <div style="font-weight:700;font-size:0.95rem;margin-top:4px;">
                                                    <?= $listing['has_elevator'] ? 'Var' : 'Yok' ?>
                                                </div>
                                                <div style="font-size:0.75rem;color:var(--text-muted);">Asansör</div>
                                            </div>
                                            <div
                                                style="background:var(--bg);border-radius:var(--radius-sm);padding:14px;text-align:center;">
                                                <div style="font-size:1.5rem;">
                                                    <?= $listing['owner_home'] ? '✓' : '✗' ?>
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
                                                     Ev Notları</div>
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
                                        <div class="card-title"> Teklifler (
                                            <?= count($offers) ?>)
                                        </div>
                                    </div>
                                    <?php if ($isOwner && !empty($offers)): ?>
                                        <div style="padding:14px 20px;border-bottom:1px solid var(--border-light);background:var(--bg);">
                                            <div style="font-size:0.85rem;font-weight:700;margin-bottom:10px;">Teklif Karşılaştırma</div>
                                            <div class="table-wrapper" style="margin:0;">
                                                <table class="table" style="font-size:0.83rem;">
                                                    <thead>
                                                        <tr>
                                                            <th>Temizlikçi</th>
                                                            <th>Fiyat</th>
                                                            <th>Puan</th>
                                                            <th>Yorum</th>
                                                            <th>Durum</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($offers as $co): ?>
                                                            <tr>
                                                                <td style="font-weight:600;"><?= e($co['worker_name']) ?></td>
                                                                <td>
                                                                    <?= formatMoney($co['price']) ?>
                                                                    <?php if ((float) $co['price'] === $minOfferPrice): ?>
                                                                        <span class="badge badge-open" style="margin-left:6px;">En Uygun</span>
                                                                    <?php elseif ((float) $co['price'] === $maxOfferPrice && count($offers) > 1): ?>
                                                                        <span class="badge badge-closed" style="margin-left:6px;">En Yüksek</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td><?= $co['review_count'] > 0 ? number_format((float) $co['rating'], 1) . '/5' : '-' ?></td>
                                                                <td><?= (int) $co['review_count'] ?></td>
                                                                <td><?= offerStatusBadge($co['status']) ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (empty($offers)): ?>
                                        <div class="empty-state" style="padding:40px;">
                                            <div class="empty-state-icon" style="font-size:2.5rem;"></div>
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
                                                                    <span title="Doğrulanmış Profil" style="color:#10b981;font-size:1rem;"></span>
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
                                                <?php if (!empty($offer['counter_price']) && ($offer['counter_status'] ?? 'none') !== 'none'): ?>
                                                    <div style="margin:10px 0 0 56px;padding:10px 12px;background:var(--bg);border:1px solid var(--border);border-radius:10px;">
                                                        <div style="font-size:0.78rem;color:var(--text-muted);">Karsi Teklif</div>
                                                        <div style="font-weight:800;"><?= formatMoney((float) $offer['counter_price']) ?></div>
                                                        <?php if (!empty($offer['counter_note'])): ?>
                                                            <div style="font-size:0.82rem;color:var(--text-secondary);margin-top:4px;"><?= e($offer['counter_note']) ?></div>
                                                        <?php endif; ?>
                                                        <div style="margin-top:6px;"><?= offerStatusBadge(($offer['counter_status'] ?? 'none') === 'pending' ? 'pending' : (($offer['counter_status'] ?? 'none') === 'accepted' ? 'accepted' : 'rejected')) ?></div>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($offerTimeline[(int) $offer['id']])): ?>
                                                    <div style="margin:8px 0 0 56px;padding:8px 10px;border-left:2px solid var(--border);background:rgba(0,0,0,0.01);">
                                                        <div style="font-size:0.76rem;color:var(--text-muted);margin-bottom:6px;">Teklif Gecmisi</div>
                                                        <?php foreach (array_slice($offerTimeline[(int) $offer['id']], 0, 4) as $tl): ?>
                                                            <?php
                                                                $eventLabel = match($tl['event_type']) {
                                                                    'offer_sent' => 'teklif gonderdi',
                                                                    'counter_sent' => 'karsi teklif sundu',
                                                                    'counter_accepted' => 'karsi teklifi kabul etti',
                                                                    'counter_rejected' => 'karsi teklifi reddetti',
                                                                    'offer_accepted' => 'teklifi kabul etti',
                                                                    'offer_rejected' => 'teklifi reddetti',
                                                                    default => $tl['event_type']
                                                                };
                                                            ?>
                                                            <div style="font-size:0.78rem;color:var(--text-secondary);margin:3px 0;">
                                                                <strong><?= e($tl['actor_name']) ?></strong>
                                                                <?= e($eventLabel) ?>
                                                                <?php if (!empty($tl['note'])): ?>
                                                                    - <?= e(mb_substr($tl['note'], 0, 70)) ?>
                                                                <?php endif; ?>
                                                                <span style="color:var(--text-muted);"> (<?= timeAgo($tl['created_at']) ?>)</span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($isOwner && $offer['status'] === 'pending' && $listing['status'] === 'open'): ?>
                                                    <div style="display:flex;gap:8px;padding-left:56px;margin-top:10px;">
                                                        <a href="detail?id=<?= $id ?>&action=accept&offer_id=<?= $offer['id'] ?>"
                                                            class="btn btn-secondary btn-sm"
                                                            data-confirm="Bu teklifi kabul etmek istediğinizden emin misiniz?"> Kabul Et</a>
                                                        <a href="../messages.php?uid=<?= $offer['user_id'] ?>" class="btn btn-outline btn-sm"> Mesaj Gönder</a>
                                                        <a href="detail?id=<?= $id ?>&action=reject&offer_id=<?= $offer['id'] ?>"
                                                            class="btn btn-ghost btn-sm"
                                                            data-confirm="Bu teklifi reddetmek istiyor musunuz?"> Reddet</a>
                                                    </div>
                                                    <form method="POST" style="display:flex;gap:8px;padding-left:56px;margin-top:8px;align-items:end;flex-wrap:wrap;">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="action" value="set_counter">
                                                        <input type="hidden" name="offer_id" value="<?= (int) $offer['id'] ?>">
                                                        <div>
                                                            <label style="font-size:0.75rem;color:var(--text-muted);display:block;">Karsi Teklif (TL)</label>
                                                            <input type="number" name="counter_price" min="1" step="1" class="form-control" style="width:140px;" required>
                                                        </div>
                                                        <div style="min-width:220px;flex:1;">
                                                            <label style="font-size:0.75rem;color:var(--text-muted);display:block;">Not</label>
                                                            <input type="text" name="counter_note" class="form-control" maxlength="200" placeholder="Opsiyonel not">
                                                        </div>
                                                        <button type="submit" class="btn btn-outline btn-sm">Karsi Teklif Gonder</button>
                                                    </form>
                                                <?php elseif ($isOwner): ?>
                                                    <div style="display:flex;gap:8px;padding-left:56px;margin-top:10px;">
                                                        <a href="../messages.php?uid=<?= $offer['user_id'] ?>" class="btn btn-outline btn-sm"> Mesaj Gönder</a>
                                                    </div>
                                                <?php elseif ($isWorker && (int) $offer['user_id'] === (int) $user['id'] && ($offer['counter_status'] ?? 'none') === 'pending' && $listing['status'] === 'open'): ?>
                                                    <form method="POST" style="display:flex;gap:8px;padding-left:56px;margin-top:10px;">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="offer_id" value="<?= (int) $offer['id'] ?>">
                                                        <button type="submit" name="action" value="counter_accept" class="btn btn-secondary btn-sm">Karsi Teklifi Kabul Et</button>
                                                        <button type="submit" name="action" value="counter_reject" class="btn btn-ghost btn-sm">Reddet</button>
                                                    </form>
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
                                                <span title="Doğrulanmış Profil" style="color:#10b981;font-size:1.1rem;"></span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size:0.82rem;color:var(--text-muted);margin-bottom:8px;">  Ev
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
                                            <div style="font-size:2rem;margin-bottom:12px;"></div>
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
                                            <div class="card-title"> Cift Onay ile Isi Tamamla</div>
                                        </div>
                                        <div class="card-body">
                                            <?php if (!empty($completionRow) && (!empty($completionPhotos) || !empty($completionRow['proof_photo']))): ?>
                                                <div style="margin-bottom:12px;">
                                                    <div style="font-size:0.8rem;color:var(--text-muted);margin-bottom:6px;">Hizmet Veren Kanit Galerisi</div>
                                                    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;">
                                                        <?php if (!empty($completionPhotos)): ?>
                                                            <?php foreach ($completionPhotos as $p): ?>
                                                                <img src="<?= UPLOAD_URL . e($p) ?>" alt="Kanit" style="width:100%;border-radius:10px;border:1px solid var(--border);max-height:160px;object-fit:cover;">
                                                            <?php endforeach; ?>
                                                        <?php elseif (!empty($completionRow['proof_photo'])): ?>
                                                            <img src="<?= UPLOAD_URL . e($completionRow['proof_photo']) ?>" alt="Kanit" style="width:100%;border-radius:10px;border:1px solid var(--border);max-height:220px;object-fit:cover;">
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($completionRow['worker_note'])): ?>
                                                <div style="font-size:0.84rem;color:var(--text-secondary);background:var(--bg);border:1px solid var(--border);padding:10px;border-radius:10px;margin-bottom:12px;">
                                                    <strong>Hizmet Veren Notu:</strong><br><?= nl2br(e($completionRow['worker_note'])) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (empty($completionRow) || (int) ($completionRow['worker_confirmed'] ?? 0) !== 1): ?>
                                                <div class="flash flash-info" style="margin-bottom:14px;">Hizmet verenin kanitli tamamlanma onayi bekleniyor.</div>
                                            <?php else: ?>
                                                <div class="flash flash-success" style="margin-bottom:14px;">Hizmet veren onay verdi. Siz onaylayinca is kapanacak.</div>
                                            <?php endif; ?>
                                            <form method="POST">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="complete_job">
                                                <div class="form-group">
                                                    <label class="form-label">6 Haneli Guvenli Onay Kodu</label>
                                                    <input type="text" name="completion_code_verify" class="form-control" maxlength="6" pattern="\d{6}" placeholder="Orn: 284731" required>
                                                    <div class="form-hint">Hizmet verenin girdigi kodu yazin.</div>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label class="form-label">Puanınız (1-5)</label>
                                                    <div class="rating-input" style="display:flex;gap:10px;justify-content:center;margin:10px 0;">
                                                        <?php for($i=1; $i<=5; $i++): ?>
                                                            <label style="cursor:pointer;">
                                                                <input type="radio" name="rating" value="<?= $i ?>" required style="display:none;" onclick="updateRatingUI(<?= $i ?>)">
                                                                <span class="star-input" id="star-<?= $i ?>" style="font-size:2rem;color:var(--text-muted); transition:var(--transition);">â˜…</span>
                                                            </label>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">Yorumunuz</label>
                                                    <textarea name="comment" class="form-control" rows="3" placeholder="Hizmetten memnun kaldınız mı?"></textarea>
                                                </div>

                                                <button type="submit" class="btn btn-secondary btn-block" <?= (empty($completionRow) || (int) ($completionRow['worker_confirmed'] ?? 0) !== 1) ? 'disabled' : '' ?>>Onayla, Tamamla ve Değerlendir</button>
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

                                <?php elseif ($isWorker && $listing['status'] === 'in_progress' && (int) $user['id'] === $acceptedWorkerId): ?>
                                    <div class="card">
                                        <div class="card-header">
                                            <div class="card-title"> Is Tamamlama Kaniti</div>
                                        </div>
                                        <div class="card-body">
                                            <?php if (!empty($completionRow) && (int) ($completionRow['worker_confirmed'] ?? 0) === 1): ?>
                                                <div class="flash flash-success" style="margin-bottom:12px;">Tamamlanma bildirimi gonderildi. Ev sahibi onayi bekleniyor.</div>
                                            <?php endif; ?>
                                            <form method="POST" enctype="multipart/form-data">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="worker_confirm_completion">
                                                <div class="form-group">
                                                    <label class="form-label">6 Haneli Guvenli Onay Kodu</label>
                                                    <input type="text" name="completion_code" class="form-control" maxlength="6" pattern="\d{6}" value="<?= e($completionRow['completion_code'] ?? '') ?>" placeholder="Orn: 284731" required>
                                                    <div class="form-hint">Bu kodu ev sahibiyle paylasin. Is kapanisinda bu kod dogrulanir.</div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Kisa Tamamlama Notu</label>
                                                    <textarea name="worker_note" class="form-control" rows="3" placeholder="Yaptiginiz isle ilgili not dusun..."><?= e($completionRow['worker_note'] ?? '') ?></textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Kanit Fotografi</label>
                                                    <input type="file" name="proof_photo" class="form-control" accept="image/png,image/jpeg,image/webp,image/gif">
                                                    <div class="form-hint">Ilk onayda en az bir fotograf beklenir.</div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Kanit Galerisi (Coklu)</label>
                                                    <input type="file" name="proof_photos[]" class="form-control" accept="image/png,image/jpeg,image/webp,image/gif" multiple>
                                                    <div class="form-hint">Birden fazla foto secip toplu yukleyebilirsiniz.</div>
                                                </div>
                                                <?php if (!empty($completionPhotos) || !empty($completionRow['proof_photo'])): ?>
                                                    <div style="margin-bottom:12px;">
                                                        <div style="font-size:0.8rem;color:var(--text-muted);margin-bottom:6px;">Yuklu Kanitlar</div>
                                                        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;">
                                                            <?php if (!empty($completionPhotos)): ?>
                                                                <?php foreach ($completionPhotos as $p): ?>
                                                                    <img src="<?= UPLOAD_URL . e($p) ?>" alt="Mevcut Kanit" style="width:100%;max-height:140px;object-fit:cover;border-radius:10px;border:1px solid var(--border);">
                                                                <?php endforeach; ?>
                                                            <?php elseif (!empty($completionRow['proof_photo'])): ?>
                                                                <img src="<?= UPLOAD_URL . e($completionRow['proof_photo']) ?>" alt="Mevcut Kanit" style="width:100%;max-height:180px;object-fit:cover;border-radius:10px;border:1px solid var(--border);">
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <button type="submit" class="btn btn-primary btn-block">Tamamlandi Olarak Bildir</button>
                                            </form>
                                        </div>
                                    </div>

                                <?php elseif ($isOwner): ?>
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="flash flash-info" style="margin-bottom:12px;">Bu sizin
                                                ilanınızdır.</div>
                                            <a href="../listings/my_listings" class="btn btn-ghost btn-block mb-2">
                                                İlanlarım</a>
                                            <a href="browse" class="btn btn-outline btn-block"> Diğer İlanlar</a>
                                        </div>
                                    </div>
                                <?php elseif ($hasOffer): ?>
                                    <div class="card">
                                        <div class="card-body" style="text-align:center;">
                                            <div style="font-size:2rem;margin-bottom:8px;"></div>
                                            <div style="font-weight:700;">Teklifiniz gönderildi!</div>
                                            <p style="font-size:0.85rem;color:var(--text-muted);margin-top:8px;">İlan sahibi
                                                teklifinizi inceleyecek.</p>
                                        </div>
                                    </div>
                                <?php elseif ($listing['status'] === 'closed'): ?>
                                    <div class="card">
                                        <div class="card-body" style="text-align:center;">
                                            <div style="font-size:2rem;margin-bottom:8px;"></div>
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
                                            <div style="font-size:2rem;margin-bottom:8px;"></div>
                                            <div style="font-weight:700;">Bu ilan artık aktif değil</div>
                                        </div>
                                    </div>
                                        <div class="card-body">
                                            <?php if (!empty($errors)): ?>
                                                <div class="flash flash-error">
                                                    <?= e(implode('<br>', $errors)) ?>
                                                </div>
                                            <?php endif; ?>
                                            <form method="POST" data-validate>
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="send_offer">
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
                                                <button type="submit" class="btn btn-primary btn-block btn-lg"> Teklifi
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

    <script src="../assets/js/app.js?v=5.0"></script>
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
                    btn.innerHTML = '
                    btn.style.background = 'rgba(239,68,68,0.1)';
                    btn.style.borderColor = '#ef4444';
                } else {
                    btn.innerHTML = '
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
            document.getElementById('copyText').textContent = ' Kopyalandı!';
            setTimeout(() => document.getElementById('copyText').textContent = 'Linki Kopyala', 2000);
        });
    }
    </script>
</body>

</html>



