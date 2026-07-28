<?php
require_once __DIR__ . '/includes/session_user.php';
session_start_persistent();

require_once __DIR__ . '/models/model_panier.php';
require_once __DIR__ . '/controllers/controller_panier.php';
require_once __DIR__ . '/includes/panier_invite.php';
require_once __DIR__ . '/includes/guest_checkout.php';
require_once __DIR__ . '/includes/site_brand.php';

$message = '';
$message_type = '';

if (isset($_GET['recommande']) && $_GET['recommande'] == '1') {
    $count = isset($_GET['count']) ? (int) $_GET['count'] : 0;
    $message = $count > 0
        ? $count . ' produit(s) de votre commande annulée ont été ajoutés au panier avec succès !'
        : 'Les produits ont été ajoutés au panier avec succès !';
    $message_type = 'success';
}

if (isset($_GET['added']) && $_GET['added'] == '1') {
    $message = 'Produit ajouté au panier avec succès.';
    $message_type = 'success';
}
if (isset($_GET['error'])) {
    $message = htmlspecialchars($_GET['error']);
    $message_type = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update':
            $result = process_update_panier();
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            break;
        case 'delete':
            $result = process_delete_from_panier();
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
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
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/includes/asset_version.php'; ?>
    <?php include __DIR__ . '/includes/pwa_meta.php'; ?>
    <title>Mon Panier — <?php echo htmlspecialchars(site_brand_name()); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/a_style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/panier.css<?php echo asset_version_query(); ?>">
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
                <span class="panier-hero__count-value"><?php echo (int) $nombre_total_articles; ?></span>
                <span class="panier-hero__count-label">article<?php echo $nombre_total_articles > 1 ? 's' : ''; ?></span>
            </div>
            <?php endif; ?>
        </header>

        <?php if ($message): ?>
        <div class="panier-flash panier-flash--<?php echo htmlspecialchars($message_type); ?>" role="status">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>" aria-hidden="true"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
        <?php endif; ?>

        <?php if (!isset($_SESSION['user_id']) && guest_checkout_has_info()): ?>
        <?php $guest_info = guest_checkout_get_info(); ?>
        <p class="panier-invite-notice">
            Commande en tant qu'invité pour <strong><?php echo htmlspecialchars($guest_info['nom']); ?></strong>
            (<?php echo htmlspecialchars($guest_info['telephone']); ?>).
            <a href="/user/connexion.php?redirect=panier">Se connecter</a> pour enregistrer votre compte.
        </p>
        <?php endif; ?>

        <?php if (empty($panier_items)): ?>
        <div class="panier-empty">
            <div class="panier-empty__icon"><i class="fas fa-shopping-cart" aria-hidden="true"></i></div>
            <p>Votre panier est vide</p>
            <a href="/index.php" class="panier-btn panier-btn--primary">Continuer mes achats</a>
        </div>
        <?php else: ?>
        <div class="panier-layout">
            <div class="panier-list">
                <?php foreach ($panier_items as $item): ?>
                <?php
                    $prix_unitaire = (!empty($item['panier_prix_unitaire']) && $item['panier_prix_unitaire'] > 0)
                        ? (float) $item['panier_prix_unitaire']
                        : (!empty($item['prix_promotion']) && $item['prix_promotion'] < $item['prix'] ? $item['prix_promotion'] : $item['prix']);
                    $prix_total_item = $prix_unitaire * $item['quantite'];
                    $item_img = !empty($item['panier_variante_image']) ? $item['panier_variante_image'] : $item['image_principale'];
                    $item_nom = !empty($item['panier_variante_nom'])
                        ? $item['nom'] . ' → ' . $item['panier_variante_nom']
                        : $item['nom'];
                ?>
                <article class="panier-card" data-item-id="<?php echo (int) $item['panier_id']; ?>">
                    <img src="/upload/<?php echo htmlspecialchars($item_img); ?>"
                        alt="<?php echo htmlspecialchars($item_nom); ?>" class="panier-card__img"
                        onerror="this.src='/image/produit1.jpg'">
                    <div class="panier-card__body">
                        <h3 class="panier-card__title"><?php echo htmlspecialchars($item_nom); ?></h3>
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
                            <form method="POST" action="" class="panier-update-form">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="panier_id" value="<?php echo (int) $item['panier_id']; ?>">
                                <div class="panier-qty">
                                    <button type="button" class="decrease-btn" aria-label="Diminuer">−</button>
                                    <input type="number" name="quantite" class="quantite-input"
                                        value="<?php echo (int) $item['quantite']; ?>" min="1"
                                        max="<?php echo (int) $item['stock']; ?>" required>
                                    <button type="button" class="increase-btn" aria-label="Augmenter">+</button>
                                </div>
                                <button type="submit" class="panier-btn panier-btn--ghost">
                                    <i class="fas fa-sync-alt" aria-hidden="true"></i> Mettre à jour
                                </button>
                            </form>
                            <form method="POST" action=""
                                onsubmit="return confirm('Retirer ce produit du panier ?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="panier_id" value="<?php echo (int) $item['panier_id']; ?>">
                                <button type="submit" class="panier-btn panier-btn--danger">
                                    <i class="fas fa-trash" aria-hidden="true"></i> Retirer
                                </button>
                            </form>
                            <div class="panier-card__line-total">
                                <?php echo number_format($prix_total_item, 0, ',', ' '); ?> FCFA
                            </div>
                        </div>
                        <p class="panier-card__stock">
                            Stock : <?php echo (int) $item['stock']; ?> <?php echo htmlspecialchars($item['unite']); ?>
                        </p>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>

            <aside class="panier-summary">
                <h2><i class="fas fa-receipt" aria-hidden="true"></i> Résumé</h2>
                <div class="panier-summary__row">
                    <span>Articles</span>
                    <strong><?php echo (int) $nombre_total_articles; ?></strong>
                </div>
                <div class="panier-summary__row">
                    <span>Produits distincts</span>
                    <strong><?php echo count($panier_items); ?></strong>
                </div>
                <div class="panier-summary__row panier-summary__row--total">
                    <span>Total</span>
                    <strong><?php echo number_format($panier_total, 0, ',', ' '); ?> FCFA</strong>
                </div>
                <div class="panier-summary__cta">
                    <a href="/commande.php" class="panier-btn panier-btn--primary">
                        <i class="fas fa-shopping-bag" aria-hidden="true"></i> Passer la commande
                    </a>
                    <a href="/index.php" class="panier-btn panier-btn--ghost">Continuer mes achats</a>
                </div>
            </aside>
        </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        document.querySelectorAll('.increase-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = this.parentElement.querySelector('.quantite-input');
                var max = parseInt(input.getAttribute('max'), 10);
                var value = parseInt(input.value, 10) || 1;
                if (value < max) input.value = value + 1;
            });
        });
        document.querySelectorAll('.decrease-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = this.parentElement.querySelector('.quantite-input');
                var value = parseInt(input.value, 10) || 1;
                if (value > 1) input.value = value - 1;
            });
        });
    </script>
</body>

</html>
