<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
if (session_status() === PHP_SESSION_NONE)
    session_start();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KVKK — Kişisel Verilerin Korunması | Temizci Burada</title>
    <meta name="description"
        content="Temizci Burada KVKK kapsamında kişisel verilerin korunması politikası, veri işleme amaçları ve haklarınız hakkında bilgi.">
    <link rel="canonical" href="https://www.temizciburada.com/kvkk">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="assets/css/style.css?v=4.0">
    <link rel="stylesheet" href="assets/css/dark-mode.css">
    <style>
        .legal-page {
            max-width: 820px;
            margin: 80px auto;
            padding: 0 24px 80px;
        }

        .legal-page h1 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .legal-page .update-date {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 40px;
        }

        .legal-page h2 {
            font-size: 1.15rem;
            font-weight: 700;
            margin: 32px 0 10px;
            color: var(--primary);
        }

        .legal-page p,
        .legal-page li {
            font-size: 0.95rem;
            line-height: 1.8;
            color: var(--text-secondary);
            margin-bottom: 10px;
        }

        .legal-page ul {
            padding-left: 20px;
        }

        .legal-page .highlight-box {
            background: #f0f4ff;
            border-left: 4px solid var(--primary);
            border-radius: 8px;
            padding: 16px 20px;
            margin: 20px 0;
        }

        .legal-breadcrumb {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .legal-breadcrumb a {
            color: var(--primary);
            text-decoration: none;
        }
    </style>

    <!-- SEO & Favicon -->
    <link rel="icon" href="/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="/logo.png">
    <meta property="og:image" content="https://www.temizciburada.com/logo.png">
</head>

<body>
    <nav class="navbar scrolled" style="background:rgba(255,255,255,0.97);">
        <div class="navbar-inner container">
            <a href="index" class="navbar-logo">
                <div class="logo-icon">🧹</div>
                <span class="navbar-logo-text"><span>Temizci Burada</span></span>
            </a>
        </div>
    </nav>

    <div class="legal-page">
        <div class="legal-breadcrumb">
            <a href="index">Ana Sayfa</a> / KVKK Aydınlatma Metni
        </div>

        <h1>🔒 KVKK Aydınlatma Metni</h1>
        <div class="update-date">Son güncelleme:
            <?= date('d.m.Y') ?>
        </div>

        <div class="highlight-box">
            <strong>Temizci Burada</strong> olarak, 6698 sayılı Kişisel Verilerin Korunması Kanunu (KVKK) kapsamında
            kişisel verilerinizi işlerken şeffaflığı esas alıyoruz.
        </div>

        <h2>1. Veri Sorumlusu</h2>
        <p>
            Temizci Burada platformu, kişisel verileriniz bakımından veri sorumlusudur.<br>
            <strong>E-posta:</strong> <a href="mailto:kvkk@temizciburada.com">kvkk@temizciburada.com</a>
        </p>

        <h2>2. İşlenen Kişisel Veriler</h2>
        <ul>
            <li><strong>Kimlik Bilgileri:</strong> Ad, soyad</li>
            <li><strong>İletişim Bilgileri:</strong> E-posta adresi, telefon numarası</li>
            <li><strong>Konum Bilgisi:</strong> Şehir / ilçe (ilan adresi)</li>
            <li><strong>Hesap Bilgileri:</strong> Kullanıcı rolü, profil fotoğrafı</li>
            <li><strong>İşlem Bilgileri:</strong> Oluşturulan ilanlar, verilen teklifler</li>
            <li><strong>Teknik Veriler:</strong> IP adresi, oturum bilgileri (güvenlik amaçlı)</li>
        </ul>

        <h2>3. Kişisel Verilerin İşlenme Amaçları</h2>
        <ul>
            <li>Hesap oluşturma ve kimlik doğrulama</li>
            <li>İlan ve teklif hizmetlerinin sunulması</li>
            <li>Kullanıcılar arası iletişimin kolaylaştırılması</li>
            <li>Güvenlik ve dolandırıcılık önleme (siber güvenlik — KVKK m.5/2-ç)</li>
            <li>Yasal yükümlülüklerin yerine getirilmesi</li>
        </ul>

        <h2>4. Kişisel Verilerin Aktarımı</h2>
        <p>
            Kişisel verileriniz; yasal zorunluluklar ve platform hizmetinin sunulması dışında
            üçüncü taraflarla <strong>paylaşılmamaktadır</strong>. Sunucu altyapısı Türkiye'de
            veya KVKK'ya uygun ülkelerde konumlandırılmaktadır.
        </p>

        <h2>5. Kişisel Verilerin Saklanma Süresi</h2>
        <ul>
            <li>Hesap aktifken: tüm veriler saklanır</li>
            <li>Hesap silme talebinden sonra: <strong>30 gün</strong> içinde silinir</li>
            <li>Yasal yükümlülük gerektiren veriler: ilgili mevzuat süresince saklanır</li>
        </ul>

        <h2>6. KVKK Kapsamındaki Haklarınız</h2>
        <p>KVKK'nın 11. maddesi kapsamında aşağıdaki haklara sahipsiniz:</p>
        <ul>
            <li>✅ Kişisel verilerinizin işlenip işlenmediğini <strong>öğrenme</strong></li>
            <li>✅ İşlenen verilerinize <strong>erişim</strong> talep etme</li>
            <li>✅ Hatalı verilerin <strong>düzeltilmesini</strong> isteme</li>
            <li>✅ Verilerinizin <strong>silinmesini</strong> talep etme</li>
            <li>✅ İşlemenin <strong>kısıtlanmasını</strong> isteme</li>
            <li>✅ Verilerin <strong>taşınabilirliği</strong>ni talep etme</li>
            <li>✅ Otomatik kararlar dahil işlemeye <strong>itiraz</strong> etme</li>
        </ul>

        <div class="highlight-box">
            <strong>Hak kullanımı için:</strong>
            <a href="mailto:kvkk@temizciburada.com">kvkk@temizciburada.com</a> adresine
            kimliğinizi doğrulayan bir e-posta gönderin. Talebiniz <strong>30 gün</strong> içinde yanıtlanır.
        </div>

        <h2>7. Güvenlik Önlemleri</h2>
        <ul>
            <li>Şifreler bcrypt ile şifrelenmektedir</li>
            <li>HTTPS / SSL zorunludur</li>
            <li>Giriş denemeleri izlenmekte, brute force saldırıları engellenmektedir</li>
            <li>CSRF token koruması uygulanmaktadır</li>
            <li>SQL injection'a karşı PDO prepared statements kullanılmaktadır</li>
        </ul>

        <h2>8. Çerezler</h2>
        <p>
            Platform yalnızca oturum yönetimi için zorunlu <strong>session cookie</strong> kullanmaktadır.
            Ayrıntılar için <a href="cerez-politikasi">Çerez Politikamızı</a> inceleyin.
        </p>

        <h2>9. Değişiklikler</h2>
        <p>
            Bu metin, mevzuat değişiklikleri veya platform güncellemeleri halinde revize edilebilir.
            Güncel metin her zaman bu sayfada yayınlanır.
        </p>

        <div
            style="margin-top:48px;padding-top:24px;border-top:1px solid var(--border);font-size:0.85rem;color:var(--text-muted);">
            ©
            <?= date('Y') ?> Temizci Burada · <a href="cerez-politikasi">Çerez Politikası</a>
        </div>
    </div>
</body>

</html>