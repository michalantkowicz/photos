<?php
require __DIR__.'/db.php';
require __DIR__.'/auth.php';

$sessionId = $_GET['s'] ?? '';
$filename  = $_GET['f'] ?? '';

// Validate session id is a UUID v4 string.
if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $sessionId)) {
    http_response_code(404);
    exit;
}

// Validate filename: strip path components and require an allowed extension.
$filename = basename($filename);
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$mimes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
if (!isset($mimes[$ext])) {
    http_response_code(404);
    exit;
}

// Look up the session row to learn whether it's open or password-protected.
$result = q('SELECT password FROM session WHERE id = ?', [$sessionId]);
if ($result->num_rows === 0) {
    http_response_code(404);
    exit;
}
$row = $result->fetch_assoc();

// Authorize: open session, OR the user has unlocked it this session, OR admin.
$open = empty($row['password']);
if (!$open && !gallery_is_unlocked($sessionId) && !is_admin()) {
    http_response_code(403);
    exit;
}

$path = __DIR__.'/data/'.$sessionId.'/'.$filename;
if (!is_file($path)) {
    http_response_code(404);
    exit;
}

// Disable any output buffering so we stream the binary file directly.
while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: '.$mimes[$ext]);
header('Content-Length: '.filesize($path));
header('Cache-Control: private, max-age=3600');
readfile($path);
exit;
