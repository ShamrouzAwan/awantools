<?php
defined('AWAN') or die();
require_once __DIR__ . '/_bootstrap.php';

if ($auth->check()) redirect('/account/dashboard');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/verify-email');

Security::verifyCsrf();

$email = Security::sanitizeEmail($_POST['email'] ?? '');
if (empty($email)) {
    Session::flash('danger', 'Please enter your email address.');
    redirect('/verify-email');
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if (!Security::checkRateLimit('resend_verify_' . $ip, 3, 3600)) {
    Session::flash('warning', 'Too many requests. Please wait an hour before trying again.');
    redirect('/verify-email');
}

$user = $db->fetch("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1", [$email]);
if ($user && empty($user['email_verified_at'])) {
    $token     = $auth->generateEmailVerificationToken((int)$user['id']);
    $verifyUrl = siteUrl('verify-email') . '?token=' . urlencode($token);
    $name      = $user['name'] ?: $user['username'];

    $mailer->sendTemplate('verify-email', $email, [
        'name'        => $name,
        'verify_url'  => $verifyUrl,
        'cta_text'    => 'Verify My Email',
        'cta_url'     => $verifyUrl,
        'email_title' => 'Verify your email address',
    ]);
}

Session::flash('success', 'If that email is registered and unverified, a new link has been sent.');
redirect('/verify-email');
