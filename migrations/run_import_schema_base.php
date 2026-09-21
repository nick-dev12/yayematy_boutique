<?php
/**
 * Importe le schéma de base (CREATE IF NOT EXISTS) sans supprimer de données.
 * CLI : php migrations/run_import_schema_base.php
 */
require_once __DIR__ . '/lib/migration_helpers.php';

$db = mig_connect();
$sqlFile = __DIR__ . '/schema_complet_production_vide.sql';

if (!is_file($sqlFile)) {
    fwrite(STDERR, "Fichier schema_complet_production_vide.sql introuvable.\n");
    mig_cli_exit(1);
}

echo "=== Import schéma de base (sans données) ===\n";
echo 'Base : ' . $db->query('SELECT DATABASE()')->fetchColumn() . "\n\n";

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    fwrite(STDERR, "Lecture SQL impossible.\n");
    mig_cli_exit(1);
}

$dbName = (string) $db->query('SELECT DATABASE()')->fetchColumn();
$importedViaCli = false;

if (DIRECTORY_SEPARATOR === '\\' && $dbName !== '') {
    $mysqlCandidates = array_merge(
        glob('C:/wamp64/bin/mysql/*/bin/mysql.exe') ?: [],
        glob('C:/wamp64/bin/mariadb/*/bin/mysql.exe') ?: []
    );
    if ($mysqlCandidates !== []) {
        usort($mysqlCandidates, static function ($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });
        $mysql = $mysqlCandidates[0];
        $cmd = 'cmd /c ""' . str_replace('/', '\\', $mysql) . '" -u root -P 3306 '
            . escapeshellarg($dbName) . ' < "' . str_replace('/', '\\', $sqlFile) . '""';
        exec($cmd, $cliOut, $cliCode);
        if ($cliCode === 0) {
            $importedViaCli = true;
            echo "Import via client MySQL OK.\n";
        }
    }
}

$ok = 0;
$skip = 0;
$err = 0;

if (!$importedViaCli) {
    $db->exec('SET FOREIGN_KEY_CHECKS=0');
    $db->exec('SET NAMES utf8mb4');
    $sql = preg_replace('/^--.*$/m', '', $sql);
    $statements = preg_split('/;\s*[\r\n]+/', $sql);

    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }
        if (preg_match('/^SET\s+/i', $statement)) {
            try {
                $db->exec($statement);
            } catch (PDOException $e) {
                // ignorer
            }
            continue;
        }
        try {
            $db->exec($statement);
            $ok++;
            if (preg_match('/CREATE TABLE(?: IF NOT EXISTS)?\s+`?([a-z0-9_]+)`?/i', $statement, $m)) {
                echo "  + table {$m[1]}\n";
            }
        } catch (PDOException $e) {
            if (mig_is_duplicate_error($e)) {
                $skip++;
                continue;
            }
            echo '  ! ' . $e->getMessage() . "\n";
            $err++;
        }
    }
    $db->exec('SET FOREIGN_KEY_CHECKS=1');
}

echo "\nTerminé : $ok OK | $skip ignorés | $err erreurs\n";
mig_cli_exit($err > 0 ? 1 : 0);
