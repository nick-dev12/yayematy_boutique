<?php
/**
 * Helpers PDO partagés (conn/conn.php est local et peut être gitignoré).
 */

if (!function_exists('db_is_available')) {
    function db_is_available(): bool
    {
        global $db;
        return $db instanceof PDO;
    }
}

if (!function_exists('app_db')) {
    function app_db(): ?PDO
    {
        global $db;
        return ($db instanceof PDO) ? $db : null;
    }
}
