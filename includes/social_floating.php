<?php
/**
 * Boutons réseaux sociaux en position fixe (bas à droite)
 * WhatsApp, TikTok, Facebook
 */

$social_config = [];
if (file_exists(__DIR__ . '/../config/social.php')) {
    $social_config = require __DIR__ . '/../config/social.php';
}

$whatsapp = $social_config['whatsapp'] ?? '';
$tiktok = $social_config['tiktok'] ?? '';
$facebook = $social_config['facebook'] ?? '';

// WhatsApp : format wa.me/CODE_PAYS_NUMERO (sans + ni espaces)
$whatsapp_url = '';
if (!empty($whatsapp)) {
    $whatsapp_clean = preg_replace('/[^0-9]/', '', $whatsapp);
    $whatsapp_url = 'https://wa.me/' . $whatsapp_clean;
}
?>
<?php if (!function_exists('get_asset_version')) { require_once __DIR__ . '/asset_version.php'; } ?>
<?php if (!defined('SOCIAL_FLOATING_CSS_LOADED')): define('SOCIAL_FLOATING_CSS_LOADED', true); ?>
<link rel="stylesheet" href="<?php echo asset_url('/css/social-floating.css'); ?>">
<?php endif; ?>
<div class="social-floating" id="socialFloating" aria-label="Réseaux sociaux">
    <?php if (!empty($whatsapp_url)): ?>
    <a href="<?php echo htmlspecialchars($whatsapp_url); ?>" target="_blank" rel="noopener noreferrer" class="social-floating-btn social-whatsapp" title="Contactez-nous sur WhatsApp" aria-label="WhatsApp">
        <i class="fab fa-whatsapp" aria-hidden="true"></i>
    </a>
    <?php endif; ?>
    <?php if (!empty($tiktok)): ?>
    <a href="<?php echo htmlspecialchars($tiktok); ?>" target="_blank" rel="noopener noreferrer" class="social-floating-btn social-tiktok" title="Suivez-nous sur TikTok" aria-label="TikTok">
        <i class="fab fa-tiktok" aria-hidden="true"></i>
    </a>
    <?php endif; ?>
    <?php if (!empty($facebook)): ?>
    <a href="<?php echo htmlspecialchars($facebook); ?>" target="_blank" rel="noopener noreferrer" class="social-floating-btn social-facebook" title="Suivez-nous sur Facebook" aria-label="Facebook">
        <i class="fab fa-facebook-f" aria-hidden="true"></i>
    </a>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/floating_back_button.php'; ?>
