<?php
require_once __DIR__ . '/includes/session_user.php';
/**
 * Traitement de l'ajout direct au panier depuis les cartes produits
 * Redirige vers la page d'origine ou le panier avec un message
 */
session_start_persistent();

require_once __DIR__ . '/controllers/controller_panier.php';
require_once __DIR__ . '/includes/guest_checkout_assets.php';
require_once __DIR__ . '/includes/site_url.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['produit_id'])) {
    header('Location: ' . public_url('/index.php'));
    exit;
}

$result = process_add_to_panier();
$produit_id = (int) $_POST['produit_id'];

if ($result['success']) {
    header('Location: ' . public_url('/panier.php?added=1'));
    exit;
}

$error_message = (string) ($result['message'] ?? '');
$return_url = isset($_POST['return_url']) && $_POST['return_url'] !== ''
    ? (string) $_POST['return_url']
    : public_url('/panier.php');

$needs_guest_info = !guest_checkout_is_connected() && guest_checkout_message_needs_info($error_message);
if ($needs_guest_info && $produit_id > 0 && stripos($return_url, 'produit.php') !== false) {
    header('Location: ' . public_url('/produit.php?id=' . $produit_id . '&guest_checkout=required'));
    exit;
}

$separator = (strpos($return_url, '?') !== false) ? '&' : '?';
header('Location: ' . $return_url . $separator . 'error=' . urlencode($error_message));
exit;
