<?php
require_once __DIR__ . '/../../includes/session_user.php';
/**
 * Gestion du stock - Catégories et produits
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

require_once __DIR__ . '/../../models/model_categories.php';

$categories = get_all_categories_with_count();
$categories = is_array($categories) ? $categories : [];
$nb_categories = count($categories);
$nb_produits = array_sum(array_map(function ($c) {
    return (int) ($c['nb_produits'] ?? 0);
}, $categories));
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <?php include __DIR__ . '/../../includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion du Stock - Administration</title>
    <?php require_once __DIR__ . '/../../includes/asset_version.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-dashboard-home.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-stock-index.css'); ?>">
</head>

<body class="page-stock-index">
    <?php include '../includes/nav.php'; ?>

    <div class="contents-container prod-catalog-hub">

        <header class="prod-catalog-hero">
            <div class="prod-catalog-hero__inner">
                <div class="prod-catalog-hero__content prod-catalog-hero__content--stock">
                    <div class="prod-catalog-hero__title-row">
                        <h1 class="prod-catalog-hero__title">Gestion du <span>stock</span></h1>
                        <div class="prod-catalog-hero__actions prod-catalog-hero__actions--top">
                            <a href="mouvements.php" class="dash-btn-outline">
                                <i class="fas fa-history"></i> Historique mouvements
                            </a>
                        </div>
                    </div>
                    <div class="prod-catalog-hero__actions prod-catalog-hero__actions--catalog">
                        <a href="../categories/ajouter.php" class="btn-primary">
                            <i class="fas fa-plus"></i> Nouvelle catégorie
                        </a>
                        <a href="../categories/sous_categories.php" class="dash-btn-outline">
                            <i class="fas fa-sitemap"></i> Sous-catégories
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <?php if (!empty($success_message)): ?>
        <div class="prod-catalog-flash message success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>

        <section class="prod-catalog-stats" aria-label="Statistiques stock">
            <article class="prod-stat prod-stat--total">
                <span class="prod-stat__icon"><i class="fa-solid fa-folder"></i></span>
                <div>
                    <p class="prod-stat__label">Catégories</p>
                    <p class="prod-stat__value"><?php echo (int) $nb_categories; ?></p>
                </div>
            </article>
            <article class="prod-stat prod-stat--actif">
                <span class="prod-stat__icon"><i class="fa-solid fa-box"></i></span>
                <div>
                    <p class="prod-stat__label">Produits actifs</p>
                    <p class="prod-stat__value"><?php echo (int) $nb_produits; ?></p>
                </div>
            </article>
        </section>

        <section class="prod-catalog-main" aria-label="Catégories">
            <header class="prod-catalog-main__head">
                <div class="prod-catalog-main__head-text">
                    <h2><i class="fa-solid fa-tags"></i> Toutes les catégories</h2>
                    <p class="prod-catalog-main__filter-hint">Sélectionnez une catégorie pour gérer le stock de ses produits.</p>
                </div>
            </header>

            <?php if (empty($categories)): ?>
            <div class="prod-catalog-empty">
                <i class="fas fa-tags"></i>
                <h3>Aucune catégorie</h3>
                <p>Aucune catégorie enregistrée pour le moment.</p>
                <a href="../categories/ajouter.php" class="btn-primary">
                    <i class="fas fa-plus"></i> Ajouter la première catégorie
                </a>
            </div>
            <?php else: ?>
            <div class="dash-cat-grid">
                <?php foreach ($categories as $categorie):
                    $nb = (int) ($categorie['nb_produits'] ?? 0);
                    $img = !empty($categorie['image']) ? upload_public_url(htmlspecialchars($categorie['image'], ENT_QUOTES, 'UTF-8')) : '';
                ?>
                <article class="dash-cat-card">
                    <a href="../categories/produits.php?id=<?php echo (int) $categorie['id']; ?>" class="dash-cat-card__visual">
                        <span class="dash-cat-card__count"><?php echo $nb; ?> produit<?php echo $nb > 1 ? 's' : ''; ?></span>
                        <?php if ($img !== ''): ?>
                        <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($categorie['nom']); ?>" onerror="this.src='<?php echo htmlspecialchars(public_url('/image/produit1.jpg'), ENT_QUOTES, 'UTF-8'); ?>'">
                        <?php else: ?>
                        <div class="dash-cat-card__placeholder"><i class="fas fa-tag"></i></div>
                        <?php endif; ?>
                    </a>
                    <div class="dash-cat-card__body">
                        <h3 class="dash-cat-card__name"><?php echo htmlspecialchars($categorie['nom']); ?></h3>
                        <?php if (!empty($categorie['parent_nom'])): ?>
                        <p class="dash-cat-card__parent"><i class="fas fa-level-up-alt" aria-hidden="true"></i> Sous-catégorie de <?php echo htmlspecialchars($categorie['parent_nom']); ?></p>
                        <?php endif; ?>
                        <p class="dash-cat-card__desc"><?php echo htmlspecialchars($categorie['description'] ?? 'Aucune description'); ?></p>
                        <div class="dash-cat-card__actions">
                            <a href="../categories/produits.php?id=<?php echo (int) $categorie['id']; ?>" class="dash-cat-card__btn dash-cat-card__btn--view">
                                <i class="fas fa-box"></i> Voir produits
                            </a>
                            <a href="../categories/modifier.php?id=<?php echo (int) $categorie['id']; ?>" class="dash-cat-card__btn dash-cat-card__btn--edit">
                                <i class="fas fa-pen"></i> Modifier
                            </a>
                            <a href="../categories/supprimer.php?id=<?php echo (int) $categorie['id']; ?>"
                                class="dash-cat-card__btn dash-cat-card__btn--delete"
                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?');">
                                <i class="fas fa-trash"></i> Supprimer
                            </a>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
