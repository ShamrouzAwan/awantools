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
    <style>
    /* ── Plugin Card Grid ───────────────────────────────────────────── */
    @keyframes pcFadeUp {
        from { opacity: 0; transform: translateY(16px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    #plugins-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 22px;
        align-items: stretch;
    }
    .pc-card {
        display: flex;
        flex-direction: column;
        background: var(--color-card, #fff);
        border: 1px solid var(--color-border, #e2e8f0);
        border-radius: 14px;
        text-decoration: none;
        color: inherit;
        overflow: hidden;
        position: relative;
        min-height: 220px;
        transition:
            transform 0.22s cubic-bezier(.34,1.36,.64,1),
            box-shadow 0.22s ease,
            border-color 0.18s ease;
        animation: pcFadeUp 0.38s ease both;
        animation-delay: calc(var(--pc-i, 0) * 40ms);
        will-change: transform;
    }
    .pc-card:hover {
        transform: translateY(-5px);
        border-color: var(--color-primary, #6366f1);
        box-shadow: 0 8px 28px rgba(99,102,241,.13), 0 2px 8px rgba(0,0,0,.05);
    }
    .pc-card:active {
        transform: translateY(-1px) scale(0.985);
        transition-duration: 0.08s;
    }
    /* Head */
    .pc-card-head {
        padding: 20px 20px 0;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }
    .pc-icon {
        width: 52px; height: 52px;
        border-radius: 12px;
        background: var(--color-primary-light, #eef2ff);
        display: flex; align-items: center; justify-content: center;
        font-size: 22px;
        color: var(--color-primary, #6366f1);
        flex-shrink: 0;
        transition: background 0.2s ease, color 0.2s ease, transform 0.22s cubic-bezier(.34,1.56,.64,1);
    }
    .pc-card:hover .pc-icon {
        background: var(--color-primary, #6366f1);
        color: #fff;
        transform: scale(1.1) rotate(-4deg);
    }
    .pc-fav {
        background: none; border: none; cursor: pointer;
        padding: 5px; color: var(--color-text-muted, #94a3b8);
        border-radius: 7px; line-height: 1; flex-shrink: 0;
        transition: color 0.15s, background 0.15s;
    }
    .pc-fav:hover  { background: var(--color-background, #f1f5f9); color: #ef4444; }
    .pc-fav.is-fav { color: #ef4444; }
    /* Body */
    .pc-card-body {
        padding: 14px 20px 0;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 7px;
    }
    .pc-name {
        font-size: 15px; font-weight: 700;
        color: var(--color-text, #0f172a);
        letter-spacing: -0.2px; line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .pc-cats { display: flex; flex-wrap: wrap; gap: 4px; }
    .pc-cat {
        font-size: 10px; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.04em;
        padding: 2px 7px; border-radius: 20px;
        background: var(--color-background, #f1f5f9);
        color: var(--color-text-secondary, #64748b);
        border: 1px solid var(--color-border, #e2e8f0);
        transition: background 0.15s, color 0.15s, border-color 0.15s;
    }
    .pc-card:hover .pc-cat {
        background: var(--color-primary-light, #eef2ff);
        color: var(--color-primary, #6366f1);
        border-color: rgba(99,102,241,.2);
    }
    .pc-desc {
        font-size: 12.5px;
        color: var(--color-text-secondary, #64748b);
        line-height: 1.55;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        flex: 1;
    }
    /* Footer */
    .pc-card-foot {
        margin-top: 14px;
        padding: 10px 20px 14px;
        border-top: 1px solid var(--color-border, #e2e8f0);
        display: flex; align-items: center;
        justify-content: space-between; gap: 8px;
        transition: border-color 0.18s;
    }
    .pc-card:hover .pc-card-foot { border-color: rgba(99,102,241,.15); }
    .pc-meta { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; min-width: 0; }
    .pc-tools {
        font-size: 11px; font-weight: 600;
        color: var(--color-primary, #6366f1);
        background: var(--color-primary-light, #eef2ff);
        padding: 2px 8px; border-radius: 20px;
    }
    .pc-rating {
        font-size: 11px; color: #f59e0b;
        display: flex; align-items: center; gap: 2px;
        white-space: nowrap;
    }
    .pc-rating-count { color: var(--color-text-muted, #94a3b8); margin-left: 2px; }
    .pc-ver { font-size: 10px; color: var(--color-text-muted, #94a3b8); }
    .pc-open {
        font-size: 12px; font-weight: 600;
        color: var(--color-primary, #6366f1);
        white-space: nowrap;
        display: flex; align-items: center; gap: 3px;
        opacity: 0.65;
        transition: opacity 0.15s, gap 0.18s;
    }
    .pc-card:hover .pc-open { opacity: 1; gap: 6px; }
    .pc-login-req {
        font-size: 10px; font-weight: 600;
        padding: 2px 7px; border-radius: 20px;
        background: var(--color-warning-light, #fef3c7);
        color: #92400e;
    }
    /* Category filter active state */
    .plugin-cat-btn.plugin-cat-active {
        background: var(--color-primary, #6366f1);
        color: #fff;
        border-color: var(--color-primary, #6366f1);
    }
    .plugin-cat-btn.plugin-cat-active:hover {
        background: var(--color-primary-hover, #4f46e5);
        border-color: var(--color-primary-hover, #4f46e5);
    }
    /* Dark mode */
    [data-theme="dark"] .pc-card:hover {
        box-shadow: 0 8px 28px rgba(99,102,241,.22), 0 2px 8px rgba(0,0,0,.35);
    }
    </style>

    <div id="plugins-grid">
        <?php foreach ($activePlugins as $_pcIdx => $plugin):
            $manifest       = json_decode($plugin['manifest'] ?? '{}', true) ?? [];
            $ogIcon         = $manifest['og_icon'] ?? 'puzzle-piece';
            $categories     = $manifest['categories'] ?? (isset($manifest['category']) && $manifest['category'] ? [$manifest['category']] : []);
            $keywords       = array_map('strtolower', $manifest['keywords'] ?? []);
            $tags           = array_map('strtolower', $manifest['tags'] ?? []);
            $allSearchTerms = array_unique(array_merge($keywords, $tags));
            $requiresAuth   = !empty($manifest['requires_login']);
            $catAttr        = implode('|', $categories);
            $offeredCount   = (int)($plugin['offered'] ?? $manifest['offered'] ?? 1);
            $isFav          = in_array((int)($plugin['id'] ?? 0), $userFavIds);
            $_pr            = $_pluginRatings[(int)$plugin['id']] ?? null;
            $hasRating      = $_pr && (int)$_pr['rating_count'] > 0;
        ?>
        <a href="/plugins/<?= e($plugin['slug']) ?>/"
           class="pc-card"
           style="--pc-i:<?= min($_pcIdx, 20) ?>"
           data-cat="<?= e($catAttr) ?>"
           data-name="<?= e(strtolower($plugin['name'])) ?>"
           data-keywords="<?= e(implode(' ', $allSearchTerms)) ?>"
           data-desc="<?= e(strtolower($plugin['description'] ?? '')) ?>">

            <div class="pc-card-head">
                <div class="pc-icon">
                    <i class="fa-solid fa-<?= e($ogIcon) ?>"></i>
                </div>
                <?php if ($auth->check()): ?>
                <button type="button"
                        class="pc-fav<?= $isFav ? ' is-fav' : '' ?>"
                        data-plugin-id="<?= (int)$plugin['id'] ?>"
                        onclick="event.stopPropagation();event.preventDefault();toggleFav(this)"
                        title="<?= $isFav ? 'Remove from favourites' : 'Save to favourites' ?>">
                    <svg width="15" height="15" fill="<?= $isFav ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                </button>
                <?php endif ?>
            </div>

            <div class="pc-card-body">
                <div class="pc-name"><?= e($plugin['name']) ?></div>
                <?php if (!empty($categories)): ?>
                <div class="pc-cats">
                    <?php foreach (array_slice($categories, 0, 2) as $_pcat): ?>
                    <span class="pc-cat"><?= e($_pcat) ?></span>
                    <?php endforeach ?>
                </div>
                <?php endif ?>
                <div class="pc-desc"><?= e($plugin['description'] ?? '') ?></div>
            </div>

            <div class="pc-card-foot">
                <div class="pc-meta">
                    <?php if ($offeredCount > 1): ?>
                    <span class="pc-tools"><?= $offeredCount ?> tools</span>
                    <?php endif ?>
                    <?php if ($hasRating): ?>
                    <span class="pc-rating" title="<?= number_format((float)$_pr['avg_rating'],1) ?>/5 from <?= (int)$_pr['rating_count'] ?> rating<?= (int)$_pr['rating_count'] !== 1 ? 's' : '' ?>">
                        <?php for ($__i=1;$__i<=5;$__i++): ?><?= $__i<=round((float)$_pr['avg_rating'])? '★':'☆' ?><?php endfor ?>
                        <span class="pc-rating-count">(<?= (int)$_pr['rating_count'] ?>)</span>
                    </span>
                    <?php else: ?>
                    <span class="pc-ver">v<?= e($plugin['version'] ?? '1.0') ?></span>
                    <?php endif ?>
                </div>
                <?php if ($requiresAuth && !$auth->check()): ?>
                <span class="pc-login-req">Login required</span>
                <?php else: ?>
                <span class="pc-open">Open <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
                <?php endif ?>
            </div>

        </a>
        <?php endforeach ?>
    </div>


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
    var cards = document.querySelectorAll('.pc-card');
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
