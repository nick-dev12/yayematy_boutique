<?php
/**
 * Installation / migrations BDD depuis le navigateur (hébergement mutualisé).
 * Ajouts uniquement — ne supprime pas les données existantes.
 *
 * URL : /admin/install_migrations.php
 */
require_once __DIR__ . '/includes/admin_auth.php';

if (normalize_admin_role($_SESSION['admin_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo 'Accès réservé aux administrateurs.';
    exit;
}

require_once __DIR__ . '/../migrations/lib/migration_helpers.php';

$action = isset($_POST['action']) ? (string) $_POST['action'] : '';
$output = '';
$migrations_dir = dirname(__DIR__) . '/migrations';

function install_run_embedded(string $scriptPath): string
{
    if (!is_file($scriptPath)) {
        return "Script introuvable : $scriptPath\n";
    }
    if (!defined('MIGRATION_EMBEDDED')) {
        define('MIGRATION_EMBEDDED', true);
    }
    ob_start();
    $code = 0;
    try {
        include $scriptPath;
    } catch (MigrationEmbeddedExit $e) {
        $code = (int) $e->getCode();
    } catch (Throwable $e) {
        echo 'ERREUR : ' . $e->getMessage() . "\n";
        $code = 1;
    }
    $text = (string) ob_get_clean();
    return $text . "\n--- Code : $code ---\n";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'schema_base') {
        $output = install_run_embedded($migrations_dir . '/run_import_schema_base.php');
    } elseif ($action === 'migrations_all') {
        $GLOBALS['argv'] = ['run_all_migrations.php', '--continue'];
        $output = install_run_embedded($migrations_dir . '/run_all_migrations.php');
    } elseif ($action === 'scan') {
        $output = install_run_embedded($migrations_dir . '/scan_schema.php');
    } elseif ($action === 'categories_parent') {
        $output = install_run_embedded($migrations_dir . '/run_add_categories_parent_id.php');
    } elseif ($action === 'produits_cols') {
        $output = install_run_embedded(dirname(__DIR__) . '/admin/run_migration_produits.php');
    }
}

$db = mig_connect();
$dbName = (string) $db->query('SELECT DATABASE()')->fetchColumn();
$tableCount = (int) $db->query('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include __DIR__ . '/../includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation BDD — Admin</title>
    <?php require_once __DIR__ . '/../includes/asset_version.php'; ?>
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-dashboard.css'); ?>">
    <style>
        .install-wrap { max-width: 52rem; margin: 0 auto; padding: 1.5rem; }
        .install-card { background: #fff; border-radius: 0.625rem; padding: 1.25rem; margin-bottom: 1rem; box-shadow: 0 2px 10px rgba(0,0,0,.06); }
        .install-card h2 { font-size: 1.125rem; margin-bottom: 0.5rem; }
        .install-card p { font-size: 1rem; margin-bottom: 0.75rem; color: #444; }
        .install-output { background: #1a1a1a; color: #e8e8e8; padding: 1rem; border-radius: 0.5rem; font-family: monospace; font-size: 0.875rem; white-space: pre-wrap; max-height: 28rem; overflow: auto; }
        .install-warn { border-left: 4px solid #f0b429; padding: 0.75rem; background: #fff9e6; margin-bottom: 1rem; font-size: 0.875rem; }
        .install-ok { border-left: 4px solid #2e7db5; padding: 0.75rem; background: #eef6fc; margin-bottom: 1rem; }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/nav.php'; ?>
<div class="install-wrap">
    <div class="content-header">
        <h1><i class="fas fa-database"></i> Installation base de données</h1>
        <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>

    <div class="install-ok">
        Base : <strong><?php echo htmlspecialchars($dbName); ?></strong> — <?php echo (int) $tableCount; ?> table(s).
    </div>

    <div class="install-warn">
        <strong>Sans suppression de données.</strong> Exécutez les étapes dans l'ordre sur Namecheap.
        Protégez ou supprimez cette page après installation.
    </div>

    <?php if ($output !== ''): ?>
    <div class="install-card">
        <h2>Résultat</h2>
        <div class="install-output"><?php echo htmlspecialchars($output); ?></div>
    </div>
    <?php endif; ?>

    <div class="install-card">
        <h2>1. Schéma de base</h2>
        <p>Tables <code>users</code>, <code>commandes</code>, <code>panier</code>, <code>produits</code>… (<code>CREATE IF NOT EXISTS</code>).</p>
        <form method="post"><input type="hidden" name="action" value="schema_base">
            <button type="submit" class="btn-primary">Importer le schéma de base</button></form>
    </div>

    <div class="install-card">
        <h2>2. Toutes les migrations</h2>
        <p>Colonnes et modules (commandes, devis, FCM, livreurs, RH…). Peut prendre 1–2 minutes.</p>
        <form method="post"><input type="hidden" name="action" value="migrations_all">
            <button type="submit" class="btn-primary">Lancer toutes les migrations</button></form>
    </div>

    <div class="install-card">
        <h2>3. Vérification</h2>
        <form method="post"><input type="hidden" name="action" value="scan">
            <button type="submit" class="btn-primary">Scanner le schéma</button></form>
    </div>

    <div class="install-card">
        <h2>Optionnel</h2>
        <form method="post" style="display:inline"><input type="hidden" name="action" value="categories_parent">
            <button type="submit" class="btn-secondary">parent_id catégories</button></form>
        <form method="post" style="display:inline;margin-left:0.5rem"><input type="hidden" name="action" value="produits_cols">
            <button type="submit" class="btn-secondary">Colonnes produits</button></form>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
