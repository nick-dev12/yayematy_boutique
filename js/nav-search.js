/**
 * Recherche dynamique dans la barre de navigation (suggestions produits).
 */
(function () {
    'use strict';

    var DEBOUNCE_MS = 280;
    var MIN_CHARS = 2;
    var SUGGEST_LIMIT = 8;

    function esc(text) {
        var el = document.createElement('div');
        el.textContent = text == null ? '' : String(text);
        return el.innerHTML;
    }

    function formatFcfa(amount) {
        var n = Math.round(Number(amount) || 0);
        return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '\u202f');
    }

    function produitUrl(base, id) {
        var sep = base.indexOf('?') >= 0 ? '&' : '?';
        return base + sep + 'id=' + encodeURIComponent(String(id));
    }

    function buildApiUrl(form, query) {
        var api = form.getAttribute('data-search-api') || 'api/get_produits.php';
        var params = new URLSearchParams();
        params.set('recherche', query);
        params.set('limit', String(SUGGEST_LIMIT));
        params.set('offset', '0');
        var cat = form.querySelector('#nav-categorie');
        if (cat && cat.value) {
            params.set('categorie', cat.value);
        }
        return api + (api.indexOf('?') >= 0 ? '&' : '?') + params.toString();
    }

    function init() {
        var form = document.getElementById('nav-search-form');
        var input = document.getElementById('nav-search');
        var panel = document.getElementById('nav-search-suggestions');
        if (!form || !input || !panel) {
            return;
        }

        var produitBase = form.getAttribute('data-produit-base') || 'produit.php';
        var fallbackImage = form.getAttribute('data-fallback-image') || '/image/produit1.jpg';
        var debounceTimer = null;
        var fetchAbort = null;
        var activeIndex = -1;
        var lastItems = [];

        function closePanel() {
            panel.hidden = true;
            panel.innerHTML = '';
            activeIndex = -1;
            lastItems = [];
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
        }

        function setActiveIndex(index) {
            var options = panel.querySelectorAll('.nav-search-suggestion');
            for (var i = 0; i < options.length; i++) {
                options[i].classList.toggle('is-active', i === index);
                options[i].setAttribute('aria-selected', i === index ? 'true' : 'false');
            }
            activeIndex = index;
            if (index >= 0 && options[index]) {
                input.setAttribute('aria-activedescendant', options[index].id);
            } else {
                input.removeAttribute('aria-activedescendant');
            }
        }

        function renderLoading() {
            panel.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            panel.innerHTML =
                '<div class="nav-search-suggestions-status" role="status">' +
                '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Recherche…</div>';
        }

        function renderEmpty(query) {
            panel.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            panel.innerHTML =
                '<div class="nav-search-suggestions-status">Aucun produit pour « ' +
                esc(query) +
                ' »</div>' +
                '<button type="button" class="nav-search-suggestions-all">Voir le catalogue</button>';
            bindAllResultsButton();
        }

        function renderItems(items, query) {
            lastItems = items;
            var html = '';
            for (var i = 0; i < items.length; i++) {
                var p = items[i];
                var id = 'nav-search-opt-' + p.id;
                var img = esc(p.image_url || fallbackImage);
                var name = esc(p.nom);
                var cat = p.categorie_nom ? '<span class="nav-search-suggestion-cat">' + esc(p.categorie_nom) + '</span>' : '';
                var price = formatFcfa(p.prix_affichage != null ? p.prix_affichage : p.prix);
                html +=
                    '<a href="' +
                    esc(produitUrl(produitBase, p.id)) +
                    '" class="nav-search-suggestion" role="option" id="' +
                    id +
                    '" data-index="' +
                    i +
                    '" aria-selected="false">' +
                    '<span class="nav-search-suggestion-thumb"><img src="' +
                    img +
                    '" alt="" loading="lazy" decoding="async" onerror="this.src=\'' +
                    esc(fallbackImage) +
                    "'\"></span>" +
                    '<span class="nav-search-suggestion-body">' +
                    '<span class="nav-search-suggestion-name">' +
                    name +
                    '</span>' +
                    cat +
                    '</span>' +
                    '<span class="nav-search-suggestion-price notranslate" translate="no">' +
                    price +
                    ' <span class="nav-search-suggestion-currency">FCFA</span></span>' +
                    '</a>';
            }
            html +=
                '<button type="button" class="nav-search-suggestions-all">Tous les résultats pour « ' +
                esc(query) +
                ' »</button>';
            panel.innerHTML = html;
            panel.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            bindSuggestionClicks();
            bindAllResultsButton();
        }

        function bindAllResultsButton() {
            var btn = panel.querySelector('.nav-search-suggestions-all');
            if (!btn) {
                return;
            }
            btn.addEventListener('click', function () {
                closePanel();
                form.submit();
            });
        }

        function bindSuggestionClicks() {
            panel.querySelectorAll('.nav-search-suggestion').forEach(function (link) {
                link.addEventListener('mousedown', function (ev) {
                    ev.preventDefault();
                });
            });
        }

        function runSearch() {
            var query = input.value.trim();
            if (query.length < MIN_CHARS) {
                closePanel();
                return;
            }

            if (fetchAbort) {
                fetchAbort.abort();
            }
            fetchAbort = new AbortController();

            renderLoading();

            fetch(buildApiUrl(form, query), { signal: fetchAbort.signal, credentials: 'same-origin' })
                .then(function (res) {
                    return res.json();
                })
                .then(function (data) {
                    if (input.value.trim() !== query) {
                        return;
                    }
                    if (!data || !data.success || !data.produits || data.produits.length === 0) {
                        renderEmpty(query);
                        return;
                    }
                    renderItems(data.produits, query);
                })
                .catch(function (err) {
                    if (err && err.name === 'AbortError') {
                        return;
                    }
                    closePanel();
                });
        }

        function scheduleSearch() {
            if (debounceTimer) {
                clearTimeout(debounceTimer);
            }
            debounceTimer = setTimeout(runSearch, DEBOUNCE_MS);
        }

        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-controls', 'nav-search-suggestions');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('role', 'combobox');

        input.addEventListener('input', scheduleSearch);
        input.addEventListener('focus', function () {
            if (input.value.trim().length >= MIN_CHARS) {
                scheduleSearch();
            }
        });

        var catSelect = form.querySelector('#nav-categorie');
        if (catSelect) {
            catSelect.addEventListener('change', function () {
                if (input.value.trim().length >= MIN_CHARS) {
                    scheduleSearch();
                }
            });
        }

        input.addEventListener('keydown', function (ev) {
            var options = panel.querySelectorAll('.nav-search-suggestion');
            if (ev.key === 'Escape') {
                closePanel();
                return;
            }
            if (!panel.hidden && options.length) {
                if (ev.key === 'ArrowDown') {
                    ev.preventDefault();
                    var next = activeIndex < options.length - 1 ? activeIndex + 1 : 0;
                    setActiveIndex(next);
                    options[next].scrollIntoView({ block: 'nearest' });
                    return;
                }
                if (ev.key === 'ArrowUp') {
                    ev.preventDefault();
                    var prev = activeIndex > 0 ? activeIndex - 1 : options.length - 1;
                    setActiveIndex(prev);
                    options[prev].scrollIntoView({ block: 'nearest' });
                    return;
                }
                if (ev.key === 'Enter' && activeIndex >= 0 && options[activeIndex]) {
                    ev.preventDefault();
                    window.location.href = options[activeIndex].href;
                    return;
                }
            }
        });

        document.addEventListener('click', function (ev) {
            if (!form.contains(ev.target) && !panel.contains(ev.target)) {
                closePanel();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
