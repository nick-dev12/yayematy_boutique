<?php
require_once __DIR__ . '/../../includes/session_user.php';
/**
 * Page de liste des produits
 * Programmation procédurale uniquement
 */

session_start_persistent();

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_email'])) {
    header('Location: ../login.php');
    exit;
}

$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

require_once __DIR__ . '/../../models/model_produits.php';
require_once __DIR__ . '/../../models/model_categories.php';
require_once __DIR__ . '/../../includes/site_brand.php';
require_once __DIR__ . '/../includes/render_dash_product_card.php';

$produits_brut = get_all_produits();
$produits_brut = is_array($produits_brut) ? $produits_brut : [];
$categories = get_all_categories();
$categories = is_array($categories) ? $categories : [];
$nb_categories = count($categories);
$recherche = trim($_GET['recherche'] ?? '');
$categorie_id = isset($_GET['categorie_id']) ? (int) $_GET['categorie_id'] : 0;

$produits = $produits_brut;
if (!empty($produits)) {
    $produits = array_values(array_filter($produits, function ($produit) use ($recherche, $categorie_id) {
        if ($categorie_id > 0 && (int) ($produit['categorie_id'] ?? 0) !== $categorie_id) {
            return false;
        }

        if ($recherche === '') {
            return true;
        }

        $needle = function_exists('mb_strtolower') ? mb_strtolower($recherche) : strtolower($recherche);
        $haystacks = [
            $produit['nom'] ?? '',
            $produit['description'] ?? '',
            $produit['categorie_nom'] ?? '',
            $produit['statut'] ?? ''
        ];

        foreach ($haystacks as $value) {
            $value = function_exists('mb_strtolower') ? mb_strtolower((string) $value) : strtolower((string) $value);
            if (strpos($value, $needle) !== false) {
                return true;
            }
        }

        return false;
    }));
}

$nb_total = count($produits_brut);
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
    'link_prefix' => '',
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
    <title>Catalogue Produits - Administration</title>
    <?php require_once __DIR__ . '/../../includes/asset_version.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-dashboard-home.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-produits-index.css'); ?>">
</head>

<body class="page-produits-index">
    <?php include '../includes/nav.php'; ?>

    <div class="contents-container prod-catalog-hub">

        <header class="prod-catalog-hero">
            <div class="prod-catalog-hero__inner">
                <div class="prod-catalog-hero__content">
                    <p class="prod-catalog-hero__eyebrow">
                        <i class="fa-solid fa-box" aria-hidden="true"></i>
                        Catalogue · <?php echo htmlspecialchars(site_brand_name_market()); ?>
                    </p>
                    <h1 class="prod-catalog-hero__title">
                        Gestion des <span>produits</span>
                    </h1>
                    <div class="prod-catalog-hero__actions">
                        <a href="ajouter.php" class="btn-primary">
                            <i class="fas fa-plus"></i> Publier un produit
                        </a>
                        <a href="../stock/index.php" class="dash-btn-outline">
                            <i class="fas fa-boxes-stacked"></i> Stock
                        </a>
                        <?php include __DIR__ . '/../includes/btn_retour_site.php'; ?>
                    </div>
                </div>
                <div class="prod-catalog-hero__meta">
                    <span class="prod-catalog-hero__count"><?php echo (int) $nb_affiches; ?></span>
                    <span class="prod-catalog-hero__count-label">
                        produit<?php echo $nb_affiches > 1 ? 's' : ''; ?>
                        <?php if ($nb_affiches !== $nb_total): ?>
                        affiché<?php echo $nb_affiches > 1 ? 's' : ''; ?>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </header>

        <?php if (!empty($success_message)): ?>
        <div class="prod-catalog-flash message success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>

        <section class="prod-catalog-stats" aria-label="Statistiques produits">
            <article class="prod-stat prod-stat--total">
                <span class="prod-stat__icon"><i class="fa-solid fa-layer-group"></i></span>
                <div>
                    <p class="prod-stat__label">Affichés</p>
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

        <section class="prod-catalog-main" aria-label="Liste des produits">
            <header class="prod-catalog-main__head">
                <h2><i class="fa-solid fa-table-cells-large"></i> Tous les produits</h2>
                <?php if ($recherche !== '' || $categorie_id > 0): ?>
                <p class="prod-catalog-main__filter-hint">Résultats filtrés · <?php echo (int) $nb_total; ?> au total</p>
                <?php else: ?>
                <p class="prod-catalog-main__filter-hint"><?php echo (int) $nb_categories; ?> catégories · <?php echo (int) $nb_total; ?> références</p>
                <?php endif; ?>
            </header>

            <form method="GET" action="" class="prod-catalog-filters">
                <div class="prod-catalog-filters__fields">
                    <div class="prod-catalog-filters__field prod-catalog-filters__field--search">
                        <label for="recherche"><i class="fa-solid fa-magnifying-glass"></i> Recherche</label>
                        <input type="text" id="recherche" name="recherche" placeholder="Nom, description, statut..."
                            value="<?php echo htmlspecialchars($recherche); ?>">
                    </div>
                    <div class="prod-catalog-filters__field prod-catalog-filters__field--cat">
                        <label for="categorie_id"><i class="fa-solid fa-folder"></i> Catégorie</label>
                        <select id="categorie_id" name="categorie_id">
                            <option value="0">Toutes</option>
                            <?php foreach ($categories as $categorie): ?>
                            <option value="<?php echo (int) $categorie['id']; ?>"
                                <?php echo $categorie_id === (int) $categorie['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categorie['nom']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="prod-catalog-filters__actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-search"></i> Filtrer
                    </button>
                    <a href="index.php" class="dash-btn-outline prod-catalog-filters__reset">
                        <i class="fas fa-rotate-left"></i> Réinitialiser
                    </a>
                </div>
            </form>

            <?php if (empty($produits)): ?>
            <div class="prod-catalog-empty">
                <i class="fas fa-box-open"></i>
                <p>Aucun produit trouvé.</p>
                <a href="ajouter.php" class="btn-primary">
                    <i class="fas fa-plus"></i> Publier un produit
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
