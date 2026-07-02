<?php
defined('AWAN') or die();
require_once __DIR__ . '/_bootstrap.php';
$siteName = $settings->siteName();
$devEmail = $settings->get('developer_email', '');
ob_start();
?>
<div class="cms-page">
    <div class="page-hero">
        <div class="page-hero-inner">
            <h1>Terms of Service</h1>
            <p class="cms-page-meta">Last updated: <?= date('F j, Y') ?></p>
        </div>
    </div>
    <div class="front-container" style="padding-top:40px;padding-bottom:64px">
        <div class="cms-content" style="max-width:800px;margin:0 auto">
            <p>By accessing or using <strong><?= e($siteName) ?></strong> ("the Platform"), you agree to be bound by these Terms of Service. Please read them carefully before using the Platform.</p>

            <h2>1. Acceptance of Terms</h2>
            <p>By creating an account or using any part of the Platform, you confirm that you are at least 13 years of age, agree to these Terms, and consent to our <a href="/privacy">Privacy Policy</a>.</p>

            <h2>2. Use of the Platform</h2>
            <p>You may use the Platform for lawful purposes only. You agree not to:</p>
            <ul>
                <li>Use the Platform in any way that violates applicable local, national, or international laws or regulations.</li>
                <li>Transmit unsolicited or unauthorised advertising or promotional material.</li>
                <li>Attempt to gain unauthorised access to any part of the Platform or its infrastructure.</li>
                <li>Use automated tools to scrape, crawl, or harvest data without permission.</li>
                <li>Upload or share content that is defamatory, obscene, illegal, or infringes third-party rights.</li>
            </ul>

            <h2>3. User Accounts</h2>
            <p>You are responsible for maintaining the confidentiality of your account credentials. You are responsible for all activities that occur under your account. Notify us immediately if you suspect unauthorised access to your account.</p>
            <p>We reserve the right to suspend or terminate accounts that violate these Terms or are found to be engaging in abusive or harmful behaviour.</p>

            <h2>4. Plugins and Third-Party Content</h2>
            <p>Plugins installed on the Platform may interact with third-party services. We are not responsible for the content, privacy practices, or reliability of third-party services. Use of third-party integrations within plugins is at your own risk.</p>

            <h2>5. Intellectual Property</h2>
            <p>The Platform software, design, and documentation are the intellectual property of the developer. You may not copy, modify, distribute, sell, or lease any part of the Platform without written permission, except as expressly permitted by an open-source licence applicable to specific components.</p>

            <h2>6. Disclaimers</h2>
            <p>THE PLATFORM IS PROVIDED "AS IS" WITHOUT WARRANTIES OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, OR NON-INFRINGEMENT. WE DO NOT WARRANT THAT THE PLATFORM WILL BE UNINTERRUPTED, ERROR-FREE, OR FREE OF HARMFUL COMPONENTS.</p>

            <h2>7. Limitation of Liability</h2>
            <p>TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW, THE DEVELOPER SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, OR ANY LOSS OF DATA, PROFITS, OR GOODWILL, ARISING FROM OR RELATED TO YOUR USE OF THE PLATFORM.</p>

            <h2>8. Modifications</h2>
            <p>We reserve the right to modify these Terms at any time. Changes take effect when posted. Continued use of the Platform after changes are posted constitutes acceptance of the revised Terms.</p>

            <h2>9. Governing Law</h2>
            <p>These Terms shall be governed by and construed in accordance with applicable law. Any disputes arising under these Terms shall be subject to the exclusive jurisdiction of the relevant courts.</p>

            <h2>10. Contact</h2>
            <p>For questions about these Terms, please <?= $devEmail ? 'email <a href="mailto:' . e($devEmail) . '">' . e($devEmail) . '</a> or ' : '' ?>use our <a href="/contact">contact form</a>.</p>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Terms of Service', $content, ['description' => 'Terms of Service for ' . $siteName . ' — your rights, responsibilities, and how we operate.']);
