<?php
require __DIR__.'/db.php';
require __DIR__.'/auth.php';

if (!isset($_POST['session_id'])) {
    safe_redirect(BASE_URL);
}

csrf_check();

$sessionId    = $_POST['session_id'];

// Verify the session exists and that the caller is allowed to modify its choices.
// Admin: always allowed. Client: must have unlocked this specific session (or it
// has no password). CSRF alone only proves "same browser" — not "may touch session X."
$sessionRow = q("SELECT password FROM session WHERE id = ?", [$sessionId])->fetch_assoc();
if (!$sessionRow) {
    audit_log('choices_session_not_found', ['session_id' => $sessionId]);
    http_response_code(404);
    die('Session not found');
}
if (!is_admin() && !gallery_is_unlocked($sessionId) && !empty($sessionRow['password'])) {
    audit_log('choices_forbidden', ['session_id' => $sessionId]);
    http_response_code(403);
    die('Forbidden');
}

$sessionName  = $_POST['session_name']  ?? '';
$chosenImages = $_POST['chosenImages']  ?? [];
$date         = date('Y-m-d H:i:s');

$conn = db();
$conn->begin_transaction();
try {
    q("DELETE FROM choice WHERE session_id = ?", [$sessionId]);

    $imageList = '';
    foreach ($chosenImages as $image) {
        $imageList .= $image."\n";
        q(
            "INSERT INTO choice (session_id, image, timestamp) VALUES (?, ?, ?)",
            [$sessionId, $image, $date]
        );
    }

    q(
        "INSERT INTO choice_snapshot (session_id, session_name, images, timestamp) VALUES (?, ?, ?, ?)",
        [$sessionId, $sessionName, $imageList, $date]
    );

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    die('Failed to save choices');
}

audit_log('choices_submitted', [
    'session_id' => $sessionId,
    'count'      => count($chosenImages),
]);

safe_redirect(BASE_URL);
