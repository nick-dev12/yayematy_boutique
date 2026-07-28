<?php
require_once __DIR__ . '/includes/session_user.php';
session_start_persistent();


// Inclusion du fichier de connexion à la BDD

// Récupérez l'ID du commerçant à partir de la session
// Récupérez l'ID de l'utilisateur depuis la variable de session
if (file_exists(__DIR__ . '/controllers/controller_commerce_users.php')) {
    require_once __DIR__ . '/controllers/controller_commerce_users.php';
}

// Meta SEO
require_once __DIR__ . '/includes/site_url.php';
require_once __DIR__ . '/includes/site_brand.php';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/style.css<?php echo asset_version_query(); ?>">
    <style>
    html.aos-not-ready [data-aos] {
        opacity: 1 !important;
        transform: none !important;
    }
    </style>
    <link rel="stylesheet" href="/css/a_style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/owl.carousel.min.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/owl.carousel.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/index-home.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/responsive-site.css<?php echo asset_version_query(); ?>">

</head>


<body class="page-accueil">

    <?php include('nav_bar.php') ?>

    <?php
    require_once __DIR__ . '/includes/render_product_card.php';
    require_once __DIR__ . '/includes/image_optimizer.php';

    // Slides hero
    $slides = [];
    if (file_exists(__DIR__ . '/models/model_slider.php')) {
        require_once __DIR__ . '/models/model_slider.php';
        $slides_result = get_all_slides('actif');
        $slides = is_array($slides_result) ? $slides_result : [];
    }

    // Catégories
    $categories = [];
    $top_categories = [];
    if (file_exists(__DIR__ . '/models/model_categories.php')) {
        require_once __DIR__ . '/models/model_categories.php';
        $categories_result = get_all_categories_with_count();
        $categories = is_array($categories_result) ? $categories_result : [];
        $top_categories = get_top_categories(2);
    }

    // Produits
    $produits_nouveaux = [];
    $produits_populaires = [];
    $produits_nos = [];
    if (file_exists(__DIR__ . '/models/model_produits.php')) {
        require_once __DIR__ . '/models/model_produits.php';
        $produits_nouveaux = get_all_produits_paginated(0, 10);
        $produits_nos = get_all_produits_paginated(0, 15);
    }
    if (file_exists(__DIR__ . '/models/model_visites.php')) {
        require_once __DIR__ . '/models/model_visites.php';
        $produits_populaires = get_produits_plus_visites(10);
    }

    $produits_vedette = array_slice(!empty($produits_nouveaux) ? $produits_nouveaux : $produits_populaires, 0, 4);

    // Images hero
    $hero_main_image = '/image/produit1.jpg';
    if (!empty($slides[0]['image'])) {
        $hero_main_image = upload_image_url('slider/' . $slides[0]['image'], 'original');
    } elseif (!empty($produits_vedette[0]['image_principale'])) {
        $hero_main_image = upload_image_url($produits_vedette[0]['image_principale'], 'md');
    }

    // Bannières duo — catégories
    $banner_categories = [];
    if (!empty($categories)) {
        $banner_categories = array_slice($categories, 0, 2);
    } elseif (!empty($top_categories)) {
        $banner_categories = array_slice($top_categories, 0, 2);
    }

    function home_category_banner_image($categorie, $fallback)
    {
        if (!empty($categorie['image'])) {
            return upload_image_url($categorie['image'], 'md');
        }
        return $fallback;
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

    <!-- Hero : slider admin -->
    <section class="home-hero">
        <?php if (!empty($slides)): ?>
        <div class="home-hero-slider slider-area owl-carousel">
            <?php foreach ($slides as $slide): ?>
            <div class="slider-item">
                <img src="<?php echo htmlspecialchars(upload_image_url('slider/' . ($slide['image'] ?? ''), 'original')); ?>"
                    alt="<?php echo htmlspecialchars($slide['titre'] ?? 'Slide'); ?>"
                    onerror="this.src='/image/produit1.jpg'">
                <?php if (!empty($slide['titre']) || !empty($slide['paragraphe']) || !empty($slide['bouton_texte'])): ?>
                <div class="home-hero-slide-overlay">
                    <div class="home-hero-slide-content">
                        <?php if (!empty($slide['titre'])): ?>
                        <h1><?php echo htmlspecialchars($slide['titre']); ?></h1>
                        <?php endif; ?>
                        <?php if (!empty($slide['paragraphe'])): ?>
                        <p><?php echo htmlspecialchars($slide['paragraphe']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($slide['bouton_texte'])): ?>
                        <a href="<?php echo htmlspecialchars(!empty($slide['bouton_lien']) ? $slide['bouton_lien'] : '/produits.php'); ?>"
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
                <a href="/produits.php" class="home-btn-primary">Découvrir la boutique</a>
            </div>
            <div class="home-hero-visual">
                <img src="<?php echo $hero_main_image; ?>" alt="<?php echo htmlspecialchars(site_brand_name()); ?>" onerror="this.src='/image/produit1.jpg'">
            </div>
        </div>
        <?php endif; ?>
    </section>

    <!-- Barre de confiance -->
    <section class="home-trust-bar">
        <div class="home-trust-inner">
            <div class="home-trust-item" aria-label="Livraison rapide sur Dakar et environs">
                <div class="home-trust-icon"><i class="fa-solid fa-truck-fast" aria-hidden="true"></i></div>
                <span>Livraison rapide sur Dakar et environs</span>
            </div>
            <div class="home-trust-item" aria-label="Satisfaction garantie — produits 100 % naturels">
                <div class="home-trust-icon"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></div>
                <span>Satisfaction garantie — produits 100 % naturels</span>
            </div>
            <div class="home-trust-item" aria-label="Assistance client 7 jours sur 7">
                <div class="home-trust-icon"><i class="fa-solid fa-headset" aria-hidden="true"></i></div>
                <span>Assistance client 7 jours sur 7</span>
            </div>
        </div>
    </section>

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
        require_once __DIR__ . '/models/model_section4.php';
        $config_result = get_section4_config();
        if ($config_result) {
            $section4_config = $config_result;
        }
    }

    // Afficher la section4 uniquement si statut = actif
    $section4_actif = ($section4_config['statut'] ?? 'actif') === 'actif';
    $section4_titre = trim($section4_config['titre'] ?? '');
    $section4_texte = trim($section4_config['texte'] ?? '');

    // Chemin de l'image de fond
    $image_fond_path = '/image/market.png';
    if (!empty($section4_config['image_fond'])) {
        $upload_path = '/upload/section4/' . htmlspecialchars($section4_config['image_fond']);
        $file_path = __DIR__ . '/upload/section4/' . $section4_config['image_fond'];
        if (file_exists($file_path)) {
            $image_fond_path = $upload_path;
        }
    }
    ?>
    <?php if ($section4_actif): ?>
    <section class="home-banner-primary" style="background-image: url('<?php echo $image_fond_path; ?>');">
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
            <a href="/produits.php" class="home-btn-primary">Parcourir maintenant</a>
        </div>
    </section>
    <?php endif; ?>

    <!-- Bannières duo : catégories -->
    <?php if (!empty($banner_categories)): ?>
    <section class="home-banners-duo">
        <div class="home-banners-duo-inner">
            <?php foreach ($banner_categories as $cat_banner): ?>
            <a href="/categorie.php?id=<?php echo (int) $cat_banner['id']; ?>" class="home-banner-card" style="background-image: url('<?php echo home_category_banner_image($cat_banner, $hero_main_image); ?>');">
                <h3><?php echo htmlspecialchars($cat_banner['nom']); ?></h3>
                <span class="home-btn-primary">Voir la catégorie</span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Nos produits -->
    <section class="home-our-products">
        <h2 class="home-section-title">Nos produits</h2>
        <div class="home-products-grid">
            <?php if (empty($produits_nos)): ?>
            <p class="home-our-products-empty">Aucun produit disponible pour le moment.</p>
            <?php else: ?>
            <?php foreach ($produits_nos as $produit): ?>
            <?php render_product_card_home($produit); ?>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="home-our-products-cta">
            <a href="/produits.php" class="home-btn-primary home-btn-primary--outline">
                Voir plus <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="home-newsletter">
        <div class="home-newsletter-inner">
            <div class="home-newsletter-text">
                <h2>Rejoignez notre newsletter</h2>
                <p>Recevez nos offres, nouveautés et conseils directement dans votre boîte mail.</p>
            </div>
            <form class="home-newsletter-form" action="mailto:sugarpaper26@gmail.com?subject=Inscription%20newsletter" method="post" enctype="text/plain">
                <input type="email" name="email" placeholder="Votre e-mail" required aria-label="Adresse e-mail">
                <button type="submit" class="home-btn-primary">S'abonner</button>
            </form>
        </div>
    </section>

    <?php include('footer.php') ?>

    <script src="/js/owl.carousel.min.js"></script>
    <script src="/js/owl.carousel.js"></script>
    <script src="/js/owl.autoplay.js"></script>
    <script>
    document.documentElement.classList.remove('aos-not-ready');

    $(document).ready(function () {
        var $heroSlider = $('.home-hero-slider');
        if ($heroSlider.length && $heroSlider.find('.slider-item').length > 0) {
            $heroSlider.owlCarousel({
                items: 1,
                loop: $heroSlider.find('.slider-item').length > 1,
                dots: true,
                autoplay: true,
                autoplayTimeout: 6000,
                autoplayHoverPause: true,
                smartSpeed: 700,
                nav: true,
                navText: [
                    '<i class="fa-solid fa-chevron-left"></i>',
                    '<i class="fa-solid fa-chevron-right"></i>'
                ]
            });
        }
    });
    </script>

</body>

</html>