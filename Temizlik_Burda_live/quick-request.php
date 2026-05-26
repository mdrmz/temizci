<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$errors = [];
$serviceOptions = getQuickServiceOptions();
$cities = getCities();

$requestedService = trim((string) ($_GET['service'] ?? ''));
$defaultService = isset($serviceOptions[$requestedService]) ? $requestedService : 'hizli-yardim';
$defaultDate = date('Y-m-d', strtotime('+1 day'));
$requestedBudget = (int) ($_GET['budget'] ?? 0);
$defaultBudget = $requestedBudget > 0 && $requestedBudget <= 999999 ? (string) $requestedBudget : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Guvenlik kontrolu basarisiz oldu. Lutfen tekrar deneyin.';
    } else {
        $service = trim((string) ($_POST['service'] ?? $defaultService));
        $city = trim((string) ($_POST['city'] ?? ''));
        $preferredDate = trim((string) ($_POST['preferred_date'] ?? ''));
        $budget = (int) ($_POST['budget'] ?? 0);
        $details = trim((string) ($_POST['details'] ?? ''));
        $isUrgent = isset($_POST['is_urgent']) ? 1 : 0;

        if (!isset($serviceOptions[$service])) {
            $errors[] = 'Gecerli bir hizmet turu seciniz.';
        }
        if ($city === '' || !in_array($city, $cities, true)) {
            $errors[] = 'Gecerli bir sehir seciniz.';
        }
        if ($preferredDate === '' || $preferredDate < date('Y-m-d')) {
            $errors[] = 'Bugun veya sonrasi bir tarih seciniz.';
        }
        if (mb_strlen($details, 'UTF-8') < 12) {
            $errors[] = 'Isi daha net anlatmak icin en az 12 karakter aciklama yaziniz.';
        }

        if (empty($errors)) {
            $_SESSION['quick_request_prefill'] = [
                'source' => 'quick-request',
                'service' => $service,
                'city' => $city,
                'preferred_date' => $preferredDate,
                'budget' => $budget > 0 ? $budget : null,
                'details' => $details,
                'is_urgent' => $isUrgent,
                'created_at' => time(),
            ];

            if (isLoggedIn()) {
                setFlash('success', 'Hizli talebiniz alindi. Ilan ekranina yonlendiriliyorsunuz.');
                redirect(APP_URL . '/listings/create?quick=1');
            }

            setFlash('info', 'Talebiniz hazir. Devam etmek icin 30 saniyede kayit olun.');
            redirect(APP_URL . '/register?quick=1');
        }
    }
}

$pageTitle = 'Hizli Is Talebi | Temizci Burada';
$pageDesc = 'Acil yardim, tasima, bahce duzenleme ve ogrenciye uygun gunluk isler icin 30 saniyede talep olusturun.';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDesc) ?>">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="<?= e(canonicalBaseUrl() . '/quick-request') ?>">
    <link rel="stylesheet" href="assets/css/style.css?v=5.9">
    <link rel="stylesheet" href="assets/css/dark-mode.css">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDesc) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= e(canonicalBaseUrl() . '/quick-request') ?>">
    <meta property="og:image" content="<?= e(canonicalBaseUrl() . '/logo.png') ?>">
</head>
<body class="minimal-home">
    <nav class="navbar" id="mainNav">
        <div class="navbar-inner container">
            <a href="index" class="navbar-logo">
                <div class="logo-icon" style="width:36px;height:36px;border-radius:8px;overflow:hidden;display:flex;align-items:center;justify-content:center;">
                    <img src="logo.png" alt="Temizci Burada" style="width:100%;height:100%;object-fit:cover;">
                </div>
                <span class="navbar-logo-text"><span>Temizci Burada</span></span>
            </a>
            <div class="navbar-actions">
                <a href="listings/browse" class="btn btn-outline btn-sm" data-track="quick_nav_browse">Ilanlari Incele</a>
                <a href="register" class="btn btn-primary btn-sm" data-track="quick_nav_register">Ucretsiz Basla</a>
            </div>
        </div>
    </nav>

    <main class="home-shell quick-page-shell">
        <section class="quick-hero-section">
            <div class="container">
                <?php if (!empty($errors)): ?>
                    <div class="flash flash-error"><?= e(implode(' ', $errors)) ?></div>
                <?php endif; ?>

                <div class="quick-layout">
                    <div class="quick-copy">
                        <div class="home-eyebrow">Yeni Ozellik</div>
                        <h1 class="quick-title">Bugun gereken isi dakikalar icinde yayina al</h1>
                        <p class="quick-lead">
                            Temizlik, tasima yardimi, bahce duzenleme ve gunluk isler icin tek ekranda net talep olustur.
                            Uygun kisiler teklif versin, sen en dogrusunu sec.
                        </p>
                        <div class="quick-proof">
                            <div><strong>30 sn</strong><span>talep formu</span></div>
                            <div><strong>24 sa</strong><span>acil oncelik</span></div>
                            <div><strong>TR</strong><span>sehir secimi</span></div>
                        </div>
                    </div>

                <form method="POST" class="card quick-form-card" id="quickRequestForm">
                    <?= csrfField() ?>
                    <div class="card-header">
                        <div>
                            <div class="card-title">Hizli is talebi</div>
                            <div class="quick-form-subtitle">Alanlari doldur, ilan taslagini hazirlayalim.</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="grid-2" style="gap:14px;">
                            <div class="form-group">
                                <label class="form-label" for="service">Is turu *</label>
                                <select class="form-control" id="service" name="service" required>
                                    <?php foreach ($serviceOptions as $key => $opt): ?>
                                        <option value="<?= e($key) ?>" <?= (($_POST['service'] ?? $defaultService) === $key) ? 'selected' : '' ?>>
                                            <?= e($opt['label']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-hint" id="serviceHint"></div>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="city">Sehir *</label>
                                <select class="form-control" id="city" name="city" required>
                                    <option value="">Sehir secin</option>
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?= e($city) ?>" <?= (($_POST['city'] ?? '') === $city) ? 'selected' : '' ?>>
                                            <?= e($city) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="grid-2" style="gap:14px;">
                            <div class="form-group">
                                <label class="form-label" for="preferred_date">Tercih edilen tarih *</label>
                                <input type="date" class="form-control" id="preferred_date" name="preferred_date" min="<?= date('Y-m-d') ?>"
                                       value="<?= e($_POST['preferred_date'] ?? $defaultDate) ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="budget">Butce (opsiyonel)</label>
                                <input type="number" class="form-control" id="budget" name="budget" min="0" max="999999" step="1"
                                       placeholder="Orn: 1200" value="<?= e((string) ($_POST['budget'] ?? $defaultBudget)) ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="details">Kisa is tarifi *</label>
                            <textarea class="form-control" id="details" name="details" rows="4" required maxlength="700"
                                      placeholder="Ornek: Bahcedeki 4 masayi depoya tasimak istiyorum. 2 kisi gerekir."><?= e($_POST['details'] ?? '') ?></textarea>
                        </div>

                        <label style="display:flex;align-items:center;gap:8px;color:var(--text-secondary);font-size:0.88rem;">
                            <input type="checkbox" name="is_urgent" <?= isset($_POST['is_urgent']) ? 'checked' : '' ?>>
                            Acil isaretle (24 saat icinde teklif alma onceligi)
                        </label>
                    </div>
                    <div class="card-footer" style="display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;">
                        <a href="listings/browse" class="btn btn-ghost" data-track="quick_form_browse">Ilanlari Incele</a>
                        <button type="submit" class="btn btn-primary" data-track="quick_form_submit_click">Hizli Talep Olustur</button>
                    </div>
                </form>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        const quickServiceOptions = <?= json_encode($serviceOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const serviceSelect = document.getElementById('service');
        const serviceHint = document.getElementById('serviceHint');
        const form = document.getElementById('quickRequestForm');

        function refreshHint() {
            const selected = serviceSelect ? serviceSelect.value : '';
            const hint = quickServiceOptions[selected] ? quickServiceOptions[selected].desc_hint : '';
            if (serviceHint) {
                serviceHint.textContent = hint || '';
            }
        }

        serviceSelect?.addEventListener('change', refreshHint);
        refreshHint();

        form?.addEventListener('submit', () => {
            try {
                if (typeof window.gtag === 'function') {
                    window.gtag('event', 'quick_request_submit', {
                        page_path: window.location.pathname,
                        service: serviceSelect ? serviceSelect.value : 'unknown'
                    });
                }
                if (Array.isArray(window.dataLayer)) {
                    window.dataLayer.push({
                        event: 'quick_request_submit',
                        service: serviceSelect ? serviceSelect.value : 'unknown'
                    });
                }
            } catch (_) {}
        });
    </script>
    <script src="assets/js/app.js?v=5.8"></script>
    <script src="assets/js/theme.js"></script>
</body>
</html>
