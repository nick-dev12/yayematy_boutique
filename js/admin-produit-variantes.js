/**
 * Variantes produit admin — ajout via modale overlay.
 */
(function () {
    function formatPriceFcfa(value) {
        var n = parseFloat(value);
        if (isNaN(n)) {
            return '';
        }
        return n.toLocaleString('fr-FR', { maximumFractionDigits: 0 }) + ' FCFA';
    }

    function previewModalImage(input) {
        var wrap = input.closest('.variante-image-wrap');
        if (!wrap) {
            return;
        }
        var img = wrap.querySelector('.variante-preview-img');
        var label = wrap.querySelector('.variante-image-label');
        if (!img || !label) {
            return;
        }
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                img.src = e.target.result;
                img.hidden = false;
                label.style.display = 'none';
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            img.src = '';
            img.hidden = true;
            label.style.display = '';
        }
    }

    function resetModal(modal) {
        var nom = modal.querySelector('#variante-modal-nom');
        var prix = modal.querySelector('#variante-modal-prix');
        var promo = modal.querySelector('#variante-modal-prix-promo');
        var file = modal.querySelector('#variante-modal-image');
        if (nom) nom.value = '';
        if (prix) prix.value = '';
        if (promo) promo.value = '';
        if (file) {
            file.value = '';
            previewModalImage(file);
        }
    }

    function openModal(modal) {
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('is-open');
        document.body.classList.add('variante-modal-open');
        var nom = modal.querySelector('#variante-modal-nom');
        if (nom) {
            nom.focus();
        }
    }

    function closeModal(modal) {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        modal.hidden = true;
        document.body.classList.remove('variante-modal-open');
        resetModal(modal);
    }

    function buildSummaryHtml(nom, prix, promo, thumbSrc) {
        var priceLine = formatPriceFcfa(prix);
        if (promo && parseFloat(promo) > 0) {
            priceLine += ' · Promo ' + formatPriceFcfa(promo);
        }
        var thumb = thumbSrc
            ? '<img class="variante-summary__thumb" src="' + thumbSrc + '" alt="">'
            : '<span class="variante-summary__thumb variante-summary__thumb--empty"><i class="fas fa-image" aria-hidden="true"></i></span>';
        return (
            '<div class="variante-summary">' +
            thumb +
            '<div class="variante-summary__text">' +
            '<strong class="variante-summary__nom"></strong>' +
            '<span class="variante-summary__prix"></span>' +
            '</div>' +
            '<button type="button" class="btn-remove-variante variante-summary__remove" title="Supprimer">&times;</button>' +
            '</div>'
        );
    }

    function attachSummaryText(summaryEl, nom, prix, promo) {
        var nameEl = summaryEl.querySelector('.variante-summary__nom');
        var priceEl = summaryEl.querySelector('.variante-summary__prix');
        if (nameEl) nameEl.textContent = nom;
        if (priceEl) {
            var priceLine = formatPriceFcfa(prix);
            if (promo && parseFloat(promo) > 0) {
                priceLine += ' · Promo ' + formatPriceFcfa(promo);
            }
            priceEl.textContent = priceLine;
        }
    }

    function transferFileToInput(sourceInput, targetInput) {
        if (!sourceInput.files || !sourceInput.files.length) {
            return;
        }
        try {
            var dt = new DataTransfer();
            dt.items.add(sourceInput.files[0]);
            targetInput.files = dt.files;
        } catch (e) {
            /* ignore */
        }
    }

    function createVarianteItemFromModal(modal, idx, includeIdField) {
        var nom = (modal.querySelector('#variante-modal-nom') || {}).value || '';
        nom = nom.trim();
        var prix = (modal.querySelector('#variante-modal-prix') || {}).value || '';
        var promo = (modal.querySelector('#variante-modal-prix-promo') || {}).value || '';
        var fileInput = modal.querySelector('#variante-modal-image');
        if (!nom) {
            alert('Indiquez un nom pour la variante.');
            return null;
        }
        if (prix === '' || isNaN(parseFloat(prix))) {
            alert('Indiquez un prix valide pour la variante.');
            return null;
        }
        if (!fileInput || !fileInput.files || !fileInput.files[0]) {
            alert('Choisissez une image pour la variante.');
            if (fileInput) {
                fileInput.focus();
            }
            return null;
        }
        if (!fileInput.files[0].type || fileInput.files[0].type.indexOf('image/') !== 0) {
            alert('Le fichier doit être une image (JPG, PNG, GIF ou WEBP).');
            fileInput.focus();
            return null;
        }

        var thumbSrc = URL.createObjectURL(fileInput.files[0]);

        var item = document.createElement('div');
        item.className = 'variante-item';
        item.dataset.index = String(idx);

        var idField = includeIdField ? '<input type="hidden" name="variantes_id[]" value="">' : '';
        item.innerHTML =
            buildSummaryHtml(nom, prix, promo, thumbSrc) +
            '<div class="variante-item__fields" hidden>' +
            idField +
            '<input type="text" name="variantes_nom[]" class="variante-nom">' +
            '<input type="number" name="variantes_prix[]" class="variante-prix">' +
            '<input type="number" name="variantes_prix_promo[]" class="variante-prix-promo">' +
            '<input type="file" name="variantes_image[]" accept="image/*" class="variante-image-input">' +
            '</div>';

        var fields = item.querySelector('.variante-item__fields');
        fields.querySelector('.variante-nom').value = nom;
        fields.querySelector('.variante-prix').value = prix;
        fields.querySelector('.variante-prix-promo').value = promo;
        var hiddenFile = fields.querySelector('.variante-image-input');
        if (fileInput && hiddenFile) {
            transferFileToInput(fileInput, hiddenFile);
        }

        attachSummaryText(item.querySelector('.variante-summary'), nom, prix, promo);
        return item;
    }

    function convertLegacyItemToSummary(item) {
        if (item.querySelector('.variante-summary')) {
            return;
        }
        var row = item.querySelector('.variante-row');
        if (!row) {
            return;
        }
        var nomInput = row.querySelector('.variante-nom');
        var prixInput = row.querySelector('.variante-prix');
        var promoInput = row.querySelector('.variante-prix-promo');
        var img = row.querySelector('.variante-preview-img');
        var nom = nomInput ? nomInput.value.trim() : '';
        var prix = prixInput ? prixInput.value : '';
        var promo = promoInput ? promoInput.value : '';
        var thumb = img && img.src && img.style.display !== 'none' && !img.hidden ? img.src : '';

        var fieldsWrap = document.createElement('div');
        fieldsWrap.className = 'variante-item__fields';
        fieldsWrap.hidden = true;
        while (row.firstChild) {
            fieldsWrap.appendChild(row.firstChild);
        }
        row.remove();

        var summary = document.createElement('div');
        summary.innerHTML = buildSummaryHtml(nom, prix, promo, thumb);
        var summaryEl = summary.firstElementChild;
        attachSummaryText(summaryEl, nom, prix, promo);
        item.insertBefore(summaryEl, item.firstChild);
        item.appendChild(fieldsWrap);
    }

    function initProduitVariantes(options) {
        options = options || {};
        var includeIdField = !!options.includeIdField;

        var container = document.getElementById('variantes-container');
        var btnAdd = document.getElementById('btn-add-variante');
        var modal = document.getElementById('variante-add-modal');
        if (!container || !btnAdd || !modal) {
            return;
        }

        var idx = container.querySelectorAll('.variante-item').length;
        container.querySelectorAll('.variante-item').forEach(convertLegacyItemToSummary);

        var backdrop = document.getElementById('variante-add-modal-backdrop');
        var btnClose = document.getElementById('variante-add-modal-close');
        var btnCancel = document.getElementById('variante-add-modal-cancel');
        var btnConfirm = document.getElementById('variante-add-modal-confirm');
        var modalFile = document.getElementById('variante-modal-image');

        if (modalFile) {
            modalFile.addEventListener('change', function () {
                previewModalImage(modalFile);
            });
        }

        btnAdd.addEventListener('click', function () {
            resetModal(modal);
            openModal(modal);
        });

        [backdrop, btnClose, btnCancel].forEach(function (el) {
            if (!el) return;
            el.addEventListener('click', function () {
                closeModal(modal);
            });
        });

        modal.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal(modal);
            }
        });

        if (btnConfirm) {
            btnConfirm.addEventListener('click', function () {
                var item = createVarianteItemFromModal(modal, idx++, includeIdField);
                if (!item) {
                    return;
                }
                container.appendChild(item);
                closeModal(modal);
            });
        }

        container.addEventListener('click', function (e) {
            var btn = e.target.closest('.variante-summary__remove, .btn-remove-variante');
            if (!btn) {
                return;
            }
            var item = btn.closest('.variante-item');
            if (item) {
                item.remove();
            }
        });
    }

    window.initProduitVariantes = initProduitVariantes;
})();
