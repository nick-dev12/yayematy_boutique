<?php
/**
 * Barre de navigation inférieure — espace client (mobile & tablette)
 * Programmation procédurale uniquement
 */

if (!function_exists('get_asset_version')) {
    require_once __DIR__ . '/../../includes/asset_version.php';
}

if (!isset($panier_count)) {
    $panier_count = 0;
    if (isset($_SESSION['user_id'])) {
        $model_path = __DIR__ . '/../../models/model_panier.php';
        if (file_exists($model_path)) {
            require_once $model_path;
            $panier_count = count_panier_items($_SESSION['user_id']);
        }
    }
}

$current_page = basename($_SERVER['PHP_SELF'] ?? '');

if (!isset($user_bottom_active)) {
    if ($current_page === 'mon-compte.php' || $current_page === 'produits-visites.php') {
        $user_bottom_active = 'dashboard';
    } elseif (in_array($current_page, [
        'mes-commandes.php',
        'commandes-annulees.php',
        'commande-categorie.php',
        'commande-personnalisee-details.php',
    ], true)) {
        $user_bottom_active = 'commandes';
    } elseif ($current_page === 'produits-livres.php') {
        $user_bottom_active = 'livres';
    } elseif (in_array($current_page, ['profil.php', 'supprimer-compte.php'], true)) {
        $user_bottom_active = 'menu';
    } else {
        $user_bottom_active = 'dashboard';
    }
}

$panier_url = '/panier.php';
?>
<link rel="stylesheet" href="/css/bottom-nav.css<?php echo asset_version_query(); ?>">
<nav class="bottom-nav bottom-nav--floating bottom-nav--has-center bottom-nav--user" id="userBottomNav" aria-label="Navigation espace client">
    <a href="mon-compte.php"
        class="bottom-nav-item bottom-nav-item--dashboard<?php echo $user_bottom_active === 'dashboard' ? ' is-active' : ''; ?>">
        <span class="bottom-nav-icon"><i class="fa-solid fa-house" aria-hidden="true"></i></span>
        <span class="bottom-nav-label">Accueil</span>
    </a>
    <a href="mes-commandes.php"
        class="bottom-nav-item bottom-nav-item--mes-commandes<?php echo $user_bottom_active === 'commandes' ? ' is-active' : ''; ?>">
        <span class="bottom-nav-icon"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i></span>
        <span class="bottom-nav-label">Commandes</span>
    </a>
    <a href="<?php echo htmlspecialchars($panier_url); ?>"
        class="bottom-nav-item bottom-nav-item--panier bottom-nav-item--center">
        <span class="bottom-nav-icon">
            <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
            <?php if ($panier_count > 0): ?>
                <span class="bottom-nav-badge"><?php echo $panier_count > 9 ? '9+' : (int) $panier_count; ?></span>
            <?php endif; ?>
        </span>
        <span class="bottom-nav-label">Panier</span>
    </a>
    <a href="produits-livres.php"
        class="bottom-nav-item bottom-nav-item--livres<?php echo $user_bottom_active === 'livres' ? ' is-active' : ''; ?>">
        <span class="bottom-nav-icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
        <span class="bottom-nav-label">Livrés</span>
    </a>
    <button type="button" class="bottom-nav-item bottom-nav-item--menu<?php echo $user_bottom_active === 'menu' ? ' is-active' : ''; ?>"
        id="userBottomNavMenuBtn" aria-label="Ouvrir le menu du compte">
        <span class="bottom-nav-icon"><i class="fa-solid fa-table-cells-large" aria-hidden="true"></i></span>
        <span class="bottom-nav-label">Menu</span>
    </button>
</nav>
<script>
    (function () {
        document.documentElement.classList.add('has-bottom-nav', 'has-user-bottom-nav');

        var menuBtn = document.getElementById('userBottomNavMenuBtn');
        if (!menuBtn) {
            return;
        }

        var userSidebar = document.getElementById('userSidebar');
        var userOverlay = document.getElementById('sidebarOverlay');

        function setMenuOpen(isOpen) {
            menuBtn.classList.toggle('is-open', isOpen);
        }

        menuBtn.addEventListener('click', function () {
            if (typeof window.toggleSidebar === 'function') {
                window.toggleSidebar();
            } else if (userSidebar && userOverlay) {
                userSidebar.classList.toggle('show');
                userOverlay.classList.toggle('show');
                document.body.style.overflow = userSidebar.classList.contains('show') ? 'hidden' : '';
            }
            setMenuOpen(userSidebar && userSidebar.classList.contains('show'));
        });

        if (userOverlay) {
            userOverlay.addEventListener('click', function () {
                setMenuOpen(false);
            });
        }

        window.addEventListener('resize', function () {
            if (window.innerWidth > 992) {
                setMenuOpen(false);
            }
        });
    })();
</script>
