<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

function uploadBlogImage(array $file): array {
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $mime = @mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowedMimes)) {
        return ['ok' => false, 'error' => 'Invalid file type. Allowed: JPEG, PNG, GIF, WebP.'];
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'Image too large. Max 5 MB.'];
    }
    $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $ext      = $extMap[$mime] ?? 'jpg';
    $filename = 'blog_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dir      = AWAN_ROOT . '/storage/uploads/';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    if (!@move_uploaded_file($file['tmp_name'], $dir . $filename)) {
        return ['ok' => false, 'error' => 'Failed to save file. Check directory permissions.'];
    }
    return ['ok' => true, 'url' => '/storage/uploads/' . $filename];
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = Security::sanitize($_POST['action'] ?? '');

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db->delete('blog_post_categories', 'post_id = ?', [$id]);
            $db->delete('blog_post_tags', 'post_id = ?', [$id]);
            $db->delete('blog_comments', 'post_id = ?', [$id]);
            $db->delete('blog_posts', 'id = ?', [$id]);
            $logger->info("Blog post #{$id} deleted by user #{$auth->id()}");
            Session::flash('success', 'Post deleted.');
        }
    }

    if ($action === 'toggle_status') {
        $id   = (int)($_POST['id'] ?? 0);
        $post = $id ? $db->fetch("SELECT status FROM blog_posts WHERE id = ?", [$id]) : null;
        if ($post) {
            $newStatus = $post['status'] === 'published' ? 'draft' : 'published';
            $extra     = $newStatus === 'published' ? ['published_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')] : ['updated_at' => date('Y-m-d H:i:s')];
            $db->update('blog_posts', array_merge(['status' => $newStatus], $extra), 'id = ?', [$id]);
            Session::flash('success', "Post {$newStatus}.");
        }
    }

    if ($action === 'save') {
        $id           = (int)($_POST['id'] ?? 0);
        $title        = trim(Security::sanitize($_POST['title'] ?? ''));
        $slug         = trim(Security::sanitize($_POST['slug'] ?? ''));
        $excerpt      = trim(Security::sanitize($_POST['excerpt'] ?? ''));
        $content      = $_POST['content'] ?? '';
        $status       = in_array($_POST['status'] ?? '', ['draft', 'published']) ? $_POST['status'] : 'draft';
        $metaDesc     = trim(Security::sanitize($_POST['meta_desc'] ?? ''));
        $metaKeywords = trim(Security::sanitize($_POST['meta_keywords'] ?? ''));
        $ogTitle      = trim(Security::sanitize($_POST['og_title'] ?? ''));
        $ogDesc       = trim(Security::sanitize($_POST['og_description'] ?? ''));
        $ogImageUrl   = trim(Security::sanitize($_POST['og_image_url'] ?? ''));
        $coverUrl     = trim(Security::sanitize($_POST['cover_image_url'] ?? ''));
        $featured     = !empty($_POST['featured']) ? 1 : 0;

        if (!$slug) $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
        $slug = trim($slug, '-');

        // Handle cover image file upload
        if (!empty($_FILES['cover_image_file']['name'])) {
            $uploadResult = uploadBlogImage($_FILES['cover_image_file']);
            if ($uploadResult['ok']) {
                $coverUrl = $uploadResult['url'];
            } else {
                Session::flash('warning', 'Cover image: ' . $uploadResult['error']);
            }
        }

        // Handle OG image file upload
        if (!empty($_FILES['og_image_file']['name'])) {
            $ogUpload = uploadBlogImage($_FILES['og_image_file']);
            if ($ogUpload['ok']) {
                $ogImageUrl = $ogUpload['url'];
            } else {
                Session::flash('warning', 'OG image: ' . $ogUpload['error']);
            }
        }

        $data = [
            'title'          => $title,
            'slug'           => $slug,
            'excerpt'        => $excerpt ?: null,
            'content'        => $content,
            'status'         => $status,
            'cover_image'    => $coverUrl ?: null,
            'meta_desc'      => $metaDesc ?: null,
            'meta_keywords'  => $metaKeywords ?: null,
            'og_title'       => $ogTitle ?: null,
            'og_description' => $ogDesc ?: null,
            'og_image'       => $ogImageUrl ?: null,
            'featured'       => $featured,
            'author_id'      => $auth->id(),
            'updated_at'     => date('Y-m-d H:i:s'),
        ];
        if ($status === 'published') {
            $data['published_at'] = date('Y-m-d H:i:s');
        }

        if ($id) {
            $db->update('blog_posts', $data, 'id = ?', [$id]);
            Session::flash('success', 'Post updated.');
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $id = $db->insert('blog_posts', $data);
            $logger->info("Blog post created: {$title}", [], $auth->id());
            Session::flash('success', 'Post created.');
        }

        // Save categories (idempotent)
        try {
            $db->delete('blog_post_categories', 'post_id = ?', [$id]);
            foreach (array_map('intval', $_POST['category_ids'] ?? []) as $cid) {
                if ($cid > 0) $db->insert('blog_post_categories', ['post_id' => $id, 'category_id' => $cid]);
            }
        } catch (Exception $e) {}

        // Save tags (idempotent)
        try {
            $db->delete('blog_post_tags', 'post_id = ?', [$id]);
            foreach (array_map('intval', $_POST['tag_ids'] ?? []) as $tid) {
                if ($tid > 0) $db->insert('blog_post_tags', ['post_id' => $id, 'tag_id' => $tid]);
            }
        } catch (Exception $e) {}
    }

    // ─── Tags & Categories CRUD ───────────────────────────────────────────────
    if ($action === 'create_category') {
        $catName = trim(Security::sanitize($_POST['cat_name'] ?? ''));
        if ($catName) {
            $catSlug = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($catName)), '-'));
            $catDesc = trim(Security::sanitize($_POST['cat_description'] ?? ''));
            try {
                $db->insert('blog_categories', ['name' => $catName, 'slug' => $catSlug, 'description' => $catDesc ?: null]);
                Session::flash('success', 'Category "' . $catName . '" created.');
            } catch (Throwable $e) {
                Session::flash('warning', 'Could not create category — the name or slug may already exist.');
            }
        }
        redirect('/admin/blog?tab=taxonomy');
    }

    if ($action === 'edit_category') {
        $catId   = (int)($_POST['id'] ?? 0);
        $catName = trim(Security::sanitize($_POST['cat_name'] ?? ''));
        $catDesc = trim(Security::sanitize($_POST['cat_description'] ?? ''));
        if ($catId && $catName) {
            $db->update('blog_categories', ['name' => $catName, 'description' => $catDesc ?: null], 'id = ?', [$catId]);
            Session::flash('success', 'Category updated.');
        }
        redirect('/admin/blog?tab=taxonomy');
    }

    if ($action === 'delete_category') {
        $catId = (int)($_POST['id'] ?? 0);
        if ($catId) {
            $db->delete('blog_post_categories', 'category_id = ?', [$catId]);
            $db->delete('blog_categories', 'id = ?', [$catId]);
            Session::flash('success', 'Category deleted.');
        }
        redirect('/admin/blog?tab=taxonomy');
    }

    if ($action === 'create_tag') {
        $tagName = trim(Security::sanitize($_POST['tag_name'] ?? ''));
        if ($tagName) {
            $tagSlug = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($tagName)), '-'));
            try {
                $db->insert('blog_tags', ['name' => $tagName, 'slug' => $tagSlug]);
                Session::flash('success', 'Tag "' . $tagName . '" created.');
            } catch (Throwable $e) {
                Session::flash('warning', 'Could not create tag — the name or slug may already exist.');
            }
        }
        redirect('/admin/blog?tab=taxonomy');
    }

    if ($action === 'edit_tag') {
        $tagId   = (int)($_POST['id'] ?? 0);
        $tagName = trim(Security::sanitize($_POST['tag_name'] ?? ''));
        if ($tagId && $tagName) {
            $db->update('blog_tags', ['name' => $tagName], 'id = ?', [$tagId]);
            Session::flash('success', 'Tag updated.');
        }
        redirect('/admin/blog?tab=taxonomy');
    }

    if ($action === 'approve_post') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            try {
                $db->update('blog_posts', [
                    'review_status' => 'approved',
                    'reviewed_by'   => $auth->id(),
                    'status'        => 'published',
                    'published_at'  => date('Y-m-d H:i:s'),
                    'updated_at'    => date('Y-m-d H:i:s'),
                ], 'id = ?', [$id]);
                $logger->info("Blog post #{$id} approved and published by admin #{$auth->id()}");
                Session::flash('success', 'Post approved and published.');
            } catch (Exception $e) {
                Session::flash('danger', 'Could not approve post.');
            }
        }
        redirect('/admin/blog');
    }

    if ($action === 'reject_post') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            try {
                $db->update('blog_posts', [
                    'review_status' => 'rejected',
                    'reviewed_by'   => $auth->id(),
                    'status'        => 'draft',
                    'updated_at'    => date('Y-m-d H:i:s'),
                ], 'id = ?', [$id]);
                $logger->info("Blog post #{$id} rejected (moved to draft) by admin #{$auth->id()}");
                Session::flash('info', 'Post rejected and moved to drafts.');
            } catch (Exception $e) {
                Session::flash('danger', 'Could not reject post.');
            }
        }
        redirect('/admin/blog');
    }

    if ($action === 'delete_tag') {
        $tagId = (int)($_POST['id'] ?? 0);
        if ($tagId) {
            $db->delete('blog_post_tags', 'tag_id = ?', [$tagId]);
            $db->delete('blog_tags', 'id = ?', [$tagId]);
            Session::flash('success', 'Tag deleted.');
        }
        redirect('/admin/blog?tab=taxonomy');
    }

    redirect('/admin/blog');
}

// Listing
$statusFilter = Security::sanitize($_GET['status'] ?? '');
$perPage = 20;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

try {
    $reviewFilter = Security::sanitize($_GET['review'] ?? '');
    if ($reviewFilter === 'pending') {
        $whereClause = "review_status = 'pending'";
        $whereParams = [];
    } elseif ($statusFilter) {
        $whereClause = 'status = ?';
        $whereParams = [$statusFilter];
    } else {
        $whereClause = '1=1';
        $whereParams = [];
    }
    $total = $db->count('blog_posts', $whereClause, $whereParams);
    $posts = $db->fetchAll(
        "SELECT bp.*, u.name AS author_name FROM blog_posts bp LEFT JOIN users u ON u.id = bp.author_id"
        . " WHERE {$whereClause}"
        . " ORDER BY bp.id DESC LIMIT ? OFFSET ?",
        array_merge($whereParams, [$perPage, $offset])
    );
    $totalPages = max(1, (int)ceil($total / $perPage));
    $counts = [
        'all'       => $db->count('blog_posts'),
        'published' => $db->count('blog_posts', "status = 'published'"),
        'draft'     => $db->count('blog_posts', "status = 'draft'"),
        'pending'   => 0,
    ];
    try { $counts['pending'] = $db->count('blog_posts', "review_status = 'pending'"); } catch (Exception $e) {}
} catch (Exception $e) {
    $reviewFilter = '';
    $total = 0; $posts = []; $totalPages = 1;
    $counts = ['all' => 0, 'published' => 0, 'draft' => 0, 'pending' => 0];
}

// Editor mode?
$editId   = (int)($_GET['edit'] ?? 0);
$editPost = $editId ? $db->fetch("SELECT * FROM blog_posts WHERE id = ?", [$editId]) : null;
$newPost  = (bool)($_GET['new'] ?? false);

$blogTab = Security::sanitize($_GET['tab'] ?? 'posts');

// Fetch categories & tags (always — needed for editor and taxonomy tab)
$allCategories = $allTags = $postCatIds = $postTagIds = [];
try {
    $allCategories = $db->fetchAll("SELECT bc.*, COUNT(bpc.post_id) AS post_count FROM blog_categories bc LEFT JOIN blog_post_categories bpc ON bpc.category_id = bc.id GROUP BY bc.id ORDER BY bc.name ASC") ?: [];
    $allTags       = $db->fetchAll("SELECT bt.*, COUNT(bpt.post_id) AS post_count FROM blog_tags bt LEFT JOIN blog_post_tags bpt ON bpt.tag_id = bt.id GROUP BY bt.id ORDER BY bt.name ASC") ?: [];
    if ($editPost) {
        $postCatIds = array_column($db->fetchAll("SELECT category_id FROM blog_post_categories WHERE post_id = ?", [$editPost['id']]) ?: [], 'category_id');
        $postTagIds = array_column($db->fetchAll("SELECT tag_id FROM blog_post_tags WHERE post_id = ?", [$editPost['id']]) ?: [], 'tag_id');
    }
} catch (Exception $e) {}

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Blog</div>
            <div class="page-subtitle"><?= number_format($total) ?> posts</div>
        </div>
    </div>
    <div class="topbar-actions">
        <a href="/admin/blog?new=1" class="btn btn-primary btn-sm">+ New Post</a>
    </div>
</div>

<div class="page-body">

<?php if ($newPost || $editPost): ?>
<!-- ─── Post Editor ─────────────────────────────────────────────────────── -->
<div style="width:100%">
    <h2 style="font-size:16px;font-weight:700;margin-bottom:20px"><?= $editPost ? 'Edit Post: ' . e($editPost['title']) : 'New Blog Post' ?></h2>
    <form method="POST" enctype="multipart/form-data">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="save">
        <?php if ($editPost): ?><input type="hidden" name="id" value="<?= $editPost['id'] ?>"><?php endif ?>

        <!-- Core fields -->
        <div class="card mb-4">
            <div class="card-header"><span class="card-title">Post Details</span></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:16px">
                <div class="form-group mb-0">
                    <label class="form-label">Title <span style="color:var(--color-danger)">*</span></label>
                    <input type="text" name="title" class="form-input" required value="<?= e($editPost['title'] ?? '') ?>" placeholder="Post title…" oninput="autoSlug(this)" style="font-size:16px;font-weight:600">
                </div>
                <div class="grid-2">
                    <div class="form-group mb-0">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" id="post-slug" class="form-input" value="<?= e($editPost['slug'] ?? '') ?>" placeholder="auto-generated" style="font-family:monospace">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-input">
                            <option value="draft" <?= ($editPost['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= ($editPost['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                        </select>
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Excerpt</label>
                    <textarea name="excerpt" class="form-input" rows="2" placeholder="Short summary shown in post listings…"><?= e($editPost['excerpt'] ?? '') ?></textarea>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Content</label>
                    <textarea name="content" id="blog-content" class="form-input" rows="2"><?= htmlspecialchars($editPost['content'] ?? '', ENT_NOQUOTES, 'UTF-8') ?></textarea>
                    <div class="form-hint" style="display:flex;align-items:center;gap:8px">
                        Rich text editor. HTML is preserved.
                        <span id="blog-autosave-msg" style="color:var(--color-text-muted);font-size:11px"></span>
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label class="form-check" style="margin-bottom:0">
                        <input type="checkbox" name="featured" value="1" <?= !empty($editPost['featured']) ? 'checked' : '' ?>>
                        <span class="form-check-label">Mark as featured post</span>
                    </label>
                </div>
            </div>
        </div>

        <?php if (!empty($allCategories) || !empty($allTags)): ?>
        <!-- Categories & Tags -->
        <div class="card mb-4">
            <div class="card-header"><span class="card-title">Categories &amp; Tags</span></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:16px">
                <?php if (!empty($allCategories)): ?>
                <div class="form-group mb-0">
                    <label class="form-label">Categories</label>
                    <div style="display:flex;flex-wrap:wrap;gap:10px">
                        <?php foreach ($allCategories as $cat): ?>
                        <label class="form-check" style="margin-bottom:0">
                            <input type="checkbox" name="category_ids[]" value="<?= $cat['id'] ?>" <?= in_array((int)$cat['id'], array_map('intval', $postCatIds)) ? 'checked' : '' ?>>
                            <span class="form-check-label"><?= e($cat['name']) ?></span>
                        </label>
                        <?php endforeach ?>
                    </div>
                </div>
                <?php endif ?>
                <?php if (!empty($allTags)): ?>
                <div class="form-group mb-0">
                    <label class="form-label">Tags</label>
                    <div style="display:flex;flex-wrap:wrap;gap:10px">
                        <?php foreach ($allTags as $tag): ?>
                        <label class="form-check" style="margin-bottom:0">
                            <input type="checkbox" name="tag_ids[]" value="<?= $tag['id'] ?>" <?= in_array((int)$tag['id'], array_map('intval', $postTagIds)) ? 'checked' : '' ?>>
                            <span class="form-check-label"><?= e($tag['name']) ?></span>
                        </label>
                        <?php endforeach ?>
                    </div>
                </div>
                <?php endif ?>
            </div>
        </div>
        <?php endif ?>

        <!-- Cover Image -->
        <div class="card mb-4">
            <div class="card-header"><span class="card-title">Featured / Cover Image</span></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
                <?php $coverImg = $editPost['cover_image'] ?? ''; ?>
                <div id="cover-preview-wrap" style="<?= $coverImg ? '' : 'display:none;' ?>margin-bottom:4px">
                    <img id="cover-preview" src="<?= e($coverImg) ?>" alt="Cover image" style="max-width:320px;max-height:180px;border-radius:6px;border:1px solid var(--color-border);object-fit:cover">
                    <div style="margin-top:6px">
                        <button type="button" class="btn btn-ghost btn-sm text-danger" onclick="removeCover()">Remove image</button>
                    </div>
                </div>

                <div>
                    <label class="form-label">Upload image</label>
                    <input type="file" name="cover_image_file" class="form-input" accept="image/*" onchange="previewCover(this)">
                    <div class="form-hint">JPEG, PNG, WebP — max 5 MB. Displayed as the hero image on the blog post and as thumbnails in listings.</div>
                </div>
                <div>
                    <label class="form-label">Or enter image URL</label>
                    <input type="url" name="cover_image_url" id="cover-url-field" class="form-input" value="<?= e($editPost['cover_image'] ?? '') ?>" placeholder="https://example.com/image.jpg" oninput="previewCoverUrl(this)">
                </div>
            </div>
        </div>

        <!-- SEO -->
        <div class="card mb-4">
            <div class="card-header"><span class="card-title">SEO & Meta</span></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
                <div class="form-group mb-0">
                    <label class="form-label">Meta Description</label>
                    <textarea name="meta_desc" class="form-input" rows="2" placeholder="Brief description for search engines (max 160 chars)…" maxlength="160"><?= e($editPost['meta_desc'] ?? '') ?></textarea>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Keywords</label>
                    <input type="text" name="meta_keywords" class="form-input" value="<?= e($editPost['meta_keywords'] ?? '') ?>" placeholder="keyword1, keyword2, keyword3">
                    <div class="form-hint">Comma-separated keywords.</div>
                </div>
            </div>
        </div>

        <!-- Open Graph -->
        <div class="card mb-4">
            <div class="card-header">
                <span class="card-title">Open Graph & Social</span>
                <span class="form-hint" style="margin:0;font-size:11px">Overrides how this post appears when shared on Facebook, LinkedIn, Twitter/X etc.</span>
            </div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
                <div class="form-group mb-0">
                    <label class="form-label">OG Title</label>
                    <input type="text" name="og_title" class="form-input" value="<?= e($editPost['og_title'] ?? '') ?>" placeholder="Leave blank to use post title">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">OG Description</label>
                    <textarea name="og_description" class="form-input" rows="2" placeholder="Leave blank to use meta description…"><?= e($editPost['og_description'] ?? '') ?></textarea>
                </div>
                <?php $ogImg = $editPost['og_image'] ?? ''; ?>
                <div id="og-preview-wrap" style="<?= $ogImg ? '' : 'display:none;' ?>margin-bottom:4px">
                    <img id="og-preview" src="<?= e($ogImg) ?>" alt="OG image" style="max-width:280px;max-height:148px;border-radius:6px;border:1px solid var(--color-border);object-fit:cover">
                    <div style="margin-top:6px">
                        <button type="button" class="btn btn-ghost btn-sm text-danger" onclick="removeOg()">Remove OG image</button>
                    </div>
                </div>
                <div>
                    <label class="form-label">OG Image — upload</label>
                    <input type="file" name="og_image_file" class="form-input" accept="image/*" onchange="previewOg(this)">
                    <div class="form-hint">Recommended: 1200×630 px. Leave blank to use the cover image.</div>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">OG Image — URL</label>
                    <input type="url" name="og_image_url" id="og-url-field" class="form-input" value="<?= e($editPost['og_image'] ?? '') ?>" placeholder="https://example.com/og-image.jpg" oninput="previewOgUrl(this)">
                </div>
            </div>
        </div>

        <div style="display:flex;gap:10px">
            <button type="submit" class="btn btn-primary"><?= $editPost ? 'Save Changes' : 'Create Post' ?></button>
            <a href="/admin/blog" class="btn btn-secondary">Cancel</a>
            <?php if ($editPost && $editPost['status'] === 'published'): ?>
            <a href="/blog/<?= e($editPost['slug']) ?>" target="_blank" class="btn btn-ghost btn-sm" style="margin-left:auto">View Post →</a>
            <?php endif ?>
        </div>
    </form>
</div>
<script>
function autoSlug(titleInput) {
    var slug = document.getElementById('post-slug');
    if (!slug || slug.dataset.manual) return;
    slug.value = titleInput.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
}
document.getElementById('post-slug') && document.getElementById('post-slug').addEventListener('input', function() {
    this.dataset.manual = '1';
});
function previewCover(input) {
    if (!input.files || !input.files[0]) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('cover-preview').src = e.target.result;
        document.getElementById('cover-preview-wrap').style.display = '';
        document.getElementById('cover-url-field').value = '';
    };
    reader.readAsDataURL(input.files[0]);
}
function previewCoverUrl(input) {
    if (input.value) {
        document.getElementById('cover-preview').src = input.value;
        document.getElementById('cover-preview-wrap').style.display = '';
    } else {
        document.getElementById('cover-preview-wrap').style.display = 'none';
    }
}
function removeCover() {
    document.getElementById('cover-preview-wrap').style.display = 'none';
    document.getElementById('cover-url-field').value = '';
}
function previewOg(input) {
    if (!input.files || !input.files[0]) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('og-preview').src = e.target.result;
        document.getElementById('og-preview-wrap').style.display = '';
        document.getElementById('og-url-field').value = '';
    };
    reader.readAsDataURL(input.files[0]);
}
function previewOgUrl(input) {
    if (input.value) {
        document.getElementById('og-preview').src = input.value;
        document.getElementById('og-preview-wrap').style.display = '';
    } else {
        document.getElementById('og-preview-wrap').style.display = 'none';
    }
}
function removeOg() {
    document.getElementById('og-preview-wrap').style.display = 'none';
    document.getElementById('og-url-field').value = '';
}
</script>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function() {
    var postId   = <?= json_encode($editPost['id'] ?? 'new') ?>;
    var saveKey  = 'blog_autosave_' + postId;
    var isDark   = document.documentElement.getAttribute('data-theme') === 'dark';
    var saveTimer;

    function showMsg(txt) {
        var el = document.getElementById('blog-autosave-msg');
        if (!el) return;
        el.textContent = txt;
        clearTimeout(el._t);
        el._t = setTimeout(function(){ el.textContent = ''; }, 3500);
    }

    tinymce.init({
        license_key: 'gpl',
        selector: '#blog-content',
        plugins: 'anchor autolink charmap codesample image link lists media searchreplace table visualblocks wordcount',
        toolbar: 'undo redo | blocks | bold italic underline | link image table | numlist bullist | codesample | removeformat',
        height: 440,
        menubar: false,
        statusbar: true,
        branding: false,
        promotion: false,
        skin: isDark ? 'oxide-dark' : 'oxide',
        content_css: isDark ? 'dark' : 'default',
        init_instance_callback: function(editor) {
            // Offer autosave restore if available and differs from saved post
            try {
                var saved = JSON.parse(localStorage.getItem(saveKey) || 'null');
                var currentContent = editor.getContent();
                if (saved && saved.content && saved.content !== currentContent) {
                    var el = document.getElementById('blog-autosave-msg');
                    if (el) {
                        el.innerHTML = 'Autosave from ' + new Date(saved.saved_at).toLocaleTimeString() + ' — ';
                        var a = document.createElement('a');
                        a.href = '#'; a.textContent = 'Restore draft';
                        a.style.cssText = 'color:var(--color-primary,#6366f1)';
                        a.onclick = function(e) {
                            e.preventDefault();
                            editor.setContent(saved.content);
                            if (saved.title) { var t=document.querySelector('[name=title]'); if(t) t.value=saved.title; }
                            if (saved.excerpt) { var x=document.querySelector('[name=excerpt]'); if(x) x.value=saved.excerpt; }
                            showMsg('Draft restored.');
                        };
                        el.appendChild(a);
                    }
                }
            } catch(e) {}
        },
        setup: function(editor) {
            editor.on('input change', function() {
                clearTimeout(saveTimer);
                saveTimer = setTimeout(function() {
                    try {
                        localStorage.setItem(saveKey, JSON.stringify({
                            title:    (document.querySelector('[name=title]')||{}).value||'',
                            excerpt:  (document.querySelector('[name=excerpt]')||{}).value||'',
                            content:  editor.getContent(),
                            saved_at: new Date().toISOString()
                        }));
                        showMsg('Draft saved locally');
                    } catch(e) {}
                }, 2000);
            });
        }
    });

    // On submit: sync editor content to textarea + clear autosave
    var form = document.getElementById('blog-content').closest('form');
    if (form) {
        form.addEventListener('submit', function() {
            if (tinymce.activeEditor) {
                document.getElementById('blog-content').value = tinymce.activeEditor.getContent();
            }
            try { localStorage.removeItem(saveKey); } catch(e) {}
        });
    }
})();
</script>

<?php else: ?>
<div style="display:flex;gap:4px;margin-bottom:20px;border-bottom:1px solid var(--color-border);padding-bottom:0">
    <a href="/admin/blog" class="btn btn-sm <?= $blogTab !== 'taxonomy' ? 'btn-primary' : 'btn-ghost' ?>" style="border-bottom-left-radius:0;border-bottom-right-radius:0;margin-bottom:-1px">
        Posts <span style="background:rgba(0,0,0,.1);padding:1px 6px;border-radius:8px;font-size:10px;margin-left:2px"><?= $counts['all'] ?></span>
    </a>
    <a href="/admin/blog?tab=taxonomy" class="btn btn-sm <?= $blogTab === 'taxonomy' ? 'btn-primary' : 'btn-ghost' ?>" style="border-bottom-left-radius:0;border-bottom-right-radius:0;margin-bottom:-1px">Tags &amp; Categories</a>
</div>

<?php if ($blogTab === 'taxonomy'): ?>
<!-- ─── Tags & Categories ──────────────────────────────────────────────── -->
<div class="grid-2" style="gap:24px;align-items:start">

    <!-- Categories -->
    <div>
        <div class="card">
            <div class="card-header">
                <span class="card-title">Categories</span>
                <span class="text-muted text-sm"><?= count($allCategories) ?> total</span>
            </div>
            <div class="card-body" style="padding-bottom:8px">
                <form method="POST" data-loading style="display:flex;gap:8px;margin-bottom:16px">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="create_category">
                    <input type="text" name="cat_name" class="form-input" placeholder="New category name" required style="flex:1;min-width:0">
                    <button type="submit" class="btn btn-primary btn-sm">Add</button>
                </form>
                <?php if (empty($allCategories)): ?>
                <p class="text-muted text-sm" style="padding:8px 0">No categories yet. Add one above.</p>
                <?php else: ?>
                <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Name</th><th>Posts</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($allCategories as $cat):
                        $editingCatId = (int)($_GET['edit_cat'] ?? 0);
                    ?>
                    <tr>
                        <td>
                            <?php if ($editingCatId === (int)$cat['id']): ?>
                            <form method="POST" data-loading style="display:flex;gap:6px;align-items:center">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="edit_category">
                                <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                <input type="text" name="cat_name" class="form-input" value="<?= e($cat['name']) ?>" required style="font-size:13px;min-width:0;flex:1">
                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                <a href="/admin/blog?tab=taxonomy" class="btn btn-ghost btn-sm">Cancel</a>
                            </form>
                            <?php else: ?>
                            <span style="font-weight:500"><?= e($cat['name']) ?></span>
                            <?php if ($cat['description']): ?>
                            <div class="text-muted" style="font-size:11px;margin-top:2px"><?= e($cat['description']) ?></div>
                            <?php endif ?>
                            <?php endif ?>
                        </td>
                        <td><span class="badge badge-secondary"><?= (int)$cat['post_count'] ?></span></td>
                        <td style="text-align:right;white-space:nowrap">
                            <a href="/admin/blog?tab=taxonomy&edit_cat=<?= $cat['id'] ?>" class="btn btn-ghost btn-sm">Rename</a>
                            <form method="POST" style="display:inline">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="delete_category">
                                <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                <button type="submit" class="btn btn-ghost btn-sm text-danger"
                                    data-confirm="Delete category &quot;<?= e(addslashes($cat['name'])) ?>&quot;? Posts keep their content but lose this category.">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
                </div>
                <?php endif ?>
            </div>
        </div>
    </div>

    <!-- Tags -->
    <div>
        <div class="card">
            <div class="card-header">
                <span class="card-title">Tags</span>
                <span class="text-muted text-sm"><?= count($allTags) ?> total</span>
            </div>
            <div class="card-body" style="padding-bottom:8px">
                <form method="POST" data-loading style="display:flex;gap:8px;margin-bottom:16px">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="create_tag">
                    <input type="text" name="tag_name" class="form-input" placeholder="New tag name" required style="flex:1;min-width:0">
                    <button type="submit" class="btn btn-primary btn-sm">Add</button>
                </form>
                <?php if (empty($allTags)): ?>
                <p class="text-muted text-sm" style="padding:8px 0">No tags yet. Add one above.</p>
                <?php else: ?>
                <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Name</th><th>Posts</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($allTags as $tag):
                        $editingTagId = (int)($_GET['edit_tag'] ?? 0);
                    ?>
                    <tr>
                        <td>
                            <?php if ($editingTagId === (int)$tag['id']): ?>
                            <form method="POST" data-loading style="display:flex;gap:6px;align-items:center">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="edit_tag">
                                <input type="hidden" name="id" value="<?= $tag['id'] ?>">
                                <input type="text" name="tag_name" class="form-input" value="<?= e($tag['name']) ?>" required style="font-size:13px;min-width:0;flex:1">
                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                <a href="/admin/blog?tab=taxonomy" class="btn btn-ghost btn-sm">Cancel</a>
                            </form>
                            <?php else: ?>
                            <span style="font-weight:500"><?= e($tag['name']) ?></span>
                            <?php endif ?>
                        </td>
                        <td><span class="badge badge-secondary"><?= (int)$tag['post_count'] ?></span></td>
                        <td style="text-align:right;white-space:nowrap">
                            <a href="/admin/blog?tab=taxonomy&edit_tag=<?= $tag['id'] ?>" class="btn btn-ghost btn-sm">Rename</a>
                            <form method="POST" style="display:inline">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="delete_tag">
                                <input type="hidden" name="id" value="<?= $tag['id'] ?>">
                                <button type="submit" class="btn btn-ghost btn-sm text-danger"
                                    data-confirm="Delete tag &quot;<?= e(addslashes($tag['name'])) ?>&quot;?">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
                </div>
                <?php endif ?>
            </div>
        </div>
    </div>

</div>

<?php else: ?>
<!-- ─── Post List ──────────────────────────────────────────────────────── -->
<div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap">
    <a href="/admin/blog" class="btn btn-sm <?= (!$statusFilter && ($reviewFilter ?? '') !== 'pending') ? 'btn-primary' : 'btn-secondary' ?>">All <span style="background:rgba(0,0,0,0.1);padding:1px 5px;border-radius:8px;font-size:10px;margin-left:2px"><?= $counts['all'] ?></span></a>
    <a href="/admin/blog?status=published" class="btn btn-sm <?= $statusFilter === 'published' ? 'btn-primary' : 'btn-secondary' ?>">Published <span style="background:rgba(0,0,0,0.1);padding:1px 5px;border-radius:8px;font-size:10px;margin-left:2px"><?= $counts['published'] ?></span></a>
    <a href="/admin/blog?status=draft" class="btn btn-sm <?= $statusFilter === 'draft' ? 'btn-primary' : 'btn-secondary' ?>">Drafts <span style="background:rgba(0,0,0,0.1);padding:1px 5px;border-radius:8px;font-size:10px;margin-left:2px"><?= $counts['draft'] ?></span></a>
    <?php if ($counts['pending'] > 0 || ($reviewFilter ?? '') === 'pending'): ?>
    <a href="/admin/blog?review=pending" class="btn btn-sm <?= ($reviewFilter ?? '') === 'pending' ? 'btn-warning' : 'btn-secondary' ?>" style="<?= $counts['pending'] > 0 ? 'color:var(--color-warning)' : '' ?>">
        Pending Review
        <?php if ($counts['pending'] > 0): ?>
        <span style="background:var(--color-warning);color:#fff;padding:1px 6px;border-radius:8px;font-size:10px;margin-left:2px"><?= $counts['pending'] ?></span>
        <?php endif ?>
    </a>
    <?php endif ?>
</div>

<div class="card">
    <?php if (empty($posts)): ?>
    <div class="empty-state">
        <div class="empty-state-icon"></div>
        <h3>No Blog Posts</h3>
        <p>Create your first post to get started.</p>
        <a href="/admin/blog?new=1" class="btn btn-primary btn-sm" style="margin-top:12px">+ New Post</a>
    </div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>Title</th><th>Cover</th><th>Author</th><th>Status</th><th>Published</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($posts as $post): ?>
            <tr>
                <td>
                    <div style="font-weight:600;font-size:13px"><?= e($post['title']) ?></div>
                    <div style="font-size:11px;color:var(--color-text-muted);font-family:monospace">/blog/<?= e($post['slug']) ?></div>
                </td>
                <td>
                    <?php if ($post['cover_image']): ?>
                    <img src="<?= e($post['cover_image']) ?>" alt="" style="width:48px;height:32px;object-fit:cover;border-radius:3px;border:1px solid var(--color-border)">
                    <?php else: ?>
                    <span style="color:var(--color-text-muted);font-size:11px">None</span>
                    <?php endif ?>
                </td>
                <td class="text-sm text-muted"><?= e($post['author_name'] ?? '—') ?></td>
                <td>
                    <span class="badge badge-<?= $post['status'] === 'published' ? 'success' : 'neutral' ?>"><?= e($post['status']) ?></span>
                    <?php if (!empty($post['review_status']) && $post['review_status'] === 'pending'): ?>
                    <span class="badge badge-warning" style="margin-left:4px">Pending Review</span>
                    <?php endif ?>
                    <?php if ($post['featured']): ?><span class="badge badge-secondary" style="margin-left:4px">Featured</span><?php endif ?>
                </td>
                <td class="text-sm text-muted"><?= $post['published_at'] ? fdate($post['published_at']) : '—' ?></td>
                <td>
                    <div style="display:flex;gap:4px;flex-wrap:wrap">
                        <a href="/admin/blog?edit=<?= $post['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                        <?php if (!empty($post['review_status']) && $post['review_status'] === 'pending'): ?>
                        <form method="POST" style="display:inline">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="approve_post">
                            <input type="hidden" name="id" value="<?= $post['id'] ?>">
                            <button type="submit" class="btn btn-sm" style="background:var(--color-success,#22c55e);color:#fff">Approve</button>
                        </form>
                        <form method="POST" style="display:inline">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="reject_post">
                            <input type="hidden" name="id" value="<?= $post['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm text-danger">Reject</button>
                        </form>
                        <?php else: ?>
                        <form method="POST" style="display:inline">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="id" value="<?= $post['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm"><?= $post['status'] === 'published' ? 'Unpublish' : 'Publish' ?></button>
                        </form>
                        <?php endif ?>
                        <?php if ($post['status'] === 'published'): ?>
                        <a href="/blog/<?= e($post['slug']) ?>" target="_blank" class="btn btn-ghost btn-sm">View</a>
                        <?php endif ?>
                        <form method="POST" style="display:inline">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $post['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm text-danger" data-confirm="Delete '<?= e(addslashes($post['title'])) ?>'? This cannot be undone.">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php endif ?>
</div>
<?php endif ?>
<?php endif ?>

</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Blog', $content, ['section' => 'blog']);
