<?php
/**
 * Compresse toutes les images raster déjà présentes dans upload/.
 * Usage : php scripts/optimize_all_uploads.php
 */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI uniquement.\n");
    exit(1);
}

$subdirs = ['produits', 'categories', 'slider', 'section4', 'trending', 'site-brand'];
$root = dirname(__DIR__);
$phpBin = (defined('PHP_BINARY') && PHP_BINARY) ? PHP_BINARY : 'php';

foreach ($subdirs as $subdir) {
    $script = $root . '/scripts/optimize_existing_images.php';
    echo "\n========== {$subdir} ==========\n";
    passthru($phpBin . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($subdir), $code);
    if ($code !== 0) {
        fwrite(STDERR, "Erreur sur {$subdir}\n");
    }
}

$sync = $root . '/scripts/sync_image_paths_database.php';
if (is_file($sync)) {
    echo "\n========== Sync BDD ==========\n";
    passthru($phpBin . ' ' . escapeshellarg($sync), $code);
}

echo "\nCompression globale terminée.\n";
