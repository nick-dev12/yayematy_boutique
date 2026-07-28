<?php
/**
 * Chargement conditionnel des assets commande invité.
 */

require_once __DIR__ . '/guest_checkout.php';
require_once __DIR__ . '/asset_version.php';

function guest_checkout_should_load_assets(): bool
{
    return !guest_checkout_is_connected();
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

function guest_checkout_render_assets(): void
{
    if (!guest_checkout_should_load_assets()) {
        return;
    }

    guest_checkout_render_config_script();
    include __DIR__ . '/auth_intl_tel_head.php';
    echo '<link rel="stylesheet" href="/css/guest-checkout-modal.css' . asset_version_query() . '">' . "\n";
    include __DIR__ . '/partials/guest_checkout_modal.php';
    include __DIR__ . '/auth_intl_tel_scripts.php';
    echo '<script src="/js/guest-checkout-modal.js' . asset_version_query() . '"></script>' . "\n";
}
