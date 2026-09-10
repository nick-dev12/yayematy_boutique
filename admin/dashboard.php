<?php
/**
 * Page d'accueil du tableau de bord administrateur
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/includes/admin_auth.php';

require_once __DIR__ . '/../includes/admin_permissions.php';

require_once __DIR__ . '/../includes/site_brand.php';
require_once __DIR__ . '/../includes/firebase_config_loader.php';
require_once __DIR__ . '/includes/dashboard_data.php';

$enable_firebase_notifications = firebase_config_is_available();
$firebase_notify_type = 'admin';

function dash_format_fcfa($montant)
{
    return number_format((float) $montant, 0, ',', ' ') . ' FCFA';
}

function dash_date_fr()
{
    $jours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
    $mois = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    return ucfirst($jours[(int) date('w')]) . ' ' . date('j') . ' ' . $mois[(int) date('n')] . ' ' . date('Y');
}

require_once __DIR__ . '/includes/render_dash_product_card.php';

$dash = admin_dashboard_load_data();
$categories = $dash['categories'];
$produits_all = $dash['produits_all'];
$nb_produits_total = $dash['nb_produits_total'];
$total_commandes = $dash['total_commandes'];
$commandes_perso_en_attente = $dash['commandes_perso_en_attente'];
$en_attente = $dash['en_attente'];
$prise_en_charge = $dash['prise_en_charge'];
$stats_mois = $dash['stats_mois'];
$stats_jour = $dash['stats_jour'];
$nb_rupture = $dash['nb_rupture'];
$nb_promo = $dash['nb_promo'];
$nb_categories = $dash['nb_categories'];
$produits_plus_vendus = $dash['produits_plus_vendus'];
$produits_aleatoires = $dash['produits_aleatoires'];
$dash_data_error = !empty($dash['data_error']);

$admin_display = trim((string) ($_SESSION['admin_prenom'] ?? ''));
if ($admin_display === '') {
    $admin_display = trim((string) ($_SESSION['admin_nom'] ?? ''));
}
if ($admin_display === '') {
    $admin_display = 'Admin';
}

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Administration Yaye Maty</title>
    <?php require_once __DIR__ . '/../includes/asset_version.php'; ?>
    <?php include __DIR__ . '/../includes/pwa_meta.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-dashboard-home.css'); ?>">
</head>

<body class="page-dashboard-admin">
    <?php include 'includes/nav.php'; ?>

    <!-- Barre de navigation verticale -->

    <!-- Contenu principal -->
    <div class="contents-container dash-hub">

        <header class="dash-hero">
            <div class="dash-hero__inner">
                <div class="dash-hero__content">
                    <p class="dash-hero__eyebrow">
                        <i class="fa-solid fa-circle" aria-hidden="true"></i>
                        <?php echo htmlspecialchars(site_brand_name_market()); ?>
                    </p>
                    <h1 class="dash-hero__title">Bonjour, <span><?php echo htmlspecialchars($admin_display); ?></span></h1>
                    <div class="dash-hero__actions">
                        <a href="produits/ajouter.php" class="btn-primary">
                            <i class="fas fa-plus"></i> Nouveau produit
                        </a>
                        <a href="commandes/index.php" class="dash-btn-outline">
                            <i class="fas fa-shopping-bag"></i> Commandes
                        </a>
                        <?php include __DIR__ . '/includes/btn_retour_site.php'; ?>
                        <button type="button" id="btn-install-pwa" class="dash-btn-outline"
                            title="Installer l'application" style="display: none;">
                            <i class="fas fa-download"></i> App
                        </button>
                    </div>
                </div>
                <div class="dash-hero__meta">
                    <span class="dash-hero__clock" id="dashClock"><?php echo date('H:i'); ?></span>
                    <span class="dash-hero__date"><?php echo dash_date_fr(); ?></span>
                </div>
            </div>
        </header>

        <?php if ($dash_data_error): ?>
        <div class="dash-alerts">
            <div class="dash-alert dash-alert--warn">
                <p><i class="fas fa-exclamation-triangle"></i> Certaines statistiques n'ont pas pu être chargées. Vérifiez la connexion à la base de données.</p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['notification_test_message'])) {
            $test_msg = $_SESSION['notification_test_message'];
            $test_type = $_SESSION['notification_test_type'] ?? 'success';
            unset($_SESSION['notification_test_message'], $_SESSION['notification_test_type']);
            ?>
        <div class="dash-alerts">
            <div class="dash-alert dash-alert--<?php echo $test_type === 'success' ? 'info' : 'warn'; ?>">
                <p><i class="fas fa-<?php echo $test_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($test_msg); ?></p>
            </div>
        </div>
        <?php } ?>

        <section class="dash-kpi-grid" aria-label="Indicateurs clés">
            <article class="dash-kpi dash-kpi--wide dash-kpi--accent">
                <span class="dash-kpi__spark" aria-hidden="true"></span>
                <div class="dash-kpi__top">
                    <div>
                        <p class="dash-kpi__label">Chiffre du mois</p>
                        <p class="dash-kpi__value" data-count="<?php echo (int) $stats_mois['montant_total']; ?>">
                            <?php echo dash_format_fcfa($stats_mois['montant_total']); ?>
                        </p>
                    </div>
                    <span class="dash-kpi__icon"><i class="fa-solid fa-coins"></i></span>
                </div>
                <p class="dash-kpi__hint"><?php echo (int) $stats_mois['nb_commandes']; ?> commande<?php echo $stats_mois['nb_commandes'] > 1 ? 's' : ''; ?> ce mois</p>
            </article>
            <article class="dash-kpi dash-kpi--bleu">
                <span class="dash-kpi__spark" aria-hidden="true"></span>
                <div class="dash-kpi__top">
                    <div>
                        <p class="dash-kpi__label">Aujourd'hui</p>
                        <p class="dash-kpi__value"><?php echo (int) $stats_jour['nb_commandes']; ?></p>
                    </div>
                    <span class="dash-kpi__icon"><i class="fa-solid fa-bolt"></i></span>
                </div>
                <p class="dash-kpi__hint"><?php echo dash_format_fcfa($stats_jour['montant_total']); ?> de ventes</p>
            </article>
            <article class="dash-kpi dash-kpi--jaune">
                <span class="dash-kpi__spark" aria-hidden="true"></span>
                <div class="dash-kpi__top">
                    <div>
                        <p class="dash-kpi__label">Catalogue</p>
                        <p class="dash-kpi__value"><?php echo $nb_produits_total; ?></p>
                    </div>
                    <span class="dash-kpi__icon"><i class="fa-solid fa-leaf"></i></span>
                </div>
                <p class="dash-kpi__hint"><?php echo $nb_categories; ?> catégories · <?php echo $nb_promo; ?> en promo</p>
            </article>
            <article class="dash-kpi dash-kpi--accent">
                <span class="dash-kpi__spark" aria-hidden="true"></span>
                <div class="dash-kpi__top">
                    <div>
                        <p class="dash-kpi__label">Stock critique</p>
                        <p class="dash-kpi__value"><?php echo $nb_rupture; ?></p>
                    </div>
                    <span class="dash-kpi__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                </div>
                <p class="dash-kpi__hint">Produits en rupture ou épuisés</p>
            </article>
        </section>

        <?php if ($en_attente > 0 || $prise_en_charge > 0 || $commandes_perso_en_attente > 0): ?>
        <div class="dash-alerts">
            <?php if ($en_attente > 0 || $prise_en_charge > 0): ?>
            <div class="dash-alert dash-alert--warn">
                <p>
                    <i class="fas fa-bell"></i>
                    <?php if ($en_attente > 0): ?>
                        <?php echo $en_attente; ?> commande<?php echo $en_attente > 1 ? 's' : ''; ?> en attente de prise en charge
                    <?php else: ?>
                        <?php echo $prise_en_charge; ?> commande<?php echo $prise_en_charge > 1 ? 's' : ''; ?> prête<?php echo $prise_en_charge > 1 ? 's' : ''; ?> à expédier
                    <?php endif; ?>
                </p>
                <a href="commandes/index.php" class="btn-alert">Traiter <i class="fas fa-arrow-right"></i></a>
            </div>
            <?php endif; ?>
            <?php if ($commandes_perso_en_attente > 0): ?>
            <div class="dash-alert dash-alert--info">
                <p>
                    <i class="fas fa-palette"></i>
                    <?php echo $commandes_perso_en_attente; ?> commande<?php echo $commandes_perso_en_attente > 1 ? 's' : ''; ?> personnalisée<?php echo $commandes_perso_en_attente > 1 ? 's' : ''; ?> en attente
                </p>
                <a href="commandes-personnalisees/index.php" class="btn-alert">Voir <i class="fas fa-arrow-right"></i></a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <section class="dash-products-block" aria-label="Produits les plus vendus">
            <header class="dash-products-block__head">
                <div>
                    <h2><i class="fa-solid fa-chart-line"></i> Produits les plus vendus</h2>
                    <p>Les références qui performent le mieux sur votre boutique.</p>
                </div>
                <a href="produits/index.php" class="dash-btn-outline dash-btn-outline--sm">
                    Catalogue <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </a>
            </header>
            <?php if (empty($produits_plus_vendus)): ?>
            <div class="dash-products-empty">
                <i class="fa-solid fa-box-open"></i>
                <p>Aucune vente enregistrée pour le moment.</p>
                <a href="produits/ajouter.php" class="btn-primary"><i class="fas fa-plus"></i> Ajouter un produit</a>
            </div>
            <?php else: ?>
            <div class="dash-prod-grid">
                <?php
                $rank = 0;
                foreach ($produits_plus_vendus as $produit):
                    $rank++;
                    $total_vendu = isset($produit['total_vendu']) ? (int) $produit['total_vendu'] : null;
                    render_dash_product_card($produit, $rank, $total_vendu);
                endforeach;
                ?>
            </div>
            <?php endif; ?>
        </section>

        <section class="dash-products-block dash-products-block--alt" aria-label="Découvrir aussi">
            <header class="dash-products-block__head">
                <div>
                    <h2><i class="fa-solid fa-shuffle"></i> Découvrir aussi</h2>
                    <p>Une sélection aléatoire pour parcourir votre catalogue.</p>
                </div>
            </header>
            <?php if (empty($produits_aleatoires)): ?>
            <div class="dash-products-empty">
                <i class="fa-solid fa-seedling"></i>
                <p>Aucun autre produit à afficher.</p>
            </div>
            <?php else: ?>
            <div class="dash-prod-grid dash-prod-grid--compact">
                <?php foreach ($produits_aleatoires as $produit):
                    render_dash_product_card($produit);
                endforeach; ?>
            </div>
            <div class="dash-products-more">
                <a href="produits/index.php" class="dash-btn-more">
                    Voir plus <span class="dash-btn-more__count"><?php echo (int) $nb_produits_total; ?> produits</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
            <?php endif; ?>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var clockEl = document.getElementById('dashClock');
            if (clockEl) {
                setInterval(function () {
                    var now = new Date();
                    clockEl.textContent = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
                }, 30000);
            }

            var installBtn = document.getElementById('btn-install-pwa');
            var deferredPrompt;

            if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
                if (installBtn) installBtn.style.display = 'none';
            } else {
                window.addEventListener('beforeinstallprompt', function (e) {
                    e.preventDefault();
                    deferredPrompt = e;
                    if (installBtn) installBtn.style.display = 'inline-flex';
                });

                if (installBtn) {
                    installBtn.addEventListener('click', function () {
                        if (!deferredPrompt) {
                            alert(
                                'L\'installation n\'est pas disponible. Essayez depuis Chrome ou Edge en mode HTTPS.'
                            );
                            return;
                        }
                        deferredPrompt.prompt();
                        deferredPrompt.userChoice.then(function (choiceResult) {
                            if (choiceResult.outcome === 'accepted') {
                                installBtn.style.display = 'none';
                            }
                            deferredPrompt = null;
                        });
                    });
                }
            }
        });
    </script>
    <?php include 'includes/footer.php'; ?>