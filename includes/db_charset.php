<?php
/**
 * Encodage UTF-8 pour MySQL (hébergement mutualisé, import phpMyAdmin, etc.)
 */

if (!function_exists('db_pdo_utf8_options')) {
    function db_pdo_utf8_options(): array
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci';
        }

        return $options;
    }
}

if (!function_exists('db_apply_utf8')) {
    function db_apply_utf8(): void
    {
        global $db;
        if (!$db instanceof PDO) {
            return;
        }
        try {
            $db->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
            $db->exec('SET CHARACTER SET utf8mb4');
        } catch (PDOException $e) {
            // Connexion partielle ou droits limités — ne pas bloquer la page
        }
    }
}

if (!function_exists('app_set_utf8_encoding')) {
    function app_set_utf8_encoding(): void
    {
        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding('UTF-8');
        }
        if (function_exists('mb_http_output')) {
            mb_http_output('UTF-8');
        }
        ini_set('default_charset', 'UTF-8');
    }
}

app_set_utf8_encoding();
