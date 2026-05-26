<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
if (session_status() === PHP_SESSION_NONE)
    session_start();
$baseUrl = canonicalBaseUrl();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gizlilik Politikası | Temizci Burada</title>
    <meta name="description"
        content="Temizci Burada mobil uygulaması ve web platformu için gizlilik politikası; toplanan veriler, kullanım amaçları, paylaşım, saklama ve kullanıcı hakları.">
    <link rel="canonical" href="<?= e($baseUrl . '/gizlilik-politikasi') ?>">
    <link rel="stylesheet" href="assets/css/style.css?v=5.9">
    <link rel="stylesheet" href="assets/css/dark-mode.css">
    <style>
        .legal-page {
            max-width: 860px;
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

    <link rel="icon" href="/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="/logo.png">
    <meta property="og:image" content="<?= e($baseUrl . '/logo.png') ?>">
</head>

<body class="public-page">
    <nav class="navbar scrolled">
        <div class="navbar-inner container">
            <a href="index" class="navbar-logo">
                <div class="logo-icon">
                    <img src="logo.png" alt="Temizci Burada">
                </div>
                <span class="navbar-logo-text"><span>Temizci Burada</span></span>
            </a>
        </div>
    </nav>

    <div class="legal-page">
        <div class="legal-breadcrumb">
            <a href="index">Ana Sayfa</a> / Gizlilik Politikası
        </div>

        <h1>Gizlilik Politikası</h1>
        <div class="update-date">Son güncelleme:
            <?= date('d.m.Y') ?>
        </div>

        <div class="highlight-box">
            Bu Gizlilik Politikası, <strong>Temizci Burada</strong> mobil uygulaması ve
            <strong>temizciburada.com</strong> web platformu için geçerlidir. Bu metin, kullanıcı verilerinin hangi
            amaçlarla toplandığını, kullanıldığını, paylaşıldığını, korunduğunu ve nasıl silinebileceğini açıklar.
        </div>

        <h2>1. Kapsam ve Veri Sorumlusu</h2>
        <p>
            Temizci Burada, ev hizmeti talep eden kullanıcılar ile hizmet verenleri buluşturan bir platformdur.
            Kişisel verileriniz bakımından veri sorumlusu Temizci Burada platformudur.
        </p>
        <p>
            <strong>İletişim:</strong> <a href="mailto:kvkk@temizciburada.com">kvkk@temizciburada.com</a>
        </p>

        <h2>2. Toplanan Kişisel Veriler</h2>
        <p>Platformu kullanırken aşağıdaki veri kategorileri işlenebilir:</p>
        <ul>
            <li><strong>Kimlik bilgileri:</strong> Ad, soyad.</li>
            <li><strong>İletişim bilgileri:</strong> E-posta adresi, telefon numarası.</li>
            <li><strong>Hesap bilgileri:</strong> Kullanıcı rolü, profil fotoğrafı, hesap tercihleri.</li>
            <li><strong>İlan ve talep bilgileri:</strong> Oluşturulan ilanlar, hizmet talepleri, verilen teklifler ve mesajlaşma/işlem kayıtları.</li>
            <li><strong>Konum bilgileri:</strong> Hizmetin sunulacağı şehir, ilçe ve kullanıcı tarafından girilen adres bilgileri.</li>
            <li><strong>Teknik bilgiler:</strong> IP adresi, cihaz/tarayıcı bilgisi, oturum ve güvenlik kayıtları.</li>
            <li><strong>Destek bilgileri:</strong> Destek talepleri, bildirimler ve kullanıcı tarafından iletilen açıklamalar.</li>
        </ul>

        <h2>3. Verilerin Kullanım Amaçları</h2>
        <p>Kişisel verileriniz aşağıdaki amaçlarla kullanılır:</p>
        <ul>
            <li>Hesap oluşturma, giriş yapma ve kullanıcı kimliğini doğrulama.</li>
            <li>Hizmet taleplerinin, ilanların, tekliflerin ve platform içi iletişimin yürütülmesi.</li>
            <li>Kullanıcı güvenliğini sağlama, kötüye kullanım ve dolandırıcılığı önleme.</li>
            <li>Destek taleplerini yanıtlamak ve kullanıcı deneyimini iyileştirmek.</li>
            <li>Yasal yükümlülükleri yerine getirmek ve uyuşmazlık halinde kayıtları korumak.</li>
            <li>Platformun teknik olarak çalışmasını, bakımını ve güvenliğini sağlamak.</li>
        </ul>

        <h2>4. Verilerin Paylaşımı</h2>
        <p>
            Kişisel verileriniz satılmaz. Veriler yalnızca platform hizmetinin sunulması, yasal yükümlülüklerin yerine
            getirilmesi, güvenlik süreçlerinin yürütülmesi veya yetkili kamu kurumlarından gelen geçerli taleplerin
            karşılanması için gerekli olduğu ölçüde paylaşılabilir.
        </p>
        <p>
            Hizmet talebi/ilan süreçlerinde, hizmetin kurulabilmesi için gerekli iletişim ve talep bilgileri ilgili
            kullanıcılarla sınırlı olarak paylaşılabilir.
        </p>

        <h2>5. Üçüncü Taraf Hizmetler ve SDK'lar</h2>
        <p>
            Platform; barındırma, güvenlik, e-posta gönderimi veya benzeri teknik hizmetler için hizmet sağlayıcılardan
            destek alabilir. Bu sağlayıcılar verileri yalnızca Temizci Burada adına ve hizmetin gerektirdiği ölçüde
            işler.
        </p>
        <p>
            Uygulamada reklam amaçlı kullanıcı takibi yapılmaz. Reklam çerezi, üçüncü taraf izleme çerezi veya davranışsal
            reklam profillemesi kullanılmaz.
        </p>

        <h2>6. Çerezler ve Benzer Teknolojiler</h2>
        <p>
            Platform yalnızca oturum yönetimi, güvenlik ve temel işlevsellik için zorunlu çerezler kullanır.
            Ayrıntılar için <a href="cerez-politikasi">Çerez Politikası</a> sayfasını inceleyebilirsiniz.
        </p>

        <h2>7. Veri Saklama ve Silme</h2>
        <ul>
            <li>Hesap aktif olduğu sürece hesap ve işlem verileri saklanabilir.</li>
            <li>Hesap silme talebi sonrası kişisel veriler, yasal saklama yükümlülükleri saklı kalmak üzere en geç 30 gün içinde silinir veya anonim hale getirilir.</li>
            <li>Yasal yükümlülük, güvenlik, dolandırıcılık önleme veya uyuşmazlık çözümü için tutulması gereken kayıtlar ilgili mevzuat süresince saklanabilir.</li>
        </ul>

        <h2>8. Kullanıcı Hakları</h2>
        <p>KVKK ve uygulanabilir mevzuat kapsamında aşağıdaki haklara sahipsiniz:</p>
        <ul>
            <li>Kişisel verilerinizin işlenip işlenmediğini öğrenme.</li>
            <li>İşlenen kişisel verilerinize erişim talep etme.</li>
            <li>Eksik veya hatalı verilerin düzeltilmesini isteme.</li>
            <li>Verilerinizin silinmesini veya anonim hale getirilmesini talep etme.</li>
            <li>İşleme faaliyetlerine itiraz etme.</li>
            <li>Verilerin aktarıldığı üçüncü kişiler hakkında bilgi talep etme.</li>
        </ul>

        <div class="highlight-box">
            Haklarınızı kullanmak veya gizlilik politikasıyla ilgili soru sormak için
            <a href="mailto:kvkk@temizciburada.com">kvkk@temizciburada.com</a> adresine e-posta gönderebilirsiniz.
            Talepleriniz mevzuatta öngörülen süreler içinde yanıtlanır.
        </div>

        <h2>9. Çocukların Gizliliği</h2>
        <p>
            Temizci Burada çocuklara yönelik bir uygulama değildir. Platform, 18 yaşından küçük kullanıcıların kendi
            başlarına hesap oluşturmasını veya hizmet talebi oluşturmasını hedeflemez.
        </p>

        <h2>10. Güvenlik</h2>
        <p>
            Kişisel verilerin korunması için HTTPS, erişim kontrolü, şifreleme, oturum güvenliği, CSRF koruması ve
            yetkisiz erişimi önlemeye yönelik teknik/idari tedbirler uygulanır.
        </p>

        <h2>11. Politika Değişiklikleri</h2>
        <p>
            Bu Gizlilik Politikası, hizmetlerde veya mevzuatta meydana gelen değişikliklere göre güncellenebilir.
            Güncel metin her zaman bu sayfada yayımlanır.
        </p>

    </div>
    <?php include 'includes/footer.php'; ?>
</body>

</html>

