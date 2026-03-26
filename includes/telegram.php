<?php
// ============================================================
// Temizci Burada  -  Telegram Bot Bildirimleri
// ============================================================
// Telegram Bot API üzerinden bildirim gönderir.
// Bot token'ı .env dosyasında TELEGRAM_BOT_TOKEN olarak ayarlayın.
// Kullanıcılar profillerinde Telegram chat ID'lerini kaydeder.

require_once __DIR__ . '/config.php';

// Telegram sabitleri
define('TELEGRAM_BOT_TOKEN', $_ENV['TELEGRAM_BOT_TOKEN'] ?? '');
define('TELEGRAM_API_URL', 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN);

/**
 * Telegram'a mesaj gönder
 */
function sendTelegram(string $chatId, string $message, string $parseMode = 'HTML'): bool
{
    if (empty(TELEGRAM_BOT_TOKEN) || empty($chatId)) {
        return false;
    }

    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => $parseMode,
        'disable_web_page_preview' => false,
    ];

    $ch = curl_init(TELEGRAM_API_URL . '/sendMessage');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result !== false;
}

/**
 * Kullanıcının Telegram chat ID'sini getir
 */
function getUserTelegramId(int $userId): ?string
{
    $db = getDB();
    try {
        $stmt = $db->prepare("SELECT telegram_chat_id FROM users WHERE id = ? AND telegram_chat_id IS NOT NULL AND telegram_chat_id != ''");
        $stmt->execute([$userId]);
        $result = $stmt->fetchColumn();
        return $result ?: null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Yeni teklif  -  Telegram bildirimi
 */
function telegramNotifyNewOffer(int $ownerId, string $workerName, string $price, string $listingTitle, int $listingId): void
{
    $chatId = getUserTelegramId($ownerId);
    if (!$chatId) return;

    $url = APP_URL . "/listings/detail?id={$listingId}";
    $message = " <b>Yeni Teklif Geldi!</b>\n\n"
        . " İlan: <b>{$listingTitle}</b>\n"
        . " Teklif Veren: {$workerName}\n"
        . " Fiyat: <b>{$price}</b>\n\n"
        . " <a href='{$url}'>Teklifi İncele â†’</a>";

    sendTelegram($chatId, $message);
}

/**
 * Teklif kabul  -  Telegram bildirimi
 */
function telegramNotifyOfferAccepted(int $workerId, string $listingTitle, int $listingId): void
{
    $chatId = getUserTelegramId($workerId);
    if (!$chatId) return;

    $url = APP_URL . "/listings/detail?id={$listingId}";
    $message = " <b>Teklifiniz Kabul Edildi!</b>\n\n"
        . " İlan: <b>{$listingTitle}</b>\n\n"
        . " Tebrikler! İlan sahibi teklifinizi kabul etti.\n\n"
        . " <a href='{$url}'>Detayları Gör â†’</a>";

    sendTelegram($chatId, $message);
}

/**
 * Yeni mesaj  -  Telegram bildirimi
 */
function telegramNotifyNewMessage(int $receiverId, string $senderName, string $preview): void
{
    $chatId = getUserTelegramId($receiverId);
    if (!$chatId) return;

    $url = APP_URL . "/messages";
    $preview = mb_substr($preview, 0, 100);
    $message = " <b>Yeni Mesaj!</b>\n\n"
        . " Gönderen: <b>{$senderName}</b>\n"
        . " \"{$preview}\"\n\n"
        . " <a href='{$url}'>Mesajı Oku →</a>";

    sendTelegram($chatId, $message);
}

/**
 * İş tamamlandı  -  Telegram bildirimi
 */
function telegramNotifyJobCompleted(int $workerId, string $listingTitle, int $rating): void
{
    $chatId = getUserTelegramId($workerId);
    if (!$chatId) return;

    $stars = str_repeat('⭐', $rating);
    $message = " <b>İş Tamamlandı!</b>\n\n"
        . " İlan: <b>{$listingTitle}</b>\n"
        . " Puanınız: {$stars} ({$rating}/5)\n\n"
        . "Harika iş çıkardınız!";

    sendTelegram($chatId, $message);
}

