<?php
/**
 * Ajoute deux catégories principales si absentes.
 * Usage : php scripts/seed_two_categories_cli.php
 */
require __DIR__ . '/../models/model_categories.php';

if (!categories_db() || !categories_table_exists()) {
    fwrite(STDERR, "Table categories indisponible. Lancez : php scripts/setup_categories_cli.php\n");
    exit(1);
}

$items = [
    ['nom' => 'Alimentation', 'description' => 'Produits alimentaires du quotidien'],
    ['nom' => 'Boissons', 'description' => 'Jus, eaux et boissons naturelles'],
];

foreach ($items as $item) {
    $existing = get_categorie_by_nom($item['nom']);
    if ($existing) {
        echo "SKIP {$item['nom']} (id={$existing['id']})\n";
        continue;
    }
    $id = create_categorie($item['nom'], $item['description']);
    if (!$id) {
        fwrite(STDERR, "Échec : {$item['nom']}\n");
        exit(1);
    }
    echo "CREATED {$item['nom']} id={$id}\n";
}
