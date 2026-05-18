<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/session.php';
require_once __DIR__.'/db.php';

// ---------------------------------------------------------------------------
// Admin auth — backed by $_SESSION['is_admin']

function is_admin(): bool {
    return !empty($_SESSION['is_admin']);
}

function require_admin(): void {
    if (!is_admin()) {
        http_response_code(403);
        die('Forbidden');
    }
}

function admin_login(): void {
    session_regenerate_id(true);
    $_SESSION['is_admin'] = true;
    unset($_SESSION['csrf']); // rotate CSRF token on privilege escalation
    audit_log('login_success');
}

function admin_logout(): void {
    audit_log('logout');
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/**
 * Verify a plaintext admin password against the stored ADMIN_PASSWORD value.
 * Accepts both legacy MD5-hex (32 hex chars) and modern bcrypt hashes so the
 * production DB doesn't break on deploy.
 */
function verify_admin_password(string $input): bool {
    $stored = ADMIN_PASSWORD;
    if (preg_match('/^\$2[ayb]\$/', $stored)) {
        return password_verify($input, $stored);
    }
    if (preg_match('/^[a-f0-9]{32}$/i', $stored)) {
        return hash_equals(strtolower($stored), md5($input));
    }
    return false;
}

// ---------------------------------------------------------------------------
// Per-gallery unlock state — backed by $_SESSION['gallery_unlocked'][session_id]

function gallery_unlock(string $sessionId): void {
    $_SESSION['gallery_unlocked'][$sessionId] = true;
}

function gallery_is_unlocked(string $sessionId): bool {
    return !empty($_SESSION['gallery_unlocked'][$sessionId]);
}

/**
 * Verify a session password against the value stored on the session row.
 * Accepts both legacy plaintext and modern bcrypt hashes.
 */
function verify_session_password(string $input, string $stored): bool {
    if (preg_match('/^\$2[ayb]\$/', $stored)) {
        return password_verify($input, $stored);
    }
    return hash_equals($stored, $input);
}

// ---------------------------------------------------------------------------
// CSRF — per-session token, embedded as a hidden field in every state-changing form

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="'.csrf_token().'">';
}

function csrf_check(): void {
    $sent = $_POST['csrf'] ?? '';
    if (empty($_SESSION['csrf']) || !is_string($sent) || !hash_equals($_SESSION['csrf'], $sent)) {
        http_response_code(403);
        die('Bad CSRF token');
    }
}

// ---------------------------------------------------------------------------
// Admin login rate-limiting — 5 failures per 10-minute window per IP

/** Block with 429 if there are already 5+ failed attempts from this IP in the last 10 minutes. */
function check_login_rate_limit(): void {
    $ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $window = date('Y-m-d H:i:s', time() - 600);
    $row    = q("SELECT COUNT(*) AS n FROM login_attempts WHERE ip = ? AND attempted_at >= ?",
                [$ip, $window])->fetch_assoc();
    if ((int)($row['n'] ?? 0) >= 5) {
        audit_log('login_rate_limited', ['ip' => $ip]);
        http_response_code(429);
        die('Zbyt wiele prób logowania. Spróbuj ponownie za 10 minut.');
    }
}

/** Record one failed admin login attempt. Prunes entries older than 24 h. */
function record_failed_login(): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    audit_log('login_failure', ['ip' => $ip]);
    q("INSERT INTO login_attempts (ip, attempted_at) VALUES (?, NOW())", [$ip]);
    q("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
}

// ---------------------------------------------------------------------------
// Safe redirect — validates the Referer against BASE_URL to prevent open-redirect

/**
 * Redirect to $referer if it points to this site, otherwise to $fallback.
 * Guards against a crafted Referer header being used as an open-redirect vector.
 */
function safe_redirect(string $fallback): void {
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $isLocal = $ref !== '' && (
        $ref === BASE_URL ||
        str_starts_with($ref, BASE_URL . '/')
    );
    header('Location: ' . ($isLocal ? $ref : $fallback));
    exit;
}

// ---------------------------------------------------------------------------
// Utilities

function hash_password(string $plain): string {
    return password_hash($plain, PASSWORD_BCRYPT);
}

/** Cryptographically random UUID v4 string (36 chars). */
function uuid_v4(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
