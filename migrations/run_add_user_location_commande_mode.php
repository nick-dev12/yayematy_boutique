<?php
/**
 * Migration : localisation utilisateur + mode livraison commandes.
 *
 * Usage : php migrations/run_add_user_location_commande_mode.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/conn/conn.php';

if (empty($db) || !($db instanceof PDO)) {
    echo "Erreur : connexion BDD indisponible.\n";
    exit(1);
}

function user_loc_mig_column_exists(PDO $db, string $table, string $col): bool {
    $q = $db->prepare("
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c
    ");
    $q->execute(['t' => $table, 'c' => $col]);
    return (int) $q->fetchColumn() > 0;
}

function user_loc_mig_safe_exec(PDO $db, string $sql, string $label): bool {
    try {
        $db->exec($sql);
        echo "  OK : $label\n";
        return true;
    } catch (PDOException $e) {
        echo "  ERREUR ($label) : " . $e->getMessage() . "\n";
        return false;
    }
}

echo "=== Migration localisation user + mode livraison ===\n";

$user_columns = [
    'last_latitude' => "DECIMAL(10,8) NULL DEFAULT NULL COMMENT 'Dernière latitude client' AFTER `statut`",
    'last_longitude' => "DECIMAL(11,8) NULL DEFAULT NULL COMMENT 'Dernière longitude client' AFTER `last_latitude`",
    'location_accuracy' => "DECIMAL(8,2) NULL DEFAULT NULL COMMENT 'Précision GPS mètres' AFTER `last_longitude`",
    'location_label' => "VARCHAR(255) NULL DEFAULT NULL COMMENT 'Adresse dérivée GPS' AFTER `location_accuracy`",
    'location_updated_at' => "DATETIME NULL DEFAULT NULL COMMENT 'Horodatage dernière position' AFTER `location_label`",
];

foreach ($user_columns as $col => $definition) {
    if (!user_loc_mig_column_exists($db, 'users', $col)) {
        user_loc_mig_safe_exec($db, "ALTER TABLE `users` ADD COLUMN `$col` $definition", "users.$col");
    } else {
        echo "  users.$col déjà présente.\n";
    }
}

if (!user_loc_mig_column_exists($db, 'commandes', 'mode_livraison')) {
    $after = user_loc_mig_column_exists($db, 'commandes', 'frais_livraison') ? 'frais_livraison' : 'adresse_livraison';
    user_loc_mig_safe_exec(
        $db,
        "ALTER TABLE `commandes` ADD COLUMN `mode_livraison` ENUM('livraison','retrait') NOT NULL DEFAULT 'livraison' COMMENT 'Mode de réception' AFTER `$after`",
        'commandes.mode_livraison'
    );
} else {
    echo "  commandes.mode_livraison déjà présente.\n";
}

echo "=== Terminé ===\n";
