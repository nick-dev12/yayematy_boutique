/**
 * Modal commande invité — interception des formulaires panier.
 */
(function () {
  var cfg = window.YAYE_GUEST_CHECKOUT || { userConnected: true, hasInfo: true };
  if (cfg.userConnected) {
    return;
  }

  var modal = null;
  var modalForm = null;
  var modalPanel = null;
  var errorEl = null;
  var nomInput = null;
  var telInput = null;
  var pendingForm = null;
  var telIti = null;
  var initialized = false;

  function refreshDomRefs() {
    modal = document.getElementById('guestCheckoutModal');
    modalForm = document.getElementById('guestCheckoutForm');
    modalPanel = modal ? modal.querySelector('.guest-checkout-modal__panel') : null;
    errorEl = document.getElementById('guestCheckoutError');
    nomInput = document.getElementById('guest-checkout-nom');
    telInput = document.getElementById('guest-checkout-telephone');
  }

  function isPanierForm(form) {
    if (!form || form.tagName !== 'FORM') {
      return false;
    }
    var action = (form.getAttribute('action') || '').toLowerCase();
    if (action.indexOf('add-to-panier.php') !== -1) {
      return true;
    }
    if (form.id === 'add-to-panier-form') {
      return true;
    }
    var actionInput = form.querySelector('input[name="action"][value="add_to_panier"]');
    return !!actionInput;
  }

  function setHiddenField(form, name, value) {
    var input = form.querySelector('input[name="' + name + '"]');
    if (!input) {
      input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      form.appendChild(input);
    }
    input.value = value;
  }

  function showError(message) {
    if (!errorEl) {
      return;
    }
    if (!message) {
      errorEl.hidden = true;
      errorEl.textContent = '';
      return;
    }
    errorEl.hidden = false;
    errorEl.textContent = message;
  }

  function phoneLooksValid(value) {
    var digits = String(value || '').replace(/\D/g, '');
    return digits.length >= 8;
  }

  function hasValidGuestFields(form) {
    var nom = form.querySelector('input[name="guest_nom"]');
    var tel = form.querySelector('input[name="guest_telephone"]');
    if (!nom || !tel) {
      return false;
    }
    return nom.value.trim().length >= 2 && phoneLooksValid(tel.value.trim());
  }

  function shouldInterceptForm(form) {
    if (!form || !isPanierForm(form)) {
      return false;
    }
    if (hasValidGuestFields(form)) {
      return false;
    }
    if (cfg.hasInfo && cfg.guestNom && cfg.guestTelephone) {
      applyGuestFieldsToForm(form, cfg.guestNom, cfg.guestTelephone);
      return false;
    }
    return true;
  }

  function positionModalOnProductPage() {
    if (!modal || !modalPanel) {
      return;
    }

    var wrapper = document.getElementById('produit-detail-wrapper');
    if (!wrapper || !document.body.classList.contains('page-produit')) {
      modal.classList.remove('guest-checkout-modal--anchored');
      modalPanel.style.removeProperty('--guest-checkout-panel-top');
      return;
    }

    modal.classList.add('guest-checkout-modal--anchored');
    var rect = wrapper.getBoundingClientRect();
    var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
    var panelHeight = modalPanel.offsetHeight || 320;
    var top = Math.max(12, rect.top);
    var maxTop = Math.max(12, viewportHeight - panelHeight - 12);
    if (top > maxTop) {
      top = maxTop;
    }
    modalPanel.style.setProperty('--guest-checkout-panel-top', top + 'px');
  }

  function redirectToGuestCheckoutRequired(form) {
    if (!form || !document.body.classList.contains('page-produit')) {
      return false;
    }
    var pidInput = form.querySelector('input[name="produit_id"]');
    var productId = pidInput ? String(pidInput.value || '').trim() : '';
    if (!productId) {
      return false;
    }
    var url = new URL(window.location.href);
    url.searchParams.set('id', productId);
    url.searchParams.set('guest_checkout', 'required');
    window.location.assign(url.toString());
    return true;
  }

  function openModal(form) {
    refreshDomRefs();
    if (!modal) {
      return redirectToGuestCheckoutRequired(form);
    }
    if (form) {
      pendingForm = form;
    }
    ensureIntlTel();
    positionModalOnProductPage();
    modal.hidden = false;
    modal.removeAttribute('hidden');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('guest-checkout-open');
    window.setTimeout(function () {
      if (nomInput) {
        nomInput.focus();
      }
    }, 50);
    return true;
  }

  function ensureIntlTel() {
    if (telIti || !telInput || typeof window.initAuthIntlTel !== 'function') {
      return;
    }
    telIti = window.initAuthIntlTel('guest-checkout-telephone');
    if (telIti && typeof telIti.setCountry === 'function') {
      try {
        var data = telIti.getSelectedCountryData();
        if (data && data.iso2) {
          telIti.setCountry(data.iso2);
        }
      } catch (e) {}
    }
  }

  function closeModal() {
    refreshDomRefs();
    if (!modal) {
      return;
    }
    modal.hidden = true;
    modal.setAttribute('hidden', 'hidden');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('guest-checkout-open');
    showError('');
    pendingForm = null;
  }

  function getE164() {
    if (!telIti) {
      return (telInput && telInput.value) ? telInput.value.trim() : '';
    }
    try {
      if (window.intlTelInput && window.intlTelInput.utils) {
        return telIti.getNumber(window.intlTelInput.utils.numberFormat.E164);
      }
    } catch (e) {}
    try {
      return telIti.getNumber();
    } catch (e2) {
      return (telInput && telInput.value) ? telInput.value.trim() : '';
    }
  }

  function applyGuestFieldsToForm(form, nom, telephone) {
    setHiddenField(form, 'guest_nom', nom);
    setHiddenField(form, 'guest_telephone', telephone);
  }

  function interceptPanierForm(form, event) {
    if (!shouldInterceptForm(form)) {
      return false;
    }
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }
    return openModal(form);
  }

  function bindPanierForm(form) {
    if (!form || form.dataset.guestCheckoutBound === '1') {
      return;
    }
    form.dataset.guestCheckoutBound = '1';
    form.addEventListener('submit', function (event) {
      interceptPanierForm(form, event);
    });
  }

  function bindAllPanierForms() {
    document.querySelectorAll('form').forEach(function (form) {
      if (isPanierForm(form)) {
        bindPanierForm(form);
      }
    });
  }

  function bindEvents() {
    if (initialized) {
      return;
    }
    initialized = true;
    refreshDomRefs();
    bindAllPanierForms();

    document.addEventListener('submit', function (event) {
      var form = event.target;
      if (interceptPanierForm(form, event)) {
        return;
      }
    }, true);

    document.addEventListener('click', function (event) {
      var btn = event.target.closest('#btn-add-panier, .btn-add-panier, .home-product-cart-btn, .home-product-add-btn');
      if (!btn) {
        return;
      }
      var form = btn.closest('form');
      if (!form || !isPanierForm(form)) {
        return;
      }
      bindPanierForm(form);
      if (!shouldInterceptForm(form)) {
        return;
      }
      event.preventDefault();
      event.stopPropagation();
      openModal(form);
    }, true);

    if (modalForm) {
      modalForm.addEventListener('submit', function (event) {
        event.preventDefault();
        showError('');

        var nom = nomInput ? nomInput.value.trim() : '';
        var telephone = getE164();

        if (nom.length < 2) {
          showError('Veuillez renseigner votre nom complet.');
          if (nomInput) {
            nomInput.focus();
          }
          return;
        }

        if (!phoneLooksValid(telephone)) {
          showError('Veuillez saisir un numéro de téléphone valide.');
          if (telInput) {
            telInput.focus();
          }
          return;
        }

        cfg.hasInfo = true;
        cfg.guestNom = nom;
        cfg.guestTelephone = telephone;

        if (!pendingForm) {
          closeModal();
          return;
        }

        applyGuestFieldsToForm(pendingForm, nom, telephone);
        var formToSubmit = pendingForm;
        closeModal();
        formToSubmit.submit();
      });
    }

    if (modal) {
      modal.querySelectorAll('[data-guest-checkout-close]').forEach(function (btn) {
        btn.addEventListener('click', closeModal);
      });
    }

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && modal && !modal.hidden) {
        closeModal();
      }
    });

    window.addEventListener('resize', function () {
      if (modal && !modal.hidden) {
        positionModalOnProductPage();
      }
    });

    window.addEventListener('scroll', function () {
      if (modal && !modal.hidden) {
        positionModalOnProductPage();
      }
    }, { passive: true });
  }

  function maybeOpenFromQuery() {
    try {
      var params = new URLSearchParams(window.location.search);
      if (params.get('guest_checkout') !== 'required') {
        return;
      }

      var productForm = document.getElementById('add-to-panier-form');
      if (productForm) {
        bindPanierForm(productForm);
        window.requestAnimationFrame(function () {
          openModal(productForm);
          positionModalOnProductPage();
        });
      }

      params.delete('guest_checkout');
      var cleanUrl = window.location.pathname;
      var rest = params.toString();
      if (rest) {
        cleanUrl += '?' + rest;
      }
      if (window.location.hash) {
        cleanUrl += window.location.hash;
      }
      window.history.replaceState({}, '', cleanUrl);
    } catch (e) {}
  }

  function init() {
    bindEvents();
    maybeOpenFromQuery();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
