<?php
require_once __DIR__ . '/includes/session_user.php';
session_start_persistent();

// Inclusion des modèles
require_once __DIR__ . '/models/model_categories.php';
require_once __DIR__ . '/models/model_produits.php';

// Récupérer l'ID de la catégorie depuis l'URL
$categorie_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Réinitialiser la variable pour éviter tout problème de cache
unset($categorie);
$categorie = null;

// Récupérer les informations de la catégorie
if ($categorie_id > 0) {
    $categorie = get_categorie_by_id($categorie_id);
}

// Si la catégorie n'existe pas, rediriger vers l'accueil
if (!$categorie || !is_array($categorie) || empty($categorie['nom'])) {
    header('Location: index.php');
    exit;
}

// Forcer la récupération du nom de la catégorie depuis le tableau
$categorie_nom = isset($categorie['nom']) ? $categorie['nom'] : 'Catégorie';

// Récupérer tous les produits de cette catégorie
$produits = get_produits_by_categorie($categorie_id);
if ($produits === false) {
    $produits = [];
}

// Inclusion du fichier de connexion à la BDD (pour les autres fonctionnalités si nécessaire)
if (file_exists(__DIR__ . '/controllers/controller_commerce_users.php')) {
    require_once __DIR__ . '/controllers/controller_commerce_users.php';
}

// Meta SEO
require_once __DIR__ . '/includes/site_url.php';
$base = get_site_base_url();
$seo_title = $categorie_nom . ' - Yaye Maty';
$desc_cat = !empty($categorie['description']) ? strip_tags($categorie['description']) : 'Produits décoratifs pour gâteaux ' . $categorie_nom . ' : anniversaire, mariage, cérémonies. Yaye Maty - Personnalisez vos gâteaux.';
$seo_description = mb_substr($desc_cat, 0, 160);
$seo_canonical = $base . '/categorie.php?id=' . (int) $categorie_id;
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/includes/pwa_meta.php'; ?>
    <?php include __DIR__ . '/includes/seo_meta.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="/css/owl.carousel.min.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/owl.carousel.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/animate.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/animate.min.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/catalogue-grid.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/product-cards.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/catalogue-responsive.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/responsive-site.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/a_style.css<?php echo asset_version_query(); ?>">
    <style>
        /* Styles personnalisés pour les cartes produits */
    </style>
</head>

<body>

    <?php
    require_once __DIR__ . '/includes/render_product_card.php';
    include('nav_bar.php');
    ?>

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
    <section class="section00">
        <section class="produit_vedetes">
            <div class="box1">
                <h1><?php echo htmlspecialchars($categorie_nom); ?></h1>
            </div>

            <?php if (empty($produits)): ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <p style="font-size: 16px;">Aucun produit publié pour le moment.</p>
                    <a href="index.php"
                        style="display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #918a44; color: white; text-decoration: none; border-radius: 5px; transition: background 0.3s ease;">
                        <i class="fas fa-arrow-left"></i> Retour à l'accueil
                    </a>
                </div>
            <?php else: ?>
                <div class="catalogue-products-section">
                    <div class="catalogue-products-grid">
                    <?php foreach ($produits as $produit): ?>
                        <?php render_product_card_home($produit); ?>
                    <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </section>

    <?php include('footer.php') ?>

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script src="/js/owl.carousel.min.js"></script>
    <script src="/js/owl.carousel.js"></script>
    <script src="/js/owl.animate.js"></script>
    <script src="/js/owl.autoplay.js"></script>

    <script>
        $(document).ready(function () {
            AOS.init();
        });
    </script>

    <script>
        // ..
        AOS.init();
    </script>

</body>

</html>