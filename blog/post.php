<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';

$slug = $GLOBALS['_route_blog_slug'] ?? ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/blog/');
$slug = Security::sanitize($slug);

try {
    $post = $db->fetch(
        "SELECT bp.*, u.name AS author_name, u.username AS author_username, u.avatar AS author_avatar, u.bio AS author_bio
         FROM blog_posts bp
         LEFT JOIN users u ON u.id = bp.author_id
         WHERE bp.slug = ? AND bp.status = 'published'
         LIMIT 1",
        [$slug]
    );
} catch (Exception $e) {
    $post = null;
}

if (!$post) {
    http_response_code(404);
    ob_start();
    ?>
    <div class="front-container" style="padding:80px 24px;text-align:center">
        <div class="error-code" style="font-size:48px">404</div>
        <h1 style="font-size:20px;margin-bottom:8px">Article Not Found</h1>
        <p style="color:var(--color-text-secondary);margin-bottom:24px">This article doesn't exist or has been unpublished.</p>
        <a href="/blog" class="btn btn-primary">← Back to Blog</a>
    </div>
    <?php
    $content = ob_get_clean();
    require THEMES_PATH . '/default/templates/layout.php';
    render_page('Article Not Found', $content);
    exit;
}

// Increment view count
try {
    $db->query("UPDATE blog_posts SET view_count = view_count + 1 WHERE id = ?", [$post['id']]);
} catch (Exception $e) {}

// Track analytics
if ($settings->get('analytics_enabled', '1') === '1' && !isBot()) {
    try { $db->insert('analytics_events', ['event' => 'blog_view', 'path' => '/blog/' . $slug, 'user_id' => $auth->id(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? null, 'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255), 'created_at' => date('Y-m-d H:i:s')]); } catch (Exception $e) {}
}

// Comment submission
$commentSuccess = false;
$commentError   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['submit_comment'])) {
    Security::verifyCsrf();
    $cName    = Security::sanitize(trim($_POST['comment_name'] ?? ''));
    $cEmail   = Security::sanitize(trim($_POST['comment_email'] ?? ''));
    $cContent = Security::sanitize(trim($_POST['comment_content'] ?? ''));
    if (!$cName || !$cContent) {
        $commentError = 'Name and comment are required.';
    } elseif ($cEmail && !filter_var($cEmail, FILTER_VALIDATE_EMAIL)) {
        $commentError = 'Please enter a valid email address.';
    } elseif (strlen($cContent) > 2000) {
        $commentError = 'Comment is too long (max 2000 characters).';
    } else {
        try {
            $db->insert('blog_comments', [
                'post_id'      => $post['id'],
                'author_name'  => $cName,
                'author_email' => $cEmail ?: '',
                'content'      => $cContent,
                'status'       => 'pending',
                'ip'           => $_SERVER['REMOTE_ADDR'] ?? null,
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
            $commentSuccess = true;
        } catch (Exception $e) {
            $commentError = 'Failed to submit comment. Please try again.';
        }
    }
}

// Fetch categories and tags
$postCategories = [];
$postTags = [];
$comments = [];
try {
    $postCategories = $db->fetchAll("SELECT bc.* FROM blog_categories bc JOIN blog_post_categories bpc ON bpc.category_id = bc.id WHERE bpc.post_id = ?", [$post['id']]);
    $postTags       = $db->fetchAll("SELECT bt.* FROM blog_tags bt JOIN blog_post_tags bpt ON bpt.tag_id = bt.id WHERE bpt.post_id = ?", [$post['id']]);
    $comments       = $db->fetchAll("SELECT * FROM blog_comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at ASC", [$post['id']]) ?: [];
} catch (Exception $e) {}

// Related posts
$relatedPosts = [];
try {
    $relatedPosts = $db->fetchAll(
        "SELECT * FROM blog_posts WHERE status = 'published' AND id != ? ORDER BY published_at DESC LIMIT 3",
        [$post['id']]
    );
} catch (Exception $e) {}

// Related/popular plugins
$relatedPlugins = [];
try {
    $relatedPlugins = $db->fetchAll(
        "SELECT id, slug, name, description, manifest FROM plugins WHERE status = 'active' ORDER BY view_count DESC, id DESC LIMIT 4"
    ) ?: [];
} catch (Exception $e) {}

$contentMaxWidth = max(600, min(1400, (int)($settings->get('content_max_width', '780') ?: 780)));
$authorName = $post['author_name'] ?? $post['author_username'] ?? 'AWAN Team';
$publishDate = fdate($post['published_at'] ?? $post['created_at']);
$readTime = max(1, (int)round(str_word_count(strip_tags($post['content'] ?? '')) / 200));

ob_start();
?>
<!-- Blog Post Header -->
<div class="blog-post-header">
    <div class="front-container">
        <nav aria-label="Breadcrumb" style="margin-bottom:20px">
            <ol style="list-style:none;display:flex;align-items:center;flex-wrap:wrap;gap:4px;font-size:13px;color:var(--color-text-muted);padding:0;margin:0">
                <li><a href="/" style="color:var(--color-text-muted);text-decoration:none" onmouseover="this.style.color='var(--color-primary)'" onmouseout="this.style.color='var(--color-text-muted)'">Home</a></li>
                <li style="margin:0 4px;opacity:.5">&#8250;</li>
                <li><a href="/blog" style="color:var(--color-text-muted);text-decoration:none" onmouseover="this.style.color='var(--color-primary)'" onmouseout="this.style.color='var(--color-text-muted)'">Blog</a></li>
                <?php if (!empty($postCategories)): ?>
                <li style="margin:0 4px;opacity:.5">&#8250;</li>
                <li><a href="/blog/category/<?= e($postCategories[0]['slug']) ?>" style="color:var(--color-text-muted);text-decoration:none" onmouseover="this.style.color='var(--color-primary)'" onmouseout="this.style.color='var(--color-text-muted)'"><?= e($postCategories[0]['name']) ?></a></li>
                <?php endif ?>
                <li style="margin:0 4px;opacity:.5">&#8250;</li>
                <li style="color:var(--color-text);font-weight:500;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= e($post['title']) ?>"><?= e($post['title']) ?></li>
            </ol>
        </nav>
        <?php if (!empty($postCategories)): ?>
        <div style="margin-bottom:12px">
            <?php foreach ($postCategories as $cat): ?>
            <a href="/blog/category/<?= e($cat['slug']) ?>" class="badge badge-primary" style="text-decoration:none;margin-right:6px"><?= e($cat['name']) ?></a>
            <?php endforeach ?>
        </div>
        <?php endif ?>
        <h1 style="font-size:clamp(24px,4vw,38px);font-weight:800;letter-spacing:-1.5px;color:var(--color-text);line-height:1.2;margin-bottom:16px"><?= e($post['title']) ?></h1>
        <?php if ($post['excerpt']): ?>
        <p style="font-size:17px;color:var(--color-text-secondary);max-width:680px;line-height:1.6"><?= e($post['excerpt']) ?></p>
        <?php endif ?>
        <div style="display:flex;align-items:center;gap:16px;margin-top:20px;flex-wrap:wrap">
            <?php $authorUsername = $post['author_username'] ?? ''; $authorAvatar = $post['author_avatar'] ?? ''; ?>
            <?php if ($authorUsername): ?>
            <a href="/blog/author/<?= e($authorUsername) ?>" style="display:flex;align-items:center;gap:10px;text-decoration:none" title="View all articles by <?= e($authorName) ?>">
            <?php else: ?>
            <div style="display:flex;align-items:center;gap:10px">
            <?php endif ?>
                <?php if ($authorAvatar): ?>
                <img src="<?= e($authorAvatar) ?>" alt="<?= e($authorName) ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--color-border);flex-shrink:0">
                <?php else: ?>
                <div style="width:36px;height:36px;background:var(--color-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:700;flex-shrink:0"><?= strtoupper(substr($authorName, 0, 2)) ?></div>
                <?php endif ?>
                <div>
                    <div style="font-size:13px;font-weight:600;color:var(--color-text)"><?= e($authorName) ?></div>
                    <?php if ($authorUsername && !empty($post['author_bio'])): ?>
                    <div style="font-size:11px;color:var(--color-text-muted)"><?= e(substr($post['author_bio'], 0, 60)) ?></div>
                    <?php endif ?>
                </div>
            <?php if ($authorUsername): ?></a><?php else: ?></div><?php endif ?>
            <span style="color:var(--color-text-muted);font-size:13px"><?= $publishDate ?></span>
            <span style="color:var(--color-text-muted);font-size:13px"><?= $readTime ?> min read</span>
            <?php if ($post['view_count'] > 0): ?>
            <span style="color:var(--color-text-muted);font-size:13px"><?= number_format($post['view_count']) ?> views</span>
            <?php endif ?>
        </div>
    </div>
</div>

<?php if ($post['cover_image']): ?>
<div class="front-container" style="padding-top:32px;max-width:900px">
    <img src="<?= e($post['cover_image']) ?>" alt="<?= e($post['title']) ?>" style="width:100%;border-radius:var(--radius-large);border:1px solid var(--color-border)">
</div>
<?php endif ?>

<!-- Post Content -->
<div class="blog-post-body">
    <div class="blog-post-content" style="font-size:16px;line-height:1.8;color:var(--color-text);max-width:<?= $contentMaxWidth ?>px;margin-left:auto;margin-right:auto">
        <?= $post['content'] ?>
    </div>

    <?php if (!empty($postTags)): ?>
    <div style="margin-top:36px;padding-top:24px;border-top:1px solid var(--color-border)">
        <span style="font-size:12px;font-weight:600;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:.06em;margin-right:8px">Tags:</span>
        <?php foreach ($postTags as $tag): ?>
        <a href="/blog/tag/<?= e($tag['slug']) ?>" class="badge badge-neutral" style="text-decoration:none;margin-right:4px"><?= e($tag['name']) ?></a>
        <?php endforeach ?>
    </div>
    <?php endif ?>

    <!-- Share -->
    <div style="margin-top:32px;padding-top:24px;border-top:1px solid var(--color-border);display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <span style="font-size:13px;font-weight:600;color:var(--color-text-muted)">Share:</span>
        <?php
        $shareUrl = ($settings->get('site_url', '') ?: '') . '/blog/' . $slug;
        $shareTitle = urlencode($post['title']);
        $shareUrlEnc = urlencode($shareUrl);
        ?>
        <a href="https://twitter.com/intent/tweet?text=<?= $shareTitle ?>&url=<?= $shareUrlEnc ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">Twitter/X</a>
        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= $shareUrlEnc ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">LinkedIn</a>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrlEnc ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">Facebook</a>
        <button class="btn btn-ghost btn-sm" data-copy="<?= e($shareUrl) ?>">Copy Link</button>
    </div>
</div>

<!-- Comments Section -->
<div style="background:var(--color-background);border-top:1px solid var(--color-border);padding:48px 0;margin-top:0">
    <div class="front-container" style="max-width:<?= $contentMaxWidth ?>px">
        <h3 style="font-size:20px;font-weight:700;margin-bottom:28px">
            Comments <?php if (!empty($comments)): ?><span style="font-size:15px;font-weight:400;color:var(--color-text-muted)">(<?= count($comments) ?>)</span><?php endif ?>
        </h3>

        <?php if (!empty($comments)): ?>
        <div style="display:flex;flex-direction:column;gap:20px;margin-bottom:40px">
            <?php foreach ($comments as $c): ?>
            <div style="display:flex;gap:14px">
                <div style="flex-shrink:0;width:36px;height:36px;border-radius:50%;background:var(--color-primary);display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:700">
                    <?= strtoupper(substr($c['author_name'], 0, 2)) ?>
                </div>
                <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                        <span style="font-size:14px;font-weight:600;color:var(--color-text)"><?= e($c['author_name']) ?></span>
                        <span style="font-size:12px;color:var(--color-text-muted)"><?= fdate($c['created_at'], 'M j, Y') ?></span>
                    </div>
                    <div style="font-size:14px;color:var(--color-text);line-height:1.7;white-space:pre-wrap"><?= e($c['content']) ?></div>
                </div>
            </div>
            <?php endforeach ?>
        </div>
        <?php endif ?>

        <?php if ($commentSuccess): ?>
        <div class="alert alert-success">Your comment has been submitted and is awaiting moderation. Thank you!</div>
        <?php elseif ($commentError): ?>
        <div class="alert alert-danger"><?= e($commentError) ?></div>
        <?php endif ?>

        <div class="card" style="margin-top:<?= !empty($comments) ? '0' : '0' ?>">
            <div class="card-header"><span class="card-title">Leave a Comment</span></div>
            <div class="card-body">
                <form method="POST" data-loading>
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="submit_comment" value="1">

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                        <div class="form-group">
                            <label class="form-label">Name <span class="req">*</span></label>
                            <input type="text" name="comment_name" class="form-input" placeholder="Your name" required
                                   value="<?= $auth->check() ? e($auth->user()['name'] ?? '') : '' ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email <span style="color:var(--color-text-muted);font-size:12px">(optional, not shown)</span></label>
                            <input type="email" name="comment_email" class="form-input" placeholder="your@email.com"
                                   value="<?= $auth->check() ? e($auth->user()['email'] ?? '') : '' ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Comment <span class="req">*</span></label>
                        <textarea name="comment_content" class="form-input" rows="4" placeholder="Share your thoughts…" required maxlength="2000"></textarea>
                        <div class="form-hint">Max 2000 characters. Comments are moderated before appearing.</div>
                    </div>
                    <button type="submit" class="btn btn-primary" data-loading="Submitting…">Post Comment</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Related Posts -->
<?php if (!empty($relatedPlugins)): ?>
<div style="background:var(--color-surface);border-top:1px solid var(--color-border);padding:40px 0">
    <div class="front-container">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
            <h3 style="font-size:20px;font-weight:700;margin:0">Explore Free Tools</h3>
            <a href="/plugins" class="btn btn-ghost btn-sm">Browse all &rarr;</a>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px">
            <?php foreach ($relatedPlugins as $rlp):
                $rlm = json_decode($rlp['manifest'] ?? '{}', true) ?? [];
            ?>
            <a href="/plugins/<?= e($rlp['slug']) ?>/" style="display:flex;align-items:center;gap:12px;padding:14px;text-decoration:none;background:var(--color-card);border:1px solid var(--color-border);border-radius:var(--radius-medium);color:var(--color-text);transition:border-color .15s"
               onmouseover="this.style.borderColor='var(--color-primary)'"
               onmouseout="this.style.borderColor=''">
                <div style="width:34px;height:34px;background:var(--color-primary-light);border-radius:var(--radius-small);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:18px">
                    <?= $rlm['icon'] ?: '<svg width="16" height="16" fill="none" stroke="var(--color-primary)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>' ?>
                </div>
                <div>
                    <div style="font-size:13px;font-weight:600;line-height:1.3"><?= e($rlp['name']) ?></div>
                    <div style="font-size:11px;color:var(--color-text-muted);margin-top:2px"><?= e(substr($rlp['description'] ?? '', 0, 40)) ?></div>
                </div>
            </a>
            <?php endforeach ?>
        </div>
    </div>
</div>
<?php endif ?>

<?php if (!empty($relatedPosts)): ?>
<div style="background:var(--color-surface);border-top:1px solid var(--color-border);padding:48px 0;margin-top:24px">
    <div class="front-container">
        <h3 style="font-size:20px;font-weight:700;margin-bottom:24px">More Articles</h3>
        <div class="blog-grid">
            <?php foreach ($relatedPosts as $rp): ?>
            <a href="/blog/<?= e($rp['slug']) ?>" class="blog-card">
                <?php if ($rp['cover_image']): ?>
                <img src="<?= e($rp['cover_image']) ?>" alt="<?= e($rp['title']) ?>" class="blog-card-cover">
                <?php else: ?>
                <div class="blog-card-cover-placeholder">
                    <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" opacity="0.4"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                </div>
                <?php endif ?>
                <div class="blog-card-body">
                    <div class="blog-card-title"><?= e($rp['title']) ?></div>
                    <?php if ($rp['excerpt']): ?>
                    <div class="blog-card-excerpt"><?= e(substr($rp['excerpt'], 0, 100)) ?>…</div>
                    <?php endif ?>
                    <div class="blog-card-meta"><?= fdate($rp['published_at'] ?? $rp['created_at']) ?></div>
                </div>
            </a>
            <?php endforeach ?>
        </div>
    </div>
</div>
<?php endif ?>

<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';

// JSON-LD BlogPosting schema
$blogJsonLd = '';
if ($seo instanceof Seo) {
    $blogJsonLd = $seo->blogPostSchema([
        'title'          => $post['title'],
        'slug'           => $post['slug'],
        'content'        => $post['content'] ?? '',
        'featured_image' => $post['featured_image'] ?? $post['og_image'] ?? $post['cover_image'] ?? '',
        'seo_desc'       => $post['meta_desc'] ?? $post['excerpt'] ?? '',
        'published_at'   => $post['published_at'] ?? $post['created_at'] ?? '',
        'updated_at'     => $post['updated_at'] ?? '',
        'author_name'    => $post['author_name'] ?? '',
    ]);
}

// Advanced SEO overrides saved via Admin -> SEO -> Blog Posts (keywords, canonical,
// robots, og_type, twitter card, custom JSON-LD, custom meta tags).
$_blogAdvMeta = json_decode($post['seo_meta'] ?? '', true) ?: [];
$_blogOpts = array_filter([
    'keywords'     => $_blogAdvMeta['keywords']     ?? null,
    'robots'       => $_blogAdvMeta['robots']       ?? null,
    'twitter_card' => $_blogAdvMeta['twitter_card'] ?? null,
    'custom_meta'  => $_blogAdvMeta['custom_meta']  ?? null,
    // og_type/canonical below already have explicit defaults, only override if advanced meta set one
    'og_type'      => $_blogAdvMeta['og_type']      ?? null,
], fn($v) => $v !== null);

if (!empty($_blogAdvMeta['schema_json'])) {
    $_blogCustomSchema = json_decode($_blogAdvMeta['schema_json'], true);
    if ($_blogCustomSchema && $seo instanceof Seo) $blogJsonLd = $seo->schemaOrg($_blogCustomSchema);
}

// Token context for {{blog_title}}/{{blog_excerpt}}/{{author_name}} in OG image URLs etc.
$_blogTokenContext = [
    'blog_title'   => $post['title'] ?? '',
    'blog_excerpt' => $post['excerpt'] ?? '',
    'author_name'  => $post['author_name'] ?? '',
];

render_page($post['title'], array_merge([
    'description'        => $post['meta_desc'] ?? $post['excerpt'] ?? substr(strip_tags($post['content'] ?? ''), 0, 160),
    'og_image'           => $post['og_image'] ?? $post['cover_image'] ?? '',
    'og_type'            => 'article',
    'og_title'           => $post['og_title'] ?? '',
    'og_description'     => $post['og_description'] ?? '',
    'canonical'          => '/blog/' . $slug,
    'article_published'  => $post['published_at'] ?? $post['created_at'] ?? '',
    'article_modified'   => $post['updated_at'] ?? $post['published_at'] ?? $post['created_at'] ?? '',
    'schema_org'         => $blogJsonLd,
    'token_context'      => $_blogTokenContext,
], $_blogOpts));
