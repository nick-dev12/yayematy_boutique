<?php
/**
 * Page des commandes livrées
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/../includes/session_user.php';
session_start_persistent();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header('Location: connexion.php');
    exit;
}

require_once __DIR__ . '/../models/model_commandes.php';
require_once __DIR__ . '/../models/model_commandes_personnalisees.php';
require_once __DIR__ . '/../includes/site_brand.php';

$commandes = get_commandes_by_user($_SESSION['user_id']);
$commandes_livrees = array_filter($commandes, function ($commande) {
    return $commande['statut'] === 'livree';
});

$commandes_perso_terminees = get_commandes_personnalisees_by_user($_SESSION['user_id'], 'terminee');
$statuts_labels = get_statuts_commande_personnalisee();

$nb_commandes_livrees = count($commandes_livrees);
$nb_commandes_perso_terminees = count($commandes_perso_terminees);

$montant_total_livre = 0;
foreach ($commandes_livrees as $commande_livree) {
    $montant_total_livre += (float) $commande_livree['montant_total'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../includes/asset_version.php'; ?>
    <?php include __DIR__ . '/../includes/pwa_meta.php'; ?>
    <title>Commandes livrées — <?php echo htmlspecialchars(site_brand_name()); ?></title>
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/user-dashboard.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/user-mon-compte.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/user-mes-commandes.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/user-produits-livres.css<?php echo asset_version_query(); ?>">
</head>
<body class="user-page-produits-livres">
    <?php include 'includes/user_nav.php'; ?>

    <div class="account-hub">

        <header class="account-hero account-hero--delivered">
            <div class="account-hero__inner">
                <div class="account-hero__profile">
                    <span class="account-hero__avatar account-hero__avatar--success" aria-hidden="true"><i class="fas fa-check-circle"></i></span>
                    <div class="account-hero__intro">
                        <p class="account-hero__eyebrow">
                            <i class="fa-solid fa-box" aria-hidden="true"></i>
                            Livraisons · <?php echo htmlspecialchars(site_brand_name_market()); ?>
                        </p>
                        <h1 class="account-hero__title">Commandes <span>reçues</span></h1>
                        <p class="account-hero__subtitle">Toutes les commandes que vous avez confirmées comme reçues.</p>
                    </div>
                </div>
                <div class="account-hero__meta account-hero__meta--success">
                    <span class="account-hero__count"><?php echo (int) $nb_commandes_livrees; ?></span>
                    <span class="account-hero__count-label">reçue<?php echo $nb_commandes_livrees > 1 ? 's' : ''; ?></span>
                </div>
            </div>
            <div class="account-hero__actions">
                <a href="/produits.php" class="account-btn account-btn--primary">
                    <i class="fas fa-store" aria-hidden="true"></i>
                    Commander à nouveau
                </a>
                <a href="mes-commandes.php" class="account-btn account-btn--outline">
                    <i class="fas fa-shopping-bag" aria-hidden="true"></i>
                    Commandes actives
                </a>
                <a href="mon-compte.php" class="account-btn account-btn--ghost">
                    <i class="fas fa-home" aria-hidden="true"></i>
                    Mon compte
                </a>
            </div>
        </header>

        <section class="account-stats orders-stats" aria-label="Statistiques livraisons">
            <div class="account-stat account-stat--actif orders-stat orders-stat--delivered">
                <span class="account-stat__icon"><i class="fas fa-box" aria-hidden="true"></i></span>
                <span class="account-stat__value"><?php echo (int) $nb_commandes_livrees; ?></span>
                <span class="account-stat__label">Reçues</span>
            </div>
            <div class="account-stat account-stat--panier orders-stat">
                <span class="account-stat__icon"><i class="fas fa-coins" aria-hidden="true"></i></span>
                <span class="account-stat__value orders-stat__value--sm"><?php echo number_format($montant_total_livre, 0, ',', ' '); ?></span>
                <span class="account-stat__label">Montant total FCFA</span>
            </div>
        </section>

        <section class="account-block" aria-label="Commandes reçues">
            <header class="account-block__head">
                <div class="account-block__head-text">
                    <h2><i class="fa-solid fa-truck-fast" aria-hidden="true"></i> Mes commandes reçues</h2>
                    <p>Retrouvez le détail de chaque livraison confirmée.</p>
                </div>
                <a href="mon-compte.php" class="account-block__link">
                    Voir les produits <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </header>

            <?php if (empty($commandes_livrees)): ?>
            <div class="account-empty">
                <span class="account-empty__icon" aria-hidden="true"><i class="fas fa-box-open"></i></span>
                <h3>Aucune commande livrée</h3>
                <p>Vos commandes reçues apparaîtront ici après confirmation de réception.</p>
                <a href="mes-commandes.php" class="account-btn account-btn--primary">
                    <i class="fas fa-shopping-bag" aria-hidden="true"></i>
                    Voir mes commandes
                </a>
            </div>
            <?php else: ?>
            <div class="orders-grid">
                <?php foreach ($commandes_livrees as $commande): ?>
                <article class="order-card order-card--delivered">
                    <header class="order-card__head">
                        <div class="order-card__info">
                            <p class="order-card__ref"><?php echo htmlspecialchars($commande['numero_commande']); ?></p>
                            <p class="order-card__date">
                                <i class="far fa-calendar" aria-hidden="true"></i>
                                <?php echo date('d/m/Y à H:i', strtotime($commande['date_commande'])); ?>
                            </p>
                        </div>
                        <span class="order-card__badge order-card__badge--livree">
                            <i class="fas fa-check-circle" aria-hidden="true"></i> Reçu
                        </span>
                    </header>

                    <dl class="order-card__details">
                        <div class="order-card__detail">
                            <dt>Montant</dt>
                            <dd><?php echo number_format((float) $commande['montant_total'], 0, ',', ' '); ?> FCFA</dd>
                        </div>
                        <div class="order-card__detail">
                            <dt>Téléphone</dt>
                            <dd><?php echo htmlspecialchars($commande['telephone_livraison']); ?></dd>
                        </div>
                        <div class="order-card__detail order-card__detail--full">
                            <dt>Adresse</dt>
                            <dd><?php echo htmlspecialchars($commande['adresse_livraison']); ?></dd>
                        </div>
                        <?php if (!empty($commande['date_livraison'])): ?>
                        <div class="order-card__detail">
                            <dt>Date livraison</dt>
                            <dd><?php echo date('d/m/Y', strtotime($commande['date_livraison'])); ?></dd>
                        </div>
                        <?php endif; ?>
                    </dl>

                    <div class="order-card__actions">
                        <a href="commande-categorie.php?commande_id=<?php echo (int) $commande['id']; ?>"
                            class="order-card__btn order-card__btn--primary">
                            <i class="fas fa-eye" aria-hidden="true"></i> Voir les produits reçus
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>

        <section class="account-block account-block--perso" aria-label="Commandes personnalisées reçues">
            <header class="account-block__head">
                <div class="account-block__head-text">
                    <h2><i class="fa-solid fa-palette" aria-hidden="true"></i> Demandes personnalisées reçues</h2>
                    <p>Vos créations sur mesure déjà livrées.</p>
                </div>
                <a href="/commande-personnalisee.php" class="account-block__link">
                    Nouvelle demande <i class="fas fa-plus" aria-hidden="true"></i>
                </a>
            </header>

            <?php if (empty($commandes_perso_terminees)): ?>
            <div class="account-empty account-empty--compact">
                <span class="account-empty__icon" aria-hidden="true"><i class="fas fa-palette"></i></span>
                <h3>Aucune demande reçue</h3>
                <p>Créez une commande personnalisée pour un produit unique.</p>
                <a href="/commande-personnalisee.php" class="account-btn account-btn--primary">
                    <i class="fas fa-palette" aria-hidden="true"></i>
                    Faire une demande
                </a>
            </div>
            <?php else: ?>
            <div class="orders-grid">
                <?php foreach ($commandes_perso_terminees as $cp): ?>
                <article class="order-card order-card--perso order-card--delivered">
                    <header class="order-card__head">
                        <div class="order-card__info">
                            <p class="order-card__ref">Demande #<?php echo (int) $cp['id']; ?></p>
                            <p class="order-card__date">
                                <i class="far fa-calendar" aria-hidden="true"></i>
                                <?php echo date('d/m/Y à H:i', strtotime($cp['date_creation'])); ?>
                            </p>
                        </div>
                        <span class="order-card__badge order-card__badge--terminee">
                            <i class="fas fa-check-circle" aria-hidden="true"></i>
                            <?php echo htmlspecialchars($statuts_labels[$cp['statut']] ?? 'Terminée'); ?>
                        </span>
                    </header>

                    <dl class="order-card__details">
                        <div class="order-card__detail order-card__detail--full">
                            <dt>Description</dt>
                            <dd><?php echo nl2br(htmlspecialchars($cp['description'])); ?></dd>
                        </div>
                        <?php if (!empty($cp['type_produit'])): ?>
                        <div class="order-card__detail">
                            <dt>Type</dt>
                            <dd><?php echo htmlspecialchars($cp['type_produit']); ?></dd>
                        </div>
                        <?php endif; ?>
                    </dl>

                    <div class="order-card__actions">
                        <a href="commande-personnalisee-details.php?id=<?php echo (int) $cp['id']; ?>"
                            class="order-card__btn order-card__btn--primary">
                            <i class="fas fa-eye" aria-hidden="true"></i> Voir les détails
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    </div>

    <?php include 'includes/user_footer.php'; ?>
