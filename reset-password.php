<?php
defined('AWAN') or die();
require_once __DIR__ . '/_bootstrap.php';

if ($auth->check()) redirect('/account/dashboard');

$token  = Security::sanitize($_GET['token'] ?? '');
$errors = [];
$done   = false;

// Validate token on GET
if (!$token) {
    Session::flash('danger', 'Missing reset token. Please request a new link.');
    redirect('/forgot-password');
}

$tokenRow = $auth->validatePasswordResetToken($token);
if (!$tokenRow) {
    Session::flash('danger', 'This reset link is invalid or has expired. Please request a new one.');
    redirect('/forgot-password');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();

    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    } else {
        $result = $auth->resetPassword($token, $password);
        if ($result['success']) {
            $logger = Logger::getInstance($db);
            $logger->auth('password_reset', $result['user_id']);
            $done = true;
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
<div style="display:flex;align-items:center;justify-content:center;min-height:calc(100vh - 54px)">
<div style="width:100%;max-width:380px">

    <div class="auth-brand">
        <div class="auth-brand-icon">A</div>
        <div class="auth-brand-name"><?= e($settings->siteName()) ?></div>
        <div class="auth-brand-tagline">Choose a new password</div>
    </div>

    <div class="auth-card">
        <?php if ($done): ?>
        <div style="text-align:center">
            <div style="width:56px;height:56px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
                <svg width="24" height="24" fill="none" stroke="#16a34a" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="auth-title" style="margin-bottom:8px">Password updated!</div>
            <p style="color:var(--color-text-secondary);font-size:14px;margin-bottom:20px">
                Your password has been reset successfully. You can now sign in with your new password.
            </p>
            <a href="/login" class="btn btn-primary w-full">Sign In</a>
        </div>
        <?php else: ?>
        <div class="auth-title">Set new password</div>
        <?php if ($tokenRow): ?>
        <div class="auth-subtitle">Resetting password for <strong><?= e($tokenRow['email']) ?></strong></div>
        <?php endif ?>

        <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger" style="margin-top:12px"><?= e($err) ?></div>
        <?php endforeach ?>

        <form method="POST" action="/reset-password?token=<?= urlencode($token) ?>" data-loading style="margin-top:16px">
            <?= Security::csrfField() ?>

            <div class="form-group">
                <label class="form-label" for="password">New Password <span class="req">*</span></label>
                <div class="password-wrap">
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Min 8 chars, 1 uppercase, 1 number"
                           autocomplete="new-password" required autofocus>
                    <button type="button" data-toggle-password="password" class="password-toggle-btn">Show</button>
                </div>
                <div class="form-hint">At least 8 characters, one uppercase letter, one number.</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password_confirm">Confirm New Password <span class="req">*</span></label>
                <div class="password-wrap">
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control"
                           placeholder="Re-enter password"
                           autocomplete="new-password" required>
                    <button type="button" data-toggle-password="password_confirm" class="password-toggle-btn">Show</button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-full" data-loading="Updating…">
                Reset Password
            </button>
        </form>
        <?php endif ?>
    </div>

    <div class="auth-footer">
        <a href="/login">Back to Sign In</a>
    </div>
</div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Reset Password', $content, ['nav' => false]);
