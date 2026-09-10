<?php
require_once __DIR__ . '/includes/session_user.php';
session_start_persistent();

require_once __DIR__ . '/conn/conn.php';
require_once __DIR__ . '/includes/db_helpers.php';

// Inclusion du fichier de connexion à la BDD

// Récupérez l'ID du commerçant à partir de la session
// Récupérez l'ID de l'utilisateur depuis la variable de session
if (file_exists(__DIR__ . '/controllers/controller_commerce_users.php')) {
    require_once __DIR__ . '/controllers/controller_commerce_users.php';
}

// Meta SEO
require_once __DIR__ . '/includes/site_url.php';
require_once __DIR__ . '/includes/asset_version.php';
require_once __DIR__ . '/includes/site_brand.php';
require_once __DIR__ . '/includes/simple_cache.php';
$base = get_site_base_url();
$seo_title = site_brand_name() . ' — ' . site_brand_tagline();
$seo_description = site_brand_name_market() . ' : produits naturels et essentiels du quotidien. ' . site_brand_tagline() . '.';
$seo_canonical = $base . '/';
?>




<!DOCTYPE html>
<html lang="fr" class="aos-not-ready">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/includes/pwa_meta.php'; ?>
    <?php include __DIR__ . '/includes/seo_meta.php'; ?>
    <?php
    require_once __DIR__ . '/includes/head_public_assets.php';
    render_public_head_assets();
    ?>
    <link rel="stylesheet" href="<?php echo asset_url('/css/style.css'); ?>">
    <style>
    html.aos-not-ready [data-aos] {
        opacity: 1 !important;
        transform: none !important;
    }
    </style>
    <link rel="stylesheet" href="<?php echo asset_url('/css/owl.carousel.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/index-home.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/catalogue-grid.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/responsive-site.css'); ?>">
    <?php if (!defined('FOOTER_CSS_LOADED')): define('FOOTER_CSS_LOADED', true); ?>
    <link rel="stylesheet" href="<?php echo asset_url('/css/footer.css'); ?>">
    <?php endif; ?>

</head>


<body class="page-accueil">

    <?php include('nav_bar.php') ?>

    <?php
    require_once __DIR__ . '/includes/render_product_card.php';
    require_once __DIR__ . '/includes/image_optimizer.php';

    // Slides hero (cache 5 min)
    $slides = [];
    if (file_exists(__DIR__ . '/models/model_slider.php')) {
        $slides = cache_remember('home_slides_actif', 300, function () {
            require_once __DIR__ . '/models/model_slider.php';
            $slides_result = get_all_slides('actif');
            return is_array($slides_result) ? $slides_result : [];
        });
    }

    $produits_nos = [];
    if (file_exists(__DIR__ . '/models/model_produits.php')) {
        require_once __DIR__ . '/models/model_produits.php';
        $produits_nos = get_all_produits_paginated(0, 36);
    }
    $produits_nouveaux = array_slice($produits_nos, 0, 10);

    $produits_bestsellers = [];
    if (file_exists(__DIR__ . '/models/model_visites.php')) {
        require_once __DIR__ . '/models/model_visites.php';
        $produits_bestsellers = array_slice(get_produits_plus_visites(5), 0, 5);
    }

    $produits_vedette = array_slice($produits_nos, 0, 6);

    // Images hero
    $hero_main_image = public_url('/image/market.png');
    if (!empty($slides[0]['image'])) {
        $hero_main_image = upload_image_url('slider/' . $slides[0]['image'], 'md');
    } elseif (!empty($produits_vedette[0]['image_principale'])) {
        $hero_main_image = upload_image_url($produits_vedette[0]['image_principale'], 'md');
    }
    ?>

    <?php if (isset($_GET['added']) && $_GET['added'] == '1'): ?>
    <div class="home-alerts">
        <div class="home-alert home-alert--success">
            <i class="fas fa-check-circle"></i> Produit ajouté au panier avec succès.
        </div>
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['compte_supprime']) && $_GET['compte_supprime'] == '1'): ?>
    <div class="home-alerts">
        <div class="home-alert home-alert--success">
            <i class="fas fa-check-circle"></i> Votre compte a été supprimé définitivement. Merci d'avoir utilisé <?php echo htmlspecialchars(site_brand_name()); ?>.
        </div>
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
    <div class="home-alerts">
        <div class="home-alert home-alert--error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['commande_perso_success'])): ?>
    <div class="home-alerts">
        <div class="home-alert home-alert--success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($_SESSION['commande_perso_success']); unset($_SESSION['commande_perso_success']); ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Hero + barre de confiance (chevauchement) -->
    <div class="home-hero-zone">
    <section class="home-hero">
        <?php if (!empty($slides)): ?>
        <div class="home-hero-slider slider-area owl-carousel">
            <?php foreach ($slides as $slide_index => $slide):
                $slide_link = trim((string) ($slide['bouton_lien'] ?? ''));
                if ($slide_link !== '' && !preg_match('#^https?://#i', $slide_link)) {
                    $slide_link = public_url('/' . ltrim($slide_link, '/'));
                }
                $has_overlay = !empty($slide['paragraphe']) || !empty($slide['bouton_texte']);
                $img_src = upload_image_url('slider/' . ($slide['image'] ?? ''), 'md');
            ?>
            <div class="slider-item<?php echo ($slide_link !== '' && !$has_overlay) ? ' slider-item--linked' : ''; ?>">
                <?php if ($slide_link !== '' && !$has_overlay): ?>
                <a href="<?php echo htmlspecialchars($slide_link); ?>" class="slider-item-link" aria-label="<?php echo htmlspecialchars($slide['titre'] ?? 'Voir'); ?>">
                <?php endif; ?>
                <img src="<?php echo htmlspecialchars($img_src); ?>"
                    alt="<?php echo htmlspecialchars($slide['titre'] ?? 'Slide'); ?>"
                    <?php echo $slide_index === 0 ? 'fetchpriority="high"' : 'loading="lazy"'; ?>
                    decoding="async"
                    onerror="this.src='<?php echo public_url('/image/produit1.jpg'); ?>'">
                <?php if ($slide_link !== '' && !$has_overlay): ?>
                </a>
                <?php endif; ?>
                <?php if ($has_overlay): ?>
                <div class="home-hero-slide-overlay">
                    <div class="home-hero-slide-content">
                        <?php if (!empty($slide['titre'])): ?>
                        <h1><?php echo htmlspecialchars($slide['titre']); ?></h1>
                        <?php endif; ?>
                        <?php if (!empty($slide['paragraphe'])): ?>
                        <p><?php echo htmlspecialchars($slide['paragraphe']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($slide['bouton_texte'])): ?>
                        <a href="<?php echo htmlspecialchars($slide_link !== '' ? $slide_link : public_url('/produits.php')); ?>"
                            class="home-btn-primary">
                            <?php echo htmlspecialchars($slide['bouton_texte']); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="home-hero-inner">
            <div class="home-hero-content">
                <h1>Bienvenue sur votre marché local</h1>
                <p>Découvrez tous vos produits préférés à petit prix</p>
                <a href="<?php echo public_url('/produits.php'); ?>" class="home-btn-primary">Découvrir la boutique</a>
            </div>
            <div class="home-hero-visual">
                <img src="<?php echo $hero_main_image; ?>" alt="<?php echo htmlspecialchars(site_brand_name()); ?>" onerror="this.src='/image/produit1.jpg'">
            </div>
        </div>
        <?php endif; ?>
    </section>

    <!-- Barre de confiance -->
    <section class="home-trust-bar" aria-label="Nos engagements">
        <div class="home-trust-inner">
            <article class="home-trust-item">
                <div class="home-trust-icon" aria-hidden="true">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
                <div class="home-trust-text">
                    <h3 class="home-trust-title">Livraison rapide</h3>
                </div>
            </article>
            <article class="home-trust-item">
                <div class="home-trust-icon" aria-hidden="true">
                    <i class="fa-solid fa-leaf"></i>
                </div>
                <div class="home-trust-text">
                    <h3 class="home-trust-title">Satisfaction garantie</h3>
                </div>
            </article>
            <article class="home-trust-item">
                <div class="home-trust-icon" aria-hidden="true">
                    <i class="fa-solid fa-headset"></i>
                </div>
                <div class="home-trust-text">
                    <h3 class="home-trust-title">Assistance client</h3>
                </div>
            </article>
            <article class="home-trust-item">
                <div class="home-trust-icon" aria-hidden="true">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div class="home-trust-text">
                    <h3 class="home-trust-title">Paiement sécurisé</h3>
                </div>
            </article>
        </div>
    </section>
    </div>

    <!-- Produits en vedette -->
    <section class="home-featured">
        <h2 class="home-section-title">Produits en vedette</h2>
        <div class="home-products-grid">
            <?php if (empty($produits_vedette)): ?>
            <p style="grid-column: 1 / -1; text-align: center; color: var(--color-gris);">Aucun produit disponible pour le moment.</p>
            <?php else: ?>
            <?php foreach ($produits_vedette as $idx => $produit): ?>
            <?php render_product_card_home($produit, $idx === 0 ? 'new' : ''); ?>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <?php
    // Récupérer la configuration de la section4
    $section4_config = [
        'titre' => 'Bienvenue chez ' . site_brand_name(),
        'texte' => 'Tous les produits a petit prix',
        'image_fond' => 'market.png',
        'statut' => 'actif'
    ];

    if (file_exists(__DIR__ . '/models/model_section4.php')) {
        $config_result = cache_remember('home_section4_config', 300, function () {
            require_once __DIR__ . '/models/model_section4.php';
            return get_section4_config();
        });
        if ($config_result) {
            $section4_config = $config_result;
        }
    }

    // Afficher la section4 uniquement si statut = actif
    $section4_actif = ($section4_config['statut'] ?? 'actif') === 'actif';
    $section4_titre = trim($section4_config['titre'] ?? '');
    $section4_texte = trim($section4_config['texte'] ?? '');

    // Chemin de l'image de fond
    $image_fond_path = public_url('/image/market.png');
    if (!empty($section4_config['image_fond'])) {
        $file_path = __DIR__ . '/upload/section4/' . $section4_config['image_fond'];
        if (file_exists($file_path)) {
            $image_fond_path = public_url('/upload/section4/' . $section4_config['image_fond']);
        } elseif (file_exists(__DIR__ . '/image/' . $section4_config['image_fond'])) {
            $image_fond_path = public_url('/image/' . $section4_config['image_fond']);
        }
    }
    ?>
    <?php if ($section4_actif): ?>
    <section class="home-banner-primary">
        <div class="home-banner-primary-shell">
            <div class="home-banner-primary-frame" style="background-image: url('<?php echo htmlspecialchars($image_fond_path); ?>');">
                <div class="home-banner-primary-overlay" aria-hidden="true"></div>
                <div class="home-banner-primary-content">
                    <?php if ($section4_titre !== ''): ?>
                    <h2><?php echo htmlspecialchars($section4_titre); ?></h2>
                    <?php else: ?>
                    <h2>Boostez votre bien-être au naturel</h2>
                    <?php endif; ?>
                    <?php if ($section4_texte !== ''): ?>
                    <p><?php echo htmlspecialchars($section4_texte); ?></p>
                    <?php else: ?>
                    <p>Des produits essentiels issus de notre production, pour votre cuisine et votre quotidien.</p>
                    <?php endif; ?>
                    <a href="<?php echo public_url('/produits.php'); ?>" class="home-btn-primary">Parcourir maintenant</a>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Nos produits (6 lignes) -->
    <?php
    $produits_ligne1 = array_slice($produits_nos, 0, 6);
    $produit_spotlight = $produits_nos[6] ?? null;
    $produits_ligne2 = array_slice($produits_nos, 7, 4);
    if (empty($produits_bestsellers)) {
        $produits_bestsellers = array_slice($produits_nos, 11, 5);
    }
    $produits_ligne4 = array_slice($produits_nos, 16, 6);
    $produits_ligne5 = array_slice($produits_nos, 22, 6);
    $produits_ligne6 = array_slice($produits_nos, 28, 6);
    ?>
    <section class="home-our-products home-our-products--spotlight">
        <div class="home-products-shell">
        <h2 class="home-section-title">Nos produits</h2>

        <?php if (empty($produits_nos)): ?>
        <p class="home-our-products-empty">Aucun produit disponible pour le moment.</p>
        <?php else: ?>

        <!-- Ligne 1 -->
        <?php if (!empty($produits_ligne1)): ?>
        <div class="home-products-grid home-products-grid--six home-products-row">
            <?php foreach ($produits_ligne1 as $produit): ?>
            <?php render_product_card_home($produit); ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Ligne 2 — Nouveautés (spotlight + 4 cartes) -->
        <?php if ($produit_spotlight || !empty($produits_ligne2)): ?>
        <div class="home-products-row-block home-products-row-block--arrivals">
        <div class="home-products-row-header">
            <h3 class="home-products-row-title">Nouveautés</h3>
            <div class="home-products-row-actions">
                <a href="<?php echo public_url('/nouveautes.php'); ?>" class="home-products-row-link">Voir tout</a>
                <div class="home-products-row-nav" aria-hidden="true">
                    <span class="home-products-row-nav-btn"><i class="fa-solid fa-chevron-left"></i></span>
                    <span class="home-products-row-nav-btn"><i class="fa-solid fa-chevron-right"></i></span>
                </div>
            </div>
        </div>
        <div class="home-arrivals-row">
            <?php if ($produit_spotlight): ?>
            <?php render_product_spotlight_card($produit_spotlight); ?>
            <?php endif; ?>
            <?php foreach ($produits_ligne2 as $produit): ?>
            <?php render_product_card_home($produit, 'new', 'shop'); ?>
            <?php endforeach; ?>
        </div>
        </div>
        <?php endif; ?>

        <!-- Ligne 3 — Meilleures ventes (bande sombre) -->
        <?php if (!empty($produits_bestsellers)): ?>
        <div class="home-products-row-block home-products-row-block--bestsellers">
        <div class="home-products-row-header">
            <h3 class="home-products-row-title">Meilleures ventes</h3>
            <div class="home-products-row-actions">
                <a href="<?php echo public_url('/produits.php'); ?>" class="home-products-row-link">Voir tout</a>
                <div class="home-products-row-nav" aria-hidden="true">
                    <span class="home-products-row-nav-btn"><i class="fa-solid fa-chevron-left"></i></span>
                    <span class="home-products-row-nav-btn"><i class="fa-solid fa-chevron-right"></i></span>
                </div>
            </div>
        </div>
        <?php render_product_bestseller_strip($produits_bestsellers); ?>
        </div>
        <?php endif; ?>

        <!-- Ligne 4 -->
        <?php if (!empty($produits_ligne4)): ?>
        <div class="home-products-grid home-products-grid--six home-products-row">
            <?php foreach ($produits_ligne4 as $produit): ?>
            <?php render_product_card_home($produit); ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Ligne 5 -->
        <?php if (!empty($produits_ligne5)): ?>
        <div class="home-products-grid home-products-grid--six home-products-row">
            <?php foreach ($produits_ligne5 as $produit): ?>
            <?php render_product_card_home($produit); ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Ligne 6 -->
        <?php if (!empty($produits_ligne6)): ?>
        <div class="home-products-grid home-products-grid--six home-products-row">
            <?php foreach ($produits_ligne6 as $produit): ?>
            <?php render_product_card_home($produit); ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php endif; ?>

        <div class="home-our-products-cta">
            <a href="<?php echo public_url('/produits.php'); ?>" class="home-btn-primary home-btn-primary--outline">
                Voir plus <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
        </div>
    </section>

    <?php include('footer.php') ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>
    <script src="<?php echo asset_url('/js/owl.carousel.min.js'); ?>" defer></script>
    <script defer>
    document.documentElement.classList.remove('aos-not-ready');

    function initHomeHeroSlider() {
        if (typeof jQuery === 'undefined' || typeof jQuery.fn.owlCarousel === 'undefined') {
            return;
        }
        var $heroSlider = jQuery('.home-hero-slider');
        if (!$heroSlider.length || $heroSlider.find('.slider-item').length === 0) {
            return;
        }
        if ($heroSlider.hasClass('owl-loaded')) {
            return;
        }
        var slideCount = $heroSlider.find('.slider-item').length;
        $heroSlider.owlCarousel({
            items: 1,
            loop: slideCount > 1,
            dots: slideCount > 1,
            autoplay: slideCount > 1,
            autoplayTimeout: 6000,
            autoplayHoverPause: true,
            smartSpeed: 700,
            nav: slideCount > 1,
            navText: [
                '<i class="fa-solid fa-chevron-left" aria-hidden="true"></i>',
                '<i class="fa-solid fa-chevron-right" aria-hidden="true"></i>'
            ]
        });
    }

    document.addEventListener('DOMContentLoaded', initHomeHeroSlider);
    window.addEventListener('load', initHomeHeroSlider);
    </script>

</body>

</html>