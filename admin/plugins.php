<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

$logger = Logger::getInstance($db);

// Sync all filesystem plugins to DB
$pluginDirs = glob(PLUGINS_PATH . '/*/plugin.json') ?: [];
foreach ($pluginDirs as $manifestFile) {
    $slug = basename(dirname($manifestFile));
    if ($slug === '_sdk') continue;
    Plugin::sync($db, $slug);
}

// ─── Handle POST ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $postAction = Security::sanitize($_POST['action'] ?? '');

    // ZIP upload
    if ($postAction === 'upload') {
        if (!isset($_FILES['plugin_zip']) || $_FILES['plugin_zip']['error'] !== UPLOAD_ERR_OK) {
            Session::flash('danger', 'Upload failed. Please select a valid ZIP file.');
            redirect('/admin/plugins');
        }

        $file = $_FILES['plugin_zip'];
        if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'zip') {
            Session::flash('danger', 'Only ZIP files are accepted.');
            redirect('/admin/plugins');
        }

        $tmpPath = $file['tmp_name'];
        $result  = Plugin::uploadZip($tmpPath);

        if (!$result['success']) {
            Session::flash('danger', 'Upload failed: ' . $result['error']);
        } else {
            Plugin::sync($db, $result['slug']);
            $logger->info("Plugin uploaded: {$result['slug']}", [], $auth->id());
            Session::flash('success', "Plugin &ldquo;{$result['name']}&rdquo; uploaded. Activate it below.");
        }
        redirect('/admin/plugins');
    }

    $slug = Security::sanitizeSlug($_POST['slug'] ?? '');

    if ($postAction === 'activate' && $slug) {
        $result = Plugin::activate($db, $slug);
        if ($result['success']) {
            $logger->info("Plugin '{$slug}' activated", [], $auth->id());
            Session::flash('success', 'Plugin activated.');
        } else {
            Session::flash('danger', $result['error'] ?? 'Could not activate plugin.');
        }
    }

    if ($postAction === 'deactivate' && $slug) {
        Plugin::deactivate($db, $slug);
        $logger->info("Plugin '{$slug}' deactivated", [], $auth->id());
        Session::flash('success', 'Plugin deactivated.');
    }

    if ($postAction === 'uninstall' && $slug) {
        Plugin::uninstall($db, $slug);
        $logger->info("Plugin '{$slug}' uninstalled", [], $auth->id());
        Session::flash('success', 'Plugin uninstalled and data removed.');
    }

    if ($postAction === 'edit_manifest' && $slug) {
        $row = $db->fetch("SELECT * FROM plugins WHERE slug = ?", [$slug]);
        if ($row) {
            $manifest   = json_decode($row['manifest'] ?? '{}', true) ?? [];
            $newName    = Security::sanitize(trim($_POST['name'] ?? $row['name']));
            $newDesc    = Security::sanitize(trim($_POST['description'] ?? $row['description'] ?? ''));
            $newVer     = Security::sanitize(trim($_POST['version'] ?? $row['version'] ?? '1.0'));
            $newOffered = max(1, (int)($_POST['offered'] ?? $manifest['offered'] ?? 1));
            $newLicense = Security::sanitize(trim($_POST['license'] ?? $manifest['license'] ?? ''));
            $newAuthorUrl = Security::sanitize(trim($_POST['author_url'] ?? $manifest['author_url'] ?? ''));
            $newHomepage  = Security::sanitize(trim($_POST['homepage'] ?? $manifest['homepage'] ?? ''));
            $newMinPhp    = Security::sanitize(trim($_POST['min_php'] ?? $manifest['min_php'] ?? ''));
            $rawCats    = Security::sanitize(trim($_POST['categories'] ?? ''));
            $rawKeys    = Security::sanitize(trim($_POST['keywords'] ?? ''));
            $rawTags    = Security::sanitize(trim($_POST['tags'] ?? ''));
            $newCats    = $rawCats ? array_map('trim', explode(',', $rawCats)) : ($manifest['categories'] ?? []);
            $newKeys    = $rawKeys ? array_map('trim', explode(',', strtolower($rawKeys))) : ($manifest['keywords'] ?? []);
            $newTags    = $rawTags ? array_map('trim', explode(',', strtolower($rawTags))) : ($manifest['tags'] ?? []);
            $newIcon    = trim($_POST['icon'] ?? ($manifest['icon'] ?? ''));

            $manifest['name']        = $newName;
            $manifest['description'] = $newDesc;
            $manifest['version']     = $newVer;
            $manifest['offered']     = $newOffered;
            $manifest['icon']        = $newIcon;
            $manifest['license']     = $newLicense;
            $manifest['author_url']  = $newAuthorUrl;
            $manifest['homepage']    = $newHomepage;
            $manifest['min_php']     = $newMinPhp;
            $manifest['categories']  = array_values(array_filter($newCats));
            $manifest['keywords']    = array_values(array_filter($newKeys));
            $manifest['tags']        = array_values(array_filter($newTags));

            $db->update('plugins', [
                'name'        => $newName,
                'description' => $newDesc,
                'version'     => $newVer,
                'offered'     => $newOffered,
                'manifest'    => json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            ], 'slug = ?', [$slug]);

            // Write back to plugin.json
            $manifestFile = PLUGINS_PATH . '/' . $slug . '/plugin.json';
            if (file_exists($manifestFile)) {
                @file_put_contents($manifestFile, json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            }

            $logger->info("Plugin manifest edited: {$slug}", [], $auth->id());
            Session::flash('success', "Plugin &ldquo;{$newName}&rdquo; updated.");
        }
    }

    redirect('/admin/plugins');
}

$plugins = Plugin::listAll($db);

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Plugins</div>
            <div class="page-subtitle"><?= count($plugins) ?> installed · <?= $db->count('plugins', "status='active'") ?> active</div>
        </div>
    </div>
    <div class="topbar-actions">
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('upload-section').scrollIntoView({behavior:'smooth'})">Upload Plugin</button>
    </div>
</div>

<div class="page-body">

    <?php if (empty($plugins)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="empty-state-icon"></div>
            <h3>No plugins installed</h3>
            <p>Upload a plugin ZIP or place a plugin folder in <code>/plugins/</code> with a <code>plugin.json</code> manifest.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Plugin</th><th>Version</th><th>Author</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($plugins as $plugin):
                    $manifest = json_decode($plugin['manifest'] ?? '{}', true) ?? [];
                ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px">
                            <?php if (!empty($manifest['icon'])): ?>
                            <span style="font-size:18px"><?= $manifest['icon'] ?></span>
                            <?php endif ?>
                            <div>
                                <div style="font-weight:600"><?= e($plugin['name']) ?></div>
                                <div class="text-muted text-sm"><?= e($plugin['description'] ?? '') ?></div>
                                <div style="display:flex;gap:4px;margin-top:4px;flex-wrap:wrap">
                                    <?php $offeredCount = (int)($plugin['offered'] ?? $manifest['offered'] ?? 1); ?>
                                    <span class="badge badge-info"><?= $offeredCount ?> <?= $offeredCount === 1 ? 'tool' : 'tools' ?></span>
                                    <?php if (!empty($manifest['requires_login'])): ?>
                                    <span class="badge badge-warning">Requires Login</span>
                                    <?php endif ?>
                                    <?php if (!empty($manifest['stores_user_data'])): ?>
                                    <span class="badge badge-neutral">Stores User Data</span>
                                    <?php endif ?>
                                    <?php if (!empty($manifest['dashboard_enabled'])): ?>
                                    <span class="badge badge-neutral">Dashboard</span>
                                    <?php endif ?>
                                    <?php if (!empty($manifest['license'])): ?>
                                    <span class="badge badge-neutral"><?= e($manifest['license']) ?></span>
                                    <?php endif ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-neutral">v<?= e($plugin['version']) ?></span></td>
                    <td class="text-muted"><?= e($plugin['author'] ?? '—') ?></td>
                    <td>
                        <span class="badge <?= $plugin['status'] === 'active' ? 'badge-success' : 'badge-neutral' ?>">
                            <?= $plugin['status'] === 'active' ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            <button class="btn btn-ghost btn-sm"
                                onclick="openEditModal(<?= htmlspecialchars(json_encode([
                                    'slug'        => $plugin['slug'],
                                    'name'        => $plugin['name'],
                                    'description' => $plugin['description'] ?? '',
                                    'version'     => $plugin['version'] ?? '1.0',
                                    'offered'     => (int)($plugin['offered'] ?? $manifest['offered'] ?? 1),
                                    'icon'        => $manifest['icon'] ?? '',
                                    'license'     => $manifest['license'] ?? '',
                                    'author_url'  => $manifest['author_url'] ?? '',
                                    'homepage'    => $manifest['homepage'] ?? '',
                                    'min_php'     => $manifest['min_php'] ?? '',
                                    'categories'  => implode(', ', $manifest['categories'] ?? (isset($manifest['category']) ? [$manifest['category']] : [])),
                                    'keywords'    => implode(', ', $manifest['keywords'] ?? []),
                                    'tags'        => implode(', ', $manifest['tags'] ?? []),
                                ]), ENT_QUOTES) ?>)">Edit</button>
                            <?php if ($plugin['status'] === 'active'): ?>
                            <form method="POST" style="display:inline">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="slug" value="<?= e($plugin['slug']) ?>">
                                <input type="hidden" name="action" value="deactivate">
                                <button type="submit" class="btn btn-ghost btn-sm" data-confirm="Deactivate this plugin?">Deactivate</button>
                            </form>
                            <a href="/plugins/<?= e($plugin['slug']) ?>/" class="btn btn-ghost btn-sm">View</a>
                            <?php else: ?>
                            <form method="POST" style="display:inline">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="slug" value="<?= e($plugin['slug']) ?>">
                                <input type="hidden" name="action" value="activate">
                                <button type="submit" class="btn btn-primary btn-sm">Activate</button>
                            </form>
                            <form method="POST" style="display:inline">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="slug" value="<?= e($plugin['slug']) ?>">
                                <input type="hidden" name="action" value="uninstall">
                                <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--color-danger)" data-confirm="Uninstall &ldquo;<?= e($plugin['name']) ?>&rdquo;? This will permanently delete all plugin data.">Uninstall</button>
                            </form>
                            <?php endif ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif ?>

    <!-- Upload Plugin -->
    <div class="card" style="margin-top:20px" id="upload-section">
        <div class="card-header"><span class="card-title">Upload Plugin</span></div>
        <div class="card-body">
            <p style="margin-bottom:16px;color:var(--color-text-secondary);font-size:13px">
                Upload a plugin as a <strong>ZIP file</strong>. The ZIP must contain a valid <code>plugin.json</code> manifest at the root or inside a single folder.
            </p>
            <form method="POST" enctype="multipart/form-data" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="upload">
                <div style="flex:1;min-width:240px">
                    <label class="form-label">Plugin ZIP File</label>
                    <input type="file" name="plugin_zip" accept=".zip" class="form-input" required>
                </div>
                <button type="submit" class="btn btn-primary">Upload &amp; Install</button>
            </form>
        </div>
    </div>

    <!-- Edit Manifest Modal -->
    <div id="edit-manifest-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);align-items:center;justify-content:center">
        <div style="background:var(--color-card);border-radius:var(--radius-medium);border:1px solid var(--color-border);width:100%;max-width:540px;margin:20px;box-shadow:var(--shadow-large)">
            <div style="padding:18px 24px;border-bottom:1px solid var(--color-border);display:flex;align-items:center;justify-content:space-between">
                <div style="font-size:15px;font-weight:700">Edit Plugin Manifest</div>
                <button onclick="closeEditModal()" style="background:none;border:none;cursor:pointer;font-size:22px;color:var(--color-text-muted);line-height:1;padding:0 4px">&times;</button>
            </div>
            <form method="POST" id="edit-manifest-form">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="edit_manifest">
                <input type="hidden" name="slug" id="em-slug">
                <div style="padding:20px 24px;display:flex;flex-direction:column;gap:14px;max-height:70vh;overflow-y:auto">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="em-name" class="form-input" required>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="em-description" class="form-input" rows="2" style="resize:vertical"></textarea>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div class="form-group" style="margin:0">
                            <label class="form-label">Version</label>
                            <input type="text" name="version" id="em-version" class="form-input" placeholder="1.0.0">
                        </div>
                        <div class="form-group" style="margin:0">
                            <label class="form-label">Tools Offered <small class="text-muted">(count)</small></label>
                            <input type="number" name="offered" id="em-offered" class="form-input" min="1" placeholder="1">
                            <div class="form-hint">How many distinct tools/functions this plugin provides.</div>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div class="form-group" style="margin:0">
                            <label class="form-label">License</label>
                            <input type="text" name="license" id="em-license" class="form-input" placeholder="MIT">
                        </div>
                        <div class="form-group" style="margin:0">
                            <label class="form-label">Min PHP</label>
                            <input type="text" name="min_php" id="em-min-php" class="form-input" placeholder="8.0">
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div class="form-group" style="margin:0">
                            <label class="form-label">Author URL</label>
                            <input type="url" name="author_url" id="em-author-url" class="form-input" placeholder="https://example.com">
                        </div>
                        <div class="form-group" style="margin:0">
                            <label class="form-label">Plugin Homepage</label>
                            <input type="url" name="homepage" id="em-homepage" class="form-input" placeholder="https://…/plugins/slug/">
                        </div>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Icon <small class="text-muted">(inline SVG markup)</small></label>
                        <textarea name="icon" id="em-icon" class="form-input" rows="2" style="resize:vertical;font-family:monospace;font-size:12px" placeholder="<svg ...>...</svg>"></textarea>
                        <div class="form-hint">Paste an inline SVG for the plugin icon. Leave blank to use the default icon.</div>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Categories <small class="text-muted">(comma-separated)</small></label>
                        <input type="text" name="categories" id="em-categories" class="form-input" placeholder="Developer Tools, Utilities">
                        <div class="form-hint">Determines which category filters this plugin appears under.</div>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Keywords <small class="text-muted">(comma-separated, lowercase)</small></label>
                        <input type="text" name="keywords" id="em-keywords" class="form-input" placeholder="convert, format, encode">
                        <div class="form-hint">Used by the search engine and site search to find this plugin.</div>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Tags <small class="text-muted">(comma-separated, lowercase)</small></label>
                        <input type="text" name="tags" id="em-tags" class="form-input" placeholder="json-formatter, json-validator, developer-tools">
                        <div class="form-hint">Granular tags for SEO and discovery — aim for 20-40 specific tags.</div>
                    </div>
                </div>
                <div style="padding:16px 24px;border-top:1px solid var(--color-border);display:flex;gap:8px;justify-content:flex-end">
                    <button type="button" onclick="closeEditModal()" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary" data-loading="Saving...">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    function openEditModal(d) {
        document.getElementById('em-slug').value        = d.slug;
        document.getElementById('em-name').value        = d.name;
        document.getElementById('em-description').value = d.description;
        document.getElementById('em-version').value     = d.version;
        document.getElementById('em-offered').value     = d.offered || 1;
        document.getElementById('em-license').value     = d.license || '';
        document.getElementById('em-min-php').value     = d.min_php || '';
        document.getElementById('em-author-url').value  = d.author_url || '';
        document.getElementById('em-homepage').value    = d.homepage || '';
        document.getElementById('em-icon').value        = d.icon || '';
        document.getElementById('em-categories').value  = d.categories;
        document.getElementById('em-keywords').value    = d.keywords;
        document.getElementById('em-tags').value        = d.tags || '';
        var m = document.getElementById('edit-manifest-modal');
        m.style.display = 'flex';
        document.getElementById('em-name').focus();
    }
    function closeEditModal() {
        document.getElementById('edit-manifest-modal').style.display = 'none';
    }
    document.getElementById('edit-manifest-modal').addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeEditModal();
    });
    </script>

    <!-- Manifest reference -->
    <div class="card" style="margin-top:16px">
        <div class="card-header"><span class="card-title">Plugin Manifest Reference</span></div>
        <div class="card-body">
            <pre style="background:var(--color-background);border:1px solid var(--color-border);border-radius:var(--radius-small);padding:16px;font-size:12px;overflow-x:auto"><?= e(json_encode([
                'name'              => 'My Plugin',
                'slug'              => 'my-plugin',
                'version'           => '1.0.0',
                'description'       => 'Short description shown in plugin cards and search results.',
                'author'            => 'Your Name',
                'author_url'        => 'https://yoursite.com',
                'homepage'          => 'https://awantools.site/plugins/my-plugin/',
                'license'           => 'MIT',
                'min_php'           => '8.0',
                'offered'           => 1,
                'icon'              => '<svg ...>...</svg>',
                'requires_login'    => false,
                'stores_user_data'  => false,
                'dashboard_enabled' => false,
                'analytics_enabled' => true,
                'permissions'       => [],
                'categories'        => ['Utilities'],
                'keywords'          => ['keyword1', 'keyword2'],
                'tags'              => ['specific-tag-1', 'specific-tag-2'],
                'meta'              => [
                    'title'       => 'My Plugin — Free Online Tool',
                    'description' => 'SEO meta description for the plugin page.',
                    'og_image'    => '',
                    'twitter_card'=> 'summary',
                    'canonical'   => 'https://awantools.site/plugins/my-plugin/',
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Plugins', $content, ['section' => 'plugins']);
