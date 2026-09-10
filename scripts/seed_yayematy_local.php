<?php
/**
 * Réinitialise le catalogue Yaye Maty (catégories, produits, slider, images locales).
 * Usage : php scripts/seed_yayematy_local.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI uniquement.\n");
    exit(1);
}

$root = dirname(__DIR__);
$imageDir = $root . '/image';
$uploadDir = $root . '/upload';

require_once $root . '/conn/conn.php';
if (!isset($db) || !($db instanceof PDO)) {
    fwrite(STDERR, "Connexion BDD indisponible.\n");
    exit(1);
}

function seed_copy_image(string $source, string $dest): bool
{
    $destDir = dirname($dest);
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    if (!is_file($source)) {
        return false;
    }
    return copy($source, $dest);
}

function seed_unique_filename(string $prefix, string $ext): string
{
    return $prefix . '_' . bin2hex(random_bytes(6)) . '.' . ltrim($ext, '.');
}

function seed_install_image(string $sourceFile, string $uploadSubdir, string $prefix): ?string
{
    global $root;
    if (!is_file($sourceFile)) {
        return null;
    }
    require_once $root . '/includes/image_optimizer.php';
    $destDir = $root . '/upload/' . trim($uploadSubdir, '/');
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    $stem = pathinfo($sourceFile, PATHINFO_FILENAME);
    $result = image_optimizer_process_tmp($sourceFile, $destDir, trim($uploadSubdir, '/'), $prefix . '_', $stem);
    if (!empty($result['success']) && !empty($result['relative_path'])) {
        return (string) $result['relative_path'];
    }
    $ext = strtolower(pathinfo($sourceFile, PATHINFO_EXTENSION) ?: 'jpg');
    $filename = seed_unique_filename($prefix, $ext);
    $dest = $root . '/upload/' . trim($uploadSubdir, '/') . '/' . $filename;
    if (!seed_copy_image($sourceFile, $dest)) {
        return null;
    }
    return trim($uploadSubdir, '/') . '/' . $filename;
}

echo "=== Seed Yaye Maty — catalogue + images ===\n";
echo 'Base : ' . $db->query('SELECT DATABASE()')->fetchColumn() . "\n\n";

$productImages = array_values(array_filter([
    $imageDir . '/produit1.jpg',
    $imageDir . '/produit2.jpg',
    $imageDir . '/produit4.png',
    $imageDir . '/produit-default.png',
    $imageDir . '/produit.png',
    $imageDir . '/Liste-des-legumes-verts.jpg',
    $imageDir . '/Liste-des-legumes-verts-removebg-preview.png',
    $imageDir . '/market.png',
    $imageDir . '/samba-market.png',
    $imageDir . '/samba-market1.png',
], 'is_file'));

if (empty($productImages)) {
    fwrite(STDERR, "Aucune image source dans /image.\n");
    exit(1);
}

$categoriesDef = [
    ['nom' => 'Les Fruits', 'description' => 'Fruits frais et de saison', 'image' => $imageDir . '/produit1.jpg'],
    ['nom' => 'Les Légumes', 'description' => 'Légumes frais du marché', 'image' => $imageDir . '/Liste-des-legumes-verts.jpg'],
    ['nom' => 'Les Noix', 'description' => 'Noix et graines naturelles', 'image' => $imageDir . '/produit2.jpg'],
    ['nom' => 'Les Huiles', 'description' => 'Huiles naturelles et vierges', 'image' => $imageDir . '/produit4.png'],
    ['nom' => 'Les Céréales', 'description' => 'Riz, mil, maïs et céréales locales', 'image' => $imageDir . '/produit-default.png'],
    ['nom' => 'Les Feuilles', 'description' => 'Feuilles aromatiques et médicinales', 'image' => $imageDir . '/Liste-des-legumes-verts-removebg-preview.png'],
    ['nom' => 'Épicerie', 'description' => 'Produits essentiels du quotidien', 'image' => $imageDir . '/market.png'],
    ['nom' => 'Promotions', 'description' => 'Offres spéciales Yaye Maty', 'image' => $imageDir . '/samba-market.png'],
];

$productsDef = [
    'Les Fruits' => [
        ['Mangues', 'Mangues fraîches et sucrées', 3500, 80, '2kg'],
        ['Bananes', 'Bananes mûres et naturelles', 2500, 100, '2kg'],
        ['Ananas', 'Ananas frais et juteux', 4500, 60, '2kg', 4000],
        ['Papayes', 'Papayes mûres', 3000, 70, '2kg'],
        ['Avocats', 'Avocats Hass', 5000, 45, '1kg'],
        ['Oranges', 'Oranges fraîches', 2000, 90, '2kg'],
    ],
    'Les Légumes' => [
        ['Tomates', 'Tomates fraîches du marché', 1500, 120, '1kg'],
        ['Oignons', 'Oignons locaux', 1200, 150, '1kg'],
        ['Pommes de terre', 'Pommes de terre de qualité', 1800, 100, '2kg'],
        ['Carottes', 'Carottes croquantes', 1400, 80, '1kg'],
        ['Aubergines', 'Aubergines fraîches', 1600, 65, '1kg'],
        ['Poivrons', 'Poivrons colorés', 2000, 55, '1kg'],
    ],
    'Les Noix' => [
        ['Noix de Cajou', 'Noix de cajou naturelles', 8500, 45, '500g'],
        ['Amandes', 'Amandes non salées', 12000, 35, '500g', 10000],
        ['Arachides', 'Arachides grillées', 5000, 70, '1kg'],
        ['Noix de Coco', 'Noix de coco fraîche', 6000, 60, '1kg'],
        ['Graines de sésame', 'Graines de sésame', 6000, 65, '500g'],
    ],
    'Les Huiles' => [
        ['Huile de Palme', 'Huile de palme rouge naturelle', 5000, 30, '1L'],
        ['Huile de Coco', 'Huile de coco vierge', 8000, 25, '500ml', 7000],
        ['Huile d\'Arachide', 'Huile d\'arachide raffinée', 4500, 35, '1L'],
        ['Huile de Sésame', 'Huile de sésame vierge', 10000, 20, '500ml'],
    ],
    'Les Céréales' => [
        ['Riz blanc', 'Riz blanc de qualité', 3000, 100, '5kg'],
        ['Mil', 'Mil naturel', 2500, 80, '2kg'],
        ['Maïs', 'Maïs séché', 2000, 90, '2kg'],
        ['Avoine', 'Flocons d\'avoine', 4000, 55, '1kg', 3500],
    ],
    'Les Feuilles' => [
        ['Feuilles de Moringa', 'Moringa séché, riche en nutriments', 4000, 50, '250g'],
        ['Basilic frais', 'Basilic aromatique', 2500, 65, '100g'],
        ['Menthe verte', 'Menthe fraîche', 2000, 70, '100g'],
        ['Thym séché', 'Thym de qualité', 3000, 45, '250g', 2500],
    ],
    'Épicerie' => [
        ['Sucre local', 'Sucre cristallisé', 1500, 200, '1kg'],
        ['Sel iodé', 'Sel de cuisine', 800, 180, '1kg'],
        ['Pâtes alimentaires', 'Pâtes de qualité', 1200, 90, '500g'],
        ['Farine de blé', 'Farine tamisée', 1800, 75, '1kg'],
    ],
    'Promotions' => [
        ['Panier découverte', 'Assortiment fruits & légumes', 9900, 40, '1 panier', 7500],
        ['Pack huiles', 'Huile palme + arachide', 8500, 25, '1 pack', 7200],
        ['Mix céréales', 'Riz + mil + maïs', 6500, 35, '3kg', 5500],
    ],
];

$sliderDef = [
    [
        'titre' => 'YAYEMATY MARKET',
        'paragraphe' => 'Votre marché local — produits naturels et essentiels du quotidien',
        'source' => $imageDir . '/samba-market.png',
        'bouton_texte' => 'Découvrir',
        'bouton_lien' => '/produits.php',
    ],
    [
        'titre' => 'Fruits & Légumes frais',
        'paragraphe' => 'Directement du marché, livraison rapide sur Dakar',
        'source' => $imageDir . '/Liste-des-legumes-verts.jpg',
        'bouton_texte' => 'Voir les produits',
        'bouton_lien' => '/produits.php',
    ],
    [
        'titre' => 'Promotions du moment',
        'paragraphe' => 'Profitez de nos offres spéciales chaque semaine',
        'source' => $imageDir . '/samba-market1.png',
        'bouton_texte' => 'Voir les promos',
        'bouton_lien' => '/promo.php',
    ],
];

echo "[1/5] Nettoyage du catalogue…\n";
$db->exec('SET FOREIGN_KEY_CHECKS=0');
$tables = [
    'commande_produits',
    'panier',
    'produits_visites',
    'stock_mouvements',
    'produits_variantes',
    'produits',
    'categories',
    'slider',
];
foreach ($tables as $table) {
    $db->exec('DELETE FROM `' . str_replace('`', '``', $table) . '`');
    echo "  - vidé : $table\n";
}
$db->exec('ALTER TABLE categories AUTO_INCREMENT = 1');
$db->exec('ALTER TABLE produits AUTO_INCREMENT = 1');
$db->exec('ALTER TABLE slider AUTO_INCREMENT = 1');

echo "\n[2/5] Préparation dossier upload/…\n";
foreach (['produits', 'categories', 'slider', 'section4'] as $subdir) {
    $path = $uploadDir . '/' . $subdir;
    if (is_dir($path)) {
        foreach (glob($path . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    } else {
        mkdir($path, 0755, true);
    }
}

$section4Image = seed_install_image($imageDir . '/market.png', 'section4', 'section4');
if ($section4Image === null) {
    $section4Image = 'market.png';
    seed_copy_image($imageDir . '/market.png', $uploadDir . '/section4/market.png');
}

echo "\n[3/5] Catégories…\n";
$catStmt = $db->prepare('INSERT INTO categories (nom, description, image, date_creation) VALUES (:nom, :description, :image, NOW())');
$categoryIds = [];
foreach ($categoriesDef as $i => $cat) {
    $imgPath = seed_install_image($cat['image'], 'categories', 'categorie');
    $catStmt->execute([
        'nom' => $cat['nom'],
        'description' => $cat['description'],
        'image' => $imgPath,
    ]);
    $categoryIds[$cat['nom']] = (int) $db->lastInsertId();
    echo "  + {$cat['nom']}\n";
}

echo "\n[4/5] Produits…\n";
$prodStmt = $db->prepare('
    INSERT INTO produits (nom, description, prix, prix_promotion, stock, categorie_id, image_principale, poids, unite, statut, date_creation)
    VALUES (:nom, :description, :prix, :promo, :stock, :cat, :image, :poids, :unite, \'actif\', NOW())
');
$productCount = 0;
$imgIndex = 0;
foreach ($productsDef as $catName => $products) {
    if (!isset($categoryIds[$catName])) {
        continue;
    }
    foreach ($products as $p) {
        $source = $productImages[$imgIndex % count($productImages)];
        $imgIndex++;
        $imgPath = seed_install_image($source, 'produits', 'produit');
        $prodStmt->execute([
            'nom' => $p[0],
            'description' => $p[1],
            'prix' => $p[2],
            'promo' => $p[5] ?? null,
            'stock' => $p[3],
            'cat' => $categoryIds[$catName],
            'image' => $imgPath,
            'poids' => $p[4],
            'unite' => 'kg',
        ]);
        $productCount++;
    }
}
echo "  + $productCount produits insérés\n";

echo "\n[5/5] Slider & bannières…\n";
$slideStmt = $db->prepare('
    INSERT INTO slider (titre, paragraphe, image, bouton_texte, bouton_lien, ordre, statut, date_creation)
    VALUES (:titre, :paragraphe, :image, :bouton, :lien, :ordre, \'actif\', NOW())
');
foreach ($sliderDef as $i => $slide) {
    $filename = seed_unique_filename('slider', pathinfo($slide['source'], PATHINFO_EXTENSION) ?: 'jpg');
    seed_copy_image($slide['source'], $uploadDir . '/slider/' . $filename);
    $slideStmt->execute([
        'titre' => $slide['titre'],
        'paragraphe' => $slide['paragraphe'],
        'image' => $filename,
        'bouton' => $slide['bouton_texte'],
        'lien' => $slide['bouton_lien'],
        'ordre' => $i + 1,
    ]);
    echo "  + slide : {$slide['titre']}\n";
}

$db->exec('DELETE FROM section4_config');
$db->prepare('
    INSERT INTO section4_config (id, titre, texte, image_fond, statut, date_modification)
    VALUES (1, :titre, :texte, :image, \'actif\', NOW())
')->execute([
    'titre' => 'Bienvenue chez Yaye Maty',
    'texte' => 'Produits naturels et essentiels — votre marché local au meilleur prix',
    'image' => basename($section4Image ?? 'market.png'),
]);

$db->exec('UPDATE trending_config SET
    label = \'Promo\',
    titre = \'Offres du marché\',
    bouton_texte = \'Voir les promos\',
    bouton_lien = \'/promo.php\',
    image = \'samba-market.png\'
    WHERE id = 1');

try {
    $db->exec('CREATE TABLE IF NOT EXISTS `site_brand_config` (
      `id` int NOT NULL DEFAULT 1,
      `logo_path` varchar(255) DEFAULT NULL,
      `logo_alt` varchar(255) DEFAULT NULL,
      `date_modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $db->exec('INSERT INTO site_brand_config (id, logo_path, logo_alt, date_modification)
        VALUES (1, \'/image/yaye_maty_logo.png\', \'YAYEMATY MARKET — Votre marché local\', NOW())
        ON DUPLICATE KEY UPDATE
            logo_path = VALUES(logo_path),
            logo_alt = VALUES(logo_alt),
            date_modification = NOW()');
} catch (PDOException $e) {
    echo "  ! site_brand_config : " . $e->getMessage() . "\n";
}

seed_copy_image($imageDir . '/samba-market.png', $uploadDir . '/samba-market.png');

$db->exec('SET FOREIGN_KEY_CHECKS=1');

echo "\n=== Terminé ===\n";
echo "Catégories : " . count($categoryIds) . "\n";
echo "Produits   : $productCount\n";
echo "Slides     : " . count($sliderDef) . "\n";
echo "Images     : dossier upload/ prêt\n";
echo "\nOuvrez : http://localhost/yayematy_boutique/\n";
