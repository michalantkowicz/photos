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

    @file_put_contents($logDir . '/audit.log', $line, FILE_APPEND | LOCK_EX);
}
