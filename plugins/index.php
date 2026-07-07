<?php
defined('AWAN') or die();

if ($settings->get('analytics_enabled', '1') === '1' && !isBot()) {
    try {
        $db->insert('analytics_events', [
            'event'      => 'page_view',
            'path'       => '/plugins',
            'user_id'    => $auth->id(),
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    } catch (Exception $e) {}
}

$activePlugins = $db->fetchAll(
    "SELECT id, slug, name, version, description, manifest, offered FROM plugins WHERE status = 'active' ORDER BY name ASC"
) ?: [];

// Total tools offered across all active plugins
$totalOfferedRow = $db->fetch("SELECT COALESCE(SUM(offered),0) AS n FROM plugins WHERE status = 'active'");
$totalOffered    = (int)($totalOfferedRow['n'] ?? 0);

// Extract unique categories from manifest JSON (supports both 'categories' array and legacy 'category' string)
$allCategories = [];
foreach ($activePlugins as $plugin) {
    $manifest = json_decode($plugin['manifest'] ?? '{}', true) ?? [];
    $cats = $manifest['categories'] ?? (isset($manifest['category']) && $manifest['category'] ? [$manifest['category']] : []);
    foreach ($cats as $cat) {
        $cat = trim($cat);
        if ($cat && !in_array($cat, $allCategories, true)) {
            $allCategories[] = $cat;
        }
    }
}
sort($allCategories);

// User favourites for heart button state
$userFavIds = [];
if ($auth->check()) {
    try {
        $favRows    = $db->fetchAll("SELECT plugin_id FROM user_favourites WHERE user_id = ?", [$auth->id()]) ?: [];
        $userFavIds = array_map('intval', array_column($favRows, 'plugin_id'));
    } catch (Exception $e) {}
}
$_favCsrf = Security::csrfToken();

// Fetch plugin ratings (avg + count per plugin)
$_pluginRatings = [];
try {
    $ratingRows = $db->fetchAll(
        "SELECT plugin_id, ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS rating_count FROM plugin_ratings GROUP BY plugin_id"
    ) ?: [];
    foreach ($ratingRows as $rr) {
        $_pluginRatings[(int)$rr['plugin_id']] = $rr;
    }
} catch (Throwable $_e) {}

// User's own ratings
$_myRatings  = [];
$_rateCsrf   = Security::csrfToken();
if ($auth->check()) {
    try {
        $myRateRows = $db->fetchAll("SELECT plugin_id, rating FROM plugin_ratings WHERE user_id = ?", [$auth->id()]) ?: [];
        foreach ($myRateRows as $mr) { $_myRatings[(int)$mr['plugin_id']] = (int)$mr['rating']; }
    } catch (Throwable $_e) {}
}

ob_start();
?>
<div class="page-hero" style="padding:48px 0 40px">
    <div class="page-hero-inner">
        <div class="section-eyebrow">Free Tools</div>
        <h1>Tools &amp; Applications</h1>
        <p>
            All tools are free to use. One account gives you access to everything — no subscriptions, no ads.
        </p>
        <?php if ($totalOffered > 0): ?>
        <div style="margin-top:16px;display:flex;align-items:center;justify-content:flex-start;gap:20px;flex-wrap:wrap">
            <div style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--color-text-muted)">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                <strong style="color:var(--color-text)"><?= $totalOffered ?></strong> tools available
            </div>
            <div style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--color-text-muted)">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                <strong style="color:var(--color-text)"><?= count($activePlugins) ?></strong> <?= count($activePlugins) === 1 ? 'plugin' : 'plugins' ?>
            </div>
            <div style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--color-text-muted)">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                100% free — no subscriptions
            </div>
        </div>
        <?php endif ?>
    </div>
</div>

<div class="front-container" style="padding-top:32px;padding-bottom:64px">

<?php if (empty($activePlugins)): ?>
    <div class="empty-state" style="padding:64px 24px">
        <div class="empty-state-icon"></div>
        <h3>No Tools Available Yet</h3>
        <p>New tools will appear here as they are made available. Check back soon!</p>
        <?php if (!$auth->check()): ?>
        <a href="/register" class="btn btn-primary" style="margin-top:16px">Create a Free Account</a>
        <?php endif ?>
    </div>
<?php else: ?>

    <!-- Search + Category Filter Bar -->
    <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:28px;align-items:center">
        <!-- Keyword search -->
        <div style="display:flex;align-items:center;gap:8px;flex:1;min-width:200px;max-width:320px;background:var(--color-surface);border:1px solid var(--color-border);border-radius:var(--radius-medium);padding:0 12px;height:36px">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="flex-shrink:0;opacity:.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="plugin-search" placeholder="Search tools…"
                   style="border:none;background:transparent;outline:none;font-size:13px;width:100%;color:var(--color-text)"
                   oninput="liveFilter()"
                   autocomplete="off">
        </div>

        <!-- Category buttons -->
        <?php if (!empty($allCategories)): ?>
        <div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center">
            <button class="btn btn-sm plugin-cat-btn plugin-cat-active" data-cat="all" onclick="setCat('all',this)">All</button>
            <?php foreach ($allCategories as $cat): ?>
            <button class="btn btn-sm btn-ghost plugin-cat-btn" data-cat="<?= e($cat) ?>" onclick="setCat(<?= htmlspecialchars(json_encode($cat), ENT_QUOTES) ?>,this)"><?= e($cat) ?></button>
            <?php endforeach ?>
        </div>
        <?php endif ?>
    </div>

    <!-- Grid -->
    <div id="plugins-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:20px">
        <?php foreach ($activePlugins as $plugin):
            $manifest    = json_decode($plugin['manifest'] ?? '{}', true) ?? [];
            $ogIcon      = $manifest['og_icon'] ?? 'puzzle-piece';
            $categories  = $manifest['categories'] ?? (isset($manifest['category']) && $manifest['category'] ? [$manifest['category']] : []);
            $keywords    = array_map('strtolower', $manifest['keywords'] ?? []);
            $tags        = array_map('strtolower', $manifest['tags'] ?? []);
            $allSearchTerms = array_unique(array_merge($keywords, $tags));
            $requiresAuth = !empty($manifest['requires_login']);
            $catAttr     = implode('|', $categories); // pipe-separated for JS filter
            $offeredCount = (int)($plugin['offered'] ?? $manifest['offered'] ?? 1);
        ?>
        <a href="/plugins/<?= e($plugin['slug']) ?>/"
           class="card plugin-card"
           data-cat="<?= e($catAttr) ?>"
           data-name="<?= e(strtolower($plugin['name'])) ?>"
           data-keywords="<?= e(implode(' ', $allSearchTerms)) ?>"
           data-desc="<?= e(strtolower($plugin['description'] ?? '')) ?>"
           style="text-decoration:none;color:inherit;display:flex;flex-direction:column;transition:border-color 0.15s,box-shadow 0.15s"
           onmouseover="this.style.borderColor='var(--color-primary)';this.style.boxShadow='var(--shadow-small)'"
           onmouseout="this.style.borderColor='';this.style.boxShadow=''">
            <div class="card-body" style="flex:1">
                <div style="display:flex;align-items:flex-start;gap:14px">
                    <div style="width:48px;height:48px;background:var(--color-primary-light);border-radius:var(--radius-small);
                                display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
                        <i class="fa-solid fa-<?= e($ogIcon) ?>" style="font-size:22px;color:var(--color-primary)"></i>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:700;font-size:15px;margin-bottom:4px"><?= e($plugin['name']) ?></div>
                        <?php foreach (array_slice($categories, 0, 2) as $cat): ?>
                        <span class="badge badge-secondary" style="margin-bottom:6px;margin-right:4px"><?= e($cat) ?></span>
                        <?php endforeach ?>
                        <div class="text-muted text-sm" style="line-height:1.5;margin-top:4px"><?= e($plugin['description'] ?? '') ?></div>
                    </div>
                </div>
            </div>
            <?php $isFav = in_array((int)($plugin['id'] ?? 0), $userFavIds); ?>
            <div class="card-footer" style="display:flex;align-items:center;justify-content:space-between">
                <span class="text-muted" style="font-size:11px">
                    v<?= e($plugin['version'] ?? '1.0') ?>
                    <?php if ($offeredCount > 1): ?>
                    &middot; <span style="color:var(--color-primary);font-weight:600"><?= $offeredCount ?> tools</span>
                    <?php endif ?>
                    <?php
                    $_pr = $_pluginRatings[(int)$plugin['id']] ?? null;
                    if ($_pr && (int)$_pr['rating_count'] > 0):
                        $_avg = (float)$_pr['avg_rating'];
                        $_cnt = (int)$_pr['rating_count'];
                    ?>
                    &middot; <span style="color:#f59e0b;font-size:11px" title="<?= number_format($_avg,1) ?> / 5 from <?= $_cnt ?> rating<?= $_cnt !== 1 ? 's' : '' ?>">
                        <?php for ($__i = 1; $__i <= 5; $__i++): ?>
                            <?= $__i <= round($_avg) ? '&#9733;' : '&#9734;' ?>
                        <?php endfor ?>
                        <span style="color:var(--color-text-muted)">(<?= $_cnt ?>)</span>
                    </span>
                    <?php endif ?>
                </span>
                <div style="display:flex;align-items:center;gap:8px">
                    <?php if ($auth->check()): ?>
                    <button type="button"
                            data-plugin-id="<?= (int)$plugin['id'] ?>"
                            onclick="event.stopPropagation();event.preventDefault();toggleFav(this)"
                            title="<?= $isFav ? 'Remove from favourites' : 'Save to favourites' ?>"
                            style="background:none;border:none;cursor:pointer;padding:3px;line-height:1;display:flex;align-items:center;color:<?= $isFav ? '#ef4444' : 'var(--color-text-muted)' ?>">
                        <svg width="15" height="15" fill="<?= $isFav ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    </button>
                    <?php endif ?>
                    <?php if ($requiresAuth && !$auth->check()): ?>
                    <span class="badge badge-warning" style="font-size:10px">Login Required</span>
                    <?php else: ?>
                    <span style="font-size:12px;color:var(--color-primary);font-weight:600">Open &rarr;</span>
                    <?php endif ?>
                </div>
            </div>
        </a>
        <?php endforeach ?>
    </div>

    <style>
    .plugin-cat-btn.plugin-cat-active {
        background: var(--color-primary);
        color: #fff;
        border-color: var(--color-primary);
    }
    .plugin-cat-btn.plugin-cat-active:hover {
        background: var(--color-primary-hover, #4f46e5);
        border-color: var(--color-primary-hover, #4f46e5);
    }
    </style>

    <div id="plugins-empty" style="display:none;text-align:center;padding:48px 24px;color:var(--color-text-muted)">
        <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="opacity:.4;margin-bottom:12px"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <div>No tools match your search.</div>
        <button onclick="clearFilter()" class="btn btn-ghost btn-sm" style="margin-top:12px">Clear Filter</button>
    </div>

    <?php if (!$auth->check()): ?>
    <div class="card" style="margin-top:40px;background:var(--color-primary-light);border-color:var(--color-primary)">
        <div class="card-body" style="text-align:center;padding:32px">
            <div style="font-size:18px;font-weight:700;margin-bottom:8px">One account for everything</div>
            <div class="text-muted" style="margin-bottom:20px">Create a free account to unlock all tools and save your data.</div>
            <a href="/register" class="btn btn-primary">Get Started — It's Free</a>
            <span style="margin:0 12px;color:var(--color-text-muted)">&middot;</span>
            <a href="/login" class="btn btn-ghost">Sign In</a>
        </div>
    </div>
    <?php endif ?>

<?php endif ?>
</div>

<script>
var _activeCat = 'all';

function setCat(cat, btn) {
    _activeCat = cat;
    document.querySelectorAll('.plugin-cat-btn').forEach(function(b) {
        b.classList.remove('plugin-cat-active');
        b.classList.add('btn-ghost');
    });
    btn.classList.add('plugin-cat-active');
    btn.classList.remove('btn-ghost');
    liveFilter();
}

function liveFilter() {
    var q = (document.getElementById('plugin-search').value || '').toLowerCase().trim();
    var cards = document.querySelectorAll('.plugin-card');
    var visible = 0;
    cards.forEach(function(card) {
        var matchCat = (_activeCat === 'all' || card.dataset.cat.split('|').indexOf(_activeCat) !== -1);
        var matchQ   = !q ||
            card.dataset.name.indexOf(q) !== -1 ||
            card.dataset.desc.indexOf(q) !== -1 ||
            card.dataset.keywords.indexOf(q) !== -1;
        var show = matchCat && matchQ;
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    document.getElementById('plugins-empty').style.display = visible === 0 ? 'block' : 'none';
}

function clearFilter() {
    document.getElementById('plugin-search').value = '';
    _activeCat = 'all';
    document.querySelectorAll('.plugin-cat-btn').forEach(function(b, i) {
        b.classList.toggle('plugin-cat-active', i === 0);
        b.classList.toggle('btn-ghost', i !== 0);
    });
    liveFilter();
}

var _favCsrf = '<?= e($_favCsrf) ?>';
function toggleFav(btn) {
    var id = btn.dataset.pluginId;
    var fd = new FormData();
    fd.append('plugin_id', id);
    fd.append('_csrf', _favCsrf);
    btn.style.opacity = '0.5';
    fetch('/account/toggle-favourite', {method:'POST', body:fd, credentials:'same-origin'})
        .then(function(r){ return r.json(); })
        .then(function(d) {
            btn.style.opacity = '';
            if (d.error) { if (d.logged_in === false) window.location = '/login'; return; }
            var svg = btn.querySelector('svg');
            if (d.favourited) {
                svg.setAttribute('fill', 'currentColor');
                btn.style.color = '#ef4444';
                btn.title = 'Remove from favourites';
            } else {
                svg.setAttribute('fill', 'none');
                btn.style.color = 'var(--color-text-muted)';
                btn.title = 'Save to favourites';
            }
        })
        .catch(function() { btn.style.opacity = ''; });
}
</script>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Tools & Applications', $content, [
    'description' => 'Browse all free tools and applications on Awan Tools. One account gives you access to everything.',
]);
