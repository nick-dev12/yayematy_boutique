<?php
/**
 * Identité visuelle du site — Yaye Maty
 */

if (!function_exists('site_brand_name')) {
    function site_brand_name()
    {
        return 'Yaye Maty';
    }
}

if (!function_exists('site_brand_name_market')) {
    function site_brand_name_market()
    {
        return 'YAYEMATY MARKET';
    }
}

if (!function_exists('site_brand_tagline')) {
    function site_brand_tagline()
    {
        return 'Votre marché local';
    }
}

if (!function_exists('site_brand_logo')) {
    function site_brand_logo()
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        if (!function_exists('get_site_brand_logo_path')) {
            $model = __DIR__ . '/../models/model_site_brand.php';
            if (is_file($model)) {
                require_once $model;
            }
        }

        if (function_exists('get_site_brand_logo_path')) {
            $cached = get_site_brand_logo_path();
            return $cached;
        }

        $cached = '/image/yaye_maty_logo.png';
        return $cached;
    }
}

if (!function_exists('site_brand_logo_alt')) {
    function site_brand_logo_alt()
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        if (!function_exists('get_site_brand_config')) {
            $model = __DIR__ . '/../models/model_site_brand.php';
            if (is_file($model)) {
                require_once $model;
            }
        }

        if (function_exists('get_site_brand_config')) {
            $config = get_site_brand_config();
            $alt = trim((string) ($config['logo_alt'] ?? ''));
            if ($alt !== '') {
                $cached = $alt;
                return $cached;
            }
        }

        $cached = site_brand_name_market() . ' — ' . site_brand_tagline();
        return $cached;
    }
}
