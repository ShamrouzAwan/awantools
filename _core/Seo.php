<?php
defined('AWAN') or die('Direct access denied.');

/**
 * Seo — Search-engine optimisation helper for AWAN Platform.
 * Generates meta tags, OG/Twitter cards, verification tags, analytics scripts,
 * reCAPTCHA widgets and sitemap/robots helpers.
 */
class Seo {
    private static ?self $instance = null;
    private Settings $settings;

    private function __construct(Settings $settings) {
        $this->settings = $settings;
    }

    public static function getInstance(Settings $settings): self {
        if (!self::$instance) self::$instance = new self($settings);
        return self::$instance;
    }

    // ─── Title ─────────────────────────────────────────────────────────────────

    /** Format a page title using the configured title format. */
    public function formatTitle(string $pageTitle): string {
        $format   = $this->settings->get('seo_title_format', ':title — :site_name');
        $siteName = $this->settings->siteName();
        $result   = str_replace([':title', ':site_name'], [$pageTitle, $siteName], $format);
        return $result ?: $siteName;
    }

    // ─── Head tags ─────────────────────────────────────────────────────────────

    /**
     * Return all SEO-related <head> tags as an HTML string.
     * @param array $opts  keys: title, description, image, canonical
     */
    public function headTags(array $opts = []): string {
        $out = '';
        $siteName  = $this->settings->siteName();
        $siteUrl   = rtrim($this->settings->get('seo_canonical_url') ?: $this->settings->get('site_url', ''), '/');
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $canonUrl  = $opts['canonical'] ?? ($siteUrl ? $siteUrl . strtok($requestUri, '?') : '');

        $title    = $opts['title'] ?? '';
        $desc     = $opts['description'] ?? $this->settings->get('seo_meta_description', $this->settings->siteTagline());
        $keywords = $this->settings->get('seo_meta_keywords', '');
        $image    = $opts['image'] ?? $this->settings->get('og_default_image', '');

        // Ensure image is absolute — fall back to auto-detecting scheme+host when site_url is blank
        if ($image && !preg_match('#^https?://#i', $image)) {
            $base = $siteUrl;
            if (!$base) {
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host   = $_SERVER['HTTP_HOST'] ?? '';
                if ($host) $base = $scheme . '://' . $host;
            }
            if ($base) $image = rtrim($base, '/') . '/' . ltrim($image, '/');
        }

        // ── Per-page OG overrides & article dates ──
        $ogTitle  = !empty($opts['og_title'])       ? $opts['og_title']       : $title;
        $ogDesc   = !empty($opts['og_description']) ? $opts['og_description'] : $desc;
        $ogTypeOv = $opts['og_type']           ?? null;
        $artPub   = $opts['article_published'] ?? '';
        $artMod   = $opts['article_modified']  ?? '';

        // ── Standard meta ──
        if ($desc)     $out .= '    <meta name="description" content="' . e($desc) . '">' . "\n";
        if ($keywords) $out .= '    <meta name="keywords" content="' . e($keywords) . '">' . "\n";

        // ── Robots ──
        if (!empty($opts['robots'])) {
            $out .= '    <meta name="robots" content="' . e($opts['robots']) . '">' . "\n";
        } else {
            $idx = $this->settings->get('seo_robots_index', '1') === '1' ? 'index' : 'noindex';
            $flw = $this->settings->get('seo_robots_follow', '1') === '1' ? 'follow' : 'nofollow';
            $out .= '    <meta name="robots" content="' . $idx . ', ' . $flw . '">' . "\n";
        }

        // ── Canonical ──
        if ($canonUrl) $out .= '    <link rel="canonical" href="' . e($canonUrl) . '">' . "\n";

        // ── Sitemap discovery ──
        if ($this->settings->get('sitemap_enabled', '1') === '1') {
            $base = $siteUrl ?: '';
            if ($base) $out .= '    <link rel="sitemap" type="application/xml" title="Sitemap" href="' . $base . '/sitemap.xml">' . "\n";
        }

        // ── OpenGraph ──
        if ($this->settings->get('og_enabled', '1') === '1') {
            $ogSite   = $this->settings->get('og_site_name', '') ?: $siteName;
            $ogType   = $ogTypeOv ?: $this->settings->get('og_type', 'website');
            $ogLocale = $this->settings->get('og_locale', 'en_US');

            $out .= '    <meta property="og:type" content="'      . e($ogType)   . '">' . "\n";
            $out .= '    <meta property="og:site_name" content="' . e($ogSite)   . '">' . "\n";
            $out .= '    <meta property="og:locale" content="'    . e($ogLocale) . '">' . "\n";
            if ($ogTitle)  $out .= '    <meta property="og:title" content="'       . e($ogTitle)  . '">' . "\n";
            if ($ogDesc)   $out .= '    <meta property="og:description" content="' . e($ogDesc)   . '">' . "\n";
            if ($image)    $out .= '    <meta property="og:image" content="'       . e($image)    . '">' . "\n";
            if ($canonUrl) $out .= '    <meta property="og:url" content="'         . e($canonUrl) . '">' . "\n";
            // Article-specific Open Graph tags (for blog posts)
            if ($artPub)  $out .= '    <meta property="article:published_time" content="' . e($artPub) . '">' . "\n";
            if ($artMod)  $out .= '    <meta property="article:modified_time" content="'  . e($artMod) . '">' . "\n";
        }

        // ── Twitter / X Card ──
        $tCard = $this->settings->get('twitter_card', '');
        if ($tCard && $tCard !== 'none') {
            $twSite    = ltrim($this->settings->get('twitter_site', ''), '@');
            $twCreator = ltrim($this->settings->get('twitter_creator', ''), '@');
            $out .= '    <meta name="twitter:card" content="' . e($tCard) . '">' . "\n";
            if ($twSite)    $out .= '    <meta name="twitter:site" content="@' . e($twSite) . '">' . "\n";
            if ($twCreator) $out .= '    <meta name="twitter:creator" content="@' . e($twCreator) . '">' . "\n";
            if ($ogTitle) $out .= '    <meta name="twitter:title" content="'       . e($ogTitle) . '">' . "\n";
            if ($ogDesc)  $out .= '    <meta name="twitter:description" content="' . e($ogDesc)  . '">' . "\n";
            if ($image)   $out .= '    <meta name="twitter:image" content="'       . e($image)   . '">' . "\n";
        }

        // ── Webmaster verification meta tags ──
        $verifications = [
            'google_site_verification' => 'google-site-verification',
            'bing_site_verification'   => 'msvalidate.01',
            'yandex_verification'      => 'yandex-verification',
        ];
        foreach ($verifications as $setting => $metaName) {
            $val = $this->settings->get($setting, '');
            if ($val) $out .= '    <meta name="' . $metaName . '" content="' . e(trim($val)) . '">' . "\n";
        }

        // ── reCAPTCHA script ──
        $out .= $this->recaptchaHeadScript();

        return $out;
    }

    // ─── Body start ────────────────────────────────────────────────────────────

    /** GTM noscript — place immediately after <body> opening tag. */
    public function bodyStart(): string {
        $gtmId = trim($this->settings->get('google_tag_manager_id', ''));
        if (!$gtmId) return '';
        return '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . e($gtmId) . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . "\n";
    }

    // ─── Body end ──────────────────────────────────────────────────────────────

    /** Analytics & tracking scripts — place before </body>. */
    public function bodyEnd(): string {
        $out = '';

        $gtmId     = trim($this->settings->get('google_tag_manager_id', ''));
        $gaId      = trim($this->settings->get('google_analytics_id', ''));
        $pixelId   = trim($this->settings->get('facebook_pixel_id', ''));
        $clarityId = trim($this->settings->get('microsoft_clarity_id', ''));

        // Google Tag Manager (handles GA if both are set)
        if ($gtmId) {
            $out .= "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','" . e($gtmId) . "');</script>\n";
        }

        // Google Analytics 4 (only if GTM is not configured)
        if ($gaId && !$gtmId) {
            $out .= '<script async src="https://www.googletagmanager.com/gtag/js?id=' . e($gaId) . '"></script>' . "\n";
            $out .= "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','" . e($gaId) . "');</script>\n";
        }

        // Facebook Pixel
        if ($pixelId) {
            $out .= "<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','" . e($pixelId) . "');fbq('track','PageView');</script>\n";
        }

        // Microsoft Clarity
        if ($clarityId) {
            $out .= "<script>(function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};t=l.createElement(r);t.async=1;t.src='https://www.clarity.ms/tag/'+i+'?ref=awan';y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y)})(window,document,'clarity','script','" . e($clarityId) . "');</script>\n";
        }

        return $out;
    }

    // ─── reCAPTCHA ─────────────────────────────────────────────────────────────

    /** reCAPTCHA <script> tag for <head>. Empty string if disabled. */
    public function recaptchaHeadScript(): string {
        if ($this->settings->get('recaptcha_enabled', '0') !== '1') return '';
        $key = $this->settings->get('recaptcha_site_key', '');
        if (!$key) return '';
        $v = $this->settings->get('recaptcha_version', '2');
        if ($v === '3') {
            return '    <script src="https://www.google.com/recaptcha/api.js?render=' . e($key) . '" async defer></script>' . "\n";
        }
        return '    <script src="https://www.google.com/recaptcha/api.js" async defer></script>' . "\n";
    }

    /**
     * Return the reCAPTCHA widget HTML to embed inside a <form>.
     * v2 = checkbox div; v3 = hidden input + submit handler.
     */
    public function recaptchaWidget(string $action = 'submit'): string {
        if ($this->settings->get('recaptcha_enabled', '0') !== '1') return '';
        $key = $this->settings->get('recaptcha_site_key', '');
        if (!$key) return '';
        $v = $this->settings->get('recaptcha_version', '2');
        if ($v === '2') {
            return '<div class="g-recaptcha" data-sitekey="' . e($key) . '" style="margin-bottom:16px"></div>' . "\n";
        }
        // v3: hidden field; script executes on form submit
        $act = preg_replace('/[^a-z0-9_]/i', '_', $action);
        $eid = 'rctoken_' . $act;
        return '<input type="hidden" name="g-recaptcha-response" id="' . $eid . '">'
            . "\n<script>document.addEventListener('DOMContentLoaded',function(){"
            . "var form=document.querySelector('[data-recaptcha-action=\"" . e($act) . "\"]');"
            . "if(!form)return;"
            . "form.addEventListener('submit',function(ev){"
            . "ev.preventDefault();"
            . "var f=this;"
            . "grecaptcha.ready(function(){"
            . "grecaptcha.execute('" . e($key) . "',{action:'" . e($act) . "'}).then(function(t){"
            . "document.getElementById('" . $eid . "').value=t;f.submit();});"
            . "});});});</script>\n";
    }

    /**
     * Verify a reCAPTCHA response token server-side.
     * Always returns true if reCAPTCHA is disabled or keys are not configured.
     */
    public function verifyRecaptcha(string $token): bool {
        if ($this->settings->get('recaptcha_enabled', '0') !== '1') return true;
        $secret = $this->settings->get('recaptcha_secret_key', '');
        if (empty($secret) || empty($token)) return false;

        $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'secret'   => $secret,
                'response' => $token,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]),
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $resp = @curl_exec($ch);
        curl_close($ch);
        if (!$resp) return false;

        $data = json_decode($resp, true);
        if (!($data['success'] ?? false)) return false;

        $v = $this->settings->get('recaptcha_version', '2');
        if ($v === '3') {
            $minScore = (float)$this->settings->get('recaptcha_v3_score', '0.5');
            return ($data['score'] ?? 0.0) >= $minScore;
        }
        return true;
    }

    // ─── JSON-LD Structured Data ────────────────────────────────────────────────

    /**
     * Generate a <script type="application/ld+json"> block.
     * @param array $data  A ready-to-encode schema.org object (must include @context and @type).
     */
    public function schemaOrg(array $data): string {
        if (empty($data)) return '';
        return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }

    /**
     * Return JSON-LD for the site homepage: WebSite + Organization.
     */
    public function homepageSchema(): string {
        $siteName = $this->settings->siteName();
        $siteUrl  = rtrim($this->settings->get('seo_canonical_url') ?: $this->settings->get('site_url', ''), '/');
        if (!$siteUrl) {
            $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host    = $_SERVER['HTTP_HOST'] ?? '';
            if ($host) $siteUrl = $scheme . '://' . $host;
        }
        $logoUrl = $this->settings->get('logo_url', '');
        if ($logoUrl && $siteUrl && !preg_match('#^https?://#i', $logoUrl)) {
            $logoUrl = rtrim($siteUrl, '/') . '/' . ltrim($logoUrl, '/');
        }

        $out = $this->schemaOrg([
            '@context' => 'https://schema.org',
            '@type'    => 'WebSite',
            'name'     => $siteName,
            'url'      => $siteUrl ?: '/',
            'description' => $this->settings->get('seo_meta_description', $this->settings->siteTagline()),
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => ($siteUrl ?: '') . '/search?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ]);

        if ($siteName) {
            $org = [
                '@context' => 'https://schema.org',
                '@type'    => 'Organization',
                'name'     => $siteName,
                'url'      => $siteUrl ?: '/',
            ];
            if ($logoUrl) $org['logo'] = $logoUrl;
            $email = $this->settings->get('developer_email', '');
            if ($email) $org['email'] = $email;
            $out .= $this->schemaOrg($org);
        }
        return $out;
    }

    /**
     * Return JSON-LD for a blog post.
     * @param array $post  Associative array with: title, slug, excerpt/content, featured_image, published_at, author_name
     */
    public function blogPostSchema(array $post): string {
        $siteName = $this->settings->siteName();
        $siteUrl  = rtrim($this->settings->get('seo_canonical_url') ?: $this->settings->get('site_url', ''), '/');
        if (!$siteUrl) {
            $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host    = $_SERVER['HTTP_HOST'] ?? '';
            if ($host) $siteUrl = $scheme . '://' . $host;
        }
        $slug    = $post['slug'] ?? '';
        $url     = $siteUrl ? $siteUrl . '/blog/' . $slug : '/blog/' . $slug;
        $image   = $post['featured_image'] ?? $post['og_image'] ?? '';
        if ($image && $siteUrl && !preg_match('#^https?://#i', $image)) {
            $image = rtrim($siteUrl, '/') . '/' . ltrim($image, '/');
        }
        $desc    = $post['seo_desc'] ?? $post['og_description'] ?? '';
        if (!$desc && !empty($post['content'])) {
            $desc = substr(strip_tags($post['content']), 0, 200);
        }

        $schema = [
            '@context'         => 'https://schema.org',
            '@type'            => 'BlogPosting',
            'headline'         => $post['title'] ?? '',
            'url'              => $url,
            'datePublished'    => $post['published_at'] ?? $post['created_at'] ?? '',
            'dateModified'     => $post['updated_at'] ?? $post['published_at'] ?? $post['created_at'] ?? '',
            'publisher'        => ['@type' => 'Organization', 'name' => $siteName],
        ];
        if ($desc)  $schema['description'] = $desc;
        if ($image) $schema['image'] = $image;
        if (!empty($post['author_name'])) {
            $schema['author'] = ['@type' => 'Person', 'name' => $post['author_name']];
        }
        return $this->schemaOrg($schema);
    }

    /** Is reCAPTCHA enabled for a specific form? */
    public function recaptchaOnForm(string $form): bool {
        if ($this->settings->get('recaptcha_enabled', '0') !== '1') return false;
        if (!$this->settings->get('recaptcha_site_key', '')) return false;
        return $this->settings->get('recaptcha_on_' . $form, '1') === '1';
    }

    // ─── FAQPage Schema ────────────────────────────────────────────────────────

    /**
     * Return JSON-LD FAQPage schema.
     * @param array $items  Flat array of ['q' => ..., 'a' => ...] pairs (HTML allowed in 'a').
     */
    public function faqPageSchema(array $items): string {
        if (empty($items)) return '';
        $entities = [];
        foreach ($items as $item) {
            if (empty($item['q'])) continue;
            $entities[] = [
                '@type' => 'Question',
                'name'  => $item['q'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => strip_tags($item['a'] ?? ''),
                ],
            ];
        }
        if (empty($entities)) return '';
        return $this->schemaOrg([
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $entities,
        ]);
    }

    // ─── Plugin / WebApplication Schema ────────────────────────────────────────

    /**
     * Return JSON-LD SoftwareApplication schema for a plugin page.
     * @param array $plugin  Keys: slug, name, description. siteUrl resolved internally.
     */
    public function pluginSchema(array $plugin): string {
        $siteUrl = rtrim($this->settings->get('seo_canonical_url') ?: $this->settings->get('site_url', ''), '/');
        if (!$siteUrl) {
            $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host    = $_SERVER['HTTP_HOST'] ?? '';
            if ($host) $siteUrl = $scheme . '://' . $host;
        }
        $slug = $plugin['slug'] ?? '';
        $url  = $siteUrl ? $siteUrl . '/plugins/' . $slug . '/' : '/plugins/' . $slug . '/';
        $data = [
            '@context'            => 'https://schema.org',
            '@type'               => 'SoftwareApplication',
            'name'                => $plugin['name'] ?? '',
            'url'                 => $url,
            'applicationCategory' => 'WebApplication',
            'operatingSystem'     => 'Any',
            'offers'              => [
                '@type'         => 'Offer',
                'price'         => '0',
                'priceCurrency' => 'USD',
            ],
        ];
        if (!empty($plugin['description'])) $data['description'] = $plugin['description'];
        return $this->schemaOrg($data);
    }
}
