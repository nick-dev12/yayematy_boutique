/**
 * Panier — mise à jour dynamique des quantités et totaux.
 */
(function () {
  var syncTimers = new WeakMap();

  function formatFcfa(amount) {
    var value = Math.round(Number(amount) || 0);
    return value.toLocaleString('fr-FR') + ' FCFA';
  }

  function clampQuantity(input) {
    var min = parseInt(input.getAttribute('min'), 10) || 1;
    var max = parseInt(input.getAttribute('max'), 10) || min;
    var value = parseInt(input.value, 10) || min;
    if (value < min) {
      value = min;
    }
    if (value > max) {
      value = max;
    }
    input.value = String(value);
    return value;
  }

  function getCard(form) {
    return form ? form.closest('.panier-card') : null;
  }

  function getUnitPrice(card) {
    if (!card) {
      return 0;
    }
    return parseFloat(card.getAttribute('data-unit-price') || '0') || 0;
  }

  function setLineTotalElement(lineEl, amount) {
    if (!lineEl) {
      return;
    }
    var value = Math.round(Number(amount) || 0).toLocaleString('fr-FR');
    var amountEl = lineEl.querySelector('.price-amount');
    if (amountEl) {
      amountEl.textContent = value;
      return;
    }
    lineEl.textContent = formatFcfa(amount);
  }

  function updateLineTotal(card) {
    if (!card) {
      return;
    }
    var input = card.querySelector('.quantite-input');
    var lineEl = card.querySelector('[data-line-total]');
    if (!input || !lineEl) {
      return;
    }
    var qty = clampQuantity(input);
    var total = getUnitPrice(card) * qty;
    setLineTotalElement(lineEl, total);
  }

  function updateSummaryTotals() {
    var totalArticles = 0;
    var grandTotal = 0;

    document.querySelectorAll('.panier-card').forEach(function (card) {
      var input = card.querySelector('.quantite-input');
      if (!input) {
        return;
      }
      var qty = clampQuantity(input);
      totalArticles += qty;
      grandTotal += getUnitPrice(card) * qty;
    });

    var heroCount = document.getElementById('panier-articles-count');
    var summaryTotal = document.getElementById('panier-summary-total');
    var navBadge = document.querySelector('.bottom-nav-badge');

    if (heroCount) {
      heroCount.textContent = String(totalArticles);
    }
    if (summaryTotal) {
      summaryTotal.textContent = formatFcfa(grandTotal);
    }
    if (navBadge) {
      navBadge.textContent = totalArticles > 9 ? '9+' : String(totalArticles);
      navBadge.hidden = totalArticles <= 0;
    }
  }

  function refreshCardUI(form) {
    var card = getCard(form);
    updateLineTotal(card);
    updateSummaryTotals();
  }

  function showFlash(message, type) {
    if (!message) {
      return;
    }
    var existing = document.querySelector('.panier-flash--live');
    if (existing) {
      existing.remove();
    }

    var flash = document.createElement('div');
    flash.className = 'panier-flash panier-flash--' + (type || 'error') + ' panier-flash--live';
    flash.setAttribute('role', 'status');
    var icon = document.createElement('i');
    icon.className = 'fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle');
    icon.setAttribute('aria-hidden', 'true');
    var text = document.createElement('span');
    text.textContent = message;
    flash.appendChild(icon);
    flash.appendChild(text);

    var hub = document.querySelector('.panier-hub');
    var hero = document.querySelector('.panier-hero');
    if (hub && hero && hero.nextElementSibling) {
      hub.insertBefore(flash, hero.nextElementSibling);
    } else if (hub) {
      hub.prepend(flash);
    }

    window.setTimeout(function () {
      flash.remove();
    }, 4000);
  }

  function applyServerTotals(data) {
    if (!data || !data.success) {
      return;
    }

    var card = document.querySelector('.panier-card[data-item-id="' + data.panier_id + '"]');
    if (card) {
      var input = card.querySelector('.quantite-input');
      if (input && typeof data.quantite !== 'undefined') {
        input.value = String(data.quantite);
      }
      var lineEl = card.querySelector('[data-line-total]');
      if (lineEl && typeof data.line_total !== 'undefined') {
        setLineTotalElement(lineEl, data.line_total);
      }
      card.classList.remove('is-syncing');
    }

    var heroCount = document.getElementById('panier-articles-count');
    var summaryTotal = document.getElementById('panier-summary-total');
    var navBadge = document.querySelector('.bottom-nav-badge');

    if (heroCount) {
      heroCount.textContent = String(data.nombre_total_articles || 0);
    }
    if (summaryTotal) {
      summaryTotal.textContent = formatFcfa(data.panier_total || 0);
    }
    if (navBadge) {
      var count = Number(data.nombre_total_articles || 0);
      navBadge.textContent = count > 9 ? '9+' : String(count);
      navBadge.hidden = count <= 0;
    }
  }

  function syncQuantity(form) {
    if (!form) {
      return;
    }

    var card = getCard(form);
    if (card) {
      card.classList.add('is-syncing');
    }

    var formData = new FormData(form);
    formData.append('ajax', '1');

    fetch(window.location.pathname + window.location.search, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (!data || !data.success) {
          throw new Error((data && data.message) ? data.message : 'Mise à jour impossible.');
        }
        applyServerTotals(data);
      })
      .catch(function (error) {
        if (card) {
          card.classList.remove('is-syncing');
        }
        showFlash(error.message || 'Erreur lors de la mise à jour.', 'error');
      });
  }

  function scheduleSync(form) {
    var previous = syncTimers.get(form);
    if (previous) {
      window.clearTimeout(previous);
    }
    var timer = window.setTimeout(function () {
      syncQuantity(form);
      syncTimers.delete(form);
    }, 280);
    syncTimers.set(form, timer);
  }

  function bindForm(form) {
    var input = form.querySelector('.quantite-input');
    var decreaseBtn = form.querySelector('.decrease-btn');
    var increaseBtn = form.querySelector('.increase-btn');
    if (!input) {
      return;
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      refreshCardUI(form);
      scheduleSync(form);
    });

    if (decreaseBtn) {
      decreaseBtn.addEventListener('click', function () {
        var value = parseInt(input.value, 10) || 1;
        if (value > 1) {
          input.value = String(value - 1);
          refreshCardUI(form);
          scheduleSync(form);
        }
      });
    }

    if (increaseBtn) {
      increaseBtn.addEventListener('click', function () {
        var max = parseInt(input.getAttribute('max'), 10) || 1;
        var value = parseInt(input.value, 10) || 1;
        if (value < max) {
          input.value = String(value + 1);
          refreshCardUI(form);
          scheduleSync(form);
        }
      });
    }

    input.addEventListener('change', function () {
      clampQuantity(input);
      refreshCardUI(form);
      scheduleSync(form);
    });

    input.addEventListener('blur', function () {
      clampQuantity(input);
      refreshCardUI(form);
      scheduleSync(form);
    });
  }

  document.querySelectorAll('[data-panier-update]').forEach(bindForm);
})();
