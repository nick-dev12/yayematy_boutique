<?php
require_once __DIR__ . '/../includes/admin_auth.php';
/**
 * Page de modification de produit
 * Programmation procédurale uniquement
 */
// Récupérer l'ID du produit
$produit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($produit_id <= 0) {
    header('Location: index.php');
    exit;
}

// Récupérer le produit et ses variantes
require_once __DIR__ . '/../../models/model_produits.php';
require_once __DIR__ . '/../../models/model_variantes.php';
$produit = get_produit_by_id($produit_id);
$variantes = $produit ? get_variantes_by_produit($produit_id) : [];

if (!$produit) {
    header('Location: index.php');
    exit;
}

// Traiter le formulaire
require_once __DIR__ . '/../../controllers/controller_produits.php';
$result = process_update_produit($produit_id);

// Si la modification est réussie, rediriger vers la liste
if (isset($result['success']) && $result['success']) {
    $_SESSION['success_message'] = $result['message'];
    header('Location: index.php');
    exit;
}

// Récupérer les catégories (stock géré via produits.stock)
require_once __DIR__ . '/../../models/model_categories.php';
require_once __DIR__ . '/../../includes/site_url.php';
$parent_categories = get_parent_categories();
$parent_categories = is_array($parent_categories) ? $parent_categories : [];
$subcategories_by_parent = [];
foreach ($parent_categories as $parent_cat) {
    $pid = (int) ($parent_cat['id'] ?? 0);
    if ($pid > 0) {
        $subcategories_by_parent[$pid] = get_subcategories_by_parent_id($pid);
    }
}
$prefill_parent_id = 0;
$prefill_sub_id = 0;
$effective_categorie_id = (int) ($produit['categorie_id'] ?? 0);
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
    <title>Modifier un Produit - Administration</title>
    <?php require_once __DIR__ . '/../../includes/asset_version.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-produit-variantes.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('/css/admin-produit-gallery.css'); ?>">
    <style>
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
        border: 2px solid #e8e8e8;
        border-radius: 8px;
        font-size: 14px;
    }

    .options-input:focus {
        outline: none;
        border-color: #918a44;
    }

    .btn-add-op

    /* The above code is a HTML form element with a dropdown select menu for selecting a
    default unit. The PHP code inside the option tags is used to dynamically set the
    selected attribute based on the value of `['unite']`. This allows the form to
    pre-select the unit that was previously saved or selected. */
    tion {
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
        border: 2px solid #e8e8e8;
        border-radius: 8px;
        font-size: 13px;
    }

    .option-tag .tag-surcharge {
        font-size: 11px;
        color: #666;
        margin-left: 4px;
    }

    </style>
</head>

<body>
    <?php include '../includes/nav.php'; ?>

    <div class="content-header content-header-form">
        <h1><i class="fas fa-edit"></i> Modifier un produit</h1>
        <div class="header-actions">
            <a href="index.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Retour au catalogue
            </a>
        </div>
    </div>

    <section class="form-add-section">
    <div class="form-add-container">
        <?php if (isset($result['message']) && !empty($result['message']) && !$result['success']): ?>
        <div class="message error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($result['message']); ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data" class="form-add">
            <div class="form-add-block">
                <h3 class="form-add-section-title"><i class="fas fa-info-circle"></i> Informations générales</h3>
                <div class="form-group">
                    <label for="nom">Nom du produit <span class="required">*</span></label>
                    <input type="text" id="nom" name="nom" required placeholder="Ex: Miel naturel pur"
                        value="<?php echo htmlspecialchars($produit['nom']); ?>">
                </div>

                <div class="form-group">
                    <label for="description">Description <span class="required">*</span></label>
                    <textarea id="description" name="description" required placeholder="Décrivez votre produit..."
                        rows="4"><?php echo htmlspecialchars($produit['description']); ?></textarea>
                </div>

                <div class="form-group-row form-group-row--categories" id="categorie-pickers-row">
                    <div class="form-group">
                        <label for="categorie_parent_id">Catégorie <span class="required">*</span></label>
                        <select id="categorie_parent_id" required>
                            <option value="">Sélectionner une catégorie</option>
                            <?php foreach ($parent_categories as $c): ?>
                            <option value="<?php echo (int) $c['id']; ?>"
                                <?php echo $prefill_parent_id === (int) $c['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['nom']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group form-group--sous-categorie" id="sous-categorie-group" hidden>
                        <label for="sous_categorie_id">Sous-catégorie <span class="form-optional">(optionnel)</span></label>
                        <select id="sous_categorie_id">
                            <option value="">Aucune — catégorie principale</option>
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
                            value="<?php echo htmlspecialchars($produit['prix']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="prix_promotion">Prix promotionnel (FCFA)</label>
                        <input type="number" id="prix_promotion" name="prix_promotion" step="0.01" min="0"
                            placeholder="Optionnel"
                            value="<?php echo htmlspecialchars($produit['prix_promotion'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="stock">Stock <span class="required">*</span></label>
                        <input type="number" id="stock" name="stock" min="0" required placeholder="0"
                            value="<?php echo (int) $produit['stock']; ?>">
                    </div>
                </div>
            </div>

            <div class="form-add-block">
            <div class="form-group form-group--product-gallery">
                <?php
                $images_produit = [];
                if (!empty($produit['images'])) {
                    $dec = json_decode($produit['images'], true);
                    if (is_array($dec)) {
                        $images_produit = $dec;
                    }
                }
                if (empty($images_produit) && !empty($produit['image_principale'])) {
                    $images_produit = [$produit['image_principale']];
                }
                ?>
                <div class="product-gallery-panel">
                    <div class="product-gallery-panel__head">
                        <h3 class="product-gallery-panel__title"><i class="fas fa-images" aria-hidden="true"></i> Images du produit</h3>
                        <p class="product-gallery-panel__hint">Ajoutez une ou plusieurs photos. La première sera l'image principale.</p>
                    </div>
                    <div class="product-gallery-wrap" id="product-gallery-wrap">
                        <div id="gallery-existing">
                            <?php foreach ($images_produit as $idx => $img_path): ?>
                            <div class="gallery-thumb-edit<?php echo $idx === 0 ? ' is-primary' : ''; ?>" data-path="<?php echo htmlspecialchars($img_path); ?>">
                                <input type="hidden" name="images_to_keep[]" value="<?php echo htmlspecialchars($img_path); ?>">
                                <span class="img-badge"><?php echo $idx === 0 ? 'Principale' : (string) ($idx + 1); ?></span>
                                <button type="button" class="img-remove-btn" title="Supprimer cette image" aria-label="Supprimer">&times;</button>
                                <img src="../../upload/<?php echo htmlspecialchars($img_path); ?>"
                                    alt="Image <?php echo $idx + 1; ?>" onerror="this.src='<?php echo htmlspecialchars(public_url('/image/produit1.jpg'), ENT_QUOTES, 'UTF-8'); ?>'">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div id="preview-supplementaires"></div>
                        <label class="product-gallery-add" for="images_supplementaires">
                            <i class="fas fa-plus" aria-hidden="true"></i>
                            <span>Ajouter</span>
                        </label>
                    </div>
                    <input type="file" id="images_supplementaires" name="images_supplementaires[]" accept="image/*" multiple hidden>
                    <p class="product-gallery-panel__formats">JPG, PNG, GIF, WEBP — plusieurs fichiers possibles. Au moins une image doit rester.</p>
                </div>
            </div>

            <div class="form-add-block form-add-block-variantes form-group--product-variantes">
                <label><i class="fas fa-layer-group"></i> Variantes du produit (optionnel)</label>
                <div id="variantes-container" class="variantes-container" aria-live="polite">
                    <?php if (!empty($variantes)): ?>
                    <?php foreach ($variantes as $idx => $var): ?>
                    <div class="variante-item" data-index="<?php echo $idx; ?>">
                        <div class="variante-row">
                            <input type="hidden" name="variantes_id[]" value="<?php echo (int)$var['id']; ?>">
                            <input type="text" name="variantes_nom[]" placeholder="Nom de la variante"
                                class="variante-nom" value="<?php echo htmlspecialchars($var['nom']); ?>">
                            <input type="number" name="variantes_prix[]" placeholder="Prix FCFA" min="0" step="0.01"
                                class="variante-prix" value="<?php echo htmlspecialchars($var['prix']); ?>">
                            <input type="number" name="variantes_prix_promo[]" placeholder="Prix promo" min="0"
                                step="0.01" class="variante-prix-promo"
                                value="<?php echo $var['prix_promotion'] ? htmlspecialchars($var['prix_promotion']) : ''; ?>">
                            <div class="variante-image-wrap">
                                <div class="variante-image-area">
                                    <input type="file" name="variantes_image[]" accept="image/*"
                                        class="variante-image-input">
                                    <span class="variante-image-label"
                                        <?php echo $var['image'] ? 'style="display: none;"' : ''; ?>><i
                                            class="fas fa-image"></i>
                                        <?php echo $var['image'] ? 'Changer' : 'Image'; ?></span>
                                    <img class="variante-preview-img"
                                        src="<?php echo $var['image'] ? '../../upload/' . htmlspecialchars($var['image']) : ''; ?>"
                                        alt="" <?php echo $var['image'] ? '' : 'style="display: none;"'; ?>>
                                </div>
                            </div>
                            <button type="button" class="btn-remove-variante" title="Supprimer">&times;</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" id="btn-add-variante" class="btn-add-variante"><i class="fas fa-plus"></i> Ajouter
                    une variante</button>
                <?php include __DIR__ . '/partials/variante_add_modal.php'; ?>
            </div>

            <div class="form-group">
                <label for="statut">Statut du produit</label>
                <select id="statut" name="statut">
                    <option value="actif" <?php echo ($produit['statut'] == 'actif') ? 'selected' : ''; ?>>Actif (visible en boutique)</option>
                    <option value="inactif" <?php echo ($produit['statut'] == 'inactif') ? 'selected' : ''; ?>>Inactif (masqué)</option>
                    <option value="rupture_stock"
                        <?php echo ($produit['statut'] == 'rupture_stock') ? 'selected' : ''; ?>>Rupture de stock</option>
                </select>
            </div>
            </div>

            <div class="form-add-actions">
                <button type="submit" class="btn-primary btn-submit-large">
                    <i class="fas fa-save"></i> Enregistrer les modifications
                </button>
                <a href="index.php" class="btn-cancel">Annuler</a>
            </div>
        </form>
    </div>
    </section>

    <script>
    (function() {
        var galleryExisting = document.getElementById('gallery-existing');
        var inputSupp = document.getElementById('images_supplementaires');
        function refreshGalleryPrimaryBadges() {
            if (!galleryExisting) return;
            var thumbs = galleryExisting.querySelectorAll('.gallery-thumb-edit');
            thumbs.forEach(function (thumb, index) {
                thumb.classList.toggle('is-primary', index === 0);
                var badge = thumb.querySelector('.img-badge');
                if (badge) {
                    badge.textContent = index === 0 ? 'Principale' : String(index + 1);
                }
            });
        }

        if (galleryExisting) {
            galleryExisting.addEventListener('click', function (e) {
                var btn = e.target.closest('.img-remove-btn');
                if (btn) {
                    e.preventDefault();
                    btn.closest('.gallery-thumb-edit').remove();
                    refreshGalleryPrimaryBadges();
                }
            });
        }

        function previewMultipleImages(input, containerId) {
            var c = document.getElementById(containerId);
            if (!c) return;
            c.innerHTML = '';
            if (!input.files) return;
            for (var i = 0; i < input.files.length; i++) {
                (function (f, index) {
                    var r = new FileReader();
                    r.onload = function (e) {
                        var d = document.createElement('div');
                        d.className = 'preview-item';
                        var badge = document.createElement('span');
                        badge.className = 'preview-badge preview-badge--new';
                        badge.textContent = 'Nouveau';
                        var img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = 'Nouvelle image ' + (index + 1);
                        d.appendChild(badge);
                        d.appendChild(img);
                        c.appendChild(d);
                    };
                    r.readAsDataURL(f);
                })(input.files[i], i);
            }
        }
        if (inputSupp) {
            inputSupp.addEventListener('change', function () {
                previewMultipleImages(this, 'preview-supplementaires');
            });
        }
        document.querySelector('form').addEventListener('submit', function(e) {
            var kept = document.querySelectorAll('input[name="images_to_keep[]"]').length;
            var newFiles = inputSupp && inputSupp.files ? inputSupp.files.length : 0;
            if (kept === 0 && newFiles === 0) {
                e.preventDefault();
                alert(
                    'Au moins une image est obligatoire. Veuillez conserver ou ajouter au moins une image.'
                );
                return false;
            }
        });
    })();
    (function() {
        var couleurInput = document.getElementById('couleur-input');
        var btnAdd = document.getElementById('btn-add-couleur');
        var list = document.getElementById('couleurs-list');
        var hidden = document.getElementById('couleurs-hidden');
        var couleurs = [];
        try {
            if (hidden && hidden.value && hidden.value !== '[]') {
                var parsed = JSON.parse(hidden.value);
                if (Array.isArray(parsed)) {
                    couleurs = parsed.filter(function(c) {
                        return typeof c === 'string' && /^#[0-9A-Fa-f]{6}$/.test(c);
                    });
                }
            }
        } catch (e) {}

        function updateHidden() {
            if (hidden) hidden.value = JSON.stringify(couleurs);
        }

        function render() {
            if (!list) return;
            list.innerHTML = '';
            couleurs.forEach(function(hex, i) {
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
            btnAdd.addEventListener('click', function() {
                var hex = couleurInput.value;
                if (hex && couleurs.indexOf(hex) === -1) {
                    couleurs.push(hex);
                    render();
                }
            });
        }
        if (list) {
            list.addEventListener('click', function(e) {
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
    (function() {
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
                    else values = (hidden.value.split(',').map(function(s) {
                        return {
                            v: s.trim(),
                            s: 0
                        };
                    })).filter(function(x) {
                        return x.v && x.v !== '[]';
                    });
                }
            } catch (e) {
                if (hidden && hidden.value && hidden.value !== '[]') {
                    values = hidden.value.split(',').map(function(s) {
                        return {
                            v: s.trim(),
                            s: 0
                        };
                    }).filter(function(x) {
                        return x.v && x.v !== '[]';
                    });
                }
            }
            values = values.filter(function(item) {
                var v = typeof item === 'object' ? item.v : item;
                return v && v !== '[]' && String(v).trim() !== '';
            });

            function updateHidden() {
                if (hidden) hidden.value = JSON.stringify(values);
            }

            function render() {
                if (!list) return;
                list.innerHTML = '';
                values.forEach(function(item, i) {
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
                btn.addEventListener('click', function() {
                    var val = (input.value || '').trim();
                    var surc = surchargeInput ? (parseInt(surchargeInput.value, 10) || 0) : 0;
                    if (val) {
                        var exists = values.some(function(x) {
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
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        btn.click();
                    }
                });
            }
            if (list) {
                list.addEventListener('click', function(e) {
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
    </script>
    <script src="<?php echo asset_url('/js/admin-produit-variantes.js'); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof initProduitVariantes === 'function') {
                initProduitVariantes({ includeIdField: true });
            }
        });

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
                subSelect.innerHTML = '<option value="">Aucune — catégorie principale</option>';
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
                if (subs.length > 0 && subSelect.value) {
                    hiddenCategorie.value = subSelect.value;
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
                    hiddenCategorie.value = parentId;
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
                });
            }

            if (parentSelect.value) {
                var subs = subsByParent[parentSelect.value] || subsByParent[String(parentSelect.value)] || [];
                if (subs.length > 0) {
                    populateSubcategories(parentSelect.value, prefillSubId);
                    subGroup.hidden = false;
                    syncCategorieValue();
                } else {
                    syncCategorieValue();
                }
            }
        })();
    </script>
    <?php include '../includes/footer.php'; ?>