<?php
/**
 * Ajoute des produits de démonstration (sans upload HTTP).
 * Usage : php scripts/seed_sample_produits_cli.php
 */
require __DIR__ . '/../models/model_produits.php';
require __DIR__ . '/../models/model_categories.php';

if (!app_db() || !categories_table_exists()) {
    fwrite(STDERR, "BDD / categories indisponibles.\n");
    exit(1);
}

$uploadDir = dirname(__DIR__) . '/upload/produits';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$source = dirname(__DIR__) . '/image/yaye_maty_logo.jpeg';
if (!is_file($source)) {
    $source = dirname(__DIR__) . '/image/yaye_maty_logo.png';
}
if (!is_file($source)) {
    fwrite(STDERR, "Image source introuvable.\n");
    exit(1);
}

$imageName = 'seed_demo_' . bin2hex(random_bytes(4)) . '.jpeg';
$dest = $uploadDir . '/' . $imageName;
if (!copy($source, $dest)) {
    fwrite(STDERR, "Copie image impossible.\n");
    exit(1);
}
$imagePath = 'produits/' . $imageName;

$categorie_id = 0;
$cat = get_categorie_by_nom('Alimentation');
if (!$cat) {
    $cat = get_categorie_by_nom('Les Fruits');
}
if ($cat) {
    $categorie_id = (int) $cat['id'];
}
if ($categorie_id <= 0) {
    $parents = get_parent_categories();
    $categorie_id = (int) ($parents[0]['id'] ?? 0);
}
if ($categorie_id <= 0) {
    fwrite(STDERR, "Aucune catégorie disponible.\n");
    exit(1);
}

$produits = [
    [
        'nom' => 'Riz parfumé 1 kg',
        'description' => 'Riz parfumé de qualité, idéal pour le quotidien.',
        'prix' => 1500,
        'stock' => 50,
    ],
    [
        'nom' => 'Huile végétale 1 L',
        'description' => 'Huile végétale pour la cuisine, bouteille 1 litre.',
        'prix' => 2500,
        'stock' => 30,
    ],
    [
        'nom' => 'Jus de bissap 1 L',
        'description' => 'Jus de bissap naturel, rafraîchissant.',
        'prix' => 1200,
        'stock' => 40,
    ],
];

foreach ($produits as $p) {
    $check = app_db()->prepare('SELECT id FROM produits WHERE nom = :nom LIMIT 1');
    $check->execute(['nom' => $p['nom']]);
    if ($check->fetchColumn()) {
        echo "SKIP {$p['nom']}\n";
        continue;
    }
    $id = create_produit([
        'nom' => $p['nom'],
        'description' => $p['description'],
        'prix' => $p['prix'],
        'prix_promotion' => null,
        'stock' => $p['stock'],
        'categorie_id' => $categorie_id,
        'image_principale' => $imagePath,
        'images' => json_encode([$imagePath]),
        'poids' => null,
        'unite' => 'unité',
        'statut' => 'actif',
    ]);
    if (!$id) {
        fwrite(STDERR, "Échec : {$p['nom']}\n");
        exit(1);
    }
    echo "CREATED {$p['nom']} id={$id}\n";
}
