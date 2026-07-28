<?php
/**
 * Page de liste des commandes utilisateur
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

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer_livraison'])) {
    $commande_id = isset($_POST['commande_id']) ? (int) $_POST['commande_id'] : 0;

    if ($commande_id > 0) {
        $commande = get_commande_by_id($commande_id, $_SESSION['user_id']);
        if ($commande && $commande['statut'] === 'livraison_en_cours') {
            require_once __DIR__ . '/../models/model_commandes_admin.php';
            if (update_commande_statut($commande_id, 'paye')) {
                header('Location: mes-commandes.php?livraison_confirmee=1');
                exit;
            }
        }
        if (empty($success_message)) {
            $error_message = 'Une erreur est survenue lors de la confirmation de la réception du colis.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['annuler_commande'])) {
    $commande_id = isset($_POST['commande_id']) ? (int) $_POST['commande_id'] : 0;

    if ($commande_id > 0) {
        $commande = get_commande_by_id($commande_id, $_SESSION['user_id']);

        if ($commande && $commande['statut'] !== 'livree' && $commande['statut'] !== 'annulee') {
            if (update_commande_statut_user($commande_id, $_SESSION['user_id'], 'annulee')) {
                header('Location: mes-commandes.php?commande_annulee=1');
                exit;
            }
            $error_message = 'Une erreur est survenue lors de l\'annulation de la commande.';
        } else {
            $error_message = 'Cette commande ne peut pas être annulée.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recommander'])) {
    $commande_id = isset($_POST['commande_id']) ? (int) $_POST['commande_id'] : 0;

    if ($commande_id > 0) {
        $commande = get_commande_by_id($commande_id, $_SESSION['user_id']);

        if ($commande && $commande['statut'] === 'annulee') {
            require_once __DIR__ . '/../models/model_panier.php';
            $produits_commande = get_commande_produits($commande_id);

            if (!empty($produits_commande)) {
                require_once __DIR__ . '/../models/model_variantes.php';
                $added_count = 0;
                foreach ($produits_commande as $produit) {
                    require_once __DIR__ . '/../models/model_produits.php';
                    $produit_info = get_produit_by_id($produit['produit_id']);

                    if ($produit_info && $produit_info['statut'] === 'actif' && $produit_info['stock'] > 0) {
                        $quantite = min($produit['quantite'], $produit_info['stock']);
                        $variante_id = !empty($produit['variante_id']) ? (int) $produit['variante_id'] : null;
                        $variante_nom = !empty($produit['variante_nom']) ? trim($produit['variante_nom']) : null;
                        $variante_image = null;
                        if ($variante_id) {
                            $var = get_variante_by_id($variante_id);
                            $variante_image = $var && !empty($var['image']) ? $var['image'] : null;
                        }
                        $surcout_poids = isset($produit['surcout_poids']) ? (float) $produit['surcout_poids'] : 0;
                        $surcout_taille = isset($produit['surcout_taille']) ? (float) $produit['surcout_taille'] : 0;
                        $prix_unitaire = isset($produit['prix_unitaire']) ? (float) $produit['prix_unitaire'] : null;

                        if (add_to_panier($_SESSION['user_id'], $produit['produit_id'], $quantite,
                            $produit['couleur'] ?? null, $produit['poids'] ?? null, $produit['taille'] ?? null,
                            $variante_id, $variante_nom, $variante_image, $surcout_poids, $surcout_taille, $prix_unitaire)) {
                            $added_count++;
                        }
                    }
                }

                if ($added_count > 0) {
                    header('Location: /panier.php?recommande=1&count=' . $added_count);
                    exit;
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

if (isset($_GET['success']) && $_GET['success'] == '1' && isset($_GET['numero'])) {
    $success_message = 'Votre commande #' . htmlspecialchars($_GET['numero']) . ' a été créée avec succès !';
}

if (isset($_GET['livraison_confirmee']) && $_GET['livraison_confirmee'] == '1') {
    $success_message = 'Colis reçu confirmé avec succès !';
}

if (isset($_GET['commande_annulee']) && $_GET['commande_annulee'] == '1') {
    $success_message = 'Commande annulée avec succès !';
}

$commandes = get_commandes_by_user($_SESSION['user_id']);

$commandes_actives = array_filter($commandes, function ($commande) {
    return $commande['statut'] !== 'livree' && $commande['statut'] !== 'paye' && $commande['statut'] !== 'annulee';
});

$commandes_perso = get_commandes_personnalisees_by_user($_SESSION['user_id']);
$commandes_perso_actives = array_filter($commandes_perso, function ($cp) {
    return !in_array($cp['statut'], ['terminee', 'refusee', 'annulee']);
});
$statuts_labels = get_statuts_commande_personnalisee();

$nb_actives = count($commandes_actives);
$nb_perso = count($commandes_perso_actives);

function user_commande_statut_label($statut)
{
    if ($statut === 'livree' || $statut === 'paye') {
        return 'Reçu';
    }
    if ($statut === 'annulee') {
        return 'Annulée';
    }
    return ucfirst(str_replace('_', ' ', (string) $statut));
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../includes/asset_version.php'; ?>
    <?php include __DIR__ . '/../includes/pwa_meta.php'; ?>
    <title>Mes Commandes — <?php echo htmlspecialchars(site_brand_name()); ?></title>
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/user-dashboard.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/user-mon-compte.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/user-mes-commandes.css<?php echo asset_version_query(); ?>">
</head>

<body class="user-page-mes-commandes">
    <?php include 'includes/user_nav.php'; ?>

    <div class="account-hub">

        <header class="account-hero">
            <div class="account-hero__inner">
                <div class="account-hero__profile">
                    <span class="account-hero__avatar" aria-hidden="true"><i class="fas fa-shopping-bag"></i></span>
                    <div class="account-hero__intro">
                        <p class="account-hero__eyebrow">
                            <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                            Commandes · <?php echo htmlspecialchars(site_brand_name_market()); ?>
                        </p>
                        <h1 class="account-hero__title">Mes <span>commandes</span></h1>
                        <p class="account-hero__subtitle">Suivez vos achats en cours et vos demandes personnalisées.</p>
                    </div>
                </div>
                <div class="account-hero__meta">
                    <span class="account-hero__count"><?php echo (int) $nb_actives; ?></span>
                    <span class="account-hero__count-label">active<?php echo $nb_actives > 1 ? 's' : ''; ?></span>
                </div>
            </div>
            <div class="account-hero__actions">
                <a href="/index.php" class="account-btn account-btn--primary">
                    <i class="fas fa-store" aria-hidden="true"></i>
                    Continuer mes achats
                </a>
                <a href="commande-categorie.php" class="account-btn account-btn--outline">
                    <i class="fas fa-layer-group" aria-hidden="true"></i>
                    Voir par catégorie
                </a>
                <a href="commandes-annulees.php" class="account-btn account-btn--ghost">
                    <i class="fas fa-ban" aria-hidden="true"></i>
                    Annulées
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

        <section class="account-stats orders-stats" aria-label="Résumé commandes">
            <div class="account-stat account-stat--commandes orders-stat">
                <span class="account-stat__icon"><i class="fas fa-shopping-bag" aria-hidden="true"></i></span>
                <span class="account-stat__value"><?php echo (int) $nb_actives; ?></span>
                <span class="account-stat__label">Actives</span>
            </div>
            <div class="account-stat account-stat--visites orders-stat orders-stat--perso">
                <span class="account-stat__icon"><i class="fas fa-palette" aria-hidden="true"></i></span>
                <span class="account-stat__value"><?php echo (int) $nb_perso; ?></span>
                <span class="account-stat__label">Personnalisées</span>
            </div>
        </section>

        <section class="account-block" aria-label="Commandes actives">
            <header class="account-block__head">
                <div class="account-block__head-text">
                    <h2><i class="fa-solid fa-clock" aria-hidden="true"></i> Commandes actives</h2>
                    <p>Consultez le détail, confirmez la réception ou annulez si nécessaire.</p>
                </div>
                <a href="/produits.php" class="account-block__link">
                    Nouvelle commande <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </header>

            <?php if (empty($commandes_actives)): ?>
            <div class="account-empty">
                <span class="account-empty__icon" aria-hidden="true"><i class="fas fa-shopping-bag"></i></span>
                <h3>Aucune commande active</h3>
                <p>Vos commandes en cours apparaîtront ici dès validation.</p>
                <a href="/produits.php" class="account-btn account-btn--primary">
                    <i class="fas fa-shopping-cart" aria-hidden="true"></i>
                    Découvrir nos produits
                </a>
            </div>
            <?php else: ?>
            <div class="orders-grid">
                <?php foreach ($commandes_actives as $commande): ?>
                    <?php
                    $statut = (string) ($commande['statut'] ?? '');
                    $can_cancel = in_array($statut, ['en_attente', 'confirmee', 'prise_en_charge', 'en_preparation'], true);
                    ?>
                <article class="order-card">
                    <header class="order-card__head">
                        <div class="order-card__info">
                            <p class="order-card__ref"><?php echo htmlspecialchars($commande['numero_commande']); ?></p>
                            <p class="order-card__date">
                                <i class="far fa-calendar" aria-hidden="true"></i>
                                <?php echo date('d/m/Y à H:i', strtotime($commande['date_commande'])); ?>
                            </p>
                        </div>
                        <span class="order-card__badge order-card__badge--<?php echo htmlspecialchars($statut, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars(user_commande_statut_label($statut)); ?>
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
                        <?php if (($commande['mode_livraison'] ?? 'livraison') === 'retrait'): ?>
                        <div class="order-card__detail">
                            <dt>Mode</dt>
                            <dd>Retrait sur place</dd>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($commande['date_livraison'])): ?>
                        <div class="order-card__detail">
                            <dt>Livraison prévue</dt>
                            <dd><?php echo date('d/m/Y', strtotime($commande['date_livraison'])); ?></dd>
                        </div>
                        <?php endif; ?>
                    </dl>

                    <div class="order-card__actions">
                        <a href="commande-categorie.php?commande_id=<?php echo (int) $commande['id']; ?>"
                            class="order-card__btn order-card__btn--primary">
                            <i class="fas fa-eye" aria-hidden="true"></i> Voir les produits
                        </a>

                        <?php if ($statut === 'livraison_en_cours'): ?>
                        <form method="post" action="" class="order-card__form">
                            <input type="hidden" name="commande_id" value="<?php echo (int) $commande['id']; ?>">
                            <button type="submit"
                                name="confirmer_livraison"
                                class="order-card__btn order-card__btn--success"
                                onclick="return confirm('Avez-vous bien reçu votre colis ?');">
                                <i class="fas fa-check-circle" aria-hidden="true"></i> Colis reçu
                            </button>
                        </form>
                        <?php endif; ?>

                        <?php if ($can_cancel): ?>
                        <form method="post" action="" class="order-card__form">
                            <input type="hidden" name="commande_id" value="<?php echo (int) $commande['id']; ?>">
                            <button type="submit"
                                name="annuler_commande"
                                class="order-card__btn order-card__btn--danger"
                                onclick="return confirm('Êtes-vous sûr de vouloir annuler cette commande ? Cette action est irréversible.');">
                                <i class="fas fa-times-circle" aria-hidden="true"></i> Annuler
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>

        <section class="account-block account-block--perso" aria-label="Commandes personnalisées">
            <header class="account-block__head">
                <div class="account-block__head-text">
                    <h2><i class="fa-solid fa-palette" aria-hidden="true"></i> Commandes personnalisées</h2>
                    <p>Vos demandes sur mesure en cours de traitement.</p>
                </div>
                <a href="/commande-personnalisee.php" class="account-block__link">
                    Nouvelle demande <i class="fas fa-plus" aria-hidden="true"></i>
                </a>
            </header>

            <?php if (empty($commandes_perso_actives)): ?>
            <div class="account-empty account-empty--compact">
                <span class="account-empty__icon" aria-hidden="true"><i class="fas fa-palette"></i></span>
                <h3>Aucune demande en cours</h3>
                <p>Créez une commande personnalisée pour un produit unique.</p>
                <a href="/commande-personnalisee.php" class="account-btn account-btn--primary">
                    <i class="fas fa-palette" aria-hidden="true"></i>
                    Faire une demande
                </a>
            </div>
            <?php else: ?>
            <div class="orders-grid">
                <?php foreach ($commandes_perso_actives as $cp): ?>
                <article class="order-card order-card--perso">
                    <header class="order-card__head">
                        <div class="order-card__info">
                            <p class="order-card__ref">Demande #<?php echo (int) $cp['id']; ?></p>
                            <p class="order-card__date">
                                <i class="far fa-calendar" aria-hidden="true"></i>
                                <?php echo date('d/m/Y à H:i', strtotime($cp['date_creation'])); ?>
                            </p>
                        </div>
                        <span class="order-card__badge order-card__badge--perso order-card__badge--<?php echo htmlspecialchars((string) $cp['statut'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($statuts_labels[$cp['statut']] ?? $cp['statut']); ?>
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
