<?php
require_once __DIR__ . '/includes/session_user.php';
session_start_persistent();

require_once __DIR__ . '/models/model_produits.php';

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$produits = get_produits_en_promo($offset, $limit);
$total_produits = count_produits_en_promo();
$total_pages = $total_produits > 0 ? ceil($total_produits / $limit) : 1;

if (file_exists(__DIR__ . '/controllers/controller_commerce_users.php')) {
    require_once __DIR__ . '/controllers/controller_commerce_users.php';
}
require_once __DIR__ . '/includes/render_product_card.php';

// Meta SEO
require_once __DIR__ . '/includes/site_url.php';
$base = get_site_base_url();
$seo_title = 'Promotions décoration gâteaux - Yaye Maty';
$seo_description = 'Promotions sur les produits décoratifs pour gâteaux : anniversaire, mariage, cérémonies. Décoration comestible et non comestible. Offres limitées.';
$seo_canonical = $base . '/promo.php';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/includes/pwa_meta.php'; ?>
    <?php include __DIR__ . '/includes/seo_meta.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/a_style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/catalogue-grid.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/responsive-site.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css">
    <style>
        .page-header {
            background: linear-gradient(135deg, var(--accent-promo) 0%, rgba(242, 92, 25, 0.85) 100%);
            padding: 40px 20px;
            text-align: center;
            color: #ffffff;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .page-header p {
            font-size: 16px;
            opacity: 0.95;
        }

        .produits-container-wrapper {
            width: 100%;
            max-width: 140rem;
            margin-inline: auto;
            box-sizing: border-box;
            padding: 0 clamp(0.75rem, 3vw, 2rem) 5rem;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.4;
        }

        .empty-state a {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: var(--accent-promo);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .empty-state a:hover {
            background: rgba(242, 92, 25, 0.9);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin: 40px 0;
            flex-wrap: wrap;
        }

        .pagination a,
        .pagination span {
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            background: #fff;
            border: 1px solid rgba(0, 0, 0, 0.1);
            color: var(--texte-fonce);
            font-weight: 600;
            transition: all 0.3s;
        }

        .pagination a:hover {
            background: var(--accent-promo);
            color: #fff;
            border-color: var(--accent-promo);
        }

        .pagination .current {
            background: var(--accent-promo);
            color: #fff;
            border-color: var(--accent-promo);
        }
    </style>
</head>

<body>
    <?php include('nav_bar.php'); ?>

    <div class="page-header">
        <h1><i class="fas fa-percent"></i> Promotions</h1>
        <p><?php echo $total_produits; ?> produit(s) en promotion - Profitez des meilleures offres !</p>
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
    <div class="produits-container-wrapper">
        <section class="section00">
            <section class="produit_vedetes">
                <?php if (empty($produits)): ?>
                    <div class="empty-state">
                        <i class="fas fa-tags"></i>
                        <p>Aucun produit en promotion pour le moment.</p>
                        <a href="produits.php"><i class="fas fa-box"></i> Voir tous les produits</a>
                    </div>
                <?php else: ?>
                    <div class="catalogue-products-section">
                        <div class="catalogue-products-grid">
                            <?php foreach ($produits as $produit): ?>
                                <?php render_product_card_home($produit, 'sale'); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>"><i class="fas fa-chevron-left"></i> Précédent</a>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= min($total_pages, 10); $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>">Suivant <i class="fas fa-chevron-right"></i></a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </section>
    </div>

    <?php include('footer.php'); ?>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>AOS.init();</script>
</body>

</html>