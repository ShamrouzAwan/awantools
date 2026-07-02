<?php
defined('AWAN') or die('Direct access denied.');

/**
 * Mailer — Email delivery for AWAN Platform.
 *
 * Uses PHP mail() exclusively. No SMTP. No external library required.
 * Works on any shared hosting environment with PHP mail() enabled.
 *
 * Public API:
 *   $mailer->send(string $to, string $subject, string $body, bool $isHtml = true): bool
 *   $mailer->sendTemplate(string $slug, string $to, array $vars = []): bool
 *   $mailer->sendTemplateQueued(string $slug, string $to, array $vars = []): bool
 *   $mailer->queue(string $to, string $subject, string $html, array $opts = []): bool
 *   $mailer->processQueue(int $limit = 20): int
 *   $mailer->lastError(): array
 *   Mailer::html(string $siteName, string $title, string $body, string $ctaText = '', string $ctaUrl = ''): string
 *   Mailer::getInstance(Settings $settings): self
 */
class Mailer
{
    private static ?self $instance = null;
    private Settings $settings;
    private array $lastError = [];
    private string $lastTrackingToken = '';

    private function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public static function getInstance(Settings $settings): self
    {
        if (!self::$instance) {
            self::$instance = new self($settings);
        }
        return self::$instance;
    }

    // ─── Setting helpers ──────────────────────────────────────────────────────

    private function fromEmail(): string
    {
        // Supports both legacy smtp_from_email key and new mail_from_email key
        $v = $this->settings->get('mail_from_email', '')
            ?: $this->settings->get('smtp_from_email', '');
        if (!empty(trim($v))) return trim($v);
        $host = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
        return 'noreply@' . $host;
    }

    private function fromName(): string
    {
        return $this->settings->get('mail_from_name', '')
            ?: $this->settings->get('smtp_from_name', '')
            ?: $this->settings->get('site_name', 'AWAN Platform');
    }

    private function replyTo(): string
    {
        $rt = trim($this->settings->get('mail_reply_to', '')
            ?: $this->settings->get('smtp_reply_to', ''));
        return $rt ?: $this->fromEmail();
    }

    // ─── Public API ───────────────────────────────────────────────────────────

    /**
     * Send an email immediately via PHP mail().
     */
    public function send(string $to, string $subject, string $body, bool $isHtml = true): bool
    {
        $from    = $this->fromEmail();
        $name    = $this->fromName();
        $replyTo = $this->replyTo();

        // Generate open-tracking pixel token and inject it into HTML emails
        $this->lastTrackingToken = '';
        if ($isHtml) {
            $token = bin2hex(random_bytes(16));
            $this->lastTrackingToken = $token;
            $siteUrl = rtrim($this->settings->get('site_url', ''), '/');
            if (!$siteUrl) {
                $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $siteUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
            }
            // Rewrite all http/https links through click tracker (exclude unsubscribe + tracking links)
            $body = preg_replace_callback(
                '/<a\s([^>]*?)href=["\']((https?:\/\/)[^"\']+)["\']/i',
                function ($m) use ($siteUrl, $token) {
                    $url = $m[2];
                    // Don't wrap tracking or unsubscribe URLs
                    if (str_contains($url, '/email-track/') || str_contains($url, '/unsubscribe')) {
                        return $m[0];
                    }
                    $wrapped = $siteUrl . '/email-track/click/' . $token . '?url=' . rawurlencode($url);
                    return '<a ' . $m[1] . 'href="' . htmlspecialchars($wrapped, ENT_QUOTES) . '"';
                },
                $body
            );
            $pixel = '<img src="' . $siteUrl . '/email-track/open/' . $token
                   . '" width="1" height="1" alt="" style="display:block;width:1px;height:1px;border:0">';
            if (strpos($body, '</body>') !== false) {
                $body = str_replace('</body>', $pixel . "\n</body>", $body);
            } else {
                $body .= $pixel;
            }
        }

        $result = $this->sendViaPhpMail($to, $subject, $body, $isHtml, $from, $name, $replyTo);
        $this->logEmail($to, $subject, $result);
        return $result;
    }

    /**
     * Render a DB email template and send immediately.
     */
    public function sendTemplate(string $slug, string $to, array $vars = []): bool
    {
        $rendered = $this->renderTemplate($slug, $vars);
        if ($rendered === null) {
            $this->lastError = ['error' => "Email template '{$slug}' not found in database."];
            return false;
        }
        [$subject, $html] = $rendered;
        return $this->send($to, $subject, $html, true);
    }

    /**
     * Render a DB template and queue it for async delivery.
     * Falls back to immediate send if queue is unavailable.
     */
    public function sendTemplateQueued(string $slug, string $to, array $vars = []): bool
    {
        $db = $GLOBALS['db'] ?? null;
        if (!$db) return $this->sendTemplate($slug, $to, $vars);

        $rendered = $this->renderTemplate($slug, $vars);
        if ($rendered === null) {
            $this->lastError = ['error' => "Email template '{$slug}' not found."];
            return false;
        }
        [$subject, $html] = $rendered;
        return $this->queue($to, $subject, $html);
    }

    /**
     * Queue a raw email for async delivery.
     * Falls back to immediate send if DB is unavailable.
     *
     * $opts keys: from_email, from_name, reply_to, to_name, scheduled_at, max_attempts
     */
    public function queue(string $to, string $subject, string $html, array $opts = []): bool
    {
        $db = $GLOBALS['db'] ?? null;
        if (!$db) return $this->send($to, $subject, $html, true);

        try {
            $db->insert('email_queue', [
                'to_email'     => $to,
                'to_name'      => $opts['to_name']      ?? null,
                'subject'      => $subject,
                'body_html'    => $html,
                'body_text'    => $this->htmlToText($html),
                'from_email'   => $opts['from_email']   ?? $this->fromEmail(),
                'from_name'    => $opts['from_name']    ?? $this->fromName(),
                'reply_to'     => $opts['reply_to']     ?? $this->replyTo(),
                'max_attempts' => $opts['max_attempts'] ?? 3,
                'scheduled_at' => $opts['scheduled_at'] ?? null,
                'status'       => 'pending',
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
            return true;
        } catch (\Throwable $e) {
            return $this->send($to, $subject, $html, true);
        }
    }

    /**
     * Process up to $limit pending items from the email queue.
     * Returns number of emails successfully sent.
     */
    public function processQueue(int $limit = 20): int
    {
        $db = $GLOBALS['db'] ?? null;
        if (!$db) return 0;

        try {
            $items = $db->fetchAll(
                "SELECT * FROM email_queue
                 WHERE status = 'pending' AND attempts < max_attempts
                   AND (scheduled_at IS NULL OR scheduled_at <= ?)
                 ORDER BY created_at ASC LIMIT ?",
                [date('Y-m-d H:i:s'), $limit]
            );
        } catch (\Throwable $e) {
            return 0;
        }

        $sent = 0;

        foreach ($items as $item) {
            try {
                $db->update('email_queue',
                    ['status' => 'processing', 'attempts' => (int)$item['attempts'] + 1],
                    'id = ?', [$item['id']]
                );
            } catch (\Throwable $e) {
                continue;
            }

            $fromEmail = $item['from_email'] ?: $this->fromEmail();
            $fromName  = $item['from_name']  ?: $this->fromName();
            $replyTo   = $item['reply_to']   ?: $this->replyTo();

            $result = $this->sendViaPhpMail(
                $item['to_email'], $item['subject'], $item['body_html'],
                true, $fromEmail, $fromName, $replyTo
            );

            try {
                if ($result) {
                    $db->update('email_queue',
                        ['status' => 'sent', 'sent_at' => date('Y-m-d H:i:s'), 'error' => null],
                        'id = ?', [$item['id']]
                    );
                    $sent++;
                } else {
                    $error       = $this->lastError['error'] ?? 'Unknown error';
                    $attempts    = (int)$item['attempts'];
                    $maxAttempts = (int)($item['max_attempts'] ?? 3);
                    $newStatus   = ($attempts >= $maxAttempts) ? 'failed' : 'pending';
                    $db->update('email_queue', [
                        'status'    => $newStatus,
                        'error'     => $error,
                        'failed_at' => ($newStatus === 'failed') ? date('Y-m-d H:i:s') : null,
                    ], 'id = ?', [$item['id']]);
                }
            } catch (\Throwable $e) {}

            $this->logEmail($item['to_email'], $item['subject'], $result);
        }

        return $sent;
    }

    /**
     * Return details of the last error.
     */
    public function lastError(): array
    {
        return $this->lastError;
    }

    // ─── Transport: PHP mail() ────────────────────────────────────────────────

    private function sendViaPhpMail(
        string $to, string $subject, string $body, bool $isHtml,
        string $fromEmail, string $fromName, string $replyTo
    ): bool {
        if ($isHtml) {
            $boundary = 'b_' . md5(uniqid('', true));
            $plain    = $this->htmlToText($body);
            $hdrs     = implode("\r\n", [
                'MIME-Version: 1.0',
                "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
                'From: =?UTF-8?B?' . base64_encode($fromName) . "?= <{$fromEmail}>",
                "Reply-To: {$replyTo}",
                'List-Unsubscribe: <' . siteUrl('/unsubscribe') . '>',
                'X-Mailer: AWAN Platform',
            ]);
            $msg = "--{$boundary}\r\n"
                 . "Content-Type: text/plain; charset=UTF-8\r\n"
                 . "Content-Transfer-Encoding: base64\r\n\r\n"
                 . chunk_split(base64_encode($plain)) . "\r\n"
                 . "--{$boundary}\r\n"
                 . "Content-Type: text/html; charset=UTF-8\r\n"
                 . "Content-Transfer-Encoding: base64\r\n\r\n"
                 . chunk_split(base64_encode($body)) . "\r\n"
                 . "--{$boundary}--";
        } else {
            $hdrs = implode("\r\n", [
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: base64',
                'From: =?UTF-8?B?' . base64_encode($fromName) . "?= <{$fromEmail}>",
                "Reply-To: {$replyTo}",
                'X-Mailer: AWAN Platform',
            ]);
            $msg = chunk_split(base64_encode($body));
        }

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $result         = @mail($to, $encodedSubject, $msg, $hdrs);

        if (!$result) {
            $this->lastError = [
                'error' => 'PHP mail() returned false. '
                    . 'Ensure From Email is set to a real inbox on your hosting domain '
                    . '(e.g. noreply@yourdomain.com) and that PHP mail() is enabled on your server.',
            ];
        } else {
            $this->lastError = [];
        }

        return (bool)$result;
    }

    // ─── Template rendering ───────────────────────────────────────────────────

    private function renderTemplate(string $slug, array $vars): ?array
    {
        $db = $GLOBALS['db'] ?? null;
        if (!$db) return null;

        try {
            $tpl = $db->fetch("SELECT * FROM email_templates WHERE slug = ?", [$slug]);
        } catch (\Throwable $e) {
            return null;
        }
        if (!$tpl) return null;

        $siteName = $this->settings->get('site_name', 'AWAN Platform');
        $merged   = array_merge(['site_name' => $siteName], $vars);

        $bodyRaw = $tpl['body'];
        $subject = $tpl['subject'];

        foreach ($merged as $k => $v) {
            $safe    = htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
            $bodyRaw = str_replace('{{' . $k . '}}', $safe, $bodyRaw);
            $subject = str_replace('{{' . $k . '}}', $safe, $subject);
        }

        $ctaText    = $vars['cta_text']    ?? '';
        $ctaUrl     = $vars['cta_url']     ?? '';
        $emailTitle = $vars['email_title'] ?? $tpl['name'];

        $html = self::html($siteName, $emailTitle, $bodyRaw, $ctaText, $ctaUrl);

        // Replace unsubscribe placeholder with actual URL
        $siteUrl = rtrim($this->settings->get('site_url', ''), '/');
        if (!$siteUrl) {
            $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $siteUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        }
        $html = str_replace('[unsubscribe_url]', $siteUrl . '/unsubscribe', $html);

        return [$subject, $html];
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function htmlToText(string $html): string
    {
        $text = preg_replace('#<br\s*/?>#i', "\n", $html);
        $text = preg_replace('#<p[^>]*>#i', "\n", $text);
        $text = preg_replace('#<h[1-6][^>]*>#i', "\n\n", $text);
        $text = preg_replace('#<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)</a>#i', '$2 [$1]', $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return trim($text);
    }

    private function logEmail(string $to, string $subject, bool $success): void
    {
        try {
            $db = $GLOBALS['db'] ?? null;
            if (!$db) return;
            $db->insert('email_logs', [
                'recipient'      => $to,
                'subject'        => $subject,
                'status'         => $success ? 'sent' : 'failed',
                'transport'      => 'php',
                'tracking_token' => $this->lastTrackingToken ?: null,
                'error_message'  => $success ? null : ($this->lastError['error'] ?? null),
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Fallback without new columns if schema migration hasn't run yet
            try {
                $db = $GLOBALS['db'] ?? null;
                if (!$db) return;
                $db->insert('email_logs', [
                    'recipient'  => $to,
                    'subject'    => $subject,
                    'status'     => $success ? 'sent' : 'failed',
                    'transport'  => 'php',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e2) {}
        }
    }

    // ─── Static HTML wrapper ──────────────────────────────────────────────────

    /**
     * Build a branded HTML email wrapper around $body.
     * Used by all callers to produce consistent email markup.
     */
    public static function html(
        string $siteName, string $title, string $body,
        string $ctaText = '', string $ctaUrl = ''
    ): string {
        $sSiteName = htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8');
        $sTitle    = htmlspecialchars($title,    ENT_QUOTES, 'UTF-8');
        $sCtaText  = htmlspecialchars($ctaText,  ENT_QUOTES, 'UTF-8');
        $sCtaUrl   = htmlspecialchars($ctaUrl,   ENT_QUOTES, 'UTF-8');

        $cta = ($ctaText !== '' && $ctaUrl !== '')
            ? "<p style='margin:24px 0 0'>"
              . "<a href='{$sCtaUrl}' style='display:inline-block;background:#6366f1;color:#ffffff;"
              . "padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;"
              . "font-size:15px;line-height:1'>{$sCtaText}</a></p>"
            : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{$sTitle}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
      <td align="center" style="padding:40px 16px">
        <table width="560" cellpadding="0" cellspacing="0" role="presentation"
               style="background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);max-width:100%">
          <tr>
            <td style="background:#6366f1;padding:24px 32px">
              <h2 style="color:#ffffff;margin:0;font-size:20px;font-weight:700;letter-spacing:-0.3px">{$sSiteName}</h2>
            </td>
          </tr>
          <tr>
            <td style="padding:32px">
              <h1 style="font-size:22px;color:#0f172a;margin:0 0 16px;font-weight:700;line-height:1.3">{$sTitle}</h1>
              <div style="font-size:15px;line-height:1.7;color:#334155">{$body}</div>
              {$cta}
            </td>
          </tr>
          <tr>
            <td style="padding:16px 32px;border-top:1px solid #e2e8f0;color:#94a3b8;font-size:12px">
              Sent by {$sSiteName} &nbsp;&middot;&nbsp; <a href="[unsubscribe_url]" style="color:#94a3b8;text-decoration:underline">Unsubscribe</a>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }
}
