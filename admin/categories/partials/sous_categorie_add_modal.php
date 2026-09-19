<?php
/**
 * Modal d'ajout de sous-catégorie (inclus par sous_categories.php).
 *
 * @var bool   $show_add_modal
 * @var array  $parents
 * @var string $add_form_error
 * @var int    $selected_parent
 * @var string $form_nom
 */
$show_add_modal = !empty($show_add_modal);
$parents = is_array($parents ?? null) ? $parents : [];
$add_form_error = (string) ($add_form_error ?? '');
$selected_parent = (int) ($selected_parent ?? 0);
$form_nom = (string) ($form_nom ?? '');
?>
<div id="sous-cat-add-modal"
    class="sous-cat-modal<?php echo $show_add_modal ? ' is-open' : ''; ?>"
    role="dialog"
    aria-modal="true"
    aria-labelledby="sous-cat-add-modal-title"
    aria-hidden="<?php echo $show_add_modal ? 'false' : 'true'; ?>">
    <button type="button" class="sous-cat-modal__backdrop" id="sous-cat-add-modal-backdrop" aria-label="Fermer"></button>
    <div class="sous-cat-modal__panel form-container">
        <header class="sous-cat-modal__head">
            <h2 id="sous-cat-add-modal-title"><i class="fas fa-sitemap" aria-hidden="true"></i> Nouvelle sous-catégorie</h2>
            <button type="button" class="sous-cat-modal__close" id="sous-cat-add-modal-close" aria-label="Fermer">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </header>

        <?php if (empty($parents)): ?>
        <div class="sous-cat-modal__body">
            <div class="sous-cat-form-error">
                <i class="fas fa-info-circle"></i>
                Créez d'abord une <a href="ajouter.php">catégorie principale</a> avant d'ajouter une sous-catégorie.
            </div>
        </div>
        <?php else: ?>
        <div class="sous-cat-modal__body">
            <?php if ($add_form_error !== ''): ?>
            <div class="sous-cat-form-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($add_form_error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="sous_categories.php" enctype="multipart/form-data" id="sous-cat-add-form">
                <input type="hidden" name="add_sous_categorie" value="1">

                <div class="form-group">
                    <label for="parent_id">Catégorie parente *</label>
                    <select id="parent_id" name="parent_id" required>
                        <option value="">— Choisir une catégorie —</option>
                        <?php foreach ($parents as $parent): ?>
                        <option value="<?php echo (int) $parent['id']; ?>"
                            <?php echo $selected_parent === (int) $parent['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($parent['nom']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="nom">Nom de la sous-catégorie *</label>
                    <input type="text" id="nom" name="nom" required
                        value="<?php echo htmlspecialchars($form_nom); ?>"
                        placeholder="Ex : Riz blanc, Mil, etc.">
                </div>

                <div class="form-group">
                    <label for="image">Image (optionnel)</label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Enregistrer la sous-catégorie
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>
