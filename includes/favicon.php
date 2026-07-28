<?php
/**
 * Favicon / icône d'onglet pour toutes les pages
 * À inclure dans le <head> pour afficher le logo Yaye Maty dans l'onglet du navigateur
 */
require_once __DIR__ . '/site_brand.php';
$brand_favicon = site_brand_logo();
?>
<link rel="icon" type="image/png" href="<?php echo htmlspecialchars($brand_favicon); ?>">
<link rel="shortcut icon" type="image/png" href="<?php echo htmlspecialchars($brand_favicon); ?>">
<link rel="apple-touch-icon" href="<?php echo htmlspecialchars($brand_favicon); ?>">
<?php include __DIR__ . '/google_fonts.php'; ?>
