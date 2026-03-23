<?php
// ============================================================
// Temizci Burada — Rozet (Badge) Sistemi
// ============================================================

require_once __DIR__ . '/config.php';

/**
 * Rozet tanımları
 */
function getBadgeDefinitions(): array
{
    return [
        'first_job' => [
            'name' => 'İlk İş',
            'icon' => '🌟',
            'desc' => 'İlk işini tamamladı',
            'check' => fn($stats) => $stats['completed_jobs'] >= 1,
        ],
        'five_jobs' => [
            'name' => '5 İş Ustası',
            'icon' => '💪',
            'desc' => '5 işi başarıyla tamamladı',
            'check' => fn($stats) => $stats['completed_jobs'] >= 5,
        ],
        'ten_jobs' => [
            'name' => '10+ Profesyonel',
            'icon' => '🏆',
            'desc' => '10 veya daha fazla iş tamamladı',
            'check' => fn($stats) => $stats['completed_jobs'] >= 10,
        ],
        'twenty_five_jobs' => [
            'name' => 'Usta Temizlikçi',
            'icon' => '👑',
            'desc' => '25+ işi tamamlayan deneyimli profesyonel',
            'check' => fn($stats) => $stats['completed_jobs'] >= 25,
        ],
        'high_rating' => [
            'name' => 'Yüksek Puanlı',
            'icon' => '⭐',
            'desc' => '4.5+ ortalama puan',
            'check' => fn($stats) => $stats['rating'] >= 4.5 && $stats['review_count'] >= 3,
        ],
        'five_star' => [
            'name' => 'Mükemmel',
            'icon' => '🌟',
            'desc' => '5/5 ortalama puan (min 5 değerlendirme)',
            'check' => fn($stats) => $stats['rating'] >= 4.9 && $stats['review_count'] >= 5,
        ],
        'fast_responder' => [
            'name' => 'Hızlı Yanıt',
            'icon' => '⚡',
            'desc' => '10+ teklif vermiş aktif kullanıcı',
            'check' => fn($stats) => $stats['total_offers'] >= 10,
        ],
        'popular' => [
            'name' => 'Popüler',
            'icon' => '🔥',
            'desc' => '20+ teklif almış popüler ilan sahibi',
            'check' => fn($stats) => $stats['received_offers'] >= 20,
        ],
        'verified' => [
            'name' => 'Doğrulanmış',
            'icon' => '✅',
            'desc' => 'Kimliği doğrulanmış güvenilir kullanıcı',
            'check' => fn($stats) => $stats['is_verified'] == 1,
        ],
        'referrer' => [
            'name' => 'Davetçi',
            'icon' => '🎁',
            'desc' => '3+ kişiyi platforma davet etti',
            'check' => fn($stats) => $stats['referral_count'] >= 3,
        ],
    ];
}

/**
 * Kullanıcının istatistiklerini hesapla
 */
function getUserStats(int $userId): array
{
    $db = getDB();

    // Tamamlanan iş sayısı
    $stmt = $db->prepare("SELECT COUNT(*) FROM offers o JOIN listings l ON o.listing_id = l.id WHERE o.user_id = ? AND o.status = 'accepted' AND l.status = 'closed'");
    $stmt->execute([$userId]);
    $completedJobs = (int)$stmt->fetchColumn();

    // Kullanıcı bilgileri
    $stmt = $db->prepare("SELECT rating, review_count, is_verified FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    // Verdiği teklif sayısı
    $stmt = $db->prepare("SELECT COUNT(*) FROM offers WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalOffers = (int)$stmt->fetchColumn();

    // Aldığı teklif sayısı
    $stmt = $db->prepare("SELECT COUNT(*) FROM offers o JOIN listings l ON o.listing_id = l.id WHERE l.user_id = ?");
    $stmt->execute([$userId]);
    $receivedOffers = (int)$stmt->fetchColumn();

    // Referans sayısı
    $referralCount = 0;
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE referred_by = ?");
        $stmt->execute([$userId]);
        $referralCount = (int)$stmt->fetchColumn();
    } catch (Exception $e) {}

    return [
        'completed_jobs' => $completedJobs,
        'rating' => (float)($user['rating'] ?? 0),
        'review_count' => (int)($user['review_count'] ?? 0),
        'is_verified' => (int)($user['is_verified'] ?? 0),
        'total_offers' => $totalOffers,
        'received_offers' => $receivedOffers,
        'referral_count' => $referralCount,
    ];
}

/**
 * Kullanıcının rozetlerini kontrol et ve yenilerini ver
 */
function checkAndAwardBadges(int $userId): array
{
    $db = getDB();
    $stats = getUserStats($userId);
    $definitions = getBadgeDefinitions();
    $newBadges = [];

    foreach ($definitions as $type => $badge) {
        if (($badge['check'])($stats)) {
            try {
                $stmt = $db->prepare("INSERT IGNORE INTO badges (user_id, badge_type, badge_name, badge_icon) VALUES (?, ?, ?, ?)");
                $stmt->execute([$userId, $type, $badge['name'], $badge['icon']]);
                if ($stmt->rowCount() > 0) {
                    $newBadges[] = $badge;
                }
            } catch (Exception $e) {
                // badges tablosu yoksa sessizce geç
            }
        }
    }

    return $newBadges;
}

/**
 * Kullanıcının mevcut rozetlerini getir
 */
function getUserBadges(int $userId): array
{
    $db = getDB();
    try {
        $stmt = $db->prepare("SELECT * FROM badges WHERE user_id = ? ORDER BY earned_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Rozet HTML'i oluştur (küçük etiket olarak)
 */
function badgeTag(string $icon, string $name): string
{
    return "<span style='display:inline-flex;align-items:center;gap:4px;background:rgba(99,102,241,0.08);color:#6366f1;padding:4px 10px;border-radius:20px;font-size:0.75rem;font-weight:600;border:1px solid rgba(99,102,241,0.15);'>{$icon} {$name}</span>";
}
