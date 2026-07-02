<?php
defined('AWAN') or die();
require_once __DIR__ . '/_bootstrap.php';

if ($auth->check()) {
    redirect('/account/dashboard');
}

$pendingUserId = (int)(Session::get('otp_pending_user_id') ?? 0);
$pendingAt     = (int)(Session::get('otp_pending_at') ?? 0);

if (!$pendingUserId || (time() - $pendingAt) > 900) {
    Session::flash('warning', 'Your login session expired. Please sign in again.');
    redirect('/login');
}

$errors = [];
$resent = false;
$next   = Session::get('otp_next') ?? '/account/dashboard';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = Security::sanitize($_POST['action'] ?? 'verify');

    if ($action === 'resend') {
        $code    = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', time() + 600);
        try {
            $db->query("DELETE FROM user_otp_codes WHERE user_id = ?", [$pendingUserId]);
            $db->insert('user_otp_codes', [
                'user_id'    => $pendingUserId,
                'code'       => $code,
                'expires_at' => $expires,
                'used'       => 0,
            ]);
            $userRow = $db->fetch("SELECT email, name, username FROM users WHERE id = ?", [$pendingUserId]);
            if ($userRow) {
                $mailer->sendTemplate('email-otp', $userRow['email'], [
                    'name'      => $userRow['name'] ?: $userRow['username'],
                    'code'      => $code,
                    'site_name' => $settings->siteName(),
                ]);
            }
        } catch (Throwable $e) {}
        Session::set('otp_pending_at', time());
        $resent = true;
    } else {
        $code = preg_replace('/\D/', '', $_POST['otp_code'] ?? '');
        if (!$code || strlen($code) < 6) {
            $errors[] = 'Please enter the 6-digit code from your email.';
        } elseif (!$auth->verifyOtpCode($pendingUserId, $code)) {
            $errors[] = 'Incorrect or expired code. Please try again or request a new one.';
        } else {
            $auth->completeOtpLogin($pendingUserId);
            $logger = Logger::getInstance($db);
            $logger->auth('login_2fa', $auth->id());
            redirect($next);
        }
    }
}

$userRow     = null;
$maskedEmail = '';
try {
    $userRow = $db->fetch("SELECT email FROM users WHERE id = ?", [$pendingUserId]);
    if ($userRow) {
        $parts = explode('@', $userRow['email']);
        $local = $parts[0] ?? '';
        $maskedEmail = substr($local, 0, 2) . str_repeat('*', max(0, strlen($local) - 2)) . '@' . ($parts[1] ?? '');
    }
} catch (Throwable $e) {}

ob_start();
?>
<div style="display:flex;align-items:center;justify-content:center;min-height:calc(100vh - 54px)">
<div style="width:100%;max-width:380px">

    <div class="auth-brand">
        <?php $authLogo = $settings->get('logo_url', ''); if ($authLogo): ?>
        <a href="/" style="display:inline-block;margin-bottom:8px">
            <img src="<?= e($authLogo) ?>" alt="<?= e($settings->siteName()) ?>" style="height:44px;width:auto;max-width:180px;object-fit:contain">
        </a>
        <?php else: ?>
        <div class="auth-brand-icon">A</div>
        <?php endif ?>
        <div class="auth-brand-name"><?= e($settings->siteName()) ?></div>
    </div>

    <div class="auth-card">
        <div style="width:56px;height:56px;border-radius:var(--radius-full);background:rgba(var(--color-primary-rgb,59,130,246),.1);display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
            <svg width="26" height="26" fill="none" stroke="var(--color-primary)" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 7 10-7"/></svg>
        </div>
        <div class="auth-title">Check your email</div>
        <div class="auth-subtitle">
            <?php if ($maskedEmail): ?>
                We sent a verification code to <strong><?= e($maskedEmail) ?></strong>
            <?php else: ?>
                Enter the 6-digit code sent to your email address.
            <?php endif ?>
        </div>

        <?php if ($resent): ?>
        <div class="alert alert-success">A new code was sent. Check your inbox.</div>
        <?php endif ?>

        <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger"><?= e($err) ?></div>
        <?php endforeach ?>

        <form method="POST" data-loading style="margin-top:20px">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="verify">
            <div class="form-group">
                <label class="form-label" style="text-align:center;display:block">Verification Code</label>
                <input type="text" name="otp_code" class="form-control"
                       inputmode="numeric" pattern="[0-9]*" maxlength="6"
                       placeholder="000000" autocomplete="one-time-code" autofocus
                       style="font-size:28px;letter-spacing:10px;text-align:center;font-weight:700;padding:14px 8px" required>
                <div class="form-hint" style="text-align:center;margin-top:6px">Code expires in 10 minutes.</div>
            </div>
            <button type="submit" class="btn btn-primary w-full" style="margin-top:8px" data-loading="Verifying...">
                Verify &amp; Sign In
            </button>
        </form>

        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--color-border);text-align:center">
            <span style="font-size:13px;color:var(--color-text-muted)">Didn't receive a code?</span>
            <form method="POST" style="display:inline">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="resend">
                <button type="submit" class="btn btn-ghost btn-sm" style="font-size:13px;margin-left:4px" data-loading="Sending...">
                    Resend
                </button>
            </form>
        </div>
        <div style="margin-top:10px;text-align:center">
            <a href="/login" style="font-size:13px;color:var(--color-text-muted);text-decoration:none">
                &larr; Back to Sign In
            </a>
        </div>
    </div>
</div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Verify Your Identity', $content, ['nav' => false]);
