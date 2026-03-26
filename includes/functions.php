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

// Dosya yükle
function uploadFile(array $file, string $subfolder = ''): string|false
{
    if ($file['error'] !== UPLOAD_ERR_OK)
        return false;
    if ($file['size'] > MAX_FILE_SIZE)
        return false;
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($file['type'], $allowed))
        return false;
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_', true) . '.' . strtolower($ext);
    $dir = UPLOAD_PATH . $subfolder;
    if (!is_dir($dir))
        mkdir($dir, 0755, true);
    $dest = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest))
        return false;
    return ($subfolder ? $subfolder . '/' : '') . $filename;
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

// Tüm kategorileri getir
function getCategories(): array
{
    static $cats = null;
    if ($cats === null) {
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

