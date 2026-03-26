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
    <title>Çerez Politikası | Temizci Burada</title>
    <meta name="description"
        content="Temizci Burada çerez politikası  -  hangi çerezleri kullandığımız ve nasıl yönetebileceğiniz.">
    <link rel="canonical" href="https://www.temizciburada.com/cerez-politikasi">
    <link rel="stylesheet" href="assets/css/style.css?v=5.0">
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

        .cookie-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
            font-size: 0.88rem;
        }

        .cookie-table th {
            background: var(--primary);
            color: #fff;
            padding: 10px 14px;
            text-align: left;
        }

        .cookie-table td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--border);
        }

        .cookie-table tr:hover td {
            background: #f9f9ff;
        }

        .badge-zorunlu {
            background: #dcfce7;
            color: #166534;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
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
                <div class="logo-icon"></div>
                <span class="navbar-logo-text"><span>Temizci Burada</span></span>
            </a>
        </div>
    </nav>

    <div class="legal-page">
        <div class="legal-breadcrumb">
            <a href="index">Ana Sayfa</a> / Çerez Politikası
        </div>

        <h1> Çerez Politikası</h1>
        <div class="update-date">Son güncelleme:
            <?= date('d.m.Y') ?>
        </div>

        <h2>Çerezler Nedir?</h2>
        <p>
            Çerezler, web sitelerinin tarayıcınıza kaydettiği küçük metin dosyalarıdır.
            Oturumunuzun açık kalmasını sağlamak ve siteyi işlevsel olarak sunabilmek için kullanılırlar.
        </p>

        <h2>Kullandığımız Çerezler</h2>
        <table class="cookie-table">
            <thead>
                <tr>
                    <th>Çerez Adı</th>
                    <th>Tür</th>
                    <th>Süre</th>
                    <th>Amaç</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>PHPSESSID</code></td>
                    <td><span class="badge-zorunlu">Zorunlu</span></td>
                    <td>Oturum</td>
                    <td>Kullanıcı oturumunu açık tutar (giriş durumu)</td>
                </tr>
                <tr>
                    <td><code>_csrf_token</code></td>
                    <td><span class="badge-zorunlu">Zorunlu</span></td>
                    <td>Oturum</td>
                    <td>CSRF saldırılarına karşı güvenlik token'ı</td>
                </tr>
                <tr>
                    <td><code>cookie_consent</code></td>
                    <td><span class="badge-zorunlu">Zorunlu</span></td>
                    <td>1 yıl</td>
                    <td>Çerez onay tercihini hatırlar</td>
                </tr>
            </tbody>
        </table>

        <p>
             Platformumuzda <strong>reklam çerezi, izleme çerezi veya üçüncü taraf analitik çerezi</strong>
            kullanılmamaktadır.
        </p>

        <h2>Zorunlu Çerezler</h2>
        <p>
            Yukarıdaki çerezler platformun temel işlevselliği için zorunludur.
            Bunlar olmadan giriş yapma ve platform kullanımı mümkün olmaz.
            KVKK kapsamında ayrıca onay gerektirmezler.
        </p>

        <h2>Çerezleri Nasıl Yönetebilirsiniz?</h2>
        <p>Tarayıcı ayarlarından çerezleri yönetebilirsiniz:</p>
        <ul>
            <li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener">Chrome</a></li>
            <li><a href="https://support.mozilla.org/tr/kb/firefox-cerezleri-nasil-yonetilir" target="_blank"
                    rel="noopener">Firefox</a></li>
            <li><a href="https://support.apple.com/tr-tr/guide/safari/sfri11471" target="_blank"
                    rel="noopener">Safari</a></li>
        </ul>
        <p>
            Çerezleri devre dışı bırakırsanız giriş yapma veya platform kullanımı
            <strong>işlevselliğini yitirebilir</strong>.
        </p>

        <h2>İletişim</h2>
        <p>
            Çerez politikamıza ilişkin sorularınız için:
            <a href="mailto:kvkk@temizciburada.com">kvkk@temizciburada.com</a>
        </p>

        <div
            style="margin-top:48px;padding-top:24px;border-top:1px solid var(--border);font-size:0.85rem;color:var(--text-muted);">
            ©
            <?= date('Y') ?> Temizci Burada · <a href="kvkk">KVKK Aydınlatma Metni</a>
        </div>
    </div>
</body>

</html>


