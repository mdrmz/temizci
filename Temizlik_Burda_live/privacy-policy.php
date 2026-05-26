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
        content="Privacy Policy for the Temizci Burada mobile application and website.">
    <link rel="canonical" href="<?= e($baseUrl . '/privacy-policy') ?>">
    <link rel="stylesheet" href="assets/css/style.css?v=5.9">
    <link rel="icon" href="/logo.png" type="image/png">
    <style>
        body {
            background: #f7f1e8;
        }

        .policy-page {
            max-width: 860px;
            margin: 72px auto;
            padding: 0 24px 80px;
            font-family: Arial, sans-serif;
            color: #1e2932;
        }

        .policy-page h1 {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .policy-page h2 {
            font-size: 20px;
            margin: 32px 0 10px;
        }

        .policy-page p,
        .policy-page li {
            font-size: 16px;
            line-height: 1.75;
        }

        .policy-box {
            background: #ffffff;
            border: 1px solid #dfd4c4;
            border-left: 4px solid #183b4e;
            border-radius: 8px;
            padding: 18px;
            margin: 24px 0;
        }

        .muted {
            color: #667085;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <main class="policy-page">
        <h1>Privacy Policy</h1>
        <p class="muted">Last updated: <?= date('F d, Y') ?></p>

        <div class="policy-box">
            This Privacy Policy applies to the Temizci Burada mobile application and the temizciburada.com website.
            It explains what personal data we collect, why we collect it, how we use it, how we share it, how long we
            keep it, and how users can request access, correction, or deletion.
        </div>

        <h2>1. Contact</h2>
        <p>
            Temizci Burada connects users who request home services with service providers. For privacy questions or
            data requests, contact us at: kvkk [at] temizciburada.com.
        </p>

        <h2>2. Personal Data We Collect</h2>
        <ul>
            <li>Identity data: first name and last name.</li>
            <li>Contact data: email address and phone number.</li>
            <li>Account data: user role, profile photo, preferences, login records, and session data.</li>
            <li>Service data: listings, service requests, offers, messages, support tickets, and user-submitted descriptions.</li>
            <li>Location and address data: city, district, and address information entered by the user for service delivery.</li>
            <li>Technical data: IP address, device or browser information, security logs, and fraud prevention records.</li>
        </ul>

        <h2>3. Why We Use Personal Data</h2>
        <ul>
            <li>To create, authenticate, and manage user accounts.</li>
            <li>To provide service requests, listings, offers, messaging, and support features.</li>
            <li>To help users and service providers communicate about a requested service.</li>
            <li>To protect the app and website against fraud, abuse, unauthorized access, and security incidents.</li>
            <li>To maintain, improve, and operate the app and website.</li>
            <li>To comply with legal obligations and resolve disputes.</li>
        </ul>

        <h2>4. Sharing</h2>
        <p>
            We do not sell personal data. We may share personal data only when necessary to provide the service,
            comply with law, respond to valid legal requests, prevent fraud, protect users, or provide customer support.
            For example, information required for a service request may be shared between the requesting user and the
            relevant service provider.
        </p>

        <h2>5. Third-Party Services</h2>
        <p>
            We may use technical service providers for hosting, security, email delivery, analytics, maintenance, or
            similar operational services. These providers process data only as needed to provide their services to
            Temizci Burada. We do not use personal data for behavioral advertising profiling, third-party advertising
            cookies, or data sale practices.
        </p>

        <h2>6. Cookies</h2>
        <p>
            The platform uses only necessary cookies for session management, security, and core functionality.
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

        <h2>11. Changes</h2>
        <p>
            We may update this Privacy Policy when our services or legal requirements change. The current version is
            always published on this page.
        </p>
    </main>
</body>

</html>
