<?php
defined('AWAN') or die();
require_once __DIR__ . '/_bootstrap.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();

    $name  = trim(Security::sanitize($_POST['name'] ?? ''));
    $email = trim(Security::sanitize($_POST['email'] ?? ''));
    $type  = trim(Security::sanitize($_POST['request_type'] ?? 'plugin'));
    $title = trim(Security::sanitize($_POST['title'] ?? ''));
    $desc  = trim(Security::sanitize($_POST['description'] ?? ''));

    if (strlen($name) < 2)  $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (strlen($title) < 3) $errors[] = 'Please enter a title for the tool.';
    if (strlen($desc) < 20) $errors[] = 'Please describe the tool in at least 20 characters.';

    if (empty($errors)) {
        try {
            $db->insert('tool_requests', [
                'name'         => $name,
                'email'        => $email,
                'request_type' => $type,
                'title'        => $title,
                'description'  => $desc,
                'status'       => 'new',
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
            $logger->info("New tool request: {$title} from {$email}");
            try {
                require_once __DIR__ . '/_core/Notifications.php';
                Notifications::create($db, 'tool', "New tool request: {$title}", "From: {$name} ({$email})", '/admin/notifications');
            } catch (Throwable $ne) {}

            // Auto-reply confirmation to the submitter
            try {
                $siteName = $settings->get('site_name', 'AWAN Platform');
                $siteUrl  = rtrim($settings->get('site_url', ''), '/');
                $replyBody = "<p>Hi {$name},</p>"
                    . "<p>Thank you for your tool request! I've received your idea and added it to the review queue. Popular requests get built first, so the more interest it gets, the sooner it may appear on the platform.</p>"
                    . "<p style='background:#f1f5f9;padding:12px 16px;border-radius:6px;font-size:14px'>"
                    . "<strong>Requested tool:</strong> " . htmlspecialchars($title) . "<br>"
                    . "<strong>Description:</strong><br>" . nl2br(htmlspecialchars($desc)) . "</p>"
                    . "<p>Keep an eye on the platform for updates!</p>";
                $replyHtml = Mailer::html($siteName, "Tool request received!", $replyBody, 'Browse Tools', ($siteUrl ?: '') . '/plugins');
                $mailer->send($email, "Tool request received — {$siteName}", $replyHtml, true);
            } catch (Throwable $me) {}

            Session::flash('success', "Thanks! Your tool request for \"{$title}\" has been submitted. I'll review it and may add it to the platform.");
            redirect('/request-tool');
        } catch (Exception $e) {
            $errors[] = 'Failed to submit. Please try again.';
        }
    }
}

ob_start();
?>
<div class="page-hero">
    <div class="page-hero-inner">
        <h1>Request a Tool</h1>
        <p>Have an idea for a new plugin or feature? Submit it here — popular requests get built first.</p>
    </div>
</div>

<div class="front-container" style="padding-top:48px;padding-bottom:72px">
    <div class="contact-grid">
        <div class="contact-form-card">
            <h2 style="font-size:18px;font-weight:700;margin-bottom:20px">Submit Your Idea</h2>
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin:0;padding-left:16px"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach ?></ul>
            </div>
            <?php endif ?>
            <form method="POST" data-loading>
                <?= Security::csrfField() ?>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Your Name <span class="req">*</span></label>
                        <input type="text" name="name" class="form-input" required value="<?= e($_POST['name'] ?? '') ?>" placeholder="John Doe">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email <span class="req">*</span></label>
                        <input type="email" name="email" class="form-input" required value="<?= e($_POST['email'] ?? '') ?>" placeholder="you@example.com">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Request Type</label>
                        <select name="request_type" class="form-input">
                            <option value="plugin" <?= ($_POST['request_type'] ?? 'plugin') === 'plugin' ? 'selected' : '' ?>>New Plugin / Tool</option>
                            <option value="feature" <?= ($_POST['request_type'] ?? '') === 'feature' ? 'selected' : '' ?>>Feature for Existing Plugin</option>
                            <option value="integration" <?= ($_POST['request_type'] ?? '') === 'integration' ? 'selected' : '' ?>>Third-Party Integration</option>
                            <option value="template" <?= ($_POST['request_type'] ?? '') === 'template' ? 'selected' : '' ?>>Template / Theme</option>
                            <option value="other" <?= ($_POST['request_type'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tool / Feature Name <span class="req">*</span></label>
                        <input type="text" name="title" class="form-input" required value="<?= e($_POST['title'] ?? '') ?>" placeholder="e.g. Time Tracker Plugin">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description <span class="req">*</span></label>
                    <textarea name="description" class="form-input" rows="6" required
                              placeholder="Describe what the tool should do, who it's for, and why it would be useful. Include any specific features or workflows."><?= e($_POST['description'] ?? '') ?></textarea>
                    <div class="form-hint">The more specific you are, the better I can assess feasibility and prioritise accordingly.</div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-full" data-loading="Submitting…">
                    Submit Request
                </button>
            </form>
        </div>

        <div>
            <div class="contact-info-card">
                <h3 style="font-size:14px;font-weight:700;margin-bottom:14px">What We're Looking For</h3>
                <?php
                $examples = [
                    ['Calculators & Converters', 'Financial, scientific, unit converters, and specialised calculators.'],
                    ['Business Tools', 'CRM, HR, inventory, invoicing, project management.'],
                    ['Developer Utilities', 'JSON formatter, regex tester, base64, hash generators.'],
                    ['Productivity', 'Notes, to-do lists, timers, habit trackers.'],
                    ['Integrations', 'Google Sheets sync, Slack alerts, email automations.'],
                ];
                foreach ($examples as [$name, $desc]):
                ?>
                <div style="padding:10px 0;border-bottom:1px solid var(--color-border)">
                    <div style="font-size:13px;font-weight:600;margin-bottom:2px"><?= $name ?></div>
                    <div style="font-size:12px;color:var(--color-text-secondary)"><?= $desc ?></div>
                </div>
                <?php endforeach ?>
                <div style="padding-top:16px">
                    <div class="badge badge-info" style="margin-bottom:8px">Review time: 3–5 business days</div>
                    <p style="font-size:12px;color:var(--color-text-muted)">Requests are evaluated based on feasibility, demand, and alignment with the platform's goals.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Request a Tool', $content, ['description' => 'Submit an idea for a new plugin or feature for the AWAN Platform.']);
