<?php
if (!function_exists('get_asset_version')) {
    require_once __DIR__ . '/includes/asset_version.php';
}
require_once __DIR__ . '/includes/store_nav_account.php';
require_once __DIR__ . '/includes/site_brand.php';
$store_nav_account = store_nav_account_info();
$asset_version = isset($asset_version) ? $asset_version : get_asset_version();

// Compter les articles du panier (connecté ou invité)
$panier_count = 0;
$panier_path = file_exists(__DIR__ . '/includes/panier_invite.php')
    ? __DIR__ . '/includes/panier_invite.php'
    : dirname(__DIR__) . '/includes/panier_invite.php';
if (file_exists($panier_path)) {
    require_once $panier_path;
    if (function_exists('panier_count_items_courant')) {
        $panier_count = panier_count_items_courant();
    }
}

// Catégories pour le menu et le filtre recherche (cache 10 min)
$categories_menu = [];
if (file_exists(__DIR__ . '/models/model_categories.php')) {
    require_once __DIR__ . '/models/model_categories.php';
    require_once __DIR__ . '/includes/simple_cache.php';
    $categories_menu = cache_remember('nav_categories_menu', 600, function () {
        $cats = get_all_categories();
        return is_array($cats) ? $cats : [];
    });
}

$nav_categorie_selected = isset($_GET['categorie']) ? (string) $_GET['categorie'] : '';
$nav_user_connected = isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
$nav_current_script = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$nav_current_categorie_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

/**
 * Classe active pour un lien du menu latéral
 */
function nav_sidebar_link_class($script_names, $current_script)
{
    $scripts = is_array($script_names) ? $script_names : [$script_names];
    return in_array($current_script, $scripts, true) ? ' nav-sidebar-link--active' : '';
}
?>
<?php
if (!defined('PUBLIC_HEAD_ASSETS_LOADED')) {
    require_once __DIR__ . '/includes/head_public_assets.php';
    render_public_head_assets();
}
?>

<!-- Barre principale : logo · recherche · compte / panier -->
<nav class="nav-planete-gateau">
    <div class="nav-brand-row">
        <a class="nav-brand-block" href="<?php echo public_url('/index.php'); ?>">
            <span class="logo">
                <img src="<?php echo site_brand_logo(); ?>" alt="<?php echo htmlspecialchars(site_brand_logo_alt()); ?>">
            </span>
        </a>
        <a href="<?php echo public_url('/nouveautes.php'); ?>" class="nav-nouveautes-btn<?php echo $nav_current_script === 'nouveautes.php' ? ' is-active' : ''; ?>">
            Nouveautés
            <span class="nav-badge nav-badge--new">NEW</span>
        </a>
    </div>

    <div class="nav-search-cluster">
        <div class="nav-search-wrapper">
            <form class="nav-search-form" action="<?php echo public_url('/produits.php'); ?>" method="get" id="nav-search-form">
                <select name="categorie" id="nav-categorie" class="nav-search-category" aria-label="Catégorie">
                    <option value="">Toutes catégories</option>
                    <?php foreach ($categories_menu as $categorie): ?>
                    <option value="<?php echo (int) $categorie['id']; ?>"
                        <?php echo $nav_categorie_selected === (string) $categorie['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($categorie['nom']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="recherche" id="nav-search" class="nav-search-input"
                    placeholder="Rechercher"
                    value="<?php echo !empty($_GET['recherche']) ? htmlspecialchars($_GET['recherche']) : ''; ?>">
                <input type="hidden" name="prix_min" id="nav-prix-min"
                    value="<?php echo isset($_GET['prix_min']) ? htmlspecialchars($_GET['prix_min']) : ''; ?>">
                <input type="hidden" name="prix_max" id="nav-prix-max"
                    value="<?php echo isset($_GET['prix_max']) ? htmlspecialchars($_GET['prix_max']) : ''; ?>">
                <input type="hidden" name="tri" id="nav-tri"
                    value="<?php echo isset($_GET['tri']) ? htmlspecialchars($_GET['tri']) : ''; ?>">
                <button type="submit" class="nav-search-btn" aria-label="Rechercher">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>
        </div>
        <div class="nav-lang-switcher" title="Langue">
            <?php
            require_once __DIR__ . '/includes/gtranslate.php';
            gtranslate_render_widget();
            ?>
        </div>
    </div>

    <div class="nav-header-actions">
        <a href="<?php echo public_url('/panier.php'); ?>"
            class="revo-util-icon"
            title="Panier<?php echo $panier_count > 0 ? ' (' . $panier_count . ')' : ''; ?>">
            <i class="fa-solid fa-cart-shopping"></i>
            <?php if ($panier_count > 0): ?>
            <span class="revo-util-badge"><?php echo $panier_count > 99 ? '99+' : $panier_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo $nav_user_connected ? public_url('/user/mon-compte.php') : public_url('/user/connexion.php'); ?>"
            class="revo-util-icon"
            title="<?php echo $nav_user_connected ? 'Mon compte' : 'Connexion'; ?>">
            <i class="fa-regular fa-user"></i>
        </a>
    </div>
</nav>

<!-- Overlay et sidebar menu latéral -->
<div class="nav-sidebar-overlay" id="navSidebarOverlay"></div>
<aside class="nav-sidebar" id="navSidebar" aria-label="Menu de navigation">
    <div class="nav-sidebar-header">
        <a href="<?php echo public_url('/index.php'); ?>" class="nav-sidebar-brand">
            <span class="nav-sidebar-brand-mark">
                <img src="<?php echo site_brand_logo(); ?>" alt="" class="nav-sidebar-brand-logo">
            </span>
            <span class="nav-sidebar-brand-text">
                <span class="nav-sidebar-brand-name"><?php echo htmlspecialchars(site_brand_name()); ?></span>
                <span class="nav-sidebar-brand-tagline"><?php echo htmlspecialchars(site_brand_tagline()); ?></span>
            </span>
        </a>
        <button type="button" class="nav-sidebar-close" id="navSidebarClose" aria-label="Fermer le menu">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <nav class="nav-sidebar-body">
        <div class="nav-sidebar-scroll">
        <div class="nav-sidebar-panel">
            <p class="nav-sidebar-section-label">Menu principal</p>
            <div class="nav-sidebar-group">
                <a href="<?php echo public_url('/index.php'); ?>"
                    class="nav-sidebar-link<?php echo nav_sidebar_link_class(['index.php'], $nav_current_script); ?>">
                    <span class="nav-sidebar-icon"><i class="fa-solid fa-house"></i></span>
                    <span class="nav-sidebar-label">Accueil</span>
                </a>
                <a href="<?php echo public_url('/nouveautes.php'); ?>"
                    class="nav-sidebar-link<?php echo nav_sidebar_link_class(['nouveautes.php'], $nav_current_script); ?>">
                    <span class="nav-sidebar-icon"><i class="fa-solid fa-sparkles"></i></span>
                    <span class="nav-sidebar-label">Nouveautés</span>
                    <span class="nav-sidebar-pill nav-sidebar-pill--new">New</span>
                </a>
                <a href="<?php echo public_url('/promo.php'); ?>"
                    class="nav-sidebar-link<?php echo nav_sidebar_link_class(['promo.php'], $nav_current_script); ?>">
                    <span class="nav-sidebar-icon"><i class="fa-solid fa-tag"></i></span>
                    <span class="nav-sidebar-label">Promo</span>
                    <span class="nav-sidebar-pill nav-sidebar-pill--hot">Hot</span>
                </a>
                <a href="<?php echo public_url('/produits.php'); ?>"
                    class="nav-sidebar-link<?php echo nav_sidebar_link_class(['produits.php'], $nav_current_script); ?>">
                    <span class="nav-sidebar-icon"><i class="fa-solid fa-table-cells-large"></i></span>
                    <span class="nav-sidebar-label">Tous les produits</span>
                </a>
                <a href="<?php echo public_url('/contact.php'); ?>"
                    class="nav-sidebar-link<?php echo nav_sidebar_link_class(['contact.php'], $nav_current_script); ?>">
                    <span class="nav-sidebar-icon"><i class="fa-solid fa-envelope"></i></span>
                    <span class="nav-sidebar-label">Contact</span>
                </a>
            </div>
        </div>

        <?php if (!empty($categories_menu)): ?>
        <div class="nav-sidebar-panel nav-sidebar-panel--categories">
            <p class="nav-sidebar-section-label">Catégories</p>
            <div class="nav-sidebar-group nav-sidebar-group--categories">
                <?php foreach ($categories_menu as $categorie): ?>
                <?php
                    $cat_active = ($nav_current_script === 'categorie.php' && $nav_current_categorie_id === (int) $categorie['id'])
                        ? ' nav-sidebar-link--active'
                        : '';
                ?>
                <a href="<?php echo public_url('/categorie.php?id=' . (int) $categorie['id']); ?>"
                    class="nav-sidebar-link nav-sidebar-link--category<?php echo $cat_active; ?>">
                    <i class="fa-solid fa-chevron-right nav-sidebar-arrow" aria-hidden="true"></i>
                    <span class="nav-sidebar-label"><?php echo htmlspecialchars($categorie['nom']); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        </div>

        <div class="nav-sidebar-footer">
        <div class="nav-sidebar-panel nav-sidebar-panel--account">
            <p class="nav-sidebar-section-label">Mon espace</p>
            <div class="nav-sidebar-group">
                <a href="<?php echo $nav_user_connected ? public_url('/user/mon-compte.php') : public_url('/user/connexion.php'); ?>"
                    class="nav-sidebar-link<?php echo nav_sidebar_link_class(['mon-compte.php', 'connexion.php', 'inscription.php'], $nav_current_script); ?>">
                    <span class="nav-sidebar-icon"><i class="fa-solid fa-user"></i></span>
                    <span class="nav-sidebar-label"><?php echo $nav_user_connected ? 'Mon compte' : 'Connexion'; ?></span>
                </a>
                <a href="<?php echo public_url('/panier.php'); ?>"
                    class="nav-sidebar-link<?php echo nav_sidebar_link_class(['panier.php'], $nav_current_script); ?>">
                    <span class="nav-sidebar-icon"><i class="fa-solid fa-bag-shopping"></i></span>
                    <span class="nav-sidebar-label">Panier</span>
                    <?php if ($panier_count > 0): ?>
                    <span class="nav-sidebar-count"><?php echo $panier_count > 99 ? '99+' : $panier_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo public_url('/contact.php'); ?>#livraison" class="nav-sidebar-link">
                    <span class="nav-sidebar-icon"><i class="fa-solid fa-truck-fast"></i></span>
                    <span class="nav-sidebar-label">Livraison</span>
                </a>
            </div>
        </div>
        </div>
    </nav>
</aside>

<!-- Barre menu : catégories · navigation -->
<section class="section1">
    <div class="section1-left">
        <button type="button" class="toggle-categories-btn revo-departments-btn" id="navMenuToggle" aria-label="Ouvrir le menu des catégories">
            <i class="fa-solid fa-bars"></i>
            <span class="revo-departments-label--long">Toutes les catégories</span>
            <span class="revo-departments-label--short">Menu</span>
        </button>
    </div>

    <nav class="section1-center" aria-label="Navigation principale">
        <a href="<?php echo public_url('/nouveautes.php'); ?>" class="revo-nav-link revo-nav-link--desktop<?php echo $nav_current_script === 'nouveautes.php' ? ' is-active' : ''; ?>">
            Nouveautés
            <span class="nav-badge nav-badge--new">NEW</span>
        </a>
        <a href="<?php echo public_url('/promo.php'); ?>" class="revo-nav-link revo-nav-link--desktop<?php echo $nav_current_script === 'promo.php' ? ' is-active' : ''; ?>">
            Promo
            <span class="nav-badge nav-badge--hot">HOT</span>
        </a>
        <a href="<?php echo public_url('/produits.php'); ?>" class="revo-nav-link revo-nav-link--desktop<?php echo in_array($nav_current_script, ['produits.php', 'categorie.php', 'produit.php'], true) ? ' is-active' : ''; ?>">
            Produits
        </a>
        <a href="<?php echo public_url('/contact.php'); ?>" class="revo-nav-link<?php echo $nav_current_script === 'contact.php' ? ' is-active' : ''; ?>">Contact</a>
    </nav>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.getElementById('navMenuToggle');
        var closeBtn = document.getElementById('navSidebarClose');
        var sidebar = document.getElementById('navSidebar');
        var overlay = document.getElementById('navSidebarOverlay');

        function openMenu() {
            if (sidebar) sidebar.classList.add('open');
            if (overlay) overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
            var icon = toggle ? toggle.querySelector('i') : null;
            if (icon) { icon.classList.remove('fa-bars'); icon.classList.add('fa-times'); }
        }

        function closeMenu() {
            if (sidebar) sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('show');
            document.body.style.overflow = '';
            var icon = toggle ? toggle.querySelector('i') : null;
            if (icon) { icon.classList.remove('fa-times'); icon.classList.add('fa-bars'); }
        }

        if (toggle) toggle.addEventListener('click', function () {
            if (sidebar && sidebar.classList.contains('open')) closeMenu();
            else openMenu();
        });
        if (closeBtn) closeBtn.addEventListener('click', closeMenu);
        if (overlay) overlay.addEventListener('click', closeMenu);

        if (sidebar) {
            sidebar.querySelectorAll('.nav-sidebar-link').forEach(function (link) {
                link.addEventListener('click', function () {
                    closeMenu();
                });
            });
        }
    });
</script>
<?php include __DIR__ . '/includes/bottom_nav.php'; ?>
