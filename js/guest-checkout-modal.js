/**
 * Modal commande invité — interception des formulaires panier.
 */
(function () {
  var cfg = window.YAYE_GUEST_CHECKOUT || { userConnected: true, hasInfo: true };
  if (cfg.userConnected) {
    return;
  }

  var modal = document.getElementById('guestCheckoutModal');
  var modalForm = document.getElementById('guestCheckoutForm');
  var errorEl = document.getElementById('guestCheckoutError');
  var nomInput = document.getElementById('guest-checkout-nom');
  var telInput = document.getElementById('guest-checkout-telephone');
  var pendingForm = null;
  var telIti = null;

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

  function openModal() {
    if (!modal) {
      return;
    }
    ensureIntlTel();
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('guest-checkout-open');
    if (nomInput) {
      nomInput.focus();
    }
  }

  function ensureIntlTel() {
    if (telIti || !telInput || typeof window.initAuthIntlTel !== 'function') {
      return;
    }
    telIti = window.initAuthIntlTel('guest-checkout-telephone');
    // Recalcule le padding gauche après réduction visuelle du bouton pays
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
    if (!modal) {
      return;
    }
    modal.hidden = true;
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

  function phoneLooksValid(value) {
    var digits = String(value || '').replace(/\D/g, '');
    return digits.length >= 8;
  }

  function applyGuestFieldsToForm(form, nom, telephone) {
    setHiddenField(form, 'guest_nom', nom);
    setHiddenField(form, 'guest_telephone', telephone);
  }

  function submitWithGuestInfo(form) {
    if (cfg.hasInfo && cfg.guestNom && cfg.guestTelephone) {
      applyGuestFieldsToForm(form, cfg.guestNom, cfg.guestTelephone);
      form.submit();
      return;
    }
    pendingForm = form;
    openModal();
  }

  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!isPanierForm(form)) {
      return;
    }

    if (form.querySelector('input[name="guest_nom"]') && form.querySelector('input[name="guest_telephone"]')) {
      return;
    }

    if (cfg.hasInfo && cfg.guestNom && cfg.guestTelephone) {
      applyGuestFieldsToForm(form, cfg.guestNom, cfg.guestTelephone);
      return;
    }

    event.preventDefault();
    submitWithGuestInfo(form);
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

  modal && modal.querySelectorAll('[data-guest-checkout-close]').forEach(function (btn) {
    btn.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && modal && !modal.hidden) {
      closeModal();
    }
  });
})();
