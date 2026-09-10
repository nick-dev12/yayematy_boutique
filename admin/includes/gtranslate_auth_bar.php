<?php
/**
 * Sélecteur de langue — pages admin sans sidebar (login, etc.)
 */
if (!function_exists('asset_url')) {
    require_once __DIR__ . '/asset_version.php';
}
if (!defined('ADMIN_GTRANSLATE_CSS_LOADED')) {
    define('ADMIN_GTRANSLATE_CSS_LOADED', true);
    echo '<link rel="stylesheet" href="' . htmlspecialchars(asset_url('/css/variables.css'), ENT_QUOTES, 'UTF-8') . '">' . "\n";
    echo '<link rel="stylesheet" href="' . htmlspecialchars(asset_url('/css/admin-gtranslate.css'), ENT_QUOTES, 'UTF-8') . '">' . "\n";
}
?>
<div class="admin-login-lang nav-lang-switcher notranslate" title="Langue">
    <?php
    require_once __DIR__ . '/../../includes/gtranslate.php';
    gtranslate_render_widget();
    gtranslate_render_core();
    ?>
</div>
