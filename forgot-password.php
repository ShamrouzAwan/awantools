<?php
defined('AWAN') or die();
require_once __DIR__ . '/_bootstrap.php';

if ($auth->check()) redirect('/account/dashboard');

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();

    $email = Security::sanitizeEmail($_POST['email'] ?? '');

    if (empty($email) || !Security::validateEmail($email)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        // Rate limit
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!Security::checkRateLimit('forgot_pw_' . $ip, 5, 3600)) {
            $errors[] = 'Too many requests. Please wait an hour before trying again.';
        } else {
            $token = $auth->generatePasswordResetToken($email);

            // Always show success to prevent email enumeration
            if ($token) {
                $resetUrl = siteUrl('reset-password') . '?token=' . urlencode($token);
                $siteName = $settings->siteName();

                $user = $db->fetch("SELECT name, username FROM users WHERE email = ? LIMIT 1", [$email]);
                $name = $user['name'] ?: ($user['username'] ?? 'there');

                $mailer->sendTemplate('password-reset', $email, [
                    'name'        => $name,
                    'reset_url'   => $resetUrl,
                    'cta_text'    => 'Reset My Password',
                    'cta_url'     => $resetUrl,
                    'email_title' => 'Reset your password',
                ]);
            }

            $success = true;
        }
    }
}

ob_start();
?>
<div style="display:flex;align-items:center;justify-content:center;min-height:calc(100vh - 54px)">
<div style="width:100%;max-width:380px">

    <div class="auth-brand">
        <?php $authLogo = $settings->get('logo_url', ''); if ($authLogo): ?>
        <a href="/" style="display:inline-block;margin-bottom:8px"><img src="<?= e($authLogo) ?>" alt="<?= e($settings->siteName()) ?>" style="height:44px;width:auto;max-width:180px;object-fit:contain"></a>
        <?php else: ?>
        <div class="auth-brand-icon">A</div>
        <?php endif ?>
        <div class="auth-brand-name"><?= e($settings->siteName()) ?></div>
        <div class="auth-brand-tagline">Reset your password</div>
    </div>

    <div class="auth-card">
        <?php if ($success): ?>
        <div style="text-align:center">
            <div style="width:56px;height:56px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
                <svg width="24" height="24" fill="none" stroke="#16a34a" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="auth-title" style="margin-bottom:8px">Check your inbox</div>
            <p style="color:var(--color-text-secondary);font-size:14px;line-height:1.6">
                If an account exists for that email, we've sent a password reset link. It expires in <strong>1 hour</strong>.
            </p>
            <p style="color:var(--color-text-muted);font-size:13px;margin-top:12px">
                Check your spam folder if you don't see it.
            </p>
        </div>
        <?php else: ?>
        <div class="auth-title">Forgot password?</div>
        <div class="auth-subtitle">Enter your email and we'll send you a reset link</div>

        <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger" style="margin-top:12px"><?= e($err) ?></div>
        <?php endforeach ?>

        <form method="POST" action="/forgot-password" data-loading style="margin-top:16px">
            <?= Security::csrfField() ?>
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control"
                       value="<?= e($_POST['email'] ?? '') ?>"
                       placeholder="you@example.com"
                       autocomplete="email" autofocus required>
            </div>
            <button type="submit" class="btn btn-primary w-full" data-loading="Sending…">
                Send Reset Link
            </button>
        </form>
        <?php endif ?>
    </div>

    <div class="auth-footer">
        <a href="/login">Back to Sign In</a>
        <?php if ($settings->registrationEnabled()): ?>
        &nbsp;·&nbsp; <a href="/register">Create account</a>
        <?php endif ?>
    </div>
</div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Forgot Password', $content, ['nav' => false]);
