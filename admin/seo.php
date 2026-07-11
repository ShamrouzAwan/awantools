<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

$logger   = Logger::getInstance($db);
$tab      = Security::sanitize($_GET['tab'] ?? 'general');
$validTabs = ['general', 'opengraph', 'analytics', 'verification', 'recaptcha', 'sitemap', 'audit'];
if (!in_array($tab, $validTabs)) $tab = 'general';

// ─── POST handler ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();

    if ($tab === 'general') {
        $fields = ['seo_title_format', 'seo_meta_description', 'seo_meta_keywords', 'seo_canonical_url'];
        foreach ($fields as $f) $settings->set($f, Security::sanitize($_POST[$f] ?? ''), 'seo');
        $settings->set('seo_robots_index',  isset($_POST['seo_robots_index'])  ? '1' : '0', 'seo');
        $settings->set('seo_robots_follow', isset($_POST['seo_robots_follow']) ? '1' : '0', 'seo');
    }

    if ($tab === 'opengraph') {
        $fields = ['og_site_name', 'og_default_image', 'og_type', 'og_locale', 'twitter_card', 'twitter_site', 'twitter_creator',
                   'og_image_bg_color', 'og_image_card_color', 'og_image_primary_color', 'og_image_eyebrow', 'og_image_watermark'];
        foreach ($fields as $f) $settings->set($f, Security::sanitize($_POST[$f] ?? ''), 'seo');
        $settings->set('og_enabled', isset($_POST['og_enabled']) ? '1' : '0', 'seo');
    }

    if ($tab === 'analytics') {
        $fields = ['google_analytics_id', 'google_tag_manager_id', 'facebook_pixel_id', 'microsoft_clarity_id'];
        foreach ($fields as $f) $settings->set($f, trim(Security::sanitize($_POST[$f] ?? '')), 'seo');
    }

    if ($tab === 'verification') {
        $fields = ['bing_site_verification', 'yandex_verification'];
        foreach ($fields as $f) $settings->set($f, trim(Security::sanitize($_POST[$f] ?? '')), 'seo');
    }

    if ($tab === 'recaptcha') {
        $fields = ['recaptcha_version', 'recaptcha_site_key', 'recaptcha_secret_key', 'recaptcha_v3_score'];
        foreach ($fields as $f) $settings->set($f, trim(Security::sanitize($_POST[$f] ?? '')), 'seo');
        $settings->set('recaptcha_enabled',     isset($_POST['recaptcha_enabled'])     ? '1' : '0', 'seo');
        $settings->set('recaptcha_on_login',    isset($_POST['recaptcha_on_login'])    ? '1' : '0', 'seo');
        $settings->set('recaptcha_on_register', isset($_POST['recaptcha_on_register']) ? '1' : '0', 'seo');
    }

    if ($tab === 'sitemap') {
        $fields = ['sitemap_change_freq', 'sitemap_priority_home', 'sitemap_priority_pages', 'robots_custom_rules'];
        foreach ($fields as $f) $settings->set($f, Security::sanitize($_POST[$f] ?? ''), 'seo');
        $settings->set('sitemap_enabled',       isset($_POST['sitemap_enabled'])       ? '1' : '0', 'seo');
        $settings->set('sitemap_include_users', isset($_POST['sitemap_include_users']) ? '1' : '0', 'seo');
        $settings->set('robots_disallow_admin', isset($_POST['robots_disallow_admin']) ? '1' : '0', 'seo');
        $settings->set('robots_disallow_api',   isset($_POST['robots_disallow_api'])   ? '1' : '0', 'seo');
    }

    $logger->info("SEO settings saved: tab={$tab}", [], $auth->id());
    Session::flash('success', 'SEO settings saved.');
    redirect('/admin/seo?tab=' . urlencode($tab));
}

// ─── Data ─────────────────────────────────────────────────────────────────────
$siteUrl    = rtrim($settings->get('seo_canonical_url') ?: $settings->get('site_url', ''), '/');
$tabLabels  = ['general' => 'General', 'opengraph' => 'OpenGraph & Social', 'analytics' => 'Analytics', 'verification' => 'Webmaster', 'recaptcha' => 'reCAPTCHA', 'sitemap' => 'Sitemap & Robots', 'audit' => 'SEO Audit'];

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">SEO & Integrations</div>
            <div class="page-subtitle">Search engine optimisation, analytics, and bot protection</div>
        </div>
    </div>
    <div class="topbar-actions">
        <?php if ($siteUrl): ?>
        <a href="<?= e($siteUrl) ?>/sitemap.xml" target="_blank" class="btn btn-ghost btn-sm">Sitemap ↗</a>
        <a href="<?= e($siteUrl) ?>/robots.txt" target="_blank" class="btn btn-ghost btn-sm">robots.txt ↗</a>
        <?php else: ?>
        <a href="/sitemap.xml" target="_blank" class="btn btn-ghost btn-sm">Sitemap ↗</a>
        <a href="/robots.txt" target="_blank" class="btn btn-ghost btn-sm">robots.txt ↗</a>
        <?php endif ?>
    </div>
</div>

<div class="page-body">
<!-- Tabs -->
<div style="display:flex;gap:0;margin-bottom:20px;border-bottom:1px solid var(--color-border);overflow-x:auto">
    <?php foreach ($tabLabels as $k => $label): ?>
    <a href="?tab=<?= $k ?>" style="
        padding:9px 16px;font-size:12.5px;font-weight:500;text-decoration:none;white-space:nowrap;
        border-bottom:2px solid <?= $tab === $k ? 'var(--color-primary)' : 'transparent' ?>;
        color:<?= $tab === $k ? 'var(--color-primary)' : 'var(--color-text-secondary)' ?>;
        margin-bottom:-1px;
    "><?= $label ?></a>
    <?php endforeach ?>
</div>

<form method="POST" action="/admin/seo?tab=<?= e($tab) ?>" style="max-width:660px">
    <?= Security::csrfField() ?>

    <?php /* ===================== GENERAL ===================== */ if ($tab === 'general'): ?>

    <div class="card mb-4">
        <div class="card-header"><span class="card-title">Page Titles</span></div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Title Format</label>
                <select name="seo_title_format" class="form-input" onchange="document.getElementById('title_preview').textContent=this.value.replace(':title','Page Name').replace(':site_name','<?= e($settings->siteName()) ?>')">
                    <?php $fmt = $settings->get('seo_title_format', ':title — :site_name');
                    $opts = [':title — :site_name', ':site_name — :title', ':title | :site_name', ':site_name | :title', ':title'];
                    foreach ($opts as $o): ?>
                    <option value="<?= e($o) ?>" <?= $fmt === $o ? 'selected' : '' ?>><?= e($o) ?></option>
                    <?php endforeach ?>
                </select>
                <div class="form-hint">Preview: <span id="title_preview"><?= e(str_replace([':title', ':site_name'], ['Page Name', $settings->siteName()], $fmt)) ?></span></div>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Canonical Base URL</label>
                <input type="url" name="seo_canonical_url" class="form-input" value="<?= e($settings->get('seo_canonical_url', '')) ?>" placeholder="https://example.com">
                <div class="form-hint">Used for canonical links, sitemap, and OG URLs. Leave blank to use the site URL from General Settings.</div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><span class="card-title">Global Meta Tags</span></div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Default Meta Description</label>
                <textarea name="seo_meta_description" class="form-input" rows="3" maxlength="160" placeholder="A concise description of your site (up to 160 characters)"><?= e($settings->get('seo_meta_description', '')) ?></textarea>
                <div class="form-hint">Used on pages with no specific description set. Aim for 120–160 characters.</div>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Meta Keywords</label>
                <input type="text" name="seo_meta_keywords" class="form-input" value="<?= e($settings->get('seo_meta_keywords', '')) ?>" placeholder="keyword1, keyword2, keyword3">
                <div class="form-hint">Comma-separated. Most modern search engines ignore this, but some directories still use it.</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">Indexing Rules</span></div>
        <div class="card-body">
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="seo_robots_index" value="1" <?= $settings->get('seo_robots_index', '1') === '1' ? 'checked' : '' ?>>
                    <span class="form-label" style="margin:0">Allow search engines to index this site</span>
                </label>
                <div class="form-hint">When disabled, adds <code>noindex</code> to all pages. Search engines may still crawl but won't show pages in results.</div>
            </div>
            <div class="form-group mb-0">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="seo_robots_follow" value="1" <?= $settings->get('seo_robots_follow', '1') === '1' ? 'checked' : '' ?>>
                    <span class="form-label" style="margin:0">Allow search engines to follow links</span>
                </label>
                <div class="form-hint">When disabled, adds <code>nofollow</code> to all pages. Only uncheck this if you're under development.</div>
            </div>
        </div>
    </div>

    <?php /* ===================== OPENGRAPH ===================== */ elseif ($tab === 'opengraph'): ?>

    <div class="card mb-4">
        <div class="card-header">
            <span class="card-title">OpenGraph Tags</span>
            <span class="form-hint" style="margin:0">Used by Facebook, LinkedIn, Slack, WhatsApp, Discord &amp; more</span>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="og_enabled" value="1" <?= $settings->get('og_enabled', '1') === '1' ? 'checked' : '' ?>>
                    <span class="form-label" style="margin:0">Enable OpenGraph tags</span>
                </label>
            </div>
            <div class="form-group">
                <label class="form-label">OG Site Name</label>
                <input type="text" name="og_site_name" class="form-input" value="<?= e($settings->get('og_site_name', '')) ?>" placeholder="<?= e($settings->siteName()) ?> (defaults to site name)">
            </div>
            <div class="form-group">
                <label class="form-label">Default OG Image</label>
                <input type="url" name="og_default_image" class="form-input" value="<?= e($settings->get('og_default_image', '')) ?>" placeholder="https://example.com/og-image.jpg">
                <div class="form-hint">Recommended: 1200×630px JPG/PNG. Used when a page doesn't have its own image. Can be a URL from your <a href="/admin/media">Media Library</a>.</div>
            </div>
            <?php if ($settings->get('og_default_image', '')): ?>
            <div style="margin-bottom:12px">
                <img src="<?= e($settings->get('og_default_image', '')) ?>" alt="OG preview" style="max-width:200px;max-height:105px;border-radius:6px;border:1px solid var(--color-border);object-fit:cover">
            </div>
            <?php endif ?>
            <div class="grid-2" style="gap:12px">
                <div class="form-group mb-0">
                    <label class="form-label">OG Type</label>
                    <select name="og_type" class="form-input">
                        <?php foreach (['website' => 'website', 'blog' => 'blog', 'article' => 'article'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= $settings->get('og_type', 'website') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">OG Locale</label>
                    <select name="og_locale" class="form-input">
                        <?php foreach (['en_US' => 'English (US)', 'en_GB' => 'English (UK)', 'fr_FR' => 'French', 'de_DE' => 'German', 'es_ES' => 'Spanish', 'it_IT' => 'Italian', 'pt_BR' => 'Portuguese (BR)', 'ar_AR' => 'Arabic', 'zh_CN' => 'Chinese (Simplified)', 'ja_JP' => 'Japanese'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= $settings->get('og_locale', 'en_US') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <span class="card-title">Auto-Generated OG Image</span>
            <span class="form-hint" style="margin:0">Customize the generated plugin preview image at <code>/og-image.php?slug=…</code></span>
        </div>
        <div class="card-body">
            <div class="form-hint" style="margin-bottom:16px">
                You can also upload a static override: <strong>assets/img/og-image.png</strong> (global fallback) or <strong>assets/img/og-image-{slug}.png</strong> (per-plugin). Static files take priority over the generated image.
                <a href="/admin/files" style="color:var(--color-primary)">Upload via File Browser</a>.
            </div>
            <div class="grid-3" style="gap:12px;margin-bottom:12px">
                <div class="form-group mb-0">
                    <label class="form-label">Background Color</label>
                    <div style="display:flex;gap:8px;align-items:center">
                        <input type="color" id="ogbg_picker" value="<?= e($settings->get('og_image_bg_color', '#0f172a')) ?>" style="width:36px;height:36px;border:1px solid var(--color-border);border-radius:6px;cursor:pointer;padding:2px" oninput="document.getElementById('ogbg_text').value=this.value">
                        <input type="text" id="ogbg_text" name="og_image_bg_color" class="form-input" value="<?= e($settings->get('og_image_bg_color', '#0f172a')) ?>" placeholder="#0f172a" style="flex:1" oninput="document.getElementById('ogbg_picker').value=this.value">
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Card Color</label>
                    <div style="display:flex;gap:8px;align-items:center">
                        <input type="color" id="ogcard_picker" value="<?= e($settings->get('og_image_card_color', '#1a2235')) ?>" style="width:36px;height:36px;border:1px solid var(--color-border);border-radius:6px;cursor:pointer;padding:2px" oninput="document.getElementById('ogcard_text').value=this.value">
                        <input type="text" id="ogcard_text" name="og_image_card_color" class="form-input" value="<?= e($settings->get('og_image_card_color', '#1a2235')) ?>" placeholder="#1a2235" style="flex:1" oninput="document.getElementById('ogcard_picker').value=this.value">
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Primary / Accent Color</label>
                    <div style="display:flex;gap:8px;align-items:center">
                        <input type="color" id="ogprimary_picker" value="<?= e($settings->get('og_image_primary_color', '#6366f1')) ?>" style="width:36px;height:36px;border:1px solid var(--color-border);border-radius:6px;cursor:pointer;padding:2px" oninput="document.getElementById('ogprimary_text').value=this.value">
                        <input type="text" id="ogprimary_text" name="og_image_primary_color" class="form-input" value="<?= e($settings->get('og_image_primary_color', '#6366f1')) ?>" placeholder="#6366f1" style="flex:1" oninput="document.getElementById('ogprimary_picker').value=this.value">
                    </div>
                </div>
            </div>
            <div class="grid-2" style="gap:12px">
                <div class="form-group mb-0">
                    <label class="form-label">Eyebrow Text</label>
                    <input type="text" name="og_image_eyebrow" class="form-input" value="<?= e($settings->get('og_image_eyebrow', '')) ?>" placeholder="AWAN TOOLS  ·  FREE ONLINE TOOL (auto)">
                    <div class="form-hint">Short label above the plugin name. Leave blank to auto-generate.</div>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Watermark Domain</label>
                    <input type="text" name="og_image_watermark" class="form-input" value="<?= e($settings->get('og_image_watermark', '')) ?>" placeholder="awantools.site (auto from site_url)">
                    <div class="form-hint">Domain shown in the bottom-right corner. Leave blank to use site URL.</div>
                </div>
            </div>
            <div style="margin-top:16px">
                <a href="/og-image.php?slug=json-tools" target="_blank" class="btn btn-secondary btn-sm">Preview OG Image</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">Twitter / X Cards</span>
            <span class="form-hint" style="margin:0">Used when links are shared on X (Twitter)</span>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Card Type</label>
                <select name="twitter_card" class="form-input">
                    <?php foreach (['none' => 'Disabled', 'summary' => 'Summary', 'summary_large_image' => 'Summary with Large Image', 'app' => 'App'] as $v => $l): ?>
                    <option value="<?= $v ?>" <?= $settings->get('twitter_card', 'summary_large_image') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="grid-2" style="gap:12px">
                <div class="form-group mb-0">
                    <label class="form-label">Site Handle</label>
                    <input type="text" name="twitter_site" class="form-input" value="<?= e($settings->get('twitter_site', '')) ?>" placeholder="@yourbrand">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Creator Handle</label>
                    <input type="text" name="twitter_creator" class="form-input" value="<?= e($settings->get('twitter_creator', '')) ?>" placeholder="@author">
                    <div class="form-hint">Default author handle. Can be overridden per page.</div>
                </div>
            </div>
        </div>
    </div>

    <?php /* ===================== ANALYTICS ===================== */ elseif ($tab === 'analytics'): ?>

    <div class="alert alert-info" style="margin-bottom:16px">
        💡 <strong>Tip:</strong> If you add both Google Analytics and Google Tag Manager, only GTM will load — it manages GA internally. Use GTM to manage all your tags in one place.
    </div>

    <div class="card mb-4">
        <div class="card-header"><span class="card-title">Google</span></div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Google Analytics 4 (GA4)</label>
                <input type="text" name="google_analytics_id" class="form-input" value="<?= e($settings->get('google_analytics_id', '')) ?>" placeholder="G-XXXXXXXXXX">
                <div class="form-hint">Find in <a href="https://analytics.google.com" target="_blank">Google Analytics</a> → Admin → Data Streams → Measurement ID</div>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Google Tag Manager</label>
                <input type="text" name="google_tag_manager_id" class="form-input" value="<?= e($settings->get('google_tag_manager_id', '')) ?>" placeholder="GTM-XXXXXXX">
                <div class="form-hint">Find in <a href="https://tagmanager.google.com" target="_blank">Google Tag Manager</a> → Container ID. When set, adds GTM snippets to all pages (replaces direct GA4).</div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><span class="card-title">Meta / Facebook</span></div>
        <div class="card-body">
            <div class="form-group mb-0">
                <label class="form-label">Facebook Pixel ID</label>
                <input type="text" name="facebook_pixel_id" class="form-input" value="<?= e($settings->get('facebook_pixel_id', '')) ?>" placeholder="123456789012345">
                <div class="form-hint">Find in <a href="https://business.facebook.com/events_manager" target="_blank">Meta Events Manager</a> → Data Sources → Pixel ID</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">Microsoft</span></div>
        <div class="card-body">
            <div class="form-group mb-0">
                <label class="form-label">Microsoft Clarity</label>
                <input type="text" name="microsoft_clarity_id" class="form-input" value="<?= e($settings->get('microsoft_clarity_id', '')) ?>" placeholder="abcde12345">
                <div class="form-hint">Find in <a href="https://clarity.microsoft.com" target="_blank">Microsoft Clarity</a> → Settings → Overview → Tracking code → Project ID</div>
            </div>
        </div>
    </div>

    <?php /* ===================== VERIFICATION ===================== */ elseif ($tab === 'verification'): ?>

    <div class="alert alert-info" style="margin-bottom:16px">
        These codes verify you own this site with search engines. Google Search Console verification is handled via DNS — no configuration needed here. For Bing and Yandex, enter only the <strong>content value</strong> of the verification meta tag (not the full tag).
    </div>

    <div class="card mb-4">
        <div class="card-header"><span class="card-title">Bing Webmaster Tools</span></div>
        <div class="card-body">
            <div class="form-group mb-0">
                <label class="form-label">Bing Verification Code</label>
                <input type="text" name="bing_site_verification" class="form-input" value="<?= e($settings->get('bing_site_verification', '')) ?>" placeholder="XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX">
                <div class="form-hint">
                    In <a href="https://www.bing.com/webmasters" target="_blank">Bing Webmaster Tools</a> → Add site → Meta tag method → <code>content="..."</code> value.
                    Outputs: <code>&lt;meta name="msvalidate.01" content="..."&gt;</code>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">Yandex Webmaster</span></div>
        <div class="card-body">
            <div class="form-group mb-0">
                <label class="form-label">Yandex Verification Code</label>
                <input type="text" name="yandex_verification" class="form-input" value="<?= e($settings->get('yandex_verification', '')) ?>" placeholder="XXXXXXXXXXXXXXXX">
                <div class="form-hint">
                    In <a href="https://webmaster.yandex.com" target="_blank">Yandex Webmaster</a> → Add site → Meta tag method → <code>content="..."</code> value.
                    Outputs: <code>&lt;meta name="yandex-verification" content="..."&gt;</code>
                </div>
            </div>
        </div>
    </div>

    <?php /* ===================== RECAPTCHA ===================== */ elseif ($tab === 'recaptcha'): ?>

    <div class="alert alert-info" style="margin-bottom:16px">
        reCAPTCHA protects your forms from spam and abuse.
        Get your keys from <a href="https://www.google.com/recaptcha/admin" target="_blank">Google reCAPTCHA Admin Console</a>.
        <ul style="margin:8px 0 0;padding-left:20px;font-size:13px">
            <li><strong>v2 Checkbox</strong> — visible challenge ("I'm not a robot" checkbox). Use reCAPTCHA v2.</li>
            <li><strong>v3 Invisible</strong> — no user interaction, returns a score (0.0–1.0). Use reCAPTCHA v3.</li>
        </ul>
    </div>

    <div class="card mb-4">
        <div class="card-header"><span class="card-title">reCAPTCHA Configuration</span></div>
        <div class="card-body">
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="recaptcha_enabled" value="1" <?= $settings->get('recaptcha_enabled', '0') === '1' ? 'checked' : '' ?>>
                    <span class="form-label" style="margin:0">Enable reCAPTCHA</span>
                </label>
            </div>
            <div class="form-group">
                <label class="form-label">Version</label>
                <div style="display:flex;gap:16px">
                    <?php foreach (['2' => 'v2 — Checkbox (visible)', '3' => 'v3 — Invisible (score-based)'] as $v => $l): ?>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px">
                        <input type="radio" name="recaptcha_version" value="<?= $v ?>" <?= $settings->get('recaptcha_version', '2') === $v ? 'checked' : '' ?>>
                        <?= $l ?>
                    </label>
                    <?php endforeach ?>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Site Key <span class="req">*</span></label>
                <input type="text" name="recaptcha_site_key" class="form-input" value="<?= e($settings->get('recaptcha_site_key', '')) ?>" placeholder="6Lc...">
                <div class="form-hint">Used client-side. Safe to be public.</div>
            </div>
            <div class="form-group">
                <label class="form-label">Secret Key <span class="req">*</span></label>
                <input type="password" name="recaptcha_secret_key" class="form-input" value="<?= e($settings->get('recaptcha_secret_key', '')) ?>" placeholder="6Lc..." autocomplete="new-password">
                <div class="form-hint">Used server-side for verification. Keep this private.</div>
            </div>
            <div class="form-group mb-0" id="v3ScoreGroup" style="<?= $settings->get('recaptcha_version', '2') === '2' ? 'display:none' : '' ?>">
                <label class="form-label">Minimum Score (v3 only)</label>
                <input type="number" name="recaptcha_v3_score" class="form-input" value="<?= e($settings->get('recaptcha_v3_score', '0.5')) ?>" min="0.0" max="1.0" step="0.1" style="max-width:120px">
                <div class="form-hint">0.0 = very likely bot · 1.0 = very likely human. Recommended: 0.5</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><span class="card-title">Apply To Forms</span></div>
        <div class="card-body">
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="recaptcha_on_login" value="1" <?= $settings->get('recaptcha_on_login', '1') === '1' ? 'checked' : '' ?>>
                    <span class="form-label" style="margin:0">Login Form</span>
                </label>
            </div>
            <div class="form-group mb-0">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="recaptcha_on_register" value="1" <?= $settings->get('recaptcha_on_register', '1') === '1' ? 'checked' : '' ?>>
                    <span class="form-label" style="margin:0">Registration Form</span>
                </label>
            </div>
        </div>
    </div>

    <script>
    document.querySelectorAll('[name="recaptcha_version"]').forEach(function(r){r.addEventListener('change',function(){document.getElementById('v3ScoreGroup').style.display=this.value==='3'?'':'none';})});
    </script>

    <?php /* ===================== SITEMAP & ROBOTS ===================== */ elseif ($tab === 'sitemap'): ?>

    <div class="card mb-4">
        <div class="card-header">
            <span class="card-title">XML Sitemap</span>
            <div style="display:flex;gap:6px">
                <a href="/sitemap.xml" target="_blank" class="btn btn-ghost btn-sm">View sitemap.xml ↗</a>
            </div>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="sitemap_enabled" value="1" <?= $settings->get('sitemap_enabled', '1') === '1' ? 'checked' : '' ?>>
                    <span class="form-label" style="margin:0">Enable XML Sitemap</span>
                </label>
                <div class="form-hint">Served at <code>/sitemap.xml</code>. Automatically includes all published pages.</div>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="sitemap_include_users" value="1" <?= $settings->get('sitemap_include_users', '0') === '1' ? 'checked' : '' ?>>
                    <span class="form-label" style="margin:0">Include User Profile Pages</span>
                </label>
                <div class="form-hint">Adds <code>/user/{username}</code> URLs to the sitemap.</div>
            </div>
            <div class="grid-3" style="gap:12px">
                <div class="form-group mb-0">
                    <label class="form-label">Change Frequency</label>
                    <select name="sitemap_change_freq" class="form-input">
                        <?php foreach (['always','hourly','daily','weekly','monthly','yearly','never'] as $f): ?>
                        <option value="<?= $f ?>" <?= $settings->get('sitemap_change_freq', 'weekly') === $f ? 'selected' : '' ?>><?= ucfirst($f) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Homepage Priority</label>
                    <input type="number" name="sitemap_priority_home" class="form-input" value="<?= e($settings->get('sitemap_priority_home', '1.0')) ?>" min="0.0" max="1.0" step="0.1">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Pages Priority</label>
                    <input type="number" name="sitemap_priority_pages" class="form-input" value="<?= e($settings->get('sitemap_priority_pages', '0.8')) ?>" min="0.0" max="1.0" step="0.1">
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">robots.txt</span>
            <a href="/robots.txt" target="_blank" class="btn btn-ghost btn-sm">View robots.txt ↗</a>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="robots_disallow_admin" value="1" <?= $settings->get('robots_disallow_admin', '1') === '1' ? 'checked' : '' ?>>
                    <span class="form-label" style="margin:0">Disallow <code>/admin/</code></span>
                </label>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="robots_disallow_api" value="1" <?= $settings->get('robots_disallow_api', '1') === '1' ? 'checked' : '' ?>>
                    <span class="form-label" style="margin:0">Disallow <code>/api/</code></span>
                </label>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Custom Rules</label>
                <textarea name="robots_custom_rules" class="form-input" rows="5" placeholder="# Add custom rules, e.g.:&#10;User-agent: GPTBot&#10;Disallow: /&#10;&#10;User-agent: Bingbot&#10;Crawl-delay: 1"><?= e($settings->get('robots_custom_rules', '')) ?></textarea>
                <div class="form-hint">These rules are appended verbatim to robots.txt. Use standard robots.txt syntax.</div>
            </div>
        </div>
    </div>

    <?php /* ===================== SEO AUDIT ===================== */ elseif ($tab === 'audit'): ?>
    <?php
    // ── Gather data for audit ──────────────────────────────────────────────────
    $_auditPlugins   = [];
    $_auditBlog      = [];
    $_auditPages     = [];
    $_auditStatic    = [];

    try { $_auditPlugins = $db->fetchAll("SELECT slug, name, description, seo_title, seo_desc FROM plugins WHERE status='active' ORDER BY name ASC") ?: []; } catch (Throwable $e) {}
    try { $_auditBlog    = $db->fetchAll("SELECT id, title, slug, meta_desc, og_image FROM blog_posts WHERE status='published' ORDER BY published_at DESC LIMIT 50") ?: []; } catch (Throwable $e) {}
    try { $_auditPages   = $db->fetchAll("SELECT id, title, slug, seo_title, seo_desc, og_image FROM pages WHERE status='published' ORDER BY title ASC") ?: []; } catch (Throwable $e) {}

    $_auditStaticDef = [
        ['id' => 'homepage',     'label' => 'Homepage',          'url' => '/'],
        ['id' => 'plugins_list', 'label' => 'Tools (/plugins)',   'url' => '/plugins'],
        ['id' => 'blog',         'label' => 'Blog Index',         'url' => '/blog'],
        ['id' => 'faq',          'label' => 'FAQ',                'url' => '/faq'],
        ['id' => 'contact',      'label' => 'Contact',            'url' => '/contact'],
        ['id' => 'terms',        'label' => 'Terms of Service',   'url' => '/terms'],
        ['id' => 'privacy',      'label' => 'Privacy Policy',     'url' => '/privacy'],
        ['id' => 'disclaimer',   'label' => 'Disclaimer',         'url' => '/disclaimer'],
        ['id' => 'cookie_policy','label' => 'Cookie Policy',      'url' => '/cookie-policy'],
    ];
    foreach ($_auditStaticDef as &$_sp) {
        $_sp['seo_title'] = $settings->get("seo_page_{$_sp['id']}_title", '');
        $_sp['seo_desc']  = $settings->get("seo_page_{$_sp['id']}_desc", '');
        $_sp['og_image']  = $settings->get("seo_page_{$_sp['id']}_og_image", '');
    }
    unset($_sp);

    function _auditHealth(string $desc, string $og_image, string $title_override = ''): array {
        $score = 0; $issues = [];
        $len = mb_strlen($desc);
        if ($desc) {
            if ($len >= 50 && $len <= 160) $score += 2;
            else { $score++; $issues[] = "Desc {$len} chars (ideal 50–160)"; }
        } else { $issues[] = 'No meta description'; }
        if ($og_image) $score++;
        else $issues[] = 'No OG image';
        if ($title_override) $score++;
        $cls = $score >= 4 ? 'success' : ($score >= 2 ? 'warning' : 'danger');
        $lbl = $score >= 4 ? 'Good'    : ($score >= 2 ? 'Fair'    : 'Weak');
        return ['cls' => $cls, 'lbl' => $lbl, 'issues' => $issues];
    }

    // Counts
    $_counts = ['good' => 0, 'fair' => 0, 'weak' => 0];
    foreach ($_auditPlugins as $r) {
        $h = _auditHealth($r['seo_desc'] ?? $r['description'] ?? '', '', $r['seo_title'] ?? '');
        $_counts[$h['cls'] === 'success' ? 'good' : ($h['cls'] === 'warning' ? 'fair' : 'weak')]++;
    }
    foreach ($_auditBlog as $r) {
        $h = _auditHealth($r['meta_desc'] ?? '', $r['og_image'] ?? '');
        $_counts[$h['cls'] === 'success' ? 'good' : ($h['cls'] === 'warning' ? 'fair' : 'weak')]++;
    }
    foreach ($_auditPages as $r) {
        $h = _auditHealth($r['seo_desc'] ?? '', $r['og_image'] ?? '', $r['seo_title'] ?? '');
        $_counts[$h['cls'] === 'success' ? 'good' : ($h['cls'] === 'warning' ? 'fair' : 'weak')]++;
    }
    foreach ($_auditStaticDef as $r) {
        $h = _auditHealth($r['seo_desc'] ?? '', $r['og_image'] ?? '', $r['seo_title'] ?? '');
        $_counts[$h['cls'] === 'success' ? 'good' : ($h['cls'] === 'warning' ? 'fair' : 'weak')]++;
    }
    $_total = array_sum($_counts);
    ?>
    <div class="grid-3" style="gap:16px;margin-bottom:24px">
        <div class="card" style="border-left:3px solid var(--color-success)">
            <div class="card-body" style="padding:16px">
                <div style="font-size:28px;font-weight:700;color:var(--color-success)"><?= $_counts['good'] ?></div>
                <div style="font-size:13px;color:var(--color-text-muted)">Good — full SEO coverage</div>
            </div>
        </div>
        <div class="card" style="border-left:3px solid var(--color-warning)">
            <div class="card-body" style="padding:16px">
                <div style="font-size:28px;font-weight:700;color:var(--color-warning)"><?= $_counts['fair'] ?></div>
                <div style="font-size:13px;color:var(--color-text-muted)">Fair — minor issues</div>
            </div>
        </div>
        <div class="card" style="border-left:3px solid var(--color-danger)">
            <div class="card-body" style="padding:16px">
                <div style="font-size:28px;font-weight:700;color:var(--color-danger)"><?= $_counts['weak'] ?></div>
                <div style="font-size:13px;color:var(--color-text-muted)">Weak — needs attention</div>
            </div>
        </div>
    </div>

    <?php
    // Build a combined "needs attention" list
    $_needsAttention = [];
    foreach ($_auditPlugins as $r) {
        $h = _auditHealth($r['seo_desc'] ?? $r['description'] ?? '', '', $r['seo_title'] ?? '');
        if ($h['cls'] !== 'success') $_needsAttention[] = ['type' => 'Plugin', 'name' => $r['name'], 'url' => '/plugins/' . $r['slug'] . '/', 'health' => $h];
    }
    foreach ($_auditBlog as $r) {
        $h = _auditHealth($r['meta_desc'] ?? '', $r['og_image'] ?? '');
        if ($h['cls'] !== 'success') $_needsAttention[] = ['type' => 'Blog', 'name' => $r['title'], 'url' => '/blog/' . $r['slug'], 'health' => $h];
    }
    foreach ($_auditPages as $r) {
        $h = _auditHealth($r['seo_desc'] ?? '', $r['og_image'] ?? '', $r['seo_title'] ?? '');
        if ($h['cls'] !== 'success') $_needsAttention[] = ['type' => 'CMS Page', 'name' => $r['title'], 'url' => '/' . $r['slug'], 'health' => $h];
    }
    foreach ($_auditStaticDef as $r) {
        $h = _auditHealth($r['seo_desc'], $r['og_image'], $r['seo_title']);
        if ($h['cls'] !== 'success') $_needsAttention[] = ['type' => 'Static', 'name' => $r['label'], 'url' => $r['url'], 'health' => $h];
    }
    // Sort: weak first, fair second
    usort($_needsAttention, fn($a, $b) => ($a['health']['cls'] === 'danger' ? 0 : 1) - ($b['health']['cls'] === 'danger' ? 0 : 1));
    ?>

    <?php if (!empty($_needsAttention)): ?>
    <div class="card">
        <div class="card-header">
            <span class="card-title">Pages Needing Attention (<?= count($_needsAttention) ?>)</span>
            <a href="/admin/seo-pages" class="btn btn-primary btn-sm">Fix in Page SEO Manager →</a>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Page</th><th>Type</th><th>Status</th><th>Issues</th></tr></thead>
                <tbody>
                <?php foreach ($_needsAttention as $_row): ?>
                <tr>
                    <td><a href="<?= e($_row['url']) ?>" target="_blank" style="color:var(--color-primary)"><?= e($_row['name']) ?></a></td>
                    <td><span class="badge badge-ghost" style="font-size:11px"><?= e($_row['type']) ?></span></td>
                    <td><span class="badge badge-<?= $_row['health']['cls'] ?>"><?= $_row['health']['lbl'] ?></span></td>
                    <td style="font-size:12px;color:var(--color-text-muted)"><?= e(implode(' · ', $_row['health']['issues'])) ?></td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-success">All tracked pages have good SEO coverage. 🎉</div>
    <?php endif ?>

    <?php endif ?>

    <?php if ($tab !== 'audit'): ?>
    <div style="margin-top:16px">
        <button type="submit" class="btn btn-primary">Save Settings</button>
        <a href="?tab=<?= e($tab) ?>" class="btn btn-ghost">Reset</a>
    </div>
    <?php endif ?>
</form>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('SEO & Integrations', $content, ['section' => 'seo']);
