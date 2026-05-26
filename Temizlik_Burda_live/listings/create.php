<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin();

$user = currentUser();
$db = getDB();
$errors = [];

// CanlÄ±da kolon farklarÄ± varsa ilan oluÅŸturma akÄ±ÅŸÄ± patlamasÄ±n.
try {
    $listingCols = $db->query("SHOW COLUMNS FROM listings")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('owner_home', $listingCols, true)) {
        $db->exec("ALTER TABLE listings ADD COLUMN owner_home TINYINT(1) NOT NULL DEFAULT 1");
    }
    if (!in_array('budget', $listingCols, true)) {
        $db->exec("ALTER TABLE listings ADD COLUMN budget DECIMAL(10,2) DEFAULT NULL");
    }
    if (!in_array('is_recurring', $listingCols, true)) {
        $db->exec("ALTER TABLE listings ADD COLUMN is_recurring TINYINT(1) NOT NULL DEFAULT 0");
    }
} catch (Throwable $e) {
    // Hata olursa aÅŸaÄŸÄ±da dinamik insert ile devam ederiz.
}

$listingCols = [];
try {
    $listingCols = $db->query("SHOW COLUMNS FROM listings")->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $listingCols = ['user_id', 'home_id', 'category_id', 'title', 'description', 'preferred_date', 'preferred_time'];
}

// Ev listesi: sadece kullanÄ±cÄ±nÄ±n aktif evleri
$homesStmt = $db->prepare("SELECT id, title, room_config, city FROM homes WHERE user_id = ? AND is_active = 1");
$homesStmt->execute([$user['id']]);
$homes = $homesStmt->fetchAll();

$categories = getCategories();
$times = getPreferredTimes();
$preHomeId = (int)($_GET['home_id'] ?? 0);

$quickPrefill = [];
if (!empty($_SESSION['quick_request_prefill']) && is_array($_SESSION['quick_request_prefill'])) {
    $candidate = $_SESSION['quick_request_prefill'];
    $createdAt = (int)($candidate['created_at'] ?? 0);
    if ($createdAt > 0 && (time() - $createdAt) <= 2 * 24 * 60 * 60) {
        $quickPrefill = $candidate;
    } else {
        unset($_SESSION['quick_request_prefill']);
    }
}

$quickServiceMap = getQuickServiceOptions();
$quickServiceKey = (string)($quickPrefill['service'] ?? '');
$quickCategorySlug = isset($quickServiceMap[$quickServiceKey]) ? (string)$quickServiceMap[$quickServiceKey]['category_slug'] : '';
$quickCategoryId = $quickCategorySlug ? resolveCategoryIdBySlug($categories, $quickCategorySlug) : 0;

$prefillTitle = '';
$prefillDesc = '';
if (!empty($quickPrefill)) {
    $prefillTitle = (string)($quickServiceMap[$quickServiceKey]['title'] ?? 'HÄ±zlÄ± iÅŸ talebi');
    $prefillDesc = trim((string)($quickPrefill['details'] ?? ''));
    if ($prefillDesc !== '' && isset($quickServiceMap[$quickServiceKey]['desc_hint'])) {
        $prefillDesc .= "\n\nNot: " . (string)$quickServiceMap[$quickServiceKey]['desc_hint'];
    }
}

$priceStats = [];
try {
    $ps = $db->query("
        SELECT l.category_id, COUNT(*) AS c, MIN(o.price) AS min_p, MAX(o.price) AS max_p, AVG(o.price) AS avg_p
        FROM offers o
        JOIN listings l ON l.id = o.listing_id
        WHERE o.status = 'accepted' AND o.price > 0
        GROUP BY l.category_id
    ")->fetchAll();

    foreach ($ps as $row) {
        $priceStats[(int)$row['category_id']] = [
            'count' => (int)$row['c'],
            'min' => (float)$row['min_p'],
            'max' => (float)$row['max_p'],
            'avg' => (float)$row['avg_p'],
        ];
    }
} catch (Throwable $e) {
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'GÃ¼venlik hatasÄ±. LÃ¼tfen sayfayÄ± yenileyip tekrar deneyin.';
    } else {
        $homeId = (int)($_POST['home_id'] ?? 0);
        $catId = (int)($_POST['category_id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $desc = trim((string)($_POST['description'] ?? ''));
        $date = trim((string)($_POST['preferred_date'] ?? ''));
        $time = trim((string)($_POST['preferred_time'] ?? 'esnek'));
        $ownerHome = isset($_POST['owner_home']) ? 1 : 0;
        $budget = ($_POST['budget'] ?? '') !== '' ? (float)$_POST['budget'] : null;
        $isRecurring = isset($_POST['is_recurring']) ? 1 : 0;

        if ($homeId <= 0) {
            $errors[] = 'Ev seÃ§iniz.';
        }
        if ($catId <= 0) {
            $errors[] = 'Kategori seÃ§iniz.';
        }
        if ($title === '') {
            $errors[] = 'BaÅŸlÄ±k zorunludur.';
        } elseif (mb_strlen($title) < 5) {
            $errors[] = 'BaÅŸlÄ±k en az 5 karakter olmalÄ±dÄ±r.';
        }
        if ($desc === '') {
            $errors[] = 'AÃ§Ä±klama zorunludur.';
        }

        $spamReason = detectListingSpamReason($title, $desc);
        if ($spamReason !== null) {
            $errors[] = $spamReason;
        }

        if ($date === '') {
            $errors[] = 'Tarih zorunludur.';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $errors[] = 'GeÃ§erli bir tarih seÃ§iniz.';
        } elseif ($date < date('Y-m-d')) {
            $errors[] = 'GeÃ§miÅŸ bir tarih seÃ§emezsiniz.';
        }

        if (!array_key_exists($time, $times)) {
            $time = 'esnek';
        }

        $homeCheck = $db->prepare("SELECT id FROM homes WHERE id = ? AND user_id = ?");
        $homeCheck->execute([$homeId, $user['id']]);
        if (!$homeCheck->fetch()) {
            $errors[] = 'GeÃ§ersiz ev seÃ§imi.';
        }

        // AynÄ± ev + tarih + saat iÃ§in aktif ilan Ã§akÄ±ÅŸmasÄ±nÄ± engelle.
        if (empty($errors) && in_array('preferred_date', $listingCols, true) && in_array('preferred_time', $listingCols, true)) {
            $clashStmt = $db->prepare("
                SELECT id
                FROM listings
                WHERE user_id = ?
                  AND home_id = ?
                  AND preferred_date = ?
                  AND preferred_time = ?
                  AND status IN ('open','in_progress')
                LIMIT 1
            ");
            $clashStmt->execute([$user['id'], $homeId, $date, $time]);
            if ($clashStmt->fetch()) {
                $errors[] = 'Bu ev iÃ§in aynÄ± tarih ve saatte zaten aktif bir ilan var.';
            }
        }

        if (empty($errors)) {
            try {
                $requiredCols = ['user_id', 'home_id', 'category_id', 'title', 'description'];
                foreach ($requiredCols as $rc) {
                    if (!in_array($rc, $listingCols, true)) {
                        throw new RuntimeException("Eksik kolon: {$rc}");
                    }
                }

                $insertData = [
                    'user_id' => (int)$user['id'],
                    'home_id' => $homeId,
                    'category_id' => $catId,
                    'title' => $title,
                    'description' => $desc,
                    'preferred_date' => $date,
                    'preferred_time' => $time,
                    'owner_home' => $ownerHome,
                    'budget' => $budget,
                    'is_recurring' => $isRecurring,
                ];

                $cols = [];
                $vals = [];
                foreach ($insertData as $col => $val) {
                    if (in_array($col, $listingCols, true)) {
                        $cols[] = $col;
                        $vals[] = $val;
                    }
                }

                $placeholders = implode(',', array_fill(0, count($cols), '?'));
                $sql = "INSERT INTO listings (" . implode(',', $cols) . ") VALUES ({$placeholders})";
                $db->prepare($sql)->execute($vals);

                $listingId = (int)$db->lastInsertId();
                broadcastNewListingToWorkers($listingId);

                if (!empty($_SESSION['quick_request_prefill'])) {
                    unset($_SESSION['quick_request_prefill']);
                }

                setFlash('success', 'Ä°lan baÅŸarÄ±yla oluÅŸturuldu!');
                redirect(APP_URL . '/listings/detail.php?id=' . $listingId);
            } catch (Throwable $e) {
                $errors[] = 'Ä°lan kaydedilemedi. LÃ¼tfen tekrar deneyin.';
            }
        }
    }
}

$formDefaults = [
    'category_id' => $quickCategoryId ?: '',
    'title' => $prefillTitle,
    'description' => $prefillDesc,
    'preferred_date' => (string)($quickPrefill['preferred_date'] ?? ''),
    'preferred_time' => !empty($quickPrefill['is_urgent']) ? 'sabah' : 'esnek',
    'budget' => (string)($quickPrefill['budget'] ?? ''),
    'is_recurring' => 0,
];

if (($formDefaults['preferred_date'] ?? '') === '') {
    $formDefaults['preferred_date'] = date('Y-m-d', strtotime('+1 day'));
}

$getFormValue = function (string $key, $fallback = '') use ($formDefaults) {
    return $_POST[$key] ?? ($formDefaults[$key] ?? $fallback);
};
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ä°lan OluÅŸtur  -  Temizci Burada</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=5.9">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <link rel="icon" href="/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="/logo.png">
    <meta property="og:image" content="https://www.temizciburada.com/logo.png">
</head>

<body>
    <div class="app-layout">
        <?php include '../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php $headerTitle = 'Yeni Ä°lan'; include '../includes/app-header.php'; ?>

            <div class="page-content">
                <div class="container-sm">
                    <div class="page-title">Yeni Ä°lan OluÅŸtur</div>
                    <div class="page-subtitle">Temizlik iÅŸi ilanÄ± verin, uygun teklifleri alÄ±n.</div>

                    <?php if (!empty($quickPrefill)): ?>
                        <div class="flash flash-info">HÄ±zlÄ± talep bilgilerinizi otomatik doldurduk. Evi seÃ§ip yayÄ±nlayabilirsiniz.</div>
                    <?php endif; ?>

                    <?php if (empty($homes)): ?>
                        <div class="flash flash-warning">
                            Ä°lan oluÅŸturmak iÃ§in Ã¶nce bir ev eklemelisiniz.
                            <a href="../homes/add" class="btn btn-primary btn-sm" style="margin-left:12px;">Ev Ekle</a>
                        </div>
                    <?php else: ?>

                        <?php if (!empty($errors)): ?>
                            <div class="flash flash-error"><?= e(implode(' ', $errors)) ?></div>
                        <?php endif; ?>

                        <form method="POST" class="card" data-validate>
                            <?= csrfField() ?>

                            <div class="card-header">
                                <div class="card-title">Hangi Ev Ä°Ã§in?</div>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label">Ev SeÃ§ *</label>
                                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;">
                                        <?php foreach ($homes as $h): ?>
                                            <label style="cursor:pointer;">
                                                <input type="radio" name="home_id" value="<?= (int)$h['id'] ?>" style="display:none;"
                                                    <?= (($getFormValue('home_id', $preHomeId) == $h['id']) ? 'checked' : '') ?>>
                                                <div class="home-pick-card" style="padding:14px;border:2px solid var(--border);border-radius:var(--radius);transition:var(--transition);">
                                                    <div style="font-weight:700;font-size:0.92rem;"><?= e($h['title']) ?></div>
                                                    <div style="font-size:0.78rem;color:var(--text-muted);"><?= e($h['room_config']) ?> Â· <?= e($h['city']) ?></div>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="card-header" style="border-top:1px solid var(--border);">
                                <div class="card-title">Ä°lan DetaylarÄ±</div>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label" for="category_id">Hizmet Kategorisi *</label>
                                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;">
                                        <?php foreach ($categories as $cat): ?>
                                            <label style="cursor:pointer;text-align:center;">
                                                <input type="radio" name="category_id" value="<?= (int)$cat['id'] ?>" style="display:none;"
                                                    <?= ((string)$getFormValue('category_id', '') === (string)$cat['id']) ? 'checked' : '' ?>>
                                                <div class="cat-pick" style="padding:12px 10px;border:2px solid var(--border);border-radius:var(--radius-sm);transition:var(--transition);">
                                                    <div style="font-size:1.5rem;"><?= $cat['icon'] ?></div>
                                                    <div style="font-size:0.78rem;font-weight:600;margin-top:4px;"><?= e($cat['name']) ?></div>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="title">Ä°lan BaÅŸlÄ±ÄŸÄ± *</label>
                                    <input type="text" id="title" name="title" class="form-control"
                                        placeholder="Ã–rn: 3+1 dairem iÃ§in genel temizlik gerekiyor"
                                        value="<?= e((string)$getFormValue('title', '')) ?>" maxlength="200" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="description">AÃ§Ä±klama *</label>
                                    <textarea id="description" name="description" class="form-control" rows="4" maxlength="1000"
                                        placeholder="YapÄ±lmasÄ±nÄ± istediÄŸiniz iÅŸi detaylÄ± anlatÄ±n."><?= e((string)$getFormValue('description', '')) ?></textarea>
                                </div>

                                <div class="grid-2" style="gap:16px;">
                                    <div class="form-group">
                                        <label class="form-label" for="preferred_date">Tercih Edilen Tarih *</label>
                                        <input type="date" id="preferred_date" name="preferred_date" class="form-control"
                                            min="<?= date('Y-m-d') ?>" value="<?= e((string)$getFormValue('preferred_date', '')) ?>" required>
                                        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
                                            <button type="button" class="btn btn-outline btn-sm" onclick="setDatePreset('today')">BugÃ¼n</button>
                                            <button type="button" class="btn btn-outline btn-sm" onclick="setDatePreset('tomorrow')">YarÄ±n</button>
                                            <button type="button" class="btn btn-outline btn-sm" onclick="setDatePreset('weekend')">Hafta Sonu</button>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="preferred_time">Saat Tercihi</label>
                                        <select id="preferred_time" name="preferred_time" class="form-control">
                                            <?php foreach ($times as $val => $label): ?>
                                                <option value="<?= e($val) ?>" <?= ($getFormValue('preferred_time', 'esnek') === $val) ? 'selected' : '' ?>>
                                                    <?= e($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <label style="display:flex;align-items:center;gap:8px;font-size:0.84rem;color:var(--text-secondary);margin-top:10px;">
                                            <input type="checkbox" id="urgent_helper" onchange="applyUrgentPreset()">
                                            Acil iÅŸ (24 saat iÃ§inde otomatik tarih/saat Ã¶ner)
                                        </label>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="budget">BÃ¼tÃ§e (TL) - Opsiyonel</label>
                                        <input type="number" id="budget" name="budget" class="form-control" placeholder="0"
                                            min="0" max="99999" step="1" value="<?= e((string)$getFormValue('budget', '')) ?>">
                                        <div class="form-hint">BÃ¼tÃ§e belirtirseniz teklifler daha doÄŸru gelir.</div>
                                        <div id="priceSuggestionBox" style="margin-top:8px;padding:10px;border:1px dashed var(--border);border-radius:10px;background:var(--bg);font-size:0.82rem;color:var(--text-secondary);">
                                            AkÄ±llÄ± fiyat Ã¶nerisi iÃ§in kategori seÃ§in.
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Periyodik Ä°lan mÄ±?</label>
                                        <div class="toggle-wrapper" style="margin-top:10px;">
                                            <label class="toggle">
                                                <input type="checkbox" name="is_recurring" <?= ($getFormValue('is_recurring', 0) ? 'checked' : '') ?>>
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span style="color:var(--text-secondary);font-size:0.88rem;">Evet, dÃ¼zenli temizlik istiyorum</span>
                                        </div>
                                        <div class="form-hint">Ã–rn: Her hafta veya 15 gÃ¼nde bir.</div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Ä°ÅŸ YapÄ±lÄ±rken Evde Olacak mÄ±sÄ±nÄ±z?</label>
                                        <div class="toggle-wrapper" style="margin-top:10px;">
                                            <label class="toggle">
                                                <input type="checkbox" name="owner_home" <?= isset($_POST['owner_home']) ? 'checked' : 'checked' ?>>
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span style="color:var(--text-secondary);font-size:0.88rem;">Evet, evde olacaÄŸÄ±m</span>
                                        </div>
                                        <div class="form-hint">Bu bilgi hizmet verenlerin teklif vermesini kolaylaÅŸtÄ±rÄ±r.</div>
                                    </div>
                                </div>

                                <div class="card" style="margin-top:10px;background:var(--bg);border:1px dashed var(--border);">
                                    <div class="card-body" style="padding:12px 14px;">
                                        <div style="font-size:0.8rem;color:var(--text-muted);">Randevu Ã–zeti</div>
                                        <div id="appointmentSummary" style="font-weight:700;">Tarih ve saat seÃ§iniz.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer" style="display:flex;gap:12px;justify-content:flex-end;">
                                <a href="my_listings" class="btn btn-ghost">Ä°ptal</a>
                                <button type="submit" class="btn btn-primary">Ä°lanÄ± YayÄ±nla</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="../assets/js/app.js?v=5.8"></script>
    <script src="../assets/js/theme.js"></script>
    <script>
        function refreshSelectedCard(name, className) {
            document.querySelectorAll(`input[name="${name}"]`).forEach((radio) => {
                const card = radio.nextElementSibling;
                if (!card) return;
                if (radio.checked) {
                    card.style.borderColor = 'var(--primary)';
                } else {
                    card.style.borderColor = 'var(--border)';
                }
            });
        }

        document.querySelectorAll('input[name="home_id"], input[name="category_id"]').forEach((radio) => {
            radio.addEventListener('change', () => {
                refreshSelectedCard(radio.name);
                if (radio.name === 'category_id') {
                    updatePriceSuggestion();
                }
            });
        });

        refreshSelectedCard('home_id');
        refreshSelectedCard('category_id');

        function setDatePreset(type) {
            const input = document.getElementById('preferred_date');
            if (!input) return;

            const d = new Date();
            if (type === 'tomorrow') {
                d.setDate(d.getDate() + 1);
            } else if (type === 'weekend') {
                const day = d.getDay();
                const diff = day === 6 ? 0 : ((6 - day + 7) % 7);
                d.setDate(d.getDate() + diff);
            }

            input.value = d.toISOString().slice(0, 10);
            updateAppointmentSummary();
        }

        function applyUrgentPreset() {
            const urgent = document.getElementById('urgent_helper');
            const dateInput = document.getElementById('preferred_date');
            const timeInput = document.getElementById('preferred_time');
            if (!urgent || !dateInput || !timeInput) return;

            if (urgent.checked) {
                const d = new Date();
                d.setDate(d.getDate() + 1);
                dateInput.value = d.toISOString().slice(0, 10);
                timeInput.value = 'sabah';
            }

            updateAppointmentSummary();
        }

        function updateAppointmentSummary() {
            const dateInput = document.getElementById('preferred_date');
            const timeInput = document.getElementById('preferred_time');
            const summary = document.getElementById('appointmentSummary');
            if (!dateInput || !timeInput || !summary) return;

            const date = dateInput.value ? new Date(dateInput.value + 'T00:00:00') : null;
            const dateText = date
                ? date.toLocaleDateString('tr-TR', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' })
                : 'Tarih seÃ§ilmedi';

            const timeText = timeInput.options[timeInput.selectedIndex]?.text || 'Saat seÃ§ilmedi';
            summary.textContent = `${dateText} - ${timeText}`;
        }

        document.getElementById('preferred_date')?.addEventListener('change', updateAppointmentSummary);
        document.getElementById('preferred_time')?.addEventListener('change', updateAppointmentSummary);
        updateAppointmentSummary();

        const categoryPriceStats = <?= json_encode($priceStats, JSON_UNESCAPED_UNICODE) ?>;
        function updatePriceSuggestion() {
            const checkedCat = document.querySelector('input[name="category_id"]:checked');
            const box = document.getElementById('priceSuggestionBox');
            if (!box) return;

            if (!checkedCat) {
                box.textContent = 'AkÄ±llÄ± fiyat Ã¶nerisi iÃ§in kategori seÃ§in.';
                return;
            }

            const stat = categoryPriceStats[checkedCat.value];
            if (!stat || !stat.count) {
                box.textContent = 'Bu kategori iÃ§in yeterli geÃ§miÅŸ teklif verisi yok.';
                return;
            }

            const min = Math.round(stat.min);
            const max = Math.round(stat.max);
            const avg = Math.round(stat.avg);
            box.innerHTML = `Ã–nerilen aralÄ±k: <strong>${min} - ${max} TL</strong> (ortalama ${avg} TL, ${stat.count} iÅŸ verisi) <button type="button" class="btn btn-outline btn-sm" style="margin-left:8px;" onclick="document.getElementById('budget').value=${avg}">OrtalamayÄ± Uygula</button>`;
        }
        updatePriceSuggestion();
    </script>
</body>

</html>
