<?php
/**
 * Modal invité — nom + téléphone avant commande sans compte.
 */
require_once dirname(__DIR__) . '/site_brand.php';
$guest_info = function_exists('guest_checkout_get_info') ? guest_checkout_get_info() : ['nom' => '', 'telephone' => ''];
?>
<div class="guest-checkout-modal" id="guestCheckoutModal" role="dialog" aria-modal="true"
    aria-labelledby="guestCheckoutTitle" aria-hidden="true" hidden>
    <div class="guest-checkout-modal__backdrop" data-guest-checkout-close tabindex="-1"></div>
    <div class="guest-checkout-modal__panel" role="document">
        <header class="guest-checkout-modal__hero">
            <button type="button" class="guest-checkout-modal__close" data-guest-checkout-close aria-label="Fermer">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
            <span class="guest-checkout-modal__hero-logo">
                <img src="<?php echo htmlspecialchars(site_brand_logo(), ENT_QUOTES, 'UTF-8'); ?>"
                    alt="<?php echo htmlspecialchars(site_brand_name(), ENT_QUOTES, 'UTF-8'); ?>">
            </span>
            <h2 class="guest-checkout-modal__title" id="guestCheckoutTitle">Commander sans compte</h2>
        </header>

        <div class="guest-checkout-modal__body">
            <form id="guestCheckoutForm" class="guest-checkout-modal__form" novalidate>
                <div class="guest-checkout-field">
                    <label class="guest-checkout-field__label" for="guest-checkout-nom">Nom complet</label>
                    <div class="guest-checkout-field__control">
                        <span class="guest-checkout-field__icon" aria-hidden="true"><i class="fas fa-user"></i></span>
                        <input type="text" id="guest-checkout-nom" name="guest_nom" autocomplete="name" required
                            maxlength="120" placeholder="Ex. Aminata Diop"
                            value="<?php echo htmlspecialchars($guest_info['nom'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>

                <div class="guest-checkout-field">
                    <label class="guest-checkout-field__label" for="guest-checkout-telephone">Téléphone</label>
                    <div class="guest-checkout-field__control guest-checkout-field__control--tel">
                        <div class="input-wrapper input-wrapper--intl-tel guest-checkout-intl">
                            <input type="tel" id="guest-checkout-telephone" name="guest_telephone" autocomplete="tel"
                                required placeholder="77 123 45 67"
                                data-initial-phone="<?php echo htmlspecialchars($guest_info['telephone'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                    <p class="guest-checkout-field__hint">Indicatif détecté automatiquement — modifiable si besoin.</p>
                </div>

                <p class="guest-checkout-modal__error" id="guestCheckoutError" role="alert" hidden></p>

                <div class="guest-checkout-modal__actions">
                    <button type="submit" class="guest-checkout-btn guest-checkout-btn--primary" id="guestCheckoutSubmit">
                        <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>
                        Passer la commande
                    </button>
                    <button type="button" class="guest-checkout-btn guest-checkout-btn--ghost" data-guest-checkout-close>
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
