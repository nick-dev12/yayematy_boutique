<?php
/**
 * Script pour générer les icônes PWA (192x192 et 512x512) depuis le logo du site
 * À exécuter si vous modifiez le logo : php generate_pwa_icons.php
 */

$sources = [
    __DIR__ . '/image/yaye_maty_logo.jpeg',
    __DIR__ . '/image/yaye_maty_logo.png',
    __DIR__ . '/logo_yaye.jpeg',
];
$iconsDir = __DIR__ . '/icons';

$source = null;
foreach ($sources as $candidate) {
    if (is_file($candidate)) {
        $source = $candidate;
        break;
    }
}

if ($source === null) {
    die("Erreur : logo source introuvable.\n");
}

if (!extension_loaded('gd')) {
    die("Erreur : l'extension GD de PHP est requise.\n");
}

$image = null;
$ext = strtolower(pathinfo($source, PATHINFO_EXTENSION));
if (in_array($ext, ['jpg', 'jpeg'], true)) {
    $image = @imagecreatefromjpeg($source);
} elseif ($ext === 'png') {
    $image = @imagecreatefrompng($source);
}

if (!$image) {
    die("Erreur : impossible de charger l'image source ({$source}).\n");
}

$sizes = [192, 512];

foreach ($sizes as $size) {
    $dest = $iconsDir . "/icon-{$size}.png";
    $resized = imagecreatetruecolor($size, $size);

    if (!$resized) {
        imagedestroy($image);
        die("Erreur : impossible de créer l'image {$size}x{$size}.\n");
    }

    $white = imagecolorallocate($resized, 255, 255, 255);
    imagefill($resized, 0, 0, $white);

    $srcWidth = imagesx($image);
    $srcHeight = imagesy($image);
    $scale = min($size / $srcWidth, $size / $srcHeight);
    $destWidth = (int) round($srcWidth * $scale);
    $destHeight = (int) round($srcHeight * $scale);
    $offsetX = (int) round(($size - $destWidth) / 2);
    $offsetY = (int) round(($size - $destHeight) / 2);

    imagecopyresampled($resized, $image, $offsetX, $offsetY, 0, 0, $destWidth, $destHeight, $srcWidth, $srcHeight);

    if (!imagepng($resized, $dest, 9)) {
        imagedestroy($image);
        imagedestroy($resized);
        die("Erreur : impossible d'enregistrer {$dest}.\n");
    }

    imagedestroy($resized);
    echo "Icône créée : icon-{$size}.png\n";
}

imagedestroy($image);
echo "Terminé. Les icônes PWA ont été générées depuis {$source}.\n";
