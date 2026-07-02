<?php
defined('AWAN') or die();
require_once __DIR__ . '/_bootstrap.php';
$siteName = $settings->siteName();
ob_start();
?>
<div class="page-hero">
    <div class="page-hero-inner">
        <h1>Disclaimer</h1>
        <p class="cms-page-meta">Last updated: <?= date('F j, Y') ?></p>
    </div>
</div>
<div class="front-container" style="padding-top:40px;padding-bottom:64px">
    <div class="cms-content" style="max-width:800px;margin:0 auto">
        <h2>General Information</h2>
        <p>The information provided by <strong><?= e($siteName) ?></strong> is for general informational purposes only. All information on the Platform is provided in good faith; however, we make no representation or warranty of any kind, express or implied, regarding the accuracy, adequacy, validity, reliability, availability, or completeness of any information on the Platform.</p>

        <h2>No Professional Advice</h2>
        <p>The Platform and its tools (including accounting, HR, inventory, and CRM plugins) are provided as general-purpose software aids. They are <strong>not</strong> a substitute for professional legal, financial, accounting, or human-resources advice. Always consult a qualified professional before making decisions based on information processed by these tools.</p>

        <h2>External Links</h2>
        <p>The Platform may contain links to third-party websites or services. We have no control over the content, privacy policies, or practices of third-party sites and accept no responsibility for them. We encourage you to review the terms and privacy policies of any third-party sites you visit.</p>

        <h2>Tool Accuracy</h2>
        <p>While we strive to ensure accuracy in all platform tools and calculators, results are provided without guarantee. Always verify important calculations independently. We shall not be liable for any errors or omissions, or for any loss or damage of any kind incurred as a result of reliance on tool output.</p>

        <h2>Availability</h2>
        <p>We do not guarantee that the Platform will be available at all times. We may experience downtime for maintenance, updates, or reasons beyond our control. We are not liable for any loss or inconvenience caused by unavailability.</p>

        <h2>Limitation of Liability</h2>
        <p>Under no circumstance shall we be held liable for any indirect, incidental, special, consequential, or punitive damages, whether based on contract, tort, strict liability, or otherwise, arising from your use of the Platform.</p>

        <h2>Contact Us</h2>
        <p>If you have questions about this Disclaimer, please use our <a href="/contact">contact form</a>.</p>
    </div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Disclaimer', $content, ['description' => 'Read our disclaimer regarding the accuracy and use of information provided by ' . $siteName . '.']);
