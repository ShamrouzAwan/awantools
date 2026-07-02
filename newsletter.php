<?php
defined('AWAN') or define('AWAN', true);
require_once __DIR__ . '/_bootstrap.php';

// Analytics
if ($settings->get('analytics_enabled', '1') === '1' && !isBot()) {
    try { $db->insert('analytics_events', ['event' => 'page_view', 'path' => '/newsletter', 'user_id' => $auth->id(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? null, 'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255), 'created_at' => date('Y-m-d H:i:s')]); } catch (Exception $e) {}
}

ob_start();
?>
<div class="page-hero">
    <div class="page-hero-inner">
        <h1>Newsletter</h1>
        <p>Get the latest tools, updates, and tutorials delivered straight to your inbox. No spam, ever.</p>
    </div>
</div>

<div class="front-section">
    <div class="front-container" style="max-width:560px">
        <div class="card">
            <div class="card-body" style="padding:40px">
                <div id="nl-form-wrap">
                    <h2 style="font-size:22px;font-weight:700;margin-bottom:8px">Stay in the Loop</h2>
                    <p style="color:var(--color-text-secondary);font-size:15px;margin-bottom:28px">Join readers who get notified about new tools and updates from Awan Tools.</p>

                    <div id="nl-alert" style="display:none;margin-bottom:16px"></div>

                    <form id="nl-form">
                        <div class="form-group">
                            <label class="form-label">Your Name <span style="color:var(--color-text-muted);font-size:12px">(optional)</span></label>
                            <input type="text" id="nl-name" class="form-input" placeholder="Jane Smith" autocomplete="name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address <span class="req">*</span></label>
                            <input type="email" id="nl-email" class="form-input" placeholder="you@example.com" required autocomplete="email">
                        </div>
                        <button type="submit" id="nl-btn" class="btn btn-primary w-full">Subscribe — It's Free</button>
                    </form>

                    <p style="font-size:12px;color:var(--color-text-muted);margin-top:16px;text-align:center">
                        You can unsubscribe at any time via the link in any email, or visit <a href="/unsubscribe">our unsubscribe page</a>.
                    </p>
                </div>

                <div id="nl-success" style="display:none;text-align:center;padding:20px 0">
                    <div style="width:56px;height:56px;background:var(--color-success-light,#d1fae5);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
                        <svg width="24" height="24" fill="none" stroke="#22c55e" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <h3 style="font-size:20px;font-weight:700;margin-bottom:8px">You're subscribed!</h3>
                    <p style="color:var(--color-text-secondary)" id="nl-success-msg">Thanks for joining. You'll hear from us soon.</p>
                    <a href="/" class="btn btn-secondary btn-sm" style="margin-top:16px">Back to Home</a>
                </div>
            </div>
        </div>

        <!-- What you'll get -->
        <div style="margin-top:32px;display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div class="card" style="padding:20px">
                <div style="font-size:20px;margin-bottom:8px">
                    <svg width="20" height="20" fill="none" stroke="var(--color-primary)" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                </div>
                <div style="font-size:13px;font-weight:600;margin-bottom:4px">New Tools</div>
                <div style="font-size:12px;color:var(--color-text-secondary)">Be first to know when new tools launch.</div>
            </div>
            <div class="card" style="padding:20px">
                <div style="font-size:20px;margin-bottom:8px">
                    <svg width="20" height="20" fill="none" stroke="var(--color-primary)" stroke-width="2" viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                </div>
                <div style="font-size:13px;font-weight:600;margin-bottom:4px">Tutorials</div>
                <div style="font-size:12px;color:var(--color-text-secondary)">Tips and guides for getting more done.</div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('nl-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var email = document.getElementById('nl-email').value.trim();
    var name  = document.getElementById('nl-name').value.trim();
    var btn   = document.getElementById('nl-btn');
    var alert = document.getElementById('nl-alert');

    if (!email) return;

    btn.disabled = true;
    btn.textContent = 'Subscribing…';
    alert.style.display = 'none';

    fetch('/api/v1/newsletter', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email, name: name })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            document.getElementById('nl-form-wrap').style.display = 'none';
            document.getElementById('nl-success').style.display = 'block';
            var msg = document.getElementById('nl-success-msg');
            if (data.message) msg.textContent = data.message;
        } else {
            alert.className = 'alert alert-danger';
            alert.textContent = data.error || 'Something went wrong. Please try again.';
            alert.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Subscribe — It\'s Free';
        }
    })
    .catch(function() {
        alert.className = 'alert alert-danger';
        alert.textContent = 'Network error. Please try again.';
        alert.style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'Subscribe — It\'s Free';
    });
});
</script>

<?php
$content = ob_get_clean();
require THEMES_PATH . '/default/templates/layout.php';
render_page('Newsletter', $content, ['description' => 'Subscribe to the Awan Tools newsletter for new tools, updates, and tutorials.']);
