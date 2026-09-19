<?php
require_once __DIR__ . '/../includes/admin_auth.php';

require_once __DIR__ . '/../../models/model_categories.php';
require_once __DIR__ . '/../../controllers/controller_categories.php';

$parents = get_parent_categories();
$show_add_modal = isset($_GET['ajouter']) && (string) $_GET['ajouter'] === '1';
$add_form_error = '';
$selected_parent = isset($_GET['parent_id']) ? (int) $_GET['parent_id'] : 0;
$form_nom = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['add_sous_categorie'])) {
    $result = process_add_sous_categorie();
    if (!empty($result['success'])) {
        $_SESSION['success_message'] = $result['message'];
        header('Location: sous_categories.php');
        exit;
    }
    $show_add_modal = true;
    $add_form_error = (string) ($result['message'] ?? 'Erreur lors de l\'enregistrement.');
    $selected_parent = isset($_POST['parent_id']) ? (int) $_POST['parent_id'] : 0;
    $form_nom = isset($_POST['nom']) ? trim((string) $_POST['nom']) : '';
}

$sous_categories = get_all_subcategories_with_count();

$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = (string) $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include __DIR__ . '/../../includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sous-catégories - Administration</title>
    <?php require_once __DIR__ . '/../../includes/asset_version.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-dashboard-home.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-stock-index.css'); ?>">
</head>
<body class="page-sous-categories">
    <?php include '../includes/nav.php'; ?>

    <div class="contents-container prod-catalog-hub">
        <header class="prod-catalog-hero">
            <div class="prod-catalog-hero__inner">
                <div class="prod-catalog-hero__content">
                    <div class="prod-catalog-hero__title-row">
                        <a href="../stock/index.php" class="prod-catalog-hero__back btn-back" title="Retour au stock" aria-label="Retour au stock">
                            <i class="fas fa-arrow-left" aria-hidden="true"></i>
                        </a>
                        <h1 class="prod-catalog-hero__title">Sous-<span>catégories</span></h1>
                        <div class="prod-catalog-hero__actions">
                            <button type="button" class="btn-primary js-open-sous-cat-modal">
                                <i class="fas fa-plus"></i> Ajouter une sous-catégorie
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <?php if ($success_message !== ''): ?>
        <div class="prod-catalog-flash message success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>

        <section class="prod-catalog-main" aria-label="Liste des sous-catégories">
            <header class="prod-catalog-main__head">
                <div class="prod-catalog-main__head-text">
                    <h2><i class="fa-solid fa-list"></i> Toutes les sous-catégories</h2>
                </div>
            </header>

            <?php if (empty($sous_categories)): ?>
            <div class="prod-catalog-empty">
                <i class="fas fa-sitemap"></i>
                <h3>Aucune sous-catégorie</h3>
                <p>Créez une catégorie principale puis ajoutez des sous-catégories.</p>
                <button type="button" class="btn-primary js-open-sous-cat-modal">
                    <i class="fas fa-plus"></i> Ajouter une sous-catégorie
                </button>
            </div>
            <?php else: ?>
            <div class="sous-cat-table-wrap">
                <table class="sous-cat-table">
                    <thead>
                        <tr>
                            <th scope="col">Sous-catégorie</th>
                            <th scope="col">Catégorie parente</th>
                            <th scope="col">Produits</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sous_categories as $sc):
                            $nb = (int) ($sc['nb_produits'] ?? 0);
                            $id = (int) $sc['id'];
                        ?>
                        <tr>
                            <td data-label="Sous-catégorie">
                                <strong><?php echo htmlspecialchars($sc['nom']); ?></strong>
                                <?php if (!empty($sc['description'])): ?>
                                <span class="sous-cat-table__desc"><?php echo htmlspecialchars($sc['description']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Parente"><?php echo htmlspecialchars($sc['parent_nom'] ?? '—'); ?></td>
                            <td data-label="Produits"><?php echo $nb; ?></td>
                            <td data-label="Actions" class="sous-cat-table__actions">
                                <a href="modifier.php?id=<?php echo $id; ?>" class="dash-cat-card__btn dash-cat-card__btn--edit">
                                    <i class="fas fa-pen"></i> Modifier
                                </a>
                                <a href="supprimer.php?id=<?php echo $id; ?>"
                                    class="dash-cat-card__btn dash-cat-card__btn--delete"
                                    onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette sous-catégorie ?');">
                                    <i class="fas fa-trash"></i> Supprimer
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </section>
    </div>

    <?php include __DIR__ . '/partials/sous_categorie_add_modal.php'; ?>

    <script>
    (function () {
        var modal = document.getElementById('sous-cat-add-modal');
        if (!modal) return;

        var backdrop = document.getElementById('sous-cat-add-modal-backdrop');
        var closeBtn = document.getElementById('sous-cat-add-modal-close');

        function openModal() {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('sous-cat-modal-open');
            var first = modal.querySelector('select, input, textarea, button');
            if (first) first.focus();
        }

        function closeModal() {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('sous-cat-modal-open');
        }

        document.querySelectorAll('.js-open-sous-cat-modal').forEach(function (btn) {
            btn.addEventListener('click', openModal);
        });
        if (backdrop) backdrop.addEventListener('click', closeModal);
        if (closeBtn) closeBtn.addEventListener('click', closeModal);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal();
            }
        });
    })();
    </script>

    <?php include '../includes/footer.php'; ?>
