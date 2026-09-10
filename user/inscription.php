<?php
/**
 * Page d'inscription utilisateur
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/../includes/session_user.php';
session_start_persistent();
require_once __DIR__ . '/../includes/google_auth_coop.php';
require_once __DIR__ . '/../includes/site_brand.php';
require_once __DIR__ . '/../includes/site_url.php';

// Si l'utilisateur est déjà connecté, rediriger vers le tableau de bord
if (!empty($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0) {
    header('Location: mon-compte.php');
    exit;
}

// Traiter le formulaire
require_once __DIR__ . '/../controllers/controller_users.php';
$result = process_user_inscription();

// Si l'inscription est réussie, rediriger vers la page de connexion
if (isset($result['success']) && $result['success']) {
    $_SESSION['inscription_success'] = $result['message'];
    header('Location: connexion.php');
    exit;
}

$post_nom = isset($_POST['nom']) ? (string) $_POST['nom'] : '';
$post_email = isset($_POST['email']) ? (string) $_POST['email'] : '';
$post_telephone = isset($_POST['telephone']) ? (string) $_POST['telephone'] : '';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../includes/asset_version.php'; ?>
    <?php include __DIR__ . '/../includes/pwa_meta.php'; ?>
    <title>Inscription — <?php echo htmlspecialchars(site_brand_name()); ?></title>
    <link rel="stylesheet" href="<?php echo asset_url('/css/variables.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/auth-social.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/auth-pages.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/auth-connexion.css'); ?>">
    <?php include __DIR__ . '/../includes/auth_intl_tel_head.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="auth-page auth-connexion auth-inscription">
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
            <h1>Créer un compte</h1>
        </header>

        <?php if (isset($result['message']) && !empty($result['message']) && empty($result['success'])): ?>
            <div class="auth-connexion-alert auth-connexion-alert--error" role="alert">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                <span><?php echo $result['message']; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="inscriptionForm">
            <div class="form-group">
                <label for="nom">Nom</label>
                <div class="auth-field">
                    <div class="auth-field__icon" aria-hidden="true">
                        <span><i class="fas fa-user"></i></span>
                    </div>
                    <div class="auth-field__control">
                        <input type="text" id="nom" name="nom" placeholder="Nom" required autocomplete="family-name"
                            value="<?php echo htmlspecialchars($post_nom); ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="email">E-mail (facultatif)</label>
                <div class="auth-field">
                    <div class="auth-field__icon" aria-hidden="true">
                        <span><i class="fas fa-envelope"></i></span>
                    </div>
                    <div class="auth-field__control">
                        <input type="email" id="email" name="email" placeholder="E-mail (facultatif)" autocomplete="email"
                            value="<?php echo htmlspecialchars($post_email); ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="telephone">Téléphone</label>
                <div class="auth-field auth-field--tel">
                    <div class="auth-field__icon" aria-hidden="true">
                        <span><i class="fas fa-phone"></i></span>
                    </div>
                    <div class="auth-field__control">
                        <div class="input-wrapper input-wrapper--intl-tel">
                            <input type="tel" id="telephone" name="telephone" placeholder="77 123 45 67" required
                                autocomplete="tel"
                                value="<?php echo htmlspecialchars($post_telephone); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="pin">Code PIN (6 chiffres)</label>
                <div class="auth-field password-wrapper">
                    <div class="auth-field__icon" aria-hidden="true">
                        <span><i class="fas fa-lock"></i></span>
                    </div>
                    <div class="auth-field__control">
                        <input type="password" id="pin" name="pin" class="pin-input" inputmode="numeric"
                            pattern="[0-9]*" maxlength="6" placeholder="••••••" required autocomplete="new-password">
                        <button type="button" class="password-toggle" onclick="togglePassword('pin', this)"
                            aria-label="Afficher le code PIN">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="pin_confirm">Confirmer le code PIN</label>
                <div class="auth-field password-wrapper">
                    <div class="auth-field__icon" aria-hidden="true">
                        <span><i class="fas fa-shield-halved"></i></span>
                    </div>
                    <div class="auth-field__control">
                        <input type="password" id="pin_confirm" name="pin_confirm" class="pin-input" inputmode="numeric"
                            pattern="[0-9]*" maxlength="6" placeholder="••••••" required autocomplete="new-password">
                        <button type="button" class="password-toggle" onclick="togglePassword('pin_confirm', this)"
                            aria-label="Afficher le code PIN">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit">Continuer</button>
        </form>

        <div class="auth-connexion-divider" role="separator">
            <span>Vous avez déjà un compte ?</span>
        </div>

        <div class="auth-connexion-actions">
            <a href="connexion.php" class="auth-connexion-btn auth-connexion-btn--secondary">Se connecter</a>

            <?php
            $google_auth_type = 'client';
            $google_auth_redirect = '/index.php';
            $google_auth_position = 'bottom';
            $google_auth_label = 'Continuer avec Google';
            $social_auth_show_apple = false;
            include __DIR__ . '/../includes/google_auth_button.php';
            ?>
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
                button.setAttribute('aria-label', 'Masquer le code PIN');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                button.setAttribute('aria-label', 'Afficher le code PIN');
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.pin-input').forEach(function (input) {
                input.addEventListener('input', function () {
                    this.value = this.value.replace(/\D/g, '').slice(0, 6);
                });
            });
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
