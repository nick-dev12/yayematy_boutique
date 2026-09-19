<?php
/**
 * Chargement conditionnel des assets commande invité.
 */

require_once __DIR__ . '/guest_checkout.php';
require_once __DIR__ . '/asset_version.php';

function guest_checkout_page_needs_assets(): bool
{
    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $catalog_pages = [
        'index.php',
        'produits.php',
        'categorie.php',
        'produit.php',
        'promo.php',
        'nouveautes.php',
        'panier.php',
        'commande.php',
    ];

    return in_array($script, $catalog_pages, true);
}

function guest_checkout_should_load_assets(): bool
{
    return !guest_checkout_is_connected() && guest_checkout_page_needs_assets();
}

function guest_checkout_render_config_script(): void
{
    if (!guest_checkout_should_load_assets()) {
        echo '<script>window.YAYE_GUEST_CHECKOUT={userConnected:true,hasInfo:true};</script>';
        return;
    }

    $info = guest_checkout_get_info();
    $config = [
        'userConnected' => false,
        'hasInfo' => guest_checkout_has_info(),
        'guestNom' => $info['nom'],
        'guestTelephone' => $info['telephone'],
    ];
    echo '<script>window.YAYE_GUEST_CHECKOUT=' . json_encode($config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ';</script>';
}

function guest_checkout_render_head_assets(): void
{
    if (!guest_checkout_should_load_assets()) {
        return;
    }

    if (defined('GUEST_CHECKOUT_HEAD_LOADED')) {
        return;
    }
    define('GUEST_CHECKOUT_HEAD_LOADED', true);

    guest_checkout_render_config_script();
    include __DIR__ . '/auth_intl_tel_head.php';
    echo '<link rel="stylesheet" href="' . htmlspecialchars(asset_url('/css/guest-checkout-modal.css'), ENT_QUOTES, 'UTF-8') . '">' . "\n";
}

function guest_checkout_render_modal_markup(): void
{
    if (!guest_checkout_should_load_assets()) {
        return;
    }

    if (defined('GUEST_CHECKOUT_MODAL_LOADED')) {
        return;
    }
    define('GUEST_CHECKOUT_MODAL_LOADED', true);

    include __DIR__ . '/partials/guest_checkout_modal.php';
}

function guest_checkout_render_scripts(): void
{
    if (!guest_checkout_should_load_assets()) {
        return;
    }

    if (defined('GUEST_CHECKOUT_SCRIPTS_LOADED')) {
        return;
    }
    define('GUEST_CHECKOUT_SCRIPTS_LOADED', true);

    include __DIR__ . '/auth_intl_tel_scripts.php';
    echo '<script src="' . htmlspecialchars(asset_url('/js/guest-checkout-modal.js'), ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
}

function guest_checkout_render_assets(): void
{
    if (!guest_checkout_should_load_assets()) {
        return;
    }

    if (!defined('GUEST_CHECKOUT_HEAD_LOADED')) {
        guest_checkout_render_head_assets();
    }

    guest_checkout_render_modal_markup();
    guest_checkout_render_scripts();
}

function guest_checkout_render_product_page_assets(): void
{
    guest_checkout_render_assets();
}
