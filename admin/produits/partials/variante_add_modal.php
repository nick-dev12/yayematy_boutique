<div id="variante-add-modal"
    class="variante-modal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="variante-add-modal-title"
    aria-hidden="true"
    hidden>
    <button type="button" class="variante-modal__backdrop" id="variante-add-modal-backdrop" aria-label="Fermer"></button>
    <div class="variante-modal__panel">
        <header class="variante-modal__head">
            <h2 id="variante-add-modal-title"><i class="fas fa-layer-group" aria-hidden="true"></i> Nouvelle variante</h2>
            <button type="button" class="variante-modal__close" id="variante-add-modal-close" aria-label="Fermer">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </header>
        <div class="variante-modal__body">
            <div class="variante-row variante-row--modal">
                <div class="form-group variante-field">
                    <label for="variante-modal-nom">Nom de la variante</label>
                    <input type="text" id="variante-modal-nom" placeholder="Ex : Format familial" class="variante-nom" autocomplete="off">
                </div>
                <div class="variante-modal__row-2">
                    <div class="form-group variante-field">
                        <label for="variante-modal-prix">Prix (FCFA)</label>
                        <input type="number" id="variante-modal-prix" placeholder="Prix" min="0" step="0.01" class="variante-prix">
                    </div>
                    <div class="form-group variante-field">
                        <label for="variante-modal-prix-promo">Prix promo (optionnel)</label>
                        <input type="number" id="variante-modal-prix-promo" placeholder="Promo" min="0" step="0.01" class="variante-prix-promo">
                    </div>
                </div>
                <div class="form-group variante-field">
                    <label for="variante-modal-image">Image <span class="required">*</span></label>
                    <div class="variante-image-wrap">
                        <div class="variante-image-area">
                            <input type="file" id="variante-modal-image" accept="image/*" class="variante-image-input" required>
                            <span class="variante-image-label"><i class="fas fa-image"></i> Choisir une image</span>
                            <img class="variante-preview-img" src="" alt="" hidden>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <footer class="variante-modal__foot">
            <button type="button" class="btn-cancel" id="variante-add-modal-cancel">Annuler</button>
            <button type="button" class="btn-primary" id="variante-add-modal-confirm">
                <i class="fas fa-check" aria-hidden="true"></i> Ajouter la variante
            </button>
        </footer>
    </div>
</div>
