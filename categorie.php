<?php
require_once __DIR__ . '/includes/session_user.php';
session_start_persistent();

require_once __DIR__ . '/includes/site_url.php';
require_once __DIR__ . '/includes/asset_version.php';
require_once __DIR__ . '/includes/image_optimizer.php';
require_once __DIR__ . '/models/model_categories.php';
require_once __DIR__ . '/models/model_produits.php';

$categorie_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$categorie = null;

if ($categorie_id > 0) {
    $categorie = get_categorie_by_id($categorie_id);
}

if (!$categorie || !is_array($categorie) || empty($categorie['nom'])) {
    header('Location: ' . public_url('/produits.php'));
    exit;
}

$categorie_nom = (string) $categorie['nom'];
$categorie_description = trim((string) ($categorie['description'] ?? ''));

$produits = get_produits_by_categorie($categorie_id);
if ($produits === false) {
    $produits = [];
}

$nb_produits = count($produits);

$categorie_image = '';
if (!empty($categorie['image'])) {
    $categorie_image = upload_image_url($categorie['image'], 'md');
} else {
    $categorie_image = public_url('/image/market.png');
}

if (file_exists(__DIR__ . '/controllers/controller_commerce_users.php')) {
    require_once __DIR__ . '/controllers/controller_commerce_users.php';
}

require_once __DIR__ . '/includes/site_brand.php';
$base = get_site_base_url();
$seo_title = $categorie_nom . ' — ' . site_brand_name();
$desc_cat = $categorie_description !== ''
    ? $categorie_description
    : 'Découvrez nos produits ' . $categorie_nom . ' sur ' . site_brand_name_market() . '.';
$seo_description = mb_substr(strip_tags($desc_cat), 0, 160);
$seo_canonical = rtrim($base, '/') . '/categorie.php?id=' . (int) $categorie_id;
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
    <link rel="stylesheet" href="<?php echo asset_url('/css/variables.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/catalogue-grid.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/product-cards.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/catalogue-responsive.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/responsive-site.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/a_style.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/categorie-page.css'); ?>">
</head>

<body class="page-categorie">

    <?php
    require_once __DIR__ . '/includes/render_product_card.php';
    include __DIR__ . '/nav_bar.php';
    ?>

    <?php if (isset($_GET['added']) && $_GET['added'] == '1'): ?>
    <div class="categorie-alert categorie-alert--success">
        <i class="fas fa-check-circle" aria-hidden="true"></i>
        Produit ajouté au panier avec succès.
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
    <div class="categorie-alert categorie-alert--error">
        <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
        <?php echo htmlspecialchars((string) $_GET['error']); ?>
    </div>
    <?php endif; ?>

    <main class="categorie-page">
        <header class="categorie-hero" style="background-image: url('<?php echo htmlspecialchars($categorie_image); ?>');">
            <div class="categorie-hero__overlay">
                <h1 class="categorie-hero__title"><?php echo htmlspecialchars($categorie_nom); ?></h1>
                <?php if ($categorie_description !== ''): ?>
                <p class="categorie-hero__desc"><?php echo htmlspecialchars($categorie_description); ?></p>
                <?php endif; ?>
                <p class="categorie-hero__count">
                    <?php echo $nb_produits === 0
                        ? 'Aucun produit pour le moment'
                        : $nb_produits . ' produit' . ($nb_produits > 1 ? 's' : '') . ' disponible' . ($nb_produits > 1 ? 's' : ''); ?>
                </p>
            </div>
        </header>

        <section class="categorie-content">
            <?php if ($nb_produits === 0): ?>
            <div class="categorie-empty">
                <i class="fa-solid fa-box-open categorie-empty__icon" aria-hidden="true"></i>
                <h2>Aucun produit dans cette catégorie</h2>
                <p>Revenez bientôt : de nouveaux articles seront ajoutés ici.</p>
                <div class="categorie-empty__actions">
                    <a href="<?php echo public_url('/produits.php'); ?>" class="home-btn-primary">
                        Voir tous les produits
                    </a>
                    <a href="<?php echo public_url('/index.php'); ?>" class="categorie-empty__link">
                        Retour à l'accueil
                    </a>
                </div>
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
    </main>

    <?php include __DIR__ . '/footer.php'; ?>

</body>

</html>
