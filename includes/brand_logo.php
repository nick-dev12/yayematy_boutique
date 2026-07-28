<?php
/**
 * Balise <img> du logo Yaye Maty (réutilisable)
 * Option : $brand_logo_class (string)
 */
if (!function_exists('site_brand_logo')) {
    require_once __DIR__ . '/site_brand.php';
}
$brand_logo_class = isset($brand_logo_class) ? trim((string) $brand_logo_class) : '';
$class_attr = $brand_logo_class !== '' ? ' class="' . htmlspecialchars($brand_logo_class) . '"' : '';
?>
<img src="<?php echo site_brand_logo(); ?>" alt="<?php echo htmlspecialchars(site_brand_logo_alt()); ?>"<?php echo $class_attr; ?>>
