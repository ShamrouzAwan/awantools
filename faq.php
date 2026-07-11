<?php
defined('AWAN') or die();
require_once __DIR__ . '/_bootstrap.php';

$faqs = [
    'General' => [
        ['q' => 'What is AWAN Platform?',
         'a' => 'AWAN is a modular PHP SaaS framework with a plugin architecture. It provides a unified authentication system, admin panel, analytics, and more — allowing developers to build and deploy independent tools (plugins) that all share the same platform.'],
        ['q' => 'Is AWAN free to use?',
         'a' => 'Yes, AWAN is completely free. You can install it on any PHP hosting provider, including Hostinger, and use every feature at no cost.'],
        ['q' => 'What are the server requirements?',
         'a' => 'AWAN requires PHP 8.1+, either SQLite (default, no setup needed) or MySQL/MariaDB, and mod_rewrite for Apache. It works on any shared hosting that supports PHP.'],
        ['q' => 'Can I use AWAN on shared hosting (like Hostinger)?',
         'a' => 'Absolutely. AWAN is designed specifically for deployment on shared hosting via Apache. Upload the files to your public_html directory, and the included .htaccess file handles all routing.'],
    ],
    'Plugins & Tools' => [
        ['q' => 'What plugins are available?',
         'a' => 'AWAN includes plugins like Notes Manager, Ledger (accounting), CRM (customer management), HRM (HR management), Inventory Manager, Smart Calculator, and more. New plugins are added regularly.'],
        ['q' => 'How do I install a plugin?',
         'a' => 'Go to Admin → Plugins, click "Install Plugin", and upload the plugin zip file. After upload, activate it from the plugins list. The plugin is then accessible via /plugins/{slug}.'],
        ['q' => 'Can I request a new plugin?',
         'a' => 'Yes! Use the <a href="/request-tool" style="color:var(--color-primary)">Request a Tool</a> form to submit your idea. I review all requests and prioritize the most requested ones.'],
        ['q' => 'Can plugins access data from each other?',
         'a' => 'Each plugin has its own database tables (prefixed with the plugin slug) but shares the platform database connection. Plugins can query other tables if needed, but inter-plugin APIs are planned for a future release.'],
    ],
    'Authentication & Security' => [
        ['q' => 'How does authentication work?',
         'a' => 'AWAN uses PHP sessions with CSRF token protection. Passwords are hashed with bcrypt. Login attempts are rate-limited. All database queries use prepared statements to prevent SQL injection.'],
        ['q' => 'Can I disable user registration?',
         'a' => 'Yes — go to Admin → Settings → Authentication and toggle registration off. Admins can still create accounts manually.'],
        ['q' => 'What roles are available?',
         'a' => 'AWAN includes three roles by default: Super Admin (full access), Admin (administrative access), and User (standard access). Plugins can define additional role-based permissions.'],
        ['q' => 'Is my data safe?',
         'a' => 'AWAN follows security best practices: prepared statements for all DB queries, CSRF protection on all forms, rate limiting on login, secure session handling, and detailed audit logging.'],
    ],
    'Admin & Settings' => [
        ['q' => 'How do I access the admin panel?',
         'a' => 'Navigate to /admin/ — you\'ll be asked to sign in if not already authenticated. Only users with the Admin or Super Admin role can access it.'],
        ['q' => 'How do I change the site appearance?',
         'a' => 'Go to Admin → Themes to activate a different theme, or Admin → Themes → Customize to change color variables. Dark mode is available for all visitors via the moon icon in the navigation bar.'],
        ['q' => 'How do I export or back up my data?',
         'a' => 'Go to Admin → Backup to download a complete database export (SQL). You can also manually download the SQLite file from /storage/database.sqlite — but this is protected by .htaccess.'],
    ],
];

ob_start();
?>
<div class="page-hero">
    <div class="page-hero-inner">
        <h1>Frequently Asked Questions</h1>
        <p>Everything you need to know about AWAN Platform, plugins, and deployment.</p>
    </div>
</div>

<div class="front-container" style="padding-top:52px;padding-bottom:72px">
    <!-- Quick jump -->
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:40px">
        <?php foreach (array_keys($faqs) as $cat): ?>
        <a href="#<?= e(urlencode(strtolower(str_replace(' ', '-', $cat)))) ?>"
           class="btn btn-secondary btn-sm"><?= e($cat) ?></a>
        <?php endforeach ?>
    </div>

    <?php foreach ($faqs as $category => $items): ?>
    <div id="<?= e(strtolower(str_replace(' ', '-', $category))) ?>" style="margin-bottom:44px">
        <h2 style="font-size:18px;font-weight:700;color:var(--color-text);margin-bottom:16px;padding-bottom:12px;border-bottom:2px solid var(--color-primary)"><?= e($category) ?></h2>
        <div class="faq-list">
            <?php foreach ($items as $faq): ?>
            <div class="faq-item">
                <button class="faq-question">
                    <?= e($faq['q']) ?>
                    <svg class="faq-chevron" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="faq-answer"><?= $faq['a'] ?></div>
            </div>
            <?php endforeach ?>
        </div>
    </div>
    <?php endforeach ?>

    <div style="text-align:center;padding:40px;background:var(--color-card);border:1px solid var(--color-border);border-radius:var(--radius-large)">
        <h3 style="font-size:18px;font-weight:700;margin-bottom:8px">Still Have Questions?</h3>
        <p style="color:var(--color-text-secondary);margin-bottom:20px">Can't find what you're looking for? Send me a message directly.</p>
        <a href="/contact" class="btn btn-primary btn-lg">Contact Me</a>
    </div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';

// FAQPage JSON-LD — flatten all categories into a single entity list
$_faqItems = [];
foreach ($faqs as $_items) {
    foreach ($_items as $_faq) {
        $_faqItems[] = ['q' => $_faq['q'], 'a' => $_faq['a']];
    }
}
$_faqSchema = ($seo instanceof Seo) ? $seo->faqPageSchema($_faqItems) : '';

render_page('FAQ', $content, [
    'description' => 'Frequently asked questions about AWAN Platform — installation, plugins, security, and more.',
    'schema_org'  => $_faqSchema,
]);
