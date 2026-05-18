<?php
// Centralised configuration. Production defaults are preserved so deploying
// to the existing prod host with no env vars set behaves exactly as before.
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'mantkowi_ewelina');
define('DB_NAME', getenv('DB_NAME') ?: 'mantkowi_ewelina_sessions');
define('BASE_URL', getenv('BASE_URL') ?: 'http://ewelina.antkowicz.pl');

// DB_PASS is never stored in this file.
// Supply it via:
//   1. DB_PASS environment variable (Docker / hosting control panels that support env vars)
//   2. A plain-text file at <parent-of-docroot>/db-pass.txt
//      On shared hosting this is typically one level above public_html, so Apache
//      cannot serve it. Create the file and place the raw password on a single line.
$_dbPass = getenv('DB_PASS');
if ($_dbPass === false || $_dbPass === '') {
    $_credFile = dirname(__DIR__) . '/db-pass.txt';
    if (is_readable($_credFile)) {
        $_dbPass = trim(file_get_contents($_credFile));
    } else {
        http_response_code(500);
        die('DB_PASS is not configured. Set the DB_PASS env var or create ' .
            dirname(__DIR__) . '/db-pass.txt containing the database password.');
    }
}
define('DB_PASS', $_dbPass);
unset($_dbPass, $_credFile);

// Hashed admin password. Accepts either a bcrypt hash ($2y$...) or, for
// backwards compatibility with the existing production deployment, a 32-char
// MD5 hex digest. auth.php::verify_admin_password() detects which.
define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD') ?: '4e6ca650f52383d9054a826b0b4db1f5');
