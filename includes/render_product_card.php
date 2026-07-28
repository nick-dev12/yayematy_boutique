<?php
/**
 * Carte produit unifiée (style page d'accueil)
 */

require_once __DIR__ . '/image_optimizer.php';

if (!function_exists('render_product_card_home')) {
    function render_product_card_home($produit, $badge = '')
    {
        $prix_affichage = !empty($produit['prix_promotion']) && $produit['prix_promotion'] < $produit['prix']
            ? $produit['prix_promotion']
            : $produit['prix'];
        $has_promotion = !empty($produit['prix_promotion']) && $produit['prix_promotion'] < $produit['prix'];
        $image = upload_image_url($produit['image_principale'] ?? '', 'md');
        $nom = htmlspecialchars($produit['nom'] ?? 'Produit sans nom');
        $id = (int) $produit['id'];
        $return_url = htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/index.php');
        $detail_url = '/produit.php?id=' . $id;

        if ($badge === '' && $has_promotion) {
            $badge = 'sale';
        }
        ?>
    <article class="home-product-card">
        <?php require __DIR__ . '/partials/product_share_button.php'; ?>
        <a href="<?php echo htmlspecialchars($detail_url); ?>" class="home-product-card-link" aria-label="Voir <?php echo $nom; ?>">
        <div class="home-product-image">
            <?php if ($badge === 'new'): ?>
            <span class="home-product-badge home-product-badge--new">Nouveau</span>
            <?php elseif ($badge === 'sale' || $has_promotion): ?>
            <span class="home-product-badge home-product-badge--sale">Promo</span>
            <?php endif; ?>
            <img src="<?php echo $image; ?>" alt="<?php echo $nom; ?>" onerror="this.src='/image/produit1.jpg'">
        </div>
        <div class="home-product-body">
            <h3 class="home-product-name"><?php echo $nom; ?></h3>
            <p class="home-product-price">
                <?php if ($has_promotion): ?>
                <span class="price-old"><?php echo number_format($produit['prix'], 0, ',', ' '); ?> FCFA</span>
                <span class="price-sale"><?php echo number_format($prix_affichage, 0, ',', ' '); ?> FCFA</span>
                <?php else: ?>
                <?php echo number_format($prix_affichage, 0, ',', ' '); ?> FCFA
                <?php endif; ?>
            </p>
        </div>
        </a>
        <form method="POST" action="/add-to-panier.php">
            <input type="hidden" name="produit_id" value="<?php echo $id; ?>">
            <input type="hidden" name="quantite" value="1">
            <input type="hidden" name="return_url" value="<?php echo $return_url; ?>">
            <button type="submit" class="home-btn-primary">
                <i class="fa-solid fa-cart-shopping"></i> Acheter
            </button>
        </form>
    </article>
        <?php
    }
}
