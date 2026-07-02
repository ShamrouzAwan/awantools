<?php
defined('AWAN') or die();
require_once __DIR__ . '/../_bootstrap.php';

if ($settings->get('google_oauth_enabled', '0') !== '1') {
    Session::flash('danger', 'Google sign-in is not enabled.');
    redirect('/login');
}

$clientId = $settings->get('google_client_id', '');
if (!$clientId) {
    Session::flash('danger', 'Google OAuth is not configured.');
    redirect('/login');
}

if ($auth->check()) redirect('/account/dashboard');

// Generate and store CSRF state
$state = bin2hex(random_bytes(16));
Session::set('google_oauth_state', $state);

// Store redirect destination
$next = Security::sanitize($_GET['next'] ?? '/account/dashboard');
Session::set('google_oauth_next', $next);

$_siteBase = rtrim($settings->get('site_url', ''), '/');
if (!$_siteBase) {
    $_proto    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $_siteBase = $_proto . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

$params = http_build_query([
    'client_id'     => $clientId,
    'redirect_uri'  => $_siteBase . '/auth/google/callback',
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'access_type'   => 'online',
    'prompt'        => 'select_account',
]);

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
exit;
