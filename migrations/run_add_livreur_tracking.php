<?php
/**
 * Migration : tables suivi GPS livreurs + colonnes commandes.
 *
 * Usage CLI : php migrations/run_add_livreur_tracking.php
 * (également exécutée automatiquement au premier accès au module GPS)
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/conn/conn.php';
require_once __DIR__ . '/lib/install_livreur_tracking.php';

if (empty($db) || !($db instanceof PDO)) {
    echo "Erreur : connexion BDD indisponible.\n";
    exit(1);
}

$ok = livreur_tracking_install_schema($db, true);
exit($ok ? 0 : 1);
