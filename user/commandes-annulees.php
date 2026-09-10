<?php
/**
 * Page de liste des commandes annulées par l'utilisateur
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/../includes/session_user.php';
session_start_persistent();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header('Location: connexion.php');
    exit;
}

require_once __DIR__ . '/../models/model_commandes.php';
require_once __DIR__ . '/../includes/site_brand.php';
require_once __DIR__ . '/../includes/site_url.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recommander'])) {
    $commande_id = isset($_POST['commande_id']) ? (int) $_POST['commande_id'] : 0;

    if ($commande_id > 0) {
        $commande = get_commande_by_id($commande_id, $_SESSION['user_id']);

        if ($commande && $commande['statut'] === 'annulee') {
            require_once __DIR__ . '/../models/model_panier.php';
            $produits_commande = get_commande_produits($commande_id);

            if (!empty($produits_commande)) {
                $added_count = 0;
                foreach ($produits_commande as $produit) {
                    require_once __DIR__ . '/../models/model_produits.php';
                    $produit_info = get_produit_by_id($produit['produit_id']);

                    if ($produit_info && $produit_info['statut'] === 'actif' && $produit_info['stock'] > 0) {
                        $panier_existant = is_in_panier($_SESSION['user_id'], $produit['produit_id']);
                        if ($panier_existant) {
                            $new_quantite = min($panier_existant['quantite'] + $produit['quantite'], $produit_info['stock']);
                            if (update_panier_quantite($panier_existant['id'], $new_quantite)) {
                                $added_count++;
                            }
                        } else {
                            $quantite = min($produit['quantite'], $produit_info['stock']);
                            if (add_to_panier($_SESSION['user_id'], $produit['produit_id'], $quantite)) {
                                $added_count++;
                            }
                        }
                    }
                }

                if ($added_count > 0) {
                    redirect_to('/panier.php?recommande=1&count=' . $added_count);
                }
                $error_message = 'Aucun produit disponible à recommander.';
            } else {
                $error_message = 'Aucun produit trouvé dans cette commande.';
            }
        } else {
            $error_message = 'Cette commande ne peut pas être recommandée.';
        }
    }
}

$commandes = get_commandes_by_user($_SESSION['user_id']);
$commandes_annulees = array_filter($commandes, function ($commande) {
    return $commande['statut'] === 'annulee';
});

$nb_commandes_annulees = count($commandes_annulees);
$montant_total_annule = 0;
foreach ($commandes_annulees as $commande_annulee) {
    $montant_total_annule += (float) $commande_annulee['montant_total'];
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../includes/asset_version.php'; ?>
    <?php include __DIR__ . '/../includes/pwa_meta.php'; ?>
    <title>Commandes annulées — <?php echo htmlspecialchars(site_brand_name()); ?></title>
    <link rel="stylesheet" href="<?php echo asset_url('/css/variables.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo asset_url('/css/user-dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/user-mon-compte.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/user-mes-commandes.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/user-commandes-annulees.css'); ?>">
</head>

<body class="user-page-commandes-annulees">
    <?php include 'includes/user_nav.php'; ?>

    <div class="account-hub">

        <header class="account-hero account-hero--cancelled">
            <div class="account-hero__inner">
                <div class="account-hero__profile">
                    <span class="account-hero__avatar account-hero__avatar--danger" aria-hidden="true"><i class="fas fa-ban"></i></span>
                    <div class="account-hero__intro">
                        <p class="account-hero__eyebrow">
                            <i class="fa-solid fa-times-circle" aria-hidden="true"></i>
                            Historique · <?php echo htmlspecialchars(site_brand_name_market()); ?>
                        </p>
                        <h1 class="account-hero__title">Commandes <span>annulées</span></h1>
                        <p class="account-hero__subtitle">Retrouvez vos commandes annulées et recommandez-les facilement.</p>
                    </div>
                </div>
                <div class="account-hero__meta account-hero__meta--danger">
                    <span class="account-hero__count"><?php echo (int) $nb_commandes_annulees; ?></span>
                    <span class="account-hero__count-label">annulée<?php echo $nb_commandes_annulees > 1 ? 's' : ''; ?></span>
                </div>
            </div>
            <div class="account-hero__actions">
                <a href="mes-commandes.php" class="account-btn account-btn--primary">
                    <i class="fas fa-shopping-bag" aria-hidden="true"></i>
                    Commandes actives
                </a>
                <a href="<?php echo public_url('/produits.php'); ?>" class="account-btn account-btn--outline">
                    <i class="fas fa-store" aria-hidden="true"></i>
                    Voir les produits
                </a>
                <a href="produits-livres.php" class="account-btn account-btn--ghost">
                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                    Commandes reçues
                </a>
            </div>
        </header>

        <?php if ($success_message): ?>
        <div class="orders-flash orders-flash--success" role="status">
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            <span><?php echo htmlspecialchars($success_message); ?></span>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="orders-flash orders-flash--error" role="alert">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <span><?php echo htmlspecialchars($error_message); ?></span>
        </div>
        <?php endif; ?>

        <section class="account-stats orders-stats" aria-label="Statistiques annulations">
            <div class="account-stat account-stat--favoris orders-stat orders-stat--cancelled">
                <span class="account-stat__icon"><i class="fas fa-ban" aria-hidden="true"></i></span>
                <span class="account-stat__value"><?php echo (int) $nb_commandes_annulees; ?></span>
                <span class="account-stat__label">Annulées</span>
            </div>
            <div class="account-stat account-stat--commandes orders-stat">
                <span class="account-stat__icon"><i class="fas fa-coins" aria-hidden="true"></i></span>
                <span class="account-stat__value orders-stat__value--sm"><?php echo number_format($montant_total_annule, 0, ',', ' '); ?></span>
                <span class="account-stat__label">Montant total FCFA</span>
            </div>
        </section>

        <section class="account-block" aria-label="Liste des commandes annulées">
            <header class="account-block__head">
                <div class="account-block__head-text">
                    <h2><i class="fa-solid fa-list" aria-hidden="true"></i> Historique des annulations</h2>
                    <p>Consultez le détail ou recommandez une commande en un clic.</p>
                </div>
            </header>

            <?php if (empty($commandes_annulees)): ?>
            <div class="account-empty">
                <span class="account-empty__icon" aria-hidden="true"><i class="fas fa-ban"></i></span>
                <h3>Aucune commande annulée</h3>
                <p>Vous n'avez annulé aucune commande pour le moment.</p>
                <a href="mes-commandes.php" class="account-btn account-btn--primary">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    Retour aux commandes
                </a>
            </div>
            <?php else: ?>
            <div class="orders-grid">
                <?php foreach ($commandes_annulees as $commande): ?>
                <article class="order-card order-card--cancelled">
                    <header class="order-card__head">
                        <div class="order-card__info">
                            <p class="order-card__ref"><?php echo htmlspecialchars($commande['numero_commande']); ?></p>
                            <p class="order-card__date">
                                <i class="far fa-calendar" aria-hidden="true"></i>
                                <?php echo date('d/m/Y à H:i', strtotime($commande['date_commande'])); ?>
                            </p>
                        </div>
                        <span class="order-card__badge order-card__badge--annulee">Annulée</span>
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
                    </dl>

                    <div class="order-card__actions">
                        <a href="commande-categorie.php?commande_id=<?php echo (int) $commande['id']; ?>"
                            class="order-card__btn order-card__btn--primary">
                            <i class="fas fa-eye" aria-hidden="true"></i> Voir les produits
                        </a>
                        <form method="post" action="" class="order-card__form">
                            <input type="hidden" name="commande_id" value="<?php echo (int) $commande['id']; ?>">
                            <button type="submit" name="recommander" class="order-card__btn order-card__btn--reorder">
                                <i class="fas fa-redo" aria-hidden="true"></i> Recommander
                            </button>
                        </form>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    </div>

    <?php include 'includes/user_footer.php'; ?>
