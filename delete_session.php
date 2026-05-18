<?php
require __DIR__.'/auth.php';

require_admin();
csrf_check();

$id = $_POST['session_id'] ?? '';
if (empty($id)) {
    http_response_code(400);
    die('Missing session_id');
}

$session = q("SELECT id, name FROM session WHERE id = ?", [$id])->fetch_assoc();
if (!$session) {
    audit_log('session_delete_failed', ['session_id' => $id, 'reason' => 'not_found']);
    http_response_code(404);
    die('Session not found');
}

$dir          = __DIR__ . '/data/' . $id;
$filesRemoved = 0;
if (is_dir($dir)) {
    foreach (glob($dir . '/*') ?: [] as $file) {
        if (is_file($file) && @unlink($file)) {
            $filesRemoved++;
        }
    }
    @rmdir($dir);
}

q("DELETE FROM choice_snapshot WHERE session_id = ?", [$id]);
q("DELETE FROM choice          WHERE session_id = ?", [$id]);
q("DELETE FROM session         WHERE id = ?",         [$id]);

audit_log('session_deleted', [
    'session_id'    => $id,
    'session_name'  => $session['name'],
    'files_removed' => $filesRemoved,
]);

header('Location: ' . BASE_URL . '/admin');
exit;
