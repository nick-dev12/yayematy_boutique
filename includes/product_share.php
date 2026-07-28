<?php
/**
 * Partage produit (WhatsApp, réseaux, lien) — frontend catalogue.
 */

if (!function_exists('product_share_abs_url')) {
    function product_share_abs_url(int $produit_id): string
    {
        if ($produit_id <= 0) {
            return '';
        }
        if (!function_exists('get_site_base_url')) {
            require_once __DIR__ . '/site_url.php';
        }
        return get_site_base_url() . '/produit.php?id=' . $produit_id;
    }
}

if (!function_exists('product_share_price_label')) {
    function product_share_price_label(array $produit): string
    {
        $prix_base = (float) ($produit['prix'] ?? 0);
        $prix_promo = isset($produit['prix_promotion']) ? (float) $produit['prix_promotion'] : 0.0;
        $prix = ($prix_promo > 0 && $prix_promo < $prix_base) ? $prix_promo : $prix_base;
        return number_format($prix, 0, ',', ' ') . ' FCFA';
    }
}

if (!function_exists('product_share_brand_name')) {
    function product_share_brand_name(): string
    {
        if (!function_exists('site_brand_name')) {
            require_once __DIR__ . '/site_brand.php';
        }
        return site_brand_name();
    }
}

if (!function_exists('product_share_text_short')) {
    function product_share_text_short(array $produit): string
    {
        $nom = trim((string) ($produit['nom'] ?? 'Produit'));
        $prix = product_share_price_label($produit);
        return 'Découvrez « ' . $nom . ' » à ' . $prix . ' sur ' . product_share_brand_name();
    }
}

if (!function_exists('product_share_message')) {
    function product_share_message(array $produit): string
    {
        $pid = (int) ($produit['id'] ?? 0);
        $nom = trim((string) ($produit['nom'] ?? 'Produit'));
        $url = product_share_abs_url($pid);
        $prix = product_share_price_label($produit);
        return 'Découvrez « ' . $nom . ' » à ' . $prix . ' sur ' . product_share_brand_name() . " :\n" . $url;
    }
}

if (!function_exists('product_share_payload')) {
    function product_share_payload(array $produit): array
    {
        $pid = (int) ($produit['id'] ?? 0);
        return [
            'url' => product_share_abs_url($pid),
            'text' => product_share_text_short($produit),
            'title' => trim((string) ($produit['nom'] ?? 'Produit')),
        ];
    }
}
