<?php
/**
 * Bandeau sous-catégories (page catégorie).
 *
 * @var array<int, array<string, mixed>> $sous_categories
 * @var int    $parent_categorie_id
 * @var int    $categorie_id
 * @var bool   $is_subcategory_view
 */
if (empty($sous_categories) || !is_array($sous_categories)) {
    return;
}

require_once __DIR__ . '/../categorie_subcat_visual.php';

$subcat_nav_count = count($sous_categories) + 1;
$parent_url = public_url('/categorie.php?id=' . (int) $parent_categorie_id);
?>
<nav class="categorie-subcats categorie-subcats--count-<?php echo (int) $subcat_nav_count; ?>"
    aria-label="Sous-catégories"
    style="--subcat-cols: <?php echo (int) $subcat_nav_count; ?>;">
    <div class="categorie-subcats__card">
        <div class="categorie-subcats__track" role="list">
            <a href="<?php echo htmlspecialchars($parent_url); ?>"
                class="categorie-subcats__item<?php echo !$is_subcategory_view ? ' is-active' : ''; ?>"
                role="listitem"
                <?php echo !$is_subcategory_view ? ' aria-current="page"' : ''; ?>>
                <span class="categorie-subcats__icon tone-grey" aria-hidden="true">
                    <i class="fa-solid fa-border-all"></i>
                </span>
                <span class="categorie-subcats__label">Tous</span>
            </a>
            <?php foreach ($sous_categories as $sous_cat):
                $sc_id = (int) ($sous_cat['id'] ?? 0);
                if ($sc_id <= 0) {
                    continue;
                }
                $is_active = $is_subcategory_view && $sc_id === $categorie_id;
                $sc_nom = (string) ($sous_cat['nom'] ?? '');
                $visual = categorie_subcat_visual($sc_nom);
                $sc_image = trim((string) ($sous_cat['image'] ?? ''));
                $sc_url = public_url('/categorie.php?id=' . $sc_id);
            ?>
            <a href="<?php echo htmlspecialchars($sc_url); ?>"
                class="categorie-subcats__item<?php echo $is_active ? ' is-active' : ''; ?>"
                role="listitem"
                <?php echo $is_active ? ' aria-current="page"' : ''; ?>>
                <span class="categorie-subcats__icon <?php echo htmlspecialchars($visual['tone']); ?>" aria-hidden="true">
                    <?php if ($sc_image !== ''): ?>
                    <img src="<?php echo htmlspecialchars(upload_image_url($sc_image, 'sm')); ?>"
                        alt=""
                        width="64"
                        height="64"
                        loading="lazy"
                        decoding="async">
                    <?php else: ?>
                    <i class="<?php echo htmlspecialchars($visual['icon']); ?>"></i>
                    <?php endif; ?>
                </span>
                <span class="categorie-subcats__label"><?php echo htmlspecialchars($sc_nom); ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</nav>
