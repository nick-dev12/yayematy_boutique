<?php
/**
 * Carte produit unifiée — design catalogue moderne
 */

require_once __DIR__ . '/image_optimizer.php';
require_once __DIR__ . '/format_price.php';
if (!function_exists('public_url')) {
    require_once __DIR__ . '/site_url.php';
}
if (!function_exists('site_brand_name')) {
    require_once __DIR__ . '/site_brand.php';
}

if (!function_exists('product_card_has_reviews')) {
    function product_card_has_reviews(array $produit): bool
    {
        return !empty($produit['nb_avis']) && (int) $produit['nb_avis'] > 0;
    }
}

if (!function_exists('product_card_review_count')) {
    function product_card_review_count(array $produit): int
    {
        return (int) ($produit['nb_avis'] ?? 0);
    }
}

if (!function_exists('render_product_card_rating')) {
    function render_product_card_rating(array $produit, string $wrapper_class = 'home-product-rating'): void
    {
        if (!product_card_has_reviews($produit)) {
            return;
        }
        $review_count = product_card_review_count($produit);
        ?>
                <div class="<?php echo htmlspecialchars($wrapper_class, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
                    <span class="home-product-stars">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </span>
                    <span class="home-product-reviews">(<?php echo $review_count; ?>)</span>
                </div>
        <?php
    }
}

if (!function_exists('render_product_card_home')) {
    function render_product_card_home($produit, $badge = '', $variant = '')
    {
        $is_shop = ($variant === 'shop');
        $prix_affichage = !empty($produit['prix_promotion']) && $produit['prix_promotion'] < $produit['prix']
            ? $produit['prix_promotion']
            : $produit['prix'];
        $has_promotion = !empty($produit['prix_promotion']) && $produit['prix_promotion'] < $produit['prix'];
        $discount_pct = 0;
        if ($has_promotion && (float) $produit['prix'] > 0) {
            $discount_pct = (int) round((1 - (float) $prix_affichage / (float) $produit['prix']) * 100);
        }
        $image = upload_image_url($produit['image_principale'] ?? '', 'md');
        $nom = htmlspecialchars($produit['nom'] ?? 'Produit sans nom');
        $id = (int) $produit['id'];
        $return_url = htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/index.php');
        $detail_url = public_url('/produit.php?id=' . $id);

        if ($badge === '' && $has_promotion) {
            $badge = 'sale';
        }

        $badge_new_label = $is_shop ? 'New' : 'NEW';
        $card_class = 'home-product-card' . ($is_shop ? ' home-product-card--shop' : '');
        ?>
    <article class="<?php echo $card_class; ?>">
        <?php require __DIR__ . '/partials/product_share_button.php'; ?>
        <?php if ($badge === 'new'): ?>
        <span class="home-product-badge home-product-badge--new"><?php echo $badge_new_label; ?></span>
        <?php elseif (($badge === 'sale' || $has_promotion) && $discount_pct > 0): ?>
        <span class="home-product-badge home-product-badge--sale">-<?php echo $discount_pct; ?>%</span>
        <?php elseif ($badge === 'sale' || $has_promotion): ?>
        <span class="home-product-badge home-product-badge--sale">Promo</span>
        <?php endif; ?>
        <a href="<?php echo htmlspecialchars($detail_url); ?>" class="home-product-card-link" aria-label="Voir <?php echo $nom; ?>">
            <div class="home-product-image">
                <img src="<?php echo $image; ?>" alt="<?php echo $nom; ?>" loading="lazy" decoding="async" onerror="this.src='<?php echo public_url('/image/produit1.jpg'); ?>'">
            </div>
            <div class="home-product-body">
                <h3 class="home-product-name"><?php echo $nom; ?></h3>
                <?php render_product_card_rating($produit); ?>
                <?php if ($is_shop): ?>
                <div class="home-product-price">
                    <?php if ($has_promotion): ?>
                    <?php echo format_price_fcfa_html($prix_affichage); ?>
                    <?php echo format_price_fcfa_html($produit['prix'], 'price-old'); ?>
                    <?php else: ?>
                    <?php echo format_price_fcfa_html($prix_affichage); ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </a>
        <?php if ($is_shop): ?>
        <form method="POST" action="<?php echo public_url('/add-to-panier.php'); ?>" class="home-product-cart-form home-product-cart-form--full">
            <input type="hidden" name="produit_id" value="<?php echo $id; ?>">
            <input type="hidden" name="quantite" value="1">
            <input type="hidden" name="return_url" value="<?php echo $return_url; ?>">
            <button type="submit" class="home-product-add-btn" aria-label="Ajouter <?php echo $nom; ?> au panier">
                <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
                <span>Ajouter au panier</span>
            </button>
        </form>
        <?php else: ?>
        <div class="home-product-footer">
            <a href="<?php echo htmlspecialchars($detail_url); ?>" class="home-product-price" aria-label="Voir <?php echo $nom; ?>">
                <?php if ($has_promotion): ?>
                <?php echo format_price_fcfa_html($produit['prix'], 'price-old'); ?>
                <?php echo format_price_fcfa_html($prix_affichage); ?>
                <?php else: ?>
                <?php echo format_price_fcfa_html($prix_affichage); ?>
                <?php endif; ?>
            </a>
        <form method="POST" action="<?php echo public_url('/add-to-panier.php'); ?>" class="home-product-cart-form">
            <input type="hidden" name="produit_id" value="<?php echo $id; ?>">
            <input type="hidden" name="quantite" value="1">
            <input type="hidden" name="return_url" value="<?php echo $return_url; ?>">
            <button type="submit" class="home-product-cart-btn" aria-label="Ajouter <?php echo $nom; ?> au panier">
                <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
            </button>
        </form>
        </div>
        <?php endif; ?>
    </article>
        <?php
    }
}

if (!function_exists('render_product_spotlight_card')) {
    function render_product_spotlight_card($produit)
    {
        $prix_affichage = !empty($produit['prix_promotion']) && $produit['prix_promotion'] < $produit['prix']
            ? $produit['prix_promotion']
            : $produit['prix'];
        $image = upload_image_url($produit['image_principale'] ?? '', 'original');
        $nom = htmlspecialchars($produit['nom'] ?? 'Produit sans nom');
        $id = (int) $produit['id'];
        $detail_url = public_url('/produit.php?id=' . $id);
        $description = trim(strip_tags((string) ($produit['description'] ?? '')));
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($description) > 110) {
                $description = mb_substr($description, 0, 107) . '...';
            }
        } elseif (strlen($description) > 110) {
            $description = substr($description, 0, 107) . '...';
        }
        if ($description === '') {
            $description = 'Découvrez ce produit frais et de qualité, disponible sur ' . site_brand_name() . '.';
        }
        ?>
    <article class="home-product-spotlight">
        <div class="home-product-spotlight-inner">
            <div class="home-product-spotlight-content">
                <span class="home-product-spotlight-badge">Nouveau</span>
                <h3 class="home-product-spotlight-title"><?php echo $nom; ?></h3>
                <p class="home-product-spotlight-desc"><?php echo htmlspecialchars($description); ?></p>
                <p class="home-product-spotlight-price"><?php echo format_price_fcfa_html($prix_affichage); ?></p>
                <a href="<?php echo htmlspecialchars($detail_url); ?>" class="home-product-spotlight-btn">
                    Explorer <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
            <div class="home-product-spotlight-visual" aria-hidden="true">
                <div class="home-product-spotlight-glow"></div>
                <img src="<?php echo $image; ?>" alt="<?php echo $nom; ?>" loading="lazy" decoding="async" onerror="this.src='<?php echo public_url('/image/produit1.jpg'); ?>'">
            </div>
        </div>
    </article>
        <?php
    }
}

if (!function_exists('render_product_bestseller_item')) {
    function render_product_bestseller_item($produit, int $rank)
    {
        $prix_affichage = !empty($produit['prix_promotion']) && $produit['prix_promotion'] < $produit['prix']
            ? $produit['prix_promotion']
            : $produit['prix'];
        $has_promotion = !empty($produit['prix_promotion']) && $produit['prix_promotion'] < $produit['prix'];
        $image = upload_image_url($produit['image_principale'] ?? '', 'original');
        $nom = htmlspecialchars($produit['nom'] ?? 'Produit sans nom');
        $id = (int) $produit['id'];
        $return_url = htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/index.php');
        $detail_url = public_url('/produit.php?id=' . $id);
        $rank_label = str_pad((string) $rank, 2, '0', STR_PAD_LEFT);
        $subtitle = trim((string) ($produit['categorie_nom'] ?? ''));
        if ($subtitle === '' && !empty($produit['unite'])) {
            $subtitle = trim((string) $produit['unite']);
        }
        ?>
    <article class="home-bestseller-item">
        <div class="home-bestseller-top">
            <div class="home-bestseller-item-meta">
                <span class="home-bestseller-rank" aria-hidden="true"><?php echo $rank_label; ?></span>
                <span class="home-bestseller-badge">Best seller</span>
            </div>
            <a href="<?php echo htmlspecialchars($detail_url); ?>" class="home-bestseller-thumb" tabindex="-1" aria-hidden="true">
                <img src="<?php echo $image; ?>" alt="" loading="lazy" decoding="async" onerror="this.src='<?php echo public_url('/image/produit1.jpg'); ?>'">
            </a>
        </div>
        <div class="home-bestseller-bottom">
            <a href="<?php echo htmlspecialchars($detail_url); ?>" class="home-bestseller-body">
                <h4 class="home-bestseller-name"><?php echo $nom; ?></h4>
                <?php if ($subtitle !== ''): ?>
                <p class="home-bestseller-subtitle"><?php echo htmlspecialchars($subtitle); ?></p>
                <?php endif; ?>
                <p class="home-bestseller-price">
                    <?php if ($has_promotion): ?>
                    <?php echo format_price_fcfa_html($produit['prix'], 'price-old'); ?>
                    <?php echo format_price_fcfa_html($prix_affichage); ?>
                    <?php else: ?>
                    <?php echo format_price_fcfa_html($prix_affichage); ?>
                    <?php endif; ?>
                </p>
                <?php render_product_card_rating($produit, 'home-bestseller-rating'); ?>
            </a>
            <form method="POST" action="<?php echo public_url('/add-to-panier.php'); ?>" class="home-bestseller-cart">
                <input type="hidden" name="produit_id" value="<?php echo $id; ?>">
                <input type="hidden" name="quantite" value="1">
                <input type="hidden" name="return_url" value="<?php echo $return_url; ?>">
                <button type="submit" class="home-bestseller-cart-btn" aria-label="Ajouter <?php echo $nom; ?> au panier">
                    <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
                </button>
            </form>
        </div>
    </article>
        <?php
    }
}

if (!function_exists('render_product_bestseller_strip')) {
    function render_product_bestseller_strip(array $produits)
    {
        $produits = array_slice($produits, 0, 5);
        if (empty($produits)) {
            return;
        }
        ?>
    <div class="home-bestsellers-strip">
        <?php foreach ($produits as $index => $produit): ?>
        <?php render_product_bestseller_item($produit, $index + 1); ?>
        <?php endforeach; ?>
    </div>
        <?php
    }
}
