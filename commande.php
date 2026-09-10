<?php
require_once __DIR__ . '/includes/session_user.php';
session_start_persistent();

require_once __DIR__ . '/models/model_panier.php';
require_once __DIR__ . '/models/model_users.php';
require_once __DIR__ . '/models/model_zones_livraison.php';
require_once __DIR__ . '/controllers/controller_commandes.php';
require_once __DIR__ . '/includes/panier_invite.php';
require_once __DIR__ . '/includes/guest_checkout.php';
require_once __DIR__ . '/includes/site_brand.php';
require_once __DIR__ . '/includes/site_url.php';
require_once __DIR__ . '/includes/image_optimizer.php';

$commande_invite = !isset($_SESSION['user_id']);
$zones_livraison = get_all_zones_livraison('actif');

if ($commande_invite && !guest_checkout_has_info()) {
    redirect_to('/panier.php?error=' . urlencode('Veuillez renseigner vos coordonnées avant de commander.'));
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_commande') {
    $result = process_create_commande();

    if ($result['success']) {
        if (!empty($result['is_guest'])) {
            redirect_to('/commande-succes.php?numero=' . urlencode($result['numero_commande']));
        }

        ignore_user_abort(true);
        header('Location: ' . public_url('/user/mes-commandes.php?success=1&numero=' . urlencode($result['numero_commande'])));
        echo ' ';
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            flush();
            if (ob_get_level()) {
                ob_end_flush();
            }
        }

        if (!empty($result['email_data']) && file_exists(__DIR__ . '/services/send_new_commande_to_admin.php')) {
            require_once __DIR__ . '/services/send_new_commande_to_admin.php';
            $d = $result['email_data'];
            send_new_commande_to_admin(
                $d['numero_commande'],
                $d['montant_total'],
                $d['nombre_articles'],
                $d['telephone_livraison'] ?? '',
                $d['adresse_livraison'] ?? '',
                $d['produits'] ?? []
            );
        }
        if (file_exists(__DIR__ . '/services/send_commande_confirmation_to_client.php')) {
            require_once __DIR__ . '/services/send_commande_confirmation_to_client.php';
            $user_mail = get_user_by_id((int) $_SESSION['user_id']);
            $client_email = trim($user_mail['email'] ?? ($_SESSION['user_email'] ?? ''));
            send_new_commande_confirmation_to_client(
                (int) $_SESSION['user_id'],
                $result['numero_commande'],
                (float) ($result['email_data']['montant_total'] ?? 0),
                $client_email
            );
        }
        exit;
    }

    $message = $result['message'];
    $message_type = 'error';
}

$user = $commande_invite ? null : get_user_by_id($_SESSION['user_id']);
$panier_items = panier_get_items_courant();

if (empty($panier_items)) {
    redirect_to('/panier.php');
}

$panier_total = panier_get_total_courant();
$nombre_total_articles = 0;
foreach ($panier_items as $item) {
    $nombre_total_articles += $item['quantite'];
}

$guest_info = $commande_invite && guest_checkout_has_info() ? guest_checkout_get_info() : ['nom' => '', 'telephone' => ''];
$user_telephone_display = $commande_invite
    ? ($guest_info['telephone'] ?? '')
    : trim((string) ($user['telephone'] ?? ''));
if (isset($_POST['telephone_livraison'])) {
    $telephone_form_value = trim((string) $_POST['telephone_livraison']);
} else {
    $tel_digits = users_normalize_phone_digits($user_telephone_display);
    $telephone_form_value = $tel_digits !== '' ? ('+' . $tel_digits) : '';
}
$user_has_location = $commande_invite ? false : users_has_location($user);
$user_lat = (!$commande_invite && $user_has_location) ? (float) $user['last_latitude'] : null;
$user_lng = (!$commande_invite && $user_has_location) ? (float) $user['last_longitude'] : null;
$user_location_label = $commande_invite ? '' : trim((string) ($user['location_label'] ?? ''));

$post_mode = isset($_POST['mode_livraison']) && $_POST['mode_livraison'] === 'retrait' ? 'retrait' : 'livraison';
$default_mode = $message_type === 'error' ? $post_mode : 'livraison';
$commande_image_fallback = public_url('/image/produit1.jpg');
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/includes/pwa_meta.php'; ?>
    <title>Passer la commande — <?php echo htmlspecialchars(site_brand_name()); ?></title>
    <link rel="preconnect" href="https://unpkg.com" crossorigin>
    <link rel="preconnect" href="https://tile.openstreetmap.org" crossorigin>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <?php
    require_once __DIR__ . '/includes/asset_version.php';
    require_once __DIR__ . '/includes/head_public_assets.php';
    render_public_head_assets([
        '/css/style.css',
        '/css/a_style.css',
        '/css/responsive-site.css',
        '/css/auth-pages.css',
        '/css/commande-checkout.css',
        '/css/footer.css',
        '/css/bottom-nav.css',
        '/css/social-floating.css',
    ]);
    if (!defined('FOOTER_CSS_LOADED')) {
        define('FOOTER_CSS_LOADED', true);
    }
    if (!defined('BOTTOM_NAV_CSS_LOADED')) {
        define('BOTTOM_NAV_CSS_LOADED', true);
    }
    if (!defined('SOCIAL_FLOATING_CSS_LOADED')) {
        define('SOCIAL_FLOATING_CSS_LOADED', true);
    }
    include __DIR__ . '/includes/auth_intl_tel_head.php';
    ?>
</head>

<body class="commande-page">

    <?php include 'nav_bar.php'; ?>

    <div class="commande-hub">
        <header class="commande-hero">
            <h1>Passer la <span>commande</span></h1>
            <p>Choisissez le retrait sur place ou une livraison avec votre position GPS.</p>
            <?php if ($commande_invite && $guest_info['nom'] !== ''): ?>
            <p class="commande-guest-badge">Commande invité : <?php echo htmlspecialchars($guest_info['nom']); ?></p>
            <?php endif; ?>
        </header>

        <?php if ($message): ?>
        <div class="commande-flash commande-flash--error" role="alert">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
        <?php endif; ?>

        <div class="commande-layout">
            <section class="commande-panel">
                <h2><i class="fas fa-truck" aria-hidden="true"></i> Mode de réception</h2>

                <form method="POST" action="" id="form-commande">
                    <input type="hidden" name="action" value="create_commande">
                    <input type="hidden" name="mode_livraison" id="mode_livraison" value="<?php echo htmlspecialchars($default_mode); ?>">
                    <input type="hidden" name="delivery_latitude" id="delivery_latitude"
                        value="<?php echo isset($_POST['delivery_latitude']) ? htmlspecialchars($_POST['delivery_latitude']) : ($user_lat !== null ? $user_lat : ''); ?>">
                    <input type="hidden" name="delivery_longitude" id="delivery_longitude"
                        value="<?php echo isset($_POST['delivery_longitude']) ? htmlspecialchars($_POST['delivery_longitude']) : ($user_lng !== null ? $user_lng : ''); ?>">

                    <div class="commande-mode-tabs" role="tablist" aria-label="Mode de réception">
                        <button type="button" class="commande-mode-tab<?php echo $default_mode === 'retrait' ? ' is-active' : ''; ?>"
                            id="tab-mode-retrait" role="tab" aria-selected="<?php echo $default_mode === 'retrait' ? 'true' : 'false'; ?>">
                            <i class="fas fa-store" aria-hidden="true"></i>
                            <span>Récupérer sur place</span>
                        </button>
                        <button type="button" class="commande-mode-tab<?php echo $default_mode === 'livraison' ? ' is-active' : ''; ?>"
                            id="tab-mode-livraison" role="tab" aria-selected="<?php echo $default_mode === 'livraison' ? 'true' : 'false'; ?>">
                            <i class="fas fa-motorcycle" aria-hidden="true"></i>
                            <span>Livraison</span>
                        </button>
                    </div>

                    <div class="commande-retrait-info" id="retrait-info" <?php echo $default_mode === 'retrait' ? '' : 'hidden'; ?>>
                        <i class="fas fa-info-circle" aria-hidden="true"></i>
                        <div>
                            <strong>Retrait en boutique</strong><br>
                            Vous récupérez votre commande sur place. Aucun frais de livraison ne sera appliqué.
                        </div>
                    </div>

                    <div id="panel-livraison" class="commande-livraison-panel" <?php echo $default_mode === 'livraison' ? '' : 'hidden'; ?>>
                        <?php if (empty($zones_livraison)): ?>
                        <div class="commande-flash commande-flash--error">
                            Aucune zone de livraison configurée. Contactez l'administrateur ou choisissez le retrait sur place.
                        </div>
                        <?php else: ?>
                        <div class="commande-field">
                            <label for="zone_livraison_id"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> Zone de livraison *</label>
                            <select id="zone_livraison_id" name="zone_livraison_id" <?php echo $default_mode === 'livraison' ? 'required' : ''; ?>>
                                <option value="">Sélectionnez votre zone</option>
                                <?php foreach ($zones_livraison as $zone): ?>
                                <option value="<?php echo (int) $zone['id']; ?>"
                                    data-prix="<?php echo (float) $zone['prix_livraison']; ?>"
                                    <?php echo (isset($_POST['zone_livraison_id']) && (int) $_POST['zone_livraison_id'] === (int) $zone['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($zone['ville'] . ' - ' . $zone['quartier']); ?>
                                    (<?php echo number_format($zone['prix_livraison'], 0, ',', ' '); ?> FCFA)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="commande-map-block is-interactive" id="commande-map-block">
                            <div class="commande-map-toolbar">
                                <p class="commande-map-label">
                                    Votre position
                                    <span id="location-label"><?php echo $user_location_label !== '' ? htmlspecialchars($user_location_label) : 'Détection automatique via GPS'; ?></span>
                                </p>
                            </div>
                            <div id="commande-map" aria-label="Carte de livraison"></div>
                            <p class="commande-location-status<?php echo $user_has_location ? ' is-ok' : ''; ?>" id="location-status">
                                <?php if ($user_has_location): ?>
                                    Position enregistrée<?php echo $user_location_label !== '' ? ' : ' . htmlspecialchars($user_location_label) : ''; ?>
                                <?php else: ?>
                                    Autorisez la localisation pour afficher votre position exacte sur la carte.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <div class="commande-field">
                        <label for="telephone_livraison"><i class="fas fa-phone" aria-hidden="true"></i> Téléphone *</label>
                        <div class="input-wrapper input-wrapper--intl-tel">
                            <input type="tel" id="telephone_livraison" name="telephone_livraison" placeholder="77 123 45 67"
                                autocomplete="tel" required value=""
                                data-initial-phone="<?php echo htmlspecialchars($telephone_form_value); ?>">
                        </div>
                        <small>Numéro joignable pour la commande</small>
                    </div>

                    <button type="submit" class="commande-btn commande-btn--primary"
                        <?php echo ($default_mode === 'livraison' && empty($zones_livraison)) ? 'disabled' : ''; ?>>
                        <i class="fas fa-check-circle" aria-hidden="true"></i>
                        Confirmer la commande
                    </button>
                </form>
            </section>

            <aside class="commande-panel commande-summary">
                <h2><i class="fas fa-shopping-cart" aria-hidden="true"></i> Résumé</h2>
                <p style="margin:0 0 14px;">
                    <span class="commande-summary-mode" id="summary-mode-label">
                        <?php echo $default_mode === 'retrait' ? 'Retrait sur place' : 'Livraison'; ?>
                    </span>
                </p>

                <?php foreach ($panier_items as $item): ?>
                <?php
                    $prix_unitaire = (!empty($item['panier_prix_unitaire']) && $item['panier_prix_unitaire'] > 0)
                        ? (float) $item['panier_prix_unitaire']
                        : (!empty($item['prix_promotion']) && $item['prix_promotion'] < $item['prix'] ? $item['prix_promotion'] : $item['prix']);
                    $item_img = !empty($item['panier_variante_image']) ? $item['panier_variante_image'] : $item['image_principale'];
                    $item_img_url = upload_image_url_from_src($item_img, 'sm');
                    $item_nom = !empty($item['panier_variante_nom']) ? $item['nom'] . ' - ' . $item['panier_variante_nom'] : $item['nom'];
                ?>
                <div class="commande-summary-item">
                    <div class="commande-summary-item__media">
                        <img src="<?php echo htmlspecialchars($item_img_url, ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo htmlspecialchars($item_nom, ENT_QUOTES, 'UTF-8'); ?>"
                            width="56" height="56" loading="lazy" decoding="async"
                            onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($commande_image_fallback, ENT_QUOTES, 'UTF-8'); ?>';">
                    </div>
                    <div>
                        <h4><?php echo htmlspecialchars($item_nom); ?></h4>
                        <p><?php echo (int) $item['quantite']; ?> × <?php echo number_format($prix_unitaire, 0, ',', ' '); ?> FCFA</p>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="commande-summary-row">
                    <span>Articles</span>
                    <strong><?php echo (int) $nombre_total_articles; ?></strong>
                </div>
                <div class="commande-summary-row">
                    <span>Sous-total</span>
                    <strong><?php echo number_format($panier_total, 0, ',', ' '); ?> FCFA</strong>
                </div>
                <div class="commande-summary-row">
                    <span>Livraison</span>
                    <strong id="summary-livraison">0 FCFA</strong>
                </div>
                <div class="commande-summary-row commande-summary-row--total">
                    <span>Total</span>
                    <strong id="summary-total"><?php echo number_format($panier_total, 0, ',', ' '); ?> FCFA</strong>
                </div>

                <a href="<?php echo public_url('/panier.php'); ?>" class="commande-link-back">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> Retour au panier
                </a>
            </aside>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        window.COMMANDE_CHECKOUT = {
            panierTotal: <?php echo json_encode((float) $panier_total); ?>,
            userLat: <?php echo $user_lat !== null ? json_encode($user_lat) : 'null'; ?>,
            userLng: <?php echo $user_lng !== null ? json_encode($user_lng) : 'null'; ?>,
            userLabel: <?php echo json_encode($user_location_label); ?>,
            hasSavedLocation: <?php echo $user_has_location ? 'true' : 'false'; ?>,
            defaultMode: <?php echo json_encode($default_mode); ?>,
            canSaveLocation: <?php echo $commande_invite ? 'false' : 'true'; ?>,
            saveLocationUrl: <?php echo json_encode(public_url('/api/user/save-location.php')); ?>
        };
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin="" defer></script>
    <?php include __DIR__ . '/includes/auth_intl_tel_scripts.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.initAuthIntlTel === 'function') {
                window.initAuthIntlTel('telephone_livraison');
            }
        });
    </script>
    <script src="<?php echo asset_url('/js/commande-checkout.js'); ?>" defer></script>
</body>

</html>
