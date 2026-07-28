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
        return '/image/yayematy-logo.png';
    }
}

if (!function_exists('site_brand_logo_alt')) {
    function site_brand_logo_alt()
    {
        return site_brand_name_market() . ' — ' . site_brand_tagline();
    }
}
