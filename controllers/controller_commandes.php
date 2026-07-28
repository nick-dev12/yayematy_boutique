<?php
/**
 * Contrôleur pour la gestion des commandes
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/../models/model_commandes.php';
require_once __DIR__ . '/../models/model_panier.php';
require_once __DIR__ . '/../models/model_zones_livraison.php';
require_once __DIR__ . '/../models/model_users.php';
require_once __DIR__ . '/../includes/panier_invite.php';
require_once __DIR__ . '/../includes/guest_checkout.php';

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

/**
 * Traite la création d'une commande
 * @return array Tableau avec 'success', 'message', et éventuellement 'commande_id' et 'numero_commande'
 */
function process_create_commande()
{
    $user_connecte = isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return [
            'success' => false,
            'message' => 'Méthode non autorisée.'
        ];
    }

    $mode_livraison = isset($_POST['mode_livraison']) && $_POST['mode_livraison'] === 'retrait' ? 'retrait' : 'livraison';
    $telephone_raw = trim($_POST['telephone_livraison'] ?? '');
    $telephone_livraison = users_normalize_phone_digits($telephone_raw);

    if ($telephone_raw === '' || $telephone_livraison === '') {
        return [
            'success' => false,
            'message' => 'Le téléphone de livraison est obligatoire.'
        ];
    }

    if (strlen($telephone_livraison) < 8) {
        return [
            'success' => false,
            'message' => 'Le numéro de téléphone semble incomplet.'
        ];
    }

    $zone_livraison_id = isset($_POST['zone_livraison_id']) ? (int) $_POST['zone_livraison_id'] : 0;
    $adresse_livraison = 'À définir';
    $frais_livraison = 0;
    $delivery_lat = null;
    $delivery_lng = null;

    if ($mode_livraison === 'retrait') {
        $adresse_livraison = 'Retrait sur place';
        $zone_livraison_id = null;
        $frais_livraison = 0;
    } else {
        if ($zone_livraison_id <= 0) {
            return [
                'success' => false,
                'message' => 'Veuillez sélectionner une zone de livraison.'
            ];
        }

        $zone = get_zone_livraison_by_id($zone_livraison_id);
        if (!$zone || $zone['statut'] !== 'actif') {
            return [
                'success' => false,
                'message' => 'La zone de livraison sélectionnée n\'est pas valide.'
            ];
        }

        $adresse_livraison = $zone['ville'] . ' - ' . $zone['quartier'];
        $frais_livraison = (float) $zone['prix_livraison'];

        $delivery_lat = isset($_POST['delivery_latitude']) ? $_POST['delivery_latitude'] : null;
        $delivery_lng = isset($_POST['delivery_longitude']) ? $_POST['delivery_longitude'] : null;

        if (!users_coords_valid($delivery_lat, $delivery_lng)) {
            return [
                'success' => false,
                'message' => 'Veuillez confirmer votre position sur la carte pour la livraison.'
            ];
        }

        $delivery_lat = round((float) $delivery_lat, 8);
        $delivery_lng = round((float) $delivery_lng, 8);
    }

    $panier_items = $user_connecte
        ? get_panier_by_user((int) $_SESSION['user_id'])
        : panier_invite_get_items();

    if (empty($panier_items)) {
        return [
            'success' => false,
            'message' => 'Votre panier est vide. Ajoutez des produits avant de passer une commande.'
        ];
    }

    foreach ($panier_items as $item) {
        if ($item['stock'] < $item['quantite']) {
            return [
                'success' => false,
                'message' => 'Le stock disponible pour "' . htmlspecialchars($item['nom']) . '" est insuffisant. Stock disponible: ' . $item['stock']
            ];
        }
    }

    if (!$user_connecte) {
        if (!guest_checkout_has_info()) {
            return [
                'success' => false,
                'message' => 'Veuillez renseigner votre nom et votre téléphone avant de commander.'
            ];
        }

        $guest_info = guest_checkout_get_info();
        $names = guest_checkout_split_name($guest_info['nom']);
        $telephone_commande = strpos($telephone_raw, '+') === 0 ? $telephone_raw : ('+' . $telephone_livraison);
        $manual_items = [];

        foreach ($panier_items as $item) {
            $prix_unitaire = (!empty($item['panier_prix_unitaire']) && $item['panier_prix_unitaire'] > 0)
                ? (float) $item['panier_prix_unitaire']
                : (!empty($item['prix_promotion']) && $item['prix_promotion'] < $item['prix']
                    ? (float) $item['prix_promotion']
                    : (float) $item['prix']);

            $nom_produit = $item['nom'];
            if (!empty($item['panier_variante_nom'])) {
                $nom_produit .= ' → ' . $item['panier_variante_nom'];
            }
            if (!empty(trim($item['panier_couleur'] ?? ''))) {
                $nom_produit .= ' · ' . trim($item['panier_couleur']);
            }
            if (!empty(trim($item['panier_poids'] ?? ''))) {
                $nom_produit .= ' · ' . trim($item['panier_poids']);
            }
            if (!empty(trim($item['panier_taille'] ?? ''))) {
                $nom_produit .= ' · ' . trim($item['panier_taille']);
            }

            $manual_items[] = [
                'produit_id' => (int) $item['id'],
                'quantite' => (int) $item['quantite'],
                'prix_unitaire' => $prix_unitaire,
                'nom_produit' => $nom_produit,
            ];
        }

        $result = create_commande_manuelle(
            $manual_items,
            $names['nom'],
            $names['prenom'],
            $telephone_commande,
            $adresse_livraison,
            null,
            null,
            $zone_livraison_id,
            $frais_livraison,
            [
                'mode_livraison' => $mode_livraison,
                'delivery_latitude' => $delivery_lat,
                'delivery_longitude' => $delivery_lng,
            ]
        );

        if (empty($result['success'])) {
            return [
                'success' => false,
                'message' => $result['error'] ?? 'Une erreur est survenue lors de la création de la commande.'
            ];
        }

        panier_invite_clear();

        return [
            'success' => true,
            'message' => 'Votre commande a été enregistrée avec succès.',
            'commande_id' => $result['commande_id'],
            'numero_commande' => $result['numero_commande'],
            'is_guest' => true,
        ];
    }

    $user_id = (int) $_SESSION['user_id'];

    $choix = [];
    foreach ($panier_items as $item) {
        $panier_id = isset($item['panier_id']) ? (int) $item['panier_id'] : 0;
        if ($panier_id <= 0) {
            continue;
        }

        $couleur = '';
        $poids = '';
        $taille = '';

        if (isset($_POST['choix'][$panier_id]) && is_array($_POST['choix'][$panier_id])) {
            $c = $_POST['choix'][$panier_id];
            $couleur = isset($c['couleur']) ? trim($c['couleur']) : '';
            $poids = isset($c['poids']) ? trim($c['poids']) : '';
            $taille = isset($c['taille']) ? trim($c['taille']) : '';
        }
        if ($couleur === '' && !empty(trim($item['panier_couleur'] ?? ''))) {
            $couleur = trim($item['panier_couleur']);
        }
        if ($poids === '' && !empty(trim($item['panier_poids'] ?? ''))) {
            $poids = trim($item['panier_poids']);
        }
        if ($taille === '' && !empty(trim($item['panier_taille'] ?? ''))) {
            $taille = trim($item['panier_taille']);
        }

        $choix[$panier_id] = ['couleur' => $couleur, 'poids' => $poids, 'taille' => $taille];
    }

    $extra = [
        'mode_livraison' => $mode_livraison,
        'delivery_latitude' => $delivery_lat,
        'delivery_longitude' => $delivery_lng,
    ];

    $result = create_commande(
        $user_id,
        $panier_items,
        $adresse_livraison,
        $telephone_livraison,
        null,
        $zone_livraison_id,
        $frais_livraison,
        $choix,
        $extra
    );

    if ($result === false) {
        return [
            'success' => false,
            'message' => 'Une erreur est survenue lors de la création de la commande. Veuillez réessayer.'
        ];
    }

    if ($result['success']) {
        clear_panier($user_id);

        $sous_total = 0;
        $nombre_articles = 0;
        $produits_email = [];
        foreach ($panier_items as $item) {
            $prix_unitaire = (!empty($item['panier_prix_unitaire']) && $item['panier_prix_unitaire'] > 0)
                ? (float) $item['panier_prix_unitaire']
                : (!empty($item['prix_promotion']) && $item['prix_promotion'] < $item['prix'] ? $item['prix_promotion'] : $item['prix']);
            $prix_total_ligne = $prix_unitaire * $item['quantite'];
            $sous_total += $prix_total_ligne;
            $nombre_articles += $item['quantite'];
            $panier_id = isset($item['panier_id']) ? (int) $item['panier_id'] : 0;
            $c = isset($choix[$panier_id]) ? $choix[$panier_id] : [];
            $nom_affichage = $item['nom'];
            if (!empty($item['panier_variante_nom'])) {
                $nom_affichage .= ' → ' . $item['panier_variante_nom'];
            }
            $produits_email[] = [
                'nom' => $nom_affichage,
                'quantite' => $item['quantite'],
                'prix_unitaire' => $prix_unitaire,
                'prix_total' => $prix_total_ligne,
                'variante_nom' => $item['panier_variante_nom'] ?? '',
                'couleur' => isset($c['couleur']) ? $c['couleur'] : ($item['panier_couleur'] ?? ''),
                'poids' => isset($c['poids']) ? $c['poids'] : ($item['panier_poids'] ?? ''),
                'taille' => isset($c['taille']) ? $c['taille'] : ($item['panier_taille'] ?? ''),
                'surcout_poids' => isset($item['panier_surcout_poids']) ? (float) $item['panier_surcout_poids'] : 0,
                'surcout_taille' => isset($item['panier_surcout_taille']) ? (float) $item['panier_surcout_taille'] : 0
            ];
        }
        $montant_total = $sous_total + $frais_livraison;

        return [
            'success' => true,
            'message' => 'Votre commande a été créée avec succès ! Numéro de commande: ' . $result['numero_commande'],
            'commande_id' => $result['commande_id'],
            'numero_commande' => $result['numero_commande'],
            'email_data' => [
                'numero_commande' => $result['numero_commande'],
                'montant_total' => $montant_total,
                'nombre_articles' => $nombre_articles,
                'telephone_livraison' => $telephone_livraison,
                'adresse_livraison' => $adresse_livraison,
                'produits' => $produits_email
            ]
        ];
    }

    return [
        'success' => false,
        'message' => 'Une erreur est survenue lors de la création de la commande.'
    ];
}
