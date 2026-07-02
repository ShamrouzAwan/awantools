<?php
defined('AWAN') or die();
require_once __DIR__ . '/_bootstrap.php';

if ($auth->check()) redirect('/account/dashboard');

$token   = Security::sanitize($_GET['token'] ?? '');
$errors  = [];

if ($token) {
    $result = $auth->verifyEmailToken($token);
    if ($result['success']) {
        $auth->loginById($result['user_id']);
        $logger = Logger::getInstance($db);
        $logger->auth('email_verified', $result['user_id']);
        Session::flash('success', 'Email verified successfully! Welcome aboard.');
        redirect($auth->isAdmin() ? '/admin/' : '/account/dashboard');
    } else {
        $errors[] = $result['error'];
    }
}

ob_start();
?>
<div style="display:flex;align-items:center;justify-content:center;min-height:calc(100vh - 54px);padding:20px 0">
<div style="width:100%;max-width:420px">

    <div class="auth-brand">
        <div class="auth-brand-icon">A</div>
        <div class="auth-brand-name"><?= e($settings->siteName()) ?></div>
    </div>

    <div class="auth-card">
        <div style="text-align:center;margin-bottom:20px">
            <div style="width:56px;height:56px;background:#ede9fe;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
                <svg width="24" height="24" fill="none" stroke="#6366f1" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>
            <div class="auth-title" style="margin-bottom:6px">Check your email</div>
        </div>

        <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger"><?= e($err) ?></div>
        <?php endforeach ?>

        <?php if (!$token && !$errors): ?>
        <p style="color:var(--color-text-secondary);font-size:14px;text-align:center;line-height:1.6;margin-bottom:20px">
            We sent a verification link to your email. Click it to activate your account.
        </p>
        <p style="color:var(--color-text-muted);font-size:13px;text-align:center;margin-bottom:20px">
            Didn't receive it? Check your spam folder or request a new link below.
        </p>
        <form method="POST" action="/resend-verification" data-loading>
            <?= Security::csrfField() ?>
            <div class="form-group">
                <label class="form-label">Your Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="you@example.com" required autocomplete="email">
            </div>
            <button type="submit" class="btn btn-secondary w-full" data-loading="Sending…">Resend Verification Email</button>
        </form>
        <?php elseif ($errors): ?>
        <p style="color:var(--color-text-secondary);font-size:14px;text-align:center;margin-top:8px">
            Request a new verification link below.
        </p>
        <form method="POST" action="/resend-verification" data-loading style="margin-top:16px">
            <?= Security::csrfField() ?>
            <div class="form-group">
                <label class="form-label">Your Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="you@example.com" required autocomplete="email">
            </div>
            <button type="submit" class="btn btn-secondary w-full" data-loading="Sending…">Request New Link</button>
        </form>
        <?php endif ?>
    </div>

    <div class="auth-footer"><a href="/login">Back to Sign In</a></div>
</div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Verify Email', $content, ['nav' => false]);
