<?php
require_once __DIR__.'/config.php';

/** Lazy singleton mysqli connection. */
function db(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            http_response_code(500);
            die('Database connection failed');
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

/**
 * Run a prepared statement.
 *
 * - SELECT-style queries return a mysqli_result.
 * - INSERT/UPDATE/DELETE return the affected_rows count as int.
 */
function q(string $sql, array $params = []) {
    $stmt = db()->prepare($sql);
    if ($stmt === false) {
        http_response_code(500);
        die('Query preparation failed');
    }
    if ($params) {
        $types = '';
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
        }
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result === false) {
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected;
    }
    return $result;
}
