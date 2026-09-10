<?php
/**
 * Page de profil utilisateur - Modification des informations
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/../includes/session_user.php';
session_start_persistent();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header('Location: connexion.php');
    exit;
}

require_once __DIR__ . '/../models/model_users.php';
require_once __DIR__ . '/../includes/site_brand.php';

$user = get_user_by_id($_SESSION['user_id']);

if (!$user) {
    session_destroy();
    header('Location: connexion.php');
    exit;
}

$success_message = '';
$error_message = '';

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_profil'])) {
    $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $telephone_raw = isset($_POST['telephone']) ? trim($_POST['telephone']) : '';
    $telephone_digits = users_normalize_phone_digits($telephone_raw);

    $errors = [];

    if ($nom === '') {
        $errors[] = 'Le nom est obligatoire.';
    } elseif (strlen($nom) < 2) {
        $errors[] = 'Le nom doit contenir au moins 2 caractères.';
    }

    if ($email !== '') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'email n\'est pas valide.';
        } else {
            $existing_user = get_user_by_email($email);
            if ($existing_user && (int) $existing_user['id'] !== (int) $_SESSION['user_id']) {
                $errors[] = 'Cet email est déjà utilisé par un autre compte.';
            }
        }
    }

    if ($telephone_raw === '' || $telephone_digits === '') {
        $errors[] = 'Le téléphone est obligatoire.';
    } elseif (strlen($telephone_digits) < 8) {
        $errors[] = 'Le numéro de téléphone semble incomplet.';
    } else {
        $existing_tel = get_user_by_telephone($telephone_digits);
        if ($existing_tel && (int) $existing_tel['id'] !== (int) $_SESSION['user_id']) {
            $errors[] = 'Ce numéro de téléphone est déjà utilisé par un autre compte.';
        }
    }

    if (empty($errors)) {
        $data = [
            'nom' => $nom,
            'prenom' => $user['prenom'] ?? '',
            'email' => $email !== '' ? $email : null,
            'telephone' => $telephone_digits,
        ];

        if (update_user($_SESSION['user_id'], $data)) {
            $_SESSION['user_nom'] = $nom;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_telephone'] = $telephone_digits;

            $_SESSION['success_message'] = 'Vos informations personnelles ont été mises à jour avec succès !';
            header('Location: profil.php');
            exit;
        }

        $error_message = 'Une erreur est survenue lors de la mise à jour. Veuillez réessayer.';
    } else {
        $error_message = implode("\n", $errors);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_mot_de_passe'])) {
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';

    $errors = [];

    if ($current_password === '') {
        $errors[] = 'Le mot de passe actuel est obligatoire.';
    } elseif (!password_verify($current_password, $user['password'])) {
        $errors[] = 'Le mot de passe actuel est incorrect.';
    }

    if ($password === '') {
        $errors[] = 'Le nouveau mot de passe est obligatoire.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
    } elseif ($password === $current_password) {
        $errors[] = 'Le nouveau mot de passe doit être différent de l\'ancien.';
    } elseif ($password !== $password_confirm) {
        $errors[] = 'Les nouveaux mots de passe ne correspondent pas.';
    }

    if (empty($errors)) {
        require_once __DIR__ . '/../conn/conn.php';
        global $db;

        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare('UPDATE users SET password = :password WHERE id = :id');

        if ($stmt->execute([
            'id' => $_SESSION['user_id'],
            'password' => $password_hash,
        ])) {
            $_SESSION['success_message'] = 'Votre mot de passe a été modifié avec succès !';
            header('Location: profil.php');
            exit;
        }

        $error_message = 'Une erreur est survenue lors de la modification du mot de passe. Veuillez réessayer.';
    } else {
        $error_message = implode("\n", $errors);
    }
}

$user = get_user_by_id($_SESSION['user_id']);

$statut_label = ucfirst($user['statut']);
$annee_inscription = date('Y', strtotime($user['date_creation']));
$date_inscription = date('d/m/Y', strtotime($user['date_creation']));
$statut_class = $user['statut'] === 'actif' ? 'actif' : 'inactif';

$user_prenom = trim((string) ($user['prenom'] ?? ''));
$user_nom = trim((string) ($user['nom'] ?? ''));
$user_initials = strtoupper(
    mb_substr($user_prenom, 0, 1, 'UTF-8') . mb_substr($user_nom, 0, 1, 'UTF-8')
);
if ($user_initials === '') {
    $user_initials = 'YM';
}

$display_name = trim($user_prenom . ' ' . $user_nom);
if ($display_name === '') {
    $display_name = $user_nom !== '' ? $user_nom : 'Mon profil';
}

$user_email_display = trim((string) ($user['email'] ?? ''));
$user_telephone_display = trim((string) ($user['telephone'] ?? ''));
if (isset($_POST['telephone'])) {
    $telephone_form_value = trim((string) $_POST['telephone']);
} else {
    $tel_digits = users_normalize_phone_digits($user_telephone_display);
    $telephone_form_value = $tel_digits !== '' ? ('+' . $tel_digits) : '';
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../includes/asset_version.php'; ?>
    <?php include __DIR__ . '/../includes/pwa_meta.php'; ?>
    <title>Mon Profil — <?php echo htmlspecialchars(site_brand_name()); ?></title>
    <link rel="stylesheet" href="<?php echo asset_url('/css/variables.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo asset_url('/css/user-dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/user-mon-compte.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/auth-pages.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/user-profil.css'); ?>">
    <?php include __DIR__ . '/../includes/auth_intl_tel_head.php'; ?>
</head>

<body class="user-page-profil">
    <?php include 'includes/user_nav.php'; ?>

    <div class="account-hub">

        <header class="account-hero account-hero--profil">
            <div class="account-hero__inner">
                <div class="account-hero__profile">
                    <span class="account-hero__avatar" aria-hidden="true"><?php echo htmlspecialchars($user_initials); ?></span>
                    <div class="account-hero__intro">
                        <p class="account-hero__eyebrow">
                            <i class="fa-solid fa-user-gear" aria-hidden="true"></i>
                            Profil · <?php echo htmlspecialchars(site_brand_name_market()); ?>
                        </p>
                        <h1 class="account-hero__title">Mon <span>profil</span></h1>
                        <p class="account-hero__subtitle">
                            <?php echo htmlspecialchars($display_name); ?>
                            <?php if ($user_telephone_display !== ''): ?>
                                · <?php echo htmlspecialchars($user_telephone_display); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <div class="account-hero__meta">
                    <span class="account-hero__count profil-statut profil-statut--<?php echo htmlspecialchars($statut_class); ?>">
                        <?php echo htmlspecialchars($statut_label); ?>
                    </span>
                    <span class="account-hero__count-label">statut</span>
                </div>
            </div>
            <div class="account-hero__actions">
                <a href="mon-compte.php" class="account-btn account-btn--primary">
                    <i class="fas fa-home" aria-hidden="true"></i>
                    Tableau de bord
                </a>
                <a href="mes-commandes.php" class="account-btn account-btn--outline">
                    <i class="fas fa-shopping-bag" aria-hidden="true"></i>
                    Mes commandes
                </a>
                <a href="<?php echo public_url('/index.php'); ?>" class="account-btn account-btn--ghost">
                    <i class="fas fa-store" aria-hidden="true"></i>
                    Boutique
                </a>
            </div>
        </header>

        <?php if ($success_message): ?>
        <div class="profil-flash profil-flash--success" role="status">
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            <span><?php echo htmlspecialchars($success_message); ?></span>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="profil-flash profil-flash--error" role="alert">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <span><?php echo nl2br(htmlspecialchars($error_message)); ?></span>
        </div>
        <?php endif; ?>

        <section class="account-stats profil-stats" aria-label="Résumé du compte">
            <div class="account-stat profil-stat-card">
                <span class="account-stat__icon"><i class="fas fa-shield-alt" aria-hidden="true"></i></span>
                <span class="account-stat__value profil-statut-value profil-statut-value--<?php echo htmlspecialchars($statut_class); ?>">
                    <?php echo htmlspecialchars($statut_label); ?>
                </span>
                <span class="account-stat__label">Statut du compte</span>
            </div>
            <div class="account-stat profil-stat-card">
                <span class="account-stat__icon"><i class="fas fa-calendar-alt" aria-hidden="true"></i></span>
                <span class="account-stat__value"><?php echo htmlspecialchars($annee_inscription); ?></span>
                <span class="account-stat__label">Membre depuis</span>
            </div>
        </section>

        <section class="account-block profil-info-block" aria-labelledby="profil-info-title">
            <div class="account-block__head">
                <div>
                    <h2 id="profil-info-title"><i class="fas fa-info-circle" aria-hidden="true"></i> Informations du compte</h2>
                    <p>Consultez les détails liés à votre inscription.</p>
                </div>
            </div>
            <dl class="profil-info-list">
                <div class="profil-info-row">
                    <dt>Date d'inscription</dt>
                    <dd><?php echo htmlspecialchars($date_inscription); ?></dd>
                </div>
                <div class="profil-info-row">
                    <dt>Statut</dt>
                    <dd><span class="profil-badge profil-badge--<?php echo htmlspecialchars($statut_class); ?>"><?php echo htmlspecialchars($statut_label); ?></span></dd>
                </div>
                <?php if ($user_email_display !== ''): ?>
                <div class="profil-info-row">
                    <dt>Email enregistré</dt>
                    <dd><?php echo htmlspecialchars($user_email_display); ?></dd>
                </div>
                <?php endif; ?>
            </dl>
        </section>

        <section class="account-block" aria-labelledby="profil-personal-title">
            <div class="account-block__head">
                <div>
                    <h2 id="profil-personal-title"><i class="fas fa-user" aria-hidden="true"></i> Informations personnelles</h2>
                    <p>Mettez à jour votre nom, votre téléphone et, si vous le souhaitez, votre email.</p>
                </div>
            </div>

            <form method="POST" action="" class="profil-form" id="profilFormInfo">
                <div class="profil-field">
                    <label for="nom">Nom <span class="profil-required">*</span></label>
                    <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" required autocomplete="family-name">
                </div>

                <div class="profil-field">
                    <label for="email">Email <span class="profil-optional">(facultatif)</span></label>
                    <input type="email" id="email" name="email"
                        value="<?php echo htmlspecialchars($user_email_display); ?>"
                        placeholder="votre@email.com" autocomplete="email">
                </div>

                <div class="profil-field">
                    <label for="telephone"><i class="fas fa-phone" aria-hidden="true"></i> Téléphone <span class="profil-required">*</span></label>
                    <div class="input-wrapper input-wrapper--intl-tel">
                        <input type="tel" id="telephone" name="telephone" placeholder="77 123 45 67" autocomplete="tel" required value=""
                            data-initial-phone="<?php echo htmlspecialchars($telephone_form_value); ?>">
                    </div>
                </div>

                <div class="profil-form-actions">
                    <button type="submit" name="modifier_profil" class="account-btn account-btn--primary">
                        <i class="fas fa-save" aria-hidden="true"></i>
                        Enregistrer les modifications
                    </button>
                    <a href="mon-compte.php" class="account-btn account-btn--outline">
                        <i class="fas fa-times" aria-hidden="true"></i>
                        Annuler
                    </a>
                </div>
            </form>
        </section>

        <section class="account-block account-block--security" aria-labelledby="profil-security-title">
            <div class="account-block__head">
                <div>
                    <h2 id="profil-security-title"><i class="fas fa-lock" aria-hidden="true"></i> Sécurité</h2>
                    <p>Modifiez votre mot de passe en confirmant l'ancien.</p>
                </div>
            </div>

            <form method="POST" action="" class="profil-form" id="profilFormPassword">
                <div class="profil-field">
                    <label for="current_password">Mot de passe actuel <span class="profil-required">*</span></label>
                    <input type="password" id="current_password" name="current_password"
                        placeholder="Entrez votre mot de passe actuel" required autocomplete="current-password">
                    <p class="profil-help">Vous devez confirmer votre mot de passe actuel pour le modifier.</p>
                </div>

                <div class="profil-field">
                    <label for="password">Nouveau mot de passe <span class="profil-required">*</span></label>
                    <input type="password" id="password" name="password"
                        placeholder="Entrez votre nouveau mot de passe" required autocomplete="new-password">
                    <p class="profil-help">Minimum 6 caractères.</p>
                </div>

                <div class="profil-field">
                    <label for="password_confirm">Confirmer le nouveau mot de passe <span class="profil-required">*</span></label>
                    <input type="password" id="password_confirm" name="password_confirm"
                        placeholder="Confirmez votre nouveau mot de passe" required autocomplete="new-password">
                </div>

                <div class="profil-form-actions">
                    <button type="submit" name="modifier_mot_de_passe" class="account-btn account-btn--primary">
                        <i class="fas fa-key" aria-hidden="true"></i>
                        Changer le mot de passe
                    </button>
                    <a href="mon-compte.php" class="account-btn account-btn--outline">
                        <i class="fas fa-times" aria-hidden="true"></i>
                        Annuler
                    </a>
                </div>
            </form>
        </section>

    </div>

    <?php include 'includes/user_footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.initAuthIntlTel === 'function') {
                window.initAuthIntlTel('telephone');
            }
        });
    </script>
    <?php include __DIR__ . '/../includes/auth_intl_tel_scripts.php'; ?>
</body>

</html>
