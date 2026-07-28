/**
 * Recherche + filtre période — page livreurs admin (commandes uniquement).
 */
(function () {
    'use strict';

    function normalizeQuery(value) {
        return (value || '').trim().toLowerCase();
    }

    function todayYmd() {
        var t = new Date();
        return t.getFullYear() + '-' + String(t.getMonth() + 1).padStart(2, '0') + '-' + String(t.getDate()).padStart(2, '0');
    }

    function addDaysYmd(ymd, days) {
        var parts = ymd.split('-');
        var dt = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
        dt.setDate(dt.getDate() + days);
        return dt.getFullYear() + '-' + String(dt.getMonth() + 1).padStart(2, '0') + '-' + String(dt.getDate()).padStart(2, '0');
    }

    function firstDayOfMonthYmd(ymd) {
        var parts = ymd.split('-');
        return parts[0] + '-' + parts[1] + '-01';
    }

    function formatYmdFr(ymd) {
        if (!ymd) return '';
        var parts = ymd.split('-');
        if (parts.length !== 3) return ymd;
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    function presetRange(preset) {
        var today = todayYmd();
        if (preset === 'today') return { from: today, to: today, preset: 'today' };
        if (preset === 'week') return { from: addDaysYmd(today, -6), to: today, preset: 'week' };
        if (preset === 'month') return { from: firstDayOfMonthYmd(today), to: today, preset: 'month' };
        return { from: null, to: null, preset: 'all' };
    }

    function periodSummaryText(range) {
        if (!range.from && !range.to) return 'Période : toutes les dates';
        if (range.from === range.to) return 'Période : aujourd\'hui (' + formatYmdFr(range.from) + ')';
        return 'Période : du ' + formatYmdFr(range.from) + ' au ' + formatYmdFr(range.to);
    }

    function itemMatchesQuery(el, query) {
        if (!query) return true;
        var haystack = el.getAttribute('data-search') || '';
        return haystack.indexOf(query) !== -1;
    }

    function itemMatchesDate(el, dateFrom, dateTo) {
        if (!dateFrom && !dateTo) return true;
        var d = el.getAttribute('data-date');
        if (!d) return false;
        if (dateFrom && d < dateFrom) return false;
        if (dateTo && d > dateTo) return false;
        return true;
    }

    function initPeriodFilter(onChange) {
        var toggle = document.getElementById('livreur-period-toggle');
        var panel = document.getElementById('livreur-period-panel');
        var dateFromInput = document.getElementById('livreur-date-debut');
        var dateToInput = document.getElementById('livreur-date-fin');
        var applyBtn = document.getElementById('livreur-period-apply');
        var summaryEl = document.getElementById('livreur-period-summary');
        var presetButtons = panel ? panel.querySelectorAll('.livreur-hub-period-preset') : [];
        var range = presetRange('today');

        if (dateFromInput) dateFromInput.value = range.from || '';
        if (dateToInput) dateToInput.value = range.to || '';
        if (summaryEl) summaryEl.textContent = periodSummaryText(range);

        function setPresetActive(preset) {
            for (var i = 0; i < presetButtons.length; i++) {
                var btn = presetButtons[i];
                btn.classList.toggle('is-active', btn.getAttribute('data-preset') === preset);
            }
        }

        function applyRange(nextRange, closePanel) {
            range = nextRange;
            if (dateFromInput) dateFromInput.value = range.from || '';
            if (dateToInput) dateToInput.value = range.to || '';
            setPresetActive(range.preset || '');
            if (summaryEl) summaryEl.textContent = periodSummaryText(range);
            if (closePanel && panel && toggle) {
                panel.hidden = true;
                toggle.setAttribute('aria-expanded', 'false');
            }
            onChange(range);
        }

        if (toggle && panel) {
            toggle.addEventListener('click', function () {
                var open = panel.hidden;
                panel.hidden = !open;
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        }

        for (var p = 0; p < presetButtons.length; p++) {
            presetButtons[p].addEventListener('click', function () {
                applyRange(presetRange(this.getAttribute('data-preset') || 'today'), true);
            });
        }

        if (applyBtn) {
            applyBtn.addEventListener('click', function () {
                var from = dateFromInput ? dateFromInput.value : '';
                var to = dateToInput ? dateToInput.value : '';
                if (from && to && from > to) {
                    var tmp = from;
                    from = to;
                    to = tmp;
                    if (dateFromInput) dateFromInput.value = from;
                    if (dateToInput) dateToInput.value = to;
                }
                applyRange({ from: from || null, to: to || null, preset: '' }, true);
            });
        }

        return {
            getRange: function () { return range; }
        };
    }

    function init() {
        var cfg = window.LIVREUR_INDEX_UI || {};
        var searchInput = document.getElementById('livreur-search-input');
        var summaryEl = document.getElementById('livreur-search-summary');
        var listEl = document.getElementById('livreur-cmd-list');
        var noResults = document.getElementById('livreur-no-results-commandes');
        if (!listEl) return;

        var currentRange = { from: null, to: null };

        if (cfg.enablePeriod) {
            currentRange = presetRange('today');
            var periodApi = initPeriodFilter(function (range) {
                currentRange = range;
                applyFilters();
            });
            if (periodApi) currentRange = periodApi.getRange();
        } else {
            var today = todayYmd();
            currentRange = { from: today, to: today };
        }

        function getRows() {
            return listEl.querySelectorAll('.liv-cmd-card[data-date]');
        }

        function applyFilters() {
            var rows = getRows();
            var query = normalizeQuery(searchInput ? searchInput.value : '');
            var visible = 0;
            var total = rows.length;

            for (var i = 0; i < rows.length; i++) {
                var row = rows[i];
                var show = itemMatchesQuery(row, query) && itemMatchesDate(row, currentRange.from, currentRange.to);
                row.hidden = !show;
                if (show) visible++;
            }

            if (noResults) {
                noResults.hidden = visible > 0 || total === 0;
            }

            if (summaryEl) {
                if (query) {
                    summaryEl.innerHTML = '<strong>' + visible + '</strong> commande' + (visible > 1 ? 's' : '') + ' sur ' + total + ' pour « ' + (searchInput ? searchInput.value.trim() : '') + ' »';
                } else {
                    summaryEl.innerHTML = '<strong>' + visible + '</strong> commande' + (visible > 1 ? 's' : '') + ' affichée' + (visible > 1 ? 's' : '');
                }
            }
        }

        if (searchInput) {
            searchInput.addEventListener('input', applyFilters);
            searchInput.addEventListener('search', applyFilters);
        }

        applyFilters();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
