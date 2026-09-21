<?php
/**
 * Crée les tables categories / produits + colonne parent_id.
 * Usage : php scripts/setup_categories_cli.php
 */
require __DIR__ . '/../conn/conn.php';

if (!($db instanceof PDO)) {
    fwrite(STDERR, "Connexion BDD indisponible.\n");
    exit(1);
}

$sqlFile = __DIR__ . '/../admin/create_tables.sql';
if (!is_file($sqlFile)) {
    fwrite(STDERR, "Fichier create_tables.sql introuvable.\n");
    exit(1);
}

$imported = false;
if (DIRECTORY_SEPARATOR === '\\') {
    $mysqlCandidates = glob('C:/wamp64/bin/mysql/*/bin/mysql.exe') ?: [];
    if ($mysqlCandidates !== []) {
        usort($mysqlCandidates, static function ($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });
        $mysql = $mysqlCandidates[0];
        $dbName = '';
        try {
            $dbName = (string) $db->query('SELECT DATABASE()')->fetchColumn();
        } catch (PDOException $e) {
            $dbName = '';
        }
        if ($dbName !== '') {
            $cmd = '"' . $mysql . '" -u root -P 3306 ' . escapeshellarg($dbName)
                . ' < ' . escapeshellarg(str_replace('/', DIRECTORY_SEPARATOR, $sqlFile));
            exec($cmd, $out, $code);
            $imported = ($code === 0);
        }
    }
}

if (!$imported) {
    $sql = file_get_contents($sqlFile);
    if ($sql === false) {
        fwrite(STDERR, "Lecture SQL impossible.\n");
        exit(1);
    }
    $sql = preg_replace('/--[^\R]*/', '', $sql);
    foreach (preg_split('/;\s*(?:\R|$)/', $sql) as $statement) {
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }
        try {
            $db->exec($statement);
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'already exists') === false && stripos($msg, 'Duplicate') === false) {
                fwrite(STDERR, $msg . "\n");
            }
        }
    }
}

try {
    $stmt = $db->query("SHOW COLUMNS FROM categories LIKE 'parent_id'");
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        $db->exec('ALTER TABLE categories ADD COLUMN parent_id INT(11) NULL DEFAULT NULL AFTER id');
        $db->exec('ALTER TABLE categories ADD KEY idx_categories_parent_id (parent_id)');
        $db->exec('
            ALTER TABLE categories
            ADD CONSTRAINT fk_categories_parent
            FOREIGN KEY (parent_id) REFERENCES categories (id)
            ON DELETE SET NULL ON UPDATE CASCADE
        ');
        echo "Colonne parent_id ajoutée.\n";
    }
} catch (PDOException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

echo "OK categories\n";
