<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$baseUrl = canonicalBaseUrl();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KVKK Aydınlatma Metni | Temizci Burada</title>
    <meta name="description"
        content="Temizci Burada KVKK Aydınlatma Metni; kişisel verilerin işlenmesi, saklanması, aktarılması ve kullanıcı hakları hakkında bilgi.">
    <link rel="canonical" href="<?= e($baseUrl . '/kvkk-aydinlatma') ?>">
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
            <a href="index">Ana Sayfa</a> / KVKK Aydınlatma Metni
        </div>

        <h1>KVKK Aydınlatma Metni</h1>
        <div class="update-date">Son güncelleme: <?= date('d.m.Y') ?></div>

        <div class="highlight-box">
            <strong>Temizci Burada</strong> olarak, 6698 sayılı Kişisel Verilerin Korunması Kanunu kapsamında
            kişisel verilerinizi hukuka uygun, şeffaf ve güvenli şekilde işlemeye özen gösteriyoruz.
        </div>

        <h2>1. Veri Sorumlusu</h2>
        <p>
            Temizci Burada platformu, kişisel verileriniz bakımından veri sorumlusu sıfatıyla hareket eder.
            Sorularınız ve hak talepleriniz için bizimle
            <a href="mailto:kvkk@temizciburada.com">kvkk@temizciburada.com</a> adresinden iletişime geçebilirsiniz.
        </p>

        <h2>2. İşlenen Kişisel Veriler</h2>
        <ul>
            <li><strong>Kimlik bilgileri:</strong> Ad ve soyad.</li>
            <li><strong>İletişim bilgileri:</strong> E-posta adresi ve telefon numarası.</li>
            <li><strong>Hesap bilgileri:</strong> Kullanıcı rolü, profil fotoğrafı, hesap tercihleri.</li>
            <li><strong>Hizmet bilgileri:</strong> İlanlar, hizmet talepleri, teklifler, mesajlaşma ve destek kayıtları.</li>
            <li><strong>Konum/adres bilgileri:</strong> Hizmetin sunulacağı şehir, ilçe ve kullanıcı tarafından girilen adres bilgileri.</li>
            <li><strong>Teknik veriler:</strong> IP adresi, oturum bilgileri, cihaz/tarayıcı bilgileri ve güvenlik kayıtları.</li>
        </ul>

        <h2>3. Kişisel Verilerin İşlenme Amaçları</h2>
        <ul>
            <li>Hesap oluşturma, giriş yapma ve kullanıcı kimliğini doğrulama.</li>
            <li>İlan, teklif ve hizmet talebi süreçlerini yürütme.</li>
            <li>Kullanıcılar arasındaki iletişimi kolaylaştırma.</li>
            <li>Destek taleplerini yanıtlamak ve hizmet kalitesini artırmak.</li>
            <li>Platform güvenliğini sağlamak, kötüye kullanım ve dolandırıcılığı önlemek.</li>
            <li>Yasal yükümlülükleri yerine getirmek ve uyuşmazlık süreçlerini yönetmek.</li>
        </ul>

        <h2>4. Kişisel Verilerin Aktarımı</h2>
        <p>
            Kişisel verileriniz satılmaz. Verileriniz yalnızca hizmetin sunulması için gerekli olduğu ölçüde ilgili
            kullanıcılarla, teknik hizmet sağlayıcılarla, yetkili kamu kurumlarıyla veya yasal zorunluluklar kapsamında
            paylaşılabilir.
        </p>

        <h2>5. Saklama Süresi</h2>
        <ul>
            <li>Hesabınız aktif olduğu sürece hesap ve işlem verileriniz saklanabilir.</li>
            <li>Hesap silme talebinden sonra kişisel verileriniz, yasal saklama yükümlülükleri saklı kalmak üzere en geç 30 gün içinde silinir veya anonim hale getirilir.</li>
            <li>Yasal yükümlülük, güvenlik, dolandırıcılık önleme veya uyuşmazlık çözümü için gerekli kayıtlar ilgili mevzuat süresince saklanabilir.</li>
        </ul>

        <h2>6. KVKK Kapsamındaki Haklarınız</h2>
        <p>KVKK'nın 11. maddesi kapsamında aşağıdaki haklara sahipsiniz:</p>
        <ul>
            <li>Kişisel verilerinizin işlenip işlenmediğini öğrenme.</li>
            <li>İşlenen kişisel verilerinize ilişkin bilgi talep etme.</li>
            <li>Eksik veya yanlış işlenen verilerin düzeltilmesini isteme.</li>
            <li>Verilerinizin silinmesini veya yok edilmesini talep etme.</li>
            <li>Verilerinizin aktarıldığı üçüncü kişileri öğrenme.</li>
            <li>İşleme faaliyetine mevzuat kapsamında itiraz etme.</li>
        </ul>

        <div class="highlight-box">
            Haklarınızı kullanmak için
            <a href="mailto:kvkk@temizciburada.com">kvkk@temizciburada.com</a> adresine e-posta gönderebilirsiniz.
            Talebiniz mevzuatta öngörülen süreler içinde yanıtlanır.
        </div>

        <h2>7. Güvenlik Önlemleri</h2>
        <p>
            Kişisel verilerin korunması için HTTPS, erişim kontrolü, parola güvenliği, oturum güvenliği, CSRF koruması,
            loglama ve yetkisiz erişimi önlemeye yönelik teknik/idari tedbirler uygulanır.
        </p>

        <h2>8. Çerezler</h2>
        <p>
            Platform yalnızca oturum yönetimi, güvenlik ve temel işlevsellik için zorunlu çerezler kullanır.
            Detaylı bilgi için <a href="cerez-politikasi">Çerez Politikası</a> sayfasını inceleyebilirsiniz.
        </p>

        <h2>9. Değişiklikler</h2>
        <p>
            Bu aydınlatma metni, mevzuat veya hizmet süreçlerindeki değişikliklere göre güncellenebilir.
            Güncel metin her zaman bu sayfada yayımlanır.
        </p>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>

</html>
