<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireLogin();

require_once AWAN_ROOT . '/_core/Totp.php';

$logger = Logger::getInstance($db);
$user   = $auth->user();
$errors = [];
$tab    = Security::sanitize($_GET['tab'] ?? 'info');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = Security::sanitize($_POST['action'] ?? '');

    if ($action === 'update_info') {
        $name = Security::sanitize($_POST['name'] ?? '');
        $bio  = Security::sanitize($_POST['bio'] ?? '');

        $updateData = [
            'name'       => $name,
            'bio'        => $bio,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Avatar upload
        if (!empty($_FILES['avatar']['name'])) {
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $mime = @mime_content_type($_FILES['avatar']['tmp_name']);
            if (in_array($mime, $allowedMimes) && $_FILES['avatar']['size'] <= 2 * 1024 * 1024) {
                $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
                $ext = $extMap[$mime] ?? 'jpg';
                $filename = 'avatar_' . $auth->id() . '_' . time() . '.' . $ext;
                $dir = AWAN_ROOT . '/storage/uploads/';
                if (!is_dir($dir)) @mkdir($dir, 0755, true);
                if (@move_uploaded_file($_FILES['avatar']['tmp_name'], $dir . $filename)) {
                    $updateData['avatar'] = '/storage/uploads/' . $filename;
                } else {
                    Session::flash('warning', 'Avatar upload failed — check directory permissions.');
                }
            } else {
                Session::flash('warning', 'Avatar must be JPEG/PNG/GIF/WebP and under 2 MB.');
            }
        }

        $db->update('users', $updateData, 'id = ?', [$auth->id()]);
        $logger->info("Profile updated", [], $auth->id());
        Session::flash('success', 'Profile updated successfully.');
        redirect('/account/profile');
    }

    if ($action === 'change_email') {
        $newEmail = Security::sanitize(trim($_POST['new_email'] ?? ''));
        $password = $_POST['confirm_password'] ?? '';
        if (!$newEmail || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif ($newEmail === $user['email']) {
            $errors[] = 'New email is the same as your current email.';
        } elseif ($db->exists('users', 'email = ? AND id != ?', [$newEmail, $auth->id()])) {
            $errors[] = 'That email address is already in use by another account.';
        } elseif (!password_verify($password, $user['password'] ?? '')) {
            $errors[] = 'Incorrect password. Please confirm your current password.';
        } else {
            $db->update('users', ['email' => $newEmail, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$auth->id()]);
            $logger->auth('email_change', $auth->id());
            Session::flash('success', 'Email address updated successfully.');
            redirect('/account/profile');
        }
    }

    if ($action === 'change_password') {
        $result = $auth->changePassword(
            $auth->id(),
            $_POST['current_password'] ?? '',
            $_POST['new_password'] ?? ''
        );

        if ($result['success']) {
            $logger->auth('password_change', $auth->id());
            Session::flash('success', 'Password changed successfully.');
            redirect('/account/profile?tab=security');
        } else {
            $errors = isset($result['errors']) ? $result['errors'] : [$result['error']];
        }
    }

    if ($action === 'otp_enable') {
        $db->update('users', ['two_fa_enabled' => 1, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$auth->id()]);
        $logger->info('2FA email OTP enabled', [], $auth->id());
        Session::flash('success', 'Email two-factor authentication has been enabled. You will receive a code by email at each login.');
        redirect('/account/profile?tab=2fa');
    }

    if ($action === 'otp_disable') {
        $db->update('users', ['two_fa_enabled' => 0, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$auth->id()]);
        $logger->info('2FA email OTP disabled', [], $auth->id());
        Session::flash('success', 'Two-factor authentication has been disabled.');
        redirect('/account/profile?tab=2fa');
    }

    // ─── TOTP (authenticator app) actions ─────────────────────────────────────
    if ($action === 'totp_setup_init') {
        $secret = Totp::generateSecret();
        if ($db->exists('user_totp', 'user_id = ?', [$auth->id()])) {
            $db->update('user_totp', ['secret' => $secret, 'enabled' => 0, 'backup_codes' => null], 'user_id = ?', [$auth->id()]);
        } else {
            $db->insert('user_totp', [
                'user_id'    => $auth->id(),
                'secret'     => $secret,
                'enabled'    => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        redirect('/account/profile?tab=2fa');
    }

    if ($action === 'totp_verify_enable') {
        $code = trim(Security::sanitize(str_replace(' ', '', $_POST['totp_code'] ?? '')));
        $rec  = $db->fetch("SELECT * FROM user_totp WHERE user_id = ?", [$auth->id()]);
        if (!$rec) {
            $errors[] = 'Setup session expired. Please start the setup again.';
            $tab = '2fa';
        } elseif (!Totp::verify($rec['secret'], $code)) {
            $errors[] = 'Incorrect code. Make sure your device time is correct and try again.';
            $tab = '2fa';
        } else {
            $backupCodes = Totp::generateBackupCodes();
            $db->update('user_totp', [
                'enabled'      => 1,
                'backup_codes' => json_encode($backupCodes),
            ], 'user_id = ?', [$auth->id()]);
            $logger->info('TOTP authenticator app 2FA enabled', [], $auth->id());
            Session::flash('success', 'Authenticator app 2FA enabled. Save your backup codes somewhere safe.');
            Session::set('totp_new_backup_codes', json_encode($backupCodes));
            redirect('/account/profile?tab=2fa');
        }
    }

    if ($action === 'totp_disable') {
        $db->query("DELETE FROM user_totp WHERE user_id = ?", [$auth->id()]);
        $logger->info('TOTP authenticator app 2FA disabled', [], $auth->id());
        Session::flash('success', 'Authenticator app 2FA has been disabled.');
        redirect('/account/profile?tab=2fa');
    }

    if ($action === 'set_password') {
        $newPw     = $_POST['new_password'] ?? '';
        $confirmPw = $_POST['confirm_password_set'] ?? '';
        if (strlen($newPw) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $newPw) || !preg_match('/[0-9]/', $newPw)) {
            $errors[] = 'Password must contain at least one uppercase letter and one number.';
        } elseif ($newPw !== $confirmPw) {
            $errors[] = 'Passwords do not match.';
        } else {
            $db->update('users', [
                'password'     => Security::hashPassword($newPw),
                'has_password' => 1,
                'updated_at'   => date('Y-m-d H:i:s'),
            ], 'id = ?', [$auth->id()]);
            $logger->auth('password_set', $auth->id());
            Session::flash('success', 'Password set successfully. You can now log in with your email and password.');
            redirect('/account/profile?tab=security');
        }
    }
}

$user             = $db->fetch("SELECT * FROM users WHERE id = ?", [$auth->id()]);
$userTwoFaEnabled = !empty($user['two_fa_enabled']);
$hasPassword      = !empty($user['has_password']) || !empty($user['password']);

// TOTP data
$totpRecord   = null;
$totpEnabled  = false;
$totpPending  = false;
$totpQrUrl    = '';
$totpSecret   = '';
$newBackupCodes = null;
try {
    $totpRecord = $db->fetch("SELECT * FROM user_totp WHERE user_id = ?", [$auth->id()]);
    if ($totpRecord) {
        $totpEnabled = !empty($totpRecord['enabled']);
        $totpPending = !$totpEnabled;
        if ($totpPending) {
            $siteName   = $settings->get('site_name', 'Awan Tools');
            $totpSecret = $totpRecord['secret'];
            $totpQrUrl  = Totp::qrImageUrl($totpRecord['secret'], e($user['email']), e($siteName));
        }
    }
    $newBackupCodesJson = Session::get('totp_new_backup_codes');
    if ($newBackupCodesJson) {
        $newBackupCodes = json_decode($newBackupCodesJson, true);
        Session::remove('totp_new_backup_codes');
    }
} catch (Throwable $e) {}

ob_start();
?>
<style>
.profile-layout { display:grid; grid-template-columns:220px 1fr; gap:24px; align-items:start; }
</style>

<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">Profile Settings</div>
            <div class="page-subtitle">Manage your account information</div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="profile-layout">

        <!-- Sidebar -->
        <div>
            <div class="card" style="position:sticky;top:calc(var(--header-height)+16px)">
                <div class="card-body" style="text-align:center">
                    <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= e($user['avatar']) ?>" alt="Avatar" style="width:60px;height:60px;border-radius:50%;object-fit:cover;margin:0 auto 10px;display:block;border:2px solid var(--color-border)">
                    <?php else: ?>
                    <div style="width:60px;height:60px;border-radius:var(--radius-full);background:var(--color-primary);
                                display:flex;align-items:center;justify-content:center;
                                color:#fff;font-size:20px;font-weight:700;margin:0 auto 10px">
                        <?= strtoupper(substr($user['name'] ?: $user['username'], 0, 2)) ?>
                    </div>
                    <?php endif ?>
                    <div style="font-weight:600"><?= e($user['name'] ?: $user['username']) ?></div>
                    <div class="text-muted text-sm">@<?= e($user['username']) ?></div>
                </div>
                <div class="card-footer">
                    <ul style="list-style:none;display:flex;flex-direction:column;gap:2px">
                        <li><a href="?tab=info" class="btn btn-ghost btn-sm w-full<?= $tab === 'info' ? '" style="background:var(--color-background);justify-content:flex-start' : '" style="justify-content:flex-start' ?>">Personal Info</a></li>
                        <li><a href="?tab=email" class="btn btn-ghost btn-sm w-full<?= $tab === 'email' ? '" style="background:var(--color-background);justify-content:flex-start' : '" style="justify-content:flex-start' ?>">Email Address</a></li>
                        <li><a href="?tab=security" class="btn btn-ghost btn-sm w-full<?= $tab === 'security' ? '" style="background:var(--color-background);justify-content:flex-start' : '" style="justify-content:flex-start' ?>">Security</a></li>
                        <li><a href="?tab=2fa" class="btn btn-ghost btn-sm w-full<?= $tab === '2fa' ? '" style="background:var(--color-background);justify-content:flex-start' : '" style="justify-content:flex-start' ?>">Two-Factor Auth</a></li>
                        <li><a href="?tab=prefs" class="btn btn-ghost btn-sm w-full<?= $tab === 'prefs' ? '" style="background:var(--color-background);justify-content:flex-start' : '" style="justify-content:flex-start' ?>">Preferences</a></li>
                        <li style="border-top:1px solid var(--color-border);margin-top:4px;padding-top:4px">
                            <a href="/account/dashboard" class="btn btn-ghost btn-sm w-full" style="justify-content:flex-start">← Back to Dashboard</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Main -->
        <div>
            <?php foreach ($errors as $e_msg): ?>
            <div class="alert alert-danger"><?= e($e_msg) ?></div>
            <?php endforeach ?>

            <?php if ($tab === 'info'): ?>
            <div class="card">
                <div class="card-header"><span class="card-title">Personal Information</span></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" data-loading>
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="update_info">

                        <div class="form-group">
                            <label class="form-label">Profile Photo</label>
                            <?php if (!empty($user['avatar'])): ?>
                            <div style="margin-bottom:8px">
                                <img src="<?= e($user['avatar']) ?>" alt="Current avatar" style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:1px solid var(--color-border)">
                            </div>
                            <?php endif ?>
                            <input type="file" name="avatar" class="form-input" accept="image/jpeg,image/png,image/gif,image/webp">
                            <div class="form-hint">JPEG, PNG, GIF, or WebP. Max 2 MB.</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-input" value="<?= e($user['name']) ?>" placeholder="Your full name">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-input" value="<?= e($user['username']) ?>" disabled>
                            <div class="form-hint">Username cannot be changed.</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Current Email</label>
                            <input type="email" class="form-input" value="<?= e($user['email']) ?>" disabled>
                            <div class="form-hint">To change your email, use the <a href="?tab=email">Email Address</a> tab.</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Bio</label>
                            <textarea name="bio" class="form-input" rows="3" placeholder="A short bio..."><?= e($user['bio'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary" data-loading="Saving...">Save Changes</button>
                    </form>
                </div>
            </div>

            <?php elseif ($tab === 'email'): ?>
            <div class="card">
                <div class="card-header"><span class="card-title">Change Email Address</span></div>
                <div class="card-body">
                    <p style="color:var(--color-text-secondary);font-size:14px;margin-bottom:20px">
                        Your current email is <strong><?= e($user['email']) ?></strong>. Enter a new email address and confirm your password to update it.
                    </p>
                    <form method="POST" data-loading>
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="change_email">

                        <div class="form-group">
                            <label class="form-label">New Email Address <span class="req">*</span></label>
                            <input type="email" name="new_email" class="form-input" placeholder="new@example.com" required autocomplete="email">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Current Password <span class="req">*</span></label>
                            <div class="password-wrap">
                                <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Confirm your password" required>
                                <button type="button" data-toggle-password="confirm_password" class="password-toggle-btn">Show</button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" data-loading="Updating...">Update Email</button>
                    </form>
                </div>
            </div>

            <?php elseif ($tab === 'security'): ?>
            <?php if ($hasPassword): ?>
            <div class="card">
                <div class="card-header"><span class="card-title">Change Password</span></div>
                <div class="card-body">
                    <form method="POST" data-loading>
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="change_password">
                        <div class="form-group">
                            <label class="form-label">Current Password <span class="req">*</span></label>
                            <div class="password-wrap">
                                <input type="password" id="current_password" name="current_password" class="form-input" placeholder="Your current password" required>
                                <button type="button" data-toggle-password="current_password" class="password-toggle-btn">Show</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">New Password <span class="req">*</span></label>
                            <div class="password-wrap">
                                <input type="password" id="new_password" name="new_password" class="form-input" placeholder="Min 8 chars, 1 uppercase, 1 number" required>
                                <button type="button" data-toggle-password="new_password" class="password-toggle-btn">Show</button>
                            </div>
                            <div class="form-hint">At least 8 characters, one uppercase letter, one number.</div>
                        </div>
                        <button type="submit" class="btn btn-primary" data-loading="Updating...">Update Password</button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Set a Password</span>
                    <span class="badge badge-neutral">Google Account</span>
                </div>
                <div class="card-body">
                    <p style="font-size:14px;color:var(--color-text-secondary);margin-bottom:20px">
                        You signed up with Google and do not have a password yet. Set one to also log in with your email address.
                    </p>
                    <form method="POST" data-loading>
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="set_password">
                        <div class="form-group">
                            <label class="form-label">New Password <span class="req">*</span></label>
                            <div class="password-wrap">
                                <input type="password" id="new_password" name="new_password" class="form-input" placeholder="Min 8 chars, 1 uppercase, 1 number" required>
                                <button type="button" data-toggle-password="new_password" class="password-toggle-btn">Show</button>
                            </div>
                            <div class="form-hint">At least 8 characters, one uppercase letter, one number.</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm Password <span class="req">*</span></label>
                            <input type="password" name="confirm_password_set" class="form-input" placeholder="Re-enter new password" required>
                        </div>
                        <button type="submit" class="btn btn-primary" data-loading="Setting password...">Set Password</button>
                    </form>
                </div>
            </div>
            <?php endif ?>

            <div class="card" style="margin-top:16px">
                <div class="card-header"><span class="card-title">Account Info</span></div>
                <div class="card-body">
                    <div style="display:flex;flex-direction:column;gap:12px">
                        <div>
                            <div class="text-muted text-sm">Last Login</div>
                            <div class="font-medium"><?= $user['last_login_at'] ? fdate($user['last_login_at'], 'M j, Y g:i a') : 'Just now' ?></div>
                        </div>
                        <?php if ($user['last_login_ip']): ?>
                        <div>
                            <div class="text-muted text-sm">Last Login IP</div>
                            <div class="font-medium" style="font-family:monospace"><?= e($user['last_login_ip']) ?></div>
                        </div>
                        <?php endif ?>
                        <div>
                            <div class="text-muted text-sm">Member Since</div>
                            <div class="font-medium"><?= fdate($user['created_at']) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <?php elseif ($tab === '2fa'): ?>

            <?php if (!empty($newBackupCodes)): ?>
            <div class="alert alert-success" style="margin-bottom:16px">
                <strong>Authenticator app 2FA enabled.</strong> Save these backup codes — each can only be used once and will not be shown again.
                <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px">
                    <?php foreach ($newBackupCodes as $bc): ?>
                    <code style="background:rgba(0,0,0,.06);border-radius:4px;padding:3px 8px;font-size:13px;letter-spacing:1px"><?= e($bc) ?></code>
                    <?php endforeach ?>
                </div>
            </div>
            <?php endif ?>

            <!-- Email OTP section -->
            <div class="card" style="margin-bottom:16px">
                <div class="card-header">
                    <span class="card-title">Email One-Time Code</span>
                    <?php if ($userTwoFaEnabled): ?>
                    <span class="badge badge-success">Enabled</span>
                    <?php else: ?>
                    <span style="display:inline-block;padding:2px 8px;border-radius:9999px;font-size:11px;font-weight:600;background:rgba(250,204,21,.15);color:#92400e;border:1px solid rgba(250,204,21,.4)">Not Enabled</span>
                    <?php endif ?>
                </div>
                <div class="card-body">
                    <?php if ($userTwoFaEnabled): ?>
                    <div style="display:flex;align-items:flex-start;gap:16px;padding:16px;background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.25);border-radius:var(--radius-small);margin-bottom:16px">
                        <div style="color:#16a34a;flex-shrink:0;margin-top:2px">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <div>
                            <div style="font-weight:600;font-size:14px;color:var(--color-text);margin-bottom:4px">Email OTP is active</div>
                            <div style="font-size:13px;color:var(--color-text-secondary)">A 6-digit code is sent to <strong><?= e($user['email']) ?></strong> each time you sign in.</div>
                        </div>
                    </div>
                    <form method="POST" data-loading>
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="otp_disable">
                        <button type="submit" class="btn btn-danger btn-sm" data-loading="Disabling..."
                            data-confirm="Disable email OTP? Your account will be less secure.">Disable Email OTP</button>
                    </form>
                    <?php else: ?>
                    <p style="color:var(--color-text-secondary);font-size:14px;margin-bottom:16px">
                        Receive a 6-digit code by email each time you sign in — no app required.
                    </p>
                    <form method="POST" data-loading>
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="otp_enable">
                        <button type="submit" class="btn btn-primary" data-loading="Enabling...">Enable Email 2FA</button>
                    </form>
                    <?php endif ?>
                </div>
            </div>

            <!-- Authenticator App (TOTP) section -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Authenticator App</span>
                    <?php if ($totpEnabled): ?>
                    <span class="badge badge-success">Enabled</span>
                    <?php elseif ($totpPending): ?>
                    <span style="display:inline-block;padding:2px 8px;border-radius:9999px;font-size:11px;font-weight:600;background:rgba(59,130,246,.12);color:#1d4ed8;border:1px solid rgba(59,130,246,.3)">Setup Pending</span>
                    <?php else: ?>
                    <span style="display:inline-block;padding:2px 8px;border-radius:9999px;font-size:11px;font-weight:600;background:rgba(250,204,21,.15);color:#92400e;border:1px solid rgba(250,204,21,.4)">Not Configured</span>
                    <?php endif ?>
                </div>
                <div class="card-body">
                    <?php if ($totpEnabled): ?>
                    <div style="display:flex;align-items:flex-start;gap:16px;padding:16px;background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.25);border-radius:var(--radius-small);margin-bottom:16px">
                        <div style="color:#16a34a;flex-shrink:0;margin-top:2px">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <div>
                            <div style="font-weight:600;font-size:14px;color:var(--color-text);margin-bottom:4px">Authenticator app is active</div>
                            <div style="font-size:13px;color:var(--color-text-secondary)">You will be asked for a 6-digit code from your app when signing in.</div>
                        </div>
                    </div>
                    <form method="POST" data-loading>
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="totp_disable">
                        <button type="submit" class="btn btn-danger btn-sm" data-loading="Removing..."
                            data-confirm="Remove authenticator app 2FA? Your account will be less secure.">Remove Authenticator App</button>
                    </form>

                    <?php elseif ($totpPending): ?>
                    <p style="font-size:14px;color:var(--color-text-secondary);margin-bottom:20px">
                        Scan the QR code below with your authenticator app (Google Authenticator, Authy, etc.), then enter the 6-digit code to confirm setup.
                    </p>
                    <div style="display:flex;gap:32px;flex-wrap:wrap;align-items:flex-start;margin-bottom:20px">
                        <div>
                            <img src="<?= e($totpQrUrl) ?>" alt="TOTP QR Code" width="180" height="180"
                                 style="border:4px solid #fff;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,.12);display:block">
                        </div>
                        <div style="flex:1;min-width:200px">
                            <div style="font-size:12px;font-weight:600;color:var(--color-text-secondary);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Manual entry key</div>
                            <code style="display:block;background:var(--color-background);border:1px solid var(--color-border);border-radius:6px;padding:10px 14px;font-size:14px;letter-spacing:2px;word-break:break-all;margin-bottom:16px"><?= e(implode(' ', str_split($totpSecret, 4))) ?></code>
                            <div style="font-size:12px;color:var(--color-text-secondary)">Enter this key manually in your app if the QR code is not readable.</div>
                        </div>
                    </div>
                    <form method="POST" data-loading style="max-width:320px">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="totp_verify_enable">
                        <div class="form-group" style="margin-bottom:12px">
                            <label class="form-label">Confirmation Code</label>
                            <input type="text" name="totp_code" class="form-input" inputmode="numeric" pattern="[0-9 ]*"
                                   maxlength="7" autocomplete="one-time-code" placeholder="000000" required
                                   style="font-family:monospace;letter-spacing:3px;font-size:18px;text-align:center">
                            <div class="form-hint">Enter the 6-digit code shown in your app.</div>
                        </div>
                        <div style="display:flex;gap:8px">
                            <button type="submit" class="btn btn-primary" data-loading="Verifying...">Verify and Enable</button>
                            <form method="POST" style="display:inline">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="action" value="totp_disable">
                                <button type="submit" class="btn btn-ghost btn-sm">Cancel</button>
                            </form>
                        </div>
                    </form>

                    <?php else: ?>
                    <p style="color:var(--color-text-secondary);font-size:14px;margin-bottom:20px">
                        Use an app like Google Authenticator, Authy, or 1Password to generate login codes. More secure than email OTP — works even without an internet connection.
                    </p>
                    <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px">
                        <?php foreach ([
                            ['icon' => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12" y2="18"/></svg>', 'text' => 'Works offline — codes generated on your device'],
                            ['icon' => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>', 'text' => 'Codes refresh every 30 seconds'],
                            ['icon' => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>', 'text' => 'Stronger protection against phishing and account takeover'],
                        ] as $step): ?>
                        <div style="display:flex;align-items:flex-start;gap:12px">
                            <div style="width:32px;height:32px;border-radius:var(--radius-small);background:var(--color-background);display:flex;align-items:center;justify-content:center;color:var(--color-primary);flex-shrink:0">
                                <?= $step['icon'] ?>
                            </div>
                            <div style="font-size:13px;color:var(--color-text-secondary);padding-top:6px"><?= $step['text'] ?></div>
                        </div>
                        <?php endforeach ?>
                    </div>
                    <form method="POST" data-loading>
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="totp_setup_init">
                        <button type="submit" class="btn btn-secondary" data-loading="Generating QR code...">Set Up Authenticator App</button>
                    </form>
                    <?php endif ?>
                </div>
            </div>

            <?php elseif ($tab === 'prefs'): ?>
            <?php
            $userPrefs = $db->fetch("SELECT * FROM user_preferences WHERE user_id = ?", [$user['id']]);
            $prefTheme  = $userPrefs['theme'] ?? 'system';
            $prefItems  = $userPrefs['items_per_page'] ?? 25;
            $prefNotifs = $userPrefs['email_notifications'] ?? 1;
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::sanitize($_POST['action'] ?? '') === 'save_prefs') {
                Security::verifyCsrf();
                $newTheme  = in_array($_POST['pref_theme'] ?? '', ['light','dark','system']) ? $_POST['pref_theme'] : 'system';
                $newItems  = max(5, min(200, (int)($_POST['pref_items'] ?? 25)));
                $newNotifs = !empty($_POST['pref_email_notifs']) ? 1 : 0;
                if ($userPrefs) {
                    $db->update('user_preferences', ['theme' => $newTheme, 'items_per_page' => $newItems, 'email_notifications' => $newNotifs], 'user_id = ?', [$user['id']]);
                } else {
                    $db->insert('user_preferences', ['user_id' => $user['id'], 'theme' => $newTheme, 'items_per_page' => $newItems, 'email_notifications' => $newNotifs]);
                }
                Session::flash('success', 'Preferences saved.');
                redirect('/account/profile?tab=prefs');
            }
            ?>
            <div class="card">
                <div class="card-header"><span class="card-title">Display Preferences</span></div>
                <div class="card-body">
                    <form method="POST" data-loading>
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="save_prefs">
                        <div class="form-group">
                            <label class="form-label">Theme</label>
                            <select name="pref_theme" class="form-input" style="max-width:220px">
                                <option value="system" <?= $prefTheme === 'system' ? 'selected' : '' ?>>System Default</option>
                                <option value="light"  <?= $prefTheme === 'light'  ? 'selected' : '' ?>>Light</option>
                                <option value="dark"   <?= $prefTheme === 'dark'   ? 'selected' : '' ?>>Dark</option>
                            </select>
                            <div class="form-hint">Overrides the browser-level dark mode preference on this site.</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Items per Page</label>
                            <input type="number" name="pref_items" class="form-input" value="<?= (int)$prefItems ?>"
                                   min="5" max="200" style="max-width:120px">
                            <div class="form-hint">How many items to show per page in lists (5–200).</div>
                        </div>
                        <div class="form-group mb-0">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                                <input type="checkbox" name="pref_email_notifs" value="1" <?= $prefNotifs ? 'checked' : '' ?>>
                                <span class="form-label" style="margin:0">Email Notifications</span>
                            </label>
                            <div class="form-hint">Receive email notifications for account activity and replies.</div>
                        </div>
                        <div style="margin-top:20px">
                            <button type="submit" class="btn btn-primary" data-loading="Saving...">Save Preferences</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php endif ?>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Profile', $content);
