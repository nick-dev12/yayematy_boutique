<?php
/**
 * Page tableau de bord utilisateur
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/../includes/session_user.php';
session_start_persistent();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header('Location: connexion.php');
    exit;
}

require_once __DIR__ . '/../models/model_users.php';
require_once __DIR__ . '/../includes/site_brand.php';

$user = get_user_by_id($_SESSION['user_id']);

if (!$user) {
    session_destroy();
    header('Location: connexion.php');
    exit;
}

require_once __DIR__ . '/../models/model_commandes.php';
$produits_commandes = get_produits_commandes_by_user($_SESSION['user_id'], 'livree');

require_once __DIR__ . '/../models/model_favoris.php';
require_once __DIR__ . '/../models/model_visites.php';
$nb_commandes = count_commandes_by_user($_SESSION['user_id']);
$nb_panier = count_panier_items_by_user($_SESSION['user_id']);
$nb_favoris = count_favoris_by_user($_SESSION['user_id']);
$nb_visites = count_visites_by_user($_SESSION['user_id']);

$enable_firebase_notifications = true;
$firebase_notify_type = 'user';

$user_prenom = trim((string) ($user['prenom'] ?? ''));
$user_nom = trim((string) ($user['nom'] ?? ''));
$user_initials = strtoupper(
    mb_substr($user_prenom, 0, 1, 'UTF-8') . mb_substr($user_nom, 0, 1, 'UTF-8')
);
if ($user_initials === '') {
    $user_initials = 'YM';
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../includes/asset_version.php'; ?>
    <?php include __DIR__ . '/../includes/pwa_meta.php'; ?>
    <title>Mon Compte — <?php echo htmlspecialchars(site_brand_name()); ?></title>
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/user-dashboard.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/user-mon-compte.css<?php echo asset_version_query(); ?>">
</head>

<body class="user-page-mon-compte">
    <?php include 'includes/user_nav.php'; ?>

    <div class="account-hub">

        <header class="account-hero">
            <div class="account-hero__inner">
                <div class="account-hero__profile">
                    <span class="account-hero__avatar" aria-hidden="true"><?php echo htmlspecialchars($user_initials); ?></span>
                    <div class="account-hero__intro">
                        <p class="account-hero__eyebrow">
                            <i class="fa-solid fa-seedling" aria-hidden="true"></i>
                            Mon espace · <?php echo htmlspecialchars(site_brand_name_market()); ?>
                        </p>
                        <h1 class="account-hero__title">
                            Bonjour, <span><?php echo htmlspecialchars($user_prenom !== '' ? $user_prenom : $user_nom); ?></span>
                        </h1>
                        <p class="account-hero__subtitle"><?php echo htmlspecialchars(site_brand_tagline()); ?></p>
                    </div>
                </div>
                <div class="account-hero__meta">
                    <span class="account-hero__count"><?php echo (int) $nb_commandes; ?></span>
                    <span class="account-hero__count-label">commande<?php echo $nb_commandes > 1 ? 's' : ''; ?></span>
                </div>
            </div>

            <div class="account-hero__actions">
                <a href="/index.php" class="account-btn account-btn--primary">
                    <i class="fas fa-store" aria-hidden="true"></i>
                    Continuer mes achats
                </a>
                <button type="button"
                    id="btn-enable-notifications"
                    class="account-btn account-btn--outline btn-enable-notifications"
                    data-notify-type="user">
                    <i class="fas fa-bell-slash" aria-hidden="true"></i>
                    Activer les notifications
                </button>
                <a href="profil.php" class="account-btn account-btn--ghost">
                    <i class="fas fa-user-pen" aria-hidden="true"></i>
                    Mon profil
                </a>
            </div>
        </header>

        <div id="notify-help-panel" class="account-notify-panel" hidden aria-live="polite">
            <div class="account-notify-panel__icon" aria-hidden="true"><i class="fas fa-circle-info"></i></div>
            <div class="account-notify-panel__body">
                <h4>Autoriser les notifications manuellement</h4>
                <ol>
                    <li>Cliquez sur le <strong>cadenas</strong> (à gauche de l'adresse)</li>
                    <li><strong>Notifications</strong> → choisissez <strong>Autoriser</strong></li>
                    <li>Cliquez sur le bouton ci-dessous</li>
                </ol>
                <button type="button" id="btn-notify-continue" class="account-btn account-btn--primary account-notify-panel__btn">
                    <i class="fas fa-check" aria-hidden="true"></i>
                    J'ai autorisé — continuer
                </button>
            </div>
        </div>

        <section class="account-stats" aria-label="Statistiques du compte">
            <a href="mes-commandes.php" class="account-stat account-stat--commandes">
                <span class="account-stat__icon"><i class="fas fa-shopping-bag" aria-hidden="true"></i></span>
                <span class="account-stat__value"><?php echo (int) $nb_commandes; ?></span>
                <span class="account-stat__label">Commandes</span>
            </a>
            <a href="/panier.php" class="account-stat account-stat--panier">
                <span class="account-stat__icon"><i class="fas fa-shopping-cart" aria-hidden="true"></i></span>
                <span class="account-stat__value"><?php echo (int) $nb_panier; ?></span>
                <span class="account-stat__label">Au panier</span>
            </a>
            <a href="/index.php" class="account-stat account-stat--favoris">
                <span class="account-stat__icon"><i class="fas fa-heart" aria-hidden="true"></i></span>
                <span class="account-stat__value"><?php echo (int) $nb_favoris; ?></span>
                <span class="account-stat__label">Favoris</span>
            </a>
            <a href="produits-visites.php" class="account-stat account-stat--visites">
                <span class="account-stat__icon"><i class="fas fa-eye" aria-hidden="true"></i></span>
                <span class="account-stat__value"><?php echo (int) $nb_visites; ?></span>
                <span class="account-stat__label">Consultés</span>
            </a>
        </section>

        <section class="account-block" aria-label="Produits livrés">
            <header class="account-block__head">
                <div class="account-block__head-text">
                    <h2><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Mes produits livrés</h2>
                    <p>Retrouvez vos achats déjà reçus et recommandez-les en un clic.</p>
                </div>
                <a href="produits-livres.php" class="account-block__link">
                    Tout voir <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </header>

            <?php if (empty($produits_commandes)): ?>
            <div class="account-empty">
                <span class="account-empty__icon" aria-hidden="true"><i class="fas fa-box-open"></i></span>
                <h3>Aucun produit livré</h3>
                <p>Vos produits apparaîtront ici une fois vos commandes livrées.</p>
                <a href="mes-commandes.php" class="account-btn account-btn--primary">
                    <i class="fas fa-shopping-bag" aria-hidden="true"></i>
                    Voir mes commandes
                </a>
            </div>
            <?php else: ?>
            <div class="account-prod-grid">
                <?php foreach ($produits_commandes as $produit): ?>
                    <?php
                    $statut_class = 'account-prod-card__badge--actif';
                    if ($produit['statut'] === 'inactif') {
                        $statut_class = 'account-prod-card__badge--inactif';
                    } elseif ($produit['statut'] === 'rupture_stock') {
                        $statut_class = 'account-prod-card__badge--rupture';
                    }
                    $statut_label = ucfirst(str_replace('_', ' ', $produit['statut']));
                    $image_src = !empty($produit['image_principale'])
                        ? '/upload/' . htmlspecialchars($produit['image_principale'], ENT_QUOTES, 'UTF-8')
                        : '/image/produit1.jpg';
                    ?>
                <article class="account-prod-card">
                    <a href="/produit.php?id=<?php echo (int) $produit['id']; ?>" class="account-prod-card__visual">
                        <img src="<?php echo $image_src; ?>"
                            alt="<?php echo htmlspecialchars($produit['nom']); ?>"
                            loading="lazy"
                            onerror="this.src='/image/produit1.jpg'">
                        <span class="account-prod-card__badge <?php echo $statut_class; ?>"><?php echo htmlspecialchars($statut_label); ?></span>
                    </a>
                    <div class="account-prod-card__body">
                        <p class="account-prod-card__cat"><?php echo htmlspecialchars($produit['categorie_nom'] ?? 'Sans catégorie'); ?></p>
                        <h3 class="account-prod-card__name">
                            <a href="/produit.php?id=<?php echo (int) $produit['id']; ?>"><?php echo htmlspecialchars($produit['nom']); ?></a>
                        </h3>
                        <p class="account-prod-card__price">
                            <?php echo number_format((float) $produit['prix_unitaire'], 0, ',', ' '); ?>
                            <span>FCFA</span>
                        </p>
                        <?php if (!empty($produit['prix_promotion'])): ?>
                        <p class="account-prod-card__promo">
                            Promo : <?php echo number_format((float) $produit['prix_promotion'], 0, ',', ' '); ?> FCFA
                        </p>
                        <?php endif; ?>
                        <ul class="account-prod-card__meta">
                            <li>
                                <i class="fas fa-cubes" aria-hidden="true"></i>
                                Qté <?php echo (int) $produit['quantite']; ?>
                                <?php if (!empty($produit['poids'])): ?>
                                · <?php echo htmlspecialchars($produit['poids']); ?>
                                <?php endif; ?>
                            </li>
                            <li>
                                <i class="fas fa-receipt" aria-hidden="true"></i>
                                <?php echo htmlspecialchars($produit['numero_commande']); ?>
                            </li>
                        </ul>
                        <div class="account-prod-card__actions">
                            <a href="/produit.php?id=<?php echo (int) $produit['id']; ?>" class="account-prod-card__btn account-prod-card__btn--primary">
                                <i class="fas fa-eye" aria-hidden="true"></i> Voir
                            </a>
                            <a href="mes-commandes.php" class="account-prod-card__btn account-prod-card__btn--ghost">
                                <i class="fas fa-list" aria-hidden="true"></i> Commande
                            </a>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    </div>

    <?php include 'includes/user_footer.php'; ?>
