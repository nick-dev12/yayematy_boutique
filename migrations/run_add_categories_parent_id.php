<?php
/**
 * Ajoute parent_id sur categories (sous-catégories).
 * CLI : php migrations/run_add_categories_parent_id.php
 */

require_once __DIR__ . '/lib/migration_helpers.php';

$db = mig_connect();

try {
    $stmt = $db->query("SHOW COLUMNS FROM categories LIKE 'parent_id'");
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Colonne parent_id déjà présente.\n";
        mig_cli_exit(0);
    }

    $db->exec("ALTER TABLE categories ADD COLUMN parent_id INT(11) NULL DEFAULT NULL AFTER id");
    $db->exec("ALTER TABLE categories ADD KEY idx_categories_parent_id (parent_id)");
    $db->exec("
        ALTER TABLE categories
        ADD CONSTRAINT fk_categories_parent
        FOREIGN KEY (parent_id) REFERENCES categories (id)
        ON DELETE SET NULL ON UPDATE CASCADE
    ");
    echo "Colonne parent_id ajoutée.\n";
} catch (PDOException $e) {
    echo $e->getMessage() . "\n";
    mig_cli_exit(1);
}
