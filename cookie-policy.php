<?php
defined('AWAN') or die();
require_once __DIR__ . '/_bootstrap.php';
$siteName = $settings->siteName();
ob_start();
?>
<div class="page-hero">
    <div class="page-hero-inner">
        <h1>Cookie Policy</h1>
        <p class="cms-page-meta">Last updated: <?= date('F j, Y') ?></p>
    </div>
</div>
<div class="front-container" style="padding-top:40px;padding-bottom:64px">
    <div class="cms-content" style="max-width:800px;margin:0 auto">
        <p>This Cookie Policy explains how <strong><?= e($siteName) ?></strong> ("we", "us", "our") uses cookies and similar technologies when you visit or use our Platform.</p>

        <h2>What Are Cookies?</h2>
        <p>Cookies are small text files placed on your device by a website you visit. They are widely used to make websites work more efficiently and to provide information to website owners. Cookies may be "session cookies" (deleted when you close your browser) or "persistent cookies" (remain for a set period).</p>

        <h2>Cookies We Use</h2>

        <h3>Strictly Necessary Cookies</h3>
        <p>These cookies are essential for the Platform to function and cannot be disabled. They include:</p>
        <table style="width:100%;border-collapse:collapse;margin:12px 0">
            <thead>
                <tr style="background:var(--color-background)">
                    <th style="padding:10px 12px;text-align:left;border:1px solid var(--color-border);font-size:13px">Cookie Name</th>
                    <th style="padding:10px 12px;text-align:left;border:1px solid var(--color-border);font-size:13px">Purpose</th>
                    <th style="padding:10px 12px;text-align:left;border:1px solid var(--color-border);font-size:13px">Duration</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding:10px 12px;border:1px solid var(--color-border);font-family:monospace;font-size:12px">PHPSESSID</td>
                    <td style="padding:10px 12px;border:1px solid var(--color-border);font-size:13px">Maintains your login session and CSRF token</td>
                    <td style="padding:10px 12px;border:1px solid var(--color-border);font-size:13px">Session (2 hours)</td>
                </tr>
            </tbody>
        </table>

        <h3>Preference Cookies</h3>
        <p>We use browser <strong>localStorage</strong> (not cookies) to remember your dark/light mode preference across visits. This data is stored locally on your device and is never transmitted to our servers.</p>

        <table style="width:100%;border-collapse:collapse;margin:12px 0">
            <thead>
                <tr style="background:var(--color-background)">
                    <th style="padding:10px 12px;text-align:left;border:1px solid var(--color-border);font-size:13px">Key</th>
                    <th style="padding:10px 12px;text-align:left;border:1px solid var(--color-border);font-size:13px">Purpose</th>
                    <th style="padding:10px 12px;text-align:left;border:1px solid var(--color-border);font-size:13px">Type</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding:10px 12px;border:1px solid var(--color-border);font-family:monospace;font-size:12px">awan-theme</td>
                    <td style="padding:10px 12px;border:1px solid var(--color-border);font-size:13px">Stores your preferred colour scheme (light or dark)</td>
                    <td style="padding:10px 12px;border:1px solid var(--color-border);font-size:13px">localStorage (not a cookie)</td>
                </tr>
            </tbody>
        </table>

        <h3>Analytics</h3>
        <p>We collect basic server-side analytics (page views, plugin visits) without cookies. No cross-site tracking or advertising cookies are used.</p>

        <h2>Cookies We Do NOT Use</h2>
        <ul>
            <li>Third-party advertising cookies</li>
            <li>Cross-site tracking cookies</li>
            <li>Social media pixel cookies</li>
            <li>Retargeting or behavioural profiling cookies</li>
        </ul>

        <h2>Managing Cookies</h2>
        <p>You can control and/or delete cookies as you wish — for details, see <a href="https://www.aboutcookies.org" target="_blank" rel="noopener">aboutcookies.org</a>. You can delete all cookies that are already on your computer and you can set most browsers to prevent them from being placed.</p>
        <p>If you disable session cookies, you will not be able to log in to the Platform.</p>

        <h2>Changes to This Policy</h2>
        <p>We may update this Cookie Policy from time to time. The "Last updated" date at the top of this page indicates when the policy was last revised.</p>

        <h2>Contact Us</h2>
        <p>If you have questions about our use of cookies, please <a href="/contact">contact us</a>.</p>
    </div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Cookie Policy', $content, ['description' => 'Learn about the cookies and local storage used by ' . $siteName . '.']);
