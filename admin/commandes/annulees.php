<?php
require_once __DIR__ . '/../includes/admin_auth.php';
/**
 * Page de liste des commandes annulées (Admin)
 * Programmation procédurale uniquement
 */
// Récupérer toutes les commandes
require_once __DIR__ . '/../../models/model_commandes_admin.php';
$toutes_commandes = get_all_commandes();

// Filtrer pour ne garder que les commandes avec le statut "annulee"
$commandes_annulees = array_filter($toutes_commandes, function ($commande) {
    return ($commande['statut'] ?? '') === 'annulee';
});

usort($commandes_annulees, function ($a, $b) {
    return strtotime($b['date_commande'] ?? 'now') <=> strtotime($a['date_commande'] ?? 'now');
});

// Statistiques
$total_commandes = count_commandes_by_statut();
$annulees = count_commandes_by_statut('annulee');

// Comptabilité : montant total des commandes annulées
$montant_total_annulees = get_montant_total_commandes('annulee');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include __DIR__ . '/../../includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commandes Annulées - Administration</title>
    <?php require_once __DIR__ . '/../../includes/asset_version.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-commandes-pages.css'); ?>">
</head>
<body class="page-commandes-annulees">
    <?php include '../includes/nav.php'; ?>
    
    <div class="content-header">
        <h1><i class="fas fa-ban"></i> Commandes Annulées</h1>
    </div>

    <!-- Statistiques -->
    <div class="commandes-stats">
        <div class="stat-box">
            <h3>Total Commandes</h3>
            <div class="stat-value"><?php echo $total_commandes; ?></div>
        </div>
        <div class="stat-box">
            <h3>Commandes Annulées</h3>
            <div class="stat-value"><?php echo $annulees; ?></div>
        </div>
    </div>

    <!-- Comptabilité -->
    <div class="comptabilite-box">
        <div class="comptabilite-label"><i class="fas fa-calculator"></i> Montant total des commandes annulées</div>
        <div class="comptabilite-value"><?php echo number_format($montant_total_annulees, 0, ',', ' '); ?> FCFA</div>
    </div>

    <!-- Liste des commandes -->
    <section class="content-section">
        <div class="section-header">
            <div class="section-title">
                <h2><i class="fas fa-ban"></i> Commandes Annulées (<?php echo count($commandes_annulees); ?>)</h2>
            </div>
            <a href="index.php" class="btn-link">
                <i class="fas fa-shopping-bag"></i> Voir les commandes à traiter
            </a>
        </div>

        <?php if (empty($commandes_annulees)): ?>
            <div class="empty-state">
                <i class="fas fa-ban"></i>
                <h3>Aucune commande annulée</h3>
                <p>Aucune commande n'a été annulée pour le moment.</p>
            </div>
        <?php else: ?>
            <div class="commandes-grid">
                <?php foreach ($commandes_annulees as $commande):
                    $client_nom = trim(
                        trim((string) ($commande['user_prenom'] ?? $commande['client_prenom'] ?? '')) . ' ' .
                        trim((string) ($commande['user_nom'] ?? $commande['client_nom'] ?? ''))
                    );
                    $client_email = trim((string) ($commande['user_email'] ?? $commande['client_email'] ?? ''));
                    $telephone = trim((string) ($commande['telephone_livraison'] ?? $commande['client_telephone'] ?? ''));
                    $adresse = trim((string) ($commande['adresse_livraison'] ?? ''));
                    $adresse_courte = $adresse !== '' ? (mb_strlen($adresse) > 30 ? mb_substr($adresse, 0, 30) . '…' : $adresse) : '—';
                ?>
                    <div class="commande-item">
                        <div class="commande-header">
                            <div class="commande-info">
                                <h3>Commande #<?php echo htmlspecialchars((string) ($commande['numero_commande'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p>
                                    <strong>Client:</strong>
                                    <?php echo htmlspecialchars($client_nom !== '' ? $client_nom : '—', ENT_QUOTES, 'UTF-8'); ?><br>
                                    <span class="client-email"><?php echo htmlspecialchars($client_email !== '' ? $client_email : '—', ENT_QUOTES, 'UTF-8'); ?></span>
                                </p>
                                <p class="commande-date">Date:
                                    <?php echo !empty($commande['date_commande']) ? date('d/m/Y à H:i', strtotime($commande['date_commande'])) : '—'; ?>
                                </p>
                            </div>
                            <span class="commande-statut statut-annulee">
                                <i class="fas fa-ban"></i> Annulée
                            </span>
                        </div>
                        <div class="commande-details">
                            <div class="detail-item">
                                <label>Montant total</label>
                                <div class="value"><?php echo number_format((float) ($commande['montant_total'] ?? 0), 0, ',', ' '); ?> FCFA</div>
                            </div>
                            <div class="detail-item">
                                <label>Adresse</label>
                                <div class="value small"><?php echo htmlspecialchars($adresse_courte, ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div class="detail-item">
                                <label>Téléphone</label>
                                <div class="value"><?php echo htmlspecialchars($telephone !== '' ? $telephone : '—', ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                        </div>

                        <a href="details.php?id=<?php echo (int) ($commande['id'] ?? 0); ?>" class="btn-view">
                            <i class="fas fa-eye"></i> Voir les détails
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php include '../includes/footer.php'; ?>

</body>
</html>

