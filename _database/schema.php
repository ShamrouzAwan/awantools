<?php
defined('AWAN') or die('Direct access denied.');

function schema_init(object $db): void {
    $isSqlite = $db->driver() === 'sqlite';
    $AI = $isSqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY';
    $TS = $isSqlite ? 'TEXT' : 'DATETIME';

    // ─── Schema version cache ─────────────────────────────────────────────────
    // Bump SCHEMA_VERSION whenever tables, columns, or seeded data change.
    // On each request, this function returns early if the DB is already at this version.
    $schemaVersion = '2.7';
    try {
        $row = $db->fetch("SELECT value FROM settings WHERE `key` = 'schema_version'");
        if ($row && $row['value'] === $schemaVersion) return;
    } catch (Throwable $e) {
        // Settings table doesn't exist yet (first boot) — run full schema
    }

    $schemas = [];

    // Users
    $schemas[] = "CREATE TABLE IF NOT EXISTS users (
        id          {$AI},
        username    VARCHAR(40)  NOT NULL UNIQUE,
        email       VARCHAR(180) NOT NULL UNIQUE,
        name        VARCHAR(120) NOT NULL DEFAULT '',
        password    VARCHAR(255) NOT NULL,
        avatar      VARCHAR(255) DEFAULT NULL,
        bio         TEXT         DEFAULT NULL,
        status      VARCHAR(20)  NOT NULL DEFAULT 'active',
        last_login_at {$TS}     DEFAULT NULL,
        last_login_ip VARCHAR(45) DEFAULT NULL,
        created_at  {$TS}       NOT NULL,
        updated_at  {$TS}       DEFAULT NULL
    )";

    // Roles
    $schemas[] = "CREATE TABLE IF NOT EXISTS roles (
        id          {$AI},
        name        VARCHAR(60)  NOT NULL,
        slug        VARCHAR(60)  NOT NULL UNIQUE,
        description TEXT         DEFAULT NULL,
        created_at  {$TS}       NOT NULL DEFAULT CURRENT_TIMESTAMP
    )";

    // User Roles pivot
    $schemas[] = "CREATE TABLE IF NOT EXISTS user_roles (
        user_id     INTEGER NOT NULL,
        role_id     INTEGER NOT NULL,
        PRIMARY KEY (user_id, role_id)
    )";

    // Settings
    $schemas[] = "CREATE TABLE IF NOT EXISTS settings (
        id          {$AI},
        `key`       VARCHAR(100) NOT NULL UNIQUE,
        `value`     TEXT         DEFAULT NULL,
        `group`     VARCHAR(60)  NOT NULL DEFAULT 'general'
    )";

    // Plugins registry
    $schemas[] = "CREATE TABLE IF NOT EXISTS plugins (
        id          {$AI},
        slug        VARCHAR(80)  NOT NULL UNIQUE,
        name        VARCHAR(120) NOT NULL,
        version     VARCHAR(20)  NOT NULL DEFAULT '1.0',
        description TEXT         DEFAULT NULL,
        author      VARCHAR(120) DEFAULT NULL,
        status      VARCHAR(20)  NOT NULL DEFAULT 'inactive',
        manifest    TEXT         DEFAULT NULL,
        offered     INTEGER      NOT NULL DEFAULT 1,
        installed_at {$TS}      DEFAULT NULL
    )";
    // Add offered column to existing installs (idempotent)
    try { $db->query("ALTER TABLE plugins ADD COLUMN offered INTEGER NOT NULL DEFAULT 1"); } catch (Throwable $e) {}

    // Logs
    $schemas[] = "CREATE TABLE IF NOT EXISTS logs (
        id          {$AI},
        level       VARCHAR(20)  NOT NULL DEFAULT 'info',
        message     TEXT         NOT NULL,
        context     TEXT         DEFAULT NULL,
        user_id     INTEGER      DEFAULT NULL,
        ip          VARCHAR(45)  DEFAULT NULL,
        user_agent  VARCHAR(255) DEFAULT NULL,
        url         VARCHAR(500) DEFAULT NULL,
        created_at  {$TS}       NOT NULL DEFAULT CURRENT_TIMESTAMP
    )";

    // Theme overrides
    $schemas[] = "CREATE TABLE IF NOT EXISTS theme_overrides (
        id             {$AI},
        theme_slug     VARCHAR(80)  NOT NULL,
        variable_key   VARCHAR(120) NOT NULL,
        variable_value VARCHAR(500) NOT NULL,
        UNIQUE (theme_slug, variable_key)
    )";

    // Pages (CMS)
    $schemas[] = "CREATE TABLE IF NOT EXISTS pages (
        id            {$AI},
        title         VARCHAR(255) NOT NULL,
        slug          VARCHAR(255) NOT NULL UNIQUE,
        content       TEXT         DEFAULT NULL,
        seo_title     VARCHAR(255) DEFAULT NULL,
        seo_desc      VARCHAR(500) DEFAULT NULL,
        status        VARCHAR(20)  NOT NULL DEFAULT 'draft',
        author_id     INTEGER      DEFAULT NULL,
        created_at    {$TS}       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at    {$TS}       DEFAULT NULL
    )";

    // Scheduled tasks
    $schemas[] = "CREATE TABLE IF NOT EXISTS scheduled_tasks (
        id               {$AI},
        slug             VARCHAR(80)  NOT NULL UNIQUE,
        name             VARCHAR(120) NOT NULL,
        description      TEXT         DEFAULT NULL,
        interval_seconds INTEGER      NOT NULL DEFAULT 3600,
        status           VARCHAR(20)  NOT NULL DEFAULT 'idle',
        run_count        INTEGER      NOT NULL DEFAULT 0,
        last_run         {$TS}        DEFAULT NULL,
        next_run         {$TS}        DEFAULT NULL,
        last_result      TEXT         DEFAULT NULL,
        created_at       {$TS}        NOT NULL DEFAULT CURRENT_TIMESTAMP
    )";

    // Analytics events
    $schemas[] = "CREATE TABLE IF NOT EXISTS analytics_events (
        id          {$AI},
        event       VARCHAR(80)  NOT NULL,
        path        VARCHAR(500) DEFAULT NULL,
        user_id     INTEGER      DEFAULT NULL,
        plugin_slug VARCHAR(80)  DEFAULT NULL,
        ip          VARCHAR(45)  DEFAULT NULL,
        user_agent  VARCHAR(255) DEFAULT NULL,
        created_at  {$TS}       NOT NULL DEFAULT CURRENT_TIMESTAMP
    )";

    // Media library
    $schemas[] = "CREATE TABLE IF NOT EXISTS media (
        id            {$AI},
        filename      VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        file_path     VARCHAR(500) NOT NULL,
        url_path      VARCHAR(500) NOT NULL,
        mime_type     VARCHAR(100) NOT NULL DEFAULT '',
        file_type     VARCHAR(20)  NOT NULL DEFAULT 'other',
        file_size     INTEGER      NOT NULL DEFAULT 0,
        width         INTEGER      DEFAULT NULL,
        height        INTEGER      DEFAULT NULL,
        folder        VARCHAR(100) NOT NULL DEFAULT 'general',
        alt_text      VARCHAR(255) DEFAULT NULL,
        uploader_id   INTEGER      DEFAULT NULL,
        created_at    {$TS}       NOT NULL DEFAULT CURRENT_TIMESTAMP
    )";

    // ─── Blog System ──────────────────────────────────────────────────────────

    $schemas[] = "CREATE TABLE IF NOT EXISTS blog_posts (
        id             {$AI},
        title          VARCHAR(255) NOT NULL,
        slug           VARCHAR(255) NOT NULL UNIQUE,
        excerpt        TEXT         DEFAULT NULL,
        content        TEXT         DEFAULT NULL,
        cover_image    VARCHAR(500) DEFAULT NULL,
        meta_desc      VARCHAR(500) DEFAULT NULL,
        meta_keywords  VARCHAR(500) DEFAULT NULL,
        og_image       VARCHAR(500) DEFAULT NULL,
        author_id      INTEGER      DEFAULT NULL,
        status         VARCHAR(20)  NOT NULL DEFAULT 'draft',
        featured       INTEGER      NOT NULL DEFAULT 0,
        view_count     INTEGER      NOT NULL DEFAULT 0,
        scheduled_at   {$TS}       DEFAULT NULL,
        published_at   {$TS}       DEFAULT NULL,
        created_at     {$TS}       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at     {$TS}       DEFAULT NULL
    )";

    $schemas[] = "CREATE TABLE IF NOT EXISTS blog_categories (
        id          {$AI},
        name        VARCHAR(120) NOT NULL,
        slug        VARCHAR(120) NOT NULL UNIQUE,
        description TEXT         DEFAULT NULL,
        color       VARCHAR(20)  DEFAULT NULL,
        created_at  {$TS}       NOT NULL DEFAULT CURRENT_TIMESTAMP
    )";

    $schemas[] = "CREATE TABLE IF NOT EXISTS blog_post_categories (
        post_id     INTEGER NOT NULL,
        category_id INTEGER NOT NULL,
        PRIMARY KEY (post_id, category_id)
    )";

    $schemas[] = "CREATE TABLE IF NOT EXISTS blog_tags (
        id         {$AI},
        name       VARCHAR(80) NOT NULL,
        slug       VARCHAR(80) NOT NULL UNIQUE,
        created_at {$TS}      NOT NULL DEFAULT CURRENT_TIMESTAMP
    )";

    $schemas[] = "CREATE TABLE IF NOT EXISTS blog_post_tags (
        post_id INTEGER NOT NULL,
        tag_id  INTEGER NOT NULL,
        PRIMARY KEY (post_id, tag_id)
    )";

    $schemas[] = "CREATE TABLE IF NOT EXISTS blog_comments (
        id           {$AI},
        post_id      INTEGER     NOT NULL,
        parent_id    INTEGER     DEFAULT NULL,
        author_name  VARCHAR(100) NOT NULL,
        author_email VARCHAR(180) NOT NULL,
        content      TEXT         NOT NULL,
        status       VARCHAR(20)  NOT NULL DEFAULT 'pending',
        ip           VARCHAR(45)  DEFAULT NULL,
        created_at   {$TS}       NOT NULL DEFAULT CURRENT_TIMESTAMP
    )";

    // ─── Client Acquisition ───────────────────────────────────────────────────

    $schemas[] = "CREATE TABLE IF NOT EXISTS quote_requests (
        id                  {$AI},
        name                VARCHAR(120) NOT NULL,
        email               VARCHAR(180) NOT NULL,
        company             VARCHAR(120) DEFAULT NULL,
        budget              VARCHAR(80)  DEFAULT NULL,
        timeline            VARCHAR(80)  DEFAULT NULL,
        project_description TEXT         NOT NULL,
        status              VARCHAR(20)  NOT NULL DEFAULT 'new',
        admin_notes         TEXT         DEFAULT NULL,
        created_at          {$TS}       NOT NULL DEFAULT CURRENT_TIMESTAMP
    )";

    $schemas[] = "CREATE TABLE IF NOT EXISTS tool_requests (
        id           {$AI},
        name         VARCHAR(120) NOT NULL,
        email        VARCHAR(180) NOT NULL,
        request_type VARCHAR(40)  NOT NULL DEFAULT 'plugin',
        title        VARCHAR(255) NOT NULL,
        description  TEXT         NOT NULL,
        status       VARCHAR(20)  NOT NULL DEFAULT 'new',
        admin_notes  TEXT         DEFAULT NULL,
        created_at   {$TS}       NOT NULL DEFAULT CURRENT_TIMESTAMP
    )";

    $schemas[] = "CREATE TABLE IF NOT EXISTS issue_reports (
        id              {$AI},
        plugin_slug     VARCHAR(80)  DEFAULT NULL,
        reporter_name   VARCHAR(120) NOT NULL,
        reporter_email  VARCHAR(180) NOT NULL,
        description     TEXT         NOT NULL,
        screenshot_path VARCHAR(500) DEFAULT NULL,
        browser         VARCHAR(255) DEFAULT NULL,
        url             VARCHAR(500) DEFAULT NULL,
        status          VARCHAR(20)  NOT NULL DEFAULT 'open',
        admin_reply     TEXT         DEFAULT NULL,
        created_at      {$TS}       NOT NULL DEFAULT CURRENT_TIMESTAMP
    )";

    $schemas[] = "CREATE TABLE IF NOT EXISTS newsletter_subscribers (
        id         {$AI},
        email      VARCHAR(180) NOT NULL UNIQUE,
        name       VARCHAR(120) DEFAULT NULL,
        status     VARCHAR(20)  NOT NULL DEFAULT 'active',
        created_at {$TS}       NOT NULL DEFAULT CURRENT_TIMESTAMP
    )";

    $schemas[] = "CREATE TABLE IF NOT EXISTS contact_messages (
        id         {$AI},
        name       VARCHAR(120) NOT NULL,
        email      VARCHAR(180) NOT NULL,
        subject    VARCHAR(255) NOT NULL,
        message    TEXT         NOT NULL,
        status     VARCHAR(20)  NOT NULL DEFAULT 'new',
        admin_note TEXT         DEFAULT NULL,
        ip         VARCHAR(45)  DEFAULT NULL,
        created_at {$TS}       NOT NULL DEFAULT CURRENT_TIMESTAMP
    )";

    $schemas[] = "CREATE TABLE IF NOT EXISTS notifications (
        id         {$AI},
        type       VARCHAR(50)  NOT NULL DEFAULT 'info',
        title      VARCHAR(255) NOT NULL,
        message    TEXT         DEFAULT NULL,
        url        VARCHAR(500) DEFAULT NULL,
        is_read    INTEGER      NOT NULL DEFAULT 0,
        expires_at {$TS}       DEFAULT NULL,
        created_at {$TS}       NOT NULL DEFAULT CURRENT_TIMESTAMP
    )";

    // Execute all schemas — all use IF NOT EXISTS so safe to run on every boot
    foreach ($schemas as $sql) {
        $db->query($sql);
    }

    // Seed default roles (idempotent)
    if (!$db->exists('roles', "slug = 'super_admin'")) {
        $db->insert('roles', ['name' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Full platform access']);
        $db->insert('roles', ['name' => 'Admin',       'slug' => 'admin',       'description' => 'Administrative access']);
        $db->insert('roles', ['name' => 'User',        'slug' => 'user',        'description' => 'Standard user access']);
    }

    // Seed default super admin account (idempotent — only if no user with username 'admin' exists)
    if (!$db->exists('users', "username = 'admin'")) {
        $defaultHash = password_hash('Admin@1234', PASSWORD_BCRYPT);
        $adminId = $db->insert('users', [
            'name'       => 'Super Admin',
            'username'   => 'admin',
            'email'      => 'admin@localhost',
            'password'   => $defaultHash,
            'status'     => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $superAdminRole = $db->fetch("SELECT id FROM roles WHERE slug = 'super_admin' LIMIT 1");
        $userRole       = $db->fetch("SELECT id FROM roles WHERE slug = 'user' LIMIT 1");
        if ($adminId && $superAdminRole) {
            $db->insert('user_roles', ['user_id' => $adminId, 'role_id' => $superAdminRole['id']]);
        }
        if ($adminId && $userRole) {
            $db->insert('user_roles', ['user_id' => $adminId, 'role_id' => $userRole['id']]);
        }
    }

    // Seed default settings (idempotent)
    $defaults = [
        ['key' => 'site_name',             'value' => 'AWAN Platform',                    'group' => 'general'],
        ['key' => 'site_tagline',          'value' => 'One Platform. Unlimited Plugins.', 'group' => 'general'],
        ['key' => 'site_url',              'value' => '',                                  'group' => 'general'],
        ['key' => 'active_theme',          'value' => 'default',                           'group' => 'theme'],
        ['key' => 'registration_enabled',  'value' => '1',                                 'group' => 'auth'],
        ['key' => 'maintenance_mode',      'value' => '0',                                 'group' => 'general'],
        ['key' => 'timezone',              'value' => 'UTC',                               'group' => 'general'],
        ['key' => 'date_format',           'value' => 'M j, Y',                           'group' => 'general'],
        ['key' => 'analytics_enabled',     'value' => '1',                                 'group' => 'analytics'],
        ['key' => 'items_per_page',        'value' => '25',                                'group' => 'general'],
        ['key' => 'media_max_size_mb',     'value' => '10',                                'group' => 'media'],
        ['key' => 'language',              'value' => 'en',                                'group' => 'general'],
        ['key' => 'api_key',               'value' => bin2hex(random_bytes(20)),           'group' => 'api'],
        ['key' => 'cron_secret',           'value' => bin2hex(random_bytes(16)),           'group' => 'scheduler'],
        // ── Email (PHP mail only) ──
        ['key' => 'mail_from_email',       'value' => '',                                  'group' => 'email'],
        ['key' => 'mail_from_name',        'value' => '',                                  'group' => 'email'],
        ['key' => 'mail_reply_to',         'value' => '',                                  'group' => 'email'],
        ['key' => 'email_queue_enabled',   'value' => '0',                                 'group' => 'email'],
        ['key' => 'email_queue_batch_size','value' => '20',                                'group' => 'email'],
        // ── SEO General ──
        ['key' => 'seo_title_format',      'value' => ':title — :site_name',               'group' => 'seo'],
        ['key' => 'seo_meta_description',  'value' => '',                                  'group' => 'seo'],
        ['key' => 'seo_meta_keywords',     'value' => '',                                  'group' => 'seo'],
        ['key' => 'seo_robots_index',      'value' => '1',                                 'group' => 'seo'],
        ['key' => 'seo_robots_follow',     'value' => '1',                                 'group' => 'seo'],
        ['key' => 'seo_canonical_url',     'value' => '',                                  'group' => 'seo'],
        // ── OpenGraph ──
        ['key' => 'og_enabled',            'value' => '1',                                 'group' => 'seo'],
        ['key' => 'og_site_name',          'value' => '',                                  'group' => 'seo'],
        ['key' => 'og_default_image',      'value' => '',                                  'group' => 'seo'],
        ['key' => 'og_type',               'value' => 'website',                           'group' => 'seo'],
        ['key' => 'og_locale',             'value' => 'en_US',                             'group' => 'seo'],
        // ── OG Image Generator ──
        ['key' => 'og_image_bg_color',      'value' => '#0f172a',                          'group' => 'seo'],
        ['key' => 'og_image_card_color',    'value' => '#1a2235',                          'group' => 'seo'],
        ['key' => 'og_image_primary_color', 'value' => '#6366f1',                          'group' => 'seo'],
        ['key' => 'og_image_eyebrow',       'value' => '',                                 'group' => 'seo'],
        ['key' => 'og_image_watermark',     'value' => '',                                 'group' => 'seo'],
        // ── Twitter/X Card ──
        ['key' => 'twitter_card',          'value' => 'summary_large_image',               'group' => 'seo'],
        ['key' => 'twitter_site',          'value' => '',                                  'group' => 'seo'],
        ['key' => 'twitter_creator',       'value' => '',                                  'group' => 'seo'],
        // ── Analytics ──
        ['key' => 'google_analytics_id',   'value' => '',                                  'group' => 'seo'],
        ['key' => 'google_tag_manager_id', 'value' => '',                                  'group' => 'seo'],
        ['key' => 'facebook_pixel_id',     'value' => '',                                  'group' => 'seo'],
        ['key' => 'microsoft_clarity_id',  'value' => '',                                  'group' => 'seo'],
        // ── Webmaster Verification ──
        ['key' => 'google_site_verification', 'value' => '',                               'group' => 'seo'],
        ['key' => 'google_sc_html_content',   'value' => '',                               'group' => 'seo'],
        ['key' => 'bing_site_verification',   'value' => '',                               'group' => 'seo'],
        ['key' => 'yandex_verification',      'value' => '',                               'group' => 'seo'],
        // ── reCAPTCHA ──
        ['key' => 'recaptcha_enabled',     'value' => '0',                                 'group' => 'seo'],
        ['key' => 'recaptcha_version',     'value' => '2',                                 'group' => 'seo'],
        ['key' => 'recaptcha_site_key',    'value' => '',                                  'group' => 'seo'],
        ['key' => 'recaptcha_secret_key',  'value' => '',                                  'group' => 'seo'],
        ['key' => 'recaptcha_v3_score',    'value' => '0.5',                               'group' => 'seo'],
        ['key' => 'recaptcha_on_login',    'value' => '1',                                 'group' => 'seo'],
        ['key' => 'recaptcha_on_register', 'value' => '1',                                 'group' => 'seo'],
        // ── Sitemap & Robots ──
        ['key' => 'sitemap_enabled',       'value' => '1',                                 'group' => 'seo'],
        ['key' => 'sitemap_include_users', 'value' => '0',                                 'group' => 'seo'],
        ['key' => 'sitemap_change_freq',   'value' => 'weekly',                            'group' => 'seo'],
        ['key' => 'sitemap_priority_home', 'value' => '1.0',                               'group' => 'seo'],
        ['key' => 'sitemap_priority_pages','value' => '0.8',                               'group' => 'seo'],
        ['key' => 'robots_disallow_admin', 'value' => '1',                                 'group' => 'seo'],
        ['key' => 'robots_disallow_api',   'value' => '1',                                 'group' => 'seo'],
        ['key' => 'robots_custom_rules',   'value' => '',                                  'group' => 'seo'],
        // ── Developer / Branding ──
        ['key' => 'developer_name',        'value' => '',                                  'group' => 'branding'],
        ['key' => 'developer_title',       'value' => 'Full-Stack Developer',              'group' => 'branding'],
        ['key' => 'developer_bio',         'value' => '',                                  'group' => 'branding'],
        ['key' => 'developer_email',       'value' => '',                                  'group' => 'branding'],
        ['key' => 'developer_portfolio',   'value' => '',                                  'group' => 'branding'],
        ['key' => 'developer_github',      'value' => '',                                  'group' => 'branding'],
        ['key' => 'developer_linkedin',    'value' => '',                                  'group' => 'branding'],
        ['key' => 'developer_twitter',     'value' => '',                                  'group' => 'branding'],
        ['key' => 'developer_facebook',    'value' => '',                                  'group' => 'branding'],
        ['key' => 'developer_instagram',   'value' => '',                                  'group' => 'branding'],
        ['key' => 'developer_youtube',     'value' => '',                                  'group' => 'branding'],
        ['key' => 'developer_whatsapp',    'value' => '',                                  'group' => 'branding'],
        // ── Branding ──
        ['key' => 'logo_url',              'value' => '',                                  'group' => 'branding'],
        ['key' => 'favicon_url',           'value' => '',                                  'group' => 'branding'],
        // ── Hero Section ──
        ['key' => 'hero_badge',            'value' => 'Free Online Tools Platform',        'group' => 'homepage'],
        ['key' => 'hero_title',            'value' => 'One Account.',                      'group' => 'homepage'],
        ['key' => 'hero_title_accent',     'value' => 'All Your Tools.',                   'group' => 'homepage'],
        ['key' => 'hero_subtitle',         'value' => 'A curated collection of free tools and applications — powered by a unified account system. Register once, use everything.', 'group' => 'homepage'],
        ['key' => 'hero_cta_text',         'value' => 'Get Started Free',                  'group' => 'homepage'],
        ['key' => 'hero_cta_url',          'value' => '/register',                         'group' => 'homepage'],
        ['key' => 'hero_secondary_cta_text','value' => 'Browse Tools',                     'group' => 'homepage'],
        ['key' => 'hero_secondary_cta_url', 'value' => '/plugins',                         'group' => 'homepage'],
        // ── Footer ──
        ['key' => 'footer_tagline',        'value' => 'A curated collection of free online tools and applications.', 'group' => 'footer'],
        ['key' => 'footer_copyright',      'value' => '',                                  'group' => 'footer'],
        // ── Scheduler ──
        ['key' => 'log_retention_days',    'value' => '90',                                'group' => 'scheduler'],
        ['key' => 'backup_retention_days', 'value' => '30',                                'group' => 'scheduler'],
    ];

    foreach ($defaults as $row) {
        if (!$db->exists('settings', '`key` = ?', [$row['key']])) {
            $db->insert('settings', $row);
        }
    }

    // ─── Idempotent column migrations ────────────────────────────────────────
    $migrations = [
        // Pages: nav/footer/type/order columns
        "ALTER TABLE pages ADD COLUMN show_in_footer  INTEGER      NOT NULL DEFAULT 0",
        "ALTER TABLE pages ADD COLUMN show_in_nav     INTEGER      NOT NULL DEFAULT 0",
        "ALTER TABLE pages ADD COLUMN page_type       VARCHAR(20)  NOT NULL DEFAULT 'page'",
        "ALTER TABLE pages ADD COLUMN sort_order      INTEGER      NOT NULL DEFAULT 0",
        // Pages: OG / canonical
        "ALTER TABLE pages ADD COLUMN og_title        VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE pages ADD COLUMN og_description  VARCHAR(500) DEFAULT NULL",
        "ALTER TABLE pages ADD COLUMN og_image        VARCHAR(500) DEFAULT NULL",
        "ALTER TABLE pages ADD COLUMN canonical_url   VARCHAR(500) DEFAULT NULL",
        // Blog posts: OG title/description
        "ALTER TABLE blog_posts ADD COLUMN og_title        VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE blog_posts ADD COLUMN og_description  VARCHAR(500) DEFAULT NULL",
        // Newsletter: unsubscribe token
        "ALTER TABLE newsletter_subscribers ADD COLUMN unsubscribe_token VARCHAR(64) DEFAULT NULL",
        // Users: email verification + google oauth
        "ALTER TABLE users ADD COLUMN email_verified_at  {$TS} DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN google_id          VARCHAR(100) DEFAULT NULL",
        // Notifications: expiry
        "ALTER TABLE notifications ADD COLUMN expires_at {$TS} DEFAULT NULL",
        // Users: Email OTP 2FA + password flag
        "ALTER TABLE users ADD COLUMN two_fa_enabled INTEGER NOT NULL DEFAULT 0",
        "ALTER TABLE users ADD COLUMN has_password   INTEGER NOT NULL DEFAULT 1",
        // Blog posts: writer approval status
        "ALTER TABLE blog_posts ADD COLUMN review_status VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE blog_posts ADD COLUMN reviewed_by  INTEGER DEFAULT NULL",
        // Settings: content width
        "ALTER TABLE settings ADD COLUMN group2 VARCHAR(60) DEFAULT NULL",
        // Email logs: open & click tracking
        "ALTER TABLE email_logs ADD COLUMN tracking_token VARCHAR(64) DEFAULT NULL",
        "ALTER TABLE email_logs ADD COLUMN opened_at TEXT DEFAULT NULL",
        "ALTER TABLE email_logs ADD COLUMN open_count INTEGER NOT NULL DEFAULT 0",
        "ALTER TABLE email_logs ADD COLUMN clicked_at TEXT DEFAULT NULL",
        "ALTER TABLE email_logs ADD COLUMN click_count INTEGER NOT NULL DEFAULT 0",
        // Notifications: per-user ownership
        "ALTER TABLE notifications ADD COLUMN user_id INTEGER DEFAULT NULL",
        // User preferences: additional preference columns
        "ALTER TABLE user_preferences ADD COLUMN email_notifications INTEGER NOT NULL DEFAULT 1",
        "ALTER TABLE user_preferences ADD COLUMN theme VARCHAR(20) DEFAULT NULL",
        "ALTER TABLE user_preferences ADD COLUMN items_per_page INTEGER DEFAULT NULL",
    ];
    foreach ($migrations as $sql) {
        try { $db->query($sql); } catch (Throwable $e) {}
    }

    // ─── Plugin ratings table ──────────────────────────────────────────────────
    $db->query("CREATE TABLE IF NOT EXISTS plugin_ratings (
        id          {$AI},
        plugin_id   INTEGER      NOT NULL,
        user_id     INTEGER      NOT NULL,
        rating      INTEGER      NOT NULL DEFAULT 5,
        created_at  {$TS}        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at  {$TS}        DEFAULT NULL,
        UNIQUE(plugin_id, user_id)
    )");

    // ─── Email OTP codes table ─────────────────────────────────────────────────
    $db->query("CREATE TABLE IF NOT EXISTS user_otp_codes (
        id          {$AI},
        user_id     INTEGER      NOT NULL,
        code        VARCHAR(20)  NOT NULL,
        expires_at  {$TS}        NOT NULL,
        used        INTEGER      NOT NULL DEFAULT 0,
        created_at  {$TS}        NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");

    // ─── User favourites table ─────────────────────────────────────────────────
    $db->query("CREATE TABLE IF NOT EXISTS user_favourites (
        id          {$AI},
        user_id     INTEGER      NOT NULL,
        plugin_id   INTEGER      NOT NULL,
        created_at  {$TS}        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, plugin_id)
    )");

    // ─── Languages table ──────────────────────────────────────────────────────
    $db->query("CREATE TABLE IF NOT EXISTS languages (
        id          {$AI},
        name        VARCHAR(80)  NOT NULL,
        code        VARCHAR(10)  NOT NULL UNIQUE,
        locale_file VARCHAR(255) DEFAULT NULL,
        is_active   INTEGER      NOT NULL DEFAULT 1,
        is_default  INTEGER      NOT NULL DEFAULT 0,
        sort_order  INTEGER      NOT NULL DEFAULT 0,
        created_at  {$TS}        NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");

    // ─── User language preference ─────────────────────────────────────────────
    $db->query("CREATE TABLE IF NOT EXISTS user_preferences (
        id          {$AI},
        user_id     INTEGER      NOT NULL UNIQUE,
        language    VARCHAR(10)  DEFAULT NULL,
        updated_at  {$TS}        DEFAULT NULL
    )");

    // ─── Testimonials ─────────────────────────────────────────────────────────
    $db->query("CREATE TABLE IF NOT EXISTS testimonials (
        id          {$AI},
        name        VARCHAR(120) NOT NULL,
        company     VARCHAR(120) DEFAULT NULL,
        title       VARCHAR(120) DEFAULT NULL,
        photo       VARCHAR(500) DEFAULT NULL,
        testimonial TEXT         NOT NULL,
        rating      INTEGER      NOT NULL DEFAULT 5,
        is_active   INTEGER      NOT NULL DEFAULT 1,
        sort_order  INTEGER      NOT NULL DEFAULT 0,
        created_at  {$TS}       NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");

    // ─── Email logs table ─────────────────────────────────────────────────────
    $db->query("CREATE TABLE IF NOT EXISTS email_logs (
        id            {$AI},
        recipient     VARCHAR(180) NOT NULL,
        subject       VARCHAR(255) NOT NULL,
        status        VARCHAR(20)  NOT NULL DEFAULT 'sent',
        transport     VARCHAR(20)  NOT NULL DEFAULT 'php',
        error_message TEXT         DEFAULT NULL,
        created_at    {$TS}       NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");

    // Migrate legacy 'error' column to 'error_message' if needed
    $extraMigrations = [
        "ALTER TABLE email_logs ADD COLUMN transport      VARCHAR(20) NOT NULL DEFAULT 'php'",
        "ALTER TABLE email_logs ADD COLUMN error_message  TEXT DEFAULT NULL",
    ];
    foreach ($extraMigrations as $sql) {
        try { $db->query($sql); } catch (Throwable $e) {}
    }

    // ─── Email Queue (async delivery) ─────────────────────────────────────────
    $db->query("CREATE TABLE IF NOT EXISTS email_queue (
        id            {$AI},
        to_email      VARCHAR(180) NOT NULL,
        to_name       VARCHAR(120) DEFAULT NULL,
        subject       VARCHAR(255) NOT NULL,
        body_html     TEXT         NOT NULL,
        body_text     TEXT         DEFAULT NULL,
        from_email    VARCHAR(180) DEFAULT NULL,
        from_name     VARCHAR(120) DEFAULT NULL,
        reply_to      VARCHAR(180) DEFAULT NULL,
        attempts      INTEGER      NOT NULL DEFAULT 0,
        max_attempts  INTEGER      NOT NULL DEFAULT 3,
        status        VARCHAR(20)  NOT NULL DEFAULT 'pending',
        scheduled_at  {$TS}       DEFAULT NULL,
        sent_at       {$TS}       DEFAULT NULL,
        failed_at     {$TS}       DEFAULT NULL,
        error         TEXT         DEFAULT NULL,
        created_at    {$TS}       NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");

    // ─── API Rate Limits (DB-backed, stateless-safe) ──────────────────────────
    $db->query("CREATE TABLE IF NOT EXISTS api_rate_limits (
        id           {$AI},
        rate_key     VARCHAR(120) NOT NULL UNIQUE,
        attempts     INTEGER      NOT NULL DEFAULT 0,
        window_start INTEGER      NOT NULL DEFAULT 0
    )");

    // ─── TOTP Secrets (2FA) ────────────────────────────────────────────────────
    $db->query("CREATE TABLE IF NOT EXISTS user_totp (
        id           {$AI},
        user_id      INTEGER      NOT NULL UNIQUE,
        secret       VARCHAR(64)  NOT NULL,
        enabled      INTEGER      NOT NULL DEFAULT 0,
        backup_codes TEXT         DEFAULT NULL,
        created_at   {$TS}       NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");

    // ─── Nav items table (header + footer management) ────────────────────────
    $db->query("CREATE TABLE IF NOT EXISTS nav_items (
        id         {$AI},
        label      VARCHAR(100) NOT NULL,
        url        VARCHAR(500) NOT NULL,
        target     VARCHAR(10)  NOT NULL DEFAULT '_self',
        location   VARCHAR(20)  NOT NULL DEFAULT 'header',
        icon       VARCHAR(100) DEFAULT NULL,
        sort_order INTEGER      NOT NULL DEFAULT 0,
        is_active  INTEGER      NOT NULL DEFAULT 1,
        created_at {$TS}       NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");

    // ─── Nav items: add columns if missing ───────────────────────────────────
    try { $db->query("ALTER TABLE nav_items ADD COLUMN location VARCHAR(20) NOT NULL DEFAULT 'header'"); } catch (Throwable $e) {}
    try { $db->query("ALTER TABLE nav_items ADD COLUMN icon VARCHAR(100) DEFAULT NULL"); } catch (Throwable $e) {}

    // ─── Password Reset Tokens ────────────────────────────────────────────────
    $db->query("CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id         {$AI},
        user_id    INTEGER      NOT NULL,
        token      VARCHAR(100) NOT NULL UNIQUE,
        expires_at {$TS}       NOT NULL,
        used_at    {$TS}       DEFAULT NULL,
        created_at {$TS}       NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");

    // ─── Email Verification Tokens ────────────────────────────────────────────
    $db->query("CREATE TABLE IF NOT EXISTS email_verification_tokens (
        id         {$AI},
        user_id    INTEGER      NOT NULL,
        token      VARCHAR(100) NOT NULL UNIQUE,
        expires_at {$TS}       NOT NULL,
        used_at    {$TS}       DEFAULT NULL,
        created_at {$TS}       NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");

    // ─── Email Templates ──────────────────────────────────────────────────────
    $db->query("CREATE TABLE IF NOT EXISTS email_templates (
        id         {$AI},
        slug       VARCHAR(80)  NOT NULL UNIQUE,
        name       VARCHAR(120) NOT NULL,
        subject    VARCHAR(255) NOT NULL,
        body       TEXT         NOT NULL,
        variables  TEXT         DEFAULT NULL,
        is_system  INTEGER      NOT NULL DEFAULT 0,
        created_at {$TS}       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at {$TS}       DEFAULT NULL
    )");

    // ─── Homepage Sections ────────────────────────────────────────────────────
    $db->query("CREATE TABLE IF NOT EXISTS homepage_sections (
        id          {$AI},
        section_key VARCHAR(60)  NOT NULL UNIQUE,
        name        VARCHAR(120) NOT NULL,
        is_enabled  INTEGER      NOT NULL DEFAULT 1,
        sort_order  INTEGER      NOT NULL DEFAULT 0,
        config      TEXT         DEFAULT NULL,
        created_at  {$TS}       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at  {$TS}       DEFAULT NULL
    )");

    // ─── Seed homepage sections (idempotent) ─────────────────────────────────
    $defaultSections = [
        ['section_key' => 'hero',          'name' => 'Hero',           'is_enabled' => 1, 'sort_order' => 10],
        ['section_key' => 'stats',         'name' => 'Statistics',     'is_enabled' => 1, 'sort_order' => 20],
        ['section_key' => 'search',        'name' => 'Search',         'is_enabled' => 1, 'sort_order' => 30],
        ['section_key' => 'featured_tools','name' => 'Featured Tools', 'is_enabled' => 1, 'sort_order' => 40],
        ['section_key' => 'why_us',        'name' => 'Why Choose Us',  'is_enabled' => 1, 'sort_order' => 50],
        ['section_key' => 'blog',          'name' => 'Blog Posts',     'is_enabled' => 1, 'sort_order' => 60],
        ['section_key' => 'testimonials',  'name' => 'Testimonials',   'is_enabled' => 1, 'sort_order' => 70],
        ['section_key' => 'cta',           'name' => 'Call to Action', 'is_enabled' => 0, 'sort_order' => 80],
        ['section_key' => 'faq',           'name' => 'FAQ',            'is_enabled' => 0, 'sort_order' => 90],
        ['section_key' => 'contact',       'name' => 'Contact',        'is_enabled' => 0, 'sort_order' => 100],
        ['section_key' => 'custom_block',  'name' => 'Custom Content', 'is_enabled' => 0, 'sort_order' => 110],
    ];
    foreach ($defaultSections as $sec) {
        if (!$db->exists('homepage_sections', 'section_key = ?', [$sec['section_key']])) {
            $db->insert('homepage_sections', array_merge($sec, ['created_at' => date('Y-m-d H:i:s')]));
        }
    }

    // ─── Settings defaults for new/extended features ───────────────────────────
    $newSettingsDefaults = [
        ['key' => 'hero_image_url',              'value' => '',     'group' => 'homepage'],
        ['key' => 'blog_section_enabled',        'value' => '1',    'group' => 'homepage'],
        ['key' => 'blog_section_title',          'value' => 'Latest Articles', 'group' => 'homepage'],
        ['key' => 'blog_section_count',          'value' => '3',    'group' => 'homepage'],
        ['key' => 'testimonials_section_enabled','value' => '1',    'group' => 'homepage'],
        ['key' => 'testimonials_section_title',  'value' => 'What People Say', 'group' => 'homepage'],
        ['key' => 'cta_section_title',           'value' => 'Ready to get started?', 'group' => 'homepage'],
        ['key' => 'cta_section_subtitle',        'value' => 'Join thousands of users already using our free tools.', 'group' => 'homepage'],
        ['key' => 'cta_section_btn_text',        'value' => 'Get Started Free', 'group' => 'homepage'],
        ['key' => 'cta_section_btn_url',         'value' => '/register', 'group' => 'homepage'],
        ['key' => 'email_verification_enabled',  'value' => '0',    'group' => 'auth'],
        ['key' => 'google_oauth_enabled',        'value' => '0',    'group' => 'auth'],
        ['key' => 'google_client_id',            'value' => '',     'group' => 'auth'],
        ['key' => 'google_client_secret',        'value' => '',     'group' => 'auth'],
        // Legacy smtp_ keys kept in DB for backward compat — no longer shown in UI
        ['key' => 'smtp_from_email',             'value' => '',     'group' => 'email'],
        ['key' => 'smtp_from_name',              'value' => '',     'group' => 'email'],
        ['key' => 'smtp_reply_to',               'value' => '',     'group' => 'email'],
        ['key' => 'setup_wizard_dismissed',      'value' => '0',    'group' => 'general'],
        ['key' => 'api_allowed_origins',         'value' => '',     'group' => 'api'],
        ['key' => 'cookie_consent_enabled',      'value' => '1',    'group' => 'general'],
        ['key' => 'analytics_prune_days',        'value' => '365',  'group' => 'analytics'],
    ];
    foreach ($newSettingsDefaults as $row) {
        if (!$db->exists('settings', '`key` = ?', [$row['key']])) {
            $db->insert('settings', $row);
        }
    }

    // ─── Migrate legacy smtp_from_email → mail_from_email if needed ──────────
    // If mail_from_email is blank but smtp_from_email has a value, copy it over
    try {
        $mailFrom = $db->fetch("SELECT value FROM settings WHERE `key` = 'mail_from_email'");
        $smtpFrom = $db->fetch("SELECT value FROM settings WHERE `key` = 'smtp_from_email'");
        if ($mailFrom && empty($mailFrom['value']) && $smtpFrom && !empty($smtpFrom['value'])) {
            $db->query("UPDATE settings SET `value` = ? WHERE `key` = 'mail_from_email'", [$smtpFrom['value']]);
        }
        $mailName = $db->fetch("SELECT value FROM settings WHERE `key` = 'mail_from_name'");
        $smtpName = $db->fetch("SELECT value FROM settings WHERE `key` = 'smtp_from_name'");
        if ($mailName && empty($mailName['value']) && $smtpName && !empty($smtpName['value'])) {
            $db->query("UPDATE settings SET `value` = ? WHERE `key` = 'mail_from_name'", [$smtpName['value']]);
        }
        $mailReply = $db->fetch("SELECT value FROM settings WHERE `key` = 'mail_reply_to'");
        $smtpReply = $db->fetch("SELECT value FROM settings WHERE `key` = 'smtp_reply_to'");
        if ($mailReply && empty($mailReply['value']) && $smtpReply && !empty($smtpReply['value'])) {
            $db->query("UPDATE settings SET `value` = ? WHERE `key` = 'mail_reply_to'", [$smtpReply['value']]);
        }
    } catch (Throwable $e) {}

    // ─── Default email templates (idempotent seed) ────────────────────────────
    $defaultEmailTemplates = [
        ['slug' => 'verify-email',       'name' => 'Email Verification',       'is_system' => 1,
         'subject' => 'Verify your email address - {{site_name}}',
         'variables' => 'name, site_name, verify_url',
         'body' => "<p>Hi {{name}},</p>\n<p>Thanks for registering! Please verify your email address by clicking the button below.</p>\n<p>This link expires in 24 hours.</p>"],
        ['slug' => 'welcome',            'name' => 'Welcome',                   'is_system' => 1,
         'subject' => 'Welcome to {{site_name}}!',
         'variables' => 'name, site_name, login_url',
         'body' => "<p>Hi {{name}},</p>\n<p>Welcome to {{site_name}}! Your account has been created successfully.</p>"],
        ['slug' => 'password-reset',     'name' => 'Password Reset',            'is_system' => 1,
         'subject' => 'Reset your password - {{site_name}}',
         'variables' => 'name, site_name, reset_url',
         'body' => "<p>Hi {{name}},</p>\n<p>We received a request to reset your password. Click the button below to choose a new password. This link expires in 1 hour.</p>\n<p>If you did not request this, you can safely ignore this email.</p>"],
        ['slug' => 'contact-reply',      'name' => 'Contact Form Auto-Reply',   'is_system' => 0,
         'subject' => 'Thanks for contacting us - {{site_name}}',
         'variables' => 'name, site_name',
         'body' => "<p>Hi {{name}},</p>\n<p>Thank you for reaching out! We will reply within 1-2 business days.</p>"],
        ['slug' => 'newsletter-welcome', 'name' => 'Newsletter Welcome',         'is_system' => 0,
         'subject' => 'Welcome to the {{site_name}} newsletter!',
         'variables' => 'name, site_name',
         'body' => "<p>Hi {{name}},</p>\n<p>You have successfully subscribed to our newsletter.</p>"],
        ['slug' => 'quote-reply',        'name' => 'Quote Request Auto-Reply',  'is_system' => 0,
         'subject' => 'We received your quote request - {{site_name}}',
         'variables' => 'name, site_name',
         'body' => "<p>Hi {{name}},</p>\n<p>Thank you for submitting a quote request. We will respond within 2-3 business days.</p>"],
        ['slug' => 'tool-request-reply', 'name' => 'Tool Request Auto-Reply',   'is_system' => 0,
         'subject' => 'Tool request received - {{site_name}}',
         'variables' => 'name, site_name, tool_title',
         'body' => "<p>Hi {{name}},</p>\n<p>Thanks for your tool suggestion! We have logged your request and will review it.</p>"],
        ['slug' => 'email-otp',          'name' => 'Email OTP Verification',   'is_system' => 1,
         'subject' => 'Your {{site_name}} login code',
         'variables' => 'name, site_name, code',
         'body' => "<p>Hi {{name}},</p>\n<p>Your login verification code is:</p>\n<div style=\"text-align:center;margin:24px 0\"><span style=\"font-size:32px;font-weight:700;letter-spacing:6px;background:#f3f4f6;padding:12px 24px;border-radius:8px;font-family:monospace\">{{code}}</span></div>\n<p>This code expires in 10 minutes. Do not share it with anyone.</p>\n<p>If you did not request this code, you can safely ignore this email.</p>"],
    ];

    foreach ($defaultEmailTemplates as $tpl) {
        if (!$db->exists('email_templates', 'slug = ?', [$tpl['slug']])) {
            $db->insert('email_templates', array_merge($tpl, [
                'created_at' => date('Y-m-d H:i:s'),
            ]));
        }
    }

    // ─── Seed system/legal pages (idempotent) ─────────────────────────────────
    $systemPages = [
        ['title'=>'About',          'slug'=>'about',          'page_type'=>'page',   'show_in_footer'=>1,'sort_order'=>1, 'status'=>'published','content'=>'<h2>About Us</h2><p>We build free, fast, and privacy-friendly online tools for everyone.</p>'],
        ['title'=>'Privacy Policy', 'slug'=>'privacy',        'page_type'=>'legal',  'show_in_footer'=>1,'sort_order'=>2, 'status'=>'published','content'=>'<h2>Privacy Policy</h2><p>We respect your privacy and do not sell your data.</p>'],
        ['title'=>'Terms of Service','slug'=>'terms',         'page_type'=>'legal',  'show_in_footer'=>1,'sort_order'=>3, 'status'=>'published','content'=>'<h2>Terms of Service</h2><p>By using this platform you agree to use it responsibly.</p>'],
        ['title'=>'Cookie Policy',  'slug'=>'cookie-policy',  'page_type'=>'legal',  'show_in_footer'=>1,'sort_order'=>4, 'status'=>'published','content'=>'<h2>Cookie Policy</h2><p>We use cookies to improve your experience.</p>'],
        ['title'=>'Disclaimer',     'slug'=>'disclaimer',     'page_type'=>'legal',  'show_in_footer'=>1,'sort_order'=>5, 'status'=>'published','content'=>'<h2>Disclaimer</h2><p>All tools are provided as-is without warranty.</p>'],
        ['title'=>'FAQ',            'slug'=>'faq',            'page_type'=>'page',   'show_in_footer'=>1,'sort_order'=>6, 'status'=>'published','content'=>'<h2>Frequently Asked Questions</h2><p>Have a question? Check our FAQ below.</p>'],
    ];
    foreach ($systemPages as $p) {
        if (!$db->exists('pages', 'slug = ?', [$p['slug']])) {
            $db->insert('pages', array_merge([
                'seo_title'  => null,
                'seo_desc'   => null,
                'author_id'  => null,
                'show_in_nav'=> 0,
                'created_at' => date('Y-m-d H:i:s'),
            ], $p));
        }
    }

    // ─── FAQs table ───────────────────────────────────────────────────────────
    $schemas[] = "CREATE TABLE IF NOT EXISTS faqs (
        id          {$AI},
        question    TEXT         NOT NULL,
        answer      TEXT         NOT NULL,
        category    VARCHAR(80)  DEFAULT NULL,
        sort_order  INTEGER      NOT NULL DEFAULT 0,
        is_active   INTEGER      NOT NULL DEFAULT 1,
        created_at  {$TS}        NOT NULL,
        updated_at  {$TS}        DEFAULT NULL
    )";

    // Run schemas
    foreach ($schemas as $sql) {
        $db->query($sql);
    }

    // ─── Seed default FAQs (idempotent) ──────────────────────────────────────
    $defaultFaqs = [
        ['question' => 'What is ' . ($settings ?? null)?->siteName() ?: 'Awan Tools' . '?',
         'answer'   => 'It is a free online toolbox for everyday and professional tasks — calculators, converters, generators, and more. No sign-up required to try any tool.',
         'sort_order' => 10],
        ['question' => 'Is it really free?',
         'answer'   => 'Yes, 100% free. No subscriptions, no paywalls, no hidden costs. Every tool on this platform is free to use forever.',
         'sort_order' => 20],
        ['question' => 'Do I need an account to use the tools?',
         'answer'   => 'Most tools work without an account. Creating a free account lets you save your work, access it from any device, and unlock features like history and personalisation.',
         'sort_order' => 30],
        ['question' => 'How do I request a new tool?',
         'answer'   => 'Use the "Request a Tool" page. Describe what you need and we will review it. Popular requests are prioritised for development.',
         'sort_order' => 40],
        ['question' => 'Is my data safe?',
         'answer'   => 'Yes. We do not sell or share your data. All data is stored securely and you can delete your account at any time.',
         'sort_order' => 50],
        ['question' => 'How do I report a bug or issue?',
         'answer'   => 'Visit the "Report an Issue" page and describe the problem. We aim to fix reported bugs within a few days.',
         'sort_order' => 60],
    ];
    foreach ($defaultFaqs as $fq) {
        if (!$db->exists('faqs', 'question = ?', [$fq['question']])) {
            $db->insert('faqs', array_merge($fq, ['is_active' => 1, 'created_at' => date('Y-m-d H:i:s')]));
        }
    }

    // ─── Seed testimonials (idempotent) ──────────────────────────────────────
    $defaultTestimonials = [
        ['name' => 'Sarah Mitchell',  'company' => 'Freelance Designer',    'title' => 'UX Designer',        'rating' => 5, 'testimonial' => 'The password generator alone saved me so much time. Clean UI, no ads, no tracking. Exactly what a good tool should be.'],
        ['name' => 'Karim Al-Hassan', 'company' => 'TechStart Ltd.',         'title' => 'Software Engineer',  'rating' => 5, 'testimonial' => 'I use the word counter every day for client deliverables. Love that it shows reading time and keyword density — details that matter.'],
        ['name' => 'Priya Nair',      'company' => 'Content Studio',         'title' => 'Content Strategist', 'rating' => 5, 'testimonial' => 'One account for all the tools I need. The Notes plugin is brilliant — I can access my notes from anywhere without paying for a subscription app.'],
        ['name' => 'James Thornton',  'company' => 'Remote First Agency',    'title' => 'Project Manager',    'rating' => 5, 'testimonial' => 'Recommended this platform to my whole team. The blog section gives it a community feel on top of being a proper toolbox.'],
        ['name' => 'Amelia Kowalski', 'company' => 'University of Warsaw',   'title' => 'Researcher',         'rating' => 5, 'testimonial' => 'Free, fast, and privacy-friendly. I appreciate that the platform is transparent about what it stores. Refreshing compared to other tools.'],
    ];
    foreach ($defaultTestimonials as $i => $t) {
        if (!$db->exists('testimonials', 'name = ?', [$t['name']])) {
            $db->insert('testimonials', array_merge($t, [
                'is_active'  => 1,
                'sort_order' => ($i + 1) * 10,
                'created_at' => date('Y-m-d H:i:s'),
            ]));
        }
    }

    // ─── Update email-otp template to use AWAN- prefix on code display ─────────
    try {
        $otpTpl = $db->fetch("SELECT id, body FROM email_templates WHERE slug = 'email-otp'");
        if ($otpTpl && strpos($otpTpl['body'], 'AWAN-') === false) {
            $updatedBody = str_replace(
                '>{{code}}<',
                '>AWAN-{{code}}<',
                $otpTpl['body']
            );
            $db->query("UPDATE email_templates SET body = ?, updated_at = ? WHERE slug = 'email-otp'", [$updatedBody, date('Y-m-d H:i:s')]);
        }
    } catch (Throwable $e) {}

    // ─── Seed built-in roles (idempotent) ────────────────────────────────────
    $builtinRoles = [
        ['name' => 'Admin',       'slug' => 'admin',       'description' => 'Full site administration access'],
        ['name' => 'Blog Writer', 'slug' => 'blog_writer', 'description' => 'Can write blog posts; posts require approval before publishing'],
        ['name' => 'User',        'slug' => 'user',        'description' => 'Standard registered user'],
    ];
    foreach ($builtinRoles as $r) {
        if (!$db->exists('roles', 'slug = ?', [$r['slug']])) {
            $db->insert('roles', array_merge($r, ['created_at' => date('Y-m-d H:i:s')]));
        }
    }

    // ─── Seed tool_catalogs homepage section (idempotent) ────────────────────
    if (!$db->exists('homepage_sections', "section_key = 'tool_catalogs'")) {
        $db->insert('homepage_sections', [
            'section_key' => 'tool_catalogs',
            'name'        => 'Tool Catalogs',
            'is_enabled'  => 1,
            'sort_order'  => 45,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    // ─── v2.7: Apply known default settings (only if currently empty) ────────
    $v27Defaults = [
        'timezone'              => 'Asia/Karachi',
        'google_oauth_enabled'  => '1',
        'google_client_id'      => '275770404774-63nuummqb36aq62t0969hc68nbn65nh4.apps.googleusercontent.com',
        'google_tag_manager_id' => 'GTM-5N38W2RZ',
        'microsoft_clarity_id'  => 'x9xe4hokzy',
        'bing_site_verification'=> 'A4FA4D57920B13E541F7840B33F84302',
        'yandex_verification'   => '1e643f60da5d1457',
        'recaptcha_enabled'     => '1',
        'recaptcha_version'     => '3',
        'recaptcha_site_key'    => '6LdEMhctAAAAANR1-6l3jVEydFU3m3MOSLkFAcHQ',
        'recaptcha_secret_key'  => '6LdEMhctAAAAAP5vMaI97gsyjrxH9U8A8YE_0oDo',
        'recaptcha_on_login'    => '1',
        'recaptcha_on_register' => '1',
        'recaptcha_v3_score'    => '0.5',
    ];
    foreach ($v27Defaults as $k => $v) {
        try {
            $existing = $db->fetch("SELECT `value` FROM settings WHERE `key` = ?", [$k]);
            if ($existing === null) {
                // Key missing — insert
                $db->insert('settings', ['key' => $k, 'value' => $v, 'group' => in_array($k, ['timezone']) ? 'general' : (str_starts_with($k, 'google_oauth') || str_starts_with($k, 'google_client') ? 'auth' : 'seo')]);
            } elseif ($existing['value'] === '' || $existing['value'] === '0' && in_array($k, ['recaptcha_enabled','google_oauth_enabled'])) {
                // Key exists but is blank or disabled — fill in
                $db->query("UPDATE settings SET `value` = ? WHERE `key` = ?", [$v, $k]);
            }
        } catch (Throwable $_e) {}
    }

    // ─── Record schema version ────────────────────────────────────────────────
    if ($db->exists('settings', "`key` = 'schema_version'")) {
        $db->query("UPDATE settings SET `value` = ? WHERE `key` = 'schema_version'", [$schemaVersion]);
    } else {
        $db->insert('settings', ['key' => 'schema_version', 'value' => $schemaVersion, 'group' => 'general']);
    }
}
