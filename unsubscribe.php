<?php
defined('AWAN') or die();
require_once __DIR__ . '/_bootstrap.php';

$success = false;
$error   = '';
$email   = '';

// Token-based unsubscribe (from email link)
$token = trim($_GET['token'] ?? '');
if ($token !== '') {
    $sub = $db->fetch("SELECT id, email, status FROM newsletter_subscribers WHERE unsubscribe_token = ?", [$token]);
    if ($sub) {
        if ($sub['status'] !== 'unsubscribed') {
            $db->update('newsletter_subscribers', ['status' => 'unsubscribed'], 'id = ?', [$sub['id']]);
        }
        $success = true;
        $email   = $sub['email'];
    } else {
        $error = 'This unsubscribe link is invalid or has already been used.';
    }
}

// Email-based unsubscribe (from form POST)
if (!$success && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $sub = $db->fetch("SELECT id, status FROM newsletter_subscribers WHERE email = ?", [$email]);
        if ($sub) {
            if ($sub['status'] !== 'unsubscribed') {
                $db->update('newsletter_subscribers', ['status' => 'unsubscribed'], 'id = ?', [$sub['id']]);
            }
            $success = true;
        } else {
            // Don't reveal whether email exists — silent success
            $success = true;
        }
    }
}

ob_start();
?>
<div class="page-hero">
    <div class="page-hero-inner">
        <h1>Unsubscribe</h1>
        <p>Remove yourself from our newsletter mailing list.</p>
    </div>
</div>

<div class="front-container" style="padding-top:40px;padding-bottom:60px">
    <div style="max-width:480px;margin:0 auto">

        <?php if ($success): ?>
            <div class="alert alert-success" style="text-align:center;padding:32px">
                <h3 style="margin-bottom:8px">Unsubscribed</h3>
                <p>You have been successfully removed from our mailing list<?= $email ? ' (<strong>' . htmlspecialchars($email) . '</strong>)' : '' ?>.</p>
                <p style="margin-top:16px"><a href="/" class="btn btn-secondary btn-sm">Back to Home</a></p>
            </div>

        <?php elseif ($token !== '' && $error): ?>
            <div class="alert alert-error" style="text-align:center;padding:32px">
                <h3 style="margin-bottom:8px">Invalid Link</h3>
                <p><?= htmlspecialchars($error) ?></p>
                <p style="margin-top:16px"><a href="/unsubscribe" class="btn btn-secondary btn-sm">Unsubscribe by email</a></p>
            </div>

        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-error" style="margin-bottom:20px"><?= htmlspecialchars($error) ?></div>
            <?php endif ?>

            <div class="card" style="padding:32px">
                <h2 style="margin-bottom:8px;font-size:1.1rem">Enter your email address</h2>
                <p style="color:var(--color-text-muted);font-size:14px;margin-bottom:24px">We'll remove you from all future newsletter emails.</p>

                <form method="POST" action="/unsubscribe">
                    <?= Security::csrfField() ?>
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input"
                               placeholder="you@example.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px">Unsubscribe</button>
                </form>
            </div>

            <p style="text-align:center;color:var(--color-text-muted);font-size:13px;margin-top:20px">
                Changed your mind? <a href="/">Go back home</a>
            </p>
        <?php endif ?>

    </div>
</div>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Unsubscribe', $content, [
    'description' => 'Unsubscribe from our newsletter mailing list.',
]);
