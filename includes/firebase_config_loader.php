<?php
/**
 * Chargement sécurisé de config/firebase_config.php (fichier souvent absent en local).
 */

if (!function_exists('firebase_config_path')) {
    function firebase_config_path(): string
    {
        return __DIR__ . '/../config/firebase_config.php';
    }
}

if (!function_exists('firebase_config_is_available')) {
    function firebase_config_is_available(): bool
    {
        return is_file(firebase_config_path());
    }
}

if (!function_exists('firebase_load_config')) {
    /**
     * @return array<string, mixed>|null
     */
    function firebase_load_config(): ?array
    {
        static $cached = null;
        static $loaded = false;

        if ($loaded) {
            return $cached;
        }

        $loaded = true;
        $path = firebase_config_path();
        if (!is_file($path)) {
            $cached = null;
            return null;
        }

        $config = require $path;
        $cached = is_array($config) ? $config : null;

        return $cached;
    }
}
