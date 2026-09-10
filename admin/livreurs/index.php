<?php
require_once __DIR__ . '/../../includes/session_user.php';
/**
 * Livraisons — commandes (prise en charge par les livreurs)
 */
session_start_persistent();

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_email'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../../includes/admin_route_access.php';
admin_route_enforce();

require_once __DIR__ . '/../../includes/admin_permissions.php';
require_once __DIR__ . '/../../includes/admin_ui_flags.php';
require_once __DIR__ . '/../../includes/site_brand.php';
require_once __DIR__ . '/../../models/model_livreur_tracking.php';

if (!admin_can_livreur_gps()) {
    header('Location: ../dashboard.php');
    exit;
}

$admin_role = normalize_admin_role($_SESSION['admin_role'] ?? 'admin');
$is_livreur = ($admin_role === 'livreur');
$is_admin = admin_can_manage_livreurs();
$admin_session_id = (int) $_SESSION['admin_id'];

$tables_ready = livreur_tracking_tables_ready();
$message = '';
if (!empty($_GET['terminee'])) {
    $message = 'Livraison terminée avec succès.';
}
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tables_ready && ($is_livreur || $is_admin)) {
    $action = $_POST['action'] ?? '';
    if ($action === 'commencer_livraison') {
        $commande_id = (int) ($_POST['commande_id'] ?? 0);
        $result = livreur_commencer_livraison($commande_id, $admin_session_id, [
            'driver_lat' => $_POST['driver_lat'] ?? '',
            'driver_lng' => $_POST['driver_lng'] ?? '',
            'driver_precision' => $_POST['driver_precision'] ?? '',
            'delivery_lat' => $_POST['delivery_lat'] ?? '',
            'delivery_lng' => $_POST['delivery_lng'] ?? '',
            'adresse_livraison' => $_POST['adresse_livraison'] ?? '',
        ], $is_livreur);
        if (!empty($result['ok'])) {
            header('Location: suivi.php?commande_id=' . (int) ($result['commande_id'] ?? $commande_id) . '&autostart=1');
            exit;
        }
        $error = $result['error'] ?? 'Impossible de démarrer la livraison.';
    }
}

$commandes_liste = $tables_ready
    ? livreur_get_commandes_livraison_list($is_livreur)
    : [];

$today_ymd = date('Y-m-d');
$stats = [
    'actives' => 0,
    'disponibles' => 0,
    'en_cours' => 0,
    'terminees' => 0,
];

foreach ($commandes_liste as $cmd_row) {
    $est_terminee = livreur_livraison_est_terminee($cmd_row, 'commande');
    if (empty($cmd_row['date_commande']) && !$is_livreur) {
        continue;
    }
    if (!$is_livreur && date('Y-m-d', strtotime($cmd_row['date_commande'])) !== $today_ymd) {
        continue;
    }

    if ($est_terminee) {
        $stats['terminees']++;
        continue;
    }

    $stats['actives']++;
    $cmd_livreur_id = !empty($cmd_row['livreur_id']) ? (int) $cmd_row['livreur_id'] : null;
    if ($cmd_livreur_id === null) {
        $stats['disponibles']++;
    } else {
        $stats['en_cours']++;
    }
}

$page_title = $is_livreur ? 'Livraisons du jour' : 'Livreurs GPS';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include __DIR__ . '/../../includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> — Administration</title>
    <?php require_once __DIR__ . '/../../includes/asset_version.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-dashboard-home.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-livreurs-index.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-livreur-suivi.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-livreur-tracking-ui.css'); ?>">
</head>
<body class="page-livreurs-index">
<?php include __DIR__ . '/../includes/nav.php'; ?>

<div class="contents-container prod-catalog-hub">

    <header class="prod-catalog-hero">
        <div class="prod-catalog-hero__inner">
            <div class="prod-catalog-hero__content">
                <p class="prod-catalog-hero__eyebrow">
                    <i class="fa-solid fa-motorcycle" aria-hidden="true"></i>
                    Livraisons · <?php echo htmlspecialchars(site_brand_name_market()); ?>
                </p>
                <h1 class="prod-catalog-hero__title">
                    <?php echo $is_livreur ? 'Livraisons <span>du jour</span>' : 'Livreurs <span>GPS</span>'; ?>
                </h1>
                <p class="prod-catalog-hero__subtitle">
                    <?php echo $is_livreur
                        ? 'Prenez une commande et suivez votre trajet en temps réel.'
                        : 'Supervisez les livraisons et le suivi GPS de vos livreurs.'; ?>
                </p>
                <div class="prod-catalog-hero__actions">
                    <?php if ($is_admin && admin_ui_show_livreurs_map()): ?>
                    <a href="carte.php" class="dash-btn-outline">
                        <i class="fas fa-map-location-dot"></i> Carte live
                    </a>
                    <?php endif; ?>
                    <?php include __DIR__ . '/../includes/btn_retour_site.php'; ?>
                </div>
            </div>
            <div class="prod-catalog-hero__meta">
                <span class="prod-catalog-hero__count"><?php echo (int) $stats['actives']; ?></span>
                <span class="prod-catalog-hero__count-label">active<?php echo $stats['actives'] > 1 ? 's' : ''; ?></span>
            </div>
        </div>
    </header>

    <?php if (!$tables_ready): ?>
    <div class="prod-catalog-flash message error">
        <i class="fas fa-database"></i>
        <span>Module GPS indisponible. Vérifiez que MySQL est démarré, puis rechargez la page.</span>
    </div>
    <?php endif; ?>

    <?php if ($message): ?>
    <div class="prod-catalog-flash message success">
        <i class="fas fa-check-circle"></i>
        <span><?php echo htmlspecialchars($message); ?></span>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="prod-catalog-flash message error">
        <i class="fas fa-exclamation-circle"></i>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
    <?php endif; ?>

    <section class="prod-catalog-stats prod-catalog-stats--3" aria-label="Statistiques livraisons">
        <article class="prod-stat prod-stat--actif">
            <span class="prod-stat__icon"><i class="fa-solid fa-hand-pointer"></i></span>
            <div>
                <p class="prod-stat__label">Disponibles</p>
                <p class="prod-stat__value"><?php echo (int) $stats['disponibles']; ?></p>
            </div>
        </article>
        <article class="prod-stat prod-stat--warn">
            <span class="prod-stat__icon"><i class="fa-solid fa-route"></i></span>
            <div>
                <p class="prod-stat__label">En cours</p>
                <p class="prod-stat__value"><?php echo (int) $stats['en_cours']; ?></p>
            </div>
        </article>
        <article class="prod-stat prod-stat--bleu">
            <span class="prod-stat__icon"><i class="fa-solid fa-circle-check"></i></span>
            <div>
                <p class="prod-stat__label">Terminées</p>
                <p class="prod-stat__value"><?php echo (int) $stats['terminees']; ?></p>
            </div>
        </article>
    </section>

    <section class="prod-catalog-main livreur-hub-main" aria-label="Commandes à livrer">
        <header class="prod-catalog-main__head">
            <div class="prod-catalog-main__head-text">
                <h2><i class="fa-solid fa-shopping-bag"></i> Commandes à livrer</h2>
                <p class="prod-catalog-main__filter-hint">
                    <?php echo $is_livreur
                        ? 'Commandes du jour disponibles ou déjà prises en charge.'
                        : 'Filtrez par période et recherchez un client ou un numéro de commande.'; ?>
                </p>
            </div>
        </header>

        <div class="livreur-hub-toolbar">
            <div class="livreur-hub-toolbar__main">
                <div class="livreur-hub-search">
                    <label class="sr-only" for="livreur-search-input">Rechercher une commande</label>
                    <i class="fas fa-search livreur-hub-search__ic" aria-hidden="true"></i>
                    <input type="search"
                        id="livreur-search-input"
                        class="livreur-hub-search__input"
                        placeholder="Nom client, téléphone, n° commande…"
                        autocomplete="off"
                        inputmode="search"
                        data-live-search-input>
                </div>
                <?php if ($is_admin): ?>
                <button type="button"
                    class="livreur-hub-period-btn"
                    id="livreur-period-toggle"
                    aria-expanded="false"
                    aria-controls="livreur-period-panel">
                    <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                    <span>Période</span>
                </button>
                <?php endif; ?>
            </div>

            <?php if ($is_admin): ?>
            <div class="livreur-hub-period-panel" id="livreur-period-panel" hidden>
                <div class="livreur-hub-period-presets" role="group" aria-label="Périodes rapides livraisons">
                    <button type="button" class="livreur-hub-period-preset is-active" data-preset="today">Aujourd'hui</button>
                    <button type="button" class="livreur-hub-period-preset" data-preset="week">7 jours</button>
                    <button type="button" class="livreur-hub-period-preset" data-preset="month">Ce mois</button>
                    <button type="button" class="livreur-hub-period-preset" data-preset="all">Tout</button>
                </div>
                <div class="livreur-hub-period-fields">
                    <div>
                        <label for="livreur-date-debut">Du</label>
                        <input type="date" id="livreur-date-debut">
                    </div>
                    <div>
                        <label for="livreur-date-fin">Au</label>
                        <input type="date" id="livreur-date-fin">
                    </div>
                    <div>
                        <label class="sr-only" for="livreur-period-apply">Appliquer</label>
                        <button type="button" class="livreur-hub-period-apply" id="livreur-period-apply">Appliquer</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($is_admin): ?>
        <p class="livreur-hub-summary" id="livreur-period-summary" aria-live="polite"></p>
        <?php endif; ?>
        <p class="livreur-hub-summary" id="livreur-search-summary" aria-live="polite"></p>

        <div class="liv-cmd-grid" id="livreur-cmd-list">
            <?php if (empty($commandes_liste)): ?>
            <div class="livreur-hub-empty">
                <i class="fas fa-box-open" aria-hidden="true"></i>
                <h3>Aucune commande</h3>
                <p>Aucune commande à livrer<?php echo $is_livreur ? ' aujourd\'hui' : ''; ?> pour le moment.</p>
            </div>
            <?php else: ?>
            <?php foreach ($commandes_liste as $cmd): ?>
                <?php
                $cmd_livreur_id = !empty($cmd['livreur_id']) ? (int) $cmd['livreur_id'] : null;
                $est_terminee = livreur_livraison_est_terminee($cmd, 'commande');
                $prise_par_moi = !$est_terminee && $cmd_livreur_id === $admin_session_id;
                $prise_par_autre = !$est_terminee && $cmd_livreur_id !== null && !$prise_par_moi;
                $disponible = !$est_terminee && $cmd_livreur_id === null;
                $client_nom = trim((string) ($cmd['user_prenom'] ?? $cmd['client_prenom'] ?? '') . ' ' . (string) ($cmd['user_nom'] ?? $cmd['client_nom'] ?? ''));
                if ($client_nom === '') {
                    $client_nom = 'Client';
                }
                $client_tel = trim((string) ($cmd['user_telephone'] ?? $cmd['client_telephone'] ?? $cmd['telephone_livraison'] ?? ''));
                $numero_commande = trim((string) ($cmd['numero_commande'] ?? ''));
                $date_iso = !empty($cmd['date_commande']) ? date('Y-m-d', strtotime($cmd['date_commande'])) : '';
                $search_blob = htmlspecialchars(livreur_commande_search_blob($cmd), ENT_QUOTES, 'UTF-8');
                $delivery_lat = livreur_parse_coord($cmd['delivery_latitude'] ?? null);
                $delivery_lng = livreur_parse_coord($cmd['delivery_longitude'] ?? null);
                $statut_label = $est_terminee ? 'Terminée' : livreur_statut_label($cmd['statut'] ?? '');

                if ($est_terminee) {
                    $badge_class = 'liv-cmd-card__badge--terminee';
                    $badge_text = 'Terminée';
                } elseif ($disponible) {
                    $badge_class = 'liv-cmd-card__badge--dispo';
                    $badge_text = 'Disponible';
                } elseif ($prise_par_moi) {
                    $badge_class = 'liv-cmd-card__badge--cours';
                    $badge_text = 'Ma livraison';
                } elseif ($prise_par_autre) {
                    $badge_class = 'liv-cmd-card__badge--occupe';
                    $badge_text = 'En cours';
                } else {
                    $badge_class = 'liv-cmd-card__badge--cours';
                    $badge_text = htmlspecialchars($statut_label);
                }
                ?>
                <article class="liv-cmd-card<?php echo $est_terminee ? ' liv-cmd-card--terminee' : ''; ?>"
                    data-search="<?php echo $search_blob; ?>"
                    data-date="<?php echo htmlspecialchars($date_iso, ENT_QUOTES, 'UTF-8'); ?>"
                    <?php echo $est_terminee ? ' data-terminee="1"' : ''; ?>>
                    <div class="liv-cmd-card__head">
                        <div class="liv-cmd-card__avatar" aria-hidden="true">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="liv-cmd-card__info">
                            <h3 class="liv-cmd-card__name"><?php echo htmlspecialchars($client_nom); ?></h3>
                            <?php if ($client_tel !== ''): ?>
                            <p class="liv-cmd-card__tel">
                                <i class="fas fa-phone" aria-hidden="true"></i>
                                <?php echo htmlspecialchars($client_tel); ?>
                            </p>
                            <?php endif; ?>
                            <?php if ($numero_commande !== ''): ?>
                            <p class="liv-cmd-card__numero">
                                <i class="fas fa-hashtag" aria-hidden="true"></i>
                                <?php echo htmlspecialchars($numero_commande); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <span class="liv-cmd-card__badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                    </div>

                    <div class="liv-cmd-card__meta">
                        <?php if ($est_terminee && $cmd_livreur_id): ?>
                        <p class="liv-cmd-card__taken">
                            Livrée par <?php echo htmlspecialchars(trim(($cmd['livreur_prenom'] ?? '') . ' ' . ($cmd['livreur_nom'] ?? ''))); ?>
                        </p>
                        <?php elseif ($prise_par_autre): ?>
                        <p class="liv-cmd-card__taken">
                            Prise par <?php echo htmlspecialchars(trim(($cmd['livreur_prenom'] ?? '') . ' ' . ($cmd['livreur_nom'] ?? ''))); ?>
                        </p>
                        <?php endif; ?>

                        <div class="liv-cmd-card__actions">
                            <?php if ($est_terminee): ?>
                            <span class="liv-cmd-card__btn liv-cmd-card__btn--ghost">
                                <i class="fas fa-check-circle" aria-hidden="true"></i> Livraison terminée
                            </span>
                            <?php elseif (($is_livreur || $is_admin) && $disponible): ?>
                            <button type="button"
                                class="liv-cmd-card__btn liv-cmd-card__btn--primary livreur-btn-prendre"
                                data-livraison-type="commande"
                                data-commande-id="<?php echo (int) $cmd['id']; ?>"
                                data-numero="<?php echo htmlspecialchars($numero_commande, ENT_QUOTES, 'UTF-8'); ?>"
                                data-adresse="<?php echo htmlspecialchars($cmd['adresse_livraison'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                data-delivery-lat="<?php echo $delivery_lat !== null ? htmlspecialchars((string) $delivery_lat, ENT_QUOTES, 'UTF-8') : ''; ?>"
                                data-delivery-lng="<?php echo $delivery_lng !== null ? htmlspecialchars((string) $delivery_lng, ENT_QUOTES, 'UTF-8') : ''; ?>">
                                <i class="fas fa-hand-pointer" aria-hidden="true"></i>
                                Prendre la commande
                            </button>
                            <?php elseif (($is_livreur || $is_admin) && $prise_par_moi): ?>
                            <a href="suivi.php?commande_id=<?php echo (int) $cmd['id']; ?>&amp;autostart=1"
                                class="liv-cmd-card__btn liv-cmd-card__btn--secondary livreur-btn-suivi">
                                <i class="fas fa-map-location-dot" aria-hidden="true"></i>
                                Suivi GPS
                            </a>
                            <?php elseif ($prise_par_autre && !$is_admin): ?>
                            <span class="liv-cmd-card__btn liv-cmd-card__btn--ghost">
                                <i class="fas fa-lock" aria-hidden="true"></i> Déjà prise
                            </span>
                            <?php elseif ($is_admin && ($prise_par_autre || $cmd_livreur_id)): ?>
                            <a href="suivi.php?commande_id=<?php echo (int) $cmd['id']; ?>"
                                class="liv-cmd-card__btn liv-cmd-card__btn--link">
                                <i class="fas fa-map-location-dot" aria-hidden="true"></i> Voir le GPS
                            </a>
                            <?php else: ?>
                            <span class="liv-cmd-card__btn liv-cmd-card__btn--ghost">Disponible</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <p class="livreur-hub-no-results" id="livreur-no-results-commandes" hidden>
            <i class="fas fa-search" aria-hidden="true"></i>
            Aucune commande ne correspond à votre recherche ou à la période sélectionnée.
        </p>
    </section>
</div>

<?php if ($tables_ready && ($is_livreur || $is_admin)): ?>
<div id="livreur-demarrage-panel" class="livreur-demarrage-panel" hidden aria-hidden="true">
    <div class="livreur-demarrage-panel__backdrop" data-livreur-demarrage-close aria-hidden="true"></div>
    <div class="livreur-demarrage-panel__card" role="dialog" aria-modal="true" aria-labelledby="livreur-demarrage-title">
        <header class="livreur-demarrage-panel__head">
            <div class="livreur-demarrage-panel__head-main">
                <span class="livreur-demarrage-panel__icon" aria-hidden="true"><i class="fas fa-route"></i></span>
                <div>
                    <h3 id="livreur-demarrage-title">Démarrer la livraison</h3>
                    <p class="livreur-demarrage-panel__cmd">
                        <span id="livreur-demarrage-label">Commande</span>
                        <strong id="livreur-demarrage-numero"></strong>
                    </p>
                </div>
            </div>
            <button type="button" class="livreur-demarrage-panel__close" data-livreur-demarrage-close aria-label="Fermer">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </header>

        <form method="post" id="livreur-demarrage-form" class="livreur-demarrage-form">
            <input type="hidden" name="action" id="livreur-demarrage-action" value="commencer_livraison">
            <input type="hidden" name="commande_id" id="livreur-demarrage-commande-id" value="">
            <input type="hidden" name="bl_id" id="livreur-demarrage-bl-id" value="">
            <input type="hidden" name="driver_lat" id="livreur-driver-lat" value="">
            <input type="hidden" name="driver_lng" id="livreur-driver-lng" value="">
            <input type="hidden" name="driver_precision" id="livreur-driver-precision" value="">
            <input type="hidden" name="delivery_lat" id="livreur-delivery-lat" value="">
            <input type="hidden" name="delivery_lng" id="livreur-delivery-lng" value="">

            <div class="livreur-demarrage-field">
                <label for="livreur-driver-position">Votre position (départ)</label>
                <input type="text" id="livreur-driver-position" readonly placeholder="Capture GPS en cours…">
            </div>

            <div class="livreur-demarrage-field livreur-demarrage-field--address">
                <label for="livreur-demarrage-adresse">Coordonnées GPS client (arrivée)</label>
                <div class="livreur-address-autocomplete" id="livreur-address-autocomplete">
                    <textarea name="adresse_livraison" id="livreur-demarrage-adresse" rows="2" required placeholder="Latitude, longitude (ex. 14.693700, -17.444100)" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" enterkeyhint="search" inputmode="decimal" role="combobox" aria-autocomplete="list" aria-controls="livreur-address-suggest" aria-expanded="false"></textarea>
                    <ul id="livreur-address-suggest" class="livreur-address-suggest" role="listbox" hidden aria-label="Suggestions d'adresse"></ul>
                </div>
            </div>

            <div id="livreur-demarrage-status" class="livreur-demarrage-status" data-state="pending" aria-live="polite"></div>

            <div id="livreur-demarrage-map" class="livreur-demarrage-map" aria-label="Carte départ et arrivée"></div>

            <div class="livreur-demarrage-legend">
                <span><i class="fas fa-motorcycle" aria-hidden="true"></i> Départ</span>
                <span><i class="fas fa-house" aria-hidden="true"></i> Arrivée</span>
                <span><i class="fas fa-route" aria-hidden="true"></i> Itinéraire</span>
            </div>

            <div class="livreur-demarrage-actions">
                <button type="button" class="btn-secondary" data-livreur-demarrage-close>Annuler</button>
                <button type="submit" class="btn-primary livreur-demarrage-submit">
                    <i class="fas fa-play" aria-hidden="true"></i> Commencer la livraison
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
window.LIVREUR_INDEX_UI = {
    enablePeriod: <?php echo $is_admin ? 'true' : 'false'; ?>
};
</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="<?php echo asset_url('/js/livreur-route-api.js'); ?>"></script>
<script src="<?php echo asset_url('/js/admin-livreur-demarrage.js'); ?>"></script>
<script src="<?php echo asset_url('/js/admin-livreurs-index-ui.js'); ?>"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
