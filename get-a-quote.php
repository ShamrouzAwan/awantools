<?php
defined('AWAN') or die();
require_once __DIR__ . '/_bootstrap.php';

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();

    $name    = trim(Security::sanitize($_POST['name'] ?? ''));
    $email   = trim(Security::sanitize($_POST['email'] ?? ''));
    $company = trim(Security::sanitize($_POST['company'] ?? ''));
    $budget  = trim(Security::sanitize($_POST['budget'] ?? ''));
    $timeline= trim(Security::sanitize($_POST['timeline'] ?? ''));
    $desc    = trim(Security::sanitize($_POST['project_description'] ?? ''));

    if (strlen($name) < 2)  $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (strlen($desc) < 20) $errors[] = 'Please describe your project in at least 20 characters.';

    if (empty($errors)) {
        try {
            $db->insert('quote_requests', [
                'name'                => $name,
                'email'               => $email,
                'company'             => $company ?: null,
                'budget'              => $budget ?: null,
                'timeline'            => $timeline ?: null,
                'project_description' => $desc,
                'status'              => 'new',
                'created_at'          => date('Y-m-d H:i:s'),
            ]);
            $logger->info("New quote request from {$email}", ['name' => $name]);
            try {
                require_once __DIR__ . '/_core/Notifications.php';
                Notifications::create($db, 'quote', "New quote request from {$name}", "Budget: {$budget} | {$desc}", '/admin/notifications');
            } catch (Throwable $ne) {}

            // Auto-reply confirmation to the submitter
            try {
                $siteName = $settings->get('site_name', 'AWAN Platform');
                $siteUrl  = rtrim($settings->get('site_url', ''), '/');
                $details  = [];
                if ($company)  $details[] = "<strong>Company:</strong> {$company}";
                if ($budget)   $details[] = "<strong>Budget:</strong> {$budget}";
                if ($timeline) $details[] = "<strong>Timeline:</strong> {$timeline}";
                $detailsHtml = $details ? '<p>' . implode('<br>', $details) . '</p>' : '';
                $replyBody = "<p>Hi {$name},</p>"
                    . "<p>Thank you for your quote request! I've received your project details and will review them carefully. Expect a detailed proposal within <strong>24–48 hours</strong>.</p>"
                    . $detailsHtml
                    . "<p style='background:#f1f5f9;padding:12px 16px;border-radius:6px;font-size:14px'>"
                    . "<strong>Project description:</strong><br>" . nl2br(htmlspecialchars($desc)) . "</p>"
                    . "<p>I look forward to working with you!</p>";
                $replyHtml = Mailer::html($siteName, "Quote request received!", $replyBody, 'Visit Platform', $siteUrl ?: '/');
                $mailer->send($email, "Quote request received — {$siteName}", $replyHtml, true);
            } catch (Throwable $me) {}

            Session::flash('success', "Thanks {$name}! Your quote request has been received. I'll review it and get back to you within 24-48 hours.");
            redirect('/get-a-quote');
        } catch (Exception $e) {
            $errors[] = 'Failed to submit request. Please try again.';
        }
    }
}

ob_start();
?>
<div class="page-hero">
    <div class="page-hero-inner">
        <h1>Get a Quote</h1>
        <p>Tell me about your project and I'll get back to you with a detailed proposal within 24–48 hours.</p>
    </div>
</div>

<div class="front-container" style="padding-top:48px;padding-bottom:72px">
    <div class="contact-grid">
        <div class="contact-form-card">
            <h2 style="font-size:18px;font-weight:700;margin-bottom:20px">Project Details</h2>
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin:0;padding-left:16px"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach ?></ul>
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
                        <label class="form-label">Company / Organisation</label>
                        <input type="text" name="company" class="form-input" value="<?= e($_POST['company'] ?? '') ?>" placeholder="Acme Inc. (optional)">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Budget Range</label>
                        <select name="budget" class="form-input">
                            <option value="">Select budget (optional)</option>
                            <option value="< $500" <?= ($_POST['budget'] ?? '') === '< $500' ? 'selected' : '' ?>>Under $500</option>
                            <option value="$500 – $1,000" <?= ($_POST['budget'] ?? '') === '$500 – $1,000' ? 'selected' : '' ?>>$500 – $1,000</option>
                            <option value="$1,000 – $5,000" <?= ($_POST['budget'] ?? '') === '$1,000 – $5,000' ? 'selected' : '' ?>>$1,000 – $5,000</option>
                            <option value="$5,000 – $10,000" <?= ($_POST['budget'] ?? '') === '$5,000 – $10,000' ? 'selected' : '' ?>>$5,000 – $10,000</option>
                            <option value="> $10,000" <?= ($_POST['budget'] ?? '') === '> $10,000' ? 'selected' : '' ?>>Over $10,000</option>
                            <option value="Discuss" <?= ($_POST['budget'] ?? '') === 'Discuss' ? 'selected' : '' ?>>Let's discuss</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Desired Timeline</label>
                    <select name="timeline" class="form-input">
                        <option value="">Select timeline (optional)</option>
                        <option value="ASAP" <?= ($_POST['timeline'] ?? '') === 'ASAP' ? 'selected' : '' ?>>As soon as possible</option>
                        <option value="1 week" <?= ($_POST['timeline'] ?? '') === '1 week' ? 'selected' : '' ?>>Within 1 week</option>
                        <option value="2-4 weeks" <?= ($_POST['timeline'] ?? '') === '2-4 weeks' ? 'selected' : '' ?>>2–4 weeks</option>
                        <option value="1-3 months" <?= ($_POST['timeline'] ?? '') === '1-3 months' ? 'selected' : '' ?>>1–3 months</option>
                        <option value="3+ months" <?= ($_POST['timeline'] ?? '') === '3+ months' ? 'selected' : '' ?>>3+ months</option>
                        <option value="Flexible" <?= ($_POST['timeline'] ?? '') === 'Flexible' ? 'selected' : '' ?>>Flexible</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Describe Your Project <span class="req">*</span></label>
                    <textarea name="project_description" class="form-input" rows="7" required
                              placeholder="What do you need built? What problem does it solve? Any specific features or requirements? The more detail, the better."><?= e($_POST['project_description'] ?? '') ?></textarea>
                    <div class="form-hint">Minimum 20 characters. The more detail you provide, the more accurate your quote will be.</div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-full" data-loading="Submitting…">
                    Submit Quote Request
                </button>
            </form>
        </div>

        <div>
            <div class="contact-info-card">
                <h3 style="font-size:15px;font-weight:700;margin-bottom:16px">What Happens Next?</h3>
                <?php foreach ([
                    ['1', 'I review your request', 'I\'ll read through your project description carefully and assess requirements.'],
                    ['2', 'I prepare a proposal', 'A detailed quote with scope, timeline, and pricing will be prepared for you.'],
                    ['3', 'We connect', 'I\'ll reach out to your email within 24–48 hours to discuss the proposal.'],
                    ['4', 'We get started', 'Once aligned, we kick off the project with clear milestones.'],
                ] as [$num, $title, $body]): ?>
                <div style="display:flex;gap:14px;padding:14px 0;border-bottom:1px solid var(--color-border)">
                    <div style="width:28px;height:28px;background:var(--color-primary);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0"><?= $num ?></div>
                    <div>
                        <div style="font-size:13px;font-weight:600;margin-bottom:3px"><?= $title ?></div>
                        <div style="font-size:12px;color:var(--color-text-secondary)"><?= $body ?></div>
                    </div>
                </div>
                <?php endforeach ?>
                <div style="padding-top:16px">
                    <a href="/contact" class="btn btn-ghost btn-sm">Or just say hello →</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Get a Quote', $content, ['description' => 'Request a project quote — describe your idea and receive a detailed proposal within 24–48 hours.']);
