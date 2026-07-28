<?php
require_once __DIR__ . '/../../includes/session_user.php';
/**
 * Page de détails d'une commande (Admin)
 * Programmation procédurale uniquement
 */

session_start_persistent();

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_email'])) {
    header('Location: ../login.php');
    exit;
}

// Récupérer l'ID de la commande
$commande_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($commande_id <= 0) {
    header('Location: index.php');
    exit;
}

// Récupérer la commande et ses produits
require_once __DIR__ . '/../../models/model_commandes_admin.php';
require_once __DIR__ . '/../../models/model_produits.php';
require_once __DIR__ . '/../../models/model_factures.php';
require_once __DIR__ . '/../../models/model_livreur_tracking.php';
require_once __DIR__ . '/../../includes/format_commande_options.php';
require_once __DIR__ . '/../../includes/site_brand.php';
$commande = get_commande_by_id($commande_id);
$produits = get_produits_by_commande($commande_id);
$produits = is_array($produits) ? $produits : [];
$facture = get_facture_by_commande($commande_id);
$cmd_tracking = livreur_tracking_tables_ready() ? livreur_get_commande_tracking($commande_id) : false;
$cmd_livraison_suivable = $cmd_tracking && !empty($cmd_tracking['livreur_id']);

if (!$commande) {
    header('Location: index.php');
    exit;
}

// Vérifier si la commande est annulée ou livrée (pas de modification possible)
$is_annulee = $commande['statut'] === 'annulee';
$is_livree = $commande['statut'] === 'livree';
$is_paye = $commande['statut'] === 'paye';

// Traiter les actions de statut (uniquement si la commande n'est pas annulée)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_annulee) {
    $statut_mis_a_jour = null;

    if (isset($_POST['prendre_en_charge'])) {
        if (update_commande_statut($commande_id, 'prise_en_charge')) {
            $statut_mis_a_jour = 'prise_en_charge';
        }
    } elseif (isset($_POST['expedier'])) {
        if (update_commande_statut($commande_id, 'livraison_en_cours')) {
            $statut_mis_a_jour = 'livraison_en_cours';
        }
    } elseif (isset($_POST['changer_statut'])) {
        $nouveau_statut = $_POST['statut'] ?? '';
        if (in_array($nouveau_statut, ['en_attente', 'prise_en_charge', 'en_preparation', 'livraison_en_cours', 'paye', 'annulee'])) {
            if (update_commande_statut($commande_id, $nouveau_statut)) {
                $statut_mis_a_jour = $nouveau_statut;
            } else {
                $_SESSION['error_message'] = 'Impossible de mettre à jour le statut. Vérifiez que la migration "add_statut_paye_commandes" a été exécutée et que la commande contient des produits.';
            }
        }
    }

    if ($statut_mis_a_jour !== null) {
        $_SESSION['success_message'] = 'Statut de la commande mis à jour avec succès. Le client sera notifié s\'il est connecté et a activé les notifications.';
        header('Location: details.php?id=' . $commande_id);
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
    <title>Détails Commande #<?php echo htmlspecialchars($commande['numero_commande'] ?? ''); ?> - Administration</title>
    <?php require_once __DIR__ . '/../../includes/asset_version.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/admin-dashboard.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/admin-dashboard-home.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/admin-commandes-details.css<?php echo asset_version_query(); ?>">
    <?php if ($has_geo_client): ?>
    <link rel="stylesheet" href="/css/platform-share-modal.css<?php echo asset_version_query(); ?>">
    <?php endif; ?>
</head>

<body class="page-commande-details">
    <?php include '../includes/nav.php'; ?>

    <?php
    $statut_cmd = (string) ($commande['statut'] ?? 'en_attente');
    $statut_display = admin_commande_statut_label($statut_cmd);
    $mode_label = admin_commande_mode_label($mode_livraison);
    $client_nom_complet = trim(($commande['user_prenom'] ?? '') . ' ' . ($commande['user_nom'] ?? ''));
    ?>

    <div class="contents-container prod-catalog-hub">

        <header class="prod-catalog-hero">
            <div class="prod-catalog-hero__inner">
                <div class="prod-catalog-hero__content">
                    <p class="prod-catalog-hero__eyebrow">
                        <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                        Commande · <?php echo htmlspecialchars(site_brand_name_market()); ?>
                    </p>
                    <h1 class="prod-catalog-hero__title">#<?php echo htmlspecialchars($commande['numero_commande'] ?? ''); ?></h1>
                    <p class="prod-catalog-hero__subtitle">Passée le <?php echo date('d/m/Y à H:i', strtotime($commande['date_commande'])); ?></p>
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
                    <span class="prod-catalog-hero__count"><?php echo number_format((float) $commande['montant_total'], 0, ',', ' '); ?></span>
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
                    <p class="prod-stat__value prod-stat__value--sm"><?php echo number_format((float) $commande['montant_total'], 0, ',', ' '); ?> F</p>
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
                    <p class="prod-catalog-main__filter-hint"><?php echo count($produits); ?> article<?php echo count($produits) > 1 ? 's' : ''; ?> dans cette commande.</p>
                </div>
            </header>

            <div class="cmd-prod-list">
            <?php foreach ($produits as $produit): ?>
                <?php $img_src = !empty($produit['image_afficher']) ? $produit['image_afficher'] : ($produit['image_principale'] ?? ''); ?>
                <?php $nom_affichage = !empty($produit['variante_nom']) ? $produit['produit_nom'] . ' → ' . $produit['variante_nom'] : ($produit['produit_nom'] ?? ''); ?>
                <article class="cmd-prod-item">
                    <img src="/upload/<?php echo htmlspecialchars($img_src ?? ''); ?>"
                        alt="<?php echo htmlspecialchars($nom_affichage ?? ''); ?>"
                        onerror="this.src='/image/produit1.jpg'">
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
                $frais = isset($commande['frais_livraison']) ? (float) $commande['frais_livraison'] : 0;
                ?>
                <?php if ($frais > 0): ?>
                <p>Sous-total produits : <?php echo number_format($sous_total, 0, ',', ' '); ?> FCFA</p>
                <p>Frais de livraison : <?php echo number_format($frais, 0, ',', ' '); ?> FCFA</p>
                <?php endif; ?>
                <h3>Total : <span class="total-value"><?php echo number_format($commande['montant_total'], 0, ',', ' '); ?> FCFA</span></h3>
            </div>
        </section>

        <section class="prod-catalog-main prod-catalog-main--alt" aria-label="Statut commande">
            <header class="prod-catalog-main__head">
                <div class="prod-catalog-main__head-text">
                    <h2><i class="fa-solid fa-tasks"></i> Statut de la commande</h2>
                </div>
            </header>

        <?php if ($is_annulee): ?>
            <div class="cmd-status-box cmd-status-box--danger">
                <h3><i class="fas fa-ban"></i> Commande annulée</h3>
                <p>Cette commande a été annulée. Les actions de modification ne sont pas disponibles.</p>
            </div>
        <?php elseif ($is_livree): ?>
            <div class="cmd-status-box cmd-status-box--ok">
                <h3><i class="fas fa-check-circle"></i> Commande livrée</h3>
                <p>Le client a confirmé la réception. La commande est terminée.</p>
            </div>
        <?php elseif ($is_paye): ?>
            <div class="cmd-status-box cmd-status-box--ok">
                <h3><i class="fas fa-money-bill-wave"></i> Commande payée</h3>
                <p>La commande a été marquée comme payée. Le stock a été décrémenté.</p>
            </div>
        <?php else: ?>
            <div class="cmd-status-form">
                <div class="form-group">
                    <label>Statut actuel</label>
                    <div class="statut-current-wrap">
                        <span class="commande-statut statut-<?php echo $commande['statut']; ?>">
                            <?php echo htmlspecialchars($statut_display); ?>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <?php if (in_array($commande['statut'], ['en_attente', 'confirmee'])): ?>
                        <form method="POST" action="">
                            <button type="submit" name="prendre_en_charge" class="btn-primary btn-prise-charge">
                                <i class="fas fa-hand-paper"></i> Prendre en charge la commande
                            </button>
                        </form>

                    <?php elseif ($commande['statut'] == 'prise_en_charge'): ?>
                        <form method="POST" action="">
                            <button type="submit" name="expedier" class="btn-primary btn-expedier">
                                <i class="fas fa-shipping-fast"></i> Mettre en livraison
                            </button>
                        </form>

                    <?php elseif ($commande['statut'] == 'livraison_en_cours'): ?>
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
                                <option value="en_attente" <?php echo $commande['statut'] == 'en_attente' ? 'selected' : ''; ?>>En Attente</option>
                                <option value="prise_en_charge" <?php echo $commande['statut'] == 'prise_en_charge' ? 'selected' : ''; ?>>Prise en charge</option>
                                <option value="en_preparation" <?php echo $commande['statut'] == 'en_preparation' ? 'selected' : ''; ?>>En Préparation</option>
                                <option value="livraison_en_cours" <?php echo $commande['statut'] == 'livraison_en_cours' ? 'selected' : ''; ?>>Livraison en cours</option>
                                <option value="paye" <?php echo $commande['statut'] == 'paye' ? 'selected' : ''; ?>>Payée (décrémente le stock)</option>
                                <option value="annulee" <?php echo $commande['statut'] == 'annulee' ? 'selected' : ''; ?>>Annulée</option>
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
    <script src="/js/platform-share-modal.js<?php echo asset_version_query(); ?>"></script>
    <script src="/js/geo-nav-apps.js<?php echo asset_version_query(); ?>"></script>
    <?php endif; ?>

    <?php include '../includes/footer.php'; ?>