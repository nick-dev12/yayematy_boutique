<?php
/**
 * Page des produits visités
 * Design identique à la page principale (index/produits)
 */

require_once __DIR__ . '/../includes/session_user.php';
session_start_persistent();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header('Location: connexion.php');
    exit;
}

require_once __DIR__ . '/../models/model_visites.php';
require_once __DIR__ . '/../includes/render_product_card.php';
$produits_visites = get_produits_visites_by_user($_SESSION['user_id'], 50);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../includes/asset_version.php'; ?>
    <?php include __DIR__ . '/../includes/pwa_meta.php'; ?>
    <title>Produits Visités - Yaye Maty</title>
    <link rel="stylesheet" href="<?php echo asset_url('/css/variables.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo asset_url('/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/a_style.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/catalogue-grid.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/responsive-site.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/user-dashboard.css'); ?>">
    <style>
        .produits-visites-page {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .produits-visites-header {
            background: var(--couleur-dominante);
            padding: 30px 20px;
            text-align: center;
            color: var(--texte-clair);
            margin-bottom: 30px;
            border-radius: 12px;
        }

        .produits-visites-header h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .produits-visites-header p {
            font-size: 15px;
            opacity: 0.95;
        }

        .produits-visites .produit_vedetes {
            margin: 0;
        }

        .produits-visites .carousel {
            position: relative;
        }

        .produits-visites .date-visite-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.6);
            color: #fff;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            z-index: 5;
        }

        .produits-visites .date-visite-badge i {
            margin-right: 4px;
        }
    </style>
</head>

<body>
    <?php include 'includes/user_nav.php'; ?>

    <div class="produits-visites-page">
        <div class="produits-visites-header">
            <h1><i class="fas fa-eye"></i> Produits Visités</h1>
            <p>Historique de vos consultations (<?php echo count($produits_visites); ?>
                produit<?php echo count($produits_visites) > 1 ? 's' : ''; ?>)</p>
        </div>

        <?php if (isset($_GET['added']) && $_GET['added'] == '1'): ?>
            <div
                style="max-width: 600px; margin: 20px auto; padding: 15px 25px; background: rgba(46, 125, 181, 0.15); border-left: 4px solid var(--turquoise); border-radius: 8px; color: var(--titres);">
                <i class="fas fa-check-circle"></i> Produit ajouté au panier avec succès.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div
                style="max-width: 600px; margin: 20px auto; padding: 15px 25px; background: rgba(242, 92, 25, 0.15); border-left: 4px solid var(--couleur-dominante); border-radius: 8px; color: var(--titres);">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        <section class="content-section produits-visites">
            <?php if (empty($produits_visites)): ?>
                <div class="empty-state">
                    <i class="fas fa-eye-slash"></i>
                    <h3>Aucun produit visité</h3>
                    <p>Vous n'avez pas encore consulté de produits. Vos consultations apparaîtront ici.</p>
                    <a href="<?php echo public_url('/produits.php'); ?>" class="btn-primary">
                        <i class="fas fa-box"></i> Découvrir nos produits
                    </a>
                </div>
            <?php else: ?>
                <section class="produit_vedetes">
                    <div class="catalogue-products-section">
                        <div class="catalogue-products-grid">
                            <?php foreach ($produits_visites as $produit): ?>
                                <?php render_product_card_home($produit); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        </section>
    </div>

    <?php include 'includes/user_footer.php'; ?>
</body>

</html>