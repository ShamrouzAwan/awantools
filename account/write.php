<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireLogin();

// Only admins and blog writers can access this
if (!$auth->isAdmin() && !$auth->hasRole('blog_writer')) {
    http_response_code(403);
    die(renderError(403, 'Access Denied', 'You need the Blog Writer role to access this page. Contact an admin to request access.'));
}

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();

    $title   = trim(Security::sanitize($_POST['title']   ?? ''));
    $excerpt = trim(Security::sanitize($_POST['excerpt'] ?? ''));
    $content = $_POST['content'] ?? '';

    if (!$title)   $errors[] = 'Title is required.';
    if (!$content) $errors[] = 'Content is required.';

    if (empty($errors)) {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', $title), '-'));
        // Ensure slug uniqueness
        try {
            $check = $db->fetch("SELECT id FROM blog_posts WHERE slug = ?", [$slug]);
            if ($check) $slug .= '-' . substr(md5(uniqid()), 0, 6);
        } catch (Exception $e) {}

        // Admins can publish directly; writers go to pending review
        $status        = $auth->isAdmin() ? 'published' : 'draft';
        $reviewStatus  = $auth->isAdmin() ? null : 'pending';

        try {
            $postId = $db->insert('blog_posts', [
                'title'         => $title,
                'slug'          => $slug,
                'excerpt'       => $excerpt ?: null,
                'content'       => $content,
                'status'        => $status,
                'review_status' => $reviewStatus,
                'author_id'     => $auth->id(),
                'published_at'  => $status === 'published' ? date('Y-m-d H:i:s') : null,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
            // Save tag associations
            if ($postId) {
                $tagIds = array_map('intval', $_POST['tag_ids'] ?? []);
                foreach ($tagIds as $tid) {
                    if ($tid > 0) {
                        try {
                            if (!$db->exists('blog_post_tags', 'post_id = ? AND tag_id = ?', [$postId, $tid])) {
                                $db->insert('blog_post_tags', ['post_id' => $postId, 'tag_id' => $tid]);
                            }
                        } catch (Throwable $te) {}
                    }
                }
            }
            $logger->info("Blog post submitted by writer user #{$auth->id()}: {$title}");
            Session::flash('success', $auth->isAdmin()
                ? 'Post published successfully.'
                : 'Post submitted for review. An admin will approve it shortly.');
            redirect('/account/my-posts');
        } catch (Exception $e) {
            $errors[] = 'Could not save post. Please try again.';
        }
    }
}

// Fetch all tags for selection UI
$allTags = [];
try {
    $allTags = $db->fetchAll("SELECT id, name FROM blog_tags ORDER BY name ASC") ?: [];
} catch (Throwable $e) {}

ob_start();
?>
<style>
.write-layout { max-width:820px; margin:0 auto; }
</style>

<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Write a Post</div>
            <div class="page-subtitle">
                <?php if ($auth->isAdmin()): ?>
                Posts you publish appear immediately on the blog.
                <?php else: ?>
                Your post will be reviewed before it appears publicly.
                <?php endif ?>
            </div>
        </div>
    </div>
    <div class="page-header-actions">
        <a href="/account/my-posts" class="btn btn-ghost btn-sm">My Posts</a>
        <a href="/account/dashboard" class="btn btn-ghost btn-sm">Dashboard</a>
    </div>
</div>

<div class="page-body">
<div class="write-layout">

    <?php foreach ($errors as $err): ?>
    <div class="alert alert-danger" style="margin-bottom:16px"><?= e($err) ?></div>
    <?php endforeach ?>

    <?php if (!$auth->isAdmin()): ?>
    <div class="alert alert-info" style="margin-bottom:20px">
        <strong>Writer Mode:</strong> Posts you submit go to pending review. An admin will approve or reject them. Once approved, your post will appear on the blog.
    </div>
    <?php endif ?>

    <form method="POST" id="write-form">
        <?= Security::csrfField() ?>

        <div class="card" style="margin-bottom:16px">
            <div class="card-body" style="display:flex;flex-direction:column;gap:16px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Title <span style="color:var(--color-danger)">*</span></label>
                    <input type="text" name="title" class="form-input form-control-lg" placeholder="Enter a descriptive title…" value="<?= e($_POST['title'] ?? '') ?>" required>
                </div>

                <div class="form-group" style="margin:0">
                    <label class="form-label">Excerpt <small class="text-muted">(optional)</small></label>
                    <textarea name="excerpt" class="form-input" rows="2" placeholder="A short summary of your post (shown in listings)…"><?= e($_POST['excerpt'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <?php if (!empty($allTags)): ?>
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><span class="card-title">Tags <small class="text-muted" style="font-weight:400">(optional)</small></span></div>
            <div class="card-body">
                <div style="display:flex;flex-wrap:wrap;gap:10px">
                    <?php foreach ($allTags as $tag): ?>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;padding:4px 10px;border-radius:var(--radius-full);border:1px solid var(--color-border);font-size:13px;transition:all .15s">
                        <input type="checkbox" name="tag_ids[]" value="<?= (int)$tag['id'] ?>"
                               style="width:14px;height:14px;flex-shrink:0"
                               <?= in_array((int)$tag['id'], array_map('intval', $_POST['tag_ids'] ?? [])) ? 'checked' : '' ?>>
                        <?= e($tag['name']) ?>
                    </label>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
        <?php endif ?>

        <div class="card" style="margin-bottom:20px">
            <div class="card-header"><span class="card-title">Content <span style="color:var(--color-danger)">*</span></span></div>
            <div class="card-body" style="padding:12px">
                <textarea name="content" id="write-content" class="form-input" rows="18" style="min-height:360px;font-family:inherit"><?= htmlspecialchars($_POST['content'] ?? '', ENT_QUOTES) ?></textarea>
            </div>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end">
            <a href="/account/my-posts" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary" data-loading="Submitting…">
                <?= $auth->isAdmin() ? 'Publish Post' : 'Submit for Review' ?>
            </button>
        </div>
    </form>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#write-content',
    license_key: 'gpl',
    height: 420,
    menubar: false,
    plugins: ['lists','link','image','code','fullscreen','autolink','codesample'],
    toolbar: 'blocks | bold italic underline strikethrough | bullist numlist | link image | code fullscreen',
    skin: (document.documentElement.getAttribute('data-theme') === 'dark') ? 'oxide-dark' : 'oxide',
    content_css: (document.documentElement.getAttribute('data-theme') === 'dark') ? 'dark' : 'default',
    placeholder: 'Start writing your post…',
    setup: function(editor) {
        // Store content to textarea before submit
        document.getElementById('write-form').addEventListener('submit', function() {
            editor.save();
        });
    }
});
</script>
<?php
$content = ob_get_clean();
require_once AWAN_ROOT . '/_bootstrap.php';
require THEMES_PATH . '/default/templates/layout.php';
render_page('Write a Post', $content);
