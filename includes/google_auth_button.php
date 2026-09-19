<?php

/**

 * Boutons connexion sociale (Google + Apple).

 * Variables optionnelles :

 * - $google_auth_label / $social_auth_label : libellé du bouton Google

 * - $google_auth_type / $social_auth_type : auto|client

 * - $google_auth_redirect / $social_auth_redirect : URL relative après connexion

 * - $google_auth_position / $social_auth_position : top|bottom

 */

$social_auth_type = isset($social_auth_type) ? trim((string) $social_auth_type) : (isset($google_auth_type) ? trim((string) $google_auth_type) : 'auto');

if (!in_array($social_auth_type, ['auto', 'client', 'vendor'], true)) {

    $social_auth_type = 'auto';

}

if ($social_auth_type === 'vendor') {

    $social_auth_type = 'client';

}

$social_auth_redirect = isset($social_auth_redirect)

    ? (string) $social_auth_redirect

    : (isset($google_auth_redirect) ? (string) $google_auth_redirect : '');

$social_auth_position = (isset($social_auth_position) && $social_auth_position === 'bottom')

    ? 'bottom'

    : ((isset($google_auth_position) && $google_auth_position === 'bottom') ? 'bottom' : 'top');

$position_class = $social_auth_position === 'top' ? ' social-auth--top' : ' social-auth--bottom';

$social_auth_disabled = !empty($google_auth_disabled) || !empty($social_auth_disabled);



$social_auth_label = isset($social_auth_label)

    ? trim((string) $social_auth_label)

    : (isset($google_auth_label) ? trim((string) $google_auth_label) : 'Continuer avec Google');

if ($social_auth_label === '') {

    $social_auth_label = 'Continuer avec Google';

}



$is_inscription_label = (stripos($social_auth_label, 'inscription') !== false);

$google_loading_label = $is_inscription_label ? 'Inscription Google...' : 'Connexion Google...';

$social_auth_layout = isset($social_auth_layout)
    ? trim((string) $social_auth_layout)
    : (isset($google_auth_layout) ? trim((string) $google_auth_layout) : 'default');

if (!in_array($social_auth_layout, ['default', 'icons'], true)) {
    $social_auth_layout = 'default';
}

$layout_class = $social_auth_layout === 'icons' ? ' social-auth--icons' : '';

$apple_label = $is_inscription_label ? 'Continuer avec Apple pour s\'inscrire' : 'Continuer avec Apple';

?>

<div class="social-auth<?php echo $position_class . $layout_class; ?><?php echo $social_auth_disabled ? ' social-auth--disabled' : ''; ?>">

    <?php if ($social_auth_position === 'bottom'): ?>

        <div class="social-auth__divider"><span>ou</span></div>

    <?php endif; ?>



    <div class="social-auth__buttons">

        <button type="button"

            class="google-auth-btn"

            aria-label="<?php echo htmlspecialchars($social_auth_label, ENT_QUOTES, 'UTF-8'); ?>"

            data-social-auth-type="<?php echo htmlspecialchars($social_auth_type, ENT_QUOTES, 'UTF-8'); ?>"

            data-social-auth-redirect="<?php echo htmlspecialchars($social_auth_redirect, ENT_QUOTES, 'UTF-8'); ?>"

            data-social-auth-loading="<?php echo htmlspecialchars($google_loading_label, ENT_QUOTES, 'UTF-8'); ?>"

            <?php echo $social_auth_disabled ? 'disabled' : ''; ?>>

            <span class="google-auth-btn__icon" aria-hidden="true"><?php if ($social_auth_layout === 'icons'): ?>
                <svg class="google-auth-btn__logo" width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" focusable="false">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
            <?php else: ?>G<?php endif; ?></span>

            <span class="social-auth-btn__label<?php echo $social_auth_layout === 'icons' ? ' auth-sr-only' : ''; ?>"><?php echo htmlspecialchars($social_auth_label, ENT_QUOTES, 'UTF-8'); ?></span>

        </button>



        <button type="button"

            class="apple-auth-btn"

            aria-label="<?php echo htmlspecialchars($apple_label, ENT_QUOTES, 'UTF-8'); ?>"

            data-social-auth-type="<?php echo htmlspecialchars($social_auth_type, ENT_QUOTES, 'UTF-8'); ?>"

            data-social-auth-redirect="<?php echo htmlspecialchars($social_auth_redirect, ENT_QUOTES, 'UTF-8'); ?>"

            data-social-auth-loading="<?php echo $is_inscription_label ? 'Inscription Apple...' : 'Connexion Apple...'; ?>"

            <?php echo $social_auth_disabled ? 'disabled' : ''; ?>>

            <span class="apple-auth-btn__icon" aria-hidden="true"><i class="fab fa-apple"></i></span>

            <span class="social-auth-btn__label<?php echo $social_auth_layout === 'icons' ? ' auth-sr-only' : ''; ?>">Continuer avec Apple</span>

        </button>

    </div>



    <?php if ($social_auth_position === 'top'): ?>

        <div class="social-auth__divider"><span>ou</span></div>

    <?php endif; ?>

</div>

