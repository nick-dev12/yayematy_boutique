<?php
require_once __DIR__ . '/../../includes/session_user.php';
/**
 * Page d'affichage des produits d'une catégorie
 */

session_start_persistent();

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_email'])) {
    header('Location: ../login.php');
    exit;
}

$categorie_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($categorie_id <= 0) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../../models/model_categories.php';
require_once __DIR__ . '/../../models/model_produits.php';
require_once __DIR__ . '/../../includes/site_brand.php';
require_once __DIR__ . '/../includes/render_dash_product_card.php';

$categorie = get_categorie_by_id($categorie_id);

if (!$categorie) {
    header('Location: index.php');
    exit;
}

$produits = get_produits_by_categorie($categorie_id);
$produits = is_array($produits) ? $produits : [];
$nb_affiches = count($produits);
$nb_actifs = count(array_filter($produits, function ($p) {
    return ($p['statut'] ?? '') === 'actif';
}));
$nb_rupture = count(array_filter($produits, function ($p) {
    return ($p['statut'] ?? '') === 'rupture_stock' || (int) ($p['stock'] ?? 0) <= 0;
}));
$nb_promo = count(array_filter($produits, function ($p) {
    return !empty($p['prix_promotion']) && (float) $p['prix_promotion'] < (float) ($p['prix'] ?? 0);
}));

$card_options = [
    'link_prefix' => '../produits/',
    'show_delete' => true,
    'status_badge' => true,
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include __DIR__ . '/../../includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($categorie['nom']); ?> - Produits</title>
    <?php require_once __DIR__ . '/../../includes/asset_version.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/admin-dashboard.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/admin-dashboard-home.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/admin-produits-index.css<?php echo asset_version_query(); ?>">
</head>
<body class="page-produits-index page-categorie-produits">
    <?php include '../includes/nav.php'; ?>

    <div class="contents-container prod-catalog-hub">

        <header class="prod-catalog-hero">
            <div class="prod-catalog-hero__inner">
                <div class="prod-catalog-hero__content">
                    <p class="prod-catalog-hero__eyebrow">
                        <i class="fa-solid fa-folder" aria-hidden="true"></i>
                        Catégorie · <?php echo htmlspecialchars(site_brand_name_market()); ?>
                    </p>
                    <h1 class="prod-catalog-hero__title"><?php echo htmlspecialchars($categorie['nom']); ?></h1>
                    <p class="prod-catalog-hero__subtitle"><?php echo htmlspecialchars($categorie['description'] ?? 'Produits de cette catégorie'); ?></p>
                    <div class="prod-catalog-hero__actions">
                        <a href="../stock/index.php" class="dash-btn-outline">
                            <i class="fas fa-arrow-left"></i> Retour stock
                        </a>
                        <a href="../produits/ajouter.php?categorie_id=<?php echo (int) $categorie_id; ?>" class="btn-primary">
                            <i class="fas fa-plus"></i> Ajouter un produit
                        </a>
                    </div>
                </div>
                <div class="prod-catalog-hero__meta">
                    <span class="prod-catalog-hero__count"><?php echo (int) $nb_affiches; ?></span>
                    <span class="prod-catalog-hero__count-label">produit<?php echo $nb_affiches > 1 ? 's' : ''; ?></span>
                </div>
            </div>
        </header>

        <section class="prod-catalog-stats" aria-label="Statistiques catégorie">
            <article class="prod-stat prod-stat--total">
                <span class="prod-stat__icon"><i class="fa-solid fa-layer-group"></i></span>
                <div>
                    <p class="prod-stat__label">Total</p>
                    <p class="prod-stat__value"><?php echo (int) $nb_affiches; ?></p>
                </div>
            </article>
            <article class="prod-stat prod-stat--actif">
                <span class="prod-stat__icon"><i class="fa-solid fa-circle-check"></i></span>
                <div>
                    <p class="prod-stat__label">Actifs</p>
                    <p class="prod-stat__value"><?php echo (int) $nb_actifs; ?></p>
                </div>
            </article>
            <article class="prod-stat prod-stat--rupture">
                <span class="prod-stat__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                <div>
                    <p class="prod-stat__label">Rupture</p>
                    <p class="prod-stat__value"><?php echo (int) $nb_rupture; ?></p>
                </div>
            </article>
            <article class="prod-stat prod-stat--promo">
                <span class="prod-stat__icon"><i class="fa-solid fa-tags"></i></span>
                <div>
                    <p class="prod-stat__label">En promo</p>
                    <p class="prod-stat__value"><?php echo (int) $nb_promo; ?></p>
                </div>
            </article>
        </section>

        <section class="prod-catalog-main" aria-label="Produits de la catégorie">
            <header class="prod-catalog-main__head">
                <div class="prod-catalog-main__head-text">
                    <h2><i class="fa-solid fa-table-cells-large"></i> Produits de la catégorie</h2>
                    <p class="prod-catalog-main__filter-hint">Gérez le stock et les fiches produits de « <?php echo htmlspecialchars($categorie['nom']); ?> ».</p>
                </div>
                <div class="prod-catalog-toolbar">
                    <a href="../produits/index.php" class="dash-btn-outline dash-btn-outline--sm">Catalogue complet</a>
                </div>
            </header>

            <?php if (empty($produits)): ?>
            <div class="prod-catalog-empty">
                <i class="fas fa-box-open"></i>
                <p>Aucun produit dans cette catégorie pour le moment.</p>
                <a href="../produits/ajouter.php?categorie_id=<?php echo (int) $categorie_id; ?>" class="btn-primary">
                    <i class="fas fa-plus"></i> Ajouter un produit
                </a>
            </div>
            <?php else: ?>
            <div class="dash-prod-grid prod-catalog-grid">
                <?php foreach ($produits as $produit):
                    render_dash_product_card($produit, 0, null, $card_options);
                endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
