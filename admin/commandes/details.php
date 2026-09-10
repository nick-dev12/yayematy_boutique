<?php
require_once __DIR__ . '/../includes/admin_auth.php';
/**
 * Page de détails d'une commande (Admin)
 * Programmation procédurale uniquement
 */

// Récupérer les ID(s) de commande
$commande_ids = [];
if (!empty($_GET['ids'])) {
    $commande_ids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string) $_GET['ids'])))));
} elseif (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    if ($id > 0) {
        $commande_ids = [$id];
    }
}

if (empty($commande_ids)) {
    header('Location: index.php');
    exit;
}

// Récupérer la/les commande(s) et leurs produits
require_once __DIR__ . '/../../models/model_commandes_admin.php';
require_once __DIR__ . '/../../models/model_produits.php';
require_once __DIR__ . '/../../models/model_factures.php';
require_once __DIR__ . '/../../models/model_livreur_tracking.php';
require_once __DIR__ . '/../../includes/format_commande_options.php';
require_once __DIR__ . '/../../includes/site_url.php';
require_once __DIR__ . '/../../includes/site_brand.php';

if (!db_is_available()) {
    $_SESSION['error_message'] = 'Connexion à la base de données impossible. Vérifiez que MySQL est démarré dans XAMPP.';
    header('Location: index.php');
    exit;
}

$commandes_list = [];
foreach ($commande_ids as $cid) {
    $row = get_commande_by_id($cid);
    if ($row) {
        $commandes_list[] = $row;
    }
}

if (empty($commandes_list)) {
    $_SESSION['error_message'] = 'Commande introuvable ou inaccessible (identifiant : ' . implode(', ', $commande_ids) . ').';
    header('Location: index.php');
    exit;
}

$commande_ids = array_map(function ($c) {
    return (int) ($c['id'] ?? 0);
}, $commandes_list);
$ids_param = implode(',', $commande_ids);
$is_grouped = count($commandes_list) > 1;
$commande = $commandes_list[0];
$commande_id = (int) $commande['id'];

$produits = [];
foreach ($commandes_list as $cmd_row) {
    $cid = (int) ($cmd_row['id'] ?? 0);
    $lignes = get_produits_by_commande($cid);
    $lignes = is_array($lignes) ? $lignes : [];
    foreach ($lignes as $ligne) {
        $ligne['_commande_id'] = $cid;
        $ligne['_numero_commande'] = (string) ($cmd_row['numero_commande'] ?? '');
        $produits[] = $ligne;
    }
}

$montant_total_groupe = array_sum(array_map(function ($c) {
    return (float) ($c['montant_total'] ?? 0);
}, $commandes_list));

$numeros_commandes = array_values(array_filter(array_map(function ($c) {
    return (string) ($c['numero_commande'] ?? '');
}, $commandes_list)));

$facture = $is_grouped ? false : get_facture_by_commande($commande_id);
$cmd_tracking = livreur_tracking_tables_ready() ? livreur_get_commande_tracking($commande_id) : false;
$cmd_livraison_suivable = !$is_grouped && $cmd_tracking && !empty($cmd_tracking['livreur_id']);

// Vérifier si toutes les commandes sont annulées / livrées / payées
$is_annulee = count(array_filter($commandes_list, function ($c) {
    return ($c['statut'] ?? '') === 'annulee';
})) === count($commandes_list);
$is_livree = count(array_filter($commandes_list, function ($c) {
    return ($c['statut'] ?? '') === 'livree';
})) === count($commandes_list);
$is_paye = count(array_filter($commandes_list, function ($c) {
    return ($c['statut'] ?? '') === 'paye';
})) === count($commandes_list);

$details_redirect = 'details.php?ids=' . rawurlencode($ids_param);

// Traiter les actions de statut (un seul traitement pour tout le groupe)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !$is_annulee && !$is_livree && !$is_paye) {
    $statut_mis_a_jour = null;

    if (isset($_POST['prendre_en_charge'])) {
        if (update_commandes_statut_batch($commande_ids, 'prise_en_charge')) {
            $statut_mis_a_jour = 'prise_en_charge';
        }
    } elseif (isset($_POST['expedier'])) {
        if (update_commandes_statut_batch($commande_ids, 'livraison_en_cours')) {
            $statut_mis_a_jour = 'livraison_en_cours';
        }
    } elseif (isset($_POST['changer_statut'])) {
        $nouveau_statut = $_POST['statut'] ?? '';
        if (in_array($nouveau_statut, ['en_attente', 'prise_en_charge', 'en_preparation', 'livraison_en_cours', 'paye', 'annulee'], true)) {
            if (update_commandes_statut_batch($commande_ids, $nouveau_statut)) {
                $statut_mis_a_jour = $nouveau_statut;
            } else {
                $_SESSION['error_message'] = 'Impossible de mettre à jour le statut. Vérifiez que la migration "add_statut_paye_commandes" a été exécutée et que les commandes contiennent des produits.';
            }
        }
    }

    if ($statut_mis_a_jour !== null) {
        $nb = count($commande_ids);
        $_SESSION['success_message'] = $nb > 1
            ? 'Statut mis à jour pour les ' . $nb . ' commandes du groupe.'
            : 'Statut de la commande mis à jour avec succès. Le client sera notifié s\'il est connecté et a activé les notifications.';
        header('Location: ' . $details_redirect);
        exit;
    }
}

require_once __DIR__ . '/../../includes/geo_location_service.php';
$mode_livraison = (string) ($commande['mode_livraison'] ?? 'livraison');
$is_retrait = ($mode_livraison === 'retrait');
$geo_cmd_lat = geo_parse_coord($commande['delivery_latitude'] ?? null);
$geo_cmd_lng = geo_parse_coord($commande['delivery_longitude'] ?? null);
$has_geo_client = !$is_retrait && geo_coords_valid($geo_cmd_lat, $geo_cmd_lng);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <?php include __DIR__ . '/../../includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_grouped ? 'Commandes groupées' : 'Détails Commande #' . htmlspecialchars($commande['numero_commande'] ?? ''); ?> - Administration</title>
    <?php require_once __DIR__ . '/../../includes/asset_version.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-dashboard-home.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-commandes-details.css'); ?>">
    <?php if ($has_geo_client): ?>
    <link rel="stylesheet" href="<?php echo asset_url('/css/platform-share-modal.css'); ?>">
    <?php endif; ?>
</head>

<body class="page-commande-details">
    <?php include '../includes/nav.php'; ?>

    <?php
    $statut_cmd = (string) ($commande['statut'] ?? 'en_attente');
    $statut_display = admin_commande_statut_label($statut_cmd);
    $mode_label = admin_commande_mode_label($mode_livraison);
    $client_nom_complet = trim(
        trim((string) ($commande['user_prenom'] ?? $commande['client_prenom'] ?? '')) . ' ' .
        trim((string) ($commande['user_nom'] ?? $commande['client_nom'] ?? ''))
    );
    if ($client_nom_complet === '') {
        $client_nom_complet = '—';
    }
    ?>

    <div class="contents-container prod-catalog-hub">

        <header class="prod-catalog-hero">
            <div class="prod-catalog-hero__inner">
                <div class="prod-catalog-hero__content">
                    <p class="prod-catalog-hero__eyebrow">
                        <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                        Commande · <?php echo htmlspecialchars(site_brand_name_market()); ?>
                    </p>
                    <h1 class="prod-catalog-hero__title">
                        <?php if ($is_grouped): ?>
                            <?php echo htmlspecialchars($client_nom_complet); ?>
                        <?php else: ?>
                            #<?php echo htmlspecialchars($commande['numero_commande'] ?? ''); ?>
                        <?php endif; ?>
                    </h1>
                    <p class="prod-catalog-hero__subtitle">
                        <?php if ($is_grouped): ?>
                            <?php echo count($commandes_list); ?> commande<?php echo count($commandes_list) > 1 ? 's' : ''; ?> regroupées
                            · <?php echo htmlspecialchars(implode(', ', array_map(function ($n) {
                                return '#' . $n;
                            }, $numeros_commandes))); ?>
                        <?php else: ?>
                            Passée le <?php echo date('d/m/Y à H:i', strtotime($commande['date_commande'])); ?>
                        <?php endif; ?>
                    </p>
                    <div class="prod-catalog-hero__actions">
                        <?php if ($facture): ?>
                        <a href="facture.php?id=<?php echo (int) $facture['id']; ?>" class="btn-primary" target="_blank">
                            <i class="fas fa-file-invoice"></i> Voir la facture
                        </a>
                        <?php else: ?>
                        <a href="generer_facture.php?id=<?php echo $commande_id; ?>" class="btn-primary">
                            <i class="fas fa-file-invoice"></i> Générer facture
                        </a>
                        <?php endif; ?>
                        <?php if ($cmd_livraison_suivable): ?>
                        <a href="../livreurs/suivi.php?commande_id=<?php echo (int) $commande_id; ?>&amp;regarder=1" class="dash-btn-outline">
                            <i class="fas fa-map-location-dot"></i> Suivre livraison
                        </a>
                        <?php endif; ?>
                        <a href="index.php" class="dash-btn-outline">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>
                <div class="prod-catalog-hero__meta">
                    <span class="prod-catalog-hero__count"><?php echo number_format($montant_total_groupe, 0, ',', ' '); ?></span>
                    <span class="prod-catalog-hero__count-label">FCFA</span>
                    <span class="prod-catalog-hero__badge commande-statut statut-<?php echo htmlspecialchars($statut_cmd); ?>">
                        <?php echo htmlspecialchars($statut_display); ?>
                    </span>
                </div>
            </div>
        </header>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="prod-catalog-flash message success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo htmlspecialchars($_SESSION['success_message'] ?? '');
            unset($_SESSION['success_message']); ?></span>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="prod-catalog-flash message error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($_SESSION['error_message'] ?? '');
            unset($_SESSION['error_message']); ?></span>
        </div>
    <?php endif; ?>

        <section class="prod-catalog-stats prod-catalog-stats--3" aria-label="Résumé commande">
            <article class="prod-stat prod-stat--money">
                <span class="prod-stat__icon"><i class="fa-solid fa-coins"></i></span>
                <div>
                    <p class="prod-stat__label">Montant total</p>
                    <p class="prod-stat__value prod-stat__value--sm"><?php echo number_format($montant_total_groupe, 0, ',', ' '); ?> F</p>
                </div>
            </article>
            <article class="prod-stat prod-stat--total">
                <span class="prod-stat__icon"><i class="fa-solid fa-box"></i></span>
                <div>
                    <p class="prod-stat__label">Produits</p>
                    <p class="prod-stat__value"><?php echo count($produits); ?></p>
                </div>
            </article>
            <article class="prod-stat prod-stat--warn">
                <span class="prod-stat__icon"><i class="fa-solid fa-truck"></i></span>
                <div>
                    <p class="prod-stat__label">Statut</p>
                    <p class="prod-stat__value prod-stat__value--sm"><?php echo htmlspecialchars($statut_display); ?></p>
                </div>
            </article>
        </section>

        <div class="cmd-detail-grid">
            <div class="cmd-detail-panel">
                <h3><i class="fas fa-user"></i> Informations client</h3>
                <div class="cmd-detail-row">
                    <label>Nom complet</label>
                    <div class="value"><?php echo htmlspecialchars($client_nom_complet); ?></div>
                </div>
                <div class="cmd-detail-row">
                    <label>Email</label>
                    <div class="value"><?php echo htmlspecialchars($commande['user_email'] ?? '—'); ?></div>
                </div>
                <div class="cmd-detail-row">
                    <label>Téléphone</label>
                    <div class="value"><?php echo htmlspecialchars($commande['user_telephone'] ?? '—'); ?></div>
                </div>
            </div>

            <div class="cmd-detail-panel">
                <h3><i class="fas fa-map-marker-alt"></i> Livraison</h3>
                <div class="cmd-detail-row">
                    <label>Adresse</label>
                    <div class="value"><?php echo nl2br(htmlspecialchars($commande['adresse_livraison'] ?? '')); ?></div>
                </div>
                <div class="cmd-detail-row">
                    <label>Mode de réception</label>
                    <div class="value">
                        <span class="cmd-detail-mode cmd-detail-mode--<?php echo htmlspecialchars($mode_livraison); ?>">
                            <i class="fas fa-<?php echo $is_retrait ? 'store' : 'motorcycle'; ?>" aria-hidden="true"></i>
                            <?php echo htmlspecialchars($mode_label); ?>
                        </span>
                    </div>
                </div>
                <?php if ($has_geo_client): ?>
                <div class="cmd-detail-row cmd-detail-row--geo">
                    <label>Localisation client</label>
                    <div class="value">
                        <span class="cmd-detail-geo-coords">
                            <?php echo htmlspecialchars(number_format($geo_cmd_lat, 6, '.', '') . ', ' . number_format($geo_cmd_lng, 6, '.', '')); ?>
                        </span>
                        <?php
                        $geo_nav_lat = $geo_cmd_lat;
                        $geo_nav_lng = $geo_cmd_lng;
                        $geo_nav_label = 'Commande #' . (string) ($commande['numero_commande'] ?? '');
                        $geo_nav_wrap_class = 'geo-nav-apps geo-nav-apps--panel';
                        include __DIR__ . '/../../includes/partials/geo_nav_apps_buttons.php';
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($commande['frais_livraison'])): ?>
                <div class="cmd-detail-row">
                    <label>Frais de livraison</label>
                    <div class="value"><?php echo number_format($commande['frais_livraison'], 0, ',', ' '); ?> FCFA</div>
                </div>
                <?php endif; ?>
                <?php if ($commande['date_livraison']): ?>
                <div class="cmd-detail-row">
                    <label>Date livraison</label>
                    <div class="value"><?php echo date('d/m/Y à H:i', strtotime($commande['date_livraison'])); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <section class="prod-catalog-main" aria-label="Produits commandés">
            <header class="prod-catalog-main__head">
                <div class="prod-catalog-main__head-text">
                    <h2><i class="fa-solid fa-box-open"></i> Produits commandés</h2>
                    <p class="prod-catalog-main__filter-hint"><?php echo count($produits); ?> article<?php echo count($produits) > 1 ? 's' : ''; ?><?php echo $is_grouped ? ' · ' . count($commandes_list) . ' commande(s)' : ''; ?>.</p>
                </div>
            </header>

            <div class="cmd-prod-list">
            <?php
            $dernier_cmd_id = null;
            foreach ($produits as $produit):
                $cmd_id_ligne = (int) ($produit['_commande_id'] ?? 0);
                if ($is_grouped && $cmd_id_ligne !== $dernier_cmd_id):
                    $dernier_cmd_id = $cmd_id_ligne;
            ?>
            <div class="cmd-prod-order-head">
                <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                Commande #<?php echo htmlspecialchars($produit['_numero_commande'] ?? ''); ?>
            </div>
            <?php endif; ?>
                <?php $img_src = !empty($produit['image_afficher']) ? $produit['image_afficher'] : ($produit['image_principale'] ?? ''); ?>
                <?php $nom_affichage = !empty($produit['variante_nom']) ? $produit['produit_nom'] . ' → ' . $produit['variante_nom'] : ($produit['produit_nom'] ?? ''); ?>
                <article class="cmd-prod-item">
                    <img src="<?php echo upload_public_url(htmlspecialchars($img_src ?? '', ENT_QUOTES, 'UTF-8')); ?>"
                        alt="<?php echo htmlspecialchars($nom_affichage ?? ''); ?>"
                        onerror="this.src='<?php echo htmlspecialchars(public_url('/image/produit1.jpg'), ENT_QUOTES, 'UTF-8'); ?>'">
                    <div>
                        <h4 class="cmd-prod-item__name"><?php echo htmlspecialchars($nom_affichage ?? ''); ?></h4>
                        <div class="cmd-prod-item__meta">
                            <div>Quantité : <?php echo (int) $produit['quantite']; ?></div>
                            <div>Prix unitaire : <?php echo number_format($produit['prix_unitaire'], 0, ',', ' '); ?> FCFA</div>
                        </div>
                        <?php if (!empty($produit['couleur']) || !empty($produit['poids']) || !empty($produit['taille']) || !empty($produit['variante_nom']) || (!empty($produit['surcout_poids']) && $produit['surcout_poids'] > 0) || (!empty($produit['surcout_taille']) && $produit['surcout_taille'] > 0)): ?>
                        <div class="produit-options-detail">
                            <?php if (!empty($produit['variante_nom'])): ?>
                            <div class="option-detail option-variante">
                                <span class="option-label">Variante:</span>
                                <span class="option-value"><?php echo htmlspecialchars($produit['variante_nom'] ?? ''); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($produit['couleur'])): ?>
                            <?php
                            $hex = trim($produit['couleur']);
                            $is_hex = preg_match('/^#[0-9A-Fa-f]{6}$/', $hex);
                            $nom_couleur = format_couleur_commande($hex);
                            ?>
                            <div class="option-detail option-couleur">
                                <span class="option-label">Couleur:</span>
                                <?php if ($is_hex): ?>
                                <span class="couleur-swatch-large" style="background-color:<?php echo htmlspecialchars($hex ?? ''); ?>;" title="<?php echo htmlspecialchars($hex ?? ''); ?>"></span>
                                <?php endif; ?>
                                <span class="option-value"><?php echo htmlspecialchars($nom_couleur ?? ''); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php 
                            $poids_raw = $produit['poids'] ?? '';
                            $taille_raw = $produit['taille'] ?? '';
                            $surcout_p = isset($produit['surcout_poids']) ? (float)$produit['surcout_poids'] : 0;
                            $surcout_t = isset($produit['surcout_taille']) ? (float)$produit['surcout_taille'] : 0;
                            $poids_lignes = parse_poids_taille_commande($poids_raw, $surcout_p);
                            $taille_lignes = parse_poids_taille_commande($taille_raw, $surcout_t);
                            $afficher_poids = !empty($poids_lignes);
                            $afficher_taille = !empty($taille_lignes);
                            ?>
                            <?php if ($afficher_poids): ?>
                            <div class="option-detail option-poids">
                                <span class="option-label">Poids:</span>
                                <div class="option-value options-lignes">
                                    <?php foreach ($poids_lignes as $opt): ?>
                                    <div class="option-ligne"><?php 
                                    echo htmlspecialchars($opt['v'] ?? ''); 
                                    if (($opt['s'] ?? 0) > 0) echo ' (poids +' . number_format($opt['s'], 0, ',', ' ') . ' FCFA)';
                                    ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if ($afficher_taille): ?>
                            <div class="option-detail option-taille">
                                <span class="option-label">Taille:</span>
                                <div class="option-value options-lignes">
                                    <?php foreach ($taille_lignes as $opt): ?>
                                    <div class="option-ligne"><?php 
                                    echo htmlspecialchars($opt['v'] ?? ''); 
                                    if (($opt['s'] ?? 0) > 0) echo ' (taille +' . number_format($opt['s'], 0, ',', ' ') . ' FCFA)';
                                    ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="cmd-prod-item__total">
                        <?php echo number_format($produit['prix_total'], 0, ',', ' '); ?> FCFA
                    </div>
                </article>
            <?php endforeach; ?>

            <div class="cmd-prod-total-box">
                <?php
                $sous_total = array_sum(array_column($produits, 'prix_total'));
                $frais = array_sum(array_map(function ($c) {
                    return (float) ($c['frais_livraison'] ?? 0);
                }, $commandes_list));
                ?>
                <?php if ($frais > 0): ?>
                <p>Sous-total produits : <?php echo number_format($sous_total, 0, ',', ' '); ?> FCFA</p>
                <p>Frais de livraison : <?php echo number_format($frais, 0, ',', ' '); ?> FCFA</p>
                <?php endif; ?>
                <h3>Total : <span class="total-value"><?php echo number_format($montant_total_groupe, 0, ',', ' '); ?> FCFA</span></h3>
            </div>
            </div>
        </section>

        <section class="prod-catalog-main prod-catalog-main--alt" aria-label="Statut commande">
            <header class="prod-catalog-main__head">
                <div class="prod-catalog-main__head-text">
                    <h2><i class="fa-solid fa-tasks"></i> Statut<?php echo $is_grouped ? ' des commandes' : ' de la commande'; ?></h2>
                    <?php if ($is_grouped): ?>
                    <p class="prod-catalog-main__filter-hint">Une action met à jour les <?php echo count($commandes_list); ?> commandes du groupe.</p>
                    <?php endif; ?>
                </div>
            </header>

        <?php if ($is_annulee): ?>
            <div class="cmd-status-box cmd-status-box--danger">
                <h3><i class="fas fa-ban"></i> Commande<?php echo $is_grouped ? 's annulées' : ' annulée'; ?></h3>
                <p>Ces commandes ont été annulées. Les actions de modification ne sont pas disponibles.</p>
            </div>
        <?php elseif ($is_livree): ?>
            <div class="cmd-status-box cmd-status-box--ok">
                <h3><i class="fas fa-check-circle"></i> Commande<?php echo $is_grouped ? 's livrées' : ' livrée'; ?></h3>
                <p>Le client a confirmé la réception. Les commandes sont terminées.</p>
            </div>
        <?php elseif ($is_paye): ?>
            <div class="cmd-status-box cmd-status-box--ok">
                <h3><i class="fas fa-money-bill-wave"></i> Commande<?php echo $is_grouped ? 's payées' : ' payée'; ?></h3>
                <p>Les commandes ont été marquées comme payées. Le stock a été décrémenté.</p>
            </div>
        <?php else: ?>
            <?php
            $statuts_groupe = array_unique(array_map(function ($c) {
                return (string) ($c['statut'] ?? 'en_attente');
            }, $commandes_list));
            $statut_aff = count($statuts_groupe) === 1 ? $statuts_groupe[0] : ($commande['statut'] ?? 'en_attente');
            $statut_display_groupe = count($statuts_groupe) > 1
                ? 'Statuts mixtes'
                : admin_commande_statut_label($statut_aff);
            ?>
            <div class="cmd-status-form">
                <div class="form-group">
                    <label>Statut actuel</label>
                    <div class="statut-current-wrap">
                        <span class="commande-statut statut-<?php echo htmlspecialchars($statut_aff); ?>">
                            <?php echo htmlspecialchars($statut_display_groupe); ?>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <?php if (in_array($statut_aff, ['en_attente', 'confirmee'], true) || count($statuts_groupe) > 1): ?>
                        <form method="POST" action="">
                            <button type="submit" name="prendre_en_charge" class="btn-primary btn-prise-charge">
                                <i class="fas fa-hand-paper"></i> Prendre en charge<?php echo $is_grouped ? ' le groupe' : ' la commande'; ?>
                            </button>
                        </form>

                    <?php elseif ($statut_aff === 'prise_en_charge'): ?>
                        <form method="POST" action="">
                            <button type="submit" name="expedier" class="btn-primary btn-expedier">
                                <i class="fas fa-shipping-fast"></i> Mettre en livraison
                            </button>
                        </form>

                    <?php elseif ($statut_aff === 'livraison_en_cours'): ?>
                        <div class="cmd-status-box cmd-status-box--warn">
                            <p><i class="fas fa-truck"></i> Commande en cours de livraison</p>
                            <p class="sub">Vous pouvez changer le statut manuellement ci-dessous pour la marquer comme « Payée » (décrémente le stock).</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="cmd-actions-divider">
                    <h3>Changer le statut manuellement</h3>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="statut">Nouveau statut</label>
                            <select id="statut" name="statut" required>
                                <option value="en_attente" <?php echo $statut_aff === 'en_attente' ? 'selected' : ''; ?>>En Attente</option>
                                <option value="prise_en_charge" <?php echo $statut_aff === 'prise_en_charge' ? 'selected' : ''; ?>>Prise en charge</option>
                                <option value="en_preparation" <?php echo $statut_aff === 'en_preparation' ? 'selected' : ''; ?>>En Préparation</option>
                                <option value="livraison_en_cours" <?php echo $statut_aff === 'livraison_en_cours' ? 'selected' : ''; ?>>Livraison en cours</option>
                                <option value="paye" <?php echo $statut_aff === 'paye' ? 'selected' : ''; ?>>Payée (décrémente le stock)</option>
                                <option value="annulee" <?php echo $statut_aff === 'annulee' ? 'selected' : ''; ?>>Annulée</option>
                            </select>
                        </div>
                        <?php if ($commande['notes']): ?>
                            <div class="form-group">
                                <label>Notes</label>
                                <div class="notes-box">
                                    <?php echo nl2br(htmlspecialchars($commande['notes'] ?? '')); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <button type="submit" name="changer_statut" class="btn-primary">
                            <i class="fas fa-save"></i> Mettre à jour le statut
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        </section>
    </div>

    <?php if ($has_geo_client): ?>
    <?php require __DIR__ . '/../../includes/partials/platform_share_modal.php'; ?>
    <script src="<?php echo asset_url('/js/platform-share-modal.js'); ?>"></script>
    <script src="<?php echo asset_url('/js/geo-nav-apps.js'); ?>"></script>
    <?php endif; ?>

    <?php include '../includes/footer.php'; ?>