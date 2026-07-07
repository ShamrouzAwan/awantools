<?php
defined('AWAN') or die();
require_once __DIR__ . '/_bootstrap.php';

// Maintenance mode check
if ($settings->isMaintenanceMode() && !$auth->isAdmin()) {
    http_response_code(503);
    die(renderError(503, 'Maintenance Mode', 'We are currently performing scheduled maintenance. Please check back soon.'));
}

// Track homepage view
if ($settings->get('analytics_enabled', '1') === '1' && !isBot()) {
    try {
        $db->insert('analytics_events', [
            'event' => 'page_view', 'path' => '/',
            'user_id'    => $auth->id(),
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    } catch (Exception $e) {}
}

// ─── Load Homepage Sections (DB-driven order + visibility) ────────────────────
$sectionOrder = [];
$sectionMap   = [];
try {
    $sectionRows = $db->fetchAll("SELECT * FROM homepage_sections ORDER BY sort_order ASC, id ASC") ?: [];
    foreach ($sectionRows as $row) {
        $row['cfg'] = json_decode($row['config'] ?? '{}', true) ?: [];
        $sectionMap[$row['section_key']] = $row;
        if ((int)$row['is_enabled'] === 1) {
            $sectionOrder[] = $row['section_key'];
        }
    }
} catch (Throwable $e) {
    // Fallback: show all core sections in default order
    $sectionOrder = ['hero', 'stats', 'search', 'featured_tools', 'tool_catalogs', 'why_us', 'blog', 'testimonials', 'cta'];
    foreach ($sectionOrder as $k) {
        $sectionMap[$k] = ['section_key' => $k, 'is_enabled' => 1, 'sort_order' => 0, 'cfg' => []];
    }
}

// Helper: get a section's config value with fallback
$sc = function(string $key, string $cfgKey, $default = '') use ($sectionMap): string {
    return (string)($sectionMap[$key]['cfg'][$cfgKey] ?? $default);
};

// ─── Live Stats ───────────────────────────────────────────────────────────────
$totalPluginsRow  = $db->fetch("SELECT COALESCE(SUM(offered),0) AS n FROM plugins WHERE status = 'active'");
$totalPlugins     = (int)($totalPluginsRow['n'] ?? 0);
$totalPluginCount = $db->count('plugins', "status = 'active'");
$totalUsers     = $db->count('users', "status = 'active'");
$totalVisitors  = $db->count('analytics_events', "event = 'page_view' AND created_at >= ?", [date('Y-m-d H:i:s', strtotime('-30 days'))]);
$totalBlogPosts = 0;
try { $totalBlogPosts = $db->count('blog_posts', "status = 'published'"); } catch (Exception $e) {}

// ─── Hero Settings ────────────────────────────────────────────────────────────
$heroImageUrl = $settings->get('hero_image_url', '');
$heroBadge    = $sc('hero', 'badge', '') ?: $settings->get('hero_badge', 'Free Online Tools Platform');
$heroTitle    = $sc('hero', 'title', '') ?: $settings->get('hero_title', 'One Platform.');
$heroAccent   = $settings->get('hero_title_accent', 'Unlimited Tools.');
$heroSub      = $settings->get('hero_subtitle', 'Access dozens of free online tools — calculators, converters, generators and more. No installs, no subscriptions.');
$heroCta      = $settings->get('hero_cta_text', 'Get Started Free');
$heroCtaUrl   = $settings->get('hero_cta_url', '/register');
$heroSecCta   = $settings->get('hero_secondary_cta_text', 'Browse Tools');
$heroSecUrl   = $settings->get('hero_secondary_cta_url', '/plugins');
$heroBadge    = $sc('hero', 'badge', '') ?: $settings->get('hero_badge', 'Free Online Tools Platform');

// Tool Catalog
$activePlugins = $db->fetchAll(
    "SELECT id, slug, name, version, description, manifest, offered FROM plugins WHERE status = 'active' ORDER BY name ASC"
) ?: [];

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

// ─── Featured Tools ───────────────────────────────────────────────────────────
$featuredLimit   = (int)$sc('featured_tools', 'limit', '6') ?: 6;
$recentPlugins   = [];
if (in_array('featured_tools', $sectionOrder)) {
    $recentPlugins = $db->fetchAll("SELECT * FROM plugins WHERE status = 'active' ORDER BY installed_at DESC LIMIT {$featuredLimit}") ?: [];
}

// ─── Blog Section ─────────────────────────────────────────────────────────────
$blogSectionTitle = $sc('blog', 'title', '') ?: $settings->get('blog_section_title', 'Latest Articles');
$blogLimit        = (int)$sc('blog', 'limit', '3') ?: 3;
$latestBlogPosts  = [];
if (in_array('blog', $sectionOrder)) {
    try {
        $latestBlogPosts = $db->fetchAll(
            "SELECT b.id, b.title, b.slug, b.excerpt, b.cover_image, b.published_at, b.created_at, u.name AS author_name
             FROM blog_posts b
             LEFT JOIN users u ON u.id = b.author_id
             WHERE b.status = 'published'
             ORDER BY b.published_at DESC, b.created_at DESC
             LIMIT {$blogLimit}"
        ) ?: [];
    } catch (Exception $e) {}
}

// ─── Testimonials ─────────────────────────────────────────────────────────────
$testimonialsTitle = $sc('testimonials', 'title', '') ?: $settings->get('testimonials_section_title', 'What People Say');
$testimonialsLimit = (int)$sc('testimonials', 'limit', '6') ?: 6;
$homeTestimonials  = [];
if (in_array('testimonials', $sectionOrder)) {
    try {
        $homeTestimonials = $db->fetchAll(
            "SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order ASC, id DESC LIMIT {$testimonialsLimit}"
        ) ?: [];
    } catch (Exception $e) {}
}

// ─── Why Us Section ───────────────────────────────────────────────────────────
$whyUsTitle = $sc('why_us', 'title', '') ?: "Why {$settings->siteName()}?";

// ─── FAQ Section ──────────────────────────────────────────────────────────────
$faqTitle = $sc('faq', 'title', '') ?: 'Frequently Asked Questions';
$homeFaqs = [];
if (in_array('faq', $sectionOrder)) {
    try {
        $homeFaqs = $db->fetchAll("SELECT * FROM faqs WHERE is_active=1 ORDER BY sort_order ASC, id ASC LIMIT 12") ?: [];
    } catch (Throwable $e) {}
}

// ─── CTA Section ──────────────────────────────────────────────────────────────
$ctaTitle    = $sc('cta', 'title',    '') ?: 'Stay in the Loop';
$ctaSubtitle = $sc('cta', 'subtitle', '') ?: 'Get notified about new tools, updates, and tutorials.';
$ctaBtnText  = $sc('cta', 'btn_text', '') ?: 'Subscribe Free';
$ctaBtnUrl   = $sc('cta', 'btn_url',  '') ?: '/newsletter';

// ─── Contact / Connect Section ────────────────────────────────────────────────
$contactTitle = $sc('contact', 'title', '') ?: 'Need Something Built?';
$devName      = $settings->get('developer_name', '');
$devTitle     = $settings->get('developer_title', 'Full-Stack Developer');
$devBio       = $settings->get('developer_bio', 'Have a project idea, a tool you need, or want to collaborate? Reach out — I\'d love to hear from you.');
$devPortfolio = $settings->get('developer_portfolio', '');
$devGithub    = $settings->get('developer_github', '');

// ─── Custom Block ─────────────────────────────────────────────────────────────
$customBlockHtml = $sectionMap['custom_block']['cfg']['content'] ?? '';

// ─── Search Placeholder ───────────────────────────────────────────────────────
$searchPlaceholder = $sc('search', 'placeholder', '') ?: 'Search for tools, blog posts, pages…';

// ─── Build each section's HTML ────────────────────────────────────────────────
$sectionHtml = [];

// HERO
ob_start();
?>
<section class="hero-section">
    <div class="hero-grid-bg"></div>
    <div class="hero-content">
      <div class="app-icon"><a href="intent://com.android.settings/#Intent;scheme=android-app;end;"><img src="https://awantools.site/storage/uploads/1782297667_28223b93_awantools-logo-100.png"></div></a>
     <div class="hero-badge">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <?= e($heroBadge) ?>
        </div>
      <h1 class="hero-title"><?= e($heroTitle) ?><br>
        <span class="text-muted"><?= e($heroAccent) ?></span>
      </h1>
      <p class="hero-subtitle"><?= e($heroSub) ?></p>
      <div class="hero-actions">
        <?php if ($auth->check()): ?>
                <a href="/account/dashboard" class="btn btn-primary btn-xl">Go to Dashboard</a>
                <?php if ($auth->isAdmin()): ?>
                <a href="/admin/" class="btn btn-secondary btn-lg">Admin Panel</a>
                <?php endif ?>
            <?php else: ?>
                <?php if ($settings->registrationEnabled()): ?>
                <a href="<?= e($heroCtaUrl) ?>" class="cta-button"><?= e($heroCta) ?></a>
                <?php endif ?>
                <a href="<?= e($heroSecUrl) ?>" class="secondary-button"><?= e($heroSecCta) ?></a>
            <?php endif ?>
      </div>
    </div>

    <div class="floating-wrapper">
      <div class="floating-element card-note" data-speed="1.2">
        <div class="note-text">
          Take notes to keep track of crucial details, and accomplish more with ease.
        </div>
        <div class="check-box-wrapper">
          <div class="check-box">✓</div>
        </div>
      </div>

      <div class="floating-element card-tasks" data-speed="-1.5">
        <div class="card-title">Today's tasks</div>
        <div class="task-item">
          <div class="task-info">
            <div class="task-dot bg-danger"></div>
            <span>New campaign ideas</span>
          </div>
          <div class="task-progress">
            <div class="progress-bar">
              <div class="progress-fill danger-fill" style="width: 60%"></div>
            </div>
            <span class="progress-text">60%</span>
          </div>
        </div>
        <div class="task-item">
          <div class="task-info">
            <div class="task-dot bg-success"></div>
            <span>Optimize UI / UX</span>
          </div>
          <div class="task-progress">
            <div class="progress-bar">
              <div class="progress-fill success-fill" style="width: 100%"></div>
            </div>
            <span class="progress-text">100%</span>
          </div>
        </div>
      </div>

      <div class="floating-element card-reminders" style="max-height:320px;overflow:auto;" data-speed="2">
        <div class="card-header">
          <div class="icon-box info-light-bg text-info">
            <svg fill="var(--color-text)" xmlns="http://www.w3.org/2000/svg" width="20px" height="20px" viewBox="0 0 2.5 2.5" enable-background="new 0 0 100 100" xml:space="preserve"><g><path d="m1.545 0.735 0.223 0.223c0.05 0.048 0.05 0.128 0 0.175L1.188 1.71V0.915l0.18 -0.182c0.048 -0.048 0.13 -0.048 0.177 0.003"/></g><path d="M0.938 0.5H0.625c-0.07 0 -0.125 0.055 -0.125 0.125v1.095C0.5 1.875 0.625 2 0.78 2s0.28 -0.125 0.28 -0.28V0.625c0.003 -0.07 -0.055 -0.125 -0.123 -0.125m-0.158 1.345c-0.07 0 -0.125 -0.055 -0.125 -0.125s0.055 -0.125 0.125 -0.125 0.125 0.055 0.125 0.125 -0.055 0.125 -0.125 0.125"/><g><path d="M1.875 1.438h-0.22l-0.15 0.15H1.85L1.848 1.85H1.245l-0.15 0.15H1.875c0.07 0 0.125 -0.055 0.125 -0.125V1.563c0 -0.068 -0.055 -0.125 -0.125 -0.125"/></g></svg>
          </div>
          <span class="card-title mb-0">Tools Offered</span>
        </div>
        <?php foreach ($allCategories as $cat): ?>
        <div class="reminder-block" style="margin-bottom: 5px;">
            <div class="reminder-title" style="display:flex;width:100%;justify-content:space-between;"><?= e($cat) ?>
            <div class="reminder-time"><svg width="18px" height="18px" viewBox="0 0 0.72 0.72" xmlns="http://www.w3.org/2000/svg"><title>file_type_bolt</title><path d="M0.203 0.045h0.314l-0.126 0.252h0.126l-0.22 0.378V0.392H0.203Z" style="fill:#fbc02d"/></svg></div>
            </div>
        </div>
            <?php endforeach ?> 
      </div>

      <div class="floating-element card-stats" data-speed="-1.8">
        <div class="card-title">Weekly Activity</div>
        <div class="chart-container">
          <div class="chart-bar-wrap">
            <div class="chart-bar bg-primary" style="height: 60%"></div>
          </div>
          <div class="chart-bar-wrap">
            <div class="chart-bar bg-info" style="height: 40%"></div>
          </div>
          <div class="chart-bar-wrap">
            <div class="chart-bar bg-success" style="height: 85%"></div>
          </div>
          <div class="chart-bar-wrap">
            <div class="chart-bar bg-warning" style="height: 50%"></div>
          </div>
          <div class="chart-bar-wrap">
            <div class="chart-bar bg-primary" style="height: 75%"></div>
          </div>
        </div>
        <div class="chart-labels">
          <span>M</span><span>T</span><span>W</span><span>T</span><span>F</span>
        </div>
      </div>

      <div class="floating-element card-integrations" data-speed="1.4">
        <div class="card-title"><?= $totalPlugins ?>+ Active Tools</div>
        <div class="integration-icons">
          <div  class="icon-placeholder danger-light-bg text-danger"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg></div>
          <div class="icon-placeholder warning-light-bg text-warning"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0 1 12 6.844a9.59 9.59 0 0 1 2.504.337c1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.02 10.02 0 0 0 22 12.017C22 6.484 17.522 2 12 2z"></path></svg></div>
          <div class="icon-placeholder info-light-bg text-info"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><rect x="5" y="5" width="3" height="3" fill="currentColor" stroke="none"></rect><rect x="16" y="5" width="3" height="3" fill="currentColor" stroke="none"></rect><rect x="5" y="16" width="3" height="3" fill="currentColor" stroke="none"></rect><path d="M14 14h3v3h-3zM17 17h3v3h-3zM14 20h3"></path></svg></div>
          <div  class="icon-placeholder danger-light-bg text-danger"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg></div>
        </div>
      </div>

      <div class="floating-element cursor cursor-1" data-speed="-2.5">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="var(--color-primary)" xmlns="http://www.w3.org/2000/svg">
          <path d="M5.5 3.21V20.8c0 .45.54.67.85.35l4.86-4.86a.5.5 0 0 1 .35-.15h6.94c.45 0 .67-.54.35-.85L5.5 3.21z" />
        </svg>
        <div class="cursor-label label-primary"></div>
      </div>

      <div class="floating-element cursor cursor-2" data-speed="1.8">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="var(--color-success)" xmlns="http://www.w3.org/2000/svg">
          <path d="M5.5 3.21V20.8c0 .45.54.67.85.35l4.86-4.86a.5.5 0 0 1 .35-.15h6.94c.45 0 .67-.54.35-.85L5.5 3.21z" />
        </svg>
        <div class="cursor-label label-success"></div>
      </div>
    </div>
  </section>
<script>

  document.addEventListener("DOMContentLoaded", () => {
        const floatingElements = document.querySelectorAll('.floating-element:not(.cursor)');
        const cursors = document.querySelectorAll('.cursor');
        const heroSection = document.querySelector('.hero-section');

        let mouseX = 0;
        let mouseY = 0;
        let currentX = 0;
        let currentY = 0;
        let isMobile = window.innerWidth <= 992;

        // Handle window resize to toggle JS animations
        window.addEventListener('resize', () => {
          isMobile = window.innerWidth <= 992;
          if (isMobile) {
            // Reset transforms so CSS Grid can take over
            floatingElements.forEach(el => el.style.transform = '');
            cursors.forEach(el => el.style.transform = '');
          }
        });

        heroSection.addEventListener('mousemove', (e) => {
          if (isMobile) return;
          mouseX = (e.clientX - window.innerWidth / 2) / 60;
          mouseY = (e.clientY - window.innerHeight / 2) / 60;
        });

        function animateParallax() {
          if (!isMobile) {
            currentX += (mouseX - currentX) * 0.08;
            currentY += (mouseY - currentY) * 0.08;

            // Animate Cards
            floatingElements.forEach(element => {
              const speed = parseFloat(element.getAttribute('data-speed')) || 1;
              const x = currentX * speed;
              const y = currentY * speed;

              // Preserve CSS rotation if it exists (for the sticky note)
              const isNote = element.classList.contains('card-note');
              const rotation = isNote ? 'rotate(-3deg)' : '';

              element.style.transform = `translate(${x}px, ${y}px) ${rotation}`;
              element.style.animation = 'none'; // Disable keyframes when JS takes over
            });

            // Animate Cursors with a slightly different feel
            cursors.forEach(cursor => {
              const speed = parseFloat(cursor.getAttribute('data-speed')) || 1;
              const x = currentX * (speed * 1.5);
              const y = currentY * (speed * 1.5);
              cursor.style.transform = `translate(${x}px, ${y}px)`;
            });
          }
          requestAnimationFrame(animateParallax);
        }

        animateParallax();
      });
</script>
<?php $sectionHtml['hero'] = ob_get_clean();

// STATS
ob_start(); ?>
<div class="stats-strip">
    <div class="stats-strip-inner">
        <div class="stats-strip-item">
            <div class="stats-strip-icon"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z"/></svg></div>
            <div>
                <div class="stats-strip-value" data-counter data-target="<?= $totalPlugins ?>"><?= $totalPlugins ?></div>
                <div class="stats-strip-label">Active Tools</div>
            </div>
        </div>
        <div class="stats-strip-item">
            <div class="stats-strip-icon"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg></div>
            <div>
                <div class="stats-strip-value" data-counter data-target="<?= $totalPluginCount ?>"><?= $totalPluginCount ?></div>
                <div class="stats-strip-label">Plugins</div>
            </div>
        </div>
        <div class="stats-strip-item">
            <div class="stats-strip-icon"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
            <div>
                <div class="stats-strip-value" data-counter data-target="<?= $totalUsers ?>"><?= $totalUsers ?></div>
                <div class="stats-strip-label">Registered Users</div>
            </div>
        </div>
        <div class="stats-strip-item">
            <div class="stats-strip-icon"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div>
            <div>
                <div class="stats-strip-value" data-counter data-target="<?= $totalVisitors ?>"><?= $totalVisitors ?></div>
                <div class="stats-strip-label">Visitors (Last Hour)</div>
            </div>
        </div>
        <div class="stats-strip-item">
            <div class="stats-strip-icon"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></div>
            <div>
                <div class="stats-strip-value" data-counter data-target="<?= $totalBlogPosts ?>"><?= $totalBlogPosts ?></div>
                <div class="stats-strip-label">Blog Articles</div>
            </div>
        </div>
        <div class="stats-strip-item">
            <div class="stats-strip-icon"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
            <div>
                <div class="stats-strip-value">100%</div>
                <div class="stats-strip-label">Free Usage</div>
            </div>
        </div>
    </div>
</div>
<?php $sectionHtml['stats'] = ob_get_clean();


// FEATURED TOOLS
ob_start();
if (!empty($recentPlugins)): ?>
<section class="front-section" style="background:var(--color-surface);border-top:1px solid var(--color-border);border-bottom:1px solid var(--color-border)">
    <div class="front-container">
        <div class="section-header">
            <div class="section-eyebrow">Available Now</div>
            <h2 class="section-title">Recently Added Tools</h2>
            <p class="section-sub">The latest free tools and applications added to the platform.</p>
        </div>
        <div class="tools-grid">
            <?php foreach ($recentPlugins as $plugin): ?>
            <?php $manifest = json_decode($plugin['manifest'] ?? '{}', true); ?>
            <a href="/plugins/<?= e($plugin['slug']) ?>" class="tool-card">
                <div class="tool-card-icon">
                    <?php $iconName = $manifest['og_icon'] ?? $manifest['icon'] ?? ''; ?>
                    <?php if ($iconName): ?>
                    <i class="fa-solid fa-<?= e($iconName) ?>" style="font-size:22px"></i>
                    <?php else: ?>
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z"/><line x1="16" y1="8" x2="2" y2="22"/></svg>
                    <?php endif ?>
                </div>
                <div>
                    <div class="tool-card-name"><?= e($plugin['name']) ?></div>
                    <div class="tool-card-desc"><?= e(substr($plugin['description'] ?? 'No description available.', 0, 100)) ?></div>
                </div>
                <div class="tool-card-meta">
                    <span class="badge badge-success">Active</span>
                    <?php if ($plugin['version']): ?><span class="badge badge-neutral">v<?= e($plugin['version']) ?></span><?php endif ?>
                </div>
            </a>
            <?php endforeach ?>
        </div>
        <div style="text-align:center;margin-top:32px">
            <a href="/plugins" class="btn btn-secondary btn-lg">Browse All Tools</a>
        </div>
    </div>
</section>
<?php endif;
$sectionHtml['featured_tools'] = ob_get_clean();


// SEARCH
ob_start(); ?>
<section class="global-search-section">
    <div class="front-container">
        <div class="section-header" style="margin-bottom:28px">
            <div class="section-eyebrow">Search</div>
            <h2 class="section-title">Find Tools, Articles &amp; More</h2>
        </div>
        <form class="search-box" action="/search" method="GET">
            <input type="text" name="q" placeholder="<?= e($searchPlaceholder) ?>" autocomplete="off" aria-label="Search" oninput="homeSearchLive(this.value)">
            <button type="submit" class="search-box-btn" aria-label="Search">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </button>
            <div class="search-results" id="home-search-results"></div>
        </form>
        <script>
        var _homeTimer;
        function homeSearchLive(q) {
            clearTimeout(_homeTimer);
            var res = document.getElementById('home-search-results');
            if (!res) return;
            if (q.length < 2) { res.style.display='none'; res.innerHTML=''; return; }
            _homeTimer = setTimeout(function(){
                fetch('/api/search?q=' + encodeURIComponent(q))
                    .then(function(r){ return r.json(); })
                    .then(function(data){
                        if (!data.success || data.data.total === 0) { res.style.display='none'; return; }
                        var d = data.data, html = '';
                        (d.plugins||[]).forEach(function(p){ html += '<a href="/plugins/'+p.slug+'/" class="search-result-item"><strong>'+p.name+'</strong><span>Tool</span></a>'; });
                        (d.posts||[]).forEach(function(p){ html += '<a href="/blog/'+p.slug+'" class="search-result-item"><strong>'+p.title+'</strong><span>Article</span></a>'; });
                        html += '<a href="/search?q='+encodeURIComponent(q)+'" class="search-result-item" style="font-size:12px;color:var(--color-primary)">See all results &rarr;</a>';
                        res.innerHTML = html;
                        res.style.display = 'block';
                    }).catch(function(){ res.style.display='none'; });
            }, 250);
        }
        document.addEventListener('click', function(e){
            var res = document.getElementById('home-search-results');
            if (res && !res.closest('.search-box').contains(e.target)) res.style.display = 'none';
        });
        </script>
    </div>
</section>
<?php $sectionHtml['search'] = ob_get_clean();


// TOOL CATALOGS — Categories grid + Recently Added + Top Tools
ob_start();
// Data for catalogs
$catalogCats    = [];
$catalogRecent  = [];
$catalogTop     = [];
$catalogFavs    = [];
try {
    $allPluginsForCatalog = $db->fetchAll("SELECT id, slug, name, description, manifest, view_count, installed_at FROM plugins WHERE status='active' ORDER BY name ASC") ?: [];
    foreach ($allPluginsForCatalog as $cp) {
        $m = json_decode($cp['manifest'] ?? '{}', true) ?? [];
        $cats = $m['categories'] ?? (isset($m['category']) ? [$m['category']] : []);
        foreach ($cats as $c) {
            $c = trim($c);
            if ($c) $catalogCats[$c] = ($catalogCats[$c] ?? 0) + 1;
        }
    }
    ksort($catalogCats);
    $catalogTop    = array_slice(array_filter($allPluginsForCatalog, fn($p) => ($p['view_count'] ?? 0) > 0
        ? true : true), 0, 6);
    usort($catalogTop, fn($a, $b) => ($b['view_count'] ?? 0) <=> ($a['view_count'] ?? 0));
    $catalogTop    = array_slice($catalogTop, 0, 6);
    usort($allPluginsForCatalog, fn($a, $b) => strcmp($b['installed_at'] ?? '', $a['installed_at'] ?? ''));
    $catalogRecent = array_slice($allPluginsForCatalog, 0, 6);
    if ($auth->check()) {
        $catalogFavs = $db->fetchAll(
            "SELECT p.id, p.slug, p.name, p.description, p.manifest FROM plugins p JOIN user_favourites uf ON uf.plugin_id = p.id WHERE uf.user_id = ? AND p.status = 'active' ORDER BY uf.created_at DESC LIMIT 6",
            [$auth->id()]
        ) ?: [];
    }
} catch (Throwable $e) {}
?>
<?php if (!empty($catalogCats) || !empty($catalogRecent)): ?>
<section class="front-section" style="padding-top:0">
    <div class="front-container">

        <?php if (!empty($catalogCats)): ?>
        <!-- Categories Grid -->
        <div style="margin-bottom:48px">
            <div class="section-header" style="margin-bottom:24px">
                <div class="section-eyebrow">Browse by Category</div>
                <h2 class="section-title" style="font-size:28px">Find the Right Tool</h2>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px">
                <?php foreach ($catalogCats as $catName => $catCount): ?>
                <a href="/plugins?cat=<?= urlencode($catName) ?>" style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:20px 12px;text-align:center;text-decoration:none;background:var(--color-card);border:1px solid var(--color-border);border-radius:var(--radius-medium);transition:border-color .15s,box-shadow .15s;color:var(--color-text)"
                   onmouseover="this.style.borderColor='var(--color-primary)';this.style.boxShadow='var(--shadow-small)'"
                   onmouseout="this.style.borderColor='';this.style.boxShadow=''">
                    <div style="font-size:12px;font-weight:700;margin-bottom:4px"><?= e($catName) ?></div>
                    <div style="font-size:11px;color:var(--color-text-muted)"><?= $catCount ?> <?= $catCount === 1 ? 'tool' : 'tools' ?></div>
                </a>
                <?php endforeach ?>
            </div>
        </div>
        <?php endif ?>

        <?php if ($auth->check() && !empty($catalogFavs)): ?>
        <!-- User Favourites -->
        <div style="margin-bottom:48px">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
                <div>
                    <div class="section-eyebrow">Your Saves</div>
                    <h2 class="section-title" style="font-size:26px;margin:0">Favourite Tools</h2>
                </div>
                <a href="/account/dashboard" class="btn btn-ghost btn-sm">View all &rarr;</a>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px">
                <?php foreach ($catalogFavs as $fp):
                    $fm = json_decode($fp['manifest'] ?? '{}', true) ?? [];
                ?>
                <a href="/plugins/<?= e($fp['slug']) ?>/" style="display:flex;align-items:center;gap:12px;padding:16px;text-decoration:none;background:var(--color-card);border:1px solid var(--color-border);border-radius:var(--radius-medium);color:var(--color-text);transition:border-color .15s"
                   onmouseover="this.style.borderColor='var(--color-primary)'"
                   onmouseout="this.style.borderColor=''">
                    <div style="width:36px;height:36px;background:var(--color-primary-light);border-radius:var(--radius-small);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:18px">
                        <?php $fmIcon = $fm['og_icon'] ?? $fm['icon'] ?? ''; ?>
                        <?= $fmIcon ? '<i class="fa-solid fa-' . e($fmIcon) . '" style="font-size:18px;color:var(--color-primary)"></i>' : '<svg width="16" height="16" fill="none" stroke="var(--color-primary)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>' ?>
                    </div>
                    <div>
                        <div style="font-size:13px;font-weight:600"><?= e($fp['name']) ?></div>
                        <div style="font-size:11px;color:var(--color-text-muted)"><?= e(substr($fp['description'] ?? '', 0, 35)) ?></div>
                    </div>
                </a>
                <?php endforeach ?>
            </div>
        </div>
        <?php endif ?>

        <?php if (!empty($catalogRecent)): ?>
        <!-- Recently Added -->
        <div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
                <div>
                    <div class="section-eyebrow">Just Added</div>
                    <h2 class="section-title" style="font-size:26px;margin:0">New Tools</h2>
                </div>
                <a href="/plugins" class="btn btn-ghost btn-sm">All tools &rarr;</a>
            </div>
            <div class="tools-grid">
                <?php foreach ($catalogRecent as $rp):
                    $rm = json_decode($rp['manifest'] ?? '{}', true) ?? [];
                ?>
                <a href="/plugins/<?= e($rp['slug']) ?>/" class="tool-card">
                    <?php $rmIcon = $rm['og_icon'] ?? $rm['icon'] ?? ''; ?>
                    <div class="tool-card-icon"><?= $rmIcon ? '<i class="fa-solid fa-' . e($rmIcon) . '" style="font-size:20px"></i>' : '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z"/><line x1="16" y1="8" x2="2" y2="22"/></svg>' ?></div>
                    <div class="tool-card-name"><?= e($rp['name']) ?></div>
                    <div class="tool-card-desc"><?= e(substr($rp['description'] ?? '', 0, 60)) ?></div>
                </a>
                <?php endforeach ?>
            </div>
        </div>
        <?php endif ?>

    </div>
</section>
<?php endif;
$sectionHtml['tool_catalogs'] = ob_get_clean();

// WHY US
ob_start(); ?>
<section class="front-section">
    <div class="front-container">
        <div class="section-header">
            <div class="section-eyebrow">Benefits</div>
            <h2 class="section-title"><?= e($whyUsTitle) ?></h2>
            <p class="section-sub">Everything you need — no installs, no extra logins, no subscriptions.</p>
        </div>
        <div class="tools-grid">
            <?php
            $points = [
                [$sc('why_us','point1','') ?: 'Dozens of Free Tools',          $sc('why_us','point1_desc','') ?: 'A growing collection of calculators, converters, generators, and utilities. New tools added regularly.'],
                [$sc('why_us','point2','') ?: 'One Account for Everything',     $sc('why_us','point2_desc','') ?: 'Register once and use every tool on the platform. Your profile works across all applications.'],
                ['Your Data, Securely Stored',  'Everything you create is saved to your account. Access your data any time, from any device.'],
                ['Always Free',                 'No subscriptions, no paywalls, no hidden charges. Every tool is free to use forever.'],
                ['Works on Any Device',         'Fully responsive — use any tool from your phone, tablet, or desktop. No app installs required.'],
                ['Request Any Tool',            'Don\'t see what you need? Submit a request and it may be added to the platform.'],
            ];
            $pointIcons = ['<path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z"/><line x1="16" y1="8" x2="2" y2="22"/>','<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>','<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>','<polyline points="20 6 9 17 4 12"/>','<rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>','<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'];
            foreach ($points as $i => [$pName, $pDesc]): ?>
            <div class="tool-card" style="text-decoration:none;pointer-events:none">
                <div class="tool-card-icon"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><?= $pointIcons[$i] ?? $pointIcons[0] ?></svg></div>
                <div class="tool-card-name"><?= e($pName) ?></div>
                <div class="tool-card-desc"><?= e($pDesc) ?></div>
            </div>
            <?php endforeach ?>
        </div>
    </div>
</section>
<?php $sectionHtml['why_us'] = ob_get_clean();

// BLOG
ob_start();
if (!empty($latestBlogPosts)): ?>
<section class="front-section" style="background:var(--color-background)">
    <div class="front-container">
        <div class="section-header">
            <div class="section-eyebrow">Blog</div>
            <h2 class="section-title"><?= e($blogSectionTitle) ?></h2>
            <p class="section-sub">Tutorials, updates, and insights from the platform.</p>
        </div>
        <div class="blog-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:24px;margin-top:8px">
            <?php foreach ($latestBlogPosts as $bp): ?>
            <?php
                $postDate    = $bp['published_at'] ?: $bp['created_at'];
                $postExcerpt = $bp['excerpt'] ?: '';
                if (!$postExcerpt && !empty($bp['content'])) $postExcerpt = substr(strip_tags($bp['content']), 0, 140);
            ?>
            <a href="/blog/<?= e($bp['slug']) ?>" class="blog-card" style="display:block;text-decoration:none;background:var(--color-surface);border:1px solid var(--color-border);border-radius:var(--radius-large);overflow:hidden;transition:box-shadow .2s,transform .2s" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.1)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
                <?php if ($bp['cover_image']): ?>
                <div style="height:180px;overflow:hidden;background:var(--color-primary-light)"><img src="<?= e($bp['cover_image']) ?>" alt="<?= e($bp['title']) ?>" style="width:100%;height:100%;object-fit:cover;display:block"></div>
                <?php else: ?>
                <div style="height:6px;background:linear-gradient(90deg,var(--color-primary),var(--color-primary-hover))"></div>
                <?php endif ?>
                <div style="padding:20px">
                    <div style="font-size:12px;color:var(--color-text-muted);margin-bottom:8px"><?= $postDate ? date('F j, Y', strtotime($postDate)) : '' ?><?php if ($bp['author_name']): ?> &bull; <?= e($bp['author_name']) ?><?php endif ?></div>
                    <div style="font-size:16px;font-weight:700;color:var(--color-text);margin-bottom:8px;line-height:1.35"><?= e($bp['title']) ?></div>
                    <?php if ($postExcerpt): ?><div style="font-size:13px;color:var(--color-text-secondary);line-height:1.6"><?= e($postExcerpt) ?><?= strlen($postExcerpt) >= 140 ? '…' : '' ?></div><?php endif ?>
                    <div style="margin-top:14px;font-size:13px;font-weight:600;color:var(--color-primary)">Read More &rarr;</div>
                </div>
            </a>
            <?php endforeach ?>
        </div>
        <?php if ($totalBlogPosts > $blogLimit): ?>
        <div style="text-align:center;margin-top:32px"><a href="/blog" class="btn btn-secondary btn-lg">View All Articles</a></div>
        <?php endif ?>
    </div>
</section>
<?php endif;
$sectionHtml['blog'] = ob_get_clean();

// TESTIMONIALS
ob_start();
if (!empty($homeTestimonials)): ?>
<section class="front-section" style="background:var(--color-surface);border-top:1px solid var(--color-border);border-bottom:1px solid var(--color-border)">
    <div class="front-container">
        <div class="section-header">
            <div class="section-eyebrow">Testimonials</div>
            <h2 class="section-title"><?= e($testimonialsTitle) ?></h2>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px;margin-top:8px">
            <?php foreach ($homeTestimonials as $t): ?>
            <div style="background:var(--color-background);border:1px solid var(--color-border);border-radius:var(--radius-large);padding:24px;display:flex;flex-direction:column;gap:16px">
                <div style="color:var(--color-text-secondary);font-size:14px;line-height:1.7;font-style:italic;flex:1">"<?= e($t['testimonial']) ?>"</div>
                <div>
                    <div style="color:#f59e0b;font-size:14px;margin-bottom:10px"><?= str_repeat('★', (int)$t['rating']) . str_repeat('☆', 5 - (int)$t['rating']) ?></div>
                    <div style="display:flex;align-items:center;gap:12px">
                        <?php if ($t['photo']): ?>
                        <img src="<?= e($t['photo']) ?>" alt="<?= e($t['name']) ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover;flex-shrink:0">
                        <?php else: ?>
                        <div style="width:40px;height:40px;border-radius:50%;background:var(--color-primary-light);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;color:var(--color-primary);flex-shrink:0"><?= strtoupper(substr($t['name'],0,1)) ?></div>
                        <?php endif ?>
                        <div>
                            <div style="font-weight:700;font-size:14px;color:var(--color-text)"><?= e($t['name']) ?></div>
                            <?php if ($t['company'] || $t['title']): ?>
                            <div style="font-size:12px;color:var(--color-text-muted)"><?= e(trim(($t['title'] ? $t['title'] . ($t['company'] ? ', ' : '') : '') . ($t['company'] ?? ''))) ?></div>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach ?>
        </div>
    </div>
</section>


<section class="exact-hero-section">
  <!-- Soft Blurred Background Glow matching the reference image -->
  <div class="exact-glow-bg"></div>

  <div class="exact-container">
    
    <!-- Left Column: Copy & Input -->
    <div class="exact-left-col">
      <h1 class="exact-headline">&lt;/ About</h1>
      <p class="exact-subhead">Awan Tools is a free collection of fast, reliable, and privacy-friendly online tools designed to simplify everyday tasks. From quick utilities to professional workflows — no complexity, no ads, no subscriptions.
        across major search engines.
      </p>
      <div class="exact-microcopy">
        <span>• No Extra Login</span><span>• Private</span>
        <span>• Free Forever</span>
      </div>
    </div>

    <!-- Right Column: 2x2 Dashboard Grid -->
    <div class="exact-right-col">
      
      <!-- Card 1: Top Gainers -->
      <div class="exact-card card-top-gainers">
        <div class="card-header">
          <span class="card-title">Developer</span>
          <span class="card-title-right">Available Tools</span>
        </div>
        <div class="gainer-list">
          <div class="gainer-row">
            <span class="gainer-name">JSON Tools</span>
            <span class="gainer-badge rank-up">19+</span>
          </div>
          <div class="gainer-row">
            <span class="gainer-name">Scannable</span>
            <span class="gainer-badge rank-up">15+</span>
          </div>
          <div class="gainer-row">
            <span class="gainer-name">JavaScript Tools</span>
            <span class="gainer-badge rank-up">10+</span>
          </div>
          <div class="gainer-row">
            <span class="gainer-name">Encoding Tools</span>
            <span class="gainer-badge rank-up">20+</span>
          </div>
          <div class="gainer-row">
            <span class="gainer-name">Network Tools</span>
            <span class="gainer-badge rank-up">20+</span>
          </div>
        </div>
      </div>

      <!-- Card 2: Site Audit -->
      <div class="exact-card card-site-audit">
        <span class="card-title text-center">Site Growth </span>
        <div class="semi-donut-container">
          <svg viewBox="0 0 100 50" class="semi-donut">
            <path d="M 10 50 A 40 40 0 0 1 90 50" fill="none" stroke="var(--color-border)" stroke-width="12" stroke-linecap="butt"></path>
            <path d="M 10 50 A 40 40 0 0 1 90 50" fill="none" stroke-width="12" stroke-linecap="butt" stroke-dasharray="80, 125" stroke="var(--color-primary)"></path>
            <defs>
              
            </defs>
          </svg>
          <div class="donut-text">
            <strong>+60%</strong>
            <span>last week</span>
          </div>
        </div>
        <div class="audit-stats-row">
          <div class="stat-block">
            <span class="stat-label">Visitors</span>
            <span class="stat-val">+3650</span>
          </div>
          <div class="stat-block">
            <span class="stat-label">New Registrations</span>
            <span class="stat-val">+430</span>
          </div>
        </div>
      </div>

      <!-- Card 3: Position Tracking -->
      <div class="exact-card card-position">
        <span class="card-title">Feedbacks</span>
        <div class="position-stats">
          <span class="stat-label">Last Week</span>
          <span class="stat-val-large">+42.5%</span>
        </div>
        <div class="chart-container">
          <svg viewBox="0 0 100 40" preserveAspectRatio="none" class="line-chart">
            <!-- Area fill under line -->
            
            <!-- Line -->
            <path fill="none" stroke="var(--color-primary)" d="M0,25 L15,30 L30,22 L50,28 L70,20 L85,25 L100,15" stroke-width="5"></path>
            <defs>
              
            </defs>
          </svg>
        </div>
      </div>

      <!-- Card 4: Keyword Difficulty -->
      <div class="exact-card card-difficulty">
        
        <div class="circle-gauge-container">
          <svg class="circle-gauge" viewBox="0 0 40 40">
            <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="var(--color-border)" stroke-width="4"></path>
            <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#2DD4BF" stroke-dasharray="65, 100" stroke-width="4"></path>
          </svg>
          <div class="gauge-center">
            <span class="gauge-number">75<small>/100</small></span>
            
          </div>
        </div>
        <span class="gauge-footer">Tools Coverage</span>
      </div>

    </div>
  </div>
</section>
<?php endif;
$sectionHtml['testimonials'] = ob_get_clean();
// FAQ
ob_start();
if (!empty($homeFaqs)): ?>
<section class="front-section" style="background:var(--color-background);border-top:1px solid var(--color-border)">
    <div class="front-container" style="max-width:780px">
        <div class="section-header">
            <div class="section-eyebrow">FAQ</div>
            <h2 class="section-title"><?= e($faqTitle) ?></h2>
        </div>
        <div style="display:flex;flex-direction:column;gap:0;border:1px solid var(--color-border);border-radius:var(--radius-large);overflow:hidden;margin-top:8px">
            <?php foreach ($homeFaqs as $i => $fq): ?>
            <div style="border-top:<?= $i > 0 ? '1px solid var(--color-border)' : 'none' ?>">
                <button type="button"
                        onclick="toggleFaq(<?= $i ?>)"
                        style="width:100%;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:18px 20px;background:none;border:none;cursor:pointer;text-align:left;font-size:15px;font-weight:600;color:var(--color-text)">
                    <span><?= e($fq['question']) ?></span>
                    <svg id="faq-icon-<?= $i ?>" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="flex-shrink:0;transition:transform .2s"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div id="faq-body-<?= $i ?>" style="display:none;padding:0 20px 18px;font-size:14px;color:var(--color-text-secondary);line-height:1.7">
                    <?= e($fq['answer']) ?>
                </div>
            </div>
            <?php endforeach ?>
        </div>
    </div>
</section>
<script>
function toggleFaq(i) {
    var body = document.getElementById('faq-body-' + i);
    var icon = document.getElementById('faq-icon-' + i);
    var open = body.style.display !== 'none';
    body.style.display = open ? 'none' : 'block';
    if (icon) icon.style.transform = open ? '' : 'rotate(180deg)';
}
</script>
<?php elseif (in_array('faq', $sectionOrder)): ?>
<!-- FAQ section enabled but no active FAQs — add some from Admin > FAQs -->
<?php endif;
$sectionHtml['faq'] = ob_get_clean();

// CTA (newsletter inline)
ob_start(); ?>
<section class="front-section-sm" style="background:var(--color-surface);border-top:1px solid var(--color-border);border-bottom:1px solid var(--color-border)">
    <div class="front-container">
        <div class="newsletter-section">
            <h3><?= e($ctaTitle) ?></h3>
            <p><?= e($ctaSubtitle) ?></p>
            <form class="newsletter-form" novalidate id="home-nl-form">
                <input type="email" id="home-nl-email" placeholder="you@example.com" required aria-label="Email address">
                <button type="submit" class="btn btn-primary" id="home-nl-btn"><?= e($ctaBtnText) ?></button>
            </form>
            <div id="home-nl-msg" style="display:none;margin-top:10px;font-size:13px"></div>
        </div>
    </div>
</section>
<script>
(function(){
    var form = document.getElementById('home-nl-form');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var email = document.getElementById('home-nl-email').value.trim();
        var btn   = document.getElementById('home-nl-btn');
        var msg   = document.getElementById('home-nl-msg');
        if (!email) return;
        btn.disabled = true;
        btn.textContent = 'Subscribing…';
        fetch('/api/v1/newsletter', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({email: email})
        }).then(function(r){ return r.json(); }).then(function(data) {
            msg.style.display = 'block';
            if (data.success) {
                msg.style.color = 'var(--color-success,#22c55e)';
                msg.textContent = data.message || 'You are subscribed!';
                form.style.display = 'none';
            } else {
                msg.style.color = 'var(--color-danger,#ef4444)';
                msg.textContent = data.error || 'Something went wrong.';
                btn.disabled = false;
                btn.textContent = '<?= e($ctaBtnText) ?>';
            }
        }).catch(function() {
            msg.style.display = 'block';
            msg.style.color = 'var(--color-danger,#ef4444)';
            msg.textContent = 'Network error. Please try again.';
            btn.disabled = false;
            btn.textContent = '<?= e($ctaBtnText) ?>';
        });
    });
})();
</script>
<?php $sectionHtml['cta'] = ob_get_clean();

// CONTACT (Developer connect card)
ob_start(); ?>
<section class="connect-section">
    <div class="front-container">
        <div class="section-header" style="margin-bottom:36px">
            <div class="section-eyebrow">Connect</div>
            <h2 class="section-title"><?= e($contactTitle) ?></h2>
        </div>
        <div class="connect-card">
            <div>
                <div class="connect-card-title"><?= $devName ? e($devName) : 'Available for Projects' ?></div>
                <div class="connect-card-sub"><?= e($devBio) ?></div>
                <div class="connect-card-badges">
                    <?php if ($devTitle): ?><span class="connect-card-badge"><?= e($devTitle) ?></span><?php endif ?>
                    <span class="connect-card-badge">Open to Projects</span>
                    <span class="connect-card-badge">Custom Tools</span>
                </div>
                <div class="connect-card-actions">
                    <a href="/get-a-quote" class="btn btn-primary">Get a Quote</a>
                    <a href="/contact" class="btn btn-secondary">Contact Me</a>
                    <?php if ($devPortfolio): ?><a href="<?= e($devPortfolio) ?>" class="btn btn-ghost" target="_blank">Portfolio</a><?php endif ?>
                    <?php if ($devGithub): ?><a href="<?= e($devGithub) ?>" class="btn btn-ghost" target="_blank">GitHub</a><?php endif ?>
                </div>
            </div>
            <?php $devInitials = $devName ? strtoupper(substr($devName, 0, 2)) : 'DEV'; ?>
            <div class="connect-avatar"><?= e($devInitials) ?></div>
        </div>
    </div>
</section>
<?php $sectionHtml['contact'] = ob_get_clean();

// CUSTOM BLOCK
ob_start();
if ($customBlockHtml): ?>
<section class="front-section" style="background:var(--color-surface);border-top:1px solid var(--color-border)">
    <div class="front-container">
        <?= $customBlockHtml ?>
    </div>
</section>
<?php endif;
$sectionHtml['custom_block'] = ob_get_clean();

// ─── Output all sections in DB-defined order ──────────────────────────────────
ob_start();
foreach ($sectionOrder as $sectionKey) {
    if (isset($sectionHtml[$sectionKey])) {
        echo $sectionHtml[$sectionKey];
    }
}
// Sections not in DB map (e.g. if sections table is empty) — ensure hero always shows
if (empty($sectionOrder) && isset($sectionHtml['hero'])) {
    foreach (['hero','stats','search','featured_tools','why_us','blog','testimonials','cta'] as $k) {
        echo $sectionHtml[$k] ?? '';
    }
}
$content = ob_get_clean();

// --- Dynamic OG Image (Previewer Toolkit profile card) ---
// Use siteUrl() so the URL is always the public-facing host (respects site_url DB
// setting, falls back to REPLIT_DOMAINS on Replit dev, then HTTP_HOST elsewhere).
$_ogBase    = rtrim(siteUrl(), '/');
// Tool count: real sum of offered tools for active plugins, rounded down to nearest 10
$_ogCount   = max(10, (int)(floor(max(1, $totalPlugins) / 10) * 10));
$_ogBadge   = $_ogCount . '+ Active Tools,No Subscriptions,Free Forever';
$_ogParams  = http_build_query([
    'category'          => 'profile',
    'template'          => 'minimal',
    'icon'              => 'terminal',
    'heading'           => 'Awan Tools',
    'subheading'        => 'Free Online Utilities',
    'description'       => "Free collection of fast, reliable, and privacy-friendly online tools designed to simplify everyday tasks. From quick utilities to professional workflows.\nNo complexity, No extra logins, No subscriptions.",
    'footer'            => 'Developer: Shamrouz Awan',
    'badge'             => $_ogBadge,
    'bg_color'          => 'ffffff',
    'heading_color'     => '111827',
    'description_color' => '6b7280',
    'accent_color'      => '3d8bff',
    'font'              => 'Poppins',
    'radius'            => '20',
    'padding'           => '54',
    'width'             => '800',
    'height'            => '470',
    'format'            => 'png',
    'username'          => '@shamrouzawan',
]);
$_ogImageUrl = $_ogBase . '/plugins/previewer-toolkit/?' . $_ogParams;

require THEMES_PATH . '/default/templates/layout.php';
render_page($settings->siteName(), $content, [
    'description' => $settings->siteTagline(),
    'og_image'    => $_ogImageUrl,
]);
