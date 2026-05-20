<?php
function audit_log(string $event, array $context = []): void {
    $ip   = $_SERVER['REMOTE_ADDR'] ?? '-';
    $ts   = date('Y-m-d H:i:s');
    $line = "[{$ts}] {$ip} {$event}";
    foreach ($context as $k => $v) {
        $v = (string) $v;
        if (ctype_digit($v)) {
            $line .= " {$k}={$v}";
        } else {
            $safe = str_replace(['"', "\n", "\r"], ['\\"', ' ', ' '], $v);
            $line .= " {$k}=\"{$safe}\"";
        }
    }
    $line .= PHP_EOL;

    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755);
        @file_put_contents($logDir . '/.htaccess', "Deny from all\n");
    }

    // Size-based rotation. When audit.log reaches the cap it is archived under
    // a unique name — audit_YYYYMMDD_<8 hex chars>.log — and a fresh audit.log
    // is started. Nothing is ever deleted: archives accumulate in logs/ and
    // stay covered by the deny-all .htaccess. The random suffix keeps names
    // collision-free even if rotation fires twice in one day, or if two
    // requests race (only one rename() wins; the other no-ops harmlessly).
    $logFile  = $logDir . '/audit.log';
    $maxBytes = 20 * 1024 * 1024; // 20 MB

    clearstatcache(true, $logFile);
    if (is_file($logFile) && filesize($logFile) >= $maxBytes) {
        $archive = sprintf('%s/audit_%s_%s.log',
            $logDir, date('Ymd'), bin2hex(random_bytes(4)));
        @rename($logFile, $archive);
    }

    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}
