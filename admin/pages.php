<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

$logger = Logger::getInstance($db);
$action = Security::sanitize($_GET['action'] ?? 'list');
$id     = (int)($_GET['id'] ?? 0);

// ─── Slug generator helper ────────────────────────────────────────────────────
function generateSlug(string $title): string {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9\s\-]/', '', $slug);
    $slug = preg_replace('/[\s\-]+/', '-', $slug);
    return trim($slug, '-');
}

// ─── POST Handler ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $postAction = Security::sanitize($_POST['action'] ?? '');

    if ($postAction === 'save') {
        $pageId    = (int)($_POST['page_id'] ?? 0);
        $title     = trim(Security::sanitize($_POST['title'] ?? ''));
        $slug      = trim(Security::sanitizeSlug($_POST['slug'] ?? ''));
        $content   = $_POST['content'] ?? '';
        $seoTitle    = trim(Security::sanitize($_POST['seo_title'] ?? ''));
        $seoDesc     = trim(Security::sanitize($_POST['seo_desc'] ?? ''));
        $ogTitle     = trim(Security::sanitize($_POST['og_title'] ?? ''));
        $ogDesc      = trim(Security::sanitize($_POST['og_description'] ?? ''));
        $ogImage     = trim(Security::sanitize($_POST['og_image'] ?? ''));
        $canonical   = trim(Security::sanitize($_POST['canonical_url'] ?? ''));
        $showInNav   = !empty($_POST['show_in_nav']) ? 1 : 0;
        $showInFooter= !empty($_POST['show_in_footer']) ? 1 : 0;
        $status      = in_array($_POST['status'] ?? '', ['draft','published','private']) ? $_POST['status'] : 'draft';

        // Allow safe HTML in content
        $content = strip_tags($content, '<p><br><b><i><u><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><a><span><div><table><thead><tbody><tr><th><td><img><hr>');

        if (!$title) {
            Session::flash('danger', 'Title is required.');
            redirect('/admin/pages?action=' . ($pageId ? 'edit&id=' . $pageId : 'new'));
        }

        if (!$slug) $slug = generateSlug($title);

        // Ensure slug uniqueness
        $existing = $db->fetch("SELECT id FROM pages WHERE slug = ? AND id != ?", [$slug, $pageId]);
        if ($existing) {
            $slug .= '-' . time();
        }

        $pageData = [
            'title'          => $title,
            'slug'           => $slug,
            'content'        => $content,
            'seo_title'      => $seoTitle ?: null,
            'seo_desc'       => $seoDesc ?: null,
            'og_title'       => $ogTitle ?: null,
            'og_description' => $ogDesc ?: null,
            'og_image'       => $ogImage ?: null,
            'canonical_url'  => $canonical ?: null,
            'show_in_nav'    => $showInNav,
            'show_in_footer' => $showInFooter,
            'status'         => $status,
        ];
        if ($pageId) {
            $db->update('pages', array_merge($pageData, ['updated_at' => date('Y-m-d H:i:s')]), 'id = ?', [$pageId]);
            $logger->info("Page updated: /{$slug}", [], $auth->id());
            Session::flash('success', 'Page updated.');
        } else {
            $db->insert('pages', array_merge($pageData, ['author_id' => $auth->id(), 'created_at' => date('Y-m-d H:i:s')]));
            $logger->info("Page created: /{$slug}", [], $auth->id());
            Session::flash('success', 'Page created.');
        }
        redirect('/admin/pages');
    }

    if ($postAction === 'delete') {
        $pageId = (int)($_POST['page_id'] ?? 0);
        $page   = $db->fetch("SELECT slug, page_type FROM pages WHERE id = ?", [$pageId]);
        if ($page) {
            if (in_array($page['page_type'] ?? 'page', ['system', 'legal'])) {
                Session::flash('danger', 'System and legal pages cannot be deleted. You can edit their visibility instead.');
            } else {
                $db->delete('pages', 'id = ?', [$pageId]);
                $logger->info("Page deleted: /{$page['slug']}", [], $auth->id());
                Session::flash('success', 'Page deleted.');
            }
        }
        redirect('/admin/pages');
    }

    redirect('/admin/pages');
}

// ─── New / Edit Form ──────────────────────────────────────────────────────────
if ($action === 'new' || ($action === 'edit' && $id)) {
    $page = $action === 'edit' ? $db->fetch("SELECT * FROM pages WHERE id = ?", [$id]) : null;
    if ($action === 'edit' && !$page) {
        Session::flash('danger', 'Page not found.');
        redirect('/admin/pages');
    }

    $isNew   = $page === null;
    $formTitle = $isNew ? 'New Page' : 'Edit Page';

    ob_start();
    ?>
    <link href="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.js"></script>
    <style>
    #page-editor .ql-container { font-size:15px; min-height:400px; border-radius:0 0 var(--radius-small) var(--radius-small); }
    #page-editor .ql-toolbar  { border-radius:var(--radius-small) var(--radius-small) 0 0; background:var(--color-background); }
    .form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    @media (max-width: 640px) { .form-grid-2 { grid-template-columns:1fr; } }
    </style>

    <div class="page-header">
        <div class="page-header-left">
            <div>
                <div class="page-title"><?= e($formTitle) ?></div>
                <div class="page-subtitle"><?= $isNew ? 'Create a new CMS page' : 'Editing: ' . e($page['slug']) ?></div>
            </div>
        </div>
        <div class="topbar-actions">
            <a href="/admin/pages" class="btn btn-secondary btn-sm">Cancel</a>
            <?php if (!$isNew): ?>
            <a href="/<?= e($page['slug']) ?>" target="_blank" class="btn btn-ghost btn-sm">View Page ↗</a>
            <?php endif ?>
        </div>
    </div>

    <div class="page-body">
        <form method="POST" id="page-form">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="page_id" value="<?= $isNew ? 0 : $page['id'] ?>">
            <input type="hidden" name="content" id="content-hidden">

            <div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start">

                <!-- Main editor -->
                <div style="display:flex;flex-direction:column;gap:16px">
                    <div class="card">
                        <div class="card-body" style="display:flex;flex-direction:column;gap:16px">
                            <div>
                                <label class="form-label">Page Title <span style="color:var(--color-danger)">*</span></label>
                                <input type="text" name="title" id="page-title" class="form-input"
                                       placeholder="About Us"
                                       value="<?= e($page['title'] ?? '') ?>"
                                       style="font-size:18px;font-weight:600" required>
                            </div>
                            <div>
                                <label class="form-label">Slug (URL path)</label>
                                <div style="display:flex;align-items:center;gap:8px">
                                    <span style="color:var(--color-text-muted);font-size:13px">/</span>
                                    <input type="text" name="slug" id="page-slug" class="form-input"
                                           placeholder="about-us"
                                           value="<?= e($page['slug'] ?? '') ?>"
                                           style="font-family:monospace">
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Content</label>
                                <div id="page-editor"><?= $page['content'] ?? '' ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div style="display:flex;flex-direction:column;gap:16px">
                    <div class="card">
                        <div class="card-header"><span class="card-title">Publish</span></div>
                        <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
                            <div>
                                <label class="form-label">Status</label>
                                <select name="status" class="form-input">
                                    <?php foreach (['draft','published','private'] as $s): ?>
                                    <option value="<?= $s ?>" <?= ($page['status'] ?? 'draft') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <?php if (!$isNew): ?>
                            <div class="text-sm text-muted">
                                Created: <?= fdate($page['created_at']) ?><br>
                                <?php if ($page['updated_at']): ?>Updated: <?= fdate($page['updated_at']) ?><?php endif ?>
                            </div>
                            <?php endif ?>
                        </div>
                        <div class="card-footer" style="display:flex;flex-direction:column;gap:8px">
                            <button type="submit" class="btn btn-primary w-full">
                                <?= $isNew ? 'Create Page' : 'Update Page' ?>
                            </button>
                            <?php if (!$isNew): ?>
                            <form method="POST">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="page_id" value="<?= $page['id'] ?>">
                                <button type="submit" class="btn btn-ghost btn-sm w-full" style="color:var(--color-danger)" data-confirm="Delete this page permanently?">Delete Page</button>
                            </form>
                            <?php endif ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><span class="card-title">SEO</span></div>
                        <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
                            <div>
                                <label class="form-label">SEO Title</label>
                                <input type="text" name="seo_title" class="form-input"
                                       placeholder="Leave blank to use page title"
                                       value="<?= e($page['seo_title'] ?? '') ?>">
                            </div>
                            <div>
                                <label class="form-label">Meta Description</label>
                                <textarea name="seo_desc" class="form-input" rows="3"
                                          placeholder="Brief description for search engines…" style="resize:vertical"><?= e($page['seo_desc'] ?? '') ?></textarea>
                            </div>
                            <div>
                                <label class="form-label">Canonical URL</label>
                                <input type="url" name="canonical_url" class="form-input"
                                       placeholder="https://example.com/page-slug"
                                       value="<?= e($page['canonical_url'] ?? '') ?>">
                                <div class="form-hint">Leave blank to auto-generate from site URL + slug.</div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><span class="card-title">Open Graph</span></div>
                        <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
                            <div>
                                <label class="form-label">OG Title</label>
                                <input type="text" name="og_title" class="form-input"
                                       placeholder="Leave blank to use SEO title"
                                       value="<?= e($page['og_title'] ?? '') ?>">
                            </div>
                            <div>
                                <label class="form-label">OG Description</label>
                                <textarea name="og_description" class="form-input" rows="2"
                                          placeholder="Leave blank to use meta description…" style="resize:vertical"><?= e($page['og_description'] ?? '') ?></textarea>
                            </div>
                            <div>
                                <label class="form-label">OG Image URL</label>
                                <input type="url" name="og_image" class="form-input"
                                       placeholder="https://example.com/og-image.jpg"
                                       value="<?= e($page['og_image'] ?? '') ?>">
                                <div class="form-hint">Recommended: 1200×630 px.</div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><span class="card-title">Visibility</span></div>
                        <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
                            <label class="form-check" style="margin-bottom:0">
                                <input type="checkbox" name="show_in_nav" value="1" <?= !empty($page['show_in_nav']) ? 'checked' : '' ?>>
                                <span class="form-check-label">Show in header navigation</span>
                            </label>
                            <label class="form-check" style="margin-bottom:0">
                                <input type="checkbox" name="show_in_footer" value="1" <?= !empty($page['show_in_footer']) ? 'checked' : '' ?>>
                                <span class="form-check-label">Show in footer links</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
    // Auto-generate slug from title (new pages only)
    const titleInput = document.getElementById('page-title');
    const slugInput  = document.getElementById('page-slug');
    <?php if ($isNew): ?>
    titleInput.addEventListener('input', () => {
        slugInput.value = titleInput.value.toLowerCase()
            .replace(/[^a-z0-9\s\-]/g, '')
            .replace(/[\s\-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    });
    <?php endif ?>

    const quill = new Quill('#page-editor', {
        theme: 'snow',
        modules: { toolbar: [
            [{ header: [1,2,3,4,false] }],
            ['bold','italic','underline','strike'],
            [{ list:'ordered' },{ list:'bullet' }],
            ['blockquote','link','image'],
            [{ align:[] }],
            ['clean']
        ]}
    });

    document.getElementById('page-form').addEventListener('submit', () => {
        document.getElementById('content-hidden').value = quill.getSemanticHTML();
    });
    </script>
    <?php
    $content = ob_get_clean();
    require THEMES_PATH . '/default/templates/admin.php';
    render_admin($formTitle, $content, ['section' => 'pages']);
    return;
}

// ─── List View ─────────────────────────────────────────────────────────────────
$pages = $db->fetchAll("SELECT * FROM pages ORDER BY COALESCE(sort_order,999), created_at DESC");

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Pages</div>
            <div class="page-subtitle"><?= count($pages) ?> page<?= count($pages) !== 1 ? 's' : '' ?></div>
        </div>
    </div>
    <div class="topbar-actions">
        <a href="/admin/pages?action=new" class="btn btn-primary btn-sm">+ New Page</a>
    </div>
</div>

<div class="page-body">
    <?php if (empty($pages)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="empty-state-icon"></div>
            <h3>No pages yet</h3>
            <p>Create your first CMS page — About, Privacy Policy, Terms, etc.</p>
            <a href="/admin/pages?action=new" class="btn btn-primary btn-sm" style="margin-top:12px">Create Page</a>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Slug</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Footer</th>
                        <th style="width:140px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pages as $p):
                    $statusClass = match($p['status']) {
                        'published' => 'badge-success',
                        'draft'     => 'badge-neutral',
                        'private'   => 'badge-warning',
                        default     => 'badge-neutral'
                    };
                    $pageType  = $p['page_type'] ?? 'page';
                    $typeClass = match($pageType) {
                        'system' => 'badge-primary',
                        'legal'  => 'badge-warning',
                        default  => 'badge-neutral',
                    };
                    $isSystem = in_array($pageType, ['system','legal']);
                ?>
                <tr>
                    <td style="font-weight:500;font-size:13px">
                        <?= e($p['title']) ?>
                        <?php if ($isSystem): ?><svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" title="System page — cannot be deleted" style="color:var(--color-text-muted);vertical-align:middle;margin-left:3px"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg><?php endif ?>
                    </td>
                    <td>
                        <code style="font-size:11px;background:var(--color-background);padding:2px 6px;border-radius:3px">/<?= e($p['slug']) ?></code>
                    </td>
                    <td><span class="badge <?= $typeClass ?>"><?= ucfirst($pageType) ?></span></td>
                    <td><span class="badge <?= $statusClass ?>"><?= e($p['status']) ?></span></td>
                    <td style="text-align:center">
                        <?php if ($p['show_in_footer'] ?? 0): ?>
                        <svg width="14" height="14" fill="none" stroke="var(--color-success)" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                        <?php else: ?>
                        <span style="color:var(--color-border)">—</span>
                        <?php endif ?>
                    </td>
                    <td>
                        <a href="/admin/pages?action=edit&id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                        <?php if ($p['status'] === 'published'): ?>
                        <a href="/<?= e($p['slug']) ?>" target="_blank" class="btn btn-ghost btn-sm">View ↗</a>
                        <?php endif ?>
                    </td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif ?>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Pages', $content, ['section' => 'pages']);
