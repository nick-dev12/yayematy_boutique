<?php
/**
 * Diagnostic encodage BDD — php scripts/check_db_charset_cli.php
 */
require __DIR__ . '/../conn/conn.php';
require __DIR__ . '/../includes/db_helpers.php';

if (!$db instanceof PDO) {
    echo "NO_DB\n";
    exit(1);
}

$row = $db->query("SHOW VARIABLES LIKE 'character_set_connection'")->fetch(PDO::FETCH_ASSOC);
echo 'character_set_connection=' . ($row['Value'] ?? '?') . PHP_EOL;

$row = $db->query("SHOW VARIABLES LIKE 'collation_connection'")->fetch(PDO::FETCH_ASSOC);
echo 'collation_connection=' . ($row['Value'] ?? '?') . PHP_EOL;

$sample = $db->query("SELECT id, nom FROM produits ORDER BY id ASC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
echo "=== sample produits ===\n";
foreach ($sample as $p) {
    echo ($p['id'] ?? '?') . ' | ' . ($p['nom'] ?? '') . PHP_EOL;
}

$table = $db->query("SHOW TABLE STATUS LIKE 'produits'")->fetch(PDO::FETCH_ASSOC);
echo 'produits_collation=' . ($table['Collation'] ?? '?') . PHP_EOL;
