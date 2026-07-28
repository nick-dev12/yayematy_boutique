<?php
/**
 * Carte produit — tableau de bord admin
 * Options : link_prefix, show_delete, status_badge
 */

if (!function_exists('render_dash_product_card')) {
    function render_dash_product_card($produit, $dash_card_rank = 0, $dash_card_sold = null, $options = [])
    {
        if (!is_array($options)) {
            $options = [];
        }

        $link_prefix = isset($options['link_prefix']) ? (string) $options['link_prefix'] : 'produits/';
        $show_delete = !empty($options['show_delete']);
        $show_status = !empty($options['status_badge']);

        $id = (int) ($produit['id'] ?? 0);
        $nom = htmlspecialchars($produit['nom'] ?? 'Produit');
        $categorie = htmlspecialchars($produit['categorie_nom'] ?? 'Sans catégorie');
        $image = '/upload/' . htmlspecialchars($produit['image_principale'] ?? 'produit1.jpg');
        $stock = (int) ($produit['stock'] ?? 0);
        $statut = $produit['statut'] ?? 'actif';
        $prix = (float) ($produit['prix'] ?? 0);
        $prix_promo = !empty($produit['prix_promotion']) && $produit['prix_promotion'] < $prix
            ? (float) $produit['prix_promotion']
            : null;

        $stock_class = 'dash-prod-card__stock-dot--ok';
        $stock_label = $stock . ' en stock';
        if ($statut === 'rupture_stock' || $stock <= 0) {
            $stock_class = 'dash-prod-card__stock-dot--out';
            $stock_label = 'Rupture';
        } elseif ($stock <= 5) {
            $stock_class = 'dash-prod-card__stock-dot--low';
            $stock_label = $stock . ' restants';
        }

        $statut_label = ucfirst(str_replace('_', ' ', $statut));
        $statut_class = 'dash-prod-card__status--actif';
        if ($statut === 'inactif') {
            $statut_class = 'dash-prod-card__status--inactif';
        } elseif ($statut === 'rupture_stock') {
            $statut_class = 'dash-prod-card__status--rupture';
        }
        ?>
    <article class="dash-prod-card">
        <?php require __DIR__ . '/../../includes/partials/product_share_button.php'; ?>
        <a href="<?php echo htmlspecialchars($link_prefix); ?>ajuster-stock.php?id=<?php echo $id; ?>" class="dash-prod-card__visual">
            <?php if ($show_status && $statut !== 'actif'): ?>
            <span class="dash-prod-card__status <?php echo $statut_class; ?>"><?php echo htmlspecialchars($statut_label); ?></span>
            <?php endif; ?>
            <?php if ($dash_card_rank > 0): ?>
            <span class="dash-prod-card__rank">#<?php echo (int) $dash_card_rank; ?></span>
            <?php endif; ?>
            <?php if ($dash_card_sold !== null && (int) $dash_card_sold > 0): ?>
            <span class="dash-prod-card__sold"><i class="fa-solid fa-fire"></i> <?php echo (int) $dash_card_sold; ?> vendus</span>
            <?php endif; ?>
            <?php if ($prix_promo !== null): ?>
            <span class="dash-prod-card__promo">Promo</span>
            <?php endif; ?>
            <img src="<?php echo $image; ?>" alt="<?php echo $nom; ?>" onerror="this.src='/image/produit1.jpg'">
        </a>
        <div class="dash-prod-card__body">
            <span class="dash-prod-card__cat"><?php echo $categorie; ?></span>
            <h3 class="dash-prod-card__name">
                <a href="<?php echo htmlspecialchars($link_prefix); ?>modifier.php?id=<?php echo $id; ?>"><?php echo $nom; ?></a>
            </h3>
            <div class="dash-prod-card__footer">
                <div class="dash-prod-card__price">
                    <?php if ($prix_promo !== null): ?>
                    <span class="dash-prod-card__price-old"><?php echo number_format($prix, 0, ',', ' '); ?></span>
                    <span class="dash-prod-card__price-now"><?php echo number_format($prix_promo, 0, ',', ' '); ?> <small>FCFA</small></span>
                    <?php else: ?>
                    <span class="dash-prod-card__price-now"><?php echo number_format($prix, 0, ',', ' '); ?> <small>FCFA</small></span>
                    <?php endif; ?>
                </div>
                <span class="dash-prod-card__stock">
                    <i class="dash-prod-card__stock-dot <?php echo $stock_class; ?>" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($stock_label); ?>
                </span>
            </div>
            <div class="dash-prod-card__actions">
                <a href="<?php echo htmlspecialchars($link_prefix); ?>modifier.php?id=<?php echo $id; ?>" class="dash-prod-card__btn dash-prod-card__btn--edit">
                    <i class="fa-solid fa-pen"></i> Modifier
                </a>
                <?php if ($show_delete): ?>
                <a href="<?php echo htmlspecialchars($link_prefix); ?>supprimer.php?id=<?php echo $id; ?>"
                    class="dash-prod-card__btn dash-prod-card__btn--delete"
                    onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?');">
                    <i class="fa-solid fa-trash"></i> Supprimer
                </a>
                <?php else: ?>
                <a href="<?php echo htmlspecialchars($link_prefix); ?>ajuster-stock.php?id=<?php echo $id; ?>" class="dash-prod-card__btn dash-prod-card__btn--stock">
                    <i class="fa-solid fa-boxes-stacked"></i> Stock
                </a>
                <?php endif; ?>
            </div>
        </div>
    </article>
        <?php
    }
}
