<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$baseUrl = canonicalBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy | Temizci Burada</title>
    <meta name="description"
        content="Privacy Policy for the Temizci Burada mobile app and website. This page explains collected data, use, sharing, retention, deletion, children privacy, and user rights.">
    <link rel="canonical" href="<?= e($baseUrl . '/kvkk') ?>">
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

        .highlight-box {
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

    <main class="legal-page">
        <div class="legal-breadcrumb">
            <a href="index">Home</a> / Privacy Policy
        </div>

        <h1>Privacy Policy</h1>
        <div class="update-date">Last updated: <?= date('F d, Y') ?></div>

        <div class="highlight-box">
            This Privacy Policy applies to the <strong>Temizci Burada</strong> mobile application and
            <strong>temizciburada.com</strong> website. It explains what personal data we collect, why we collect it,
            how we use it, how we share it, how long we keep it, and how users can request access, correction, or deletion.
        </div>

        <h2>1. Data Controller and Contact</h2>
        <p>
            Temizci Burada is a platform that connects users requesting home services with service providers.
            For privacy questions or data requests, contact us at: kvkk [at] temizciburada.com.
        </p>

        <h2>2. Personal Data We Collect</h2>
        <ul>
            <li><strong>Identity data:</strong> first name and last name.</li>
            <li><strong>Contact data:</strong> email address and phone number.</li>
            <li><strong>Account data:</strong> user role, profile photo, preferences, login records, and session data.</li>
            <li><strong>Service data:</strong> listings, service requests, offers, messages, support tickets, and user-submitted descriptions.</li>
            <li><strong>Location and address data:</strong> city, district, and address information entered by the user for service delivery.</li>
            <li><strong>Technical data:</strong> IP address, device/browser information, security logs, and fraud prevention records.</li>
        </ul>

        <h2>3. How We Use Personal Data</h2>
        <ul>
            <li>To create, authenticate, and manage user accounts.</li>
            <li>To provide listings, service requests, offers, messaging, and support features.</li>
            <li>To help users and service providers communicate about a requested service.</li>
            <li>To protect the app and website against abuse, fraud, unauthorized access, and security incidents.</li>
            <li>To maintain, improve, and operate the app and website.</li>
            <li>To comply with legal obligations and resolve disputes.</li>
        </ul>

        <h2>4. Sharing and Disclosure</h2>
        <p>
            We do not sell personal data. We may share personal data only when necessary to provide the service,
            comply with law, respond to valid legal requests, prevent fraud, protect users, or provide customer support.
            For example, information required for a service request may be shared between the requesting user and the
            relevant service provider.
        </p>

        <h2>5. Third-Party Services and SDKs</h2>
        <p>
            We may use technical service providers for hosting, security, email delivery, analytics, maintenance, or
            similar operational services. These providers process data only as needed to provide their services to
            Temizci Burada. We do not use personal data for behavioral advertising profiling, third-party advertising
            cookies, or data sale practices.
        </p>

        <h2>6. Cookies and Similar Technologies</h2>
        <p>
            The platform uses only necessary cookies for session management, security, and core functionality.
            More information is available in our <a href="cerez-politikasi">Cookie Policy</a>.
        </p>

        <h2>7. Data Retention and Deletion</h2>
        <ul>
            <li>Account and transaction data may be stored while the account remains active.</li>
            <li>After an account deletion request, personal data is deleted or anonymized within 30 days unless retention is legally required.</li>
            <li>Records required for legal compliance, security, fraud prevention, or dispute resolution may be retained for the applicable legal period.</li>
        </ul>

        <h2>8. User Rights</h2>
        <p>
            Users may request access, correction, deletion, restriction, objection, and information about data processing.
            Requests can be sent to kvkk [at] temizciburada.com and will be reviewed within the legally required period.
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

        <h2>11. Changes to This Policy</h2>
        <p>
            We may update this Privacy Policy when our services or legal requirements change. The current version is
            always published on this page.
        </p>

        <div
            style="margin-top:48px;padding-top:24px;border-top:1px solid var(--border);font-size:0.85rem;color:var(--text-muted);">
            <?= date('Y') ?> Temizci Burada Â· <a href="privacy">Privacy Policy</a> Â·
            <a href="kvkk-aydinlatma">KVKK Aydinlatma Metni</a> Â· <a href="cerez-politikasi">Cookie Policy</a>
        </div>
    </main>
</body>

</html>
