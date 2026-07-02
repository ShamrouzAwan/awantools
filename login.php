<?php
defined('AWAN') or die();
require_once __DIR__ . '/_bootstrap.php';

if ($auth->check()) {
    redirect($auth->isAdmin() ? '/admin/' : '/account/dashboard');
}

$errors = [];
$next   = Security::sanitize($_GET['next'] ?? '/account/dashboard');
$googleEnabled = $settings->get('google_oauth_enabled', '0') === '1'
              && $settings->get('google_client_id', '') !== '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();

    $identifier = Security::sanitize($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if ($seo->recaptchaOnForm('login')) {
        $token = $_POST['g-recaptcha-response'] ?? '';
        if (!$seo->verifyRecaptcha($token)) {
            $errors[] = 'reCAPTCHA verification failed. Please try again.';
        }
    }

    if (empty($identifier) || empty($password)) {
        $errors[] = 'Please enter your email/username and password.';
    } elseif (empty($errors)) {
        $result = $auth->attempt($identifier, $password);

        if ($result['success']) {
            $logger = Logger::getInstance($db);
            $logger->auth('login', $auth->id());
            redirect($next);
        } elseif (!empty($result['needs_otp'])) {
            // Send OTP email
            try {
                $mailer->sendTemplate('email-otp', $result['user_email'], [
                    'name'      => $result['user_name'],
                    'code'      => $result['otp_code'],
                    'site_name' => $settings->siteName(),
                ]);
            } catch (Throwable $e) {}
            Session::set('otp_next', $next);
            redirect('/2fa');
        } elseif (!empty($result['needs_verification'])) {
            Session::flash('warning', $result['error']);
            redirect('/verify-email');
        } else {
            $errors[] = $result['error'];
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
        <div class="auth-brand-tagline"><?= e($settings->siteTagline()) ?></div>
    </div>

    <div class="auth-card">
        <div class="auth-title">Welcome back</div>
        <div class="auth-subtitle">Sign in to your account to continue</div>

        <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger"><?= e($err) ?></div>
        <?php endforeach ?>

        <?php if ($googleEnabled): ?>
        <a href="/auth/google?next=<?= urlencode($next) ?>" class="btn btn-outline w-full" style="display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:16px;border-color:var(--color-border)">
            <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.6 20H24v8h11.3C33.7 33.1 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3 0 5.7 1.1 7.8 2.9l6-6C34.5 6.1 29.6 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20c11 0 20-9 20-20 0-1.3-.1-2.7-.4-4z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.5 15.1 18.9 12 24 12c3 0 5.7 1.1 7.8 2.9l6-6C34.5 6.1 29.6 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-1.9 13.5-5.1l-6.2-5.2C29.3 35.3 26.8 36 24 36c-5.3 0-9.7-2.9-11.3-7L6 34.1C9.4 39.8 16.1 44 24 44z"/><path fill="#1976D2" d="M43.6 20H24v8h11.3c-.7 2.1-2 3.9-3.7 5.2l6.2 5.2C40.7 35.4 44 30.1 44 24c0-1.3-.1-2.7-.4-4z"/></svg>
            Continue with Google
        </a>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;color:var(--color-text-muted);font-size:12px">
            <div style="flex:1;height:1px;background:var(--color-border)"></div>or<div style="flex:1;height:1px;background:var(--color-border)"></div>
        </div>
        <?php endif ?>

        <form method="POST" action="/login?next=<?= urlencode($next) ?>" data-loading
              <?= $seo->recaptchaOnForm('login') && $settings->get('recaptcha_version','2')==='3' ? 'data-recaptcha-action="login"' : '' ?>>
            <?= Security::csrfField() ?>

            <div class="form-group">
                <label class="form-label" for="identifier">Email or Username</label>
                <input type="text" id="identifier" name="identifier" class="form-control"
                       value="<?= e($_POST['identifier'] ?? '') ?>"
                       placeholder="you@example.com" autocomplete="username" autofocus required>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">
                    Password
                    <a href="/forgot-password" style="float:right;font-size:12px;font-weight:400;color:var(--color-primary);text-decoration:none">Forgot password?</a>
                </label>
                <div class="password-wrap">
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="••••••••" autocomplete="current-password" required>
                    <button type="button" data-toggle-password="password" class="password-toggle-btn">Show</button>
                </div>
            </div>

            <?php if ($seo->recaptchaOnForm('login')): ?>
            <?= $seo->recaptchaWidget('login') ?>
            <?php endif ?>

            <button type="submit" class="btn btn-primary w-full" data-loading="Signing in...">
                Sign In
            </button>
        </form>
    </div>

    <?php if ($settings->registrationEnabled()): ?>
    <div class="auth-footer">
        Don't have an account? <a href="/register">Create one</a>
    </div>
    <?php endif ?>
</div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Sign In', $content, ['nav' => false]);
