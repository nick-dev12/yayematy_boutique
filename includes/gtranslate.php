<?php
/**
 * GTranslate — langue partagée boutique + admin (cookie googtrans).
 * Langues : français (défaut), anglais, chinois traditionnel.
 *
 * @see https://gtranslate.io
 */

if (!function_exists('gtranslate_is_admin_area')) {
    function gtranslate_is_admin_area(): bool
    {
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        return strpos($script, '/admin/') !== false;
    }
}

if (!function_exists('gtranslate_render_locale_guard')) {
    /**
     * Admin : français par défaut (ignore le cookie boutique).
     * Boutique : restaure le cookie boutique après une visite admin.
     */
    function gtranslate_render_locale_guard(): void
    {
        static $rendered = false;
        if ($rendered) {
            return;
        }
        $rendered = true;
        ?>
<script>
(function () {
    var GT_ADMIN_KEY = 'yaye_admin_gt_lang';
    var GT_SHOP_KEY = 'yaye_shop_gt_saved';
    var GT_SHOP_LANG_KEY = 'yaye_shop_lang';

    function isAdminPath() {
        return (window.location.pathname || '').indexOf('/admin/') !== -1;
    }

    function readGoogTransValue() {
        var match = document.cookie.match(/(?:^|;\s*)googtrans=([^;]+)/);
        return match ? decodeURIComponent(match[1]) : '';
    }

    function clearGoogTrans() {
        document.cookie = 'googtrans=;path=/;expires=Thu, 01 Jan 1970 00:00:00 GMT';
        document.cookie = 'googtrans=;path=/;max-age=0';
    }

    function setGoogTransPair(targetLang) {
        document.cookie = 'googtrans=' + encodeURIComponent('/fr/' + targetLang) + ';path=/;max-age=31536000';
    }

    function resetDomFrench() {
        document.documentElement.setAttribute('lang', 'fr');
        document.documentElement.classList.remove('translated-ltr', 'translated-rtl');
        try {
            localStorage.removeItem('__GT_TRANSLATE_LANGS');
        } catch (e) {}
    }

    function applyAdminLocale() {
        if (!isAdminPath()) {
            return;
        }

        var current = readGoogTransValue();
        if (current && sessionStorage.getItem(GT_SHOP_KEY) === null) {
            sessionStorage.setItem(GT_SHOP_KEY, current);
        }

        var adminLang = sessionStorage.getItem(GT_ADMIN_KEY) || 'fr';
        if (adminLang === 'fr') {
            clearGoogTrans();
            resetDomFrench();
            window.__YAYE_GT_SKIP_FALLBACK = true;
            return;
        }

        window.__YAYE_GT_SKIP_FALLBACK = false;
        setGoogTransPair(adminLang);
    }

    function restoreShopLocale() {
        if (isAdminPath()) {
            return;
        }
        var saved = sessionStorage.getItem(GT_SHOP_KEY);
        if (!saved) {
            return;
        }
        document.cookie = 'googtrans=' + encodeURIComponent(saved) + ';path=/;max-age=31536000';
        sessionStorage.removeItem(GT_SHOP_KEY);
    }

    function readShopLangChoice() {
        try {
            return localStorage.getItem(GT_SHOP_LANG_KEY) || '';
        } catch (e) {
            return '';
        }
    }

    function saveShopLangChoice(lang) {
        try {
            localStorage.setItem(GT_SHOP_LANG_KEY, lang || 'fr');
        } catch (e) {}
    }

    /** Boutique : français par défaut tant qu'aucune autre langue n'est choisie explicitement. */
    function enforceShopFrenchDefault() {
        if (isAdminPath()) {
            return;
        }
        var chosen = readShopLangChoice();
        if (!chosen || chosen === 'fr') {
            clearGoogTrans();
            resetDomFrench();
            window.__YAYE_GT_SKIP_FALLBACK = true;
            return;
        }
        window.__YAYE_GT_SKIP_FALLBACK = false;
        setGoogTransPair(chosen);
    }

    applyAdminLocale();
    restoreShopLocale();
    enforceShopFrenchDefault();

    document.addEventListener('click', function (event) {
        var adminLink = event.target.closest(
            '.admin-sidebar .gt_options a[data-gt-lang], .admin-login-lang.nav-lang-switcher .gt_options a[data-gt-lang]'
        );
        if (adminLink) {
            var lang = adminLink.getAttribute('data-gt-lang') || 'fr';
            sessionStorage.setItem(GT_ADMIN_KEY, lang);
            if (lang === 'fr') {
                window.__YAYE_GT_SKIP_FALLBACK = true;
            } else {
                window.__YAYE_GT_SKIP_FALLBACK = false;
            }
            return;
        }

        var shopLink = event.target.closest(
            '.nav-gtranslate-wrapper a[data-gt-lang], .gt_float_switcher a[data-gt-lang], .gt_options a[data-gt-lang]'
        );
        if (!shopLink || isAdminPath()) {
            return;
        }
        var shopLang = shopLink.getAttribute('data-gt-lang') || 'fr';
        saveShopLangChoice(shopLang);
        if (shopLang === 'fr') {
            clearGoogTrans();
            resetDomFrench();
            window.__YAYE_GT_SKIP_FALLBACK = true;
        } else {
            window.__YAYE_GT_SKIP_FALLBACK = false;
        }
    }, true);
})();
</script>
        <?php
    }
}

if (!function_exists('gtranslate_render_widget')) {
    function gtranslate_render_widget(): void
    {
        gtranslate_render_locale_guard();

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

        if (!function_exists('public_url')) {
            require_once __DIR__ . '/site_url.php';
        }
        if (!function_exists('asset_url')) {
            require_once __DIR__ . '/asset_version.php';
        }

        $zh_tw_flag_url = public_url('/image/flags/cn.svg');
        $flags_base_url = public_url('/image/flags/gtranslate/');
        $float_js_url = asset_url('/js/vendor/gtranslate/float.js');
        $lib_js_url = asset_url('/js/vendor/gtranslate/lib.min.js');
        $fallback_js_url = asset_url('/js/gtranslate-fallback.js');
        $batch_api_url = public_url('/api/gtranslate_batch.php');
        ?>
<script>
window.gtranslateSettings = {
    default_language: 'fr',
    languages: ['fr', 'en', 'zh-TW'],
    wrapper_selector: '.gtranslate-lang-wrapper',
    native_language_names: true,
    flag_style: '2d',
    flags_location: <?php echo json_encode($flags_base_url, JSON_UNESCAPED_SLASHES); ?>,
    alt_flags: {
        'zh-TW': <?php echo json_encode($zh_tw_flag_url, JSON_UNESCAPED_SLASHES); ?>
    },
    switcher_horizontal_position: 'inline',
    float_switcher_open_direction: 'bottom',
    url_structure: 'none'
};
window.__GT_LIB_SRC = <?php echo json_encode($lib_js_url, JSON_UNESCAPED_SLASHES); ?>;
</script>
<script>
(function () {
    var ZH_TW_LABEL = '繁體中文';
    var ZH_TW_FLAG = <?php echo json_encode($zh_tw_flag_url, JSON_UNESCAPED_SLASHES); ?>;

    function syncChinaSelectedFlag() {
        var isZhTw = /(?:^|;\s*)googtrans=[^;]*\/zh-TW/i.test(document.cookie || '');
        document.querySelectorAll('.gt_float_switcher').forEach(function (sw) {
            sw.classList.toggle('gt-flag-china', isZhTw);
        });
    }

    function fixTraditionalChineseLang(link) {
        if (!link || link.getAttribute('data-gt-lang') !== 'zh-TW') {
            return;
        }

        var code = link.querySelector('.gt-lang-code');
        if (code) {
            code.textContent = ZH_TW_LABEL;
        }

        link.querySelectorAll('img').forEach(function (img) {
            img.src = ZH_TW_FLAG;
            img.alt = '';
        });
    }

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
                fixTraditionalChineseLang(link);
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

            fixTraditionalChineseLang(link);
        });

        syncChinaSelectedFlag();
    }

    var dedupeTimer = null;
    function scheduleDedupe() {
        if (dedupeTimer) {
            clearTimeout(dedupeTimer);
        }
        dedupeTimer = setTimeout(dedupeLangOptions, 80);
    }

    document.addEventListener('DOMContentLoaded', function () {
        scheduleDedupe();
        document.querySelectorAll('.nav-lang-switcher, .admin-lang-switcher').forEach(function (wrap) {
            wrap.addEventListener('click', scheduleDedupe, { passive: true });
        });
    });
})();
</script>
<script src="<?php echo htmlspecialchars($float_js_url, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
<script src="<?php echo htmlspecialchars($fallback_js_url, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
<script defer>
document.addEventListener('DOMContentLoaded', function () {
    if (window.__YAYE_GT_SKIP_FALLBACK) {
        return;
    }
    if (window.YayeGtranslateFallback && typeof window.YayeGtranslateFallback.init === 'function') {
        window.YayeGtranslateFallback.init({
            apiUrl: <?php echo json_encode($batch_api_url, JSON_UNESCAPED_SLASHES); ?>,
            sourceLang: 'fr'
        });
    }
});
</script>
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
