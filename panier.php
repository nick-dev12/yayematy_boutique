<?php
require_once __DIR__ . '/includes/session_user.php';
session_start_persistent();

require_once __DIR__ . '/models/model_panier.php';
require_once __DIR__ . '/controllers/controller_panier.php';
require_once __DIR__ . '/includes/panier_invite.php';
require_once __DIR__ . '/includes/guest_checkout.php';
require_once __DIR__ . '/includes/site_brand.php';
require_once __DIR__ . '/includes/site_url.php';
require_once __DIR__ . '/includes/image_optimizer.php';

if (!function_exists('panier_item_unit_price')) {
    function panier_item_unit_price(array $item): float
    {
        if (!empty($item['panier_prix_unitaire']) && $item['panier_prix_unitaire'] > 0) {
            return (float) $item['panier_prix_unitaire'];
        }
        if (!empty($item['prix_promotion']) && $item['prix_promotion'] < $item['prix']) {
            return (float) $item['prix_promotion'];
        }
        return (float) ($item['prix'] ?? 0);
    }
}

$message = '';
$message_type = '';

if (isset($_GET['recommande']) && $_GET['recommande'] == '1') {
    header('Location: ' . public_url('/panier.php'));
    exit;
}

if (isset($_GET['added']) && $_GET['added'] == '1') {
    header('Location: ' . public_url('/panier.php'));
    exit;
}
if (isset($_GET['error'])) {
    $message = htmlspecialchars($_GET['error']);
    $message_type = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $is_ajax = !empty($_POST['ajax']);

    switch ($_POST['action']) {
        case 'update':
            $result = process_update_panier();
            if ($is_ajax) {
                header('Content-Type: application/json; charset=utf-8');
                if (!$result['success']) {
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $panier_id = (int) ($_POST['panier_id'] ?? 0);
                $panier_items = panier_get_items_courant();
                $panier_total = panier_get_total_courant();
                $nombre_total_articles = 0;
                $line_total = 0;
                $quantite_serveur = (int) ($_POST['quantite'] ?? 0);

                foreach ($panier_items as $item) {
                    $nombre_total_articles += (int) $item['quantite'];
                    if ((int) $item['panier_id'] === $panier_id) {
                        $prix_unitaire = panier_item_unit_price($item);
                        $quantite_serveur = (int) $item['quantite'];
                        $line_total = $prix_unitaire * $quantite_serveur;
                    }
                }

                echo json_encode([
                    'success' => true,
                    'message' => $result['message'],
                    'panier_total' => $panier_total,
                    'nombre_total_articles' => $nombre_total_articles,
                    'produits_distincts' => count($panier_items),
                    'line_total' => $line_total,
                    'panier_id' => $panier_id,
                    'quantite' => $quantite_serveur,
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            if ($result['success']) {
                header('Location: ' . public_url('/panier.php'));
                exit;
            }
            $message = $result['message'];
            $message_type = 'error';
            break;
        case 'delete':
            $result = process_delete_from_panier();
            if ($result['success']) {
                header('Location: ' . public_url('/panier.php'));
                exit;
            }
            $message = $result['message'];
            $message_type = 'error';
            break;
    }
}

if (!isset($_SESSION['user_id'])) {
    // Panier invité autorisé
}

$panier_items = panier_get_items_courant();
$panier_total = panier_get_total_courant();
$nombre_total_articles = 0;
foreach ($panier_items as $item) {
    $nombre_total_articles += $item['quantite'];
}

if (file_exists(__DIR__ . '/controllers/controller_commerce_users.php')) {
    require_once __DIR__ . '/controllers/controller_commerce_users.php';
}

$panier_image_fallback = public_url('/image/produit1.jpg');
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/includes/pwa_meta.php'; ?>
    <title>Mon Panier — <?php echo htmlspecialchars(site_brand_name()); ?></title>
    <?php
    require_once __DIR__ . '/includes/asset_version.php';
    require_once __DIR__ . '/includes/head_public_assets.php';
    render_public_head_assets([
        '/css/style.css',
        '/css/a_style.css',
        '/css/responsive-site.css',
        '/css/panier.css',
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
    ?>
</head>

<body class="panier-page">
    <?php include 'nav_bar.php'; ?>

    <div class="panier-hub">
        <header class="panier-hero">
            <div class="panier-hero__intro">
                <h1>Mon <span>panier</span></h1>
                <p><?php echo htmlspecialchars(site_brand_name_market()); ?> · Vérifiez vos articles avant de commander.</p>
            </div>
            <?php if (!empty($panier_items)): ?>
            <div class="panier-hero__count">
                <span class="panier-hero__count-value" id="panier-articles-count"><?php echo (int) $nombre_total_articles; ?></span>
                <span class="panier-hero__count-label">article<?php echo $nombre_total_articles > 1 ? 's' : ''; ?></span>
            </div>
            <?php endif; ?>
        </header>

        <?php if ($message && $message_type === 'error'): ?>
        <div class="panier-flash panier-flash--error" role="alert">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
        <?php endif; ?>

        <?php if (!isset($_SESSION['user_id']) && guest_checkout_has_info()): ?>
        <?php $guest_info = guest_checkout_get_info(); ?>
        <p class="panier-invite-notice">
            Commande en tant qu'invité pour <strong><?php echo htmlspecialchars($guest_info['nom']); ?></strong>
            (<?php echo htmlspecialchars($guest_info['telephone']); ?>).
            <a href="<?php echo public_url('/user/connexion.php?redirect=panier'); ?>">Se connecter</a> pour enregistrer votre compte.
        </p>
        <?php endif; ?>

        <?php if (empty($panier_items)): ?>
        <div class="panier-empty">
            <div class="panier-empty__icon"><i class="fas fa-shopping-cart" aria-hidden="true"></i></div>
            <p>Votre panier est vide</p>
            <a href="<?php echo public_url('/index.php'); ?>" class="panier-btn panier-btn--primary">Continuer mes achats</a>
        </div>
        <?php else: ?>
        <div class="panier-layout">
            <div class="panier-list">
                <?php foreach ($panier_items as $item): ?>
                <?php
                    $prix_unitaire = panier_item_unit_price($item);
                    $prix_total_item = $prix_unitaire * $item['quantite'];
                    $item_img = !empty($item['panier_variante_image']) ? $item['panier_variante_image'] : $item['image_principale'];
                    $item_img_url = upload_image_url_from_src($item_img, 'sm');
                    $item_nom = !empty($item['panier_variante_nom'])
                        ? $item['nom'] . ' → ' . $item['panier_variante_nom']
                        : $item['nom'];
                    $item_detail_url = public_url('/produit.php?id=' . (int) ($item['id'] ?? $item['produit_id'] ?? 0));
                ?>
                <article class="panier-card" data-item-id="<?php echo (int) $item['panier_id']; ?>"
                    data-unit-price="<?php echo htmlspecialchars((string) $prix_unitaire, ENT_QUOTES, 'UTF-8'); ?>">
                    <a href="<?php echo htmlspecialchars($item_detail_url, ENT_QUOTES, 'UTF-8'); ?>" class="panier-card__media" aria-label="Voir <?php echo htmlspecialchars($item_nom, ENT_QUOTES, 'UTF-8'); ?>">
                        <img src="<?php echo htmlspecialchars($item_img_url, ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo htmlspecialchars($item_nom, ENT_QUOTES, 'UTF-8'); ?>"
                            class="panier-card__img" width="110" height="110" loading="eager" decoding="async"
                            onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($panier_image_fallback, ENT_QUOTES, 'UTF-8'); ?>';">
                    </a>
                    <div class="panier-card__body">
                        <div class="panier-card__head">
                            <h3 class="panier-card__title"><?php echo htmlspecialchars($item_nom); ?></h3>
                            <form method="POST" action="" class="panier-delete-form"
                                onsubmit="return confirm('Retirer ce produit du panier ?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="panier_id" value="<?php echo (int) $item['panier_id']; ?>">
                                <button type="submit" class="panier-btn panier-btn--danger panier-btn--icon" aria-label="Retirer <?php echo htmlspecialchars($item_nom, ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="fas fa-trash" aria-hidden="true"></i>
                                    <span class="panier-btn__label">Retirer</span>
                                </button>
                            </form>
                        </div>
                        <p class="panier-card__cat"><?php echo htmlspecialchars($item['categorie_nom']); ?></p>
                        <?php if (!empty($item['panier_couleur']) || !empty($item['panier_poids']) || !empty($item['panier_taille'])): ?>
                        <p class="panier-card__opts">
                            <?php
                            $opts = [];
                            if (!empty(trim($item['panier_couleur'] ?? ''))) {
                                $hex = trim($item['panier_couleur']);
                                $opts[] = preg_match('/^#[0-9A-Fa-f]{6}$/', $hex)
                                    ? '<span class="opt-swatch" style="background:' . htmlspecialchars($hex) . '"></span> ' . htmlspecialchars($hex)
                                    : 'Couleur: ' . htmlspecialchars($hex);
                            }
                            if (!empty(trim($item['panier_poids'] ?? ''))) {
                                $opts[] = 'Poids: ' . htmlspecialchars($item['panier_poids']);
                            }
                            if (!empty(trim($item['panier_taille'] ?? ''))) {
                                $opts[] = 'Taille: ' . htmlspecialchars($item['panier_taille']);
                            }
                            echo implode(' · ', $opts);
                            ?>
                        </p>
                        <?php endif; ?>
                        <div class="panier-card__price">
                            <?php echo number_format($prix_unitaire, 0, ',', ' '); ?> FCFA
                            <?php if (empty($item['panier_prix_unitaire']) && !empty($item['prix_promotion']) && $item['prix_promotion'] < $item['prix']): ?>
                            <span class="panier-card__price-old"><?php echo number_format($item['prix'], 0, ',', ' '); ?> FCFA</span>
                            <span class="panier-badge-promo">PROMO</span>
                            <?php endif; ?>
                        </div>
                        <div class="panier-card__actions">
                            <form method="POST" action="" class="panier-update-form" data-panier-update>
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="panier_id" value="<?php echo (int) $item['panier_id']; ?>">
                                <div class="panier-qty">
                                    <button type="button" class="decrease-btn" aria-label="Diminuer">−</button>
                                    <input type="number" name="quantite" class="quantite-input"
                                        value="<?php echo (int) $item['quantite']; ?>" min="1"
                                        max="<?php echo (int) $item['stock']; ?>" required
                                        aria-label="Quantité">
                                    <button type="button" class="increase-btn" aria-label="Augmenter">+</button>
                                </div>
                            </form>
                            <div class="panier-card__line-total notranslate" translate="no" data-line-total>
                                <span class="price-amount"><?php echo number_format($prix_total_item, 0, ',', ' '); ?></span>
                                <span class="price-currency">FCFA</span>
                            </div>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>

            <aside class="panier-summary">
                <div class="panier-summary__row panier-summary__row--total">
                    <span>Total</span>
                    <strong id="panier-summary-total" class="notranslate" translate="no"><?php echo number_format($panier_total, 0, ',', ' '); ?> FCFA</strong>
                </div>
                <div class="panier-summary__cta">
                    <a href="<?php echo public_url('/commande.php'); ?>" class="panier-btn panier-btn--primary">
                        <i class="fas fa-shopping-bag" aria-hidden="true"></i> Passer la commande
                    </a>
                    <a href="<?php echo public_url('/index.php'); ?>" class="panier-btn panier-btn--ghost">Continuer mes achats</a>
                </div>
            </aside>
        </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>

    <script src="<?php echo asset_url('/js/panier.js'); ?>"></script>
</body>

</html>
