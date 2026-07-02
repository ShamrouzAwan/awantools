<?php
defined('AWAN') or die();
require_once __DIR__ . '/../../_bootstrap.php';

if ($settings->get('google_oauth_enabled', '0') !== '1') {
    redirect('/login');
}

if ($auth->check()) redirect('/account/dashboard');

$code  = $_GET['code']  ?? '';
$state = $_GET['state'] ?? '';
$error = $_GET['error'] ?? '';

if ($error) {
    Session::flash('warning', 'Google sign-in was cancelled.');
    redirect('/login');
}

// CSRF state check
$storedState = Session::get('google_oauth_state');
Session::forget('google_oauth_state');

if (!$state || !$storedState || !hash_equals($storedState, $state)) {
    Session::flash('danger', 'Invalid OAuth state. Please try again.');
    redirect('/login');
}

if (!$code) {
    Session::flash('danger', 'No authorization code received from Google.');
    redirect('/login');
}

$clientId     = $settings->get('google_client_id', '');
$clientSecret = $settings->get('google_client_secret', '');
$_siteUrlBase = rtrim($settings->get('site_url', ''), '/');
if (!$_siteUrlBase) {
    $_proto       = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $_siteUrlBase = $_proto . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}
$redirectUri  = $_siteUrlBase . '/auth/google/callback';

// Exchange code for access token
$tokenResponse = curlPost('https://oauth2.googleapis.com/token', [
    'code'          => $code,
    'client_id'     => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri'  => $redirectUri,
    'grant_type'    => 'authorization_code',
]);

if (!$tokenResponse || empty($tokenResponse['access_token'])) {
    Session::flash('danger', 'Failed to exchange authorization code. Please try again.');
    redirect('/login');
}

// Fetch user info
$userInfo = curlGet('https://www.googleapis.com/oauth2/v3/userinfo', $tokenResponse['access_token']);

if (!$userInfo || empty($userInfo['email'])) {
    Session::flash('danger', 'Failed to retrieve user information from Google.');
    redirect('/login');
}

$googleUser = [
    'id'    => $userInfo['sub']   ?? '',
    'email' => $userInfo['email'] ?? '',
    'name'  => trim(($userInfo['given_name'] ?? '') . ' ' . ($userInfo['family_name'] ?? ''))
             ?: ($userInfo['name'] ?? ''),
];

$result = $auth->findOrCreateGoogleUser($googleUser);

if (!$result['success']) {
    Session::flash('danger', $result['error'] ?? 'Google sign-in failed.');
    redirect('/login');
}

$logger = Logger::getInstance($db);
$logger->auth('google_login', $auth->id());

$next = Session::get('google_oauth_next') ?: '/account/dashboard';
Session::forget('google_oauth_next');
redirect($auth->isAdmin() ? '/admin/' : $next);

// ─── Helpers ──────────────────────────────────────────────────────────────────

function curlPost(string $url, array $data): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    if (!$resp) return null;
    return json_decode($resp, true) ?: null;
}

function curlGet(string $url, string $accessToken): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    if (!$resp) return null;
    return json_decode($resp, true) ?: null;
}
