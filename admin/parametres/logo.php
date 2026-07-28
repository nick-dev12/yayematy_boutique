<?php
require_once __DIR__ . '/../../includes/session_user.php';
/**
 * Paramètre logo du site — upload et prévisualisation
 */

session_start_persistent();

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_email'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../../models/model_site_brand.php';
require_once __DIR__ . '/../../includes/site_brand.php';

$config = get_site_brand_config();
$current_logo = site_brand_logo();
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../controllers/controller_site_brand.php';
    $result = process_update_site_brand_logo();

    if (!empty($result['success'])) {
        $_SESSION['success_message'] = $result['message'];
        header('Location: ../parametres.php');
        exit;
    }

    $error_message = $result['message'] ?? 'Erreur inconnue';
    $config = get_site_brand_config();
    $current_logo = site_brand_logo();
}

$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <?php include __DIR__ . '/../../includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logo du site — Administration</title>
    <?php require_once __DIR__ . '/../../includes/asset_version.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/admin-dashboard.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/admin-param-logo.css<?php echo asset_version_query(); ?>">
</head>

<body class="page-admin-logo">
    <?php include '../includes/nav.php'; ?>

    <section class="produits-section logo-param-section">
        <div class="section-title">
            <h2><i class="fas fa-image"></i> Logo du site</h2>
            <p class="section-subtitle">
                Personnalisez le logo affiché sur la boutique, les pages de connexion, les factures et l’administration.
            </p>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <div class="logo-param-layout">
            <div class="logo-param-form-card">
                <form method="POST" action="" enctype="multipart/form-data" class="logo-param-form" id="logo-form">
                    <input type="hidden" name="MAX_FILE_SIZE" value="5242880">

                    <div class="form-group">
                        <label for="logo_alt">
                            <i class="fas fa-tag"></i> Texte alternatif (accessibilité)
                        </label>
                        <input type="text" id="logo_alt" name="logo_alt"
                            value="<?php echo htmlspecialchars($config['logo_alt'] ?? site_brand_logo_alt()); ?>"
                            placeholder="YAYEMATY MARKET — Votre marché local">
                        <small>Utilisé par les lecteurs d’écran et lorsque l’image ne charge pas.</small>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-cloud-upload-alt"></i> Nouveau logo</label>
                        <div class="logo-dropzone" id="logo-dropzone" tabindex="0" role="button" aria-label="Choisir un logo">
                            <input type="file" id="logo" name="logo" class="logo-dropzone__input"
                                accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml">
                            <div class="logo-dropzone__content">
                                <i class="fas fa-cloud-arrow-up"></i>
                                <p class="logo-dropzone__title">Glissez votre logo ici</p>
                                <p class="logo-dropzone__hint">ou cliquez pour parcourir · JPG, PNG, WEBP, GIF, SVG · max 5 Mo</p>
                            </div>
                        </div>
                        <div class="logo-upload-preview" id="logo-upload-preview" hidden>
                            <img src="" alt="Aperçu du nouveau logo" id="logo-upload-preview-img">
                            <button type="button" class="logo-upload-preview__clear" id="logo-upload-clear">
                                <i class="fas fa-times"></i> Retirer
                            </button>
                        </div>
                    </div>

                    <div class="logo-param-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Enregistrer le logo
                        </button>
                        <button type="submit" name="reset_default" value="1" class="btn-secondary"
                            formaction="" onclick="return confirm('Rétablir le logo Yaye Maty par défaut ?');">
                            <i class="fas fa-rotate-left"></i> Logo par défaut
                        </button>
                        <a href="../parametres.php" class="btn-cancel">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                    </div>
                </form>
            </div>

            <aside class="logo-param-preview-card" aria-label="Prévisualisation du logo">
                <h3 class="logo-preview-heading"><i class="fas fa-eye"></i> Aperçu en direct</h3>
                <p class="logo-preview-sub">Le logo sera visible aux emplacements suivants :</p>

                <div class="logo-preview-block">
                    <span class="logo-preview-label">Barre de navigation</span>
                    <div class="logo-preview-mock logo-preview-mock--nav">
                        <img src="<?php echo htmlspecialchars($current_logo); ?>" alt="" id="preview-nav-logo"
                            data-default-src="<?php echo htmlspecialchars(site_brand_default_logo_path()); ?>">
                        <span><?php echo htmlspecialchars(site_brand_name_market()); ?></span>
                    </div>
                </div>

                <div class="logo-preview-block">
                    <span class="logo-preview-label">Pied de page</span>
                    <div class="logo-preview-mock logo-preview-mock--footer">
                        <img src="<?php echo htmlspecialchars($current_logo); ?>" alt="" id="preview-footer-logo">
                    </div>
                </div>

                <div class="logo-preview-block">
                    <span class="logo-preview-label">Connexion / factures</span>
                    <div class="logo-preview-mock logo-preview-mock--auth">
                        <img src="<?php echo htmlspecialchars($current_logo); ?>" alt="" id="preview-auth-logo">
                    </div>
                </div>

                <div class="logo-preview-block">
                    <span class="logo-preview-label">Onglet navigateur (favicon)</span>
                    <div class="logo-preview-mock logo-preview-mock--favicon">
                        <img src="<?php echo htmlspecialchars($current_logo); ?>" alt="" id="preview-favicon-logo">
                        <span>Yaye Maty</span>
                    </div>
                </div>

                <?php if (!empty($config['date_modification'])): ?>
                    <p class="logo-preview-meta">
                        <i class="fas fa-clock"></i>
                        Dernière modification : <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($config['date_modification']))); ?>
                    </p>
                <?php endif; ?>
            </aside>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>

    <script>
        (function () {
            var input = document.getElementById('logo');
            var dropzone = document.getElementById('logo-dropzone');
            var previewWrap = document.getElementById('logo-upload-preview');
            var previewImg = document.getElementById('logo-upload-preview-img');
            var clearBtn = document.getElementById('logo-upload-clear');
            var previewTargets = [
                document.getElementById('preview-nav-logo'),
                document.getElementById('preview-footer-logo'),
                document.getElementById('preview-auth-logo'),
                document.getElementById('preview-favicon-logo')
            ];

            function setPreviewSrc(src) {
                previewTargets.forEach(function (img) {
                    if (img) img.src = src;
                });
            }

            function handleFile(file) {
                if (!file || !file.type.match(/^image\//)) return;
                var reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    previewWrap.hidden = false;
                    dropzone.classList.add('is-filled');
                    setPreviewSrc(e.target.result);
                };
                reader.readAsDataURL(file);
            }

            input.addEventListener('change', function () {
                if (input.files && input.files[0]) handleFile(input.files[0]);
            });

            clearBtn.addEventListener('click', function () {
                input.value = '';
                previewWrap.hidden = true;
                dropzone.classList.remove('is-filled');
                var current = document.getElementById('preview-nav-logo');
                var fallback = current ? current.getAttribute('data-default-src') : '';
                setPreviewSrc(current ? current.src.split('?')[0] : fallback);
            });

            ['dragenter', 'dragover'].forEach(function (evt) {
                dropzone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    dropzone.classList.add('is-dragover');
                });
            });
            ['dragleave', 'drop'].forEach(function (evt) {
                dropzone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    dropzone.classList.remove('is-dragover');
                });
            });
            dropzone.addEventListener('drop', function (e) {
                var files = e.dataTransfer.files;
                if (files && files[0]) {
                    input.files = files;
                    handleFile(files[0]);
                }
            });
        })();
    </script>
</body>

</html>
