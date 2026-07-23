<?php
defined('AWAN') or die('Direct access denied.');

require_once __DIR__ . '/../../plugins/_sdk.php';
require_once __DIR__ . '/migrations/install.php';

whiteboard_uninstall($db);
