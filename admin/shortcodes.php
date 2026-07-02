<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

$shortcodes = [
    [
        'tag'     => '[blog_catalog]',
        'desc'    => 'Embed a grid of recent blog posts.',
        'params'  => 'limit="6" category="news"',
        'example' => '[blog_catalog limit="4"]',
    ],
    [
        'tag'     => '[plugin_catalog]',
        'desc'    => 'Embed the tools/plugins grid.',
        'params'  => 'limit="8" category="Productivity"',
        'example' => '[plugin_catalog limit="6" category="Productivity"]',
    ],
    [
        'tag'     => '[categories]',
        'desc'    => 'Show plugin category badges/links.',
        'params'  => 'style="pills|grid"',
        'example' => '[categories style="pills"]',
    ],
    [
        'tag'     => '[search_bar]',
        'desc'    => 'Embed a live search bar.',
        'params'  => 'placeholder="Search tools..."',
        'example' => '[search_bar placeholder="Find a tool..."]',
    ],
    [
        'tag'     => '[newsletter]',
        'desc'    => 'Embed the newsletter sign-up form.',
        'params'  => 'title="Stay updated"',
        'example' => '[newsletter title="Get weekly tips"]',
    ],
    [
        'tag'     => '[testimonials]',
        'desc'    => 'Display active testimonials.',
        'params'  => 'limit="3" style="grid|slider"',
        'example' => '[testimonials limit="6"]',
    ],
    [
        'tag'     => '[faq]',
        'desc'    => 'Display FAQ accordion.',
        'params'  => 'limit="10"',
        'example' => '[faq limit="5"]',
    ],
    [
        'tag'     => '[contact_form]',
        'desc'    => 'Embed a contact form.',
        'params'  => '',
        'example' => '[contact_form]',
    ],
    [
        'tag'     => '[recent_posts]',
        'desc'    => 'Show a list of recent blog posts (sidebar style).',
        'params'  => 'limit="5"',
        'example' => '[recent_posts limit="3"]',
    ],
    [
        'tag'     => '[site_name]',
        'desc'    => 'Outputs the site name.',
        'params'  => '',
        'example' => '[site_name]',
    ],
    [
        'tag'     => '[year]',
        'desc'    => 'Outputs the current four-digit year.',
        'params'  => '',
        'example' => 'Copyright &copy; [year] [site_name]',
    ],
    [
        'tag'     => '[user_name]',
        'desc'    => 'Outputs the logged-in user\'s display name (empty if not logged in).',
        'params'  => '',
        'example' => 'Welcome back, [user_name]!',
    ],
];

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Shortcodes</div>
            <div class="page-subtitle">Use these codes inside Page and Blog content to embed dynamic content.</div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="card" style="margin-bottom:20px">
        <div class="card-header"><span class="card-title">How to use shortcodes</span></div>
        <div class="card-body">
            <p style="font-size:14px;color:var(--color-text-secondary);margin-bottom:12px">
                Paste any shortcode directly into the <strong>content</strong> field of a Page or Blog post. The platform replaces the tag with the rendered output when the page is displayed.
            </p>
            <div style="background:var(--color-background);border:1px solid var(--color-border);border-radius:var(--radius-small);padding:12px;font-family:monospace;font-size:13px">
                [plugin_catalog limit="6" category="Productivity"]
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">Available Shortcodes</span></div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:180px">Shortcode</th>
                        <th>Description</th>
                        <th>Parameters</th>
                        <th>Example</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($shortcodes as $sc): ?>
                    <tr>
                        <td><code style="background:var(--color-background);padding:2px 8px;border-radius:4px;font-size:12px;white-space:nowrap"><?= e($sc['tag']) ?></code></td>
                        <td style="font-size:13px;color:var(--color-text-secondary)"><?= e($sc['desc']) ?></td>
                        <td style="font-size:12px;color:var(--color-text-muted);font-family:monospace">
                            <?= $sc['params'] ? e($sc['params']) : '<span style="color:var(--color-text-muted);font-style:italic">none</span>' ?>
                        </td>
                        <td>
                            <code style="background:var(--color-background);padding:2px 8px;border-radius:4px;font-size:12px;user-select:all"><?= e($sc['example']) ?></code>
                            <button class="btn btn-ghost btn-sm" style="font-size:11px;margin-left:4px" data-copy="<?= e($sc['example']) ?>">Copy</button>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:20px">
        <div class="card-header"><span class="card-title">Notes</span></div>
        <div class="card-body">
            <ul style="font-size:13px;color:var(--color-text-secondary);padding-left:20px;line-height:1.8">
                <li>Shortcodes with parameters use the <code>key="value"</code> format inside the square brackets.</li>
                <li>Shortcodes are processed server-side before the page is sent to the browser.</li>
                <li>Unknown shortcodes are silently removed — they will not appear in the rendered output.</li>
                <li>Nesting shortcodes (one inside another) is not supported.</li>
                <li>The <strong>TinyMCE</strong> blog editor may encode quote marks — use the HTML source view to paste shortcodes safely.</li>
            </ul>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Shortcodes', $content);
