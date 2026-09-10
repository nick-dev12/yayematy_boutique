<?php
require_once __DIR__ . '/../includes/admin_auth.php';
/**
 * Page d'ajout d'un slide
 * Programmation procédurale uniquement
 */
// Traiter le formulaire
require_once __DIR__ . '/../../controllers/controller_slider.php';
$result = process_add_slide();

// Si l'ajout est réussi, rediriger
if (isset($result['success']) && $result['success']) {
    $_SESSION['success_message'] = $result['message'];
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include __DIR__ . '/../../includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Slide - Administration</title>
    <?php require_once __DIR__ . '/../../includes/asset_version.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin-dashboard.css<?php echo asset_version_query(); ?>">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: #6b2f20;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e8e8e8;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #ffffff;
            color: #000000;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #918a44;
            box-shadow: 0 0 0 3px rgba(145, 138, 68, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-hint {
            display: block;
            margin-top: 0.375rem;
            color: #8c8c8c;
            font-size: 0.75rem;
            line-height: 1.4;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e8e8e8;
        }

        .btn-primary {
            padding: 12px 30px;
            background: #918a44;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #6b2f20;
            transform: translateY(-2px);
        }

        .btn-back {
            padding: 12px 30px;
            background: #ffffff;
            color: #6b2f20;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: #e0e0e0;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/nav.php'; ?>
    
    <div class="content-header">
        <h1><i class="fas fa-plus-circle"></i> Ajouter un Slide</h1>
        <div class="header-actions">
            <a href="index.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <section class="content-section">
        <?php if (isset($result['message']) && !empty($result['message']) && !$result['success']): ?>
            <div style="background: #fee; border-left: 4px solid #c26638; color: #6b2f20; padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; font-size: 14px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $result['message']; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data" class="form-container">
            <input type="hidden" name="MAX_FILE_SIZE" value="52428800">
            <div class="form-group">
                <label for="titre">Titre *</label>
                <input type="text" id="titre" name="titre" required
                       value="<?php echo isset($_POST['titre']) ? htmlspecialchars($_POST['titre']) : ''; ?>"
                       placeholder="Titre du slide">
            </div>

            <div class="form-group">
                <label for="image">Image *</label>
                <input type="file" id="image" name="image" accept="image/*" required>
                <small class="form-hint">JPEG, PNG, WEBP… · max. 50 Mo</small>
            </div>

            <div class="form-group">
                <label for="bouton_lien">Lien du bouton (optionnel)</label>
                <input type="text" id="bouton_lien" name="bouton_lien"
                       value="<?php echo isset($_POST['bouton_lien']) ? htmlspecialchars($_POST['bouton_lien']) : ''; ?>"
                       placeholder="Ex: /produits.php">
            </div>

            <div class="form-group">
                <label for="ordre">Ordre d'affichage</label>
                <input type="number" id="ordre" name="ordre" min="0" value="<?php echo isset($_POST['ordre']) ? intval($_POST['ordre']) : 0; ?>">
                <small class="form-hint">Plus le nombre est petit, plus le slide apparaît en premier</small>
            </div>

            <input type="hidden" name="statut" value="actif">

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <a href="index.php" class="btn-back">Annuler</a>
            </div>
        </form>
    </section>

    <?php include '../includes/footer.php'; ?>

