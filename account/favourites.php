<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireLogin();

$favourites = $db->fetchAll(
    "SELECT p.id, p.slug, p.name, p.description, p.manifest, p.offered
     FROM user_favourites f
     JOIN plugins p ON p.id = f.plugin_id AND p.status = 'active'
     WHERE f.user_id = ?
     ORDER BY f.created_at DESC",
    [$auth->id()]
) ?: [];

$_favCsrf = Security::csrfToken();

ob_start();
?>
<div class="page-hero" style="padding:40px 0 32px">
    <div class="page-hero-inner">
        <div class="section-eyebrow">My Account</div>
        <h1>Saved Tools</h1>
        <p>Tools you have bookmarked for quick access.</p>
    </div>
</div>

<div class="front-container" style="padding-top:32px;padding-bottom:60px">
    <?php if (empty($favourites)): ?>
    <div style="text-align:center;padding:60px 0;color:var(--color-text-muted)">
        <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 16px;display:block;opacity:.35"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        <p style="font-size:15px;margin:0 0 4px;font-weight:500">No saved tools yet</p>
        <p style="font-size:13px;margin:0 0 20px">Click the heart icon on any tool to save it here.</p>
        <a href="/plugins" class="btn btn-primary">Browse Tools</a>
    </div>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
        <?php foreach ($favourites as $plugin):
            $manifest = json_decode($plugin['manifest'] ?? '{}', true) ?? [];
            $icon = $manifest['og_icon'] ?? '';
            $cats = $manifest['categories'] ?? (isset($manifest['category']) && $manifest['category'] ? [$manifest['category']] : []);
        ?>
        <div class="plugin-card" style="position:relative">
            <button class="fav-btn fav-active"
                    data-plugin-id="<?= $plugin['id'] ?>"
                    data-csrf="<?= e($_favCsrf) ?>"
                    title="Remove from saved"
                    style="position:absolute;top:12px;right:12px;background:none;border:none;cursor:pointer;padding:4px;color:var(--color-primary)">
                <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            </button>
            <a href="/plugins/<?= e($plugin['slug']) ?>/" style="text-decoration:none;color:inherit;display:block">
                <?php if ($icon): ?>
                <div class="plugin-icon" style="display:flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:10px;background:var(--color-background);border:1px solid var(--color-border)"><i class="fa-solid fa-<?= e($icon) ?>" style="font-size:18px;color:var(--color-primary)"></i></div>
                <?php else: ?>
                <div class="plugin-icon" style="background:var(--color-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:18px;border-radius:10px;width:44px;height:44px"><?= strtoupper(substr($plugin['name'], 0, 1)) ?></div>
                <?php endif ?>
                <div class="plugin-name" style="margin-top:10px"><?= e($plugin['name']) ?></div>
                <?php if (!empty($cats)): ?>
                <div style="margin:4px 0 6px;display:flex;flex-wrap:wrap;gap:4px">
                    <?php foreach ($cats as $cat): ?>
                    <span class="badge badge-neutral" style="font-size:10px"><?= e($cat) ?></span>
                    <?php endforeach ?>
                </div>
                <?php endif ?>
                <div class="plugin-desc"><?= e(mb_substr($plugin['description'] ?? '', 0, 110)) ?></div>
            </a>
        </div>
        <?php endforeach ?>
    </div>
    <?php endif ?>
</div>

<script>
document.querySelectorAll('.fav-btn').forEach(function(btn){
    btn.addEventListener('click', function(e){
        e.preventDefault();
        var pluginId = btn.dataset.pluginId;
        var csrf     = btn.dataset.csrf;
        var fd = new FormData();
        fd.append('_csrf', csrf);
        fd.append('plugin_id', pluginId);
        fetch('/account/toggle-favourite', { method:'POST', body:fd, credentials:'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(data){
                if (data.action === 'removed') {
                    btn.closest('.plugin-card').style.opacity = '0';
                    btn.closest('.plugin-card').style.transition = 'opacity .3s';
                    setTimeout(function(){ btn.closest('.plugin-card').remove(); }, 300);
                }
            })
            .catch(function(){});
    });
});
</script>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Saved Tools', $content, ['description' => 'Your saved tools and applications.']);
