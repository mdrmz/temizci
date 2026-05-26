<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Giriş gerekmez - herkese açık
if (session_status() === PHP_SESSION_NONE)
    session_start();

$db = getDB();
$categories = getCategories();
$cities = getCities();

// Filtreler
$search = trim($_GET['q'] ?? '');
$catSlug = trim($_GET['cat'] ?? '');
$city = trim($_GET['city'] ?? '');
$district = trim($_GET['district'] ?? '');
$minPrice = (int)($_GET['min_price'] ?? 0);
$maxPrice = (int)($_GET['max_price'] ?? 0);
$dateFrom = trim($_GET['date_from'] ?? '');
$minRating = max(0, min(5, (float) ($_GET['min_rating'] ?? 0)));
$onlyRecurring = isset($_GET['only_recurring']) ? 1 : 0;
$onlyUrgent = isset($_GET['only_urgent']) ? 1 : 0;
$sortBy = trim($_GET['sort'] ?? 'newest');
$catId = 0;
if ($catSlug) {
    foreach ($categories as $c) {
        if ($c['slug'] === $catSlug) {
            $catId = $c['id'];
            break;
        }
    }
}

// Sayfalama
$perPage = 12;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$where = ["l.status = 'open'"];
$params = [];

if ($search) {
    $where[] = "(l.title LIKE ? OR l.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($catId) {
    $where[] = "l.category_id = ?";
    $params[] = $catId;
}
if ($city) {
    $where[] = "h.city = ?";
    $params[] = $city;
}
if ($district) {
    $where[] = "h.district LIKE ?";
    $params[] = "%$district%";
}
if ($minPrice > 0) {
    $where[] = "l.budget >= ?";
    $params[] = $minPrice;
}
if ($maxPrice > 0) {
    $where[] = "l.budget <= ?";
    $params[] = $maxPrice;
}
if ($dateFrom) {
    $where[] = "l.preferred_date >= ?";
    $params[] = $dateFrom;
}
if ($minRating > 0) {
    $where[] = "u.rating >= ?";
    $params[] = $minRating;
}
if ($onlyRecurring) {
    $where[] = "COALESCE(l.is_recurring, 0) = 1";
}
if ($onlyUrgent) {
    $where[] = "l.preferred_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 DAY)";
}

// Sıralama
$orderBy = match($sortBy) {
    'best_price' => 'l.budget ASC',
    'highest_price' => 'l.budget DESC',
    'top_rated' => 'u.rating DESC, u.review_count DESC',
    'most_offers' => 'offer_count DESC',
    'soonest' => 'l.preferred_date ASC',
    default => 'l.created_at DESC',
};

$whereStr = implode(' AND ', $where);

$countStmt = $db->prepare("SELECT COUNT(*) FROM listings l JOIN homes h ON l.home_id=h.id WHERE $whereStr");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = (int) ceil($total / $perPage);

$stmt = $db->prepare("
    SELECT l.*, c.name AS cat_name, c.icon AS cat_icon,
           u.name AS owner_name, u.rating AS owner_rating,
           h.city, h.district, h.room_config, h.photo AS home_photo,
           (SELECT COUNT(*) FROM offers WHERE listing_id = l.id) AS offer_count
    FROM listings l
    JOIN categories c ON l.category_id = c.id
    JOIN users u ON l.user_id = u.id
    JOIN homes h ON l.home_id = h.id
    WHERE $whereStr
    ORDER BY $orderBy
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$listings = $stmt->fetchAll();

$isLoggedIn = isLoggedIn();
$notifCount = $isLoggedIn ? getUnreadNotificationCount(currentUser()['id']) : 0;
$initials = $isLoggedIn ? strtoupper(substr(currentUser()['name'], 0, 1)) : '';

function buildUrl(array $extra = [], array $remove = []): string
{
    $params = $_GET;
    foreach ($extra as $k => $v)
        $params[$k] = $v;
    foreach ($remove as $k)
        unset($params[$k]);
    unset($params['page']);
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $baseUrl = canonicalBaseUrl();
    $selectedCatName = '';
    if ($catSlug) {
        foreach ($categories as $c) {
            if ($c['slug'] === $catSlug) {
                $selectedCatName = normalizeUtf8((string) $c['name']);
                break;
            }
        }
    }

    $seoTitleBase = 'Temizlik Ilanlari';
    if ($selectedCatName !== '') {
        $seoTitleBase = $selectedCatName . ' Ilanlari';
    }
    if ($search !== '') {
        $seoTitleBase = '"' . normalizeUtf8($search) . '" icin ' . $seoTitleBase;
    }
    if ($city) {
        $seoTitleBase .= ' - ' . normalizeUtf8($city);
    }
    $seoTitle = $seoTitleBase . ' | Temizci Burada';

    $seoDesc = 'Turkiye genelinde guncel temizlik ilanlarini karsilastirin.';
    if ($city && $selectedCatName !== '') {
        $seoDesc = normalizeUtf8($city) . ' bolgesindeki ' . $selectedCatName . ' ilanlarini inceleyin, butce ve tarih filtreleriyle hizli sonuca ulasin.';
    } elseif ($city) {
        $seoDesc = normalizeUtf8($city) . ' icin acik temizlik ilanlarini inceleyin ve size uygun isi bulun.';
    } elseif ($selectedCatName !== '') {
        $seoDesc = $selectedCatName . ' kategorisindeki guncel ilanlari tek sayfada karsilastirin.';
    } elseif ($search !== '') {
        $seoDesc = '"' . normalizeUtf8($search) . '" aramaniza uygun guncel temizlik ilanlari burada.';
    }

    $canonicalParams = array_filter(['q' => $search, 'cat' => $catSlug, 'city' => $city]);
    $canonicalQuery = $canonicalParams ? '?' . http_build_query($canonicalParams) : '';
    $canonicalUrl = $baseUrl . '/listings/browse' . $canonicalQuery;

    $hasLowValueParams = $district || $minPrice || $maxPrice || $dateFrom || $minRating || $onlyRecurring || $onlyUrgent || ($sortBy !== 'newest');
    $robotsContent = ($page > 5 || $hasLowValueParams) ? 'noindex, follow' : 'index, follow';

    $itemListElements = [];
    $pos = 1 + (($page - 1) * $perPage);
    foreach ($listings as $l) {
        $itemListElements[] = [
            '@type' => 'ListItem',
            'position' => $pos++,
            'url' => $baseUrl . '/listings/detail?id=' . (int) $l['id'],
            'name' => normalizeUtf8((string) $l['title']),
        ];
    }
    $collectionSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $seoTitleBase,
        'url' => $canonicalUrl,
        'inLanguage' => 'tr-TR',
        'isPartOf' => ['@type' => 'WebSite', 'name' => 'Temizci Burada', 'url' => $baseUrl . '/'],
        'mainEntity' => [
            '@type' => 'ItemList',
            'itemListElement' => $itemListElements,
        ],
    ];
    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => $baseUrl . '/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Ilanlar', 'item' => $baseUrl . '/listings/browse'],
        ],
    ];
    if ($city) {
        $breadcrumbSchema['itemListElement'][] = ['@type' => 'ListItem', 'position' => 3, 'name' => normalizeUtf8($city), 'item' => $canonicalUrl];
    } elseif ($selectedCatName !== '') {
        $breadcrumbSchema['itemListElement'][] = ['@type' => 'ListItem', 'position' => 3, 'name' => $selectedCatName, 'item' => $canonicalUrl];
    }
    ?>
    <title><?= $seoTitle ?></title>
    <meta name="description" content="<?= e($seoDesc) ?>">
    <meta name="robots" content="<?= e($robotsContent) ?>">
    <link rel="canonical" href="<?= $canonicalUrl ?>">
    <link rel="alternate" hreflang="tr-TR" href="<?= $canonicalUrl ?>">
    <link rel="alternate" hreflang="x-default" href="<?= $baseUrl ?>/listings/browse">
    <meta name="geo.region" content="TR">
    <meta name="geo.placename" content="<?= e($city ? (normalizeUtf8($city) . ', Turkiye') : 'Turkiye') ?>">
    <!-- Open Graph -->
    <meta property="og:title" content="<?= $seoTitle ?>">
    <meta property="og:description" content="<?= e($seoDesc) ?>">
    <meta property="og:url" content="<?= $canonicalUrl ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Temizci Burada">
    <meta property="og:locale" content="tr_TR">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= $seoTitle ?>">
    <meta name="twitter:description" content="<?= e($seoDesc) ?>">
    <script type="application/ld+json"><?= json_encode($collectionSchema, JSON_UNESCAPED_UNICODE) ?></script>
    <script type="application/ld+json"><?= json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE) ?></script>
    <link rel="stylesheet" href="../assets/css/style.css?v=5.9">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">

    <!-- SEO & Favicon -->
    <link rel="icon" href="/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="/logo.png">
    <meta property="og:image" content="<?= e($baseUrl . '/logo.png') ?>">
</head>


<body class="browse-page<?= $isLoggedIn ? '' : ' public-page' ?>">

    <?php if ($isLoggedIn): ?>
        <div class="app-layout">
            <?php include '../includes/sidebar.php'; ?>
            <div class="main-content">
                <?php $headerTitle = 'İlanları Gez'; include '../includes/app-header.php'; ?>

            <div class="page-content">
                <?php else: ?>
                    <!-- Public navbar -->
                    <nav class="navbar scrolled">
                        <div class="navbar-inner container">
                            <a href="../index" class="navbar-logo">
                                <div class="logo-icon">
                                    <img src="../logo.png" alt="Temizci Burada">
                                </div>
                                <span class="navbar-logo-text"><span>Temizci Burada</span></span>
                            </a>
                            <div class="navbar-nav"><a href="browse">İlanlar</a></div>
                            <div class="navbar-actions">
                                <button class="theme-toggle-btn" id="themeToggle" title="Tema Değiştir"></button><a href="../login" class="btn btn-outline btn-sm">Giriş
                                    Yap</a><a href="../register" class="btn btn-primary btn-sm">Kayıt Ol</a></div>
                        </div>
                    </nav>
                    <div style="padding: calc(var(--header-h) + 24px) 0 40px;">
                        <div class="container">
                        <?php endif; ?>

                        <?= isset($flashShown) ? '' : flashHtml() ?>

                        <div class="browse-hero">
                            <div>
                                <div class="browse-kicker">Canli ilan pazari</div>
                                <div class="page-title">İlanları Gez</div>
                                <div class="page-subtitle">
                                    <?= $total ?> ilan bulundu
                                    <?= $search ? "  -  \"" . e($search) . "\"" : '' ?>
                                </div>
                            </div>
                            <div class="browse-hero-stats">
                                <span>Hizli teklif</span>
                                <span>Sehir filtreli</span>
                                <span>Butce karsilastir</span>
                            </div>
                        </div>

                        <!-- Arama / Filtre -->
                        <form method="GET" class="search-bar browse-search-bar" autocomplete="off">
                            <div class="form-group" style="flex:2;min-width:200px;">
                                <label class="form-label">Arama</label>
                                <input type="text" name="q" class="form-control" placeholder="İlan ara..." autocomplete="off" spellcheck="false" value="<?= e($search) ?>">
                            </div>
                            <div class="form-group" style="flex:1.2;">
                                <label class="form-label">Kategori</label>
                                <select name="cat" class="form-control">
                                    <option value="">Tümü</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= e($cat['slug']) ?>" <?= $catSlug === $cat['slug'] ? 'selected' : '' ?>>
                                            <?= $cat['icon'] ?>
                                            <?= e($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" style="flex:1.2;">
                                <label class="form-label">Şehir</label>
                                <select name="city" class="form-control">
                                    <option value="">Tüm Şehirler</option>
                                    <?php foreach ($cities as $c): ?>
                                        <option value="<?= e($c) ?>" <?= $city === $c ? 'selected' : '' ?>>
                                            <?= e($c) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div style="padding-top:22px;">
                                <button type="submit" class="btn btn-primary"> Ara</button>
                                <?php if ($search || $catSlug || $city || $district || $minPrice || $maxPrice || $dateFrom || $minRating || $onlyRecurring || $onlyUrgent): ?>
                                    <a href="browse" class="btn btn-ghost" style="margin-left:6px;"> Temizle</a>
                                <?php endif; ?>
                            </div>
                        </form>

                        <!-- Gelişmiş Filtreler -->
                        <div class="browse-filter-toggle">
                            <button onclick="document.getElementById('advFilters').style.display = document.getElementById('advFilters').style.display === 'none' ? 'flex' : 'none'" 
                                style="background:none;border:none;color:var(--primary);font-size:0.85rem;font-weight:600;cursor:pointer;padding:0;"> Gelişmiş Filtreler <?= ($district || $minPrice || $maxPrice || $dateFrom || $minRating || $onlyRecurring || $onlyUrgent || $sortBy !== 'newest') ? '(aktif)' : '' ?></button>
                            <form method="GET" id="advFilters" class="search-bar" style="display:<?= ($district || $minPrice || $maxPrice || $dateFrom || $minRating || $onlyRecurring || $onlyUrgent || $sortBy !== 'newest') ? 'flex' : 'none' ?>;margin-top:10px;">
                                <?php if ($search): ?><input type="hidden" name="q" value="<?= e($search) ?>"><?php endif; ?>
                                <?php if ($catSlug): ?><input type="hidden" name="cat" value="<?= e($catSlug) ?>"><?php endif; ?>
                                <?php if ($city): ?><input type="hidden" name="city" value="<?= e($city) ?>"><?php endif; ?>
                                <div class="form-group" style="flex:1;">
                                    <label class="form-label">İlçe</label>
                                    <input type="text" name="district" class="form-control" placeholder="Örn: Kadıköy" value="<?= e($district) ?>">
                                </div>
                                <div class="form-group" style="flex:1;">
                                    <label class="form-label">Min Bütçe (₺)</label>
                                    <input type="number" name="min_price" class="form-control" placeholder="0" value="<?= $minPrice ?: '' ?>" min="0">
                                </div>
                                <div class="form-group" style="flex:1;">
                                    <label class="form-label">Max Bütçe (₺)</label>
                                    <input type="number" name="max_price" class="form-control" placeholder="∞" value="<?= $maxPrice ?: '' ?>" min="0">
                                </div>
                                <div class="form-group" style="flex:1;">
                                    <label class="form-label">Tarihten İtibaren</label>
                                    <input type="date" name="date_from" class="form-control" value="<?= e($dateFrom) ?>">
                                </div>
                                <div class="form-group" style="flex:1;">
                                    <label class="form-label">Min Puan</label>
                                    <select name="min_rating" class="form-control">
                                        <option value="0" <?= $minRating <= 0 ? 'selected' : '' ?>>Farketmez</option>
                                        <option value="3" <?= $minRating == 3.0 ? 'selected' : '' ?>>3.0+</option>
                                        <option value="3.5" <?= $minRating == 3.5 ? 'selected' : '' ?>>3.5+</option>
                                        <option value="4" <?= $minRating == 4.0 ? 'selected' : '' ?>>4.0+</option>
                                        <option value="4.5" <?= $minRating == 4.5 ? 'selected' : '' ?>>4.5+</option>
                                    </select>
                                </div>
                                <div class="form-group" style="flex:1.2;">
                                    <label class="form-label">Sırala</label>
                                    <select name="sort" class="form-control">
                                        <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>> En Yeni</option>
                                        <option value="best_price" <?= $sortBy === 'best_price' ? 'selected' : '' ?>> En Uygun</option>
                                        <option value="highest_price" <?= $sortBy === 'highest_price' ? 'selected' : '' ?>> En Yüksek Bütçe</option>
                                        <option value="top_rated" <?= $sortBy === 'top_rated' ? 'selected' : '' ?>> En Yüksek Puanlı Ev Sahibi</option>
                                        <option value="most_offers" <?= $sortBy === 'most_offers' ? 'selected' : '' ?>> En Çok Teklif</option>
                                        <option value="soonest" <?= $sortBy === 'soonest' ? 'selected' : '' ?>> En Yakın Tarih</option>
                                    </select>
                                </div>
                                <div class="form-group" style="display:flex;align-items:end;gap:12px;min-width:230px;">
                                    <label style="display:flex;align-items:center;gap:6px;font-size:0.86rem;margin-bottom:10px;">
                                        <input type="checkbox" name="only_urgent" value="1" <?= $onlyUrgent ? 'checked' : '' ?>> Sadece acil (48 saat)
                                    </label>
                                    <label style="display:flex;align-items:center;gap:6px;font-size:0.86rem;margin-bottom:10px;">
                                        <input type="checkbox" name="only_recurring" value="1" <?= $onlyRecurring ? 'checked' : '' ?>> Sadece periyodik
                                    </label>
                                </div>
                                <div style="padding-top:22px;">
                                    <button type="submit" class="btn btn-primary btn-sm">Uygula</button>
                                </div>
                            </form>
                        </div>

                        <!-- Aktif Filtreler -->
                        <?php if ($catId || $city || $district || $minRating || $onlyRecurring || $onlyUrgent): ?>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
                                <?php foreach ($categories as $c):
                                    if ($c['id'] == $catId): ?>
                                        <a href="<?= buildUrl(remove: ['cat']) ?>" class="badge badge-closed"
                                            style="padding:6px 14px;font-size:0.82rem;">
                                            <?= $c['icon'] ?>
                                            <?= e($c['name']) ?> 
                                        </a>
                                    <?php endif; endforeach; ?>
                                <?php if ($city): ?>
                                    <a href="<?= buildUrl(remove: ['city']) ?>" class="badge badge-open"
                                        style="padding:6px 14px;font-size:0.82rem;">
                                        <?= e($city) ?> 
                                    </a>
                                <?php endif; ?>
                                <?php if ($district): ?>
                                    <a href="<?= buildUrl(remove: ['district']) ?>" class="badge"
                                        style="padding:6px 14px;font-size:0.82rem;">
                                        İlçe: <?= e($district) ?> 
                                    </a>
                                <?php endif; ?>
                                <?php if ($minRating > 0): ?>
                                    <a href="<?= buildUrl(remove: ['min_rating']) ?>" class="badge"
                                        style="padding:6px 14px;font-size:0.82rem;">
                                        Min puan: <?= number_format($minRating, 1) ?>+ 
                                    </a>
                                <?php endif; ?>
                                <?php if ($onlyUrgent): ?>
                                    <a href="<?= buildUrl(remove: ['only_urgent']) ?>" class="badge"
                                        style="padding:6px 14px;font-size:0.82rem;">
                                        Acil 
                                    </a>
                                <?php endif; ?>
                                <?php if ($onlyRecurring): ?>
                                    <a href="<?= buildUrl(remove: ['only_recurring']) ?>" class="badge"
                                        style="padding:6px 14px;font-size:0.82rem;">
                                        Periyodik 
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- İlan Grid -->
                        <?php if (empty($listings)): ?>
                            <div class="card">
                                <div class="empty-state">
                                    <div class="empty-state-icon"></div>
                                    <h3>İlan bulunamadı</h3>
                                    <p>Arama kriterlerinizi değiştirerek tekrar deneyin.</p>
                                    <a href="browse" class="btn btn-primary">Tüm İlanları Gör</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="grid-3">
                                <?php foreach ($listings as $l): ?>
                                    <div class="card listing-card">
                                        <?php if ($l['home_photo']): ?>
                                            <img src="<?= UPLOAD_URL . e($l['home_photo']) ?>" alt="" class="card-img">
                                        <?php else: ?>
                                            <div class="card-img-placeholder">
                                                <?= $l['cat_icon'] ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="card-content">
                                            <div class="listing-cat">
                                                <?= $l['cat_icon'] ?>
                                                <?= e($l['cat_name']) ?>
                                            </div>
                                            <div class="listing-title">
                                                <?= e($l['title']) ?>
                                            </div>
                                            <div class="listing-meta">
                                                <span>
                                                    <?= e($l['city']) ?>
                                                </span>
                                                <?php if (!empty($l['district'])): ?>
                                                    <span>
                                                        <?= e($l['district']) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <span>
                                                    <?= e($l['room_config']) ?>
                                                </span>
                                                <span>
                                                    <?= date('d M Y', strtotime($l['preferred_date'])) ?>
                                                </span>
                                                <span>
                                                    <?= $l['offer_count'] ?> teklif
                                                </span>
                                                <span>
                                                    <?= $l['owner_rating'] ? number_format((float) $l['owner_rating'], 1) . '/5 ev sahibi puanı' : 'Yeni ev sahibi' ?>
                                                </span>
                                            </div>
                                            <p
                                                style="font-size:0.82rem;color:var(--text-muted);margin-bottom:12px;line-height:1.5;">
                                                <?= e(mb_substr($l['description'], 0, 90)) ?>...
                                            </p>
                                            <div class="listing-footer">
                                                <?php if ($l['budget']): ?>
                                                    <span class="listing-budget">
                                                        <?= formatMoney($l['budget']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="font-size:0.82rem;color:var(--text-muted);">Bütçe açık</span>
                                                <?php endif; ?>
                                                <a href="detail?id=<?= $l['id'] ?>" class="btn btn-primary btn-sm">Teklif Ver</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Sayfalama -->
                            <?php if ($pages > 1): ?>
                                <div class="pagination">
                                    <?php if ($page > 1): ?>
                                        <a href="<?= buildUrl(['page' => $page - 1]) ?>" class="page-btn"><</a>
                                    <?php endif; ?>
                                    <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
                                        <a href="<?= buildUrl(['page' => $i]) ?>"
                                            class="page-btn <?= $i === $page ? 'active' : '' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor; ?>
                                    <?php if ($page < $pages): ?>
                                        <a href="<?= buildUrl(['page' => $page + 1]) ?>" class="page-btn">></a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($isLoggedIn): ?>
                        </div>
                    </div>
                </div>
                <div class="sidebar-overlay" id="sidebarOverlay"></div>
            <?php else: ?>
        </div>
        </div>
        <?php include '../includes/footer.php'; ?>
    <?php endif; ?>

    <script src="../assets/js/app.js?v=5.8"></script>
    <script src="../assets/js/theme.js"></script>
</body>

</html>




