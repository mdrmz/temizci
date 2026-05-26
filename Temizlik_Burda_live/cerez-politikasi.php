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
    <title>Ã‡erez PolitikasÄ± | Temizci Burada</title>
    <meta name="description"
        content="Temizci Burada Ã§erez politikasÄ±  -  hangi Ã§erezleri kullandÄ±ÄŸÄ±mÄ±z ve nasÄ±l yÃ¶netebileceÄŸiniz.">
    <link rel="canonical" href="<?= e($baseUrl . '/cerez-politikasi') ?>">
    <link rel="stylesheet" href="assets/css/style.css?v=5.9">
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
            <a href="index">Ana Sayfa</a> / Ã‡erez PolitikasÄ±
        </div>

        <h1> Ã‡erez PolitikasÄ±</h1>
        <div class="update-date">Son gÃ¼ncelleme:
            <?= date('d.m.Y') ?>
        </div>

        <h2>Ã‡erezler Nedir?</h2>
        <p>
            Ã‡erezler, web sitelerinin tarayÄ±cÄ±nÄ±za kaydettiÄŸi kÃ¼Ã§Ã¼k metin dosyalarÄ±dÄ±r.
            Oturumunuzun aÃ§Ä±k kalmasÄ±nÄ± saÄŸlamak ve siteyi iÅŸlevsel olarak sunabilmek iÃ§in kullanÄ±lÄ±rlar.
        </p>

        <h2>KullandÄ±ÄŸÄ±mÄ±z Ã‡erezler</h2>
        <table class="cookie-table">
            <thead>
                <tr>
                    <th>Ã‡erez AdÄ±</th>
                    <th>TÃ¼r</th>
                    <th>SÃ¼re</th>
                    <th>AmaÃ§</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>PHPSESSID</code></td>
                    <td><span class="badge-zorunlu">Zorunlu</span></td>
                    <td>Oturum</td>
                    <td>KullanÄ±cÄ± oturumunu aÃ§Ä±k tutar (giriÅŸ durumu)</td>
                </tr>
                <tr>
                    <td><code>_csrf_token</code></td>
                    <td><span class="badge-zorunlu">Zorunlu</span></td>
                    <td>Oturum</td>
                    <td>CSRF saldÄ±rÄ±larÄ±na karÅŸÄ± gÃ¼venlik token'Ä±</td>
                </tr>
                <tr>
                    <td><code>cookie_consent</code></td>
                    <td><span class="badge-zorunlu">Zorunlu</span></td>
                    <td>1 yÄ±l</td>
                    <td>Ã‡erez onay tercihini hatÄ±rlar</td>
                </tr>
            </tbody>
        </table>

        <p>
             Platformumuzda <strong>reklam Ã§erezi, izleme Ã§erezi veya Ã¼Ã§Ã¼ncÃ¼ taraf analitik Ã§erezi</strong>
            kullanÄ±lmamaktadÄ±r.
        </p>

        <h2>Zorunlu Ã‡erezler</h2>
        <p>
            YukarÄ±daki Ã§erezler platformun temel iÅŸlevselliÄŸi iÃ§in zorunludur.
            Bunlar olmadan giriÅŸ yapma ve platform kullanÄ±mÄ± mÃ¼mkÃ¼n olmaz.
            KVKK kapsamÄ±nda ayrÄ±ca onay gerektirmezler.
        </p>

        <h2>Ã‡erezleri NasÄ±l YÃ¶netebilirsiniz?</h2>
        <p>TarayÄ±cÄ± ayarlarÄ±ndan Ã§erezleri yÃ¶netebilirsiniz:</p>
        <ul>
            <li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener">Chrome</a></li>
            <li><a href="https://support.mozilla.org/tr/kb/firefox-cerezleri-nasil-yonetilir" target="_blank"
                    rel="noopener">Firefox</a></li>
            <li><a href="https://support.apple.com/tr-tr/guide/safari/sfri11471" target="_blank"
                    rel="noopener">Safari</a></li>
        </ul>
        <p>
            Ã‡erezleri devre dÄ±ÅŸÄ± bÄ±rakÄ±rsanÄ±z giriÅŸ yapma veya platform kullanÄ±mÄ±
            <strong>iÅŸlevselliÄŸini yitirebilir</strong>.
        </p>

        <h2>Ä°letiÅŸim</h2>
        <p>
            Ã‡erez politikamÄ±za iliÅŸkin sorularÄ±nÄ±z iÃ§in:
            <a href="mailto:kvkk@temizciburada.com">kvkk@temizciburada.com</a>
        </p>

        <div
            style="margin-top:48px;padding-top:24px;border-top:1px solid var(--border);font-size:0.85rem;color:var(--text-muted);">
            Â©
            <?= date('Y') ?> Temizci Burada Â· <a href="gizlilik-politikasi">Gizlilik PolitikasÄ±</a> Â· <a href="kvkk">KVKK AydÄ±nlatma Metni</a>
        </div>
    </div>
</body>

</html>


