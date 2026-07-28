<?php
/**
 * Chargement unique des assets partage produit (CSS + modal + JS).
 */

if (!function_exists('product_share_should_load_assets')) {
    function product_share_should_load_assets(): bool
    {
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($script === '') {
            return false;
        }

        $catalog_pages = ['index.php', 'produits.php', 'nouveautes.php', 'promo.php', 'categorie.php', 'produit.php'];
        if (in_array(basename($script), $catalog_pages, true)) {
            return true;
        }

        if (strpos($script, '/user/produits-visites.php') !== false) {
            return true;
        }

        $admin_pages = [
            '/admin/dashboard.php',
            '/admin/produits/index.php',
            '/admin/categories/produits.php',
        ];
        foreach ($admin_pages as $admin_page) {
            if (substr($script, -strlen($admin_page)) === $admin_page) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('product_share_render_assets')) {
    function product_share_render_assets(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (!function_exists('asset_version_query')) {
            require_once __DIR__ . '/asset_version.php';
        }

        echo '<link rel="stylesheet" href="/css/product-share.css' . asset_version_query() . '">' . "\n";
        echo '<link rel="stylesheet" href="/css/platform-share-modal.css' . asset_version_query() . '">' . "\n";
        include __DIR__ . '/partials/platform_share_modal.php';
        echo '<script src="/js/platform-share-modal.js' . asset_version_query() . '" defer></script>' . "\n";
    }
}
