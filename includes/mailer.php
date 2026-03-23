<?php
// ============================================================
// Temizci Burada — E-posta Gönderme Yardımcısı
// ============================================================
// PHP mail() fonksiyonu ile basit bildirim e-postaları gönderir.
// Production'da SMTP kullanmak için bu dosyayı güncelleyebilirsiniz.

require_once __DIR__ . '/config.php';

/**
 * Güzel tasarımlı HTML e-posta gönder
 */
function sendMail(string $to, string $subject, string $body, string $actionUrl = '', string $actionText = ''): bool
{
    $appName = APP_NAME;
    $appUrl = APP_URL;
    
    $actionBtn = '';
    if ($actionUrl && $actionText) {
        $actionBtn = "
        <div style='text-align:center;margin:30px 0;'>
            <a href='{$actionUrl}' style='background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;padding:14px 32px;border-radius:12px;text-decoration:none;font-weight:700;font-size:0.95rem;display:inline-block;'>{$actionText}</a>
        </div>";
    }

    $html = "
    <!DOCTYPE html>
    <html>
    <head><meta charset='utf-8'></head>
    <body style='margin:0;padding:0;background:#f4f4f8;font-family:Inter,Arial,sans-serif;'>
        <div style='max-width:560px;margin:40px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);'>
            <!-- Header -->
            <div style='background:linear-gradient(135deg,#0f0c29,#302b63);padding:28px;text-align:center;'>
                <div style='font-size:1.4rem;font-weight:800;color:#fff;'>{$appName}</div>
                <div style='font-size:0.8rem;color:rgba(255,255,255,0.6);margin-top:4px;'>Ev Temizliği Platformu</div>
            </div>
            
            <!-- Content -->
            <div style='padding:32px 28px;'>
                <div style='font-size:0.95rem;color:#333;line-height:1.8;'>
                    {$body}
                </div>
                {$actionBtn}
            </div>
            
            <!-- Footer -->
            <div style='background:#f8f8fc;padding:20px 28px;text-align:center;border-top:1px solid #eee;'>
                <div style='font-size:0.75rem;color:#999;'>
                    Bu e-posta <a href='{$appUrl}' style='color:#6366f1;text-decoration:none;'>{$appName}</a> tarafından gönderilmiştir.<br>
                    Bildirim ayarlarınızı profilinizden değiştirebilirsiniz.
                </div>
            </div>
        </div>
    </body>
    </html>";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . $appName . " <noreply@temizciburada.com>\r\n";
    $headers .= "Reply-To: info@temizciburada.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    return @mail($to, "=?UTF-8?B?" . base64_encode($subject) . "?=", $html, $headers);
}

/**
 * Yeni teklif geldiğinde ilan sahibine e-posta
 */
function notifyNewOffer(int $ownerId, string $workerName, string $price, string $listingTitle, int $listingId): void
{
    $db = getDB();
    $stmt = $db->prepare("SELECT email, name FROM users WHERE id = ?");
    $stmt->execute([$ownerId]);
    $owner = $stmt->fetch();
    if (!$owner) return;

    $body = "
        <p>Merhaba <strong>{$owner['name']}</strong>,</p>
        <p>📋 <strong>\"{$listingTitle}\"</strong> ilanınıza yeni bir teklif geldi!</p>
        <div style='background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:16px;margin:16px 0;'>
            <div style='font-weight:700;color:#166534;'>💰 Teklif: {$price}</div>
            <div style='color:#15803d;font-size:0.88rem;margin-top:4px;'>👤 Teklif Veren: {$workerName}</div>
        </div>
        <p>İlanınıza gelen teklifleri inceleyip, en uygununu seçebilirsiniz.</p>
    ";
    
    sendMail($owner['email'], "Yeni Teklif: {$listingTitle}", $body, APP_URL . "/listings/detail?id={$listingId}", "Teklifi İncele →");
}

/**
 * Teklif kabul edildiğinde işçiye e-posta
 */
function notifyOfferAccepted(int $workerId, string $listingTitle, int $listingId): void
{
    $db = getDB();
    $stmt = $db->prepare("SELECT email, name FROM users WHERE id = ?");
    $stmt->execute([$workerId]);
    $worker = $stmt->fetch();
    if (!$worker) return;

    $body = "
        <p>Merhaba <strong>{$worker['name']}</strong>,</p>
        <p>🎉 Harika haber! <strong>\"{$listingTitle}\"</strong> ilanına verdiğiniz teklif <strong style='color:#059669;'>kabul edildi!</strong></p>
        <p>İlan sahibi ile iletişime geçerek işin detaylarını konuşabilirsiniz.</p>
    ";
    
    sendMail($worker['email'], "✅ Teklifiniz Kabul Edildi!", $body, APP_URL . "/listings/detail?id={$listingId}", "Detayları Gör →");
}

/**
 * Yeni mesaj geldiğinde bildirim e-postası
 */
function notifyNewMessage(int $receiverId, string $senderName): void
{
    $db = getDB();
    $stmt = $db->prepare("SELECT email, name FROM users WHERE id = ?");
    $stmt->execute([$receiverId]);
    $receiver = $stmt->fetch();
    if (!$receiver) return;

    $body = "
        <p>Merhaba <strong>{$receiver['name']}</strong>,</p>
        <p>💬 <strong>{$senderName}</strong> size yeni bir mesaj gönderdi.</p>
        <p>Mesajınızı okumak ve yanıtlamak için aşağıdaki butona tıklayın.</p>
    ";
    
    sendMail($receiver['email'], "Yeni Mesaj: {$senderName}", $body, APP_URL . "/messages", "Mesajları Aç →");
}

/**
 * İş tamamlandığında değerlendirme bildirimi
 */
function notifyJobCompleted(int $workerId, string $listingTitle, int $rating): void
{
    $db = getDB();
    $stmt = $db->prepare("SELECT email, name FROM users WHERE id = ?");
    $stmt->execute([$workerId]);
    $worker = $stmt->fetch();
    if (!$worker) return;

    $stars = str_repeat('⭐', $rating);
    $body = "
        <p>Merhaba <strong>{$worker['name']}</strong>,</p>
        <p>✅ <strong>\"{$listingTitle}\"</strong> işi tamamlandı ve size bir değerlendirme yapıldı!</p>
        <div style='background:#fefce8;border:1px solid #fde68a;border-radius:12px;padding:16px;margin:16px 0;text-align:center;'>
            <div style='font-size:1.5rem;'>{$stars}</div>
            <div style='font-weight:600;color:#92400e;margin-top:4px;'>{$rating}/5 Puan</div>
        </div>
    ";
    
    sendMail($worker['email'], "İş Tamamlandı & Değerlendirme", $body, APP_URL . "/profile", "Profilimi Gör →");
}
