<?php
/**
 * Page de connexion utilisateur
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/../includes/session_user.php';
session_start_persistent();
require_once __DIR__ . '/../includes/google_auth_coop.php';
require_once __DIR__ . '/../includes/site_brand.php';
require_once __DIR__ . '/../includes/site_url.php';

// Redirection après connexion (page demandée ou index)
$redirect_after = isset($_POST['redirect']) ? trim($_POST['redirect']) : (isset($_GET['redirect']) ? trim($_GET['redirect']) : '');
$redirect_url = normalize_redirect_target($redirect_after);

// Si l'admin est déjà connecté, rediriger vers l'espace admin
if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_email'])) {
    redirect_to('/admin/dashboard.php');
}

// Si l'utilisateur est déjà connecté, rediriger
if (!empty($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0) {
    header('Location: ' . $redirect_url);
    exit;
}

// Traiter le formulaire de connexion (admin + user)
require_once __DIR__ . '/../controllers/controller_users.php';
$result = process_unified_login();

// Connexion admin : session + redirection vers l'espace admin
if (isset($result['success']) && $result['success'] && $result['type'] === 'admin' && $result['admin']) {
    session_regenerate_persistent();
    $_SESSION['admin_id'] = $result['admin']['id'];
    $_SESSION['admin_nom'] = $result['admin']['nom'];
    $_SESSION['admin_prenom'] = $result['admin']['prenom'];
    $_SESSION['admin_email'] = $result['admin']['email'];
    $_SESSION['admin_statut'] = $result['admin']['statut'];
    $_SESSION['admin_role'] = $result['admin']['role'] ?? 'admin';

    redirect_to('/admin/dashboard.php');
}

// Connexion utilisateur : session + redirection
if (isset($result['success']) && $result['success'] && $result['type'] === 'user' && $result['user']) {
    session_regenerate_persistent();
    $_SESSION['user_id'] = $result['user']['id'];
    $_SESSION['user_nom'] = $result['user']['nom'];
    $_SESSION['user_prenom'] = $result['user']['prenom'];
    $_SESSION['user_email'] = $result['user']['email'];
    $_SESSION['user_telephone'] = $result['user']['telephone'];
    $_SESSION['user_statut'] = $result['user']['statut'];

    if (file_exists(__DIR__ . '/../includes/panier_invite.php')) {
        require_once __DIR__ . '/../includes/panier_invite.php';
        panier_fusionner_invite_apres_connexion((int) $result['user']['id']);
    }

    header('Location: ' . $redirect_url);
    exit;
}

// Afficher le message de succès d'inscription si présent
$inscription_success = '';
if (isset($_SESSION['inscription_success'])) {
    $inscription_success = $_SESSION['inscription_success'];
    unset($_SESSION['inscription_success']);
}

$active_login_mode = (isset($_POST['login_mode']) && (string) $_POST['login_mode'] === 'email') ? 'email' : 'phone';
$post_telephone = isset($_POST['telephone']) ? (string) $_POST['telephone'] : '';
$post_pin = isset($_POST['pin']) ? (string) $_POST['pin'] : '';
$post_email = isset($_POST['email']) ? (string) $_POST['email'] : '';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../includes/asset_version.php'; ?>
    <?php include __DIR__ . '/../includes/pwa_meta.php'; ?>
    <title>Connexion — <?php echo htmlspecialchars(site_brand_name()); ?></title>
    <link rel="stylesheet" href="<?php echo asset_url('/css/variables.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/auth-social.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/auth-pages.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/auth-connexion.css'); ?>">
    <?php include __DIR__ . '/../includes/auth_intl_tel_head.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="auth-page auth-connexion auth-page--<?php echo $active_login_mode === 'phone' ? 'phone' : 'email'; ?>">
    <div class="auth-connexion-shell">
        <a href="<?php echo public_url('/index.php'); ?>" class="auth-connexion-back" aria-label="Retour à l'accueil">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            <span>Retour</span>
        </a>

        <div class="auth-connexion-brand">
            <a href="<?php echo public_url('/index.php'); ?>" aria-label="<?php echo htmlspecialchars(site_brand_name()); ?>">
                <span class="auth-connexion-brand__mark">
                    <?php
                    $brand_logo_class = 'auth-connexion-logo';
                    include __DIR__ . '/../includes/brand_logo.php';
                    ?>
                </span>
            </a>
        </div>

        <header class="auth-connexion-intro">
            <h1>Connexion</h1>
        </header>

        <?php if (!empty($inscription_success)): ?>
            <div class="auth-connexion-alert auth-connexion-alert--success" role="status">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span><?php echo htmlspecialchars($inscription_success); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($result['message']) && !empty($result['message']) && empty($result['success'])): ?>
            <div class="auth-connexion-alert auth-connexion-alert--error" role="alert">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                <span><?php echo $result['message']; ?></span>
            </div>
        <?php endif; ?>

        <div class="login-mode-tabs" role="tablist" aria-label="Mode de connexion">
            <button type="button" role="tab" id="tab-phone" aria-controls="panel-phone"
                aria-selected="<?php echo $active_login_mode === 'phone' ? 'true' : 'false'; ?>"
                tabindex="<?php echo $active_login_mode === 'phone' ? '0' : '-1'; ?>">
                <i class="fas fa-phone" aria-hidden="true"></i>
                <span class="tab-label-long">Téléphone</span>
            </button>
            <button type="button" role="tab" id="tab-email" aria-controls="panel-email"
                aria-selected="<?php echo $active_login_mode === 'email' ? 'true' : 'false'; ?>"
                tabindex="<?php echo $active_login_mode === 'email' ? '0' : '-1'; ?>">
                <i class="fas fa-envelope" aria-hidden="true"></i>
                <span class="tab-label-long">Email</span>
            </button>
        </div>

        <div id="panel-phone" class="login-panel" role="tabpanel" aria-labelledby="tab-phone"
            <?php echo $active_login_mode !== 'phone' ? 'hidden' : ''; ?>>
            <form method="POST" action="" id="loginFormPhone">
                <input type="hidden" name="login_mode" value="phone">
                <?php if (!empty($redirect_after)): ?>
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect_after); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="telephone">Téléphone</label>
                    <div class="auth-field auth-field--tel">
                        <div class="auth-field__icon" aria-hidden="true">
                            <span><i class="fas fa-phone"></i></span>
                        </div>
                        <div class="auth-field__control">
                            <div class="input-wrapper input-wrapper--intl-tel">
                                <input type="tel" id="telephone" name="telephone" placeholder="77 123 45 67"
                                    autocomplete="tel"
                                    value="<?php echo htmlspecialchars($post_telephone); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="pin">Mot de passe</label>
                    <div class="auth-field password-wrapper">
                        <div class="auth-field__icon" aria-hidden="true">
                            <span><i class="fas fa-lock"></i></span>
                        </div>
                        <div class="auth-field__control">
                            <input type="password" id="pin" name="pin" placeholder="Mot de passe"
                                autocomplete="current-password"
                                value="<?php echo htmlspecialchars($post_pin); ?>">
                            <button type="button" class="password-toggle" onclick="togglePassword('pin', this)"
                                aria-label="Afficher le mot de passe">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="accepte_conditions_phone" name="accepte_conditions_phone" value="1" required
                        <?php echo (isset($_POST['accepte_conditions_phone']) && $_POST['accepte_conditions_phone'] === '1') ? 'checked' : ''; ?>>
                    <label for="accepte_conditions_phone">
                        J'accepte les <a href="<?php echo public_url('/conditions-utilisation.php'); ?>" target="_blank" rel="noopener">conditions d'utilisation</a>
                    </label>
                </div>

                <button type="submit" class="btn-submit">Continuer</button>
            </form>
        </div>

        <div id="panel-email" class="login-panel" role="tabpanel" aria-labelledby="tab-email"
            <?php echo $active_login_mode !== 'email' ? 'hidden' : ''; ?>>
            <form method="POST" action="" id="loginFormEmail">
                <input type="hidden" name="login_mode" value="email">
                <?php if (!empty($redirect_after)): ?>
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect_after); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="email">E-mail</label>
                    <div class="auth-field">
                        <div class="auth-field__icon" aria-hidden="true">
                            <span><i class="fas fa-envelope"></i></span>
                        </div>
                        <div class="auth-field__control">
                            <input type="email" id="email" name="email" placeholder="E-mail" autocomplete="email"
                                value="<?php echo htmlspecialchars($post_email); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <div class="auth-field password-wrapper">
                        <div class="auth-field__icon" aria-hidden="true">
                            <span><i class="fas fa-lock"></i></span>
                        </div>
                        <div class="auth-field__control">
                            <input type="password" id="password" name="password" placeholder="Mot de passe"
                                autocomplete="current-password">
                            <button type="button" class="password-toggle" onclick="togglePassword('password', this)"
                                aria-label="Afficher le mot de passe">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <p class="auth-connexion-forgot">
                    <a href="mot-de-passe-oublie.php">Mot de passe oublié ?</a>
                </p>

                <div class="checkbox-group">
                    <input type="checkbox" id="accepte_conditions" name="accepte_conditions" value="1" required>
                    <label for="accepte_conditions">
                        J'accepte les <a href="<?php echo public_url('/conditions-utilisation.php'); ?>" target="_blank" rel="noopener">conditions d'utilisation</a>
                    </label>
                </div>

                <button type="submit" class="btn-submit">Continuer</button>
            </form>
        </div>

        <div class="auth-connexion-actions">
            <?php
            $google_auth_type = 'auto';
            $google_auth_redirect = $redirect_url;
            $google_auth_position = 'bottom';
            $google_auth_label = 'Continuer avec Google';
            $social_auth_show_apple = false;
            include __DIR__ . '/../includes/google_auth_button.php';
            ?>

            <a href="inscription.php" class="auth-connexion-btn auth-connexion-btn--secondary">Créer un compte</a>
        </div>

        <p class="auth-connexion-legal">
            En cliquant sur « Continuer », j'ai lu et j'accepte les
            <a href="<?php echo public_url('/conditions-utilisation.php'); ?>" target="_blank" rel="noopener">conditions d'utilisation</a>
            et la
            <a href="<?php echo public_url('/politique-confidentialite.php'); ?>" target="_blank" rel="noopener">politique de confidentialité</a>.
        </p>
    </div>

    <script>
        function togglePassword(inputId, button) {
            var input = document.getElementById(inputId);
            var icon = button.querySelector('i');
            if (!input || !icon) return;
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                button.setAttribute('aria-label', 'Masquer le mot de passe');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                button.setAttribute('aria-label', 'Afficher le mot de passe');
            }
        }

        (function () {
            var tabEmail = document.getElementById('tab-email');
            var tabPhone = document.getElementById('tab-phone');
            var panelEmail = document.getElementById('panel-email');
            var panelPhone = document.getElementById('panel-phone');
            if (!tabEmail || !tabPhone || !panelEmail || !panelPhone) return;

            function showMode(mode) {
                var isPhone = mode === 'phone';
                var root = document.querySelector('.auth-page');
                if (root) {
                    root.classList.remove('auth-page--email', 'auth-page--phone');
                    root.classList.add(isPhone ? 'auth-page--phone' : 'auth-page--email');
                }
                panelPhone.hidden = !isPhone;
                panelEmail.hidden = isPhone;
                tabPhone.setAttribute('aria-selected', isPhone ? 'true' : 'false');
                tabEmail.setAttribute('aria-selected', isPhone ? 'false' : 'true');
                tabPhone.tabIndex = isPhone ? 0 : -1;
                tabEmail.tabIndex = isPhone ? -1 : 0;
            }

            tabPhone.addEventListener('click', function () { showMode('phone'); });
            tabEmail.addEventListener('click', function () { showMode('email'); });
        })();

        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.initAuthIntlTel === 'function') {
                window.initAuthIntlTel('telephone');
            }
        });
    </script>
    <?php include __DIR__ . '/../includes/auth_intl_tel_scripts.php'; ?>
    <script src="<?php echo asset_url('/js/auth-geo-capture.js'); ?>"></script>
    <?php include __DIR__ . '/../includes/google_auth_scripts.php'; ?>
    <?php include __DIR__ . '/../includes/social_floating.php'; ?>
</body>

</html>
