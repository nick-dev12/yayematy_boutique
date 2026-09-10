<?php
/**
 * Affichage prix FCFA — non traduisible (GTranslate / Google Translate).
 */

if (!function_exists('format_price_fcfa_html')) {
    /**
     * @param float|int|string $amount
     */
    function format_price_fcfa_html($amount, string $wrapper_class = 'price-current'): string
    {
        $wrapper_class = trim($wrapper_class);
        $classes = trim($wrapper_class . ' notranslate');
        $formatted = number_format((float) $amount, 0, ',', ' ');

        return sprintf(
            '<span class="%s" translate="no"><span class="price-amount">%s</span><span class="price-currency">FCFA</span></span>',
            htmlspecialchars($classes, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($formatted, ENT_QUOTES, 'UTF-8')
        );
    }
}
