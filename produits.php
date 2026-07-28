<?php
require_once __DIR__ . '/includes/session_user.php';
session_start_persistent();

// Inclusion des modèles
require_once __DIR__ . '/models/model_produits.php';

// Récupérer les produits (recherche + filtres ou tous)
$produits_tous = [];
$total_produits = 0;
$recherche_actuelle = isset($_GET['recherche']) ? trim($_GET['recherche']) : '';
$prix_min = isset($_GET['prix_min']) && $_GET['prix_min'] !== '' ? (float) $_GET['prix_min'] : null;
$prix_max = isset($_GET['prix_max']) && $_GET['prix_max'] !== '' ? (float) $_GET['prix_max'] : null;
$categorie_id = isset($_GET['categorie']) && $_GET['categorie'] !== '' ? (int) $_GET['categorie'] : null;
$tri = isset($_GET['tri']) && in_array($_GET['tri'], ['date', 'prix_asc', 'prix_desc', 'nom']) ? $_GET['tri'] : 'date';
$has_filters = !empty($recherche_actuelle) || $prix_min !== null || $prix_max !== null || $categorie_id !== null || $tri !== 'date';

if (file_exists(__DIR__ . '/models/model_produits.php')) {
    if ($has_filters) {
        $produits_tous = search_produits_with_filters($recherche_actuelle, $prix_min, $prix_max, $categorie_id, $tri, 0, 20);
        $total_produits = count_search_produits_with_filters($recherche_actuelle, $prix_min, $prix_max, $categorie_id);
    } else {
        $produits_tous = get_all_produits_paginated(0, 20);
        $total_produits = count_all_produits_actifs();
    }
}

// Inclusion du fichier de connexion à la BDD (pour les autres fonctionnalités si nécessaire)
if (file_exists(__DIR__ . '/controllers/controller_commerce_users.php')) {
    require_once __DIR__ . '/controllers/controller_commerce_users.php';
}

// Meta SEO
require_once __DIR__ . '/includes/site_url.php';
$base = get_site_base_url();
$seo_title = 'Produits décoratifs pour gâteaux - Yaye Maty';
$seo_description = 'Catalogue de produits décoratifs pour gâteaux : gâteaux d\'anniversaire, mariage, cérémonies. Décoration comestible et non comestible. Personnalisation à grande échelle.';
$seo_canonical = $base . '/produits.php';
?>

<!DOCTYPE html>
<html lang="fr">

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
    <link rel="stylesheet" href="/css/a_style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/catalogue-grid.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/product-cards.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/catalogue-responsive.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/responsive-site.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/product-share.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <style>
        .produits-page-header {
            background: var(--couleur-dominante);
            padding: 2.5rem 1.25rem;
            text-align: center;
            color: var(--texte-clair);
            margin-bottom: 2.5rem;
            width: 100%;
            box-sizing: border-box;
        }

        .produits-page-header h1 {
            font-size: clamp(1.75rem, 4vw, 2rem);
            margin-bottom: 0.625rem;
            font-weight: 700;
        }

        .produits-page-header p {
            font-size: 1rem;
            opacity: 0.9;
        }

        .btn-voir-plus {
            padding: 0.9375rem 2.5rem;
            background: var(--couleur-dominante);
            color: #ffffff;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            margin: 1.875rem auto;
        }

        .btn-voir-plus:hover {
            background: rgba(242, 92, 25, 0.9);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(242, 92, 25, 0.3);
        }

        .btn-voir-plus:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .produits-count {
            text-align: center;
            margin-top: 0.9375rem;
            color: #666;
            font-size: 0.875rem;
        }

        body.page-produits {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        body.page-produits .produits-container-wrapper {
            flex: 1;
            padding-bottom: 6.25rem;
        }

        body.page-produits .footer {
            margin-top: 5rem;
            position: relative;
            width: 100%;
            clear: both;
            flex-shrink: 0;
        }

        body.page-produits .section00 {
            margin-bottom: 3.75rem;
            padding: 0;
            background: transparent;
        }

        .filtres-actifs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: center;
            margin-top: 0.75rem;
            font-size: 0.875rem;
            opacity: 0.95;
        }

        .filtres-actifs span {
            background: rgba(255, 255, 255, 0.25);
            padding: 0.375rem 0.75rem;
            border-radius: 1.25rem;
        }
    </style>
</head>

<body class="page-produits">
    <?php
    require_once __DIR__ . '/includes/render_product_card.php';
    include('nav_bar.php');
    ?>

    <div class="produits-page-header">
        <h1><i class="fas fa-box"></i>
            <?php echo !empty($recherche_actuelle) ? 'Résultats pour "' . htmlspecialchars($recherche_actuelle) . '"' : 'Tous nos produits'; ?>
        </h1>
        <?php if ($has_filters): ?>
            <p><?php echo $total_produits . ' produit(s) trouvé(s)'; ?></p>
            <p class="filtres-actifs">
                <?php if (!empty($recherche_actuelle)): ?><span><i class="fas fa-search"></i>
                        <?php echo htmlspecialchars($recherche_actuelle); ?></span><?php endif; ?>
                <?php if ($prix_min !== null): ?><span><i class="fas fa-coins"></i> Min
                        <?php echo number_format($prix_min, 0, ',', ' '); ?> FCFA</span><?php endif; ?>
                <?php if ($prix_max !== null): ?><span><i class="fas fa-coins"></i> Max
                        <?php echo number_format($prix_max, 0, ',', ' '); ?> FCFA</span><?php endif; ?>
            </p>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['added']) && $_GET['added'] == '1'): ?>
        <div
            style="max-width: 600px; margin: 20px auto; padding: 15px 25px; background: rgba(46, 125, 181, 0.15); border-left: 4px solid var(--turquoise); border-radius: 8px; color: var(--titres);">
            <i class="fas fa-check-circle"></i> Produit ajouté au panier avec succès.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div
            style="max-width: 600px; margin: 20px auto; padding: 15px 25px; background: rgba(242, 92, 25, 0.15); border-left: 4px solid var(--couleur-dominante); border-radius: 8px; color: var(--titres);">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>
    <div class="produits-container-wrapper">
        <section class="section00">
            <section class="produit_vedetes">
                <div class="catalogue-products-section">
                <div class="catalogue-products-grid" id="produits-container">
                    <?php if (empty($produits_tous)): ?>
                        <div style="text-align: center; padding: 40px; color: #666; grid-column: 1 / -1;">
                            <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                            <p style="font-size: 16px;">Aucun produit publié pour le moment.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($produits_tous as $produit): ?>
                            <?php render_product_card_home($produit); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if (!empty($produits_tous) && $total_produits > 20): ?>
                    <div style="text-align: center; margin-top: 40px; padding: 20px;">
                        <button id="btn-voir-plus" class="btn-voir-plus" onclick="chargerPlusProduits()">
                            <i class="fas fa-chevron-down"></i> Voir plus
                        </button>
                        <p id="produits-count" class="produits-count">
                            Affichés: <span id="count-actuel">20</span> / <?php echo $total_produits; ?> produits
                        </p>
                    </div>
                <?php endif; ?>
                </div>
            </section>
        </section>
    </div>

    <?php include('footer.php'); ?>

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        AOS.init();

        let offsetActuel = 20; // On a déjà affiché les 20 premiers
        const limit = 20;
        const totalProduits = <?php echo $total_produits; ?>;
        const apiBaseParams = '<?php
        $p = ['offset' => 0, 'limit' => 20];
        if ($has_filters) {
            if (!empty($recherche_actuelle))
                $p['recherche'] = $recherche_actuelle;
            if ($prix_min !== null)
                $p['prix_min'] = $prix_min;
            if ($prix_max !== null)
                $p['prix_max'] = $prix_max;
            if ($categorie_id !== null)
                $p['categorie'] = $categorie_id;
            $p['tri'] = $tri;
        }
        echo http_build_query($p);
        ?>';

        function getApiUrl() {
            const params = new URLSearchParams(apiBaseParams);
            params.set('offset', offsetActuel);
            return 'api/get_produits.php?' + params.toString();
        }

        function chargerPlusProduits() {
            const btn = document.getElementById('btn-voir-plus');
            const container = document.getElementById('produits-container');
            const countActuel = document.getElementById('count-actuel');

            // Désactiver le bouton pendant le chargement
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Chargement...';

            // Faire la requête AJAX
            fetch(getApiUrl())
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.produits.length > 0) {
                        // Ajouter les nouveaux produits
                        data.produits.forEach(produit => {
                            const article = document.createElement('article');
                            article.className = 'home-product-card';
                            article.setAttribute('data-produit-id', produit.id);

                            const returnUrl = (window.location.pathname + window.location.search).replace(/"/g, '&quot;');
                            let badgeHTML = '';
                            if (produit.has_promotion) {
                                badgeHTML = '<span class="home-product-badge home-product-badge--sale">Promo</span>';
                            }

                            let prixHTML = '';
                            if (produit.has_promotion) {
                                prixHTML = `<span class="price-old">${formatNumber(produit.prix)} FCFA</span>
                                            <span class="price-sale">${formatNumber(produit.prix_affichage)} FCFA</span>`;
                            } else {
                                prixHTML = `${formatNumber(produit.prix_affichage)} FCFA`;
                            }

                            let shareHTML = '';
                            if (typeof window.buildProductShareHtml === 'function') {
                                shareHTML = window.buildProductShareHtml({
                                    url: produit.share_url || '',
                                    text: produit.share_text || produit.nom,
                                    title: produit.share_title || produit.nom
                                });
                            }

                            article.innerHTML = shareHTML + `
                                <a href="produit.php?id=${produit.id}" class="home-product-card-link" aria-label="Voir ${escapeHtml(produit.nom)}">
                                <div class="home-product-image">
                                    ${badgeHTML}
                                    <img src="${escapeHtml(produit.image_url || ('/upload/' + (produit.image_principale || 'produit1.jpg')))}" alt="${escapeHtml(produit.nom)}" onerror="this.src='/image/produit1.jpg'">
                                </div>
                                <div class="home-product-body">
                                    <h3 class="home-product-name">${escapeHtml(produit.nom)}</h3>
                                    <p class="home-product-price">${prixHTML}</p>
                                </div>
                                </a>
                                <form method="POST" action="/add-to-panier.php">
                                    <input type="hidden" name="produit_id" value="${produit.id}">
                                    <input type="hidden" name="quantite" value="1">
                                    <input type="hidden" name="return_url" value="${returnUrl}">
                                    <button type="submit" class="home-btn-primary"><i class="fa-solid fa-cart-shopping"></i> Acheter</button>
                                </form>
                            `;

                            container.appendChild(article);
                        });

                        // Mettre à jour le compteur
                        offsetActuel += data.produits.length;
                        countActuel.textContent = offsetActuel;

                        // Vérifier s'il reste des produits
                        if (offsetActuel >= totalProduits) {
                            btn.style.display = 'none';
                        } else {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-chevron-down"></i> Voir plus';
                        }
                    } else {
                        // Plus de produits à charger
                        btn.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du chargement:', error);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-chevron-down"></i> Voir plus';
                    alert('Une erreur est survenue lors du chargement des produits.');
                });
        }

        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>

</html>