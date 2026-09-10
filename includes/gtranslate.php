<?php
/**
 * GTranslate — langue partagée boutique + admin (cookie googtrans).
 * Langues : français (défaut), anglais, chinois traditionnel.
 *
 * @see https://gtranslate.io
 */

if (!function_exists('gtranslate_render_widget')) {
    function gtranslate_render_widget(): void
    {
        static $widget_count = 0;
        $widget_count++;
        $id = $widget_count > 1 ? ' id="gtranslate-widget-' . $widget_count . '"' : '';
        echo '<div class="gtranslate-lang-wrapper nav-gtranslate-wrapper notranslate"' . $id
            . ' aria-label="Choisir la langue du site"></div>';
    }
}

if (!function_exists('gtranslate_render_core')) {
    function gtranslate_render_core(): void
    {
        if (defined('GTRANSLATE_CORE_LOADED')) {
            return;
        }
        define('GTRANSLATE_CORE_LOADED', true);
        ?>
<script>
window.gtranslateSettings = {
    default_language: 'fr',
    languages: ['fr', 'en', 'zh-TW'],
    wrapper_selector: '.gtranslate-lang-wrapper',
    native_language_names: true,
    flag_style: '3d',
    switcher_horizontal_position: 'inline',
    float_switcher_open_direction: 'bottom',
    url_structure: 'none'
};
</script>
<script>
(function () {
    function dedupeLangOptions() {
        document.querySelectorAll('.nav-lang-switcher .gt_options a, .admin-lang-switcher .gt_options a').forEach(function (link) {
            link.querySelectorAll('.gt-lang-label').forEach(function (node) {
                node.remove();
            });

            var codes = link.querySelectorAll('.gt-lang-code');
            for (var i = 1; i < codes.length; i++) {
                codes[i].remove();
            }

            var code = link.querySelector('.gt-lang-code');
            if (!code) {
                return;
            }

            var label = code.textContent.replace(/\s+/g, ' ').trim();
            Array.from(link.childNodes).forEach(function (node) {
                if (node.nodeType !== 3) {
                    return;
                }
                if (node.textContent.replace(/\s+/g, ' ').trim() === label) {
                    node.remove();
                }
            });
        });
    }

    function watchLangSwitcher() {
        document.querySelectorAll('.nav-lang-switcher .gtranslate-lang-wrapper, .admin-lang-switcher .gtranslate-lang-wrapper').forEach(function (wrapper) {
            dedupeLangOptions();
            var observer = new MutationObserver(dedupeLangOptions);
            observer.observe(wrapper, { childList: true, subtree: true, characterData: true });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', watchLangSwitcher);
    } else {
        watchLangSwitcher();
    }
})();
</script>
<script src="https://cdn.gtranslate.net/widgets/latest/float.js" defer></script>
        <?php
    }
}

if (!function_exists('gtranslate_render_assets')) {
    function gtranslate_render_assets(): void
    {
        gtranslate_render_widget();
        gtranslate_render_core();
    }
}
