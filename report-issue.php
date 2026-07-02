<?php
defined('AWAN') or die();
require_once __DIR__ . '/_bootstrap.php';

// Handle POST (from modal or direct form)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();

    $pluginSlug = trim(Security::sanitize($_POST['plugin_slug'] ?? ''));
    $name       = trim(Security::sanitize($_POST['reporter_name'] ?? ''));
    $email      = trim(Security::sanitize($_POST['reporter_email'] ?? ''));
    $desc       = trim(Security::sanitize($_POST['description'] ?? ''));
    $browser    = substr(trim($_POST['browser'] ?? ''), 0, 255);
    $url        = substr(trim(Security::sanitize($_POST['url'] ?? '')), 0, 500);

    $errors = [];
    if (strlen($name) < 2) $errors[] = 'Name required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (strlen($desc) < 10) $errors[] = 'Please describe the issue.';

    if (empty($errors)) {
        // Handle screenshot upload
        $screenshotPath = null;
        if (!empty($_FILES['screenshot']['tmp_name'])) {
            $upload = $_FILES['screenshot'];
            $ext    = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp']) && $upload['size'] < 5 * 1024 * 1024) {
                $dir  = UPLOADS_PATH . '/reports/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $fname = 'report_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($upload['tmp_name'], $dir . $fname)) {
                    $screenshotPath = '/storage/uploads/reports/' . $fname;
                }
            }
        }

        try {
            $db->insert('issue_reports', [
                'plugin_slug'    => $pluginSlug ?: null,
                'reporter_name'  => $name,
                'reporter_email' => $email,
                'description'    => $desc,
                'screenshot_path'=> $screenshotPath,
                'browser'        => $browser ?: null,
                'url'            => $url ?: null,
                'status'         => 'open',
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
            $logger->warn("Issue reported on " . ($pluginSlug ?: 'platform') . " by {$email}");

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Report submitted successfully.']);
                exit;
            }
            Session::flash('success', 'Thank you for reporting! Your issue has been logged and will be reviewed.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/');
        } catch (Exception $e) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to submit report.']);
                exit;
            }
            Session::flash('danger', 'Failed to submit report. Please try again.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
    } else {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
            exit;
        }
        Session::flash('danger', implode(' ', $errors));
        redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }
}

// GET — standalone report form
ob_start();
?>
<div class="page-hero">
    <div class="page-hero-inner">
        <h1>Report an Issue</h1>
        <p>Encountered a bug or problem? Let me know and I'll fix it as soon as possible.</p>
    </div>
</div>
<div class="front-container" style="padding-top:48px;padding-bottom:72px;max-width:640px">
    <div class="contact-form-card">
        <form method="POST" enctype="multipart/form-data" data-loading>
            <?= Security::csrfField() ?>
            <input type="hidden" name="plugin_slug" value="<?= e($_GET['plugin'] ?? '') ?>">
            <input type="hidden" name="url" value="<?= e($_SERVER['HTTP_REFERER'] ?? '') ?>">
            <input type="hidden" name="browser" id="report-browser-page">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Your Name <span class="req">*</span></label>
                    <input type="text" name="reporter_name" class="form-input" required placeholder="John Doe">
                </div>
                <div class="form-group">
                    <label class="form-label">Email <span class="req">*</span></label>
                    <input type="email" name="reporter_email" class="form-input" required placeholder="you@example.com">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Describe the Issue <span class="req">*</span></label>
                <textarea name="description" class="form-input" rows="5" required placeholder="What went wrong? What were you trying to do? What did you expect to happen?"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Screenshot (optional)</label>
                <input type="file" name="screenshot" class="form-input" accept="image/*">
            </div>
            <button type="submit" class="btn btn-danger btn-lg w-full" data-loading="Submitting…">Submit Report</button>
        </form>
    </div>
</div>
<script>var b=document.getElementById('report-browser-page');if(b)b.value=navigator.userAgent;</script>
<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Report an Issue', $content, ['description' => 'Found a bug? Report an issue and help improve AWAN Platform.']);
