<?php
defined('AWAN') or die();
require_once __DIR__ . '/_bootstrap.php';
$siteName  = $settings->siteName();
$devEmail  = $settings->get('developer_email', '');
$siteUrl   = $settings->get('site_url', '');
ob_start();
?>
<div class="cms-page">
    <div class="page-hero">
        <div class="page-hero-inner">
            <h1>Privacy Policy</h1>
            <p class="cms-page-meta">Last updated: <?= date('F j, Y') ?></p>
        </div>
    </div>
    <div class="front-container" style="padding-top:40px;padding-bottom:64px">
        <div class="cms-content" style="max-width:800px;margin:0 auto">
            <p>This Privacy Policy describes how <strong><?= e($siteName) ?></strong><?= $siteUrl ? ' (' . e($siteUrl) . ')' : '' ?> ("we", "us", or "our") collects, uses, and shares information about you when you use our platform.</p>

            <h2>1. Information We Collect</h2>
            <h3>Information You Provide</h3>
            <ul>
                <li><strong>Account Registration:</strong> Username, email address, and password when you register for an account.</li>
                <li><strong>Contact Forms:</strong> Name, email, and message content when you submit a contact, quote, or tool-request form.</li>
                <li><strong>Profile Updates:</strong> Any additional profile information you choose to provide (name, avatar, bio).</li>
                <li><strong>Newsletter:</strong> Email address if you subscribe to our newsletter.</li>
            </ul>
            <h3>Information Collected Automatically</h3>
            <ul>
                <li><strong>Log Data:</strong> IP address, browser type and version, pages visited, date/time of access, and referring URLs.</li>
                <li><strong>Analytics:</strong> Aggregated page-view counts and plugin-usage events (not linked to personally identifiable information unless you are signed in).</li>
                <li><strong>Cookies:</strong> Session cookies to maintain your login state. We do not use third-party advertising cookies.</li>
            </ul>

            <h2>2. How We Use Your Information</h2>
            <ul>
                <li>To provide, maintain, and improve the platform.</li>
                <li>To authenticate you and manage your account.</li>
                <li>To respond to your enquiries, requests, and support tickets.</li>
                <li>To send newsletters and platform updates (only if you have subscribed).</li>
                <li>To detect and prevent fraud, abuse, and security incidents.</li>
                <li>To comply with legal obligations.</li>
            </ul>

            <h2>3. Sharing of Information</h2>
            <p>We do not sell, trade, or rent your personal information to third parties. We may share information in the following limited circumstances:</p>
            <ul>
                <li><strong>Legal Requirements:</strong> We may disclose information if required by law, regulation, court order, or governmental authority.</li>
                <li><strong>Safety:</strong> To protect the rights, property, or safety of the platform, its users, or the public.</li>
                <li><strong>Consent:</strong> With your explicit consent in any other circumstances.</li>
            </ul>

            <h2>4. Data Retention</h2>
            <p>Account data is retained for as long as your account is active. System logs are automatically purged after 30 days. You may request deletion of your account and associated data at any time by contacting us.</p>

            <h2>5. Security</h2>
            <p>We implement appropriate technical and organisational security measures, including password hashing (bcrypt), HTTPS enforcement, CSRF protection, and rate limiting. However, no transmission or storage method is 100% secure, and we cannot guarantee absolute security.</p>

            <h2>6. Cookies</h2>
            <p>We use only strictly necessary session cookies to maintain your logged-in state. No marketing or cross-site tracking cookies are used. See our <a href="/cookie-policy">Cookie Policy</a> for full details.</p>

            <h2>7. Your Rights</h2>
            <p>Depending on your jurisdiction, you may have the right to:</p>
            <ul>
                <li>Access, correct, or delete your personal data.</li>
                <li>Withdraw consent at any time (where processing is based on consent).</li>
                <li>Object to processing or request restriction.</li>
                <li>Lodge a complaint with your local data protection authority.</li>
            </ul>

            <h2>8. Children's Privacy</h2>
            <p>The platform is not directed to children under the age of 13. We do not knowingly collect personal information from children.</p>

            <h2>9. Changes to This Policy</h2>
            <p>We may update this Privacy Policy from time to time. We will notify you of significant changes by updating the "Last updated" date above. Continued use of the platform after changes constitutes acceptance of the updated policy.</p>

            <h2>10. Contact</h2>
            <p>If you have any questions about this Privacy Policy or wish to exercise your rights, please contact us<?= $devEmail ? ' at <a href="mailto:' . e($devEmail) . '">' . e($devEmail) . '</a>' : '' ?> or via the <a href="/contact">contact form</a>.</p>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Privacy Policy', $content, ['description' => 'Read our Privacy Policy — how we collect, use, and protect your personal information.']);
