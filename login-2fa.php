<?php
defined('AWAN') or die();
require_once __DIR__ . '/_bootstrap.php';

if ($auth->check()) {
    redirect($auth->isAdmin() ? '/admin/' : '/account/dashboard');
}

$pendingUserId = (int)(Session::get('totp_pending_user_id') ?? 0);
$pendingAt     = (int)(Session::get('totp_pending_at') ?? 0);

if (!$pendingUserId || (time() - $pendingAt) > 600) {
    Session::remove('totp_pending_user_id');
    Session::remove('totp_pending_at');
    Session::flash('warning', 'Session expired. Please sign in again.');
    redirect('/login');
}

$next      = Security::sanitize($_GET['next'] ?? '/account/dashboard');
$errors    = [];
$useBackup = !empty($_GET['backup']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $code     = preg_replace('/\s+/', '', $_POST['code'] ?? '');
    $isBackup = !empty($_POST['use_backup']);
    $totpRow  = $db->fetch("SELECT * FROM user_totp WHERE user_id = ? AND enabled = 1", [$pendingUserId]);

    if (!$totpRow) {
        // TOTP was disabled in the meantime — complete login normally
        $auth->completeTotpLogin($pendingUserId);
        redirect($next);
    }

    $verified = false;
    if ($isBackup) {
        $result = Totp::verifyBackupCode($totpRow['backup_codes'] ?? '[]', $code);
        if ($result['valid']) {
            $db->update('user_totp', ['backup_codes' => json_encode($result['remaining'])], 'user_id = ?', [$pendingUserId]);
            $verified = true;
        }
    } else {
        $verified = Totp::verify($totpRow['secret'], $code);
    }

    if ($verified) {
        $auth->completeTotpLogin($pendingUserId);
        $logger = Logger::getInstance($db);
        $logger->auth('login_2fa', $pendingUserId);
        redirect($next);
    } else {
        $errors[]  = $isBackup
            ? 'Invalid backup code. Check for typos and try again.'
            : 'Invalid code. Codes refresh every 30 seconds — wait and try again.';
        $useBackup = $isBackup;
    }
}

ob_start();
?>
<div style="display:flex;align-items:center;justify-content:center;min-height:calc(100vh - 54px)">
<div style="width:100%;max-width:360px">

    <div class="auth-brand">
        <?php $logo = $settings->get('logo_url',''); if ($logo): ?>
        <a href="/"><img src="<?= e($logo) ?>" alt="<?= e($settings->siteName()) ?>" style="height:44px;width:auto;max-width:180px;object-fit:contain"></a>
        <?php else: ?>
        <div class="auth-brand-icon"><?= strtoupper(substr($settings->siteName(), 0, 1)) ?></div>
        <?php endif ?>
        <div class="auth-brand-name"><?= e($settings->siteName()) ?></div>
        <div class="auth-brand-tagline">Two-Factor Authentication</div>
    </div>

    <div class="auth-card">
        <?php if (!$useBackup): ?>
        <div class="auth-title">Enter Verification Code</div>
        <div class="auth-subtitle">Open your authenticator app and enter the 6-digit code for <strong><?= e($settings->siteName()) ?></strong>.</div>
        <?php else: ?>
        <div class="auth-title">Enter Backup Code</div>
        <div class="auth-subtitle">Enter one of your saved 8-character backup codes. Each code can only be used once.</div>
        <?php endif ?>

        <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger" style="margin-top:16px"><?= e($err) ?></div>
        <?php endforeach ?>

        <form method="POST" action="/login/2fa?next=<?= urlencode($next) ?>" data-loading autocomplete="off" style="margin-top:20px">
            <?= Security::csrfField() ?>
            <?php if ($useBackup): ?><input type="hidden" name="use_backup" value="1"><?php endif ?>

            <div class="form-group">
                <label class="form-label" for="code" style="text-align:center;display:block">
                    <?= $useBackup ? 'Backup Code' : 'Authenticator Code' ?>
                </label>
                <input type="text" id="code" name="code" class="form-control"
                       placeholder="<?= $useBackup ? 'XXXX-XXXX' : '000 000' ?>"
                       inputmode="<?= $useBackup ? 'text' : 'numeric' ?>"
                       autocomplete="one-time-code"
                       maxlength="<?= $useBackup ? '9' : '6' ?>"
                       style="font-size:24px;letter-spacing:5px;text-align:center;font-variant-numeric:tabular-nums"
                       autofocus required>
                <div class="form-hint" style="text-align:center;margin-top:6px">
                    <?= $useBackup ? 'Each backup code can only be used once.' : 'Code refreshes every 30 seconds.' ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-full" style="margin-top:4px" data-loading="Verifying...">
                Verify &amp; Sign In
            </button>
        </form>

        <div style="margin-top:18px;text-align:center;font-size:13px;border-top:1px solid var(--color-border);padding-top:14px">
            <?php if (!$useBackup): ?>
            <a href="/login/2fa?next=<?= urlencode($next) ?>&backup=1" style="color:var(--color-primary);text-decoration:none">
                Lost your phone? Use a backup code
            </a>
            <?php else: ?>
            <a href="/login/2fa?next=<?= urlencode($next) ?>" style="color:var(--color-primary);text-decoration:none">
                Use authenticator app instead
            </a>
            <?php endif ?>
        </div>
        <div style="margin-top:8px;text-align:center;font-size:13px">
            <a href="/login" onclick="<?php ?>return true" style="color:var(--color-text-muted);text-decoration:none">
                &larr; Back to sign in
            </a>
        </div>
    </div>

</div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Two-Factor Authentication', $content, ['nav' => false]);
