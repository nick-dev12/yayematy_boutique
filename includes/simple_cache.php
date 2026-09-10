<?php
/**
 * Cache fichier simple (TTL) pour données peu volatiles.
 */

if (!function_exists('cache_remember')) {
    function cache_remember(string $key, int $ttl, callable $fn)
    {
        $dir = dirname(__DIR__) . '/storage/cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $safe_key = preg_replace('/[^a-z0-9_-]/i', '_', $key);
        $file = $dir . '/' . $safe_key . '.json';

        if (is_file($file) && (time() - (int) filemtime($file)) < $ttl) {
            $raw = @file_get_contents($file);
            if ($raw !== false && $raw !== '') {
                $data = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $data;
                }
            }
        }

        $data = $fn();
        @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);

        return $data;
    }
}

if (!function_exists('cache_forget')) {
    function cache_forget(string $key): void
    {
        $dir = dirname(__DIR__) . '/storage/cache';
        $safe_key = preg_replace('/[^a-z0-9_-]/i', '_', $key);
        $file = $dir . '/' . $safe_key . '.json';
        if (is_file($file)) {
            @unlink($file);
        }
    }
}
