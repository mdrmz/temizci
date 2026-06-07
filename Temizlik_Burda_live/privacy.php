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
        content="Privacy Policy for the Temizci Burada mobile app and related services operated by Piksel Analitik.">
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

    <main class="legal-page">
        <div class="legal-breadcrumb">
            <a href="index">Home</a> / Privacy Policy
        </div>

        <h1>Privacy Policy</h1>
        <div class="update-date">Effective date: 2026-06-07</div>

        <div class="highlight-box">
            This privacy policy applies to the Temizci Burada app for mobile devices, together with any related
            services operated by Piksel Analitik (collectively, the "Application"). Piksel Analitik is hereby referred
            to as the "Service Provider".
        </div>

        <h2>Information Collection and Use</h2>
        <p>The Application collects information when you download and use it. This information may include:</p>
        <ul>
            <li>Your device's Internet Protocol address.</li>
            <li>The pages of the Application that you visit, the time and date of your visit, and the time spent on those pages.</li>
            <li>The time spent on the Application.</li>
            <li>The mobile operating system you use.</li>
        </ul>

        <h2>Cookies and Tracking Technologies</h2>
        <p>
            The Application or its third-party SDKs may use cookies, SDKs, pixels, and similar technologies to support
            functionality, analytics, or service delivery. Where required by applicable law, the Service Provider will
            obtain consent before using non-essential tracking technologies.
        </p>

        <h2>Location Information</h2>
        <p>
            The Application collects your device's location to provide location-based features, improve the Application,
            and support related services.
        </p>
        <ul>
            <li>Geolocation Services: The Service Provider may use location data to provide location-based features or content.</li>
            <li>Analytics and Improvements: Aggregated location data may help the Service Provider understand usage patterns and improve performance.</li>
            <li>Third-Party Services: Location data may be shared with third-party services used to support Application functionality, subject to this privacy policy and applicable law.</li>
        </ul>

        <h2>Your Rights</h2>
        <p>
            You may request access to, correction of, or deletion of your personal data held by the Service Provider.
            To exercise these rights, or to withdraw consent where processing is based on consent, contact the Service
            Provider at <a href="mailto:mehmet.durmaz@pikselanalitik.com">mehmet.durmaz@pikselanalitik.com</a>.
        </p>

        <h2>Your California Privacy Rights (CCPA/CPRA)</h2>
        <p>
            If you are a California resident, you have the right to know what personal information is collected, the
            right to delete personal information, the right to opt out of the sale or sharing of personal information,
            and the right to non-discrimination for exercising these rights. To exercise your CCPA/CPRA rights, contact
            the Service Provider at
            <a href="mailto:mehmet.durmaz@pikselanalitik.com">mehmet.durmaz@pikselanalitik.com</a>.
        </p>

        <p>
            The Service Provider may use the information you provide to send important information, required notices,
            and, where permitted by law, marketing communications.
        </p>
        <p>
            For a better experience while using the Application, the Service Provider may require you to provide certain
            personally identifiable information, including but not limited to email, address, and phone number. The
            information the Service Provider requests will be retained and used as described in this privacy policy.
        </p>

        <h2>Third Party Access</h2>
        <p>
            Only aggregated, anonymized data is periodically transmitted to external services to aid the Service
            Provider in improving the Application and their service. The Service Provider may share your information
            with third parties in the ways that are described in this privacy statement.
        </p>

        <h2>International Data Transfers</h2>
        <p>
            The Service Provider or its third-party service providers may transfer personal data to countries outside
            your country of residence, including outside the European Economic Area (EEA). Where applicable law requires
            safeguards for international transfers, the Service Provider will use appropriate mechanisms.
        </p>
        <ul>
            <li>Standard Contractual Clauses (SCCs) approved by the European Commission.</li>
            <li>Adequacy decisions or other legally recognized transfer mechanisms.</li>
            <li>Your consent, where required and legally permitted.</li>
        </ul>
        <p>
            Data protection laws in other countries may differ from those in your jurisdiction. Where required by law,
            the Service Provider will apply appropriate safeguards and obtain any consent required for the transfer.
        </p>

        <h2>Third-Party Service Providers</h2>
        <p>
            Please note that the Application utilizes third-party services that have their own Privacy Policy about
            handling data. Below are the links to the Privacy Policy of the third-party service providers used by the
            Application:
        </p>
        <ul>
            <li><a href="https://www.google.com/policies/privacy/" rel="noopener noreferrer" target="_blank">Google Play Services</a></li>
        </ul>

        <p>The Service Provider may disclose User Provided and Automatically Collected Information:</p>
        <ul>
            <li>As required by law, such as to comply with a subpoena, or similar legal process.</li>
            <li>When they believe in good faith that disclosure is necessary to protect their rights, protect your safety or the safety of others, investigate fraud, or respond to a government request.</li>
            <li>With their trusted services providers who work on their behalf, do not have an independent use of the information the Service Provider discloses to them, and have agreed to adhere to the rules set forth in this privacy statement.</li>
        </ul>

        <h2>Opt-Out Rights</h2>
        <p>
            You can stop further collection of information from your mobile device by uninstalling the Application.
            Uninstalling will stop the Application from collecting data from your device, but it does not automatically
            delete information that has already been transmitted to the Service Provider or to third parties.
        </p>
        <p>
            To request deletion of your personal data, to withdraw consent, or to exercise any of your rights, contact
            the Service Provider at
            <a href="mailto:mehmet.durmaz@pikselanalitik.com">mehmet.durmaz@pikselanalitik.com</a>.
        </p>

        <h2>Data Retention Policy</h2>
        <p>The Service Provider retains personal data based on its necessity for the stated purposes:</p>
        <ul>
            <li>User Provided Data: Retained for the duration of your use of the Application plus 12 months thereafter, unless longer retention is required by law.</li>
            <li>Automatically Collected Data: Retained for up to 24 months from collection, unless longer retention is required for legal compliance.</li>
            <li>Aggregated and Anonymized Data: Retained indefinitely as it no longer identifies you.</li>
            <li>Data required for legal compliance: Retained as long as required by applicable law.</li>
        </ul>
        <p>
            You may request deletion of your personal data, subject to any legal obligation to retain it. If you want
            the Service Provider to delete User Provided Data submitted through the Application, please contact them at
            <a href="mailto:mehmet.durmaz@pikselanalitik.com">mehmet.durmaz@pikselanalitik.com</a>. Please note that
            some User Provided Data may be required for the Application to function properly.
        </p>

        <h2>Children</h2>
        <p>
            The Application is not intended for children under 16 years of age, or such higher age as required by
            applicable law. The Service Provider does not knowingly solicit data from children or market the Application
            to them.
        </p>
        <p>
            Where parental or guardian consent is required under applicable law, the Application is not intended for use
            without that consent. The Service Provider does not knowingly collect personally identifiable information
            from children under 16 years of age in violation of applicable law. In the event the Service Provider
            discovers that a child has provided personal information, the Service Provider will immediately delete this
            from their servers. If you are a parent or guardian and you are aware that your child has provided the
            Service Provider with personal information, please contact the Service Provider
            (<a href="mailto:mehmet.durmaz@pikselanalitik.com">mehmet.durmaz@pikselanalitik.com</a>) so that they will
            be able to take the necessary actions.
        </p>

        <h2>Security</h2>
        <p>
            The Service Provider is concerned about safeguarding the confidentiality of your information. The Service
            Provider provides physical, electronic, and procedural safeguards to protect information the Service
            Provider processes and maintains.
        </p>

        <h2>Data Breach Notification</h2>
        <p>
            If a data breach occurs that affects your personal data, the Service Provider will notify you in accordance
            with applicable legal requirements, including, where required, providing information about the nature of the
            breach and the steps being taken to address it.
        </p>

        <h2>Changes</h2>
        <p>
            The Service Provider may update this Privacy Policy from time to time. The Service Provider will notify you
            of material changes by posting the updated Privacy Policy with an effective date. Where required by law, the
            Service Provider will seek your consent to material changes before they take effect.
        </p>
        <p>
            Previous versions of this Privacy Policy will be maintained and made available upon request by contacting
            the Service Provider at
            <a href="mailto:mehmet.durmaz@pikselanalitik.com">mehmet.durmaz@pikselanalitik.com</a>.
        </p>

        <h2>Your Consent</h2>
        <p>
            Where processing is based on consent, you provide that consent by affirmatively opting in to the relevant
            feature or action. You may withdraw consent at any time without affecting processing carried out before
            withdrawal. Processing based on other lawful bases is carried out as described above.
        </p>

        <h2>Contact Us</h2>
        <p>
            If you have any questions regarding privacy while using the Application, or have questions about the
            practices, please contact the Service Provider via email at
            <a href="mailto:mehmet.durmaz@pikselanalitik.com">mehmet.durmaz@pikselanalitik.com</a>.
        </p>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>

</html>
