<?php
// Load config.local.php if it exists (project root first, then one level up
// for shared-hosting layouts where config lives outside the webroot).
// Env vars always take precedence over the file (Docker uses env vars).
$_cfg = [];
foreach ([__DIR__, dirname(__DIR__)] as $_dir) {
    $_file = $_dir . '/config.local.php';
    if (is_readable($_file)) {
        $_cfg = require $_file;
        break;
    }
}

$_get = static function (string $name) use ($_cfg): string {
    $v = getenv($name);
    if ($v !== false && $v !== '') return $v;
    if (!empty($_cfg[$name]))       return $_cfg[$name];
    http_response_code(500);
    die("Missing configuration: {$name}. Copy config.local.php.example to config.local.php and fill in your values.");
};

define('DB_HOST',   $_get('DB_HOST'));
define('DB_USER',   $_get('DB_USER'));
define('DB_PASS',   $_get('DB_PASS'));
define('DB_NAME',   $_get('DB_NAME'));
define('BASE_URL',  $_get('BASE_URL'));
// Accepts a bcrypt hash ($2y$...) or a legacy 32-char MD5 hex string.
// auth.php::verify_admin_password() detects which format is in use.
define('ADMIN_PASSWORD', $_get('ADMIN_PASSWORD'));

unset($_cfg, $_dir, $_file, $_get);
