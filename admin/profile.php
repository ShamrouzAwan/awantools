<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';
requireLogin();

$logger = Logger::getInstance($db);
$user   = $db->fetch('SELECT * FROM users WHERE id = ?', [$auth->id()]);

if (!$user) redirect('/login');

$errors = [];

// ─── POST ─────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $action = $_POST['action'] ?? 'profile';

    if ($action === 'profile') {
        $name  = trim(Security::sanitize($_POST['name'] ?? ''));
        $bio   = trim(Security::sanitize($_POST['bio'] ?? ''));
        $email = trim(strtolower($_POST['email'] ?? ''));

        if (empty($name))   $errors[] = 'Display name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';

        // Check email uniqueness
        $existing = $db->fetch('SELECT id FROM users WHERE email = ? AND id != ?', [$email, $auth->id()]);
        if ($existing) $errors[] = 'That email is already in use by another account.';

        if (empty($errors)) {
            // Handle avatar upload
            $avatarUrl = $user['avatar'] ?? null;
            if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $finfo    = new finfo(FILEINFO_MIME_TYPE);
                $mime     = $finfo->file($_FILES['avatar']['tmp_name']);
                $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($mime, $allowed)) {
                    $errors[] = 'Avatar must be JPG, PNG, GIF, or WEBP.';
                } elseif ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
                    $errors[] = 'Avatar must be under 2MB.';
                } else {
                    $exts     = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
                    $ext      = $exts[$mime];
                    $filename = 'avatar_' . $auth->id() . '_' . time() . '.' . $ext;
                    $destPath = UPLOADS_PATH . '/' . $filename;
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destPath)) {
                        $avatarUrl = '/storage/uploads/' . $filename;
                        // Also add to media library
                        $info = @getimagesize($destPath);
                        $db->insert('media', [
                            'filename'      => $filename,
                            'original_name' => 'avatar.' . $ext,
                            'file_path'     => $destPath,
                            'url_path'      => $avatarUrl,
                            'mime_type'     => $mime,
                            'file_type'     => 'image',
                            'file_size'     => $_FILES['avatar']['size'],
                            'width'         => $info[0] ?? null,
                            'height'        => $info[1] ?? null,
                            'folder'        => 'avatars',
                            'uploader_id'   => $auth->id(),
                            'created_at'    => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }

            if (empty($errors)) {
                $db->update('users', [
                    'name'       => $name,
                    'bio'        => $bio,
                    'email'      => $email,
                    'avatar'     => $avatarUrl,
                    'updated_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$auth->id()]);
                $logger->info('Profile updated', [], $auth->id());
                Session::flash('success', 'Profile saved successfully.');
                redirect('/admin/profile');
            }
        }
        // Re-fetch user to show current state in form
        $user = $db->fetch('SELECT * FROM users WHERE id = ?', [$auth->id()]);
    }

    if ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        }

        if (empty($errors)) {
            $db->update('users', ['password' => password_hash($new, PASSWORD_DEFAULT)], 'id = ?', [$auth->id()]);
            $logger->info('Password changed via profile', [], $auth->id());
            Session::flash('success', 'Password changed successfully.');
            redirect('/admin/profile');
        }
    }

    // ─── TOTP actions ─────────────────────────────────────────────────────────
    if ($action === 'totp_setup') {
        $secret = Totp::generateSecret();
        Session::set('totp_setup_secret', $secret);
        redirect('/admin/profile?section=2fa');
    }

    if ($action === 'totp_cancel') {
        Session::remove('totp_setup_secret');
        redirect('/admin/profile?section=2fa');
    }

    if ($action === 'totp_enable') {
        $pendingSecret = Session::get('totp_setup_secret');
        $code = preg_replace('/\s+/', '', $_POST['totp_code'] ?? '');
        if ($pendingSecret && Totp::verify($pendingSecret, $code)) {
            $backupCodes = Totp::generateBackupCodes(8);
            $existing    = $db->fetch("SELECT id FROM user_totp WHERE user_id = ?", [$auth->id()]);
            if ($existing) {
                $db->update('user_totp', [
                    'secret'       => $pendingSecret,
                    'enabled'      => 1,
                    'backup_codes' => json_encode($backupCodes),
                ], 'user_id = ?', [$auth->id()]);
            } else {
                $db->insert('user_totp', [
                    'user_id'      => $auth->id(),
                    'secret'       => $pendingSecret,
                    'enabled'      => 1,
                    'backup_codes' => json_encode($backupCodes),
                    'created_at'   => date('Y-m-d H:i:s'),
                ]);
            }
            Session::remove('totp_setup_secret');
            Session::set('totp_new_backup_codes', $backupCodes);
            $logger->info('TOTP 2FA enabled', [], $auth->id());
            Session::flash('success', '2FA enabled. Save your backup codes — they will only be shown once.');
            redirect('/admin/profile?section=2fa');
        } else {
            $errors[] = 'Invalid code. Please scan the QR again and try a fresh 6-digit code.';
        }
    }

    if ($action === 'totp_disable') {
        $code    = preg_replace('/\s+/', '', $_POST['totp_code'] ?? '');
        $totpRow = $db->fetch("SELECT * FROM user_totp WHERE user_id = ? AND enabled = 1", [$auth->id()]);
        if ($totpRow && Totp::verify($totpRow['secret'], $code)) {
            $db->update('user_totp', ['enabled' => 0], 'user_id = ?', [$auth->id()]);
            $logger->info('TOTP 2FA disabled', [], $auth->id());
            Session::flash('success', 'Two-factor authentication disabled.');
            redirect('/admin/profile?section=2fa');
        } else {
            $errors[] = 'Invalid code. 2FA was not disabled.';
        }
    }
}

// ─── Roles ────────────────────────────────────────────────────────────────────
$userRoles = $db->fetchAll(
    "SELECT r.name FROM roles r JOIN user_roles ur ON ur.role_id = r.id WHERE ur.user_id = ?",
    [$auth->id()]
);
$roleNames = implode(', ', array_column($userRoles, 'name')) ?: 'No roles';

// ─── Activity stats ───────────────────────────────────────────────────────────
$loginCount  = $db->count('logs', "user_id = ? AND message LIKE '%login%'", [$auth->id()]);
$pageEdits   = $db->count('pages', 'author_id = ?', [$auth->id()]);

// ─── TOTP data ────────────────────────────────────────────────────────────────
$totpRecord        = null;
$totpEnabled       = false;
$totpPendingSecret = null;
$totpNewCodes      = null;
try {
    $totpRecord  = $db->fetch("SELECT * FROM user_totp WHERE user_id = ?", [$auth->id()]);
    $totpEnabled = $totpRecord && (int)($totpRecord['enabled'] ?? 0) === 1;
} catch (Throwable $e) {}
$totpPendingSecret = Session::get('totp_setup_secret');
$totpNewCodes      = Session::get('totp_new_backup_codes');
if ($totpNewCodes) Session::remove('totp_new_backup_codes');
$profileSection = Security::sanitize($_GET['section'] ?? '');

// ─── View ─────────────────────────────────────────────────────────────────────
ob_start();
?>
<div class="page-header">
    <div class="page-header-left">
        <div>
            <div class="page-title">My Profile</div>
            <div class="page-subtitle">Manage your account details and password</div>
        </div>
    </div>
    <div class="topbar-actions">
        <a href="/user/<?= e($user['username']) ?>" target="_blank" class="btn btn-ghost btn-sm">View Public Profile →</a>
    </div>
</div>

<div class="page-body">
<?php foreach ($errors as $err): ?>
<div class="alert alert-danger" style="margin-bottom:12px"><?= e($err) ?></div>
<?php endforeach ?>

<div class="grid-2" style="gap:20px;align-items:start">

    <!-- Profile form -->
    <div>
        <form method="POST" enctype="multipart/form-data">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="profile">

            <div class="card" style="margin-bottom:16px">
                <div class="card-header"><span class="card-title">Profile Information</span></div>
                <div class="card-body">

                    <!-- Avatar preview + upload -->
                    <div class="form-group" style="display:flex;gap:16px;align-items:flex-end">
                        <div>
                            <?php if (!empty($user['avatar'])): ?>
                            <img src="<?= e($user['avatar']) ?>" alt="Avatar"
                                 style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:2px solid var(--color-border)">
                            <?php else: ?>
                            <div style="width:72px;height:72px;border-radius:50%;background:var(--color-primary);display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:700;color:#fff">
                                <?= strtoupper(substr($user['name'] ?: $user['username'], 0, 1)) ?>
                            </div>
                            <?php endif ?>
                        </div>
                        <div style="flex:1">
                            <label class="form-label">Profile Photo</label>
                            <input type="file" name="avatar" class="form-input" accept="image/jpeg,image/png,image/gif,image/webp">
                            <div class="form-hint">JPG, PNG, WEBP · Max 2MB</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Display Name <span class="req">*</span></label>
                        <input type="text" name="name" class="form-input" value="<?= e($user['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address <span class="req">*</span></label>
                        <input type="email" name="email" class="form-input" value="<?= e($user['email']) ?>" required>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Bio</label>
                        <textarea name="bio" class="form-input" rows="4" placeholder="Tell us a bit about yourself…"><?= e($user['bio'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Save Profile</button>
                </div>
            </div>
        </form>

        <!-- Password change -->
        <form method="POST">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="password">
            <div class="card">
                <div class="card-header"><span class="card-title">Change Password</span></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <div class="password-wrap">
                            <input type="password" id="adm_current_password" name="current_password" class="form-input" required autocomplete="current-password">
                            <button type="button" data-toggle-password="adm_current_password" class="password-toggle-btn">Show</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <div class="password-wrap">
                            <input type="password" id="adm_new_password" name="new_password" class="form-input" required minlength="8" autocomplete="new-password">
                            <button type="button" data-toggle-password="adm_new_password" class="password-toggle-btn">Show</button>
                        </div>
                        <div class="form-hint">Minimum 8 characters</div>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Confirm New Password</label>
                        <div class="password-wrap">
                            <input type="password" id="adm_confirm_password" name="confirm_password" class="form-input" required autocomplete="new-password">
                            <button type="button" data-toggle-password="adm_confirm_password" class="password-toggle-btn">Show</button>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-secondary">Change Password</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Account info card -->
    <div>
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><span class="card-title">Account Info</span></div>
            <div class="card-body">
                <div style="display:flex;flex-direction:column;gap:12px">
                    <div>
                        <div class="form-label" style="margin-bottom:2px">Username</div>
                        <div style="font-weight:600">@<?= e($user['username']) ?></div>
                    </div>
                    <div>
                        <div class="form-label" style="margin-bottom:2px">Role(s)</div>
                        <div style="font-weight:600"><?= e($roleNames) ?></div>
                    </div>
                    <div>
                        <div class="form-label" style="margin-bottom:2px">Account Status</div>
                        <span class="badge badge-<?= $user['status'] === 'active' ? 'success' : 'danger' ?>"><?= e($user['status']) ?></span>
                    </div>
                    <div>
                        <div class="form-label" style="margin-bottom:2px">Member Since</div>
                        <div><?= fdate($user['created_at']) ?></div>
                    </div>
                    <?php if ($user['last_login_at']): ?>
                    <div>
                        <div class="form-label" style="margin-bottom:2px">Last Login</div>
                        <div><?= fdate($user['last_login_at'], 'M j, Y g:i A') ?></div>
                    </div>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><span class="card-title">Activity</span></div>
            <div class="card-body">
                <div class="stats-grid" style="grid-template-columns:1fr 1fr;gap:12px">
                    <div class="stat-card" style="padding:16px">
                        <div style="font-size:24px;font-weight:800"><?= $pageEdits ?></div>
                        <div style="font-size:11px;color:var(--color-text-muted)">Pages Authored</div>
                    </div>
                    <div class="stat-card" style="padding:16px">
                        <div style="font-size:24px;font-weight:800"><?= $loginCount ?></div>
                        <div style="font-size:11px;color:var(--color-text-muted)">Login Events</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Two-Factor Authentication -->
        <div class="card" style="margin-top:16px" id="section-2fa">
            <div class="card-header">
                <span class="card-title">Two-Factor Authentication</span>
                <?php if ($totpEnabled): ?>
                <span class="badge badge-success" style="font-size:11px">Enabled</span>
                <?php else: ?>
                <span class="badge badge-neutral" style="font-size:11px">Disabled</span>
                <?php endif ?>
            </div>
            <div class="card-body">

                <?php if ($totpNewCodes): ?>
                <!-- Show backup codes after enabling -->
                <div class="alert alert-success" style="margin-bottom:16px">
                    2FA is now active. Store these backup codes somewhere safe — they are shown <strong>once only</strong>:
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:16px;font-family:monospace;font-size:14px">
                    <?php foreach ($totpNewCodes as $code): ?>
                    <div style="padding:6px 10px;background:var(--color-background);border:1px solid var(--color-border);border-radius:var(--radius-medium);text-align:center;font-weight:600"><?= e($code) ?></div>
                    <?php endforeach ?>
                </div>
                <a href="/admin/profile?section=2fa" class="btn btn-ghost btn-sm">Done — Hide Codes</a>

                <?php elseif ($totpPendingSecret): ?>
                <!-- QR code setup step -->
                <p style="font-size:13px;color:var(--color-text-secondary);margin-bottom:16px">
                    Scan this QR code with your authenticator app (<strong>Google Authenticator</strong>, <strong>Authy</strong>, <strong>1Password</strong>, etc.), then enter the 6-digit code to confirm.
                </p>
                <div style="text-align:center;margin-bottom:16px">
                    <img src="<?= Totp::qrImageUrl($totpPendingSecret, $user['email'], $settings->siteName()) ?>"
                         alt="TOTP QR Code"
                         style="width:160px;height:160px;border:1px solid var(--color-border);border-radius:var(--radius-medium)">
                    <div style="margin-top:8px;font-size:11px;color:var(--color-text-muted)">
                        Can't scan? Manual key: <code style="user-select:all"><?= e($totpPendingSecret) ?></code>
                    </div>
                </div>
                <form method="POST" data-loading>
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="totp_enable">
                    <div class="form-group">
                        <label class="form-label">Verification Code</label>
                        <input type="text" name="totp_code" class="form-input" placeholder="000000"
                               inputmode="numeric" maxlength="6" autocomplete="one-time-code"
                               style="font-size:20px;letter-spacing:4px;text-align:center;max-width:160px"
                               autofocus required>
                        <div class="form-hint">Enter the 6-digit code from your app to confirm setup.</div>
                    </div>
                    <div style="display:flex;gap:8px">
                        <button type="submit" class="btn btn-primary" data-loading="Activating...">Activate 2FA</button>
                        <button type="button" class="btn btn-ghost" onclick="document.getElementById('totp-cancel-form').submit()">Cancel</button>
                    </div>
                </form>
                <form id="totp-cancel-form" method="POST" style="display:none">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="totp_cancel">
                </form>

                <?php elseif ($totpEnabled): ?>
                <!-- Disable 2FA -->
                <p style="font-size:13px;color:var(--color-text-secondary);margin-bottom:16px">
                    2FA is protecting your account. To disable it, enter your current authenticator code.
                </p>
                <?php $remaining = count(array_filter(json_decode($totpRecord['backup_codes'] ?? '[]', true) ?: [], fn($c) => $c !== null)); ?>
                <?php if ($remaining < 3): ?>
                <div class="alert alert-warning" style="margin-bottom:12px;font-size:13px">
                    Only <?= $remaining ?> backup code<?= $remaining !== 1 ? 's' : '' ?> remaining. Generate new ones by disabling and re-enabling 2FA.
                </div>
                <?php endif ?>
                <form method="POST" data-loading>
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="totp_disable">
                    <div class="form-group">
                        <label class="form-label">Current Authenticator Code</label>
                        <input type="text" name="totp_code" class="form-input" placeholder="000000"
                               inputmode="numeric" maxlength="6" autocomplete="one-time-code"
                               style="font-size:20px;letter-spacing:4px;text-align:center;max-width:160px" required>
                    </div>
                    <button type="submit" class="btn btn-danger" data-loading="Disabling..."
                            onclick="return confirm('Disable two-factor authentication? Your account will be less secure.')">
                        Disable 2FA
                    </button>
                </form>

                <?php else: ?>
                <!-- Enable 2FA prompt -->
                <p style="font-size:13px;color:var(--color-text-secondary);margin-bottom:16px">
                    Add an extra layer of security. When enabled, you will be asked for a code from your authenticator app every time you sign in.
                </p>
                <form method="POST">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="totp_setup">
                    <button type="submit" class="btn btn-primary">Enable Two-Factor Authentication</button>
                </form>
                <?php endif ?>

            </div>
        </div>
    </div>
</div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/admin.php';
render_admin('My Profile', $content, ['section' => 'profile']);
