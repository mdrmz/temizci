<?php
// ============================================================
// Temizci Burada — Genel Yardımcı Fonksiyonlar
// ============================================================

require_once __DIR__ . '/db.php';

function normalizeUtf8(string $str): string
{
    if ($str === '') {
        return $str;
    }

    if (function_exists('mb_check_encoding') && !mb_check_encoding($str, 'UTF-8')) {
        $str = mb_convert_encoding($str, 'UTF-8', 'Windows-1254,ISO-8859-9,ISO-8859-1,UTF-8');
    }

    // Recover common Turkish mojibake sequences.
    $map = [
        'Ã¼' => 'ü', 'Ãœ' => 'Ü',
        'ÄŸ' => 'ğ', 'Äž' => 'Ğ',
        'ÅŸ' => 'ş', 'Åž' => 'Ş',
        'Ä±' => 'ı', 'Ä°' => 'İ',
        'Ã¶' => 'ö', 'Ã–' => 'Ö',
        'Ã§' => 'ç', 'Ã‡' => 'Ç',
        'â‚º' => '₺',
        'â€™' => "'",
        'â€œ' => '"',
        'â€' => '"',
        'â€“' => '-',
        'â€”' => '-',
        'Â' => '',
    ];
    return strtr($str, $map);
}

function canonicalBaseUrl(): string
{
    $baseFromConfig = rtrim(APP_URL, '/');
    $hostRaw = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($hostRaw === '') {
        return $baseFromConfig;
    }

    $host = strtolower(preg_replace('/:\d+$/', '', $hostRaw) ?? $hostRaw);
    if (
        $host === '' ||
        $host === 'localhost' ||
        $host === '127.0.0.1' ||
        filter_var($host, FILTER_VALIDATE_IP)
    ) {
        return $baseFromConfig;
    }

    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $isHttps ? 'https' : 'http';
    if (!$isHttps && str_starts_with($baseFromConfig, 'https://')) {
        $scheme = 'https';
    }

    return $scheme . '://' . $host;
}

// XSS korumalı çıktı
function e(string $str): string
{
    return htmlspecialchars(normalizeUtf8($str), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Yönlendirme
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// Flash mesaj set
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Flash mesaj göster ve sil
function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Flash HTML çıktısı
function flashHtml(): string
{
    $flash = getFlash();
    if (!$flash)
        return '';
    $icons = ['success' => '✅', 'error' => '❌', 'warning' => '⚠️', 'info' => 'ℹ️'];
    $icon = $icons[$flash['type']] ?? 'ℹ️';
    return '<div class="flash flash-' . e($flash['type']) . '">' . $icon . ' ' . e($flash['message']) . '</div>';
}

function setUploadError(string $message): void
{
    $GLOBALS['tb_upload_error'] = $message;
}

function getUploadError(): string
{
    $msg = $GLOBALS['tb_upload_error'] ?? '';
    return is_string($msg) ? $msg : '';
}

function formatUploadSize(int $bytes): string
{
    if ($bytes <= 0) {
        return '0 MB';
    }
    return number_format($bytes / (1024 * 1024), 1) . ' MB';
}

// Dosya yükle
function uploadFile(array $file, string $subfolder = ''): string|false
{
    setUploadError('');

    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $uploadLimit = (string) ini_get('upload_max_filesize');
                $postLimit = (string) ini_get('post_max_size');
                setUploadError("Dosya boyutu sunucu limitini aşıyor (upload_max_filesize={$uploadLimit}, post_max_size={$postLimit}).");
                break;
            case UPLOAD_ERR_PARTIAL:
                setUploadError('Dosya yüklemesi yarım kaldı. İnternet bağlantınızı kontrol edip tekrar deneyin.');
                break;
            case UPLOAD_ERR_NO_FILE:
                setUploadError('Yüklenecek dosya seçilmedi.');
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
            case UPLOAD_ERR_EXTENSION:
            default:
                setUploadError('Sunucu dosyayı kaydedemedi. Lütfen daha sonra tekrar deneyin.');
                break;
        }
        return false;
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0) {
        setUploadError('Dosya boş görünüyor. Farklı bir fotoğraf seçip tekrar deneyin.');
        return false;
    }
    if ($size > MAX_FILE_SIZE) {
        setUploadError('Dosya çok büyük. En fazla ' . formatUploadSize(MAX_FILE_SIZE) . ' olmalı.');
        return false;
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        setUploadError('Yüklenen dosya doğrulanamadı.');
        return false;
    }

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/heic' => 'heic',
        'image/heif' => 'heif',
    ];

    $detectedMime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $mime = finfo_file($finfo, $tmp);
            finfo_close($finfo);
            if (is_string($mime)) {
                $detectedMime = strtolower(trim($mime));
            }
        }
    }
    if ($detectedMime === '' && function_exists('mime_content_type')) {
        $mime = mime_content_type($tmp);
        if (is_string($mime)) {
            $detectedMime = strtolower(trim($mime));
        }
    }
    if ($detectedMime === '') {
        $detectedMime = strtolower(trim((string) ($file['type'] ?? '')));
    }

    if (!isset($allowedMimes[$detectedMime])) {
        setUploadError('Desteklenmeyen dosya türü. JPG, PNG, WEBP, GIF veya HEIC kullanın.');
        return false;
    }

    // HEIC/HEIF dışındaki dosyalar gerçek görsel mi kontrol et.
    if (!in_array($detectedMime, ['image/heic', 'image/heif'], true) && @getimagesize($tmp) === false) {
        setUploadError('Dosya görsel olarak doğrulanamadı. Lütfen farklı bir fotoğraf deneyin.');
        return false;
    }

    $ext = $allowedMimes[$detectedMime];
    try {
        $random = bin2hex(random_bytes(12));
    } catch (Throwable $e) {
        $random = uniqid('', true);
    }
    $filename = 'img_' . $random . '.' . $ext;

    $baseDir = rtrim(UPLOAD_PATH, '/\\');
    $safeSub = trim(str_replace('\\', '/', $subfolder), '/');
    $dir = $safeSub !== '' ? ($baseDir . '/' . $safeSub) : $baseDir;

    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        setUploadError('Yükleme klasörü oluşturulamadı.');
        return false;
    }

    $dest = $dir . '/' . $filename;
    if (!move_uploaded_file($tmp, $dest)) {
        setUploadError('Dosya sunucuya taşınamadı.');
        return false;
    }

    return ($safeSub !== '' ? $safeSub . '/' : '') . $filename;
}

// İlan görüntülenme sayısını artır
function incrementViewCount(int $listingId): void
{
    $db = getDB();
    $db->prepare("UPDATE listings SET view_count = view_count + 1 WHERE id = ?")
        ->execute([$listingId]);
}

// Bildirimi oluştur
function createNotification(int $userId, string $type, string $message, string $link = ''): void
{
    $db = getDB();
    try {
        $prefStmt = $db->prepare("SELECT notif_in_app FROM users WHERE id = ? LIMIT 1");
        $prefStmt->execute([$userId]);
        $inApp = (int) ($prefStmt->fetchColumn() ?? 1);
        if ($inApp !== 1) {
            return;
        }
    } catch (Exception $e) {
        // Kolon yoksa varsayilan olarak bildirim olustur.
    }

    $db->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?,?,?,?)")
        ->execute([$userId, $type, $message, $link]);
}

// Okunmamış bildirim sayısı
function getUnreadNotificationCount(int $userId): int
{
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

// Zaman formatla
function timeAgo(string $datetime): string
{
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60)
        return 'Az önce';
    if ($diff < 3600)
        return floor($diff / 60) . ' dakika önce';
    if ($diff < 86400)
        return floor($diff / 3600) . ' saat önce';
    if ($diff < 2592000)
        return floor($diff / 86400) . ' gün önce';
    return date('d.m.Y', $time);
}

// Para formatla
function formatMoney(float $amount): string
{
    return number_format($amount, 0, ',', '.') . ' ₺';
}

// Oda konfigürasyonları
function getRoomConfigs(): array
{
    return ['1+0', '1+1', '2+0', '2+1', '3+1', '3+2', '4+1', '4+2', '5+1', '5+2', '6+1'];
}

// Tercih edilen zamanlar
function getPreferredTimes(): array
{
    return [
        'sabah' => '☀️ Sabah (08:00 – 12:00)',
        'ogle' => '🌤️ Öğle (12:00 – 16:00)',
        'aksam' => '🌙 Akşam (16:00 – 20:00)',
        'esnek' => '⏰ Esnek (Herhangi bir zaman)',
    ];
}

function ensureMarketplaceCategories(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $coreCategories = [
        ['name' => 'Hizli Yardim', 'icon' => 'HY', 'slug' => 'hizli-yardim'],
        ['name' => 'Tasima ve Kurulum', 'icon' => 'TK', 'slug' => 'tasima-yardim'],
        ['name' => 'Ogrenci Destek', 'icon' => 'OD', 'slug' => 'ogrenci-destek'],
    ];

    try {
        $db = getDB();
        $existing = $db->query("SELECT slug FROM categories")->fetchAll(PDO::FETCH_COLUMN);
        $existingMap = [];
        foreach ($existing as $slug) {
            $existingMap[(string) $slug] = true;
        }

        $insert = $db->prepare("INSERT INTO categories (name, icon, slug) VALUES (?, ?, ?)");
        foreach ($coreCategories as $cat) {
            if (!isset($existingMap[$cat['slug']])) {
                $insert->execute([$cat['name'], $cat['icon'], $cat['slug']]);
            }
        }
    } catch (Throwable $e) {
        // Kategori yazma yetkisi yoksa mevcut sistemle devam et.
    }
}

function getQuickServiceOptions(): array
{
    return [
        'ev-temizligi' => [
            'label' => 'Ev temizligi',
            'category_slug' => 'ev-temizligi',
            'title' => 'Ev temizligi icin destek ariyorum',
            'desc_hint' => 'Oda sayisi, temizlik kapsamı ve tercih ettiginiz saati yazin.',
        ],
        'tasima-yardim' => [
            'label' => 'Esya tasima / bahce yardimi',
            'category_slug' => 'tasima-yardim',
            'title' => 'Esya tasima ve duzenleme yardimi ariyorum',
            'desc_hint' => 'Tasınacak esyayi, kac kisi gerektigini ve kat bilgisini yazin.',
        ],
        'bahce' => [
            'label' => 'Bahce duzenleme',
            'category_slug' => 'bahce',
            'title' => 'Bahce duzenleme icin yardim ariyorum',
            'desc_hint' => 'Yapilacak isleri (toplama, duzenleme, tasima) acikca yazin.',
        ],
        'ogrenci-destek' => [
            'label' => 'Ogrenciye uygun gunluk is',
            'category_slug' => 'ogrenci-destek',
            'title' => 'Gunluk ve kisa sureli destek ariyorum',
            'desc_hint' => 'Isin suresini, ucret modelini ve beklentinizi belirtin.',
        ],
        'hizli-yardim' => [
            'label' => 'Acil yardim (24 saat)',
            'category_slug' => 'hizli-yardim',
            'title' => 'Acil yardim ihtiyacim var',
            'desc_hint' => 'Ihtiyacinizi net yazin, acil oldugunu belirtin.',
        ],
    ];
}

function resolveCategoryIdBySlug(array $categories, string $slug): int
{
    foreach ($categories as $category) {
        if ((string) ($category['slug'] ?? '') === $slug) {
            return (int) ($category['id'] ?? 0);
        }
    }
    return 0;
}

function detectListingSpamReason(string $title, string $description): ?string
{
    $haystack = mb_strtolower(trim($title . ' ' . $description), 'UTF-8');
    if ($haystack === '') {
        return null;
    }

    if (preg_match('/https?:\/\/|www\.|t\.me|telegram|discord|onlyfans|escort/i', $haystack)) {
        return 'Ilanda dis baglanti veya uygunsuz anahtar kelime kullanamazsiniz.';
    }

    if (preg_match('/\+?\d[\d\s\-\(\)]{8,}\d/u', $description)) {
        return 'Ilan aciklamasina telefon numarasi yazmayin. Iletisim platform icinden yapilir.';
    }

    if (preg_match('/(kripto|bitcoin|forex|yatirim tavsiyesi|bahis)/i', $haystack)) {
        return 'Ilan icerigi platform kapsami disinda gorunuyor.';
    }

    if (mb_strlen(trim($description), 'UTF-8') < 20) {
        return 'Aciklama en az 20 karakter olmali ve isi net anlatmalidir.';
    }

    return null;
}

function detectOfferSpamReason(string $message): ?string
{
    $text = mb_strtolower(trim($message), 'UTF-8');
    if ($text === '') {
        return 'Teklif mesaji bos olamaz.';
    }
    if (mb_strlen($text, 'UTF-8') < 10) {
        return 'Teklif mesaji en az 10 karakter olmalidir.';
    }
    if (preg_match('/https?:\/\/|www\.|t\.me|telegram|whatsapp/i', $text)) {
        return 'Teklif mesajinda dis baglanti veya numara paylasmayin.';
    }
    if (preg_match('/\+?\d[\d\s\-\(\)]{8,}\d/u', $text)) {
        return 'Teklif mesajinda telefon numarasi paylasmayin.';
    }
    return null;
}

function normalizePhone(string $phone): string
{
    $clean = preg_replace('/[^\d\+]/', '', $phone) ?? '';
    if ($clean === '') {
        return '';
    }
    if (str_starts_with($clean, '+90')) {
        $digits = preg_replace('/\D/', '', $clean) ?? '';
        return '+90' . substr($digits, -10);
    }
    $digits = preg_replace('/\D/', '', $clean) ?? '';
    if (str_starts_with($digits, '90') && strlen($digits) >= 12) {
        return '+90' . substr($digits, -10);
    }
    if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
        return '+90' . substr($digits, 1);
    }
    if (strlen($digits) === 10) {
        return '+90' . $digits;
    }
    return $clean;
}

function isValidTrPhone(string $phone): bool
{
    $normalized = normalizePhone($phone);
    return (bool) preg_match('/^\+90[1-9][0-9]{9}$/', $normalized);
}

function broadcastNewListingToWorkers(int $listingId): void
{
    try {
        $db = getDB();
        $ls = $db->prepare("
            SELECT l.id, l.user_id, l.title, c.name AS cat_name, h.city
            FROM listings l
            JOIN categories c ON c.id = l.category_id
            JOIN homes h ON h.id = l.home_id
            WHERE l.id = ?
            LIMIT 1
        ");
        $ls->execute([$listingId]);
        $listing = $ls->fetch();
        if (!$listing) {
            return;
        }

        $city = (string) ($listing['city'] ?? '');
        $workers = $db->prepare("
            SELECT id
            FROM users
            WHERE role = 'worker'
              AND is_active = 1
              AND id != ?
              AND (city = ? OR city IS NULL OR city = '')
            ORDER BY is_verified DESC, review_count DESC, rating DESC
            LIMIT 120
        ");
        $workers->execute([(int) $listing['user_id'], $city]);
        $ids = $workers->fetchAll(PDO::FETCH_COLUMN);
        if (empty($ids)) {
            return;
        }

        $msg = $city . ' bolgesinde yeni is: ' . (string) ($listing['title'] ?? 'Yeni ilan');
        $link = '/listings/detail.php?id=' . (int) $listingId;
        foreach ($ids as $wid) {
            createNotification((int) $wid, 'new_job_match', $msg, $link);
        }
    } catch (Throwable $e) {
        // Ana akis etkilenmesin.
    }
}

// Tüm kategorileri getir
function getCategories(): array
{
    static $cats = null;
    if ($cats === null) {
        ensureMarketplaceCategories();
        $cats = getDB()->query("SELECT * FROM categories ORDER BY id")->fetchAll();
    }
    return $cats;
}

// İlan durumu etiketi
function statusBadge(string $status): string
{
    $map = [
        'open' => ['Açık', 'badge-open'],
        'in_progress' => ['Devam Ediyor', 'badge-progress'],
        'closed' => ['Tamamlandı', 'badge-closed'],
        'cancelled' => ['İptal', 'badge-cancelled'],
    ];
    [$label, $class] = $map[$status] ?? ['Bilinmiyor', 'badge-default'];
    return '<span class="badge ' . $class . '">' . $label . '</span>';
}

// Teklif durumu etiketi
function offerStatusBadge(string $status): string
{
    $map = [
        'pending' => ['Bekliyor', 'badge-open'],
        'accepted' => ['Kabul Edildi', 'badge-closed'],
        'rejected' => ['Reddedildi', 'badge-cancelled'],
    ];
    [$label, $class] = $map[$status] ?? ['Bilinmiyor', 'badge-default'];
    return '<span class="badge ' . $class . '">' . $label . '</span>';
}

// Yıldız oranı HTML
function starRating(float $rating, int $max = 5): string
{
    $html = '';
    for ($i = 1; $i <= $max; $i++) {
        $html .= '<span class="star ' . ($i <= $rating ? 'star-filled' : 'star-empty') . '">★</span>';
    }
    return $html;
}

// Şehir listesi (Türkiye)
function getCities(): array
{
    return [
        'Adana',
        'Adıyaman',
        'Afyonkarahisar',
        'Ağrı',
        'Amasya',
        'Ankara',
        'Antalya',
        'Artvin',
        'Aydın',
        'Balıkesir',
        'Bilecik',
        'Bingöl',
        'Bitlis',
        'Bolu',
        'Burdur',
        'Bursa',
        'Çanakkale',
        'Çankırı',
        'Çorum',
        'Denizli',
        'Diyarbakır',
        'Edirne',
        'Elazığ',
        'Erzincan',
        'Erzurum',
        'Eskişehir',
        'Gaziantep',
        'Giresun',
        'Gümüşhane',
        'Hakkari',
        'Hatay',
        'Isparta',
        'Mersin',
        'İstanbul',
        'İzmir',
        'Kars',
        'Kastamonu',
        'Kayseri',
        'Kırklareli',
        'Kırşehir',
        'Kocaeli',
        'Konya',
        'Kütahya',
        'Malatya',
        'Manisa',
        'Kahramanmaraş',
        'Mardin',
        'Muğla',
        'Muş',
        'Nevşehir',
        'Niğde',
        'Ordu',
        'Rize',
        'Sakarya',
        'Samsun',
        'Siirt',
        'Sinop',
        'Sivas',
        'Tekirdağ',
        'Tokat',
        'Trabzon',
        'Tunceli',
        'Şanlıurfa',
        'Uşak',
        'Van',
        'Yozgat',
        'Zonguldak',
        'Aksaray',
        'Bayburt',
        'Karaman',
        'Kırıkkale',
        'Batman',
        'Şırnak',
        'Bartın',
        'Ardahan',
        'Iğdır',
        'Yalova',
        'Karabük',
        'Kilis',
        'Osmaniye',
        'Düzce'
    ];
}

