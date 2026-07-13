<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

$logger = Logger::getInstance($db);
$tab    = Security::sanitize($_GET['tab'] ?? 'plugins');
$validTabs = ['plugins', 'blog', 'pages', 'static'];
if (!in_array($tab, $validTabs)) $tab = 'plugins';

// ─── POST: Save SEO overrides ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $type       = Security::sanitize($_POST['type'] ?? '');
    $id         = Security::sanitize($_POST['id'] ?? '');
    $seo_title  = Security::sanitize(trim($_POST['seo_title']      ?? ''));
    $seo_desc   = Security::sanitize(trim($_POST['seo_desc']       ?? ''));
    $og_title   = Security::sanitize(trim($_POST['og_title']       ?? ''));
    $og_desc    = Security::sanitize(trim($_POST['og_description'] ?? ''));
    // og_image is a URL that may legitimately contain a "?" query string with {{token}} placeholders —
    // do not run through Security::sanitize() (strips content) or urlencode it away; just trim.
    $og_image   = trim($_POST['og_image'] ?? '');

    // ── Advanced SEO fields, packed into one JSON blob (seo_meta) ──
    $keywords    = Security::sanitize(trim($_POST['keywords'] ?? ''));
    $canonical   = trim($_POST['canonical'] ?? '');
    $robotsIndex = !empty($_POST['robots_index']);
    $robotsFollow= !empty($_POST['robots_follow']);
    $robotsAdv   = Security::sanitize(trim($_POST['robots_advanced'] ?? ''));
    $robotsParts = [$robotsIndex ? 'index' : 'noindex', $robotsFollow ? 'follow' : 'nofollow'];
    if ($robotsAdv) $robotsParts[] = $robotsAdv;
    $robots      = implode(', ', $robotsParts);
    $ogType      = Security::sanitize(trim($_POST['og_type'] ?? ''));
    $twitterCard = Security::sanitize(trim($_POST['twitter_card'] ?? ''));
    $schemaType  = Security::sanitize(trim($_POST['schema_type'] ?? 'auto'));
    $schemaJson  = trim($_POST['schema_json'] ?? '');
    if ($schemaJson && json_decode($schemaJson, true) === null) $schemaJson = ''; // discard invalid JSON silently

    $customMeta = [];
    $cmNames    = $_POST['custom_meta_name']    ?? [];
    $cmContents = $_POST['custom_meta_content'] ?? [];
    foreach ($cmNames as $i => $n) {
        $n = Security::sanitize(trim($n));
        $v = Security::sanitize(trim($cmContents[$i] ?? ''));
        if ($n === '' || $v === '') continue;
        $entry = ['content' => $v];
        if (str_contains($n, ':')) $entry['property'] = $n; else $entry['name'] = $n;
        $customMeta[] = $entry;
    }

    $seoMeta = array_filter([
        'keywords'     => $keywords ?: null,
        'canonical'    => $canonical ?: null,
        'robots'       => $robots,
        'og_type'      => $ogType ?: null,
        'twitter_card' => $twitterCard ?: null,
        'schema_type'  => $schemaType !== 'auto' ? $schemaType : null,
        'schema_json'  => $schemaJson ?: null,
        'custom_meta'  => $customMeta ?: null,
    ], fn($v) => $v !== null);
    $seoMetaJson = $seoMeta ? json_encode($seoMeta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

    if ($type === 'plugin' && $id) {
        try {
            $db->query(
                "UPDATE plugins SET seo_title=?, seo_desc=?, og_title=?, og_description=?, og_image=?, seo_meta=? WHERE slug=?",
                [$seo_title ?: null, $seo_desc ?: null, $og_title ?: null, $og_desc ?: null, $og_image ?: null, $seoMetaJson, $id]
            );
            $logger->info("Plugin SEO updated: {$id}", [], $auth->id());
            Session::flash('success', 'Plugin SEO saved.');
        } catch (Throwable $e) {
            Session::flash('danger', 'Save failed: ' . $e->getMessage());
        }
    } elseif ($type === 'blog' && $id) {
        $db->query(
            "UPDATE blog_posts SET meta_desc=?, og_title=?, og_description=?, og_image=?, seo_meta=? WHERE id=?",
            [$seo_desc ?: null, $og_title ?: null, $og_desc ?: null, $og_image ?: null, $seoMetaJson, (int)$id]
        );
        $logger->info("Blog SEO updated: id={$id}", [], $auth->id());
        Session::flash('success', 'Blog post SEO saved.');
    } elseif ($type === 'page' && $id) {
        $db->query(
            "UPDATE pages SET seo_title=?, seo_desc=?, og_title=?, og_description=?, og_image=?, canonical_url=?, seo_meta=? WHERE id=?",
            [$seo_title ?: null, $seo_desc ?: null, $og_title ?: null, $og_desc ?: null, $og_image ?: null, $canonical ?: null, $seoMetaJson, (int)$id]
        );
        $logger->info("CMS page SEO updated: id={$id}", [], $auth->id());
        Session::flash('success', 'Page SEO saved.');
    } elseif ($type === 'static' && $id) {
        // Validate static page id (alphanumeric + underscore only)
        if (preg_match('/^[a-z0-9_]+$/', $id)) {
            $settings->set("seo_page_{$id}_title",    $seo_title, 'seo');
            $settings->set("seo_page_{$id}_desc",     $seo_desc,  'seo');
            $settings->set("seo_page_{$id}_og_image", $og_image,  'seo');
            $settings->set("seo_page_{$id}_meta",     (string)$seoMetaJson, 'seo');
            $logger->info("Static page SEO updated: {$id}", [], $auth->id());
            Session::flash('success', 'Page SEO saved.');
        }
    }
    redirect('/admin/seo-pages?tab=' . urlencode($tab));
}

// ─── Data ─────────────────────────────────────────────────────────────────────
$plugins = [];
try {
    $plugins = $db->fetchAll(
        "SELECT slug, name, description, seo_title, seo_desc, og_title, og_description, og_image, seo_meta FROM plugins WHERE status='active' ORDER BY name ASC"
    ) ?: [];
} catch (Throwable $e) {
    $plugins = $db->fetchAll("SELECT slug, name, description FROM plugins WHERE status='active' ORDER BY name ASC") ?: [];
}

$blogPosts = $db->fetchAll(
    "SELECT id, title, slug, meta_desc, og_title, og_description, og_image, seo_meta FROM blog_posts WHERE status='published' ORDER BY published_at DESC"
) ?: [];

$cmsPages = $db->fetchAll(
    "SELECT id, title, slug, seo_title, seo_desc, og_title, og_description, og_image, canonical_url, seo_meta FROM pages WHERE status='published' ORDER BY title ASC"
) ?: [];

$staticPages = [
    ['id' => 'homepage',     'label' => 'Homepage',          'url' => '/'],
    ['id' => 'plugins_list', 'label' => 'Tools (/plugins)',   'url' => '/plugins'],
    ['id' => 'blog',         'label' => 'Blog Index',         'url' => '/blog'],
    ['id' => 'faq',          'label' => 'FAQ',                'url' => '/faq'],
    ['id' => 'contact',      'label' => 'Contact',            'url' => '/contact'],
    ['id' => 'terms',        'label' => 'Terms of Service',   'url' => '/terms'],
    ['id' => 'privacy',      'label' => 'Privacy Policy',     'url' => '/privacy'],
    ['id' => 'disclaimer',   'label' => 'Disclaimer',         'url' => '/disclaimer'],
    ['id' => 'cookie_policy','label' => 'Cookie Policy',      'url' => '/cookie-policy'],
    ['id' => 'search',       'label' => 'Search Results',     'url' => '/search'],
    ['id' => 'register',     'label' => 'Register',           'url' => '/register'],
    ['id' => 'login',        'label' => 'Login',              'url' => '/login'],
];
foreach ($staticPages as &$sp) {
    $sp['seo_title'] = $settings->get("seo_page_{$sp['id']}_title", '');
    $sp['seo_desc']  = $settings->get("seo_page_{$sp['id']}_desc", '');
    $sp['og_image']  = $settings->get("seo_page_{$sp['id']}_og_image", '');
    $sp['seo_meta']  = $settings->get("seo_page_{$sp['id']}_meta", '');
}
unset($sp);

/** Decode a row's seo_meta JSON into a flat array of advanced-field defaults for the JS modal. */
function seoMetaDefaults($json): array {
    $m = json_decode($json ?: '', true) ?: [];
    return [
        'keywords'      => $m['keywords']      ?? '',
        'canonical'     => $m['canonical']     ?? '',
        'robots_index'  => !isset($m['robots']) || !str_contains($m['robots'], 'noindex'),
        'robots_follow' => !isset($m['robots']) || !str_contains($m['robots'], 'nofollow'),
        'robots_advanced' => trim(preg_replace('/^(no)?index,\s*(no)?follow,?\s*/', '', $m['robots'] ?? '')),
        'og_type'       => $m['og_type']       ?? '',
        'twitter_card'  => $m['twitter_card']  ?? '',
        'schema_type'   => $m['schema_type']   ?? 'auto',
        'schema_json'   => $m['schema_json']   ?? '',
        'custom_meta'   => $m['custom_meta']   ?? [],
    ];
}

$tabLabels = ['plugins' => 'Plugins', 'blog' => 'Blog Posts', 'pages' => 'CMS Pages', 'static' => 'Static Pages'];

// ─── Health helper ────────────────────────────────────────────────────────────
function seoPageHealth(string $desc, string $og_image = '', string $title_override = ''): array {
    $score = 0; $issues = [];
    $len = mb_strlen($desc);
    if ($desc) {
        if ($len >= 50 && $len <= 160) $score += 2;
        else { $score++; $issues[] = "{$len} chars (ideal 50–160)"; }
    } else { $issues[] = 'No description'; }
    if ($og_image) $score++;
    else $issues[] = 'No OG image';
    if ($title_override) $score++;
    $cls = $score >= 4 ? 'success' : ($score >= 2 ? 'warning' : 'danger');
    $lbl = $score >= 4 ? 'Good'    : ($score >= 2 ? 'Fair'    : 'Weak');
    return ['cls' => $cls, 'lbl' => $lbl, 'issues' => $issues, 'score' => $score];
}

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Page SEO Manager</div>
            <div class="page-subtitle">Set SEO titles, meta descriptions, and OG images for every page on your site.</div>
        </div>
    </div>
    <div class="page-header-right">
        <a href="/admin/seo?tab=audit" class="btn btn-ghost btn-sm">SEO Audit →</a>
    </div>
</div>

<!-- Tabs -->
<div class="tabs" style="margin-bottom:24px">
    <?php foreach ($tabLabels as $k => $label): ?>
    <a href="?tab=<?= $k ?>" class="tab<?= $tab === $k ? ' tab-active' : '' ?>"><?= $label ?></a>
    <?php endforeach ?>
</div>

<?php /* ===================== PLUGINS ===================== */ if ($tab === 'plugins'): ?>

<div class="card">
    <div class="card-header">
        <span class="card-title">Plugins (<?= count($plugins) ?>)</span>
        <span class="form-hint" style="margin:0">Overrides take priority over the plugin's built-in description.</span>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Plugin</th>
                    <th>SEO Title</th>
                    <th>Meta Description</th>
                    <th>OG Image</th>
                    <th>Health</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($plugins as $row):
                $effDesc = $row['seo_desc'] ?? $row['description'] ?? '';
                $h = seoPageHealth($effDesc, $row['og_image'] ?? '', $row['seo_title'] ?? '');
            ?>
            <tr>
                <td>
                    <div style="font-weight:500"><?= e($row['name']) ?></div>
                    <div style="font-size:11px;color:var(--color-text-muted)">/plugins/<?= e($row['slug']) ?>/</div>
                </td>
                <td>
                    <?php if (!empty($row['seo_title'])): ?>
                        <span style="font-size:12px"><?= e(mb_substr($row['seo_title'], 0, 40)) ?><?= mb_strlen($row['seo_title']) > 40 ? '…' : '' ?></span>
                    <?php else: ?>
                        <span style="font-size:11px;color:var(--color-text-muted)">Using plugin name</span>
                    <?php endif ?>
                </td>
                <td>
                    <?php if (!empty($row['seo_desc'])): ?>
                        <span style="font-size:12px"><?= e(mb_substr($row['seo_desc'], 0, 55)) ?><?= mb_strlen($row['seo_desc']) > 55 ? '…' : '' ?></span>
                        <span style="font-size:10px;color:var(--color-text-muted);display:block"><?= mb_strlen($row['seo_desc']) ?> chars</span>
                    <?php elseif (!empty($row['description'])): ?>
                        <span style="font-size:11px;color:var(--color-text-muted)"><?= e(mb_substr($row['description'], 0, 50)) ?>… <em>(default)</em></span>
                    <?php else: ?>
                        <span style="font-size:11px;color:var(--color-danger)">—</span>
                    <?php endif ?>
                </td>
                <td>
                    <?php if (!empty($row['og_image'])): ?>
                        <img src="<?= e($row['og_image']) ?>" style="width:48px;height:28px;object-fit:cover;border-radius:3px;border:1px solid var(--color-border)" alt="">
                    <?php else: ?>
                        <span style="font-size:11px;color:var(--color-text-muted)">—</span>
                    <?php endif ?>
                </td>
                <td>
                    <span class="badge badge-<?= $h['cls'] ?>" title="<?= e(implode(', ', $h['issues'])) ?>"><?= $h['lbl'] ?></span>
                    <?php if (!empty($h['issues'])): ?>
                    <div style="font-size:10px;color:var(--color-text-muted);margin-top:2px"><?= e(implode(' · ', $h['issues'])) ?></div>
                    <?php endif ?>
                </td>
                <td>
                    <button type="button" class="btn btn-ghost btn-sm js-seo-edit"
                        data-type="plugin"
                        data-id="<?= e($row['slug']) ?>"
                        data-name="<?= e($row['name']) ?>"
                        data-seo-title="<?= e($row['seo_title'] ?? '') ?>"
                        data-seo-desc="<?= e($row['seo_desc'] ?? '') ?>"
                        data-og-title="<?= e($row['og_title'] ?? '') ?>"
                        data-og-description="<?= e($row['og_description'] ?? '') ?>"
                        data-og-image="<?= e($row['og_image'] ?? '') ?>"
                        data-scope="plugin"
                        data-token-name="<?= e($row['name']) ?>"
                        data-token-slug="<?= e($row['slug']) ?>"
                        data-seo-meta="<?= e(json_encode(seoMetaDefaults($row['seo_meta'] ?? ''))) ?>">Edit</button>
                </td>
            </tr>
            <?php endforeach ?>
            <?php if (empty($plugins)): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--color-text-muted);padding:40px">No active plugins found.</td></tr>
            <?php endif ?>
            </tbody>
        </table>
    </div>
</div>

<?php /* ===================== BLOG POSTS ===================== */ elseif ($tab === 'blog'): ?>

<div class="card">
    <div class="card-header">
        <span class="card-title">Blog Posts (<?= count($blogPosts) ?>)</span>
        <a href="/admin/blog" class="btn btn-ghost btn-sm">Manage Posts →</a>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Post</th>
                    <th>Meta Description</th>
                    <th>OG Image</th>
                    <th>Health</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($blogPosts as $row):
                $h = seoPageHealth($row['meta_desc'] ?? '', $row['og_image'] ?? '');
            ?>
            <tr>
                <td>
                    <div style="font-weight:500"><?= e(mb_substr($row['title'], 0, 55)) ?><?= mb_strlen($row['title']) > 55 ? '…' : '' ?></div>
                    <div style="font-size:11px;color:var(--color-text-muted)">/blog/<?= e($row['slug']) ?></div>
                </td>
                <td>
                    <?php if (!empty($row['meta_desc'])): ?>
                        <span style="font-size:12px"><?= e(mb_substr($row['meta_desc'], 0, 60)) ?><?= mb_strlen($row['meta_desc']) > 60 ? '…' : '' ?></span>
                        <span style="font-size:10px;color:var(--color-text-muted);display:block"><?= mb_strlen($row['meta_desc']) ?> chars</span>
                    <?php else: ?>
                        <span style="font-size:11px;color:var(--color-danger)">Not set</span>
                    <?php endif ?>
                </td>
                <td>
                    <?php if (!empty($row['og_image'])): ?>
                        <img src="<?= e($row['og_image']) ?>" style="width:48px;height:28px;object-fit:cover;border-radius:3px;border:1px solid var(--color-border)" alt="">
                    <?php else: ?>
                        <span style="font-size:11px;color:var(--color-text-muted)">—</span>
                    <?php endif ?>
                </td>
                <td>
                    <span class="badge badge-<?= $h['cls'] ?>" title="<?= e(implode(', ', $h['issues'])) ?>"><?= $h['lbl'] ?></span>
                    <?php if (!empty($h['issues'])): ?>
                    <div style="font-size:10px;color:var(--color-text-muted);margin-top:2px"><?= e(implode(' · ', $h['issues'])) ?></div>
                    <?php endif ?>
                </td>
                <td>
                    <button type="button" class="btn btn-ghost btn-sm js-seo-edit"
                        data-type="blog"
                        data-id="<?= $row['id'] ?>"
                        data-name="<?= e($row['title']) ?>"
                        data-seo-desc="<?= e($row['meta_desc'] ?? '') ?>"
                        data-og-title="<?= e($row['og_title'] ?? '') ?>"
                        data-og-description="<?= e($row['og_description'] ?? '') ?>"
                        data-og-image="<?= e($row['og_image'] ?? '') ?>"
                        data-scope="blog"
                        data-token-title="<?= e($row['title']) ?>"
                        data-seo-meta="<?= e(json_encode(seoMetaDefaults($row['seo_meta'] ?? ''))) ?>">Edit</button>
                </td>
            </tr>
            <?php endforeach ?>
            <?php if (empty($blogPosts)): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--color-text-muted);padding:40px">No published blog posts found.</td></tr>
            <?php endif ?>
            </tbody>
        </table>
    </div>
</div>

<?php /* ===================== CMS PAGES ===================== */ elseif ($tab === 'pages'): ?>

<div class="card">
    <div class="card-header">
        <span class="card-title">CMS Pages (<?= count($cmsPages) ?>)</span>
        <a href="/admin/pages" class="btn btn-ghost btn-sm">Manage Pages →</a>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Page</th>
                    <th>SEO Title</th>
                    <th>Meta Description</th>
                    <th>OG Image</th>
                    <th>Health</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($cmsPages as $row):
                $h = seoPageHealth($row['seo_desc'] ?? '', $row['og_image'] ?? '', $row['seo_title'] ?? '');
            ?>
            <tr>
                <td>
                    <div style="font-weight:500"><?= e($row['title']) ?></div>
                    <div style="font-size:11px;color:var(--color-text-muted)">/<?= e($row['slug']) ?></div>
                </td>
                <td>
                    <?php if (!empty($row['seo_title'])): ?>
                        <span style="font-size:12px"><?= e(mb_substr($row['seo_title'], 0, 40)) ?><?= mb_strlen($row['seo_title']) > 40 ? '…' : '' ?></span>
                    <?php else: ?>
                        <span style="font-size:11px;color:var(--color-text-muted)">Using page title</span>
                    <?php endif ?>
                </td>
                <td>
                    <?php if (!empty($row['seo_desc'])): ?>
                        <span style="font-size:12px"><?= e(mb_substr($row['seo_desc'], 0, 60)) ?><?= mb_strlen($row['seo_desc']) > 60 ? '…' : '' ?></span>
                        <span style="font-size:10px;color:var(--color-text-muted);display:block"><?= mb_strlen($row['seo_desc']) ?> chars</span>
                    <?php else: ?>
                        <span style="font-size:11px;color:var(--color-danger)">Not set</span>
                    <?php endif ?>
                </td>
                <td>
                    <?php if (!empty($row['og_image'])): ?>
                        <img src="<?= e($row['og_image']) ?>" style="width:48px;height:28px;object-fit:cover;border-radius:3px;border:1px solid var(--color-border)" alt="">
                    <?php else: ?>
                        <span style="font-size:11px;color:var(--color-text-muted)">—</span>
                    <?php endif ?>
                </td>
                <td>
                    <span class="badge badge-<?= $h['cls'] ?>" title="<?= e(implode(', ', $h['issues'])) ?>"><?= $h['lbl'] ?></span>
                    <?php if (!empty($h['issues'])): ?>
                    <div style="font-size:10px;color:var(--color-text-muted);margin-top:2px"><?= e(implode(' · ', $h['issues'])) ?></div>
                    <?php endif ?>
                </td>
                <td>
                    <button type="button" class="btn btn-ghost btn-sm js-seo-edit"
                        data-type="page"
                        data-id="<?= $row['id'] ?>"
                        data-name="<?= e($row['title']) ?>"
                        data-seo-title="<?= e($row['seo_title'] ?? '') ?>"
                        data-seo-desc="<?= e($row['seo_desc'] ?? '') ?>"
                        data-og-title="<?= e($row['og_title'] ?? '') ?>"
                        data-og-description="<?= e($row['og_description'] ?? '') ?>"
                        data-og-image="<?= e($row['og_image'] ?? '') ?>"
                        data-scope="page"
                        data-token-title="<?= e($row['title']) ?>"
                        data-canonical="<?= e($row['canonical_url'] ?? '') ?>"
                        data-seo-meta="<?= e(json_encode(seoMetaDefaults($row['seo_meta'] ?? ''))) ?>">Edit</button>
                </td>
            </tr>
            <?php endforeach ?>
            <?php if (empty($cmsPages)): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--color-text-muted);padding:40px">No published CMS pages found.</td></tr>
            <?php endif ?>
            </tbody>
        </table>
    </div>
</div>

<?php /* ===================== STATIC PAGES ===================== */ elseif ($tab === 'static'): ?>

<div class="alert alert-info" style="margin-bottom:16px">
    Static pages are built-in PHP pages. Their SEO overrides are saved to the database and applied automatically.
</div>
<div class="card">
    <div class="card-header">
        <span class="card-title">Static Pages (<?= count($staticPages) ?>)</span>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Page</th>
                    <th>SEO Title Override</th>
                    <th>Meta Description</th>
                    <th>OG Image</th>
                    <th>Health</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($staticPages as $row):
                $h = seoPageHealth($row['seo_desc'], $row['og_image'], $row['seo_title']);
            ?>
            <tr>
                <td>
                    <div style="font-weight:500"><?= e($row['label']) ?></div>
                    <div style="font-size:11px;color:var(--color-text-muted)"><?= e($row['url']) ?></div>
                </td>
                <td>
                    <?php if (!empty($row['seo_title'])): ?>
                        <span style="font-size:12px"><?= e(mb_substr($row['seo_title'], 0, 40)) ?><?= mb_strlen($row['seo_title']) > 40 ? '…' : '' ?></span>
                    <?php else: ?>
                        <span style="font-size:11px;color:var(--color-text-muted)">Using page title</span>
                    <?php endif ?>
                </td>
                <td>
                    <?php if (!empty($row['seo_desc'])): ?>
                        <span style="font-size:12px"><?= e(mb_substr($row['seo_desc'], 0, 60)) ?><?= mb_strlen($row['seo_desc']) > 60 ? '…' : '' ?></span>
                        <span style="font-size:10px;color:var(--color-text-muted);display:block"><?= mb_strlen($row['seo_desc']) ?> chars</span>
                    <?php else: ?>
                        <span style="font-size:11px;color:var(--color-danger)">Not set</span>
                    <?php endif ?>
                </td>
                <td>
                    <?php if (!empty($row['og_image'])): ?>
                        <img src="<?= e($row['og_image']) ?>" style="width:48px;height:28px;object-fit:cover;border-radius:3px;border:1px solid var(--color-border)" alt="">
                    <?php else: ?>
                        <span style="font-size:11px;color:var(--color-text-muted)">—</span>
                    <?php endif ?>
                </td>
                <td>
                    <span class="badge badge-<?= $h['cls'] ?>" title="<?= e(implode(', ', $h['issues'])) ?>"><?= $h['lbl'] ?></span>
                    <?php if (!empty($h['issues'])): ?>
                    <div style="font-size:10px;color:var(--color-text-muted);margin-top:2px"><?= e(implode(' · ', $h['issues'])) ?></div>
                    <?php endif ?>
                </td>
                <td>
                    <button type="button" class="btn btn-ghost btn-sm js-seo-edit"
                        data-type="static"
                        data-id="<?= e($row['id']) ?>"
                        data-name="<?= e($row['label']) ?>"
                        data-seo-title="<?= e($row['seo_title']) ?>"
                        data-seo-desc="<?= e($row['seo_desc']) ?>"
                        data-og-image="<?= e($row['og_image']) ?>"
                        data-scope="static"
                        data-token-title="<?= e($row['label']) ?>"
                        data-token-url="<?= e($row['url']) ?>"
                        data-seo-meta="<?= e(json_encode(seoMetaDefaults($row['seo_meta'] ?? ''))) ?>">Edit</button>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif ?>

<!-- ─── Edit Modal ─────────────────────────────────────────────────────────── -->
<div id="seo-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:var(--color-card);border:1px solid var(--color-border);border-radius:var(--radius-large);width:100%;max-width:580px;max-height:90vh;overflow-y:auto;margin:16px">
        <div style="padding:20px 24px 16px;border-bottom:1px solid var(--color-border);display:flex;align-items:center;justify-content:space-between">
            <div>
                <div style="font-weight:700;font-size:16px">Edit SEO</div>
                <div id="seo-modal-name" style="font-size:12px;color:var(--color-text-muted);margin-top:2px"></div>
            </div>
            <button type="button" id="seo-modal-close" style="background:none;border:none;cursor:pointer;color:var(--color-text-muted);font-size:20px;padding:4px 8px">✕</button>
        </div>
        <form id="seo-modal-form" method="POST" action="/admin/seo-pages">
            <div style="padding:20px 24px">
                <?php echo Security::csrfField() ?>
                <input type="hidden" name="tab" value="">
                <input type="hidden" name="type" id="seo-input-type" value="">
                <input type="hidden" name="id" id="seo-input-id" value="">

                <div id="seo-field-seo-title-wrap" class="form-group">
                    <label class="form-label">SEO Title <span style="font-size:11px;color:var(--color-text-muted)">(overrides the page title in search results)</span></label>
                    <input type="text" name="seo_title" id="seo-input-seo-title" class="form-input" maxlength="70" placeholder="Leave blank to use the default page title">
                    <div class="form-hint"><span id="seo-title-count">0</span>/70 chars — ideal: under 60</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Meta Description <span style="font-size:11px;color:var(--color-text-muted);font-weight:400">(shown in Google search results)</span></label>
                    <textarea name="seo_desc" id="seo-input-seo-desc" class="form-input" rows="3" maxlength="200" placeholder="Describe this page in 50–160 characters…"></textarea>
                    <div class="form-hint"><span id="seo-desc-count">0</span>/200 chars — ideal: 50–160 · <span id="seo-desc-status" style="font-weight:500"></span></div>
                </div>

                <div id="seo-field-og-title-wrap" class="form-group">
                    <label class="form-label">OG Title <span style="font-size:11px;color:var(--color-text-muted)">(for social sharing cards — defaults to SEO title)</span></label>
                    <input type="text" name="og_title" id="seo-input-og-title" class="form-input" maxlength="100" placeholder="Leave blank to use the SEO title">
                </div>

                <div id="seo-field-og-desc-wrap" class="form-group">
                    <label class="form-label">OG Description <span style="font-size:11px;color:var(--color-text-muted)">(for social sharing cards — defaults to meta description)</span></label>
                    <textarea name="og_description" id="seo-input-og-description" class="form-input" rows="2" maxlength="200" placeholder="Leave blank to use the meta description"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">OG Image URL <span style="font-size:11px;color:var(--color-text-muted)">(1200×630px recommended)</span></label>
                    <input type="text" name="og_image" id="seo-input-og-image" class="form-input" placeholder="https://example.com/og-image.jpg or build one below">
                    <div class="form-hint">Used when shared on Facebook, Twitter/X, LinkedIn, Slack, etc. Leave blank to use the global default OG image. Can contain <code>{{tokens}}</code> resolved live at render time.</div>
                    <div id="seo-og-preview" style="margin-top:8px;display:none">
                        <img id="seo-og-preview-img" src="" style="max-width:200px;max-height:105px;border-radius:6px;border:1px solid var(--color-border);object-fit:cover" alt="OG preview">
                    </div>
                </div>

                <!-- ─── Previewer Toolkit builder ─────────────────────────────────── -->
                <div class="form-group" style="border:1px solid var(--color-border);border-radius:var(--radius-medium);padding:14px;background:var(--color-background)">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
                        <label class="form-label" style="margin:0">OG Image Builder <span style="font-size:11px;color:var(--color-text-muted)">(Previewer Toolkit)</span></label>
                        <button type="button" id="seo-builder-toggle" class="btn btn-ghost btn-sm">Open builder</button>
                    </div>
                    <div id="seo-builder-panel" style="display:none;margin-top:10px">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
                            <div>
                                <label class="form-label" style="font-size:11px">Category</label>
                                <select id="seo-b-category" class="form-input">
                                    <option value="og" selected>OG Images</option>
                                    <option value="social">Social Cards</option>
                                    <option value="profile">Profile Cards</option>
                                    <option value="docs">Docs Previews</option>
                                    <option value="dashboard">Dashboards</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label" style="font-size:11px">Template</label>
                                <select id="seo-b-template" class="form-input">
                                    <option value="github_dark" selected>GitHub Dark</option>
                                    <option value="gradient_pro">Gradient Pro</option>
                                    <option value="aurora">Aurora</option>
                                    <option value="github_light">GitHub Light</option>
                                    <option value="glass_modern">Glass Modern</option>
                                    <option value="mono">Mono</option>
                                </select>
                            </div>
                        </div>
                        <?php foreach ([
                            ['heading', 'Heading'], ['subheading', 'Subheading'],
                            ['description', 'Description'], ['badge', 'Badge'], ['footer', 'Footer'],
                        ] as [$bf, $bl]): ?>
                        <div style="margin-bottom:8px">
                            <div style="display:flex;align-items:center;justify-content:space-between">
                                <label class="form-label" style="font-size:11px;margin:0"><?= $bl ?></label>
                                <select class="seo-b-token-insert" data-target="seo-b-<?= $bf ?>" style="font-size:11px;padding:2px 4px;border:1px solid var(--color-border);border-radius:4px;background:var(--color-card)">
                                    <option value="">+ Insert token…</option>
                                </select>
                            </div>
                            <input type="text" id="seo-b-<?= $bf ?>" class="form-input seo-b-field" style="font-size:12px">
                        </div>
                        <?php endforeach ?>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:10px">
                            <div><label class="form-label" style="font-size:11px">BG Color</label><input type="text" id="seo-b-bg_color" class="form-input seo-b-field" value="3d8bff" style="font-size:12px"></div>
                            <div><label class="form-label" style="font-size:11px">Accent Color</label><input type="text" id="seo-b-accent_color" class="form-input seo-b-field" value="050b18" style="font-size:12px"></div>
                            <div><label class="form-label" style="font-size:11px">Heading Color</label><input type="text" id="seo-b-heading_color" class="form-input seo-b-field" value="ffffff" style="font-size:12px"></div>
                        </div>
                        <div style="display:flex;gap:12px;align-items:flex-start">
                            <img id="seo-b-preview" src="" style="width:200px;height:105px;object-fit:cover;border-radius:6px;border:1px solid var(--color-border);background:var(--color-card)" alt="Preview">
                            <div style="flex:1">
                                <div class="form-hint" style="margin:0 0 8px">Live preview uses this row's real values in place of tokens. The stored URL keeps <code>{{tokens}}</code> so it stays live everywhere this page is shared.</div>
                                <button type="button" id="seo-builder-use" class="btn btn-primary btn-sm">Use as OG Image</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ─── Advanced SEO ──────────────────────────────────────────────── -->
                <div class="form-group" style="border:1px solid var(--color-border);border-radius:var(--radius-medium);padding:14px;background:var(--color-background)">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
                        <label class="form-label" style="margin:0">Advanced SEO</label>
                        <button type="button" id="seo-advanced-toggle" class="btn btn-ghost btn-sm">Show advanced</button>
                    </div>
                    <div id="seo-advanced-panel" style="display:none;margin-top:10px">
                        <div class="form-group">
                            <label class="form-label" style="font-size:12px">Keywords <span style="font-size:11px;color:var(--color-text-muted)">(comma-separated)</span></label>
                            <input type="text" name="keywords" id="seo-input-keywords" class="form-input">
                        </div>
                        <div class="form-group" id="seo-canonical-wrap">
                            <label class="form-label" style="font-size:12px">Canonical URL</label>
                            <input type="text" name="canonical" id="seo-input-canonical" class="form-input" placeholder="https://example.com/preferred-url">
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size:12px">Robots</label>
                            <div style="display:flex;gap:16px;margin-bottom:6px">
                                <label style="font-size:12px;display:flex;align-items:center;gap:5px"><input type="checkbox" name="robots_index" id="seo-input-robots-index" checked> Index</label>
                                <label style="font-size:12px;display:flex;align-items:center;gap:5px"><input type="checkbox" name="robots_follow" id="seo-input-robots-follow" checked> Follow</label>
                            </div>
                            <input type="text" name="robots_advanced" id="seo-input-robots-advanced" class="form-input" placeholder="noarchive, max-snippet:-1, max-image-preview:large">
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                            <div class="form-group">
                                <label class="form-label" style="font-size:12px">OG Type</label>
                                <select name="og_type" id="seo-input-og-type" class="form-input">
                                    <option value="">Default (website)</option>
                                    <option value="article">Article</option>
                                    <option value="product">Product</option>
                                    <option value="profile">Profile</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" style="font-size:12px">Twitter Card</label>
                                <select name="twitter_card" id="seo-input-twitter-card" class="form-input">
                                    <option value="">Default (site setting)</option>
                                    <option value="summary">Summary</option>
                                    <option value="summary_large_image">Summary — Large Image</option>
                                    <option value="none">None</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size:12px">JSON-LD Schema</label>
                            <select name="schema_type" id="seo-input-schema-type" class="form-input" style="margin-bottom:6px">
                                <option value="auto">Auto (default for this content type)</option>
                                <option value="custom">Custom JSON-LD override</option>
                                <option value="none">None</option>
                            </select>
                            <textarea name="schema_json" id="seo-input-schema-json" class="form-input" rows="3" style="display:none;font-family:monospace;font-size:11px" placeholder='{"@context":"https://schema.org","@type":"Article",...}'></textarea>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label" style="font-size:12px">Custom Meta Tags</label>
                            <div id="seo-custom-meta-rows"></div>
                            <button type="button" id="seo-custom-meta-add" class="btn btn-ghost btn-sm">+ Add meta tag</button>
                            <div class="form-hint">Name like <code>author</code>, or a property like <code>og:custom</code> (contains a colon).</div>
                        </div>
                    </div>
                </div>
            </div>
            <div style="padding:16px 24px;border-top:1px solid var(--color-border);display:flex;gap:10px;justify-content:flex-end">
                <button type="button" id="seo-modal-cancel" class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary">Save SEO</button>
            </div>
        </form>
    </div>
</div>

<script>
var SEO_TOKEN_CATALOG = <?= json_encode(SeoTokens::catalog(), JSON_UNESCAPED_SLASHES) ?>;
(function () {
    var modal     = document.getElementById('seo-modal');
    var form      = document.getElementById('seo-modal-form');
    var nameEl    = document.getElementById('seo-modal-name');
    var titleInp  = document.getElementById('seo-input-seo-title');
    var descInp   = document.getElementById('seo-input-seo-desc');
    var ogTitleInp= document.getElementById('seo-input-og-title');
    var ogDescInp = document.getElementById('seo-input-og-description');
    var ogImgInp  = document.getElementById('seo-input-og-image');
    var ogPreview = document.getElementById('seo-og-preview');
    var ogPreviewImg = document.getElementById('seo-og-preview-img');
    var titleCount = document.getElementById('seo-title-count');
    var descCount  = document.getElementById('seo-desc-count');
    var descStatus = document.getElementById('seo-desc-status');
    var titleWrap  = document.getElementById('seo-field-seo-title-wrap');
    var ogTitleWrap= document.getElementById('seo-field-og-title-wrap');
    var ogDescWrap = document.getElementById('seo-field-og-desc-wrap');
    var keywordsInp = document.getElementById('seo-input-keywords');
    var canonicalInp= document.getElementById('seo-input-canonical');
    var canonicalWrap = document.getElementById('seo-canonical-wrap');
    var robotsIndexInp  = document.getElementById('seo-input-robots-index');
    var robotsFollowInp = document.getElementById('seo-input-robots-follow');
    var robotsAdvInp     = document.getElementById('seo-input-robots-advanced');
    var ogTypeInp    = document.getElementById('seo-input-og-type');
    var twitterCardInp = document.getElementById('seo-input-twitter-card');
    var schemaTypeInp = document.getElementById('seo-input-schema-type');
    var schemaJsonInp = document.getElementById('seo-input-schema-json');
    var customMetaRows = document.getElementById('seo-custom-meta-rows');
    var advToggle = document.getElementById('seo-advanced-toggle');
    var advPanel  = document.getElementById('seo-advanced-panel');
    var builderToggle = document.getElementById('seo-builder-toggle');
    var builderPanel  = document.getElementById('seo-builder-panel');
    var currentScope = '';
    var currentTokens = {};

    advToggle.addEventListener('click', function () {
        var showing = advPanel.style.display !== 'none';
        advPanel.style.display = showing ? 'none' : '';
        advToggle.textContent = showing ? 'Show advanced' : 'Hide advanced';
    });
    builderToggle.addEventListener('click', function () {
        var showing = builderPanel.style.display !== 'none';
        builderPanel.style.display = showing ? 'none' : '';
        builderToggle.textContent = showing ? 'Open builder' : 'Close builder';
        if (!showing) updateBuilderPreview();
    });
    schemaTypeInp.addEventListener('change', function () {
        schemaJsonInp.style.display = schemaTypeInp.value === 'custom' ? '' : 'none';
    });

    function addCustomMetaRow(name, content) {
        var row = document.createElement('div');
        row.style.display = 'flex';
        row.style.gap = '6px';
        row.style.marginBottom = '6px';
        row.innerHTML =
            '<input type="text" name="custom_meta_name[]" class="form-input" style="font-size:12px" placeholder="name or og:property" value="' + (name || '').replace(/"/g, '&quot;') + '">' +
            '<input type="text" name="custom_meta_content[]" class="form-input" style="font-size:12px" placeholder="content" value="' + (content || '').replace(/"/g, '&quot;') + '">' +
            '<button type="button" class="btn btn-ghost btn-sm seo-cm-remove">✕</button>';
        row.querySelector('.seo-cm-remove').addEventListener('click', function () { row.remove(); });
        customMetaRows.appendChild(row);
    }
    document.getElementById('seo-custom-meta-add').addEventListener('click', function () { addCustomMetaRow('', ''); });

    // ── Token insert dropdowns (builder) ──
    document.querySelectorAll('.seo-b-token-insert').forEach(function (sel) {
        sel.addEventListener('change', function () {
            if (!sel.value) return;
            var target = document.getElementById(sel.dataset.target);
            target.value += (target.value ? ' ' : '') + '{{' + sel.value + '}}';
            sel.value = '';
            updateBuilderPreview();
        });
    });
    document.querySelectorAll('.seo-b-field').forEach(function (el) { el.addEventListener('input', updateBuilderPreview); });
    document.getElementById('seo-b-category').addEventListener('change', updateBuilderPreview);
    document.getElementById('seo-b-template').addEventListener('change', updateBuilderPreview);

    function populateTokenDropdowns(scope) {
        var groups = SEO_TOKEN_CATALOG;
        var html = '<option value="">+ Insert token…</option>';
        Object.keys(groups).forEach(function (g) {
            html += '<optgroup label="' + g + '">';
            Object.keys(groups[g]).forEach(function (tok) {
                html += '<option value="' + tok + '">' + groups[g][tok] + '</option>';
            });
            html += '</optgroup>';
        });
        document.querySelectorAll('.seo-b-token-insert').forEach(function (sel) { sel.innerHTML = html; });
    }

    /** Resolve {{token}} placeholders using this row's real values, for the live preview only. */
    function resolveForPreview(str) {
        return (str || '').replace(/\{\{\s*([a-z0-9_]+)\s*\}\}/gi, function (m, key) {
            return (currentTokens[key] !== undefined) ? currentTokens[key] : m;
        });
    }
    function updateBuilderPreview() {
        var params = new URLSearchParams();
        params.set('category', document.getElementById('seo-b-category').value);
        params.set('template', document.getElementById('seo-b-template').value);
        params.set('format', 'webp');
        params.set('width', '800');
        params.set('height', '460');
        ['heading', 'subheading', 'description', 'badge', 'footer', 'bg_color', 'accent_color', 'heading_color'].forEach(function (f) {
            var v = document.getElementById('seo-b-' + f).value;
            if (v) params.set(f, resolveForPreview(v));
        });
        document.getElementById('seo-b-preview').src = '/plugins/previewer-toolkit/render?' + params.toString();
    }
    document.getElementById('seo-builder-use').addEventListener('click', function () {
        var params = new URLSearchParams();
        params.set('category', document.getElementById('seo-b-category').value);
        params.set('template', document.getElementById('seo-b-template').value);
        params.set('format', 'webp');
        params.set('width', '800');
        params.set('height', '460');
        ['heading', 'subheading', 'description', 'badge', 'footer', 'bg_color', 'accent_color', 'heading_color'].forEach(function (f) {
            var v = document.getElementById('seo-b-' + f).value;
            if (v) params.set(f, v); // keep raw {{tokens}} in the stored URL
        });
        ogImgInp.value = '/plugins/previewer-toolkit/render?' + params.toString();
        updateOgPreview();
    });

    function updateTitleCount() {
        var l = titleInp.value.length;
        titleCount.textContent = l;
        titleCount.style.color = l > 60 ? 'var(--color-danger)' : '';
    }
    function updateDescCount() {
        var l = descInp.value.length;
        descCount.textContent = l;
        if (!l) { descStatus.textContent = 'empty'; descStatus.style.color = 'var(--color-danger)'; }
        else if (l < 50) { descStatus.textContent = 'too short'; descStatus.style.color = 'var(--color-warning)'; }
        else if (l > 160) { descStatus.textContent = 'too long'; descStatus.style.color = 'var(--color-warning)'; }
        else { descStatus.textContent = 'ideal length ✓'; descStatus.style.color = 'var(--color-success)'; }
    }
    function updateOgPreview() {
        var url = resolveForPreview(ogImgInp.value.trim());
        if (url) { ogPreviewImg.src = url; ogPreview.style.display = ''; }
        else { ogPreview.style.display = 'none'; }
    }

    titleInp.addEventListener('input', updateTitleCount);
    descInp.addEventListener('input', updateDescCount);
    ogImgInp.addEventListener('input', updateOgPreview);

    function openModal(btn) {
        var type   = btn.dataset.type;
        var id     = btn.dataset.id;
        var name   = btn.dataset.name;
        currentScope = btn.dataset.scope || type;
        nameEl.textContent = name + ' (' + type + ')';
        document.getElementById('seo-input-type').value = type;
        document.getElementById('seo-input-id').value = id;
        form.querySelector('[name="tab"]').value = '<?= e($tab) ?>';

        titleInp.value   = btn.dataset.seoTitle   || '';
        descInp.value    = btn.dataset.seoDesc    || '';
        ogTitleInp.value = btn.dataset.ogTitle    || '';
        ogDescInp.value  = btn.dataset.ogDescription || '';
        ogImgInp.value   = btn.dataset.ogImage    || '';

        // Real values for this row, used only to resolve {{tokens}} in live previews.
        currentTokens = {
            plugin_name: btn.dataset.tokenName || '', plugin_slug: btn.dataset.tokenSlug || '',
            blog_title: btn.dataset.tokenTitle || '', page_title: btn.dataset.tokenTitle || '',
            page_url: btn.dataset.tokenUrl || ''
        };

        // Advanced fields (from seo_meta)
        var meta = {};
        try { meta = JSON.parse(btn.dataset.seoMeta || '{}'); } catch (e) { meta = {}; }
        keywordsInp.value = meta.keywords || '';
        canonicalInp.value = meta.canonical || btn.dataset.canonical || '';
        robotsIndexInp.checked  = meta.robots_index  !== false;
        robotsFollowInp.checked = meta.robots_follow !== false;
        robotsAdvInp.value = meta.robots_advanced || '';
        ogTypeInp.value = meta.og_type || '';
        twitterCardInp.value = meta.twitter_card || '';
        schemaTypeInp.value = meta.schema_type || 'auto';
        schemaJsonInp.value = meta.schema_json || '';
        schemaJsonInp.style.display = schemaTypeInp.value === 'custom' ? '' : 'none';
        customMetaRows.innerHTML = '';
        (meta.custom_meta || []).forEach(function (m) { addCustomMetaRow(m.name || m.property || '', m.content || ''); });
        advPanel.style.display = 'none';
        advToggle.textContent = 'Show advanced';
        builderPanel.style.display = 'none';
        builderToggle.textContent = 'Open builder';
        ['heading','subheading','description','badge','footer'].forEach(function (f) { document.getElementById('seo-b-' + f).value = ''; });
        populateTokenDropdowns(currentScope);

        // Blog posts don't have seo_title — hide those fields
        var hideTitleFields = (type === 'blog');
        titleWrap.style.display  = hideTitleFields ? 'none' : '';
        ogTitleWrap.style.display= hideTitleFields ? 'none' : '';
        ogDescWrap.style.display = hideTitleFields ? 'none' : '';
        canonicalWrap.style.display = '';

        // Static pages don't have separate og_title/og_description fields
        if (type === 'static') {
            ogTitleWrap.style.display = 'none';
            ogDescWrap.style.display  = 'none';
        }

        updateTitleCount();
        updateDescCount();
        updateOgPreview();
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.js-seo-edit').forEach(function (btn) {
        btn.addEventListener('click', function () { openModal(btn); });
    });
    document.getElementById('seo-modal-close').addEventListener('click', closeModal);
    document.getElementById('seo-modal-cancel').addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
})();
</script>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Page SEO Manager', $content, ['section' => 'seo-pages']);
