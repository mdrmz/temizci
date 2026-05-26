<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    redirect(APP_URL . '/dashboard');
}

try {
    $db = getDB();
    $stmt = $db->query("
        SELECT l.*, c.name AS cat_name, c.icon AS cat_icon,
               u.name AS owner_name,
               h.city, h.room_config, h.photo AS home_photo
        FROM listings l
        JOIN categories c ON l.category_id = c.id
        JOIN users u ON l.user_id = u.id
        JOIN homes h ON l.home_id = h.id
        WHERE l.status = 'open'
        ORDER BY l.created_at DESC
        LIMIT 6
    ");
    $listings = $stmt->fetchAll();
    $categories = getCategories();

    $statsRow = $db->query("
        SELECT
            (SELECT COUNT(*) FROM listings WHERE status = 'closed') AS completed_jobs,
            (SELECT COUNT(*) FROM users WHERE role = 'worker' AND is_active = 1) AS active_workers,
            (SELECT ROUND(COALESCE(AVG(rating), 0), 1) FROM users WHERE role = 'worker' AND review_count > 0) AS avg_worker_rating,
            (SELECT COUNT(*) FROM users WHERE role = 'worker' AND is_verified = 1) AS verified_workers,
            (SELECT COUNT(*) FROM reviews) AS total_reviews
    ")->fetch();

    $homepageStats = [
        'completed_jobs' => (int) ($statsRow['completed_jobs'] ?? 2500),
        'active_workers' => (int) ($statsRow['active_workers'] ?? 1200),
        'avg_worker_rating' => (float) ($statsRow['avg_worker_rating'] ?? 4.8),
        'verified_workers' => (int) ($statsRow['verified_workers'] ?? 0),
        'total_reviews' => (int) ($statsRow['total_reviews'] ?? 0),
    ];

    $reviewStmt = $db->query("
        SELECT r.rating, r.comment, r.created_at, u.name AS worker_name
        FROM reviews r
        JOIN users u ON u.id = r.reviewee_id
        ORDER BY r.created_at DESC
        LIMIT 3
    ");
    $latestReviews = $reviewStmt->fetchAll();
} catch (Exception $e) {
    $listings = [];
    $categories = [];
    $homepageStats = [
        'completed_jobs' => 2500,
        'active_workers' => 1200,
        'avg_worker_rating' => 4.8,
        'verified_workers' => 0,
        'total_reviews' => 0,
    ];
    $latestReviews = [];
}

$baseUrl = canonicalBaseUrl();
$homeCanonical = $baseUrl . '/';
$homeTitle = 'Temizci Burada - Eviniz ve Gunluk Isler Icin Guvenilir Hizmet Platformu';
$homeDesc = 'Temizlik, tasima, bahce yardimi ve ogrenciye uygun gunluk isler dahil ihtiyaciniz icin hizli ilan olusturun.';
$homeImage = $baseUrl . '/logo.png';

$cityLookup = [];
foreach (getCities() as $cityName) {
    $normalizedCity = normalizeUtf8((string) $cityName);
    if (function_exists('iconv')) {
        $normalizedCity = (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalizedCity);
    }
    $normalizedCity = preg_replace('/[^a-zA-Z]/', '', (string) $normalizedCity) ?? '';
    $key = strtolower($normalizedCity);
    $cityLookup[$key] = $cityName;
}
$wantedCityKeys = ['istanbul', 'ankara', 'izmir', 'bursa', 'antalya', 'kocaeli', 'mersin', 'adana', 'konya', 'gaziantep', 'samsun', 'mugla'];
$geoCities = [];
foreach ($wantedCityKeys as $k) {
    if (isset($cityLookup[$k])) {
        $geoCities[] = $cityLookup[$k];
    }
}
if (empty($geoCities)) {
    $geoCities = array_slice(array_values($cityLookup), 0, 12);
}

$homeListItems = [];
$position = 1;
foreach ($listings as $item) {
    $homeListItems[] = [
        '@type' => 'ListItem',
        'position' => $position++,
        'url' => $baseUrl . '/listings/detail?id=' . (int) ($item['id'] ?? 0),
        'name' => normalizeUtf8((string) ($item['title'] ?? 'Temizlik ilani')),
    ];
}

$quickServiceOptions = getQuickServiceOptions();
$popularJourneys = [
    [
        'title' => 'Ayni gun temizlik',
        'text' => 'Acil bosluk, misafir hazirligi ve son dakika toparlama isleri.',
        'service' => 'hizli-yardim',
        'meta' => '24 saat oncelikli',
    ],
    [
        'title' => 'Haftalik rutin',
        'text' => 'Ev temizligi, camasir, utu ve yuzey bakimini duzenli hale getir.',
        'service' => 'ev-temizligi',
        'meta' => 'Tekrar eden isler',
    ],
    [
        'title' => 'Tasima destegi',
        'text' => 'Koli, mobilya, bahce ve depo duzenleme icin guvenilir destek bul.',
        'service' => 'tasima-yardim',
        'meta' => 'Kisa sureli ekip',
    ],
    [
        'title' => 'Ogrenciye uygun is',
        'text' => 'Gunluk isleri hem uygun butceyle hem de hizli teklif akisiyle yayinla.',
        'service' => 'ogrenci-destek',
        'meta' => 'Esnek saat',
    ],
];
$mobileQuickServices = [
    ['label' => 'Ev', 'title' => 'Ev temizligi', 'service' => 'ev-temizligi'],
    ['label' => 'Cam', 'title' => 'Cam dahil', 'service' => 'ev-temizligi'],
    ['label' => 'Acil', 'title' => 'Bugun lazim', 'service' => 'hizli-yardim'],
    ['label' => 'Tasima', 'title' => 'Esya yardimi', 'service' => 'tasima-yardim'],
];
$trustSignals = [
    ['title' => 'Profil ve yorum kontrolu', 'text' => 'Teklifleri puan, yorum ve profil bilgileriyle ayni ekranda karsilastir.'],
    ['title' => 'Platform ici mesajlasma', 'text' => 'Is netlesmeden telefon paylasmadan, ayrintilari guvenli sekilde konus.'],
    ['title' => 'Net ilan bilgisi', 'text' => 'Oda, tarih, sehir, butce ve kapsam bilgileri hizmet verenin dogru teklif vermesine yardim eder.'],
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($homeTitle) ?></title>
    <meta name="description" content="<?= e($homeDesc) ?>">
    <meta name="keywords" content="temizlikci bul, ev temizligi, hizli yardim, esya tasima, bahce yardimi, ogrenciye uygun is, gunluk is ilani">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= e($homeCanonical) ?>">
    <link rel="alternate" hreflang="tr-TR" href="<?= e($homeCanonical) ?>">
    <link rel="alternate" hreflang="x-default" href="<?= e($homeCanonical) ?>">
    <meta name="geo.region" content="TR">
    <meta name="geo.placename" content="Turkiye">
    <meta name="geo.position" content="39.0;35.0">
    <meta name="ICBM" content="39.0,35.0">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#20435f">
    <link rel="apple-touch-icon" href="logo.png">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= e($homeCanonical) ?>">
    <meta property="og:title" content="<?= e($homeTitle) ?>">
    <meta property="og:description" content="<?= e($homeDesc) ?>">
    <meta property="og:image" content="<?= e($homeImage) ?>">
    <meta property="og:locale" content="tr_TR">
    <meta property="og:site_name" content="Temizci Burada">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($homeTitle) ?>">
    <meta name="twitter:description" content="<?= e($homeDesc) ?>">
    <meta name="twitter:image" content="<?= e($homeImage) ?>">
    <?php
    $homeGraphSchema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'WebSite',
                'name' => 'Temizci Burada',
                'alternateName' => 'temizciburada.com',
                'url' => $homeCanonical,
                'description' => 'Ev temizligi, cam temizligi ve gunubirlik hizmetler icin Turkiye genelinde guvenilir platform.',
                'inLanguage' => 'tr-TR',
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => $baseUrl . '/listings/browse?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
            [
                '@type' => 'Organization',
                'name' => 'Temizci Burada',
                'url' => $homeCanonical,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $homeImage,
                ],
                'contactPoint' => [
                    '@type' => 'ContactPoint',
                    'email' => 'info@temizciburada.com',
                    'contactType' => 'customer support',
                    'availableLanguage' => 'Turkish',
                ],
                'areaServed' => [
                    '@type' => 'Country',
                    'name' => 'Turkiye',
                ],
            ],
            [
                '@type' => 'LocalBusiness',
                'name' => 'Temizci Burada',
                'url' => $homeCanonical,
                'image' => $homeImage,
                'serviceArea' => [
                    '@type' => 'Country',
                    'name' => 'Turkiye',
                ],
                'geo' => [
                    '@type' => 'GeoCoordinates',
                    'latitude' => 39.0,
                    'longitude' => 35.0,
                ],
            ],
        ],
    ];
    $faqSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => [
            [
                '@type' => 'Question',
                'name' => 'Temizci Burada nedir?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Temizci Burada, ev sahiplerini guvenilir temizlik hizmeti verenlerle bulusturan Turkiye merkezli bir pazar yeridir.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => 'Kayit ve ilan acmak ucretsiz mi?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Evet, kayit ve ilan acma islemleri ev sahipleri ve hizmet verenler icin ucretsizdir.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => 'Teklif almak ne kadar surer?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Ilaniniz yayina girdikten sonra genellikle ayni gun icinde teklifler gelmeye baslar.',
                ],
            ],
        ],
    ];
    $howToSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'HowTo',
        'name' => 'Temizci Burada ile temizlikci nasil bulunur?',
        'description' => 'Temizlik hizmeti icin ilan olusturma ve teklif secme adimlari.',
        'totalTime' => 'PT5M',
        'step' => [
            ['@type' => 'HowToStep', 'name' => 'Ucretsiz kayit ol', 'url' => $baseUrl . '/register'],
            ['@type' => 'HowToStep', 'name' => 'Ev bilgilerini ekle', 'url' => $baseUrl . '/homes/add'],
            ['@type' => 'HowToStep', 'name' => 'Ilan olustur', 'url' => $baseUrl . '/listings/create'],
            ['@type' => 'HowToStep', 'name' => 'Teklifleri sec', 'url' => $baseUrl . '/listings/browse'],
        ],
    ];
    $latestListSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'Guncel temizlik ilanlari',
        'itemListElement' => $homeListItems,
    ];
    ?>
    <script type="application/ld+json"><?= json_encode($homeGraphSchema, JSON_UNESCAPED_UNICODE) ?></script>
    <script type="application/ld+json"><?= json_encode($faqSchema, JSON_UNESCAPED_UNICODE) ?></script>
    <script type="application/ld+json"><?= json_encode($howToSchema, JSON_UNESCAPED_UNICODE) ?></script>
    <?php if (!empty($homeListItems)): ?>
    <script type="application/ld+json"><?= json_encode($latestListSchema, JSON_UNESCAPED_UNICODE) ?></script>
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="assets/css/style.css?v=5.9">
    <link rel="stylesheet" href="assets/css/dark-mode.css">
    <link rel="icon" href="/logo.png" type="image/png">
    <style>
        body { padding-top: 0; }
        .navbar { background: transparent; border-bottom: 0; }
        .navbar.scrolled { background: transparent; border-bottom: 0; box-shadow: none; }
        body.minimal-home {
            --primary: #6340b3;
            --primary-dark: #4b2f8d;
            --secondary: #7954c8;
            --accent: #8e6bd8;
            --gradient: linear-gradient(135deg, #6340b3 0%, #6340b3 100%);
            --text-primary: #2f2352;
            --text-secondary: #60557e;
            --text-muted: #857ca1;
            --bg: #f6f2ff;
            --bg-white: #ffffff;
            --bg-card: rgba(255,255,255,0.94);
            --bg-hover: #f0eaff;
            --border: #ddd2f6;
            --border-light: #ebe3ff;
            background: #f6f2ff;
            color: var(--text-primary);
        }

        body.minimal-home .navbar {
            top: 14px;
        }

        body.minimal-home .navbar-inner {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(99, 64, 179, 0.16);
            box-shadow: 0 12px 28px rgba(87, 61, 153, 0.10);
            border-radius: 16px;
        }

        .home-shell {
            padding-top: 126px;
        }

        .home-hero {
            padding: 24px 0 28px;
        }

        .home-hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.85fr);
            gap: 24px;
            align-items: stretch;
        }

        .home-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border-radius: 999px;
            background: #eee7ff;
            color: #4f3c8b;
            border: 1px solid #d8c9ff;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .home-title {
            margin: 14px 0 12px;
            font-family: var(--font-display);
            font-size: clamp(2rem, 4.4vw, 3.2rem);
            line-height: 1.08;
            color: #2f2352;
            letter-spacing: -0.02em;
        }

        .home-title-accent {
            color: #6c48c0;
        }

        .home-lead {
            max-width: 620px;
            font-size: 1.03rem;
            line-height: 1.72;
            color: #625680;
            margin-bottom: 22px;
        }

        .home-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .home-actions .btn {
            min-height: 48px;
            padding: 0 22px;
            border-radius: 12px;
            font-weight: 700;
        }

        .home-checks {
            margin-top: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .home-check {
            display: inline-flex;
            align-items: center;
            padding: 7px 11px;
            border-radius: 999px;
            background: rgba(255,255,255,0.92);
            border: 1px solid #dccff9;
            color: #5b4e7f;
            font-size: 0.79rem;
            font-weight: 600;
        }

        .home-preview {
            background: #6340b3;
            border-radius: 22px;
            border: 1px solid rgba(255,255,255,0.26);
            box-shadow: 0 18px 34px rgba(70, 48, 128, 0.26);
            color: #fff;
            padding: 22px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .home-preview-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        .home-preview-title {
            font-size: 1.02rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .home-preview-sub {
            font-size: 0.82rem;
            opacity: 0.82;
        }

        .home-preview-tag {
            font-size: 0.76rem;
            font-weight: 700;
            padding: 6px 9px;
            border-radius: 999px;
            background: rgba(255,255,255,0.16);
            border: 1px solid rgba(255,255,255,0.25);
            height: fit-content;
        }

        .home-preview-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .home-preview-item {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            border: 1px solid rgba(255,255,255,0.26);
            background: rgba(255,255,255,0.14);
            border-radius: 11px;
            padding: 10px 12px;
            font-size: 0.86rem;
        }

        .home-preview-price {
            font-weight: 700;
            color: #efe7ff;
        }

        .home-kpis {
            padding: 4px 0 12px;
        }

        .home-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .home-kpi {
            background: rgba(255,255,255,0.9);
            border: 1px solid #dbe4eb;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(18, 40, 53, 0.08);
            padding: 14px;
        }

        .home-kpi-value {
            font-size: 1.24rem;
            font-weight: 800;
            color: #5c3ea8;
            line-height: 1.2;
            margin-bottom: 2px;
        }

        .home-kpi-label {
            font-size: 0.82rem;
            color: #5c6e7c;
        }

        .home-proof {
            padding-top: 18px;
        }

        .home-proof-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 8px;
        }

        .home-proof-card {
            background: rgba(255,255,255,0.92);
            border: 1px solid #dbe4eb;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(18, 40, 53, 0.08);
            padding: 14px;
        }

        .home-proof-value {
            font-size: 1.24rem;
            font-weight: 800;
            color: #5c3ea8;
            line-height: 1.2;
        }

        .home-proof-label {
            margin-top: 2px;
            font-size: 0.82rem;
            color: #5c6e7c;
        }

        .home-review-grid {
            margin-top: 12px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .home-review-card {
            border-radius: 14px;
            border: 1px solid #dce5ec;
            background: rgba(255,255,255,0.92);
            box-shadow: 0 8px 20px rgba(17, 39, 52, 0.07);
            padding: 14px;
        }

        .home-review-rating {
            color: #ca7b03;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }

        .home-review-card p {
            margin: 6px 0 8px;
            color: #4e6170;
            font-size: 0.86rem;
            line-height: 1.55;
        }

        .home-review-meta {
            font-size: 0.78rem;
            color: #708390;
        }

        .home-section {
            padding: 54px 0 8px;
        }

        .home-head {
            text-align: center;
            max-width: 720px;
            margin: 0 auto 26px;
        }

        .home-head h2 {
            font-family: var(--font-display);
            font-size: clamp(1.65rem, 3.2vw, 2.2rem);
            color: #1d3649;
            margin-bottom: 10px;
            letter-spacing: -0.015em;
        }

        .home-head p {
            color: #536572;
            font-size: 0.96rem;
            line-height: 1.7;
        }

        .home-steps {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .home-step {
            border-radius: 15px;
            border: 1px solid #dce5ec;
            background: rgba(255,255,255,0.9);
            box-shadow: 0 8px 22px rgba(17, 39, 52, 0.07);
            padding: 18px;
        }

        .home-step-no {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #efe7ff;
            color: #5c3ea8;
            font-size: 0.84rem;
            font-weight: 800;
            margin-bottom: 11px;
        }

        .home-step h3 {
            font-size: 1rem;
            color: #3b2d66;
            margin-bottom: 7px;
        }

        .home-step p {
            color: #655985;
            font-size: 0.88rem;
            line-height: 1.65;
        }

        .home-cats .cat-item,
        .home-listings .listing-card {
            border: 1px solid #dce5ec;
            border-radius: 15px;
            box-shadow: 0 8px 22px rgba(17, 39, 52, 0.07);
            background: rgba(255,255,255,0.92);
        }

        .home-geo {
            padding-top: 26px;
        }

        .home-city-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .home-city-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0 12px;
            border-radius: 12px;
            border: 1px solid #dce5ec;
            background: rgba(255,255,255,0.9);
            color: #4e3f7e;
            font-weight: 600;
            font-size: 0.88rem;
            text-decoration: none;
            transition: var(--transition);
        }

        .home-city-link:hover {
            border-color: #c7d7e2;
            background: #ffffff;
            transform: translateY(-2px);
        }

        .home-cats .cat-item:hover,
        .home-listings .listing-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 26px rgba(17, 39, 52, 0.11);
        }

        .home-listings .section-topline {
            margin-bottom: 18px;
            align-items: flex-end;
        }

        .home-listings .section-topline .home-head {
            margin: 0;
            text-align: left;
            max-width: 620px;
        }

        .home-cta {
            padding-bottom: 12px;
        }

        .home-cta-box {
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.24);
            background: #6340b3;
            box-shadow: 0 20px 38px rgba(72, 49, 131, 0.24);
            color: #fff;
            padding: clamp(22px, 3vw, 34px);
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }

        .home-cta-box h2 {
            font-family: var(--font-display);
            font-size: clamp(1.5rem, 3vw, 2rem);
            margin-bottom: 8px;
        }

        .home-cta-box p {
            max-width: 620px;
            opacity: 0.9;
            line-height: 1.6;
        }

        body.minimal-home .footer {
            margin-top: 56px;
            background: linear-gradient(180deg, #102733 0%, #0d1f29 100%);
        }

        @media (max-width: 1024px) {
            .home-hero-grid {
                grid-template-columns: 1fr;
            }
            .home-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .home-proof-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .home-review-grid {
                grid-template-columns: 1fr;
            }
            .home-steps {
                grid-template-columns: 1fr;
            }
            .home-city-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .home-shell {
                padding-top: 110px;
            }
            .home-hero {
                padding-top: 12px;
            }
            .home-title {
                font-size: 2rem;
            }
            .home-lead {
                font-size: 0.94rem;
                margin-bottom: 16px;
            }
            .home-actions {
                width: 100%;
            }
            .home-actions .btn {
                width: 100%;
                justify-content: center;
            }
            .home-kpi-grid {
                grid-template-columns: 1fr;
            }
            .home-proof-grid {
                grid-template-columns: 1fr;
            }
            .home-city-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .home-section {
                padding-top: 38px;
            }
            .home-listings .section-topline .home-head {
                text-align: center;
                margin: 0 auto;
            }
            .home-listings .section-topline {
                justify-content: center;
            }
        }
    </style>
    <style>
        body.home-lab {
            --primary: #173f46;
            --primary-dark: #102f36;
            --secondary: #3e7b59;
            --accent: #c46d3a;
            --gradient: linear-gradient(135deg, #173f46 0%, #2f6b6e 58%, #3e7b59 100%);
            --text-primary: #1d2d32;
            --text-secondary: #50646a;
            --text-muted: #7a8b90;
            --bg: #f5f2ec;
            --bg-card: #ffffff;
            --border: #ded7cc;
            --border-light: #ece6dc;
            background:
                linear-gradient(180deg, #f7f3eb 0%, #f5f7f4 42%, #eef4f1 100%);
            color: var(--text-primary);
        }

        body.home-lab .navbar-inner {
            border-radius: 8px;
            border-color: rgba(23, 63, 70, 0.14);
            box-shadow: 0 10px 24px rgba(23, 63, 70, 0.08);
        }

        body.home-lab .navbar-nav a {
            border-radius: 8px;
        }

        body.home-lab .nav-mini-icon,
        body.home-lab .btn-mini-icon {
            display: none;
        }

        body.home-lab .btn,
        body.home-lab .form-control {
            border-radius: 8px;
        }

        body.home-lab .home-shell {
            padding-top: 104px;
        }

        body.home-lab .home-hero {
            padding: 30px 0 26px;
        }

        body.home-lab .home-hero-grid {
            grid-template-columns: minmax(0, 1.05fr) minmax(350px, 0.95fr);
            gap: 28px;
            align-items: center;
        }

        body.home-lab .home-eyebrow {
            border-radius: 8px;
            background: #e8f0ed;
            border: 1px solid #d3e1dc;
            color: #24564d;
            letter-spacing: 0;
            text-transform: none;
            font-size: 0.84rem;
        }

        body.home-lab .home-title {
            max-width: 720px;
            margin: 14px 0 14px;
            font-size: 3.35rem;
            line-height: 1.04;
            letter-spacing: 0;
            color: #142b31;
        }

        body.home-lab .home-title-accent {
            color: #b85f31;
        }

        body.home-lab .home-lead {
            max-width: 660px;
            color: #4d6268;
            font-size: 1.02rem;
        }

        .home-quick-search {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;
            gap: 10px;
            margin: 20px 0 12px;
            padding: 10px;
            border: 1px solid rgba(23, 63, 70, 0.12);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.88);
            box-shadow: 0 10px 24px rgba(23, 63, 70, 0.08);
        }

        .home-quick-search .form-control {
            min-height: 46px;
        }

        .home-quick-search .btn {
            min-height: 46px;
            padding: 0 18px;
        }

        .home-mobile-services {
            display: none;
        }

        .home-mobile-service {
            min-height: 76px;
            padding: 12px;
            border: 1px solid #dce7e1;
            border-radius: 10px;
            background: #ffffff;
            color: #173f46;
            box-shadow: 0 8px 18px rgba(23, 63, 70, 0.06);
        }

        .home-mobile-service span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 28px;
            border-radius: 8px;
            background: #e9f3ee;
            color: #2e6d56;
            font-size: 0.76rem;
            font-weight: 900;
        }

        .home-mobile-service strong {
            display: block;
            margin-top: 8px;
            color: #173f46;
            font-size: 0.9rem;
            line-height: 1.25;
        }

        body.home-lab .home-checks {
            gap: 8px;
        }

        body.home-lab .home-check {
            border-radius: 8px;
            background: #ffffff;
            border-color: #dce6e1;
            color: #40585d;
        }

        .home-command {
            border: 1px solid rgba(23, 63, 70, 0.14);
            border-radius: 10px;
            background: #ffffff;
            box-shadow: 0 22px 44px rgba(23, 63, 70, 0.13);
            overflow: hidden;
        }

        .home-command-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 16px 18px;
            border-bottom: 1px solid #e6eee9;
            background: #f8fbf8;
        }

        .home-command-title {
            font-weight: 800;
            color: #173f46;
        }

        .home-command-sub {
            color: #6a7f84;
            font-size: 0.82rem;
        }

        .home-live-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 30px;
            padding: 0 10px;
            border-radius: 8px;
            background: #e8f3ed;
            color: #2f684a;
            border: 1px solid #cfe4d8;
            font-size: 0.78rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .home-command-body {
            padding: 18px;
            display: grid;
            gap: 14px;
        }

        .home-progress-line {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }

        .home-progress-step {
            min-height: 70px;
            border: 1px solid #e1e9e5;
            border-radius: 8px;
            padding: 10px;
            background: #fbfdfa;
        }

        .home-progress-step strong {
            display: block;
            color: #173f46;
            font-size: 0.88rem;
        }

        .home-progress-step span {
            display: block;
            color: #718388;
            font-size: 0.74rem;
            margin-top: 3px;
        }

        .home-offer-row {
            display: grid;
            grid-template-columns: 44px minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            padding: 12px;
            border: 1px solid #e2eae5;
            border-radius: 8px;
            background: #ffffff;
        }

        .home-offer-avatar {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            background: #e9f1ed;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .home-offer-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .home-offer-title {
            font-weight: 800;
            color: #263a40;
        }

        .home-offer-meta {
            color: #6c7d82;
            font-size: 0.8rem;
        }

        .home-offer-price {
            color: #b85f31;
            font-weight: 900;
            white-space: nowrap;
        }

        .home-mini-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .home-mini-stat {
            border: 1px solid #e2eae5;
            border-radius: 8px;
            padding: 12px;
            background: #fbfdfa;
        }

        .home-mini-stat strong {
            display: block;
            color: #173f46;
            font-size: 1.05rem;
        }

        .home-mini-stat span {
            color: #6c7d82;
            font-size: 0.76rem;
        }

        body.home-lab .home-section {
            padding: 44px 0 8px;
        }

        body.home-lab .home-head h2 {
            font-family: var(--font-display);
            font-size: 2.08rem;
            line-height: 1.16;
            letter-spacing: 0;
            color: #17343a;
        }

        body.home-lab .home-head p {
            color: #5b6e73;
        }

        .home-journey-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .home-journey-card {
            min-height: 210px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border: 1px solid #dfe8e3;
            border-radius: 8px;
            background: #ffffff;
            padding: 16px;
            box-shadow: 0 10px 24px rgba(23, 63, 70, 0.06);
        }

        .home-journey-meta {
            color: #b85f31;
            font-weight: 800;
            font-size: 0.78rem;
        }

        .home-journey-card h3 {
            margin: 10px 0 8px;
            color: #173f46;
            font-size: 1.05rem;
        }

        .home-journey-card p {
            color: #5f7378;
            font-size: 0.88rem;
            line-height: 1.6;
        }

        .home-journey-card a {
            margin-top: 14px;
            font-weight: 800;
            color: #2e6d56;
        }

        .home-planner-grid {
            display: grid;
            grid-template-columns: minmax(0, 0.88fr) minmax(360px, 1.12fr);
            gap: 22px;
            align-items: stretch;
        }

        .home-planner-copy {
            padding: 22px;
            border: 1px solid #dfe8e3;
            border-radius: 8px;
            background: #173f46;
            color: #ffffff;
        }

        .home-planner-copy .home-eyebrow {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.18);
        }

        .home-planner-copy h2 {
            margin: 16px 0 12px;
            font-family: var(--font-display);
            font-size: 2.15rem;
            line-height: 1.16;
            letter-spacing: 0;
        }

        .home-planner-copy p {
            color: rgba(255, 255, 255, 0.82);
            line-height: 1.72;
        }

        .home-planner-points {
            display: grid;
            gap: 10px;
            margin-top: 20px;
        }

        .home-planner-point {
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 8px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.08);
        }

        .home-planner-point strong {
            display: block;
            font-size: 0.92rem;
        }

        .home-planner-point span {
            color: rgba(255, 255, 255, 0.76);
            font-size: 0.8rem;
        }

        .planner-panel {
            border: 1px solid #dfe8e3;
            border-radius: 8px;
            background: #ffffff;
            padding: 18px;
            box-shadow: 0 14px 30px rgba(23, 63, 70, 0.08);
        }

        .planner-controls {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .planner-control-full {
            grid-column: 1 / -1;
        }

        .planner-range-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
        }

        .planner-range-value {
            min-width: 74px;
            text-align: right;
            color: #173f46;
            font-weight: 900;
        }

        .planner-panel input[type="range"] {
            width: 100%;
            accent-color: #3e7b59;
        }

        .planner-options {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .planner-chip {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            border: 1px solid #dce7e1;
            border-radius: 8px;
            background: #f8fbf8;
            color: #3d5358;
            font-weight: 800;
            font-size: 0.8rem;
            cursor: pointer;
            text-align: center;
        }

        .planner-chip input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .planner-chip:has(input:checked) {
            border-color: #3e7b59;
            background: #eaf4ee;
            color: #2f684a;
        }

        .planner-result {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 14px;
            align-items: end;
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid #e4ece7;
        }

        .planner-result-label {
            color: #6c7d82;
            font-size: 0.82rem;
            font-weight: 800;
        }

        .planner-result-price {
            color: #b85f31;
            font-size: 2rem;
            line-height: 1;
            font-weight: 900;
        }

        .planner-result-note {
            color: #5b6f74;
            font-size: 0.84rem;
            margin-top: 6px;
        }

        .planner-summary {
            color: #173f46;
            font-weight: 800;
            text-align: right;
        }

        .home-trust-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .home-trust-card {
            border: 1px solid #dfe8e3;
            border-radius: 8px;
            background: #ffffff;
            padding: 16px;
        }

        .home-trust-card h3 {
            color: #173f46;
            font-size: 1rem;
            margin-bottom: 8px;
        }

        .home-trust-card p {
            color: #5e7176;
            font-size: 0.88rem;
            line-height: 1.6;
        }

        body.home-lab .home-review-card,
        body.home-lab .home-proof-card,
        body.home-lab .home-step,
        body.home-lab .home-cats .cat-item,
        body.home-lab .home-listings .listing-card,
        body.home-lab .home-city-link {
            border-radius: 8px;
            border-color: #dfe8e3;
            box-shadow: 0 8px 20px rgba(23, 63, 70, 0.06);
        }

        body.home-lab .cat-item .cat-icon {
            border-radius: 8px;
            background: #edf4f0;
            color: #2e6d56;
        }

        body.home-lab .home-step-no {
            border-radius: 8px;
            background: #eaf4ee;
            color: #2f684a;
        }

        body.home-lab .home-cta-box {
            border-radius: 8px;
            background: #173f46;
            box-shadow: 0 18px 34px rgba(23, 63, 70, 0.18);
        }

        body.home-lab .home-cta-box .btn-outline {
            background: #ffffff;
            color: #173f46;
            border-color: #ffffff;
        }

        @media (max-width: 1080px) {
            body.home-lab .home-hero-grid,
            .home-planner-grid {
                grid-template-columns: 1fr;
            }

            .home-journey-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            body.home-lab .home-shell {
                padding-top: 82px;
                padding-bottom: 92px;
            }

            body.home-lab .home-hero {
                padding: 10px 0 16px;
            }

            body.home-lab .navbar {
                top: 8px;
            }

            body.home-lab .navbar-inner {
                min-height: 48px;
                padding: 8px 10px;
            }

            body.home-lab .home-title {
                max-width: 11ch;
                font-size: 2.42rem !important;
                line-height: 1.02;
                margin: 12px 0 10px;
            }

            body.home-lab .home-lead {
                max-width: 36ch;
                font-size: 0.96rem !important;
                line-height: 1.58 !important;
                margin-bottom: 14px;
            }

            body.home-lab .home-eyebrow {
                padding: 6px 10px;
                font-size: 0.78rem;
            }

            .home-quick-search,
            .planner-controls,
            .planner-result {
                grid-template-columns: 1fr;
            }

            .home-quick-search {
                margin: 14px 0 12px;
                padding: 8px;
                gap: 8px;
                border-radius: 12px;
            }

            .home-quick-search .form-control {
                min-height: 44px;
                font-size: 0.92rem;
            }

            .home-quick-search .btn,
            .planner-result .btn {
                width: 100%;
            }

            .home-mobile-services {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
                margin: 10px 0 12px;
            }

            body.home-lab .home-actions {
                display: grid;
                grid-template-columns: 1fr;
                gap: 8px !important;
            }

            body.home-lab .home-actions .btn {
                min-height: 46px;
                font-size: 0.94rem;
            }

            body.home-lab .home-checks {
                grid-template-columns: 1fr;
                gap: 7px !important;
                margin-top: 8px !important;
            }

            body.home-lab .home-check {
                min-height: 32px;
                padding: 6px 10px;
                font-size: 0.78rem;
            }

            body.home-lab .home-command {
                margin-top: 2px;
                border-radius: 12px;
            }

            .home-command-top {
                padding: 13px 14px;
            }

            .home-command-body {
                padding: 14px;
                gap: 10px;
            }

            .home-progress-line,
            .home-mini-grid,
            .home-trust-grid,
            .planner-options {
                grid-template-columns: 1fr;
            }

            .home-progress-step {
                min-height: auto;
                display: flex;
                justify-content: space-between;
                gap: 10px;
                align-items: center;
            }

            .home-journey-grid {
                grid-template-columns: 1fr;
            }

            .home-offer-row {
                grid-template-columns: 40px minmax(0, 1fr);
            }

            .home-offer-price {
                grid-column: 2;
            }

            .planner-summary {
                text-align: left;
            }

            body.home-lab .home-planner-copy {
                padding: 18px;
            }

            body.home-lab .home-planner-copy h2 {
                font-size: 1.65rem;
            }

            body.home-lab .tb-mobile-cta {
                display: none !important;
            }

            body.home-lab.tb-has-mobile-cta {
                padding-bottom: var(--mobile-nav-h, 74px) !important;
            }
        }

        @media (max-width: 420px) {
            body.home-lab .home-title {
                font-size: 2.2rem !important;
            }

            .home-mobile-service {
                min-height: 70px;
                padding: 10px;
            }
        }

        html[data-theme="dark"] body.home-lab {
            --primary: #9bd4c0;
            --primary-dark: #d8efe6;
            --secondary: #91c7a4;
            --accent: #f0a36d;
            --text-primary: #f3f6f4;
            --text-secondary: #c6d3d0;
            --text-muted: #93a5a4;
            --bg: #111820;
            --bg-card: #17232b;
            --border: #2a3b42;
            --border-light: #203037;
            background: linear-gradient(180deg, #101820 0%, #132128 52%, #10201e 100%);
            color: #f3f6f4;
        }

        html[data-theme="dark"] body.home-lab .navbar-inner,
        html[data-theme="dark"] body.home-lab .home-command,
        html[data-theme="dark"] body.home-lab .home-quick-search,
        html[data-theme="dark"] body.home-lab .home-journey-card,
        html[data-theme="dark"] body.home-lab .planner-panel,
        html[data-theme="dark"] body.home-lab .home-trust-card,
        html[data-theme="dark"] body.home-lab .home-review-card,
        html[data-theme="dark"] body.home-lab .home-step,
        html[data-theme="dark"] body.home-lab .cat-item,
        html[data-theme="dark"] body.home-lab .home-city-link,
        html[data-theme="dark"] body.home-lab .listing-card {
            background: #17232b;
            border-color: #2b3d44;
            color: #f3f6f4;
            box-shadow: 0 16px 34px rgba(0, 0, 0, 0.28);
        }

        html[data-theme="dark"] body.home-lab .home-title,
        html[data-theme="dark"] body.home-lab .home-head h2,
        html[data-theme="dark"] body.home-lab .home-command-title,
        html[data-theme="dark"] body.home-lab .home-offer-title,
        html[data-theme="dark"] body.home-lab .home-progress-step strong,
        html[data-theme="dark"] body.home-lab .home-mini-stat strong,
        html[data-theme="dark"] body.home-lab .home-trust-card h3,
        html[data-theme="dark"] body.home-lab .home-step h3,
        html[data-theme="dark"] body.home-lab .listing-title {
            color: #f5f7f3;
        }

        html[data-theme="dark"] body.home-lab .home-title-accent,
        html[data-theme="dark"] body.home-lab .home-journey-meta,
        html[data-theme="dark"] body.home-lab .home-offer-price,
        html[data-theme="dark"] body.home-lab .planner-result-price {
            color: #f0a36d;
        }

        html[data-theme="dark"] body.home-lab .home-lead,
        html[data-theme="dark"] body.home-lab .home-head p,
        html[data-theme="dark"] body.home-lab .home-command-sub,
        html[data-theme="dark"] body.home-lab .home-offer-meta,
        html[data-theme="dark"] body.home-lab .home-progress-step span,
        html[data-theme="dark"] body.home-lab .home-mini-stat span,
        html[data-theme="dark"] body.home-lab .home-journey-card p,
        html[data-theme="dark"] body.home-lab .home-trust-card p,
        html[data-theme="dark"] body.home-lab .home-step p,
        html[data-theme="dark"] body.home-lab .planner-result-note {
            color: #c6d3d0;
        }

        html[data-theme="dark"] body.home-lab .home-eyebrow,
        html[data-theme="dark"] body.home-lab .home-check,
        html[data-theme="dark"] body.home-lab .home-live-pill,
        html[data-theme="dark"] body.home-lab .home-progress-step,
        html[data-theme="dark"] body.home-lab .home-mini-stat,
        html[data-theme="dark"] body.home-lab .planner-chip {
            background: #203039;
            border-color: #344a52;
            color: #d8efe6;
        }

        html[data-theme="dark"] body.home-lab .home-command-top,
        html[data-theme="dark"] body.home-lab .planner-panel .form-control,
        html[data-theme="dark"] body.home-lab .home-quick-search .form-control {
            background: #111c23;
            border-color: #2b3d44;
            color: #f3f6f4;
        }

        html[data-theme="dark"] body.home-lab .home-quick-search .form-control option,
        html[data-theme="dark"] body.home-lab .planner-panel .form-control option {
            background: #111c23;
            color: #f3f6f4;
        }

        html[data-theme="dark"] body.home-lab .home-offer-row {
            background: #152229;
            border-color: #2b3d44;
        }

        html[data-theme="dark"] body.home-lab .home-planner-copy,
        html[data-theme="dark"] body.home-lab .home-cta-box {
            background: #0f2f34;
            border-color: #2f5557;
        }

        html[data-theme="dark"] body.home-lab .btn-outline {
            background: #13232b;
            color: #f5f7f3;
            border-color: #385059;
        }

        html[data-theme="dark"] body.home-lab .navbar-logo span,
        html[data-theme="dark"] body.home-lab .navbar-logo-text span {
            background: none;
            -webkit-text-fill-color: #d8efe6;
            color: #d8efe6;
        }

        html[data-theme="dark"] body.home-lab .theme-toggle-btn {
            background: #f5f7f3;
            border-color: #f5f7f3;
            color: #173f46;
        }

        html[data-theme="dark"] body.home-lab .home-actions .btn-outline {
            background: #111c23;
            color: #f5f7f3;
            border-color: #385059;
        }

        html[data-theme="dark"] body.home-lab .home-secondary-cta {
            background: #111c23 !important;
            color: #f5f7f3 !important;
            border-color: #385059 !important;
        }

        html[data-theme="dark"] body.home-lab .home-cta-box .btn-outline {
            background: #f5f7f3;
            color: #173f46;
            border-color: #f5f7f3;
        }

        @media (max-width: 768px) {
            html[data-theme="dark"] body.home-lab {
                --primary: #173f46;
                --primary-dark: #102f36;
                --secondary: #3e7b59;
                --accent: #c46d3a;
                --text-primary: #1d2d32;
                --text-secondary: #50646a;
                --text-muted: #7a8b90;
                --bg: #f5f2ec;
                --bg-card: #ffffff;
                --border: #ded7cc;
                --border-light: #ece6dc;
                background: linear-gradient(180deg, #f7f3eb 0%, #f5f7f4 44%, #eef4f1 100%);
                color: #1d2d32;
            }

            html[data-theme="dark"] body.home-lab .navbar-inner,
            html[data-theme="dark"] body.home-lab .home-command,
            html[data-theme="dark"] body.home-lab .home-quick-search,
            html[data-theme="dark"] body.home-lab .home-mobile-service,
            html[data-theme="dark"] body.home-lab .home-journey-card,
            html[data-theme="dark"] body.home-lab .planner-panel,
            html[data-theme="dark"] body.home-lab .home-trust-card,
            html[data-theme="dark"] body.home-lab .home-review-card,
            html[data-theme="dark"] body.home-lab .home-step,
            html[data-theme="dark"] body.home-lab .cat-item,
            html[data-theme="dark"] body.home-lab .home-city-link,
            html[data-theme="dark"] body.home-lab .listing-card {
                background: #ffffff;
                border-color: #dfe8e3;
                color: #1d2d32;
                box-shadow: 0 10px 24px rgba(23, 63, 70, 0.08);
            }

            html[data-theme="dark"] body.home-lab .home-title,
            html[data-theme="dark"] body.home-lab .home-head h2,
            html[data-theme="dark"] body.home-lab .home-command-title,
            html[data-theme="dark"] body.home-lab .home-offer-title,
            html[data-theme="dark"] body.home-lab .home-progress-step strong,
            html[data-theme="dark"] body.home-lab .home-mini-stat strong,
            html[data-theme="dark"] body.home-lab .home-trust-card h3,
            html[data-theme="dark"] body.home-lab .home-step h3,
            html[data-theme="dark"] body.home-lab .home-mobile-service strong,
            html[data-theme="dark"] body.home-lab .listing-title {
                color: #142b31;
            }

            html[data-theme="dark"] body.home-lab .home-title-accent,
            html[data-theme="dark"] body.home-lab .home-journey-meta,
            html[data-theme="dark"] body.home-lab .home-offer-price,
            html[data-theme="dark"] body.home-lab .planner-result-price {
                color: #b85f31;
            }

            html[data-theme="dark"] body.home-lab .home-lead,
            html[data-theme="dark"] body.home-lab .home-head p,
            html[data-theme="dark"] body.home-lab .home-command-sub,
            html[data-theme="dark"] body.home-lab .home-offer-meta,
            html[data-theme="dark"] body.home-lab .home-progress-step span,
            html[data-theme="dark"] body.home-lab .home-mini-stat span,
            html[data-theme="dark"] body.home-lab .home-journey-card p,
            html[data-theme="dark"] body.home-lab .home-trust-card p,
            html[data-theme="dark"] body.home-lab .home-step p,
            html[data-theme="dark"] body.home-lab .planner-result-note {
                color: #50646a;
            }

            html[data-theme="dark"] body.home-lab .home-eyebrow,
            html[data-theme="dark"] body.home-lab .home-check,
            html[data-theme="dark"] body.home-lab .home-live-pill,
            html[data-theme="dark"] body.home-lab .home-progress-step,
            html[data-theme="dark"] body.home-lab .home-mini-stat,
            html[data-theme="dark"] body.home-lab .planner-chip,
            html[data-theme="dark"] body.home-lab .home-mobile-service span {
                background: #e9f3ee;
                border-color: #d4e5dc;
                color: #2e6d56;
            }

            html[data-theme="dark"] body.home-lab .home-command-top {
                background: #f8fbf8;
                border-color: #e6eee9;
            }

            html[data-theme="dark"] body.home-lab .home-quick-search .form-control,
            html[data-theme="dark"] body.home-lab .planner-panel .form-control {
                background: #ffffff;
                border-color: #d7e3dd;
                color: #173f46;
            }

            html[data-theme="dark"] body.home-lab .home-offer-row {
                background: #ffffff;
                border-color: #e2eae5;
            }

            html[data-theme="dark"] body.home-lab .home-secondary-cta {
                background: #ffffff !important;
                color: #173f46 !important;
                border-color: #cfded6 !important;
            }

            html[data-theme="dark"] body.home-lab .navbar-logo span,
            html[data-theme="dark"] body.home-lab .navbar-logo-text span {
                background: none;
                -webkit-text-fill-color: #173f46;
                color: #173f46;
            }

            html[data-theme="dark"] body.home-lab .tb-mobile-nav {
                background: rgba(255, 255, 255, 0.94);
                border-color: #d9e4de;
                box-shadow: 0 16px 34px rgba(23, 63, 70, 0.16);
            }

            html[data-theme="dark"] body.home-lab .tb-mobile-nav__item {
                color: #52666c;
            }

            html[data-theme="dark"] body.home-lab .tb-mobile-nav__icon {
                background: #eff5f2;
                border-color: #d8e4de;
                color: #2e6d56;
            }

            body.home-lab .cookie-banner,
            html[data-theme="dark"] body.home-lab .cookie-banner {
                left: 14px;
                right: 14px;
                bottom: calc(var(--mobile-nav-h, 74px) + 14px + env(safe-area-inset-bottom));
                max-width: none;
                padding: 14px;
                border-radius: 14px;
                background: rgba(255, 255, 255, 0.96);
                border: 1px solid #d9e4de;
                color: #294047;
                box-shadow: 0 18px 40px rgba(23, 63, 70, 0.18);
            }

            body.home-lab .cookie-banner p,
            html[data-theme="dark"] body.home-lab .cookie-banner p {
                font-size: 0.82rem;
                line-height: 1.48;
                color: #405860;
            }

            body.home-lab .cookie-banner a,
            html[data-theme="dark"] body.home-lab .cookie-banner a {
                color: #2e6d56;
                font-weight: 800;
            }
        }

        /* Homepage premium cleanup (less AI-like gradients) */
        body.home-lab {
            background: linear-gradient(180deg, #f8f6f1 0%, #f5f8f7 45%, #f1f5f3 100%);
            color: #1f2f35;
        }

        body.home-lab .navbar-inner {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #dce6e1;
            box-shadow: 0 10px 28px rgba(23, 63, 70, 0.09);
            border-radius: 14px;
        }

        body.home-lab .home-eyebrow {
            background: #e7f0eb;
            border-color: #cfe0d8;
            color: #2b6350;
        }

        body.home-lab .home-title {
            letter-spacing: -0.02em;
            line-height: 1.1;
            max-width: 15ch;
        }

        body.home-lab .home-lead {
            color: #50636a;
            max-width: 60ch;
        }

        body.home-lab .home-command,
        body.home-lab .home-journey-card,
        body.home-lab .home-trust-card,
        body.home-lab .home-review-card,
        body.home-lab .home-step,
        body.home-lab .home-cats .cat-item,
        body.home-lab .home-listings .listing-card,
        body.home-lab .home-city-link,
        body.home-lab .planner-panel,
        body.home-lab .home-cta-box {
            background: #ffffff;
            border: 1px solid #dfe8e3;
            box-shadow: 0 12px 26px rgba(23, 63, 70, 0.08);
        }

        body.home-lab .home-offer-row {
            background: #f8fbfa;
            border: 1px solid #dce7e2;
        }

        body.home-lab .btn-primary {
            background: linear-gradient(135deg, #1f4a56 0%, #2f6e58 100%);
            box-shadow: 0 8px 18px rgba(31, 74, 86, 0.22);
        }

        body.home-lab .btn-outline {
            background: #f5faf8;
            border-color: #cfe0d8;
            color: #2a4c58;
        }

        @media (max-width: 768px) {
            body.home-lab .navbar-nav {
                justify-content: flex-start;
                overflow-x: auto;
                scrollbar-width: none;
            }

            body.home-lab .navbar-nav::-webkit-scrollbar {
                display: none;
            }
        }
    </style>
</head>
<body class="minimal-home home-lab">
    <nav class="navbar" id="mainNav">
        <div class="navbar-inner container">
            <a href="index" class="navbar-logo">
                <div class="logo-icon" style="width:36px;height:36px;border-radius:8px;overflow:hidden;display:flex;align-items:center;justify-content:center;">
                    <img src="logo.png" alt="Temizci Burada Logo" style="width:100%;height:100%;object-fit:cover;">
                </div>
                <span class="navbar-logo-text"><span>Temizci Burada</span></span>
            </a>
            <div class="navbar-nav">
                <a href="listings/browse">Ilanlar</a>
                <a href="#planlayici">Planlayici</a>
                <a href="#guven">Guven</a>
                <a href="#sehirler">Sehirler</a>
            </div>
            <div class="navbar-actions">
                <button class="theme-toggle-btn" id="themeToggle" title="Tema Degistir" style="margin-right:10px;">Tema</button>
                <a href="login" class="btn btn-outline btn-sm" data-track="home_nav_login">Giris</a>
                <a href="register" class="btn btn-primary btn-sm" data-track="home_nav_register">Ucretsiz basla</a>
            </div>
        </div>
    </nav>
    <main class="home-shell">
        <section class="home-hero">
            <div class="container home-hero-grid">
                <div>
                    <div class="home-eyebrow">30 saniyede talep ac</div>
                    <h1 class="home-title">Ev isi icin <span class="home-title-accent">hemen teklif al.</span></h1>
                    <p class="home-lead">Temizlik, cam, utu, bahce ve tasima isini yaz. Uygun hizmet verenler teklif gondersin, sen fiyat ve profili karsilastir.</p>
                    <form class="home-quick-search" action="listings/browse" method="GET">
                        <select class="form-control" name="city" aria-label="Sehir sec">
                            <option value="">Sehir sec</option>
                            <?php foreach (array_slice($geoCities, 0, 12) as $cityName): ?>
                                <option value="<?= e($cityName) ?>"><?= e($cityName) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select class="form-control" name="cat" aria-label="Hizmet sec">
                            <option value="">Hizmet sec</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= e($cat['slug']) ?>"><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                            <?php if (empty($categories)): ?>
                                <?php foreach ($quickServiceOptions as $key => $opt): ?>
                                    <option value="<?= e($opt['category_slug']) ?>"><?= e($opt['label']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <button class="btn btn-primary" type="submit" data-track="home_search_submit">Ilan bul</button>
                    </form>
                    <div class="home-mobile-services" aria-label="Hizli hizmet secimi">
                        <?php foreach ($mobileQuickServices as $service): ?>
                            <a href="quick-request?service=<?= e($service['service']) ?>" class="home-mobile-service" data-track="home_mobile_service_click">
                                <span><?= e($service['label']) ?></span>
                                <strong><?= e($service['title']) ?></strong>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="home-actions">
                        <a href="quick-request" class="btn btn-primary btn-lg" data-track="home_hero_quick_request">30 sn'de talep ac</a>
                        <a href="#planlayici" class="btn btn-outline btn-lg home-secondary-cta" data-track="home_hero_planner">Tahmini butce hesapla</a>
                    </div>
                    <div class="home-checks">
                        <span class="home-check">Onayli profil sinyalleri</span>
                        <span class="home-check">Platform ici mesajlasma</span>
                        <span class="home-check">Acil ve planli is akisi</span>
                    </div>
                </div>
                <div class="home-command" aria-label="Temizci Burada teklif panosu">
                    <div class="home-command-top">
                        <div>
                            <div class="home-command-title">Canli talep panosu</div>
                            <div class="home-command-sub">Ilan ac, teklifleri tek yerde topla.</div>
                        </div>
                        <span class="home-live-pill">Yeni teklif</span>
                    </div>
                    <div class="home-command-body">
                        <div class="home-progress-line">
                            <div class="home-progress-step"><strong>Talep</strong><span>2+1 ev, cam dahil</span></div>
                            <div class="home-progress-step"><strong>Teklif</strong><span>3 hizmet veren</span></div>
                            <div class="home-progress-step"><strong>Secim</strong><span>Puan ve fiyat</span></div>
                            <div class="home-progress-step"><strong>Is</strong><span>Takvimde net</span></div>
                        </div>
                        <div class="home-offer-row">
                            <div class="home-offer-avatar"><img src="logo.png" alt="Temizci Burada"></div>
                            <div>
                                <div class="home-offer-title">Haftalik ev temizligi</div>
                                <div class="home-offer-meta">Kadikoy - Cumartesi 10:00 - Cam dahil</div>
                            </div>
                            <div class="home-offer-price">1.350 TL</div>
                        </div>
                        <div class="home-offer-row">
                            <div class="home-offer-avatar"><img src="logo.png" alt="Temizci Burada"></div>
                            <div>
                                <div class="home-offer-title">Tasima ve duzenleme yardimi</div>
                                <div class="home-offer-meta">Ankara - 2 kisi - Ayni gun</div>
                            </div>
                            <div class="home-offer-price">900 TL</div>
                        </div>
                        <div class="home-mini-grid">
                            <div class="home-mini-stat">
                                <strong><?= $homepageStats['completed_jobs'] > 0 ? number_format($homepageStats['completed_jobs'], 0, ',', '.') : 'Yeni' ?></strong>
                                <span>Tamamlanan is</span>
                            </div>
                            <div class="home-mini-stat">
                                <strong><?= $homepageStats['active_workers'] > 0 ? number_format($homepageStats['active_workers'], 0, ',', '.') : 'Hazir' ?></strong>
                                <span>Aktif profil</span>
                            </div>
                            <div class="home-mini-stat">
                                <strong><?= $homepageStats['avg_worker_rating'] > 0 ? number_format($homepageStats['avg_worker_rating'], 1, ',', '.') : 'Net' ?></strong>
                                <span>Ortalama puan</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="home-section home-journeys">
            <div class="container">
                <div class="home-head">
                    <h2>Bugun neye ihtiyacin var?</h2>
                    <p>En cok kullanilan akislari tek dokunusla baslat; sonra ayrintiyi ilanda netlestir.</p>
                </div>
                <div class="home-journey-grid">
                    <?php foreach ($popularJourneys as $journey): ?>
                        <article class="home-journey-card">
                            <div>
                                <div class="home-journey-meta"><?= e($journey['meta']) ?></div>
                                <h3><?= e($journey['title']) ?></h3>
                                <p><?= e($journey['text']) ?></p>
                            </div>
                            <a href="quick-request?service=<?= e($journey['service']) ?>" data-track="home_journey_click">Talep ac</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section id="planlayici" class="home-section home-planner">
            <div class="container home-planner-grid">
                <div class="home-planner-copy">
                    <div class="home-eyebrow">Mini planlayici</div>
                    <h2>Butceyi ve kapsami daha ilana girmeden hisset.</h2>
                    <p>Bu alan kullanicinin sitede kalmasini saglar: hizmet turu, oda sayisi, saat ve ek islere gore kabaca bir aralik gosterir. Sonra tek tikla hizli talep formuna gider.</p>
                    <div class="home-planner-points">
                        <div class="home-planner-point"><strong>Net beklenti</strong><span>Hizmet veren daha dogru teklif verir.</span></div>
                        <div class="home-planner-point"><strong>Daha az mesaj kalabaligi</strong><span>Kapsam bastan secildigi icin pazarlik daha sakin ilerler.</span></div>
                        <div class="home-planner-point"><strong>Mobilde hizli</strong><span>Kaydir, sec, talebi hazirla.</span></div>
                    </div>
                </div>
                <div class="planner-panel" data-home-planner>
                    <div class="planner-controls">
                        <div class="form-group">
                            <label class="form-label" for="plannerService">Hizmet</label>
                            <select class="form-control" id="plannerService" data-planner-service>
                                <?php foreach ($quickServiceOptions as $key => $opt): ?>
                                    <option value="<?= e($key) ?>"><?= e($opt['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="plannerRooms">Ev / is olcegi</label>
                            <select class="form-control" id="plannerRooms" data-planner-rooms>
                                <option value="1">1+1 veya kucuk is</option>
                                <option value="2" selected>2+1 standart</option>
                                <option value="3">3+1 genis</option>
                                <option value="4">4+1 ve uzeri</option>
                            </select>
                        </div>
                        <div class="form-group planner-control-full">
                            <div class="planner-range-row">
                                <label class="form-label" for="plannerHours" style="margin:0;">Tahmini sure</label>
                                <span class="planner-range-value" data-planner-hours-label>4 saat</span>
                            </div>
                            <input type="range" id="plannerHours" min="2" max="9" step="1" value="4" data-planner-hours>
                        </div>
                        <div class="form-group planner-control-full">
                            <label class="form-label">Ek kapsam</label>
                            <div class="planner-options">
                                <label class="planner-chip"><input type="checkbox" value="window" data-planner-extra>Cam dahil</label>
                                <label class="planner-chip"><input type="checkbox" value="iron" data-planner-extra>Utu destek</label>
                                <label class="planner-chip"><input type="checkbox" value="urgent" data-planner-extra>Acil is</label>
                            </div>
                        </div>
                    </div>
                    <div class="planner-result">
                        <div>
                            <div class="planner-result-label">Tahmini aralik</div>
                            <div class="planner-result-price" data-planner-price>1.150 - 1.450 TL</div>
                            <div class="planner-result-note">Gercek teklif; sehir, tarih, ev durumu ve hizmet veren profiline gore degisir.</div>
                        </div>
                        <div>
                            <div class="planner-summary" data-planner-summary>Ev temizligi - 2+1 - 4 saat</div>
                            <a href="quick-request" class="btn btn-primary mt-3" data-planner-link data-track="home_planner_quick_request">Talebi hazirla</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <?php /* Guven algisini dusuren dusuk sayac degerleri nedeniyle bu blok gecici olarak kapatildi. */ ?>

        <section id="guven" class="home-section home-proof">
            <div class="container">
                <div class="home-head">
                    <h2>Secim yaparken elinde veri olsun.</h2>
                    <p>Yorum, profil ve ilan ayrintilarini bir araya getirerek daha rahat karar ver.</p>
                </div>
                <?php /* Guven kartlarindaki numeric sayaÃ§lar bu asamada gostermiyoruz. */ ?>
                <div class="home-trust-grid">
                    <?php foreach ($trustSignals as $signal): ?>
                        <article class="home-trust-card">
                            <h3><?= e($signal['title']) ?></h3>
                            <p><?= e($signal['text']) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($latestReviews)): ?>
                    <div class="home-review-grid">
                        <?php foreach ($latestReviews as $review): ?>
                            <article class="home-review-card">
                                <div class="home-review-rating"><?= str_repeat('*', max(1, min(5, (int) ($review['rating'] ?? 0)))) ?></div>
                                <p><?= e(mb_strimwidth((string) ($review['comment'] ?? ''), 0, 170, '...')) ?></p>
                                <div class="home-review-meta"><?= e((string) ($review['worker_name'] ?? 'Hizmet Veren')) ?> - <?= e(date('d.m.Y', strtotime((string) ($review['created_at'] ?? 'now')))) ?></div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="home-proof-card" style="max-width:720px;margin:12px auto 0;">
                        <div class="home-proof-label">Ilk yorumlar geldikce bu alanda dogrudan kullanici deneyimlerini gosterecegiz.</div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section id="nasil-calisir" class="home-section">
            <div class="container">
                <div class="home-head">
                    <h2>Uc adimda temiz ve net surec</h2>
                    <p>Ilk kez kullansan bile kolayca ilerlemen icin akisi sade tuttuk.</p>
                </div>
                <div class="home-steps">
                    <article class="home-step">
                        <div class="home-step-no">1</div>
                        <h3>Evini tanimla</h3>
                        <p>Ev tipi, oda duzeni ve tarih bilgisini gir. Istersen fotograflarini ekle.</p>
                    </article>
                    <article class="home-step">
                        <div class="home-step-no">2</div>
                        <h3>Ilanini yayinla</h3>
                        <p>Butce araligini belirle. Hizmet verenler net bir ilan gorup teklif gondersin.</p>
                    </article>
                    <article class="home-step">
                        <div class="home-step-no">3</div>
                        <h3>Teklifini sec</h3>
                        <p>Fiyat, profil ve puani ayni ekranda karsilastir. Icine sinen kisiyle anlas.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="kategoriler" class="home-section home-cats">
            <div class="container">
                <div class="home-head">
                    <h2>Hangi hizmeti ariyorsun?</h2>
                    <p>Tek dokunusla ilgili kategoriye gecip uygun ilanlari gorebilirsin.</p>
                </div>
                <div class="cat-grid">
                    <?php foreach ($categories as $cat): ?>
                        <a href="listings/browse?cat=<?= e($cat['slug']) ?>" class="cat-item">
                            <div class="cat-icon"><?= $cat['icon'] ?></div>
                            <div class="cat-name"><?= e($cat['name']) ?></div>
                        </a>
                    <?php endforeach; ?>
                    <?php if (empty($categories)): ?>
                        <a href="listings/browse?cat=ev-temizligi" class="cat-item"><div class="cat-icon">ET</div><div class="cat-name">Ev Temizligi</div></a>
                        <a href="listings/browse?cat=cam-pencere" class="cat-item"><div class="cat-icon">CP</div><div class="cat-name">Cam ve Pencere</div></a>
                        <a href="listings/browse?cat=utu-camasir" class="cat-item"><div class="cat-icon">UC</div><div class="cat-name">Utu ve Camasir</div></a>
                        <a href="listings/browse?cat=bulasik" class="cat-item"><div class="cat-icon">BL</div><div class="cat-name">Bulasik</div></a>
                        <a href="listings/browse?cat=bahce" class="cat-item"><div class="cat-icon">BH</div><div class="cat-name">Bahce</div></a>
                        <a href="listings/browse?cat=koltuk-yikama" class="cat-item"><div class="cat-icon">KY</div><div class="cat-name">Koltuk Yikama</div></a>
                        <a href="listings/browse?cat=genel-temizlik" class="cat-item"><div class="cat-icon">GT</div><div class="cat-name">Genel Temizlik</div></a>
                        <a href="listings/browse" class="cat-item"><div class="cat-icon">DG</div><div class="cat-name">Diger</div></a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section id="sehirler" class="home-section home-geo">
            <div class="container">
                <div class="home-head">
                    <h2>Sehre gore ilanlari kesfet</h2>
                    <p>Bulundugun sehre gore aktif ilanlari filtreleyerek daha hizli sonuca ulas.</p>
                </div>
                <div class="home-city-grid">
                    <?php foreach ($geoCities as $cityName): ?>
                        <a class="home-city-link" href="listings/browse?city=<?= urlencode($cityName) ?>"><?= e($cityName) ?></a>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top:10px;font-size:0.86rem;color:#5f7180;">
                    Yerel sayfalar:
                    <?php foreach (array_slice($geoCities, 0, 4) as $cityName): ?>
                        <a href="hizmet?city=<?= urlencode($cityName) ?>&service=ev-temizligi" style="margin-right:8px;"><?= e($cityName) ?> ev temizligi</a>
                        <a href="hizmet?city=<?= urlencode($cityName) ?>&service=hizli-yardim" style="margin-right:8px;"><?= e($cityName) ?> hizli yardim</a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <?php if (!empty($listings)): ?>
            <section class="home-section home-listings">
                <div class="container">
                    <div class="section-topline">
                        <div class="home-head">
                            <h2>Guncel ilanlar</h2>
                            <p>Bugun yayinlanan ilanlardan bazi ornekler.</p>
                        </div>
                        <a href="listings/browse" class="btn btn-outline">Tum ilanlar</a>
                    </div>
                    <div class="grid-3">
                        <?php foreach ($listings as $listing): ?>
                            <div class="card listing-card">
                                <?php if (!empty($listing['home_photo'])): ?>
                                    <div class="card-img-placeholder" style="background:url('<?= APP_URL ?>/uploads/homes/<?= $listing['home_photo'] ?>') center/cover no-repeat; position:relative; border-radius:var(--radius-lg) var(--radius-lg) 0 0;">
                                        <div style="position:absolute; inset:0; background:linear-gradient(to bottom, transparent, rgba(17,39,51,0.76)); border-radius:var(--radius-lg) var(--radius-lg) 0 0;"></div>
                                        <span style="position:absolute; bottom:12px; left:12px; background:rgba(255,255,255,0.9); color:var(--primary); padding:6px 12px; border-radius:20px; font-size:0.82rem; font-weight:700; display:flex; gap:8px; align-items:center;">
                                            <span><?= $listing['cat_icon'] ?></span> <?= e($listing['cat_name']) ?>
                                        </span>
                                    </div>
                                    <div class="card-content">
                                <?php else: ?>
                                    <div class="card-img-placeholder"><?= $listing['cat_icon'] ?></div>
                                    <div class="card-content">
                                        <div class="listing-cat"><?= $listing['cat_icon'] ?> <?= e($listing['cat_name']) ?></div>
                                <?php endif; ?>
                                    <div class="listing-title"><?= e($listing['title']) ?></div>
                                    <div class="listing-meta">
                                        <span>Sehir <?= e($listing['city']) ?></span>
                                        <span>Ev <?= e($listing['room_config']) ?></span>
                                        <span>Tarih <?= date('d M', strtotime($listing['preferred_date'])) ?></span>
                                    </div>
                                    <div class="listing-footer">
                                        <?php if ($listing['budget']): ?>
                                            <span class="listing-budget"><?= formatMoney($listing['budget']) ?></span>
                                        <?php else: ?>
                                            <span style="color:var(--text-muted);font-size:0.85rem;">Butce belirsiz</span>
                                        <?php endif; ?>
                                        <a href="listings/detail?id=<?= $listing['id'] ?>" class="btn btn-primary btn-sm" data-track="home_listing_detail_click">Detayi Gor</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="home-section home-cta">
            <div class="container">
                <div class="home-cta-box">
                    <div>
                        <h2>Ihtiyacin neyse hizli talep ac</h2>
                        <p>Temizlik, tasima, bahce ve gunluk destek isleri icin tek yerden ilan ver, teklifler gelsin.</p>
                    </div>
                    <div class="cta-actions">
                        <a href="quick-request" class="btn btn-primary btn-lg" data-track="home_bottom_quick_request">Hizli Talep Ac</a>
                        <a href="register?role=worker" class="btn btn-outline btn-lg" data-track="home_bottom_worker_register">Hizmet Vermek Istiyorum</a>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <footer class="footer">
        <div class="container">
            <div class="grid-4" style="gap:40px;">
                <div style="grid-column:span 2;">
                    <div class="footer-logo">Temizci Burada</div>
                    <p class="footer-desc">Evinin duzenine onem verenlerle, isini ozenle yapan hizmet verenleri ayni cizgide bulusturan yeni nesil temizlik platformu.</p>
                </div>
                <div>
                    <div class="footer-title">Platform</div>
                    <div class="footer-links">
                        <a href="listings/browse">Ilanlar</a>
                        <a href="register">Kayit Ol</a>
                        <a href="login">Giris Yap</a>
                    </div>
                </div>
                <div>
                    <div class="footer-title">Iletisim</div>
                    <div class="footer-links">
                        <a href="mailto:info@temizciburada.com" data-track="home_footer_email">info@temizciburada.com</a>
                        <a href="privacy">Privacy Policy</a>
                        <a href="gizlilik-politikasi">Gizlilik Politikasi</a>
                        <a href="kvkk">KVKK</a>
                        <a href="cerez-politikasi">Cerez Politikasi</a>
                        <a href="destek">Destek</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Temizci Burada - Tum haklari saklidir. - <a href="privacy" style="color:rgba(255,255,255,0.65);">Privacy Policy</a> - <a href="gizlilik-politikasi" style="color:rgba(255,255,255,0.65);">Gizlilik Politikasi</a> - <a href="kvkk" style="color:rgba(255,255,255,0.65);">KVKK</a> - <a href="cerez-politikasi" style="color:rgba(255,255,255,0.65);">Cerez Politikasi</a></p>
            </div>
        </div>
    </footer>
    <div id="cookieBanner" class="cookie-banner">
        <p>Bu site yalnizca oturum yonetimi icin zorunlu cerezler kullanir. <a href="privacy">Privacy Policy</a>, <a href="gizlilik-politikasi">Gizlilik Politikasi</a>, <a href="cerez-politikasi">Cerez Politikasi</a> ve <a href="kvkk">KVKK Aydinlatma Metni</a></p>
        <button id="cookieAccept" onclick="acceptCookies()" class="btn btn-primary btn-sm">Anladim, Kabul Et</button>
    </div>
    <script>
        function acceptCookies() {
            localStorage.setItem('cookie_consent', '1');
            document.getElementById('cookieBanner').style.display = 'none';
        }
        if (!localStorage.getItem('cookie_consent')) {
            document.getElementById('cookieBanner').style.display = 'flex';
        }
    </script>
    <script>
        (function() {
            const planner = document.querySelector('[data-home-planner]');
            if (!planner) return;

            const services = {
                'ev-temizligi': { label: 'Ev temizligi', base: 520, perHour: 145 },
                'tasima-yardim': { label: 'Esya tasima / bahce yardimi', base: 460, perHour: 165 },
                'bahce': { label: 'Bahce duzenleme', base: 420, perHour: 150 },
                'ogrenci-destek': { label: 'Ogrenciye uygun gunluk is', base: 280, perHour: 115 },
                'hizli-yardim': { label: 'Acil yardim', base: 620, perHour: 170 }
            };

            const service = planner.querySelector('[data-planner-service]');
            const rooms = planner.querySelector('[data-planner-rooms]');
            const hours = planner.querySelector('[data-planner-hours]');
            const hoursLabel = planner.querySelector('[data-planner-hours-label]');
            const extras = Array.from(planner.querySelectorAll('[data-planner-extra]'));
            const price = planner.querySelector('[data-planner-price]');
            const summary = planner.querySelector('[data-planner-summary]');
            const link = planner.querySelector('[data-planner-link]');

            const money = value => Math.round(value / 10) * 10;
            const format = value => money(value).toLocaleString('tr-TR') + ' TL';

            function updatePlanner() {
                const selected = services[service.value] || services['ev-temizligi'];
                const roomCount = parseInt(rooms.value || '2', 10);
                const hourCount = parseInt(hours.value || '4', 10);
                const extraValues = extras.filter(item => item.checked).map(item => item.value);

                let total = selected.base + (hourCount * selected.perHour) + (roomCount * 110);
                if (extraValues.includes('window')) total += 260;
                if (extraValues.includes('iron')) total += 190;
                if (extraValues.includes('urgent')) total += 320;

                const min = total * 0.9;
                const max = total * 1.18;
                const roomLabel = rooms.options[rooms.selectedIndex] ? rooms.options[rooms.selectedIndex].text : roomCount + '+1';

                if (hoursLabel) hoursLabel.textContent = hourCount + ' saat';
                if (price) price.textContent = format(min) + ' - ' + format(max);
                if (summary) summary.textContent = selected.label + ' - ' + roomLabel + ' - ' + hourCount + ' saat';
                if (link) {
                    const params = new URLSearchParams({
                        service: service.value,
                        budget: String(money(total)),
                        source: 'planner'
                    });
                    link.href = 'quick-request?' + params.toString();
                }
            }

            [service, rooms, hours].forEach(item => item && item.addEventListener('input', updatePlanner));
            extras.forEach(item => item.addEventListener('change', updatePlanner));
            updatePlanner();
        })();
    </script>
    <script src="assets/js/app.js?v=5.8"></script>
    <script src="assets/js/theme.js"></script>
    <script>
        const nav = document.getElementById('mainNav');
        window.addEventListener('scroll', () => {
            nav.classList.toggle('scrolled', window.scrollY > 20);
        });
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                const SW_RESET_KEY = 'tb_sw_reset_v3';
                const runRegister = () => {
                    navigator.serviceWorker.register('/sw.js?v=9')
                        .then((registration) => {
                            console.log('SW Registered: ', registration.scope);
                        })
                        .catch((error) => {
                            console.log('SW Registration Failed: ', error);
                        });
                };

                if (!localStorage.getItem(SW_RESET_KEY)) {
                    localStorage.setItem(SW_RESET_KEY, '1');
                    navigator.serviceWorker.getRegistrations()
                        .then((regs) => Promise.all(regs.map((r) => r.unregister())))
                        .then(() => (window.caches ? caches.keys().then((keys) => Promise.all(keys.map((k) => caches.delete(k)))) : Promise.resolve()))
                        .finally(() => {
                            runRegister();
                            window.location.reload();
                        });
                } else {
                    runRegister();
                }
            });
        }
    </script>
</body>
</html>

