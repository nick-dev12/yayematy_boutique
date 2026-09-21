<?php
require_once __DIR__ . '/../includes/admin_auth.php';
/**
 * Page d'ajout de produit
 * Formulaire direct - stock géré via la colonne produits.stock (plus de lien stock_articles)
 */
require_once __DIR__ . '/../../controllers/controller_produits.php';
$result = process_add_produit();

if (isset($result['success']) && $result['success']) {
    $_SESSION['success_message'] = $result['message'];
    $categorie_id = isset($_POST['categorie_id']) ? (int) $_POST['categorie_id'] : 0;
    if ($categorie_id > 0) {
        header('Location: ../categories/produits.php?id=' . $categorie_id);
    } else {
        header('Location: ../stock/index.php');
    }
    exit;
}

require_once __DIR__ . '/../../models/model_categories.php';
$parent_categories = get_parent_categories();
$parent_categories = is_array($parent_categories) ? $parent_categories : [];
$subcategories_by_parent = [];
foreach ($parent_categories as $parent_cat) {
    $pid = (int) ($parent_cat['id'] ?? 0);
    if ($pid > 0) {
        $subcategories_by_parent[$pid] = get_subcategories_by_parent_id($pid);
    }
}

$categorie_id_prefill = isset($_GET['categorie_id']) ? (int) $_GET['categorie_id'] : 0;
$prefill_parent_id = 0;
$prefill_sub_id = 0;
$posted_categorie_id = isset($_POST['categorie_id']) ? (int) $_POST['categorie_id'] : 0;
$effective_categorie_id = $posted_categorie_id > 0 ? $posted_categorie_id : $categorie_id_prefill;

if ($effective_categorie_id > 0) {
    $prefill_cat = get_categorie_by_id($effective_categorie_id);
    if ($prefill_cat) {
        $raw_parent = (int) ($prefill_cat['parent_id'] ?? 0);
        if ($raw_parent > 0) {
            $prefill_parent_id = $raw_parent;
            $prefill_sub_id = (int) $prefill_cat['id'];
        } else {
            $prefill_parent_id = (int) $prefill_cat['id'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <?php include __DIR__ . '/../../includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un produit - Administration</title>
    <?php require_once __DIR__ . '/../../includes/asset_version.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-produit-variantes.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-produit-gallery.css'); ?>">
</head>

<body>
    <?php include '../includes/nav.php'; ?>
    
    <div class="content-header content-header-form">
        <h1><i class="fas fa-plus"></i> Ajouter un produit</h1>
        <div class="header-actions">
            <?php if ($categorie_id_prefill > 0): ?>
            <a href="../categories/produits.php?id=<?php echo $categorie_id_prefill; ?>" class="btn-back">
                <i class="fas fa-arrow-left"></i> Retour aux produits
            </a>
            <?php else: ?>
            <a href="../stock/index.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Retour au stock
            </a>
            <?php endif; ?>
        </div>
    </div>

    <section class="form-add-section">
    <div class="form-add-container">
        <?php if (isset($result['message']) && !empty($result['message']) && !$result['success']): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $result['message']; ?></span>
            </div>
        <?php endif; ?>
        
            <form method="POST" action="" enctype="multipart/form-data" class="form-add">
            <div class="form-add-block">
                <h3 class="form-add-section-title"><i class="fas fa-info-circle"></i> Informations générales</h3>
                <div class="form-group">
                    <label for="nom">Nom du produit <span class="required">*</span></label>
                    <input type="text" id="nom" name="nom" required placeholder="Ex: Miel naturel pur"
                            value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="description">Description <span class="required">*</span></label>
                        <textarea id="description" name="description" required placeholder="Décrivez votre produit..."
                            rows="4"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <div class="form-group-row form-group-row--categories" id="categorie-pickers-row">
                    <div class="form-group">
                        <label for="categorie_parent_id">Catégorie <span class="required">*</span></label>
                        <select id="categorie_parent_id" required>
                            <option value="">Sélectionner une catégorie</option>
                            <?php if (!empty($parent_categories)): ?>
                                <?php foreach ($parent_categories as $c): ?>
                                    <option value="<?php echo (int) $c['id']; ?>"
                                        <?php echo $prefill_parent_id === (int) $c['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>Aucune catégorie disponible</option>
                            <?php endif; ?>
                        </select>
                        <?php if (empty($parent_categories)): ?>
                            <small class="form-help form-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                Aucune catégorie disponible. <a href="../categories/ajouter.php" class="link-accent">Créer une catégorie</a>
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group form-group--sous-categorie" id="sous-categorie-group" hidden>
                        <label for="sous_categorie_id">Sous-catégorie <span class="required">*</span></label>
                        <select id="sous_categorie_id">
                            <option value="">Sélectionner une sous-catégorie</option>
                        </select>
                    </div>
                </div>

                <input type="hidden" name="categorie_id" id="categorie_id"
                    value="<?php echo $effective_categorie_id > 0 ? (int) $effective_categorie_id : ''; ?>">
            </div>

            <div class="form-add-block">
                    <h3 class="form-add-section-title"><i class="fas fa-tag"></i> Prix et stock</h3>
                <div class="form-group-row">
                    <div class="form-group">
                        <label for="prix">Prix (FCFA) <span class="required">*</span></label>
                        <input type="number" id="prix" name="prix" step="0.01" min="0" required placeholder="0"
                               value="<?php echo isset($_POST['prix']) ? htmlspecialchars($_POST['prix']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="prix_promotion">Prix promotionnel (FCFA)</label>
                            <input type="number" id="prix_promotion" name="prix_promotion" step="0.01" min="0"
                                placeholder="Optionnel"
                               value="<?php echo isset($_POST['prix_promotion']) ? htmlspecialchars($_POST['prix_promotion']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="stock">Stock <span class="required">*</span></label>
                        <input type="number" id="stock" name="stock" min="0" required placeholder="0"
                               value="<?php echo isset($_POST['stock']) ? htmlspecialchars($_POST['stock']) : '0'; ?>">
                    </div>
                </div>
            </div>

                <div class="form-add-block">
                    <div class="form-group form-group--product-gallery">
                        <div class="product-gallery-panel">
                            <div class="product-gallery-panel__head">
                                <h3 class="product-gallery-panel__title"><i class="fas fa-images" aria-hidden="true"></i> Images du produit <span class="required">*</span></h3>
                                <p class="product-gallery-panel__hint">Ajoutez une ou plusieurs photos. La première sera l'image principale.</p>
                            </div>
                            <label class="product-gallery-upload" for="images_produit">
                                <i class="fas fa-cloud-upload-alt" aria-hidden="true"></i>
                                <span>Cliquer pour ajouter des images</span>
                                <small>JPG, PNG, GIF, WEBP — plusieurs fichiers possibles</small>
                            </label>
                            <input type="file" id="images_produit" name="images_produit[]" accept="image/*" multiple required hidden>
                            <div id="preview-images" class="image-preview-accumulator"></div>
                            <p class="product-gallery-panel__formats">Au moins une image est obligatoire.</p>
                        </div>
                    </div>

                    <div class="form-add-block form-add-block-variantes form-group--product-variantes">
                        <label><i class="fas fa-layer-group"></i> Variantes du produit (optionnel)</label>
                        <div id="variantes-container" class="variantes-container" aria-live="polite"></div>
                        <button type="button" id="btn-add-variante" class="btn-add-variante"><i class="fas fa-plus"></i>
                            Ajouter une variante</button>
                        <?php include __DIR__ . '/partials/variante_add_modal.php'; ?>
                    </div>

                    <div class="form-group">
                        <label for="statut">Statut du produit</label>
                        <select id="statut" name="statut">
                            <option value="actif" <?php echo (!isset($_POST['statut']) || $_POST['statut'] == 'actif') ? 'selected' : ''; ?>>
                                Actif (visible en boutique)</option>
                            <option value="inactif" <?php echo (isset($_POST['statut']) && $_POST['statut'] == 'inactif') ? 'selected' : ''; ?>>
                                Inactif (masqué)</option>
                        </select>
                    </div>
                </div>

            <div class="form-add-actions">
                <button type="submit" class="btn-primary btn-submit-large">
                        <i class="fas fa-plus"></i> Ajouter le produit
                </button>
                <?php if ($categorie_id_prefill > 0): ?>
                <a href="../categories/produits.php?id=<?php echo $categorie_id_prefill; ?>" class="btn-cancel">Annuler</a>
                <?php else: ?>
                <a href="../stock/index.php" class="btn-cancel">Annuler</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    </section>

    <style>
        .stock-search-bar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: stretch;
            padding: 20px;
            background: linear-gradient(135deg, #f8f7f2 0%, #f0efe8 100%);
            border: 1px solid #e5e3d8;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        .stock-search-input-wrap {
            flex: 1 1 280px;
            position: relative;
        }
        .stock-search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #918a44;
            font-size: 16px;
        }
        .stock-search-input {
            width: 100%;
            padding: 14px 14px 14px 44px;
            border: 2px solid #e5e3d8;
            border-radius: 10px;
            font-size: 15px;
            background: #fff;
            transition: border-color 0.2s;
        }
        .stock-search-input:focus {
            outline: none;
            border-color: #918a44;
            box-shadow: 0 0 0 3px rgba(145, 138, 68, 0.15);
        }
        .stock-search-select-wrap {
            flex: 0 1 220px;
        }
        .stock-search-select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e3d8;
            border-radius: 10px;
            font-size: 15px;
            background: #fff;
            color: #333;
        }
        .stock-search-select:focus {
            outline: none;
            border-color: #918a44;
        }
        .stock-search-btn {
            padding: 14px 24px;
            border-radius: 10px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .file-input-single {
            cursor: pointer;
        }

        .image-preview-accumulator {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 15px;
        }

        .image-preview-accumulator .preview-item {
            position: relative;
        }

        .image-preview-accumulator .preview-item img {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid rgba(242, 92, 25, 0.3);
            display: block;
        }

        .image-preview-accumulator .preview-item .preview-badge {
            position: absolute;
            top: 4px;
            left: 4px;
            background: var(--couleur-dominante, #F25C19);
            color: #fff;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .image-preview-accumulator .preview-item .preview-remove {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 22px;
            height: 22px;
            border: none;
            background: rgba(0, 0, 0, 0.6);
            color: #fff;
            border-radius: 50%;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            line-height: 1;
        }

        .image-preview-accumulator .preview-item .preview-remove:hover {
            background: #c00;
        }

        .couleurs-picker-block {
            margin-top: 8px;
        }

        .couleurs-add-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .couleurs-add-row input[type="color"] {
            width: 50px;
            height: 40px;
            padding: 2px;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
        }

        .btn-add-couleur {
            padding: 12px 18px;
            background: #918a44;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add-couleur:hover {
            background: #7a7340;
        }

        .couleurs-swatches {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 10px 0;
        }

        .couleur-swatch {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            background: #f5f5f5;
            border-radius: 20px;
            border: 2px solid #ddd;
        }

        .couleur-swatch .swatch-preview {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid #333;
        }

        .couleur-swatch .swatch-hex {
            font-size: 12px;
            color: #333;
        }

        .couleur-swatch .swatch-remove {
            width: 24px;
            height: 24px;
            border: none;
            background: #c00;
            color: #fff;
            border-radius: 50%;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            line-height: 1;
        }

        .couleur-swatch .swatch-remove:hover {
            background: #a00;
        }

        .options-add-block {
            margin-top: 8px;
        }

        .options-add-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .options-input {
            flex: 1;
            min-width: 150px;
            padding: 10px 14px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .options-input:focus {
            outline: none;
            border-color: #918a44;
        }

        .btn-add-option {
            padding: 10px 16px;
            background: #918a44;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add-option:hover {
            background: #7a7340;
        }

        .options-tags-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 10px 0;
        }

        .option-tag {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: #f5f5f5;
            border-radius: 20px;
            border: 2px solid #ddd;
            font-size: 13px;
        }

        .option-tag .tag-remove {
            width: 22px;
            height: 22px;
            border: none;
            background: #c00;
            color: #fff;
            border-radius: 50%;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            line-height: 1;
        }

        .option-tag .tag-remove:hover {
            background: #a00;
        }

        .options-surcharge {
            width: 90px;
            padding: 8px 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 13px;
        }

        .option-tag .tag-surcharge {
            font-size: 11px;
            color: #666;
            margin-left: 4px;
        }

        .form-add-block-variantes {
            margin-top: 0;
            padding-top: 1.25rem;
            border-top: 1px solid rgba(140, 140, 140, 0.2);
        }

        .form-add-block-variantes > label {
            display: block;
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: var(--color-noir, #1a1a1a);
        }

        .form-add-block-variantes > label i {
            color: var(--color-orange, #f25c19);
            margin-right: 0.375rem;
        }

        .btn-remove-variante {
            width: 2.125rem;
            height: 2.125rem;
            flex-shrink: 0;
            border: none;
            background: var(--color-orange, #f25c19);
            color: var(--color-blanc, #fff);
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 1.125rem;
            line-height: 1;
        }

        .btn-remove-variante:hover {
            filter: brightness(0.92);
        }
    </style>
    <script>
        (function () {
            var input = document.getElementById('images_produit');
            var container = document.getElementById('preview-images');
            var accumulatedFiles = [];

            function updateInputFiles() {
                var dt = new DataTransfer();
                for (var i = 0; i < accumulatedFiles.length; i++) {
                    dt.items.add(accumulatedFiles[i]);
                }
                input.files = dt.files;
            }

            function addPreviews(newFiles) {
                for (var i = 0; i < newFiles.length; i++) {
                    (function (file, idx) {
                        if (!file.type.match('image.*')) return;
                        var pos = accumulatedFiles.length;
                        accumulatedFiles.push(file);
                        var reader = new FileReader();
                        reader.onload = function (e) {
                            var div = document.createElement('div');
                            div.className = 'preview-item';
                            div.dataset.index = pos;
                            var badge = document.createElement('span');
                            badge.className = 'preview-badge';
                            badge.textContent = pos === 0 ? 'Principale' : (pos + 1);
                            var img = document.createElement('img');
                            img.src = e.target.result;
                            img.alt = 'Aperçu ' + (pos + 1);
                            var btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'preview-remove';
                            btn.innerHTML = '&times;';
                            btn.title = 'Retirer';
                            btn.onclick = function (ev) {
                                ev.preventDefault();
                                ev.stopPropagation();
                                var idx = parseInt(div.dataset.index, 10);
                                accumulatedFiles.splice(idx, 1);
                                div.remove();
                                for (var j = 0; j < container.children.length; j++) {
                                    container.children[j].dataset.index = j;
                                    container.children[j].querySelector('.preview-badge').textContent =
                                        j === 0 ? 'Principale' : (j + 1);
                                }
                                updateInputFiles();
                            };
                            div.appendChild(badge);
                            div.appendChild(img);
                            div.appendChild(btn);
                            container.appendChild(div);
                        };
                        reader.readAsDataURL(file);
                    })(newFiles[i], i);
                }
                updateInputFiles();
            }

            input.addEventListener('change', function () {
                if (this.files && this.files.length > 0) {
                    var newFiles = [];
                    for (var i = 0; i < this.files.length; i++) {
                        newFiles.push(this.files[i]);
                    }
                    addPreviews(newFiles);
                }
            });

            document.querySelector('.form-add').addEventListener('submit', function (e) {
                if (accumulatedFiles.length === 0) {
                    e.preventDefault();
                    alert('Veuillez ajouter au moins une image.');
                    return false;
                }
            });
        })();
        (function () {
            var couleurInput = document.getElementById('couleur-input');
            var btnAdd = document.getElementById('btn-add-couleur');
            var list = document.getElementById('couleurs-list');
            var hidden = document.getElementById('couleurs-hidden');
            var couleurs = [];
            try {
                if (hidden && hidden.value) {
                    var parsed = JSON.parse(hidden.value);
                    if (Array.isArray(parsed)) couleurs = parsed;
                }
            } catch (e) { }

            function updateHidden() {
                if (hidden) hidden.value = JSON.stringify(couleurs);
            }

            function render() {
                if (!list) return;
                list.innerHTML = '';
                couleurs.forEach(function (hex, i) {
                    var div = document.createElement('div');
                    div.className = 'couleur-swatch';
                    div.innerHTML = '<span class="swatch-preview" style="background:' + hex +
                        '"></span><span class="swatch-hex">' + hex +
                        '</span><button type="button" class="swatch-remove" data-i="' + i +
                        '" title="Retirer">&times;</button>';
                    list.appendChild(div);
                });
                updateHidden();
            }
            if (btnAdd && couleurInput) {
                btnAdd.addEventListener('click', function () {
                    var hex = couleurInput.value;
                    if (hex && couleurs.indexOf(hex) === -1) {
                        couleurs.push(hex);
                        render();
                    }
                });
            }
            if (list) {
                list.addEventListener('click', function (e) {
                    var btn = e.target.closest('.swatch-remove');
                    if (btn) {
                        var i = parseInt(btn.dataset.i, 10);
                        couleurs.splice(i, 1);
                        render();
                    }
                });
            }
            render();
        })();
        (function () {
            function initOptionsWithSurcharge(idInput, idSurcharge, idList, idHidden, btnId) {
                var input = document.getElementById(idInput);
                var surchargeInput = document.getElementById(idSurcharge);
                var list = document.getElementById(idList);
                var hidden = document.getElementById(idHidden);
                var btn = document.getElementById(btnId);
                var values = [];
                try {
                    if (hidden && hidden.value && hidden.value !== '[]') {
                        var parsed = JSON.parse(hidden.value);
                        if (Array.isArray(parsed)) values = parsed;
                        else values = (hidden.value.split(',').map(function (s) {
                            return {
                                v: s.trim(),
                                s: 0
                            };
                        })).filter(function (x) {
                            return x.v && x.v !== '[]';
                        });
                    }
                } catch (e) {
                    if (hidden && hidden.value && hidden.value !== '[]') {
                        values = hidden.value.split(',').map(function (s) {
                            return {
                                v: s.trim(),
                                s: 0
                            };
                        }).filter(function (x) {
                            return x.v && x.v !== '[]';
                        });
                    }
                }
                values = values.filter(function (item) {
                    var v = typeof item === 'object' ? item.v : item;
                    return v && v !== '[]' && String(v).trim() !== '';
                });

                function updateHidden() {
                    if (hidden) hidden.value = JSON.stringify(values);
                }

                function render() {
                    if (!list) return;
                    list.innerHTML = '';
                    values.forEach(function (item, i) {
                        var v = typeof item === 'object' ? item.v : item;
                        var s = typeof item === 'object' ? (item.s || 0) : 0;
                        var surc = s > 0 ? ' <span class="tag-surcharge">+' + s + ' FCFA</span>' : '';
                        var div = document.createElement('div');
                        div.className = 'option-tag';
                        div.innerHTML = '<span>' + (v.replace(/</g, '&lt;').replace(/>/g, '&gt;')) + surc +
                            '</span><button type="button" class="tag-remove" data-i="' + i +
                            '" title="Retirer">&times;</button>';
                        list.appendChild(div);
                    });
                    updateHidden();
                }
                if (btn && input) {
                    btn.addEventListener('click', function () {
                        var val = (input.value || '').trim();
                        var surc = surchargeInput ? (parseInt(surchargeInput.value, 10) || 0) : 0;
                        if (val) {
                            var exists = values.some(function (x) {
                                return (typeof x === 'object' ? x.v : x) === val;
                            });
                            if (!exists) {
                                values.push({
                                    v: val,
                                    s: surc
                                });
                                input.value = '';
                                if (surchargeInput) surchargeInput.value = '';
                                render();
                            }
                        }
                    });
                    input.addEventListener('keypress', function (e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            btn.click();
                        }
                    });
                }
                if (list) {
                    list.addEventListener('click', function (e) {
                        var b = e.target.closest('.tag-remove');
                        if (b) {
                            values.splice(parseInt(b.dataset.i, 10), 1);
                            render();
                        }
                    });
                }
                render();
            }
            initOptionsWithSurcharge('poids-input', 'poids-surcharge', 'poids-list', 'poids-hidden', 'btn-add-poids');
            initOptionsWithSurcharge('taille-input', 'taille-surcharge', 'taille-list', 'taille-hidden',
                'btn-add-taille');
        })();
        (function () {
            var subsByParent = <?php echo json_encode(array_map(function ($subs) {
                return array_values(array_map(function ($s) {
                    return ['id' => (int) ($s['id'] ?? 0), 'nom' => (string) ($s['nom'] ?? '')];
                }, is_array($subs) ? $subs : []));
            }, $subcategories_by_parent), JSON_UNESCAPED_UNICODE); ?>;

            var parentSelect = document.getElementById('categorie_parent_id');
            var subGroup = document.getElementById('sous-categorie-group');
            var subSelect = document.getElementById('sous_categorie_id');
            var hiddenCategorie = document.getElementById('categorie_id');
            var form = document.querySelector('form.form-add');
            var prefillSubId = <?php echo (int) $prefill_sub_id; ?>;

            if (!parentSelect || !hiddenCategorie || !subGroup || !subSelect) {
                return;
            }

            function populateSubcategories(parentId, selectedSubId) {
                var subs = subsByParent[parentId] || subsByParent[String(parentId)] || [];
                subSelect.innerHTML = '<option value="">Sélectionner une sous-catégorie</option>';
                subs.forEach(function (sc) {
                    if (!sc || !sc.id) {
                        return;
                    }
                    var opt = document.createElement('option');
                    opt.value = String(sc.id);
                    opt.textContent = sc.nom;
                    if (selectedSubId && Number(sc.id) === Number(selectedSubId)) {
                        opt.selected = true;
                    }
                    subSelect.appendChild(opt);
                });
            }

            function syncCategorieValue() {
                var parentId = parentSelect.value;
                if (!parentId) {
                    hiddenCategorie.value = '';
                    return;
                }
                var subs = subsByParent[parentId] || subsByParent[String(parentId)] || [];
                if (subs.length > 0) {
                    hiddenCategorie.value = subSelect.value || '';
                } else {
                    hiddenCategorie.value = parentId;
                }
            }

            function onParentChange() {
                var parentId = parentSelect.value;
                if (!parentId) {
                    subGroup.hidden = true;
                    subSelect.value = '';
                    hiddenCategorie.value = '';
                    return;
                }
                var subs = subsByParent[parentId] || subsByParent[String(parentId)] || [];
                if (subs.length > 0) {
                    populateSubcategories(parentId, 0);
                    subGroup.hidden = false;
                    hiddenCategorie.value = '';
                } else {
                    subGroup.hidden = true;
                    subSelect.value = '';
                    hiddenCategorie.value = parentId;
                }
            }

            parentSelect.addEventListener('change', onParentChange);
            subSelect.addEventListener('change', syncCategorieValue);

            if (form) {
                form.addEventListener('submit', function (e) {
                    syncCategorieValue();
                    var parentId = parentSelect.value;
                    if (!parentId) {
                        e.preventDefault();
                        parentSelect.focus();
                        return;
                    }
                    var subs = subsByParent[parentId] || subsByParent[String(parentId)] || [];
                    if (subs.length > 0 && !subSelect.value) {
                        e.preventDefault();
                        subGroup.hidden = false;
                        subSelect.focus();
                        return;
                    }
                });
            }

            if (parentSelect.value) {
                onParentChange();
                if (prefillSubId > 0) {
                    subSelect.value = String(prefillSubId);
                    syncCategorieValue();
                }
            }
        })();
    </script>
    <script src="<?php echo asset_url('/js/admin-produit-variantes.js'); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof initProduitVariantes === 'function') {
                initProduitVariantes();
            }
        });
    </script>
    <?php include '../includes/footer.php'; ?>