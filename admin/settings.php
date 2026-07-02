<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireAdmin();

$logger = Logger::getInstance($db);
$tab    = Security::sanitize($_GET['tab'] ?? 'general');
$validTabs = ['general', 'auth', 'email', 'api', 'language', 'branding', 'social', 'homepage'];
if (!in_array($tab, $validTabs)) $tab = 'general';

function uploadBrandingFile(array $file, string $type): array {
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    if ($type === 'favicon') $allowedMimes[] = 'image/x-icon';
    $mime = @mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowedMimes)) {
        return ['ok' => false, 'error' => "Invalid file type. Allowed: JPEG, PNG, GIF, WebP, SVG."];
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'File too large. Max 2 MB.'];
    }
    $extMap = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp','image/svg+xml'=>'svg','image/x-icon'=>'ico'];
    $ext = $extMap[$mime] ?? 'png';
    $filename = $type . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dir  = AWAN_ROOT . '/storage/uploads/';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    if (!@move_uploaded_file($file['tmp_name'], $dir . $filename)) {
        return ['ok' => false, 'error' => 'Failed to save file. Check directory permissions.'];
    }
    return ['ok' => true, 'url' => '/storage/uploads/' . $filename];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();

    if ($tab === 'general') {
        foreach (['site_name', 'site_tagline', 'site_url', 'timezone', 'date_format', 'items_per_page', 'content_max_width'] as $f) {
            $settings->set($f, Security::sanitize($_POST[$f] ?? ''), 'general');
        }
        $settings->set('maintenance_mode',    isset($_POST['maintenance_mode']) ? '1' : '0', 'general');
        $settings->set('maintenance_title',   Security::sanitize($_POST['maintenance_title']   ?? ''), 'general');
        $settings->set('maintenance_message', Security::sanitize($_POST['maintenance_message'] ?? ''), 'general');
    }

    if ($tab === 'auth') {
        $settings->set('registration_enabled',       isset($_POST['registration_enabled'])       ? '1' : '0', 'auth');
        $settings->set('email_verification_enabled', isset($_POST['email_verification_enabled']) ? '1' : '0', 'auth');
        $settings->set('google_oauth_enabled',        isset($_POST['google_oauth_enabled'])       ? '1' : '0', 'auth');
        $settings->set('google_client_id',            Security::sanitize($_POST['google_client_id'] ?? ''), 'auth');
        // Only update secret if a new value was provided (blank = keep existing)
        $newSecret = Security::sanitize($_POST['google_client_secret'] ?? '');
        if ($newSecret !== '') {
            $settings->set('google_client_secret', $newSecret, 'auth');
        }
    }

    if ($tab === 'email') {
        // Sender identity (new mail_ keys; also write legacy smtp_ keys for backward compat)
        $mailFromEmail = Security::sanitize($_POST['mail_from_email'] ?? '');
        $mailFromName  = Security::sanitize($_POST['mail_from_name']  ?? '');
        $mailReplyTo   = Security::sanitize($_POST['mail_reply_to']   ?? '');
        $settings->set('mail_from_email', $mailFromEmail, 'email');
        $settings->set('mail_from_name',  $mailFromName,  'email');
        $settings->set('mail_reply_to',   $mailReplyTo,   'email');
        // Keep legacy keys in sync so old references still work
        $settings->set('smtp_from_email', $mailFromEmail, 'email');
        $settings->set('smtp_from_name',  $mailFromName,  'email');
        $settings->set('smtp_reply_to',   $mailReplyTo,   'email');

        // Queue settings
        $settings->set('email_queue_enabled',    isset($_POST['email_queue_enabled']) ? '1' : '0', 'email');
        $settings->set('email_queue_batch_size', (string)(int)($_POST['email_queue_batch_size'] ?? 20), 'email');

        // Send test email
        if (isset($_POST['send_test'])) {
            $to = Security::sanitize($_POST['test_email'] ?? $auth->email());
            if (!empty($to)) {
                $siteName = $settings->get('site_name', 'AWAN Platform');
                $body = Mailer::html(
                    $siteName,
                    'Test Email',
                    '<p>This is a test email from your AWAN Platform installation.</p>'
                    . '<p>If you can see this, PHP <code>mail()</code> is working correctly on your server.</p>',
                    'Visit Platform',
                    siteUrl()
                );
                $result = $mailer->send($to, "Test Email — {$siteName}", $body, true);
                if ($result) {
                    Session::flash('success', "Test email sent to {$to}. Check your inbox.");
                } else {
                    $err = $mailer->lastError();
                    Session::flash('danger', 'Email failed: ' . ($err['error'] ?? 'Unknown error'));
                }
                redirect('/admin/settings?tab=email');
            }
        }
    }

    if ($tab === 'api') {
        if (isset($_POST['regenerate_key'])) {
            $settings->set('api_key', bin2hex(random_bytes(20)), 'api');
            Session::flash('success', 'API key regenerated.');
            redirect('/admin/settings?tab=api');
        }
        if (isset($_POST['regenerate_cron'])) {
            $settings->set('cron_secret', bin2hex(random_bytes(16)), 'scheduler');
            Session::flash('success', 'Cron secret regenerated.');
            redirect('/admin/settings?tab=api');
        }
    }

    if ($tab === 'language') {
        $settings->set('language', Security::sanitize($_POST['language'] ?? 'en'), 'general');
        // Reset Lang singleton for the new locale
        Session::flash('success', 'Language saved.');
        redirect('/admin/settings?tab=language');
    }

    if ($tab === 'branding') {
        // Handle logo upload
        if (!empty($_FILES['logo_file']['name'])) {
            $result = uploadBrandingFile($_FILES['logo_file'], 'logo');
            if ($result['ok']) {
                $settings->set('logo_url', $result['url'], 'branding');
            } else {
                Session::flash('danger', 'Logo: ' . $result['error']);
                redirect('/admin/settings?tab=branding');
            }
        } elseif (isset($_POST['logo_url'])) {
            $settings->set('logo_url', Security::sanitize($_POST['logo_url']), 'branding');
        }
        if (isset($_POST['delete_logo'])) {
            $settings->set('logo_url', '', 'branding');
            Session::flash('success', 'Logo removed.');
            redirect('/admin/settings?tab=branding');
        }
        // Handle favicon upload
        if (!empty($_FILES['favicon_file']['name'])) {
            $result = uploadBrandingFile($_FILES['favicon_file'], 'favicon');
            if ($result['ok']) {
                $settings->set('favicon_url', $result['url'], 'branding');
            } else {
                Session::flash('danger', 'Favicon: ' . $result['error']);
                redirect('/admin/settings?tab=branding');
            }
        } elseif (isset($_POST['favicon_url'])) {
            $settings->set('favicon_url', Security::sanitize($_POST['favicon_url']), 'branding');
        }
        if (isset($_POST['delete_favicon'])) {
            $settings->set('favicon_url', '', 'branding');
            Session::flash('success', 'Favicon removed.');
            redirect('/admin/settings?tab=branding');
        }
        foreach (['developer_name','developer_title','developer_bio','developer_email'] as $f) {
            $settings->set($f, Security::sanitize($_POST[$f] ?? ''), 'branding');
        }
        $settings->set('footer_tagline',   Security::sanitize($_POST['footer_tagline'] ?? ''),   'footer');
        $settings->set('footer_copyright', Security::sanitize($_POST['footer_copyright'] ?? ''), 'footer');
    }

    if ($tab === 'social') {
        foreach (['developer_portfolio','developer_github','developer_linkedin','developer_twitter','developer_facebook','developer_instagram','developer_youtube','developer_whatsapp'] as $f) {
            $settings->set($f, Security::sanitize($_POST[$f] ?? ''), 'branding');
        }
    }

    if ($tab === 'homepage') {
        foreach (['hero_badge','hero_title','hero_title_accent','hero_subtitle','hero_cta_text','hero_cta_url','hero_secondary_cta_text','hero_secondary_cta_url','hero_image_url','blog_section_title','blog_section_count','testimonials_section_title'] as $f) {
            $settings->set($f, Security::sanitize($_POST[$f] ?? ''), 'homepage');
        }
        $settings->set('blog_section_enabled',         isset($_POST['blog_section_enabled'])         ? '1' : '0', 'homepage');
        $settings->set('testimonials_section_enabled', isset($_POST['testimonials_section_enabled']) ? '1' : '0', 'homepage');
    }

    $logger->info("Settings saved: tab={$tab}", [], $auth->id());
    Session::flash('success', 'Settings saved.');
    redirect('/admin/settings?tab=' . urlencode($tab));
}

$timezones = ['UTC', 'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles',
              'Europe/London', 'Europe/Paris', 'Europe/Berlin', 'Asia/Tokyo', 'Asia/Shanghai',
              'Asia/Singapore', 'Asia/Kolkata', 'Australia/Sydney', 'Pacific/Auckland'];

$languages = [
    'en' => 'English',
    'es' => 'Español (Spanish)',
    'fr' => 'Français (French)',
    'de' => 'Deutsch (German)',
    'ar' => 'العربية (Arabic)',
    'zh' => '中文 (Chinese)',
    'pt' => 'Português (Portuguese)',
    'ja' => '日本語 (Japanese)',
];

ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Settings</div>
            <div class="page-subtitle">Configure your platform</div>
        </div>
    </div>
</div>

<div class="page-body">
    <!-- Tabs -->
    <div style="display:flex;gap:4px;margin-bottom:20px;border-bottom:1px solid var(--color-border);padding-bottom:0;overflow-x:auto">
        <?php
        $tabs = ['general' => 'General', 'auth' => 'Auth', 'email' => 'Email', 'api' => 'API', 'language' => 'Language', 'branding' => 'Branding', 'social' => 'Social', 'homepage' => 'Homepage'];
        foreach ($tabs as $k => $label):
        ?>
        <a href="?tab=<?= $k ?>" style="
            padding:8px 14px;font-size:13px;font-weight:500;text-decoration:none;white-space:nowrap;
            border-bottom:2px solid <?= $tab === $k ? 'var(--color-primary)' : 'transparent' ?>;
            color:<?= $tab === $k ? 'var(--color-primary)' : 'var(--color-text-secondary)' ?>;
            margin-bottom:-1px;
        "><?= $label ?></a>
        <?php endforeach ?>
    </div>

    <form method="POST" action="/admin/settings?tab=<?= e($tab) ?>" data-loading style="max-width:640px" enctype="multipart/form-data">
        <?= Security::csrfField() ?>

        <?php if ($tab === 'general'): ?>
        <div class="card">
            <div class="card-header"><span class="card-title">Site Settings</span></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Site Name</label>
                    <input type="text" name="site_name" class="form-input" value="<?= e($settings->get('site_name', 'AWAN Platform')) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tagline</label>
                    <input type="text" name="site_tagline" class="form-input" value="<?= e($settings->get('site_tagline', '')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Site URL</label>
                    <input type="url" name="site_url" class="form-input" value="<?= e($settings->get('site_url', '')) ?>" placeholder="https://example.com">
                    <div class="form-hint">Used for email links and the cron endpoint URL.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Timezone</label>
                    <select name="timezone" class="form-input">
                        <?php foreach ($timezones as $tz): ?>
                        <option value="<?= e($tz) ?>" <?= $settings->get('timezone', 'UTC') === $tz ? 'selected' : '' ?>><?= e($tz) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Items Per Page</label>
                    <input type="number" name="items_per_page" class="form-input" value="<?= e($settings->get('items_per_page', '25')) ?>" min="5" max="200">
                </div>
                <div class="form-group">
                    <label class="form-label">Blog Content Max Width (px)</label>
                    <input type="number" name="content_max_width" class="form-input" value="<?= e($settings->get('content_max_width', '780')) ?>" min="600" max="1400" style="max-width:180px">
                    <div class="form-hint">Max width of blog post body text (600–1400 px). Default: 780.</div>
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="maintenance_mode" value="1" <?= $settings->isMaintenanceMode() ? 'checked' : '' ?>>
                        <span class="form-label" style="margin:0">Maintenance Mode</span>
                    </label>
                    <div class="form-hint">Only admins can access the site while enabled.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Maintenance Page Title</label>
                    <input type="text" name="maintenance_title" class="form-input"
                           value="<?= e($settings->get('maintenance_title', 'Under Maintenance')) ?>"
                           placeholder="Under Maintenance">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Maintenance Message</label>
                    <textarea name="maintenance_message" class="form-input" rows="2"
                              placeholder="We're currently performing scheduled maintenance. We'll be back shortly."><?= e($settings->get('maintenance_message', "We're currently performing scheduled maintenance. We'll be back shortly.")) ?></textarea>
                </div>
            </div>
        </div>

        <?php elseif ($tab === 'auth'): ?>
        <?php
        $verifyOn    = $settings->get('email_verification_enabled','0') === '1';
        $fromReady   = !empty($settings->get('mail_from_email','') ?: $settings->get('smtp_from_email',''));
        if ($verifyOn && !$fromReady):
        ?>
        <div class="alert alert-warning" style="margin-bottom:16px">
            <strong>From address not set.</strong> Email verification is enabled but no From address is configured — new users may not receive verification emails.
            <a href="/admin/settings?tab=email" style="font-weight:600">Configure Email &rarr;</a>
        </div>
        <?php endif ?>
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><span class="card-title">Registration &amp; Access</span></div>
            <div class="card-body">
                <div class="form-group mb-0">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="registration_enabled" value="1" <?= $settings->registrationEnabled() ? 'checked' : '' ?>>
                        <span class="form-label" style="margin:0">Allow Public Registration</span>
                    </label>
                    <div class="form-hint">When disabled, only admins can create new accounts.</div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><span class="card-title">Email Verification</span></div>
            <div class="card-body">
                <div class="form-group mb-0">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="email_verification_enabled" value="1"
                               <?= $settings->get('email_verification_enabled','0')==='1' ? 'checked' : '' ?>>
                        <span class="form-label" style="margin:0">Require Email Verification</span>
                    </label>
                    <div class="form-hint">New users must verify their email before signing in. Requires From Email to be configured in the Email tab.</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="card-title">Google OAuth</span>
                <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">Google Console →</a>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="google_oauth_enabled" value="1"
                               <?= $settings->get('google_oauth_enabled','0')==='1' ? 'checked' : '' ?>>
                        <span class="form-label" style="margin:0">Enable Google Sign-In</span>
                    </label>
                    <div class="form-hint">Shows "Continue with Google" buttons on the login and register pages.</div>
                </div>
                <div class="grid-2" style="gap:12px">
                    <div class="form-group mb-0">
                        <label class="form-label">Client ID</label>
                        <input type="text" name="google_client_id" class="form-input"
                               value="<?= e($settings->get('google_client_id','')) ?>"
                               placeholder="1234567890-xxx.apps.googleusercontent.com"
                               autocomplete="off">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Client Secret</label>
                        <input type="password" name="google_client_secret" class="form-input"
                               value="<?= e($settings->get('google_client_secret','')) ?>"
                               placeholder="Leave blank to keep current"
                               autocomplete="new-password">
                    </div>
                </div>
                <?php
                $callbackUrl = siteUrl('auth/google/callback');
                ?>
                <div class="form-hint" style="margin-top:12px">
                    Authorized redirect URI to add in Google Console: <code><?= e($callbackUrl) ?></code>
                </div>
            </div>
        </div>

        <?php elseif ($tab === 'email'): ?>
        <?php
        $fromEmail    = $settings->get('mail_from_email', '') ?: $settings->get('smtp_from_email', '');
        $fromName     = $settings->get('mail_from_name',  '') ?: $settings->get('smtp_from_name',  $settings->get('site_name',''));
        $replyTo      = $settings->get('mail_reply_to',   '') ?: $settings->get('smtp_reply_to',   '');
        $queueEnabled = $settings->get('email_queue_enabled', '0') === '1';
        $queueBatch   = $settings->get('email_queue_batch_size', '20');
        ?>

        <?php if (empty($fromEmail)): ?>
        <div class="alert alert-warning" style="margin-bottom:16px">
            <strong>From Email not set.</strong> Outgoing emails will use a server default until you configure one below.
        </div>
        <?php endif ?>

        <!-- Transport info -->
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><span class="card-title">Email Transport</span></div>
            <div class="card-body">
                <div style="display:flex;align-items:flex-start;gap:14px;padding:14px 16px;border:2px solid var(--color-primary,#6366f1);border-radius:8px">
                    <div style="font-size:20px;margin-top:1px">&#128231;</div>
                    <div>
                        <div style="font-weight:600;font-size:14px;margin-bottom:4px">PHP mail()</div>
                        <div style="font-size:13px;color:var(--color-text-muted);line-height:1.6">
                            Emails are sent via your hosting server's built-in <code>mail()</code> function.
                            No external mail server or credentials are required.
                            This is the most compatible option for shared hosting environments.
                        </div>
                        <div style="margin-top:8px;font-size:12px;color:var(--color-text-muted)">
                            For best deliverability: set the From Email to a real inbox on your domain
                            (e.g. <code>noreply@yourdomain.com</code>) and configure SPF/DKIM records in your DNS.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sender Identity -->
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><span class="card-title">Sender Identity</span></div>
            <div class="card-body">
                <div class="grid-2" style="gap:12px;margin-bottom:12px">
                    <div class="form-group mb-0">
                        <label class="form-label">From Email <span style="color:var(--color-danger,#ef4444)">*</span></label>
                        <input type="email" name="mail_from_email" class="form-input"
                               value="<?= e($fromEmail) ?>"
                               placeholder="noreply@yourdomain.com">
                        <div class="form-hint">Must be a real inbox on your hosting domain for reliable delivery.</div>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">From Name</label>
                        <input type="text" name="mail_from_name" class="form-input"
                               value="<?= e($fromName) ?>"
                               placeholder="<?= e($settings->get('site_name','Awan Tools')) ?>">
                        <div class="form-hint">Display name shown to email recipients.</div>
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Reply-To Email <span style="font-weight:400;color:var(--color-text-muted)">(optional)</span></label>
                    <input type="email" name="mail_reply_to" class="form-input"
                           value="<?= e($replyTo) ?>"
                           placeholder="support@yourdomain.com" style="max-width:320px">
                    <div class="form-hint">Where replies from users are directed. Defaults to From Email if left blank.</div>
                </div>
            </div>
        </div>

        <!-- Email Queue -->
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><span class="card-title">Email Queue</span></div>
            <div class="card-body">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:10px">
                    <input type="checkbox" name="email_queue_enabled" value="1" <?= $queueEnabled ? 'checked' : '' ?>>
                    <span class="form-label" style="margin:0">Enable async email queue</span>
                </label>
                <div class="form-hint" style="margin-bottom:12px">
                    When enabled, emails are queued in the database and sent by the built-in scheduler
                    (or via <code>POST /api/cron/email-queue</code>) instead of sending inline on each request.
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Batch Size</label>
                    <input type="number" name="email_queue_batch_size" class="form-input"
                           value="<?= e($queueBatch) ?>" min="1" max="200" style="max-width:120px">
                    <div class="form-hint">Emails processed per scheduler run. Default: 20.</div>
                </div>
                <?php if ($queueEnabled): ?>
                <div style="margin-top:12px;display:flex;gap:8px">
                    <a href="/admin/email-queue" class="btn btn-ghost btn-sm">View Queue</a>
                </div>
                <?php endif ?>
            </div>
        </div>

        <!-- Test email -->
        <div class="card">
            <div class="card-header"><span class="card-title">Send a Test Email</span></div>
            <div class="card-body">
                <p style="font-size:13px;color:var(--color-text-muted);margin-bottom:14px">
                    Save your settings first, then send a test email to verify PHP <code>mail()</code> is working on your server.
                </p>
                <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
                    <div class="form-group mb-0" style="flex:1;min-width:200px">
                        <label class="form-label">Send To</label>
                        <input type="email" name="test_email" class="form-input"
                               value="<?= e($auth->email() ?? '') ?>" placeholder="you@example.com">
                    </div>
                    <div class="form-group mb-0">
                        <button type="submit" name="send_test" value="1" class="btn btn-secondary">Send Test Email</button>
                    </div>
                    <div class="form-group mb-0">
                        <a href="/admin/email-logs" class="btn btn-ghost btn-sm" style="padding:9px 14px">View Logs</a>
                    </div>
                </div>
            </div>
        </div>

        <?php elseif ($tab === 'api'): ?>
        <div class="card" style="margin-bottom:16px">
            <div class="card-header">
                <span class="card-title">REST API Key</span>
                <a href="/api" target="_blank" class="btn btn-ghost btn-sm">View API →</a>
            </div>
            <div class="card-body">
                <p class="text-muted text-sm" style="margin-bottom:12px">
                    Use this key to authenticate API requests. Pass it as:<br>
                    <code>Authorization: Bearer {key}</code> or <code>?api_key={key}</code>
                </p>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                    <code style="flex:1;padding:10px 14px;background:var(--color-background);border:1px solid var(--color-border);border-radius:var(--radius-medium);font-size:12px;word-break:break-all"><?= e($settings->get('api_key','—')) ?></code>
                    <button type="button" class="btn btn-ghost btn-sm"
                            onclick="navigator.clipboard.writeText('<?= e($settings->get('api_key','')) ?>').then(()=>{this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',2000)})">Copy</button>
                </div>
                <div style="margin-top:12px">
                    <button type="submit" name="regenerate_key" value="1" class="btn btn-danger btn-sm"
                            onclick="return confirm('Regenerate API key? All existing integrations using the old key will stop working.')">
                        Regenerate Key
                    </button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><span class="card-title">Cron Secret</span></div>
            <div class="card-body">
                <p class="text-muted text-sm" style="margin-bottom:12px">
                    Required to trigger the <code>/cron</code> endpoint. Add to your crontab:
                </p>
                <code style="display:block;padding:10px 14px;background:var(--color-background);border:1px solid var(--color-border);border-radius:var(--radius-medium);font-size:12px;margin-bottom:12px;word-break:break-all">
                    * * * * * curl -s "<?= e($settings->get('site_url', 'http://localhost:8080')) ?>/cron?secret=<?= e($settings->get('cron_secret','')) ?>"
                </code>
                <button type="submit" name="regenerate_cron" value="1" class="btn btn-danger btn-sm"
                        onclick="return confirm('Regenerate cron secret?')">Regenerate Secret</button>
            </div>
        </div>

        <?php elseif ($tab === 'language'): ?>
        <div class="card">
            <div class="card-header"><span class="card-title">Platform Language</span></div>
            <div class="card-body">
                <div class="form-group mb-0">
                    <label class="form-label">Interface Language</label>
                    <select name="language" class="form-input">
                        <?php foreach ($languages as $code => $label): ?>
                        <option value="<?= e($code) ?>" <?= $settings->get('language','en') === $code ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                    <div class="form-hint">
                        Language files live in <code>_lang/{locale}.php</code>.
                        Currently only English is fully translated — other locales fall back to English for missing keys.
                        <a href="#" onclick="alert('Create awan/_lang/es.php and return a PHP array with translated strings.')">How to add a language →</a>
                    </div>
                </div>
            </div>
        </div>
        <?php elseif ($tab === 'branding'): ?>
        <?php
        $currentLogo    = $settings->get('logo_url', '');
        $currentFavicon = $settings->get('favicon_url', '');
        ?>
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><span class="card-title">Site Logo</span></div>
            <div class="card-body">
                <?php if ($currentLogo): ?>
                <div style="margin-bottom:12px;padding:12px;background:var(--color-background);border:1px solid var(--color-border);border-radius:var(--radius-medium);display:flex;align-items:center;gap:12px">
                    <img src="<?= e($currentLogo) ?>" alt="Current Logo" style="height:48px;width:auto;max-width:200px;object-fit:contain">
                    <div style="flex:1;font-size:13px;color:var(--color-text-muted)"><?= e($currentLogo) ?></div>
                    <button type="submit" name="delete_logo" value="1" class="btn btn-danger btn-sm" onclick="return confirm('Remove logo?')">Remove</button>
                </div>
                <?php endif ?>
                <div class="form-group">
                    <label class="form-label">Upload Logo</label>
                    <input type="file" name="logo_file" class="form-input" accept="image/*">
                    <div class="form-hint">Recommended: PNG or SVG, max 2 MB. Will replace the current logo.</div>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Or choose from Media Library / enter URL</label>
                    <div style="display:flex;gap:8px">
                        <input type="text" name="logo_url" id="branding_logo_url" class="form-input" value="<?= e($currentLogo) ?>" placeholder="https://example.com/logo.png">
                        <button type="button" class="btn btn-ghost btn-sm" onclick="openMediaPicker(function(url){document.getElementById('branding_logo_url').value=url})">Choose</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><span class="card-title">Favicon</span></div>
            <div class="card-body">
                <?php if ($currentFavicon): ?>
                <div style="margin-bottom:12px;padding:12px;background:var(--color-background);border:1px solid var(--color-border);border-radius:var(--radius-medium);display:flex;align-items:center;gap:12px">
                    <img src="<?= e($currentFavicon) ?>" alt="Current Favicon" style="height:32px;width:32px;object-fit:contain">
                    <div style="flex:1;font-size:13px;color:var(--color-text-muted)"><?= e($currentFavicon) ?></div>
                    <button type="submit" name="delete_favicon" value="1" class="btn btn-danger btn-sm" onclick="return confirm('Remove favicon?')">Remove</button>
                </div>
                <?php endif ?>
                <div class="form-group">
                    <label class="form-label">Upload Favicon</label>
                    <input type="file" name="favicon_file" class="form-input" accept="image/*,.ico">
                    <div class="form-hint">ICO, PNG, or SVG. 32×32 px recommended. Max 2 MB.</div>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Or choose from Media Library / enter URL</label>
                    <div style="display:flex;gap:8px">
                        <input type="text" name="favicon_url" id="branding_favicon_url" class="form-input" value="<?= e($currentFavicon) ?>" placeholder="https://example.com/favicon.ico">
                        <button type="button" class="btn btn-ghost btn-sm" onclick="openMediaPicker(function(url){document.getElementById('branding_favicon_url').value=url})">Choose</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><span class="card-title">Brand Identity</span></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Your Name / Brand Name</label>
                    <input type="text" name="developer_name" class="form-input" value="<?= e($settings->get('developer_name','')) ?>" placeholder="AWAN">
                </div>
                <div class="form-group">
                    <label class="form-label">Title / Role</label>
                    <input type="text" name="developer_title" class="form-input" value="<?= e($settings->get('developer_title','')) ?>" placeholder="Full-Stack Developer">
                </div>
                <div class="form-group">
                    <label class="form-label">Short Bio</label>
                    <textarea name="developer_bio" class="form-input" rows="3" placeholder="A brief description shown on the homepage Connect section."><?= e($settings->get('developer_bio','')) ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Email (public)</label>
                    <input type="email" name="developer_email" class="form-input" value="<?= e($settings->get('developer_email','')) ?>" placeholder="hello@example.com">
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><span class="card-title">Footer Text</span></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Footer Tagline</label>
                    <input type="text" name="footer_tagline" class="form-input" value="<?= e($settings->get('footer_tagline','')) ?>" placeholder="A curated collection of free online tools.">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Copyright Line</label>
                    <input type="text" name="footer_copyright" class="form-input" value="<?= e($settings->get('footer_copyright','')) ?>" placeholder="Leave blank to use site name">
                </div>
            </div>
        </div>

        <?php elseif ($tab === 'social'): ?>
        <div class="card">
            <div class="card-header"><span class="card-title">Social Media Links</span></div>
            <div class="card-body">
                <div class="form-hint" style="margin-bottom:16px">Links shown in the footer Connect column. Leave blank to hide.</div>
                <?php
                $socialFields = [
                    'developer_portfolio'  => ['label'=>'Portfolio / Website', 'ph'=>'https://myportfolio.com'],
                    'developer_github'     => ['label'=>'GitHub',              'ph'=>'https://github.com/username'],
                    'developer_linkedin'   => ['label'=>'LinkedIn',            'ph'=>'https://linkedin.com/in/username'],
                    'developer_twitter'    => ['label'=>'X / Twitter',         'ph'=>'https://x.com/username'],
                    'developer_facebook'   => ['label'=>'Facebook',            'ph'=>'https://facebook.com/username'],
                    'developer_instagram'  => ['label'=>'Instagram',           'ph'=>'https://instagram.com/username'],
                    'developer_youtube'    => ['label'=>'YouTube',             'ph'=>'https://youtube.com/@channel'],
                    'developer_whatsapp'   => ['label'=>'WhatsApp',            'ph'=>'https://wa.me/1234567890'],
                    'developer_email'      => ['label'=>'Email Address',       'ph'=>'hello@example.com'],
                ];
                foreach ($socialFields as $key => $meta): ?>
                <div class="form-group">
                    <label class="form-label"><?= $meta['label'] ?></label>
                    <input type="text" name="<?= $key ?>" class="form-input" value="<?= e($settings->get($key,'')) ?>" placeholder="<?= $meta['ph'] ?>">
                </div>
                <?php endforeach ?>
            </div>
        </div>

        <?php elseif ($tab === 'homepage'): ?>
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><span class="card-title">Hero Section</span></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Badge Text</label>
                    <input type="text" name="hero_badge" class="form-input" value="<?= e($settings->get('hero_badge','')) ?>" placeholder="Free Online Tools Platform">
                    <div class="form-hint">Small eyebrow label above the main headline.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Headline</label>
                    <input type="text" name="hero_title" class="form-input" value="<?= e($settings->get('hero_title','')) ?>" placeholder="One Account.">
                </div>
                <div class="form-group">
                    <label class="form-label">Headline Accent (highlighted text)</label>
                    <input type="text" name="hero_title_accent" class="form-input" value="<?= e($settings->get('hero_title_accent','')) ?>" placeholder="All Your Tools.">
                </div>
                <div class="form-group">
                    <label class="form-label">Subtitle</label>
                    <textarea name="hero_subtitle" class="form-input" rows="3"><?= e($settings->get('hero_subtitle','')) ?></textarea>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Hero Image</label>
                    <?php $heroImg = $settings->get('hero_image_url',''); if ($heroImg): ?>
                    <div style="margin-bottom:8px;padding:8px;background:var(--color-background);border:1px solid var(--color-border);border-radius:var(--radius-medium);display:flex;align-items:center;gap:10px">
                        <img src="<?= e($heroImg) ?>" alt="Hero" style="height:48px;width:auto;max-width:120px;object-fit:cover;border-radius:4px">
                        <span style="flex:1;font-size:12px;color:var(--color-text-muted);word-break:break-all"><?= e($heroImg) ?></span>
                    </div>
                    <?php endif ?>
                    <div style="display:flex;gap:8px">
                        <input type="text" name="hero_image_url" id="hero_image_url" class="form-input" value="<?= e($heroImg) ?>" placeholder="/storage/uploads/hero.jpg or https://…">
                        <button type="button" class="btn btn-ghost btn-sm" onclick="openMediaPicker(function(url){document.getElementById('hero_image_url').value=url})">Choose</button>
                    </div>
                    <div class="form-hint">Displayed in the hero section. Leave blank for no image. Recommended: 1200×600 px.</div>
                </div>
            </div>
        </div>
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><span class="card-title">Hero CTAs</span></div>
            <div class="card-body">
                <div class="grid-2" style="gap:12px">
                    <div class="form-group">
                        <label class="form-label">Primary Button Text</label>
                        <input type="text" name="hero_cta_text" class="form-input" value="<?= e($settings->get('hero_cta_text','')) ?>" placeholder="Get Started Free">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Primary Button URL</label>
                        <input type="text" name="hero_cta_url" class="form-input" value="<?= e($settings->get('hero_cta_url','')) ?>" placeholder="/register">
                    </div>
                </div>
                <div class="grid-2" style="gap:12px">
                    <div class="form-group mb-0">
                        <label class="form-label">Secondary Button Text</label>
                        <input type="text" name="hero_secondary_cta_text" class="form-input" value="<?= e($settings->get('hero_secondary_cta_text','')) ?>" placeholder="Browse Tools">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Secondary Button URL</label>
                        <input type="text" name="hero_secondary_cta_url" class="form-input" value="<?= e($settings->get('hero_secondary_cta_url','')) ?>" placeholder="/plugins">
                    </div>
                </div>
            </div>
        </div>
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><span class="card-title">Blog Section</span></div>
            <div class="card-body">
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="blog_section_enabled" <?= $settings->get('blog_section_enabled','1')==='1' ? 'checked' : '' ?>>
                        <span class="form-label" style="margin:0">Show latest blog posts on homepage</span>
                    </label>
                </div>
                <div class="grid-2" style="gap:12px">
                    <div class="form-group">
                        <label class="form-label">Section Title</label>
                        <input type="text" name="blog_section_title" class="form-input" value="<?= e($settings->get('blog_section_title','Latest Articles')) ?>" placeholder="Latest Articles">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Number of Posts</label>
                        <select name="blog_section_count" class="form-input">
                            <?php foreach ([2,3,4,6] as $n): ?>
                            <option value="<?= $n ?>" <?= $settings->get('blog_section_count','3')==$n ? 'selected' : '' ?>><?= $n ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><span class="card-title">Testimonials Section</span></div>
            <div class="card-body">
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="testimonials_section_enabled" <?= $settings->get('testimonials_section_enabled','1')==='1' ? 'checked' : '' ?>>
                        <span class="form-label" style="margin:0">Show testimonials on homepage</span>
                    </label>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Section Title</label>
                    <input type="text" name="testimonials_section_title" class="form-input" value="<?= e($settings->get('testimonials_section_title','What People Say')) ?>" placeholder="What People Say">
                </div>
                <div class="form-hint" style="margin-top:8px">
                    Manage testimonials content in <a href="/admin/testimonials">Admin → Testimonials</a>.
                </div>
            </div>
        </div>
        <?php endif ?>

        <div style="margin-top:16px">
            <?php if ($tab !== 'api'): ?>
            <button type="submit" class="btn btn-primary" data-loading="Saving…">Save Settings</button>
            <?php endif ?>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('Settings', $content, ['section' => 'settings']);
