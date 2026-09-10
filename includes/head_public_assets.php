<?php
/**
 * Assets communs <head> — une seule inclusion par page.
 */

if (!function_exists('render_public_head_assets')) {
    function render_public_head_assets(array $extra_css = []): void
    {
        if (defined('PUBLIC_HEAD_ASSETS_LOADED')) {
            return;
        }
        define('PUBLIC_HEAD_ASSETS_LOADED', true);

        if (!function_exists('asset_url')) {
            require_once __DIR__ . '/asset_version.php';
        }
        ?>
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="<?php echo asset_url('/css/variables.css'); ?>">
<link rel="stylesheet" href="<?php echo asset_url('/css/nabare.css'); ?>">
<link rel="stylesheet" href="<?php echo asset_url('/css/store-header.css'); ?>">
<?php
        include __DIR__ . '/google_fonts.php';

        foreach ($extra_css as $css_path) {
            $css_path = (string) $css_path;
            if ($css_path === '') {
                continue;
            }
            echo '<link rel="stylesheet" href="' . htmlspecialchars(asset_url($css_path), ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }
    }
}
