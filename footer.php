<?php
require_once __DIR__ . '/includes/site_brand.php';
$social_config = file_exists(__DIR__ . '/config/social.php') ? require __DIR__ . '/config/social.php' : [];
$whatsapp_clean = preg_replace('/[^0-9]/', '', (string) ($social_config['whatsapp'] ?? '221773643529'));
$whatsapp_url = 'https://wa.me/' . $whatsapp_clean;
$telephone_contact = '+221 77 364 35 29';
$adresse_maps_url = 'https://www.google.com/maps/place/Boulangerie+,+P%C3%A2tisserie+,+Fast+Food+,+Glacier+,+Dibiterie+,+Cr%C3%AAperie.+(+Yaye+Maty+)/@14.7744383,-17.3330073,13z/data=!4m10!1m2!2m1!1syaye+maty!3m6!1s0xec19f2232220d67:0x3424ffc829f1cad4!8m2!3d14.7409971!4d-17.2823468!15sCgl5YXllIG1hdHlaCyIJeWF5ZSBtYXR5kgEGYmFrZXJ54AEA!16s%2Fg%2F11p7377czl?authuser=0&entry=ttu&g_ep=EgoyMDI2MDcyMi4wIKXMDSoASAFQAw%3D%3D';
$adresse_contact = 'Yaye Maty — Boulangerie, Pâtisserie, Fast Food, Glacier, Dibiterie, Crêperie';
?>
<footer class="footer">
    <div class="container footer_container">
        <div class="footer_item">
            <a href="/index.php" class="footer_logo">
                <img src="<?php echo site_brand_logo(); ?>" alt="<?php echo htmlspecialchars(site_brand_logo_alt()); ?>" class="footer_logo_img"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                <span class="footer_logo_fallback"><i class="fas fa-store"></i> <?php echo htmlspecialchars(site_brand_name()); ?></span>
            </a>
            <div class="footer_p">
                <?php echo htmlspecialchars(site_brand_name()); ?> — <?php echo htmlspecialchars(site_brand_tagline()); ?>
            </div>
            <div class="footer_social">
                <?php if (!empty($social_config['facebook'])): ?>
                <a href="<?php echo htmlspecialchars($social_config['facebook']); ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <?php endif; ?>
                <?php if (!empty($social_config['tiktok'])): ?>
                <a href="<?php echo htmlspecialchars($social_config['tiktok']); ?>" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                <?php endif; ?>
                <a href="<?php echo htmlspecialchars($whatsapp_url); ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>
        <div class="footer_item">
            <h3 class="footer_item_titl">Contact</h3>
            <ul class="footer_list">
                <li class="li footer_list_item">
                    <i class="fas fa-phone"></i>
                    <a href="tel:+221773643529"><?php echo htmlspecialchars($telephone_contact); ?></a>
                </li>
                <li class="li footer_list_item">
                    <i class="fas fa-map-marker-alt"></i>
                    <a href="<?php echo htmlspecialchars($adresse_maps_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($adresse_contact); ?></a>
                </li>
            </ul>
        </div>
        <div class="footer_item">
            <h3 class="footer_item_titl">Liens rapides</h3>
            <ul class="footer_list">
                <?php if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])): ?>
                    <li class="li footer_list_item">
                        <a href="/user/mon-compte.php">Mon compte</a>
                    </li>
                    <li class="li footer_list_item">
                        <a href="/user/deconnexion.php">Déconnexion</a>
                    </li>
                <?php else: ?>
                    <li class="li footer_list_item">
                        <a href="/user/connexion.php">Connexion</a>
                    </li>
                    <li class="li footer_list_item">
                        <a href="/user/inscription.php">Inscription</a>
                    </li>
                <?php endif; ?>
                <li class="li footer_list_item">
                    <a href="/panier.php">Panier</a>
                </li>
                <li class="li footer_list_item">
                    <a href="/produits.php">Produits</a>
                </li>
            </ul>
        </div>
        <div class="footer_item">
            <h3 class="footer_item_titl">Informations légales</h3>
            <ul class="footer_list">
                <li class="li footer_list_item">
                    <a href="/politique-confidentialite.php">Politique de confidentialité</a>
                </li>
                <li class="li footer_list_item">
                    <a href="/conditions-utilisation.php">Conditions d'utilisation</a>
                </li>
                <li class="li footer_list_item">
                    <a href="/politique-suppression-compte.php">Suppression de compte</a>
                </li>
            </ul>
        </div>
    </div>
    <div class="footer_bottom">
        <div class="container footer_bottom_container">
            <p class="footer_copy">
                2026 By <?php echo htmlspecialchars(site_brand_name()); ?> Team | All rights reserved
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
<?php include __DIR__ . '/includes/firebase_notifications_scripts.php'; ?>