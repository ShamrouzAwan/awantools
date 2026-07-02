<?php
defined('AWAN') or die();
require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verifyCsrf();
}

if ($auth->check()) {
    $logger = Logger::getInstance($db);
    $logger->auth('logout', $auth->id());
    $auth->logout();
}

Session::flash('success', 'You have been signed out.');
redirect('/login');
