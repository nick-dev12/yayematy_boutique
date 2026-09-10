<?php
require_once __DIR__ . '/includes/site_brand.php';
if (!function_exists('asset_url')) {
    require_once __DIR__ . '/includes/asset_version.php';
}

$social_config = file_exists(__DIR__ . '/config/social.php') ? require __DIR__ . '/config/social.php' : [];
$whatsapp_clean = preg_replace('/[^0-9]/', '', (string) ($social_config['whatsapp'] ?? '221773643529'));
$whatsapp_url = 'https://wa.me/' . $whatsapp_clean;
?>
<?php if (!defined('FOOTER_CSS_LOADED')): define('FOOTER_CSS_LOADED', true); ?>
<link rel="stylesheet" href="<?php echo asset_url('/css/footer.css'); ?>">
<?php endif; ?>
<footer class="footer">
    <div class="footer_container">
        <div class="footer_layout">
            <section class="footer_brand" aria-label="<?php echo htmlspecialchars(site_brand_name()); ?>">
                <a href="<?php echo public_url('/index.php'); ?>" class="footer_logo">
                    <img src="<?php echo site_brand_logo(); ?>" alt="<?php echo htmlspecialchars(site_brand_logo_alt()); ?>" class="footer_logo_img"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                    <span class="footer_logo_fallback"><i class="fas fa-store" aria-hidden="true"></i> <?php echo htmlspecialchars(site_brand_name()); ?></span>
                </a>
                <div class="footer_brand_row">
                    <p class="footer_tagline">
                        <strong><?php echo htmlspecialchars(site_brand_name()); ?></strong>
                        <?php echo htmlspecialchars(site_brand_tagline()); ?>
                    </p>
                    <div class="footer_social">
                    <?php if (!empty($social_config['facebook'])): ?>
                    <a href="<?php echo htmlspecialchars($social_config['facebook']); ?>" target="_blank" rel="noopener noreferrer" class="footer_social_link footer_social_link--facebook" aria-label="Facebook"><i class="fab fa-facebook-f" aria-hidden="true"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($social_config['tiktok'])): ?>
                    <a href="<?php echo htmlspecialchars($social_config['tiktok']); ?>" target="_blank" rel="noopener noreferrer" class="footer_social_link footer_social_link--tiktok" aria-label="TikTok"><i class="fab fa-tiktok" aria-hidden="true"></i></a>
                    <?php endif; ?>
                    <a href="<?php echo htmlspecialchars($whatsapp_url); ?>" target="_blank" rel="noopener noreferrer" class="footer_social_link footer_social_link--whatsapp" aria-label="WhatsApp"><i class="fab fa-whatsapp" aria-hidden="true"></i></a>
                    </div>
                </div>
            </section>

            <div class="footer_nav">
                <nav class="footer_col" aria-label="Boutique">
                    <h3 class="footer_item_titl">Boutique</h3>
                    <ul class="footer_list">
                        <li class="footer_list_item"><a href="<?php echo public_url('/index.php'); ?>">Accueil</a></li>
                        <li class="footer_list_item"><a href="<?php echo public_url('/produits.php'); ?>">Produits</a></li>
                        <li class="footer_list_item"><a href="<?php echo public_url('/panier.php'); ?>">Panier</a></li>
                        <li class="footer_list_item"><a href="<?php echo public_url('/contact.php'); ?>">Contact</a></li>
                    </ul>
                </nav>

                <nav class="footer_col" aria-label="Mon compte">
                    <h3 class="footer_item_titl">Mon compte</h3>
                    <ul class="footer_list">
                        <?php if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])): ?>
                        <li class="footer_list_item"><a href="<?php echo public_url('/user/mon-compte.php'); ?>">Mon compte</a></li>
                        <li class="footer_list_item"><a href="<?php echo public_url('/user/mes-commandes.php'); ?>">Mes commandes</a></li>
                        <li class="footer_list_item"><a href="<?php echo public_url('/user/deconnexion.php'); ?>">Déconnexion</a></li>
                        <?php else: ?>
                        <li class="footer_list_item"><a href="<?php echo public_url('/user/connexion.php'); ?>">Connexion</a></li>
                        <li class="footer_list_item"><a href="<?php echo public_url('/user/inscription.php'); ?>">Inscription</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>

                <nav class="footer_col footer_col--legal" aria-label="Informations légales">
                    <h3 class="footer_item_titl">Informations</h3>
                    <ul class="footer_list">
                        <li class="footer_list_item"><a href="<?php echo public_url('/politique-confidentialite.php'); ?>">Politique de confidentialité</a></li>
                        <li class="footer_list_item"><a href="<?php echo public_url('/conditions-utilisation.php'); ?>">Conditions d'utilisation</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <div class="footer_bottom">
        <div class="footer_bottom_container">
            <p class="footer_copy">
                &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(site_brand_name()); ?> — Tous droits réservés
            </p>
        </div>
    </div>
</footer>
<?php include __DIR__ . '/includes/social_floating.php'; ?>
<?php
require_once __DIR__ . '/includes/product_share_assets.php';
if (product_share_should_load_assets()) {
    product_share_render_assets();
}
require_once __DIR__ . '/includes/guest_checkout_assets.php';
if (guest_checkout_should_load_assets()) {
    guest_checkout_render_assets();
}
?>
<?php
require_once __DIR__ . '/includes/gtranslate.php';
gtranslate_render_core();
?>
<?php include __DIR__ . '/includes/firebase_notifications_scripts.php'; ?>
