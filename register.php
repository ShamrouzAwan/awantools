<?php
defined('AWAN') or die();
require_once __DIR__ . '/_bootstrap.php';

if ($auth->check()) redirect('/account/dashboard');
if (!$settings->registrationEnabled()) {
    Session::flash('warning', 'Registration is currently disabled.');
    redirect('/login');
}

$errors        = [];
$vals          = [];
$googleEnabled = $settings->get('google_oauth_enabled', '0') === '1'
              && $settings->get('google_client_id', '') !== '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();

    $vals = [
        'username' => Security::sanitize($_POST['username'] ?? ''),
        'email'    => Security::sanitizeEmail($_POST['email'] ?? ''),
        'name'     => Security::sanitize($_POST['name'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm'  => $_POST['password_confirm'] ?? '',
    ];

    if ($seo->recaptchaOnForm('register')) {
        $token = $_POST['g-recaptcha-response'] ?? '';
        if (!$seo->verifyRecaptcha($token)) {
            $errors[] = 'reCAPTCHA verification failed. Please try again.';
        }
    }

    if ($vals['password'] !== $vals['confirm']) {
        $errors[] = 'Passwords do not match.';
    } elseif (empty($errors)) {
        $result = $auth->register($vals['username'], $vals['email'], $vals['password'], $vals['name']);
        if ($result['success']) {
            $logger = Logger::getInstance($db);
            $logger->auth('register', $result['user_id']);

            // Email verification flow
            if ($settings->get('email_verification_enabled', '0') === '1') {
                $token     = $auth->generateEmailVerificationToken($result['user_id']);
                $verifyUrl = siteUrl('verify-email') . '?token=' . urlencode($token);
                $name      = $vals['name'] ?: $vals['username'];

                $mailer->sendTemplate('verify-email', $vals['email'], [
                    'name'        => $name,
                    'verify_url'  => $verifyUrl,
                    'cta_text'    => 'Verify My Email',
                    'cta_url'     => $verifyUrl,
                    'email_title' => 'Verify your email address',
                ]);

                Session::flash('info', 'Account created! Please check your email to verify your address before signing in.');
                redirect('/verify-email');
            } else {
                // Auto-login
                $auth->attempt($vals['email'], $vals['password']);
                Session::flash('success', 'Welcome! Your account has been created.');
                redirect('/account/dashboard');
            }
        } else {
            if (isset($result['errors'])) {
                $errors = $result['errors'];
            } else {
                $errors[] = $result['error'];
            }
        }
    }
}

ob_start();
?>
<div style="display:flex;align-items:center;justify-content:center;min-height:calc(100vh - 54px);padding:20px 0">
<div style="width:100%;max-width:420px">

    <div class="auth-brand">
        <?php $authLogo = $settings->get('logo_url', ''); if ($authLogo): ?>
        <a href="/" style="display:inline-block;margin-bottom:8px"><img src="<?= e($authLogo) ?>" alt="<?= e($settings->siteName()) ?>" style="height:44px;width:auto;max-width:180px;object-fit:contain"></a>
        <?php else: ?>
        <div class="auth-brand-icon">A</div>
        <?php endif ?>
        <div class="auth-brand-name"><?= e($settings->siteName()) ?></div>
        <div class="auth-brand-tagline">Create your account — free forever</div>
    </div>

    <div class="auth-card">
        <div class="auth-title">Create account</div>
        <div class="auth-subtitle">Get started with <?= e($settings->siteName()) ?></div>

        <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger"><?= e($err) ?></div>
        <?php endforeach ?>

        <?php if ($googleEnabled): ?>
        <a href="/auth/google" class="btn btn-outline w-full" style="display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:16px;border-color:var(--color-border)">
            <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.6 20H24v8h11.3C33.7 33.1 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3 0 5.7 1.1 7.8 2.9l6-6C34.5 6.1 29.6 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20c11 0 20-9 20-20 0-1.3-.1-2.7-.4-4z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.5 15.1 18.9 12 24 12c3 0 5.7 1.1 7.8 2.9l6-6C34.5 6.1 29.6 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-1.9 13.5-5.1l-6.2-5.2C29.3 35.3 26.8 36 24 36c-5.3 0-9.7-2.9-11.3-7L6 34.1C9.4 39.8 16.1 44 24 44z"/><path fill="#1976D2" d="M43.6 20H24v8h11.3c-.7 2.1-2 3.9-3.7 5.2l6.2 5.2C40.7 35.4 44 30.1 44 24c0-1.3-.1-2.7-.4-4z"/></svg>
            Sign up with Google
        </a>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;color:var(--color-text-muted);font-size:12px">
            <div style="flex:1;height:1px;background:var(--color-border)"></div>or<div style="flex:1;height:1px;background:var(--color-border)"></div>
        </div>
        <?php endif ?>

        <form method="POST" action="/register" data-loading
              <?= $seo->recaptchaOnForm('register') && $settings->get('recaptcha_version','2')==='3' ? 'data-recaptcha-action="register"' : '' ?>>
            <?= Security::csrfField() ?>

            <div class="form-group">
                <label class="form-label" for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control"
                       value="<?= e($vals['name'] ?? '') ?>"
                       placeholder="Jane Smith" autocomplete="name">
            </div>

            <div class="grid-2">
                <div class="form-group mb-0">
                    <label class="form-label" for="username">Username <span class="req">*</span></label>
                    <input type="text" id="username" name="username" class="form-control"
                           value="<?= e($vals['username'] ?? '') ?>"
                           placeholder="janedoe" autocomplete="username" required>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label" for="email">Email <span class="req">*</span></label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?= e($vals['email'] ?? '') ?>"
                           placeholder="you@example.com" autocomplete="email" required>
                </div>
            </div>

            <div class="form-group" style="margin-top:16px">
                <label class="form-label" for="password">Password <span class="req">*</span></label>
                <div class="password-wrap">
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Min 8 chars, 1 uppercase, 1 number" autocomplete="new-password" required>
                    <button type="button" data-toggle-password="password" class="password-toggle-btn">Show</button>
                </div>
                <div id="pw-strength-meter"></div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password_confirm">Confirm Password <span class="req">*</span></label>
                <div class="password-wrap">
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control"
                           placeholder="Re-enter password" autocomplete="new-password" required>
                    <button type="button" data-toggle-password="password_confirm" class="password-toggle-btn">Show</button>
                </div>
            </div>

            <?php if ($seo->recaptchaOnForm('register')): ?>
            <?= $seo->recaptchaWidget('register') ?>
            <?php endif ?>

            <button type="submit" class="btn btn-primary w-full" data-loading="Creating account...">
                Create Account
            </button>
        </form>
    </div>

    <div class="auth-footer">
        Already have an account? <a href="/login">Sign in</a>
    </div>
</div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Create Account', $content, ['nav' => false]);
echo '<script src="/assets/js/password-strength.js"></script><script>initPasswordStrength("password","pw-strength-meter");</script>';
