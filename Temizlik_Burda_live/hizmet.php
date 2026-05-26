<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$cities = getCities();
$categories = getCategories();
$quickOptions = getQuickServiceOptions();

$city = trim((string) ($_GET['city'] ?? 'Istanbul'));
$serviceSlug = trim((string) ($_GET['service'] ?? 'ev-temizligi'));

if (!in_array($city, $cities, true)) {
    $city = 'Istanbul';
}

$category = null;
foreach ($categories as $cat) {
    if ((string) ($cat['slug'] ?? '') === $serviceSlug) {
        $category = $cat;
        break;
    }
}
if (!$category) {
    foreach ($quickOptions as $opt) {
        if (($opt['category_slug'] ?? '') === $serviceSlug) {
            $serviceSlug = (string) $opt['category_slug'];
            break;
        }
    }
    foreach ($categories as $cat) {
        if ((string) ($cat['slug'] ?? '') === $serviceSlug) {
            $category = $cat;
            break;
        }
    }
}
if (!$category && !empty($categories)) {
    $category = $categories[0];
    $serviceSlug = (string) ($category['slug'] ?? 'ev-temizligi');
}

$serviceName = normalizeUtf8((string) ($category['name'] ?? 'Hizmet'));
$canonicalUrl = canonicalBaseUrl() . '/hizmet?city=' . rawurlencode($city) . '&service=' . rawurlencode($serviceSlug);
$pageTitle = $city . ' ' . $serviceName . ' | Temizci Burada';
$pageDesc = $city . ' icin ' . $serviceName . ' ilanlarini hizlica inceleyin, teklif alin ve guvenilir hizmet verenlere ulasin.';

$latestListings = [];
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT l.id, l.title, l.preferred_date, l.budget, h.district, h.city
        FROM listings l
        JOIN homes h ON h.id = l.home_id
        WHERE l.status = 'open'
          AND h.city = ?
          AND l.category_id = ?
        ORDER BY l.created_at DESC
        LIMIT 8
    ");
    $stmt->execute([$city, (int) ($category['id'] ?? 0)]);
    $latestListings = $stmt->fetchAll();
} catch (Throwable $e) {
    $latestListings = [];
}

$faqSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => [
        [
            '@type' => 'Question',
            'name' => $city . ' icinde teklif ne kadar hizli gelir?',
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Ilan yayina alindiktan sonra genelde ayni gun icinde ilk teklifler gelir.'],
        ],
        [
            '@type' => 'Question',
            'name' => 'Hizmet veren secerken nelere bakmaliyim?',
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Puan, yorum, profil dogrulama durumu ve fiyat/mesaj netligine birlikte bakmanizi oneririz.'],
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDesc) ?>">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    <link rel="alternate" hreflang="tr-TR" href="<?= e($canonicalUrl) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDesc) ?>">
    <meta property="og:image" content="<?= e(canonicalBaseUrl() . '/logo.png') ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($pageDesc) ?>">
    <script type="application/ld+json"><?= json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <link rel="stylesheet" href="assets/css/style.css?v=5.9">
    <link rel="stylesheet" href="assets/css/dark-mode.css">
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
                <a href="quick-request" class="btn btn-outline btn-sm">Hizli Talep</a>
                <a href="register" class="btn btn-primary btn-sm">Ucretsiz Basla</a>
            </div>
        </div>
    </nav>

    <main class="home-shell">
        <section class="home-hero" style="padding-bottom:12px;">
            <div class="container">
                <div class="home-eyebrow"><?= e($city) ?> icin yerel hizmet sayfasi</div>
                <h1 class="home-title"><?= e($city) ?> icinde <?= e($serviceName) ?> hizmeti ara</h1>
                <p class="home-lead" style="max-width:760px;">
                    <?= e($city) ?> bolgesinde <?= e($serviceName) ?> ihtiyaci icin ilan olusturabilir, acik ilanlari filtreleyebilir
                    ve uygun hizmet verenlerden teklif alabilirsin.
                </p>
                <div class="home-actions">
                    <a href="listings/browse?city=<?= urlencode($city) ?>&cat=<?= urlencode($serviceSlug) ?>" class="btn btn-primary btn-lg">Filtrelenmis Ilanlar</a>
                    <a href="quick-request" class="btn btn-outline btn-lg">30 sn'de Talep Ac</a>
                </div>
            </div>
        </section>

        <section class="home-section" style="padding-top:8px;">
            <div class="container">
                <div class="home-head" style="text-align:left;max-width:none;">
                    <h2><?= e($city) ?> icin guncel <?= e($serviceName) ?> ilanlari</h2>
                    <p>Asagidaki liste son acilan ilanlardan olusur. Tumunu gormek icin filtrelenmis listeye gecebilirsin.</p>
                </div>

                <?php if (!empty($latestListings)): ?>
                    <div class="grid-3">
                        <?php foreach ($latestListings as $l): ?>
                            <article class="card listing-card">
                                <div class="card-content">
                                    <div class="listing-cat"><?= e($serviceName) ?></div>
                                    <div class="listing-title"><?= e($l['title']) ?></div>
                                    <div class="listing-meta">
                                        <span>Sehir <?= e($l['city']) ?></span>
                                        <span>Ilce <?= e((string) ($l['district'] ?? '-')) ?></span>
                                        <span>Tarih <?= e(date('d.m.Y', strtotime((string) $l['preferred_date']))) ?></span>
                                    </div>
                                    <div class="listing-footer">
                                        <?php if (!empty($l['budget'])): ?>
                                            <span class="listing-budget"><?= e(formatMoney((float) $l['budget'])) ?></span>
                                        <?php else: ?>
                                            <span style="font-size:0.85rem;color:var(--text-muted);">Butce belirtilmedi</span>
                                        <?php endif; ?>
                                        <a href="listings/detail?id=<?= (int) $l['id'] ?>" class="btn btn-primary btn-sm">Detay</a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            Bu filtrede acik ilan bulunmuyor. Hemen yeni talep acmak icin
                            <a href="quick-request">hizli talep ekranini</a> kullanabilirsin.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/app.js?v=5.8"></script>
    <script src="assets/js/theme.js"></script>
</body>
</html>

