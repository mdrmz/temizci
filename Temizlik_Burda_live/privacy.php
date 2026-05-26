<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
if (session_status() === PHP_SESSION_NONE)
    session_start();
$baseUrl = canonicalBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy | Temizci Burada</title>
    <meta name="description"
        content="Privacy Policy for the Temizci Burada mobile app and web platform, including collected data, usage, sharing, retention, deletion, and user rights.">
    <link rel="canonical" href="<?= e($baseUrl . '/privacy') ?>">
    <link rel="stylesheet" href="assets/css/style.css?v=5.9">
    <link rel="stylesheet" href="assets/css/dark-mode.css">
    <style>
        .legal-page {
            max-width: 900px;
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
            margin-bottom: 28px;
        }

        .legal-page h2 {
            font-size: 1.15rem;
            font-weight: 700;
            margin: 32px 0 10px;
            color: var(--primary);
        }

        .legal-page h3 {
            font-size: 1rem;
            font-weight: 700;
            margin: 24px 0 8px;
            color: var(--text-primary);
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

        .language-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 0 0 28px;
        }

        .language-nav a {
            border: 1px solid var(--border);
            border-radius: 999px;
            color: var(--primary);
            font-size: 0.85rem;
            font-weight: 700;
            padding: 7px 12px;
            text-decoration: none;
        }

        .language-note {
            color: var(--text-muted);
            font-size: 0.88rem;
            line-height: 1.7;
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
            <a href="index">Home</a> / Privacy Policy
        </div>

        <h1>Privacy Policy</h1>
        <div class="update-date">Last updated: <?= date('F d, Y') ?></div>

        <div class="language-nav" aria-label="Languages">
            <a href="#english">English</a>
            <a href="#turkish">Türkçe</a>
            <a href="#german">Deutsch</a>
            <a href="#arabic">العربية</a>
        </div>

        <p class="language-note">
            This page is the official privacy policy for the Temizci Burada mobile app and web platform. The English
            version is provided first for app store review and international users. Other language sections are provided
            for convenience.
        </p>

        <section id="english" lang="en">
            <div class="highlight-box">
                This Privacy Policy applies to the <strong>Temizci Burada</strong> mobile application and
                <strong>temizciburada.com</strong> web platform. It explains what personal data we collect, why we use it,
                how we share it, how long we keep it, and how users can request access, correction, or deletion.
            </div>

            <h2>1. Data Controller and Contact</h2>
            <p>
                Temizci Burada is a platform that connects users requesting home services with service providers.
                For privacy questions or user data requests, contact us at
                <a href="mailto:kvkk@temizciburada.com">kvkk@temizciburada.com</a>.
            </p>

            <h2>2. Personal Data We Collect</h2>
            <ul>
                <li><strong>Identity data:</strong> first name and last name.</li>
                <li><strong>Contact data:</strong> email address and phone number.</li>
                <li><strong>Account data:</strong> user role, profile photo, account preferences, and login/session data.</li>
                <li><strong>Service and listing data:</strong> service requests, listings, offers, messages, and support requests.</li>
                <li><strong>Location data:</strong> city, district, and address information entered by the user for service delivery.</li>
                <li><strong>Technical data:</strong> IP address, browser/device information, security logs, and fraud prevention records.</li>
            </ul>

            <h2>3. How We Use Personal Data</h2>
            <ul>
                <li>To create and manage user accounts.</li>
                <li>To provide listings, offers, service requests, support, and platform communication.</li>
                <li>To help users and service providers contact each other about a requested service.</li>
                <li>To protect the platform against abuse, fraud, unauthorized access, and security incidents.</li>
                <li>To comply with legal obligations and resolve disputes.</li>
                <li>To maintain, improve, and operate the app and website.</li>
            </ul>

            <h2>4. Sharing and Disclosure</h2>
            <p>
                We do not sell personal data. We may share only the data required to operate the service, comply with
                law, respond to valid legal requests, prevent fraud, or provide user support. For example, information
                necessary for a service request may be shared between the requesting user and the relevant service
                provider.
            </p>

            <h2>5. Third-Party Services and SDKs</h2>
            <p>
                We may use technical service providers for hosting, security, email delivery, analytics, or maintenance.
                These providers process data only as needed to provide their services to Temizci Burada. We do not use
                behavioral advertising profiling, third-party advertising cookies, or data sale practices.
            </p>

            <h2>6. Cookies and Similar Technologies</h2>
            <p>
                The platform uses only necessary cookies for session management, security, and core functionality. More
                information is available in our <a href="cerez-politikasi">Cookie Policy</a>.
            </p>

            <h2>7. Data Retention and Deletion</h2>
            <ul>
                <li>Account and transaction data may be stored while the account remains active.</li>
                <li>After an account deletion request, personal data is deleted or anonymized within 30 days unless retention is legally required.</li>
                <li>Records required for legal compliance, security, fraud prevention, or dispute resolution may be retained for the applicable legal period.</li>
            </ul>

            <h2>8. User Rights</h2>
            <p>
                Users may request access, correction, deletion, restriction, objection, and information about data
                processing by emailing <a href="mailto:kvkk@temizciburada.com">kvkk@temizciburada.com</a>. Requests are
                reviewed and answered within the legally required period.
            </p>

            <h2>9. Children</h2>
            <p>
                Temizci Burada is not directed to children. Users under 18 are not intended to create accounts or submit
                service requests on their own.
            </p>

            <h2>10. Security</h2>
            <p>
                We use technical and administrative safeguards such as HTTPS, access controls, password hashing, session
                security, CSRF protection, logging, and measures against unauthorized access.
            </p>

            <h2>11. Changes</h2>
            <p>
                We may update this Privacy Policy when our services or legal requirements change. The current version is
                always published on this page.
            </p>
        </section>

        <section id="turkish" lang="tr">
            <h2>Türkçe Gizlilik Politikası Özeti</h2>
            <p>
                Bu politika, Temizci Burada mobil uygulaması ve temizciburada.com web platformu için geçerlidir.
                Ad, soyad, e-posta, telefon, kullanıcı rolü, profil bilgileri, ilan/talep/teklif bilgileri, hizmet
                adresi bilgileri, IP adresi ve güvenlik kayıtları gibi veriler hizmetin sunulması, hesap yönetimi,
                kullanıcı iletişimi, güvenlik, destek ve yasal yükümlülükler için işlenir.
            </p>
            <p>
                Kişisel veriler satılmaz. Hizmetin kurulabilmesi için gerekli bilgiler ilgili kullanıcılarla, teknik
                hizmet sağlayıcılarla veya yasal zorunluluklar kapsamında yetkili kurumlarla paylaşılabilir. Hesap silme
                talebinden sonra kişisel veriler, yasal saklama yükümlülükleri saklı kalmak üzere en geç 30 gün içinde
                silinir veya anonim hale getirilir. Talepleriniz için
                <a href="mailto:kvkk@temizciburada.com">kvkk@temizciburada.com</a> adresine yazabilirsiniz.
            </p>
        </section>

        <section id="german" lang="de">
            <h2>Kurze Datenschutzerklärung auf Deutsch</h2>
            <p>
                Diese Datenschutzerklärung gilt für die mobile App Temizci Burada und die Website temizciburada.com.
                Wir verarbeiten personenbezogene Daten wie Name, E-Mail, Telefonnummer, Kontoinformationen,
                Serviceanfragen, Angebote, Standort-/Adressangaben und technische Sicherheitsdaten, um den Dienst
                bereitzustellen, Nutzerkonten zu verwalten, Sicherheit zu gewährleisten, Support zu leisten und
                gesetzliche Pflichten zu erfüllen.
            </p>
            <p>
                Wir verkaufen keine personenbezogenen Daten. Daten können nur soweit erforderlich mit beteiligten Nutzern,
                technischen Dienstleistern oder zuständigen Behörden geteilt werden. Lösch- und Auskunftsanfragen können
                an <a href="mailto:kvkk@temizciburada.com">kvkk@temizciburada.com</a> gesendet werden.
            </p>
        </section>

        <section id="arabic" lang="ar" dir="rtl">
            <h2>ملخص سياسة الخصوصية باللغة العربية</h2>
            <p>
                تنطبق سياسة الخصوصية هذه على تطبيق Temizci Burada للهاتف المحمول وعلى موقع temizciburada.com. نقوم
                بمعالجة بيانات مثل الاسم، البريد الإلكتروني، رقم الهاتف، معلومات الحساب، طلبات الخدمة، العروض، معلومات
                الموقع أو العنوان، والبيانات التقنية الخاصة بالأمان من أجل تشغيل الخدمة، إدارة الحسابات، تقديم الدعم،
                حماية المنصة، والامتثال للالتزامات القانونية.
            </p>
            <p>
                لا نبيع البيانات الشخصية. قد تتم مشاركة البيانات فقط عند الحاجة لتقديم الخدمة، مع مزودي الخدمات التقنية،
                أو عند وجود التزام قانوني. يمكن إرسال طلبات الوصول أو التصحيح أو الحذف إلى
                <a href="mailto:kvkk@temizciburada.com">kvkk@temizciburada.com</a>.
            </p>
        </section>

    </div>
    <?php include 'includes/footer.php'; ?>
</body>

</html>
