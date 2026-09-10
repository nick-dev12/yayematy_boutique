<?php
/**
 * Icônes des notifications push FCM (logo site ou image produit)
 */

if (!function_exists('fcm_notification_default_icon_path')) {
    function fcm_notification_default_icon_path(): string
    {
        if (!function_exists('site_brand_logo')) {
            require_once __DIR__ . '/site_brand.php';
        }
        $logo = trim((string) site_brand_logo());
        return $logo !== '' ? $logo : '/image/yaye_maty_logo.jpeg';
    }
}

if (!function_exists('fcm_notification_icon_url_from_path')) {
    function fcm_notification_icon_url_from_path(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return fcm_notification_icon_url_from_path(fcm_notification_default_icon_path());
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        require_once __DIR__ . '/site_url.php';
        $base = rtrim(get_site_base_url(), '/');
        return $base . (strpos($path, '/') === 0 ? $path : '/' . $path);
    }
}

if (!function_exists('fcm_notification_product_image_path')) {
    function fcm_notification_product_image_path(?string $filename): string
    {
        $filename = trim((string) $filename);
        if ($filename === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $filename)) {
            return $filename;
        }
        if (strpos($filename, '/') === 0) {
            return $filename;
        }
        return '/upload/' . ltrim($filename, '/');
    }
}

if (!function_exists('fcm_notification_first_product_image_path')) {
    /**
     * @param array<int, array<string, mixed>> $produits
     */
    function fcm_notification_first_product_image_path(array $produits): string
    {
        foreach ($produits as $produit) {
            if (!is_array($produit)) {
                continue;
            }
            $candidates = [
                $produit['image_afficher'] ?? '',
                $produit['image'] ?? '',
                $produit['image_principale'] ?? '',
                $produit['variante_image'] ?? '',
                $produit['panier_variante_image'] ?? '',
            ];
            foreach ($candidates as $candidate) {
                $path = fcm_notification_product_image_path((string) $candidate);
                if ($path !== '') {
                    return $path;
                }
            }
        }
        return '';
    }
}

if (!function_exists('fcm_notification_resolve_icon_url')) {
    function fcm_notification_resolve_icon_url(?string $product_image = null): string
    {
        $product_path = fcm_notification_product_image_path($product_image);
        if ($product_path !== '') {
            return fcm_notification_icon_url_from_path($product_path);
        }
        return fcm_notification_icon_url_from_path(fcm_notification_default_icon_path());
    }
}

if (!function_exists('fcm_notification_icon_data')) {
    /**
     * @param array<int, array<string, mixed>>|null $produits
     * @return array{icon:string}
     */
    function fcm_notification_icon_data(?array $produits = null): array
    {
        $product_path = $produits ? fcm_notification_first_product_image_path($produits) : '';
        return [
            'icon' => fcm_notification_resolve_icon_url($product_path !== '' ? $product_path : null),
        ];
    }
}
