/**
 * Secours traduction (Chine / Google bloqué) — même UI GTranslate, pas de changement visuel.
 */
(function () {
    'use strict';

    function getGoogTransPair() {
        var match = document.cookie.match(/(?:^|;\s*)googtrans=([^;]+)/);
        if (!match) {
            return '';
        }
        var parts = decodeURIComponent(match[1]).split('/').filter(Boolean);
        if (parts.length < 2) {
            return '';
        }
        return parts[parts.length - 2] + '|' + parts[parts.length - 1];
    }

    function targetLangFromPair(pair) {
        if (!pair) {
            return '';
        }
        var bits = pair.split('|');
        return bits.length > 1 ? bits[1] : '';
    }

    function isTranslated() {
        var html = document.documentElement;
        if (html.classList.contains('translated-ltr') || html.classList.contains('translated-rtl')) {
            return true;
        }
        if (html.getAttribute('lang') && html.getAttribute('lang') !== 'fr') {
            return true;
        }
        return false;
    }

    function restoreWidgetTranslation() {
        var pair = getGoogTransPair();
        if (!pair || pair.indexOf('|zh-TW') === -1 && pair.indexOf('|zh-CN') === -1) {
            return;
        }
        var attempts = 0;
        function tick() {
            attempts += 1;
            if (typeof window.doGTranslate === 'function') {
                window.doGTranslate(pair);
            }
            if (attempts < 12 && !isTranslated()) {
                setTimeout(tick, 400);
            }
        }
        tick();
    }

    function shouldSkipNode(node) {
        var el = node.parentElement;
        if (!el) {
            return true;
        }
        if (el.closest('.notranslate, .nav-lang-switcher, .gt_float_switcher, script, style, noscript, textarea, input, select, code, pre')) {
            return true;
        }
        return false;
    }

    function collectTextNodes(root, limit) {
        var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null);
        var nodes = [];
        var n;
        while ((n = walker.nextNode())) {
            if (nodes.length >= limit) {
                break;
            }
            if (shouldSkipNode(n)) {
                continue;
            }
            var t = n.textContent.replace(/\s+/g, ' ').trim();
            if (t.length < 2) {
                continue;
            }
            nodes.push(n);
        }
        return nodes;
    }

    function runServerFallback(apiUrl, sl, tl) {
        var nodes = collectTextNodes(document.body, 40);
        if (!nodes.length) {
            return;
        }
        var originals = nodes.map(function (node) {
            return node.textContent;
        });

        fetch(apiUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ texts: originals, sl: sl, tl: tl }),
        })
            .then(function (res) {
                return res.json();
            })
            .then(function (data) {
                if (!data || !data.success || !Array.isArray(data.texts)) {
                    return;
                }
                data.texts.forEach(function (text, i) {
                    if (nodes[i] && typeof text === 'string' && text !== '') {
                        nodes[i].textContent = text;
                    }
                });
                document.documentElement.setAttribute('lang', tl);
            })
            .catch(function () {});
    }

    function maybeServerFallback(config) {
        var pair = getGoogTransPair();
        var tl = targetLangFromPair(pair);
        if (!tl || (tl !== 'zh-TW' && tl !== 'zh-CN')) {
            return;
        }
        if (isTranslated()) {
            return;
        }
        runServerFallback(config.apiUrl, config.sourceLang || 'fr', tl);
    }

    function init(config) {
        if (window.__YAYE_GT_SKIP_FALLBACK) {
            return;
        }
        if (!config || !config.apiUrl) {
            return;
        }
        if ((window.location.pathname || '').indexOf('/admin/') !== -1) {
            var adminLang = '';
            try {
                adminLang = sessionStorage.getItem('yaye_admin_gt_lang') || 'fr';
            } catch (e) {
                adminLang = 'fr';
            }
            if (adminLang === 'fr') {
                return;
            }
        }

        restoreWidgetTranslation();

        window.setTimeout(function () {
            if (!isTranslated()) {
                maybeServerFallback(config);
            }
        }, 4500);
    }

    window.YayeGtranslateFallback = { init: init };
})();
