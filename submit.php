<?php
require __DIR__.'/db.php';
require __DIR__.'/auth.php';

require_admin();
csrf_check();

$sessionId          = uuid_v4();                            // ignore client value
$sessionName        = $_POST['session_name']        ?? '';
$sessionUrl         = $_POST['session_url']         ?? '';
$sessionDescription = $_POST['session_description'] ?? '';
// Session passwords are shareable access codes (the photographer hands them
// to clients), not real user passwords — keep them plaintext so the admin
// can read them back from the listing. verify_session_password() still
// accepts bcrypt for any rows that were hashed in the past.
$sessionPassword    = $_POST['session_password']    ?? '';
// Optional client e-mail — used only to pre-fill the "share" mailto link in
// the admin listing. Not shown to clients, not used for auth.
$sessionEmail       = $_POST['session_email']        ?? '';

// The `url` column is UNIQUE (two sessions can't share a slug). Reject a
// duplicate up front — before creating any files — with a clear message,
// instead of letting the INSERT throw an uncaught mysqli exception that
// dumps a fatal error and stack trace at the user.
if (q("SELECT 1 FROM session WHERE url = ?", [$sessionUrl])->num_rows > 0) {
    http_response_code(409);
    die('Sesja o tym adresie już istnieje — wróć w przeglądarce i wybierz inną nazwę sesji.');
}

$photoDir = __DIR__.'/data/'.$sessionId;
if (!is_dir($photoDir)) {
    mkdir($photoDir, 0755, true);
}

$allowedExt = ['jpg', 'jpeg', 'png'];
$savedFilenames = [];

$names = $_FILES['session_files']['name']     ?? [];
$tmps  = $_FILES['session_files']['tmp_name'] ?? [];

for ($i = 0, $n = count($names); $i < $n; $i++) {
    $filename = basename($names[$i]);
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        continue;
    }
    if (move_uploaded_file($tmps[$i], $photoDir.'/'.$filename)) {
        $savedFilenames[] = $filename;
    }
}

q(
    "INSERT INTO session (id, name, url, description, file_names, password, email) VALUES (?, ?, ?, ?, ?, ?, ?)",
    [$sessionId, $sessionName, $sessionUrl, $sessionDescription, implode("\n", $savedFilenames), $sessionPassword, $sessionEmail]
);

audit_log('session_created', [
    'session_id'   => $sessionId,
    'session_name' => $sessionName,
    'files'        => count($savedFilenames),
]);

safe_redirect(BASE_URL.'/admin');
