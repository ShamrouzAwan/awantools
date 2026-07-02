<?php
defined('AWAN') or die();
require_once __DIR__ . '/_bootstrap.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();

    $name    = trim(Security::sanitize($_POST['name'] ?? ''));
    $email   = trim(Security::sanitize($_POST['email'] ?? ''));
    $subject = trim(Security::sanitize($_POST['subject'] ?? ''));
    $message = trim(Security::sanitize($_POST['message'] ?? ''));

    if (strlen($name) < 2)    $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (strlen($subject) < 3) $errors[] = 'Please enter a subject.';
    if (strlen($message) < 10) $errors[] = 'Message must be at least 10 characters.';

    if (empty($errors)) {
        try {
            // Save to database
            $db->insert('contact_messages', [
                'name'       => $name,
                'email'      => $email,
                'subject'    => $subject,
                'message'    => $message,
                'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $logger->info("Contact form submission from {$email}: {$subject}", [
                'name' => $name, 'email' => $email, 'subject' => $subject,
            ]);
            try {
                require_once __DIR__ . '/_core/Notifications.php';
                Notifications::create($db, 'contact', "New contact from {$name}", $subject, '/admin/notifications');
            } catch (Throwable $ne) {}

            // Auto-reply confirmation to the submitter
            try {
                $siteName = $settings->get('site_name', 'AWAN Platform');
                $siteUrl  = rtrim($settings->get('site_url', ''), '/');
                $replyBody = "<p>Hi {$name},</p>"
                    . "<p>Thank you for reaching out. I've received your message and will get back to you within <strong>24–48 hours</strong>.</p>"
                    . "<p style='background:#f1f5f9;padding:12px 16px;border-radius:6px;font-size:14px'>"
                    . "<strong>Your message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>"
                    . "<p>In the meantime, feel free to browse the platform.</p>";
                $replyHtml = Mailer::html($siteName, "We received your message!", $replyBody, 'Visit Platform', $siteUrl ?: '/');
                $mailer->send($email, "Message received — {$siteName}", $replyHtml, true);
            } catch (Throwable $me) {}

            Session::flash('success', 'Your message has been sent! I\'ll get back to you soon.');
            redirect('/contact');
        } catch (Throwable $e) {
            $errors[] = 'Failed to send message. Please try again or contact us directly.';
        }
    }
}

$devEmail    = $settings->get('developer_email', '');
$devGithub   = $settings->get('developer_github', '');
$devLinkedin = $settings->get('developer_linkedin', '');
$devTwitter  = $settings->get('developer_twitter', '');

ob_start();
?>
<div class="page-hero">
    <div class="page-hero-inner">
        <h1>Contact Me</h1>
        <p>Have a project in mind, a question, or just want to say hello? I'd love to hear from you.</p>
    </div>
</div>

<div class="front-container" style="padding-top:48px;padding-bottom:64px">
    <div class="contact-grid">
        <!-- Form -->
        <div class="contact-form-card">
            <h2 style="font-size:18px;font-weight:700;margin-bottom:20px">Send a Message</h2>
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin:0;padding-left:16px">
                    <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
            <?php endif ?>
            <form method="POST" data-loading>
                <?= Security::csrfField() ?>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Your Name <span class="req">*</span></label>
                        <input type="text" name="name" class="form-input" required
                               value="<?= e($_POST['name'] ?? '') ?>" placeholder="John Doe">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address <span class="req">*</span></label>
                        <input type="email" name="email" class="form-input" required
                               value="<?= e($_POST['email'] ?? '') ?>" placeholder="you@example.com">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Subject <span class="req">*</span></label>
                    <input type="text" name="subject" class="form-input" required
                           value="<?= e($_POST['subject'] ?? '') ?>" placeholder="How can I help you?">
                </div>
                <div class="form-group">
                    <label class="form-label">Message <span class="req">*</span></label>
                    <textarea name="message" class="form-input" rows="6" required
                              placeholder="Describe your project, question, or inquiry…"><?= e($_POST['message'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-full" data-loading="Sending…">
                    Send Message
                </button>
            </form>
        </div>

        <!-- Info sidebar -->
        <div>
            <div class="contact-info-card">
                <h3 style="font-size:15px;font-weight:700;margin-bottom:16px">Contact Info</h3>

                <?php if ($devEmail): ?>
                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </div>
                    <div>
                        <div class="contact-info-label">Email</div>
                        <div class="contact-info-value"><a href="mailto:<?= e($devEmail) ?>" style="color:var(--color-primary);text-decoration:none"><?= e($devEmail) ?></a></div>
                    </div>
                </div>
                <?php endif ?>

                <?php if ($devGithub): ?>
                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg>
                    </div>
                    <div>
                        <div class="contact-info-label">GitHub</div>
                        <div class="contact-info-value"><a href="<?= e($devGithub) ?>" target="_blank" rel="noopener" style="color:var(--color-primary);text-decoration:none"><?= parse_url($devGithub, PHP_URL_HOST) . parse_url($devGithub, PHP_URL_PATH) ?></a></div>
                    </div>
                </div>
                <?php endif ?>

                <?php if ($devLinkedin): ?>
                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </div>
                    <div>
                        <div class="contact-info-label">LinkedIn</div>
                        <div class="contact-info-value"><a href="<?= e($devLinkedin) ?>" target="_blank" rel="noopener" style="color:var(--color-primary);text-decoration:none">Connect on LinkedIn</a></div>
                    </div>
                </div>
                <?php endif ?>

                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <div class="contact-info-label">Response Time</div>
                        <div class="contact-info-value">Within 24–48 hours</div>
                    </div>
                </div>
            </div>

            <div class="contact-info-card" style="margin-top:16px">
                <h3 style="font-size:14px;font-weight:700;margin-bottom:12px">Looking for Something Specific?</h3>
                <div style="display:flex;flex-direction:column;gap:8px">
                    <a href="/get-a-quote" class="btn btn-outline btn-sm" style="justify-content:center">Request a Quote</a>
                    <a href="/request-tool" class="btn btn-ghost btn-sm" style="justify-content:center">Request a New Tool</a>
                    <a href="/faq" class="btn btn-ghost btn-sm" style="justify-content:center">Browse FAQ</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Contact', $content, ['description' => 'Get in touch — send a message, request a quote, or ask any questions.']);
