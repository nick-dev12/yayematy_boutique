<?php
require_once __DIR__ . '/includes/session_user.php';
session_start_persistent();
require_once __DIR__ . '/includes/site_brand.php';
require_once __DIR__ . '/models/model_commandes.php';

$numero = isset($_GET['numero']) ? trim((string) $_GET['numero']) : '';
$commande = $numero !== '' ? get_commande_by_numero($numero) : false;
$produits = ($commande && !empty($commande['id'])) ? get_commande_produits((int) $commande['id']) : [];

$social_config = [];
if (file_exists(__DIR__ . '/config/social.php')) {
    $social_config = require __DIR__ . '/config/social.php';
}
$whatsapp_clean = preg_replace('/[^0-9]/', '', (string) ($social_config['whatsapp'] ?? ''));
$whatsapp_url = '';

if ($whatsapp_clean !== '') {
    $client_nom = trim(
        trim((string) ($commande['client_prenom'] ?? '')) . ' ' . trim((string) ($commande['client_nom'] ?? ''))
    );
    if ($client_nom === '') {
        $client_nom = 'Client';
    }

    $lignes_produits = [];
    foreach ($produits as $p) {
        $nom = trim((string) ($p['nom'] ?? 'Produit'));
        if (!empty($p['variante_nom'])) {
            $nom .= ' → ' . trim((string) $p['variante_nom']);
        }
        $qty = (int) ($p['quantite'] ?? 1);
        $prix = number_format((float) ($p['prix_total'] ?? 0), 0, ',', ' ');
        $lignes_produits[] = '• ' . $nom . ' × ' . $qty . ' — ' . $prix . ' FCFA';
    }

    $total = number_format((float) ($commande['montant_total'] ?? 0), 0, ',', ' ');
    $tel = trim((string) ($commande['telephone_livraison'] ?? $commande['client_telephone'] ?? ''));
    $adresse = trim((string) ($commande['adresse_livraison'] ?? ''));

    $msg = "Bonjour " . site_brand_name() . ",\n\n";
    $msg .= "Je viens de passer une commande et souhaite vous contacter.\n\n";
    $msg .= "📦 Commande : " . ($numero !== '' ? $numero : 'N/A') . "\n";
    $msg .= "👤 Client : " . $client_nom . "\n";
    if ($tel !== '') {
        $msg .= "📞 Téléphone : " . $tel . "\n";
    }
    if ($adresse !== '') {
        $msg .= "📍 Adresse : " . $adresse . "\n";
    }
    $msg .= "\n🛒 Produits commandés :\n";
    if (!empty($lignes_produits)) {
        $msg .= implode("\n", $lignes_produits) . "\n";
    } else {
        $msg .= "• Détails produits indisponibles\n";
    }
    if (!empty($commande['montant_total'])) {
        $msg .= "\n💰 Total : " . $total . " FCFA\n";
    }
    $msg .= "\nMerci !";

    $whatsapp_url = 'https://wa.me/' . $whatsapp_clean . '?text=' . rawurlencode($msg);
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/includes/asset_version.php'; ?>
    <?php include __DIR__ . '/includes/pwa_meta.php'; ?>
    <title>Commande confirmée — <?php echo htmlspecialchars(site_brand_name()); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/panier.css<?php echo asset_version_query(); ?>">
</head>

<body class="panier-page">
    <?php include 'nav_bar.php'; ?>

    <div class="panier-hub">
        <div class="panier-empty panier-empty--success">
            <div class="panier-empty__icon"><i class="fas fa-check-circle" aria-hidden="true"></i></div>
            <h1>Merci pour votre commande</h1>
            <?php if ($numero !== ''): ?>
            <p>Votre commande <strong><?php echo htmlspecialchars($numero); ?></strong> a bien été enregistrée.</p>
            <?php else: ?>
            <p>Votre commande a bien été enregistrée.</p>
            <?php endif; ?>
            <p class="panier-invite-notice">Nous vous contacterons sur le numéro indiqué pour la suite de la commande.</p>

            <div class="panier-success-actions">
                <?php if ($whatsapp_url !== ''): ?>
                <a href="<?php echo htmlspecialchars($whatsapp_url); ?>"
                    target="_blank" rel="noopener noreferrer"
                    class="panier-btn panier-btn--whatsapp"
                    title="Contactez-nous sur WhatsApp">
                    <i class="fab fa-whatsapp" aria-hidden="true"></i>
                    Nous contacter sur WhatsApp
                </a>
                <?php endif; ?>
                <a href="/index.php" class="panier-btn panier-btn--primary">Retour à la boutique</a>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>

</html>
