<?php
require_once __DIR__ . '/../includes/admin_auth.php';
/**
 * Historique des ventes / Comptabilité
 * Filtres: jour, période (date début - date fin), mois, année
 * Vue par défaut: ventes du jour
 */
// Accès réservé aux administrateurs (rôle admin uniquement)
if (($_SESSION['admin_role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../../models/model_commandes_admin.php';
require_once __DIR__ . '/../../includes/site_brand.php';

$periode = isset($_GET['periode']) ? $_GET['periode'] : 'jour';
if (!in_array($periode, ['jour', 'plage', 'annee'])) {
    $periode = 'jour';
}

$annee = isset($_GET['annee']) ? (int) $_GET['annee'] : (int) date('Y');
$mois = isset($_GET['mois']) ? (int) $_GET['mois'] : (int) date('n');
$jour = isset($_GET['jour']) ? (int) $_GET['jour'] : (int) date('j');
$date_debut = isset($_GET['date_debut']) ? trim($_GET['date_debut']) : null;
$date_fin = isset($_GET['date_fin']) ? trim($_GET['date_fin']) : null;

// Le filtre "mois" travaille uniquement sur le mois choisi de l'année en cours.
if ($periode === 'mois') {
    $annee = (int) date('Y');
}

// Si date_jour fournie (input type=date), extraire annee, mois, jour
if (!empty($_GET['date_jour'])) {
    $parts = explode('-', $_GET['date_jour']);
    if (count($parts) === 3) {
        $annee = (int) $parts[0];
        $mois = (int) $parts[1];
        $jour = (int) $parts[2];
    }
}

$commandes = get_commandes_by_periode($periode, $annee, $mois, $date_debut, $date_fin, $jour);
$stats = get_stats_comptabilite_periode($commandes);

// Stats globales (toutes les commandes, tous statuts)
$stats_globales = [
    'montant_total' => get_montant_total_commandes(),
    'montant_livrees' => get_montant_total_commandes('livree'),
    'montant_non_traitees' => get_montant_total_commandes() - get_montant_total_commandes('livree') - get_montant_total_commandes('annulee'),
    'nb_total' => count_commandes_by_statut(),
    'nb_livrees' => count_commandes_by_statut('livree'),
    'nb_annulees' => count_commandes_by_statut('annulee')
];

$libelle_periode = '';
switch ($periode) {
    case 'jour':
        $libelle_periode = date('d/m/Y', strtotime("$annee-$mois-$jour"));
        break;
    case 'plage':
        if ($date_debut && $date_fin) {
            $libelle_periode = date('d/m/Y', strtotime($date_debut)) . ' - ' . date('d/m/Y', strtotime($date_fin));
        } elseif ($date_debut) {
            $libelle_periode = 'À partir du ' . date('d/m/Y', strtotime($date_debut));
        } elseif ($date_fin) {
            $libelle_periode = "Jusqu'au " . date('d/m/Y', strtotime($date_fin));
        } else {
            $libelle_periode = 'Période personnalisée';
        }
        break;
    case 'annee':
        $libelle_periode = "Année $annee";
        break;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <?php include __DIR__ . '/../../includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des ventes - Comptabilité</title>
    <?php require_once __DIR__ . '/../../includes/asset_version.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-dashboard-home.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-commandes-pages.css'); ?>">
</head>

<body class="page-commandes-historique">
    <?php include '../includes/nav.php'; ?>

    <div class="contents-container prod-catalog-hub">

        <header class="prod-catalog-hero">
            <div class="prod-catalog-hero__inner">
                <div class="prod-catalog-hero__content">
                    <p class="prod-catalog-hero__eyebrow">
                        <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
                        Comptabilité · <?php echo htmlspecialchars(site_brand_name_market()); ?>
                    </p>
                    <h1 class="prod-catalog-hero__title">Historique des <span>ventes</span></h1>
                    <p class="prod-catalog-hero__subtitle">Analysez vos performances et suivez la comptabilité par période.</p>
                    <div class="prod-catalog-hero__actions">
                        <a href="index.php" class="dash-btn-outline">
                            <i class="fas fa-arrow-left"></i> Retour commandes
                        </a>
                    </div>
                </div>
                <div class="prod-catalog-hero__meta">
                    <span class="prod-catalog-hero__count"><?php echo (int) $stats['nb_commandes']; ?></span>
                    <span class="prod-catalog-hero__count-label">commande<?php echo (int) $stats['nb_commandes'] > 1 ? 's' : ''; ?> · période</span>
                </div>
            </div>
        </header>

        <form method="GET" action="historique-ventes.php" class="prod-catalog-filters">
            <div class="prod-catalog-filters__fields">
                <div class="prod-catalog-filters__field prod-catalog-filters__field--sm">
                    <label for="periode-select"><i class="fa-solid fa-filter"></i> Type de période</label>
                    <select name="periode" id="periode-select">
                        <option value="jour" <?php echo $periode === 'jour' ? 'selected' : ''; ?>>Jour</option>
                        <option value="plage" <?php echo $periode === 'plage' ? 'selected' : ''; ?>>Période</option>
                        <option value="annee" <?php echo $periode === 'annee' ? 'selected' : ''; ?>>Année</option>
                    </select>
                </div>
                <div class="prod-catalog-filters__field prod-catalog-filters__field--sm" id="wrap-jour" style="display:<?php echo $periode === 'jour' ? 'flex' : 'none'; ?>">
                    <label for="date-jour"><i class="fa-solid fa-calendar-day"></i> Date</label>
                    <input type="date" name="date_jour" id="date-jour" value="<?php echo sprintf('%04d-%02d-%02d', $annee, $mois, $jour); ?>">
                </div>
                <div class="prod-catalog-filters__field prod-catalog-filters__field--sm" id="wrap-plage" style="display:<?php echo $periode === 'plage' ? 'flex' : 'none'; ?>">
                    <label for="date-debut"><i class="fa-solid fa-calendar-plus"></i> Date début</label>
                    <input type="date" name="date_debut" id="date-debut" value="<?php echo htmlspecialchars($date_debut ?? date('Y-m-d')); ?>">
                </div>
                <div class="prod-catalog-filters__field prod-catalog-filters__field--sm" id="wrap-plage-fin" style="display:<?php echo $periode === 'plage' ? 'flex' : 'none'; ?>">
                    <label for="date-fin"><i class="fa-solid fa-calendar-check"></i> Date fin</label>
                    <input type="date" name="date_fin" id="date-fin" value="<?php echo htmlspecialchars($date_fin ?? date('Y-m-d')); ?>">
                </div>
                <div class="prod-catalog-filters__field prod-catalog-filters__field--sm" id="wrap-annee" style="display:<?php echo $periode === 'annee' ? 'flex' : 'none'; ?>">
                    <label for="annee-select"><i class="fa-solid fa-calendar"></i> Année</label>
                    <select name="annee" id="annee-select">
                        <?php for ($a = date('Y'); $a >= date('Y') - 5; $a--): ?>
                        <option value="<?php echo $a; ?>" <?php echo $annee === $a ? 'selected' : ''; ?>><?php echo $a; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="prod-catalog-filters__actions">
                <button type="submit" class="btn-primary"><i class="fas fa-search"></i> Appliquer</button>
            </div>
        </form>

        <section class="prod-catalog-main" aria-label="Vue d'ensemble globale">
            <header class="prod-catalog-main__head">
                <div class="prod-catalog-main__head-text">
                    <h2><i class="fa-solid fa-globe"></i> Vue d'ensemble globale</h2>
                </div>
            </header>
            <div class="prod-catalog-stats" style="margin-bottom:0;">
                <article class="prod-stat prod-stat--money">
                    <span class="prod-stat__icon"><i class="fa-solid fa-coins"></i></span>
                    <div>
                        <p class="prod-stat__label">Montant total</p>
                        <p class="prod-stat__value prod-stat__value--sm"><?php echo number_format($stats_globales['montant_total'], 0, ',', ' '); ?> F</p>
                    </div>
                </article>
                <article class="prod-stat prod-stat--ok">
                    <span class="prod-stat__icon"><i class="fa-solid fa-check"></i></span>
                    <div>
                        <p class="prod-stat__label">Montant livrées</p>
                        <p class="prod-stat__value prod-stat__value--sm"><?php echo number_format($stats_globales['montant_livrees'], 0, ',', ' '); ?> F</p>
                    </div>
                </article>
                <article class="prod-stat prod-stat--warn">
                    <span class="prod-stat__icon"><i class="fa-solid fa-hourglass-half"></i></span>
                    <div>
                        <p class="prod-stat__label">Non traitées</p>
                        <p class="prod-stat__value prod-stat__value--sm"><?php echo number_format($stats_globales['montant_non_traitees'], 0, ',', ' '); ?> F</p>
                    </div>
                </article>
                <article class="prod-stat prod-stat--total">
                    <span class="prod-stat__icon"><i class="fa-solid fa-receipt"></i></span>
                    <div>
                        <p class="prod-stat__label">Nb commandes</p>
                        <p class="prod-stat__value"><?php echo (int) $stats_globales['nb_total']; ?></p>
                    </div>
                </article>
            </div>
        </section>

        <section class="prod-catalog-main prod-catalog-main--alt" aria-label="Période sélectionnée">
            <header class="prod-catalog-main__head">
                <div class="prod-catalog-main__head-text">
                    <h2><i class="fa-solid fa-calendar-alt"></i> Période : <?php echo htmlspecialchars($libelle_periode); ?></h2>
                </div>
            </header>

            <div class="prod-catalog-stats prod-catalog-stats--3" style="margin-bottom:22px;">
                <article class="prod-stat prod-stat--orange">
                    <span class="prod-stat__icon"><i class="fa-solid fa-sack-dollar"></i></span>
                    <div>
                        <p class="prod-stat__label">Montant période</p>
                        <p class="prod-stat__value prod-stat__value--sm"><?php echo number_format($stats['montant_total'], 0, ',', ' '); ?> F</p>
                    </div>
                </article>
                <article class="prod-stat prod-stat--total">
                    <span class="prod-stat__icon"><i class="fa-solid fa-list"></i></span>
                    <div>
                        <p class="prod-stat__label">Nb commandes</p>
                        <p class="prod-stat__value"><?php echo (int) $stats['nb_commandes']; ?></p>
                    </div>
                </article>
                <article class="prod-stat prod-stat--ok">
                    <span class="prod-stat__icon"><i class="fa-solid fa-truck"></i></span>
                    <div>
                        <p class="prod-stat__label">Livrées</p>
                        <p class="prod-stat__value prod-stat__value--sm"><?php echo number_format($stats['montant_livrees'], 0, ',', ' '); ?> F</p>
                    </div>
                </article>
            </div>

        <?php if (empty($commandes)): ?>
            <div class="prod-catalog-empty">
                <i class="fas fa-receipt"></i>
                <h3>Aucune vente sur cette période</h3>
                <p>Modifiez les filtres pour afficher l'historique des ventes.</p>
            </div>
        <?php else: ?>
            <div class="dash-table-wrap">
                <table class="dash-data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>N° Commande</th>
                            <th>Client</th>
                            <th>Statut</th>
                            <th class="col-montant">Montant</th>
                            <th class="col-action">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commandes as $c): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($c['date_commande'])); ?></td>
                            <td><?php echo htmlspecialchars($c['numero_commande']); ?></td>
                            <td><?php echo htmlspecialchars(trim(($c['user_prenom'] ?? '') . ' ' . ($c['user_nom'] ?? ''))); ?></td>
                            <td><span class="commande-statut statut-<?php echo htmlspecialchars($c['statut']); ?>"><?php echo ucfirst(str_replace('_', ' ', $c['statut'])); ?></span></td>
                            <td class="col-montant"><?php echo number_format($c['montant_total'], 0, ',', ' '); ?> FCFA</td>
                            <td class="col-action">
                                <a href="details.php?id=<?php echo (int) $c['id']; ?>" class="dash-table-btn" title="Voir détails"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        </section>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        (function () {
            var periodeSelect = document.getElementById('periode-select');
            function togglePeriodeFields(periode) {
                var wrapJour = document.getElementById('wrap-jour');
                var wrapPlage = document.getElementById('wrap-plage');
                var wrapPlageFin = document.getElementById('wrap-plage-fin');
                var wrapAnnee = document.getElementById('wrap-annee');
                var displayFlex = 'flex';
                var displayNone = 'none';
                if (wrapJour) wrapJour.style.display = periode === 'jour' ? displayFlex : displayNone;
                if (wrapPlage) wrapPlage.style.display = periode === 'plage' ? displayFlex : displayNone;
                if (wrapPlageFin) wrapPlageFin.style.display = periode === 'plage' ? displayFlex : displayNone;
                if (wrapAnnee) wrapAnnee.style.display = periode === 'annee' ? displayFlex : displayNone;
            }
            if (periodeSelect) {
                periodeSelect.addEventListener('change', function () { togglePeriodeFields(this.value); });
            }
        })();
    </script>
</body>

</html>