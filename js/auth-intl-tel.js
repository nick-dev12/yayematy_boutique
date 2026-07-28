/**
 * Indicatif téléphonique international (intl-tel-input) — pages auth Yaye Maty.
 */
(function () {
  function getGeoCountryCode() {
    var meta = document.querySelector('meta[name="auth-geo-country"]');
    var code = meta ? String(meta.getAttribute('content') || '').trim().toLowerCase() : '';
    return /^[a-z]{2}$/.test(code) ? code : 'sn';
  }

  function normalizeInitialPhone(raw) {
    var value = String(raw || '').trim().replace(/[^\d+]/g, '');
    if (value === '') {
      return '';
    }
    if (value.charAt(0) !== '+') {
      value = '+' + value;
    }
    return value;
  }

  function applyInitialPhone(iti, input, raw) {
    var normalized = normalizeInitialPhone(raw);
    input.value = '';

    if (normalized === '') {
      return;
    }

    try {
      iti.setNumber(normalized);
    } catch (e) {
      input.value = raw;
      return;
    }

    var current = String(input.value || '').trim();
    if (current === '' || current.charAt(0) === '+' || /^\d{9,}$/.test(current.replace(/\s/g, ''))) {
      try {
        var country = iti.getSelectedCountryData();
        var dialCode = country && country.dialCode ? String(country.dialCode) : '';
        var digits = normalized.replace(/\D/g, '');
        if (dialCode !== '' && digits.indexOf(dialCode) === 0) {
          input.value = digits.slice(dialCode.length);
        }
      } catch (e2) { }
    }
  }

  function getE164(iti) {
    try {
      if (
        typeof intlTelInput !== 'undefined' &&
        intlTelInput.utils &&
        typeof intlTelInput.utils.numberFormat !== 'undefined'
      ) {
        return iti.getNumber(intlTelInput.utils.numberFormat.E164);
      }
    } catch (e) { }
    try {
      return iti.getNumber();
    } catch (e2) {
      return '';
    }
  }

  function initAuthIntlTel(inputId) {
    var input = document.getElementById(inputId);
    if (!input || typeof window.intlTelInput === 'undefined') {
      return null;
    }

    var geoCountry = getGeoCountryCode();
    var preferred = [geoCountry, 'sn', 'ga', 'ci', 'fr', 'ml', 'tg', 'bf', 'ne', 'cm', 'bj'];
    var seen = {};
    preferred = preferred.filter(function (c) {
      if (seen[c]) return false;
      seen[c] = true;
      return true;
    });

    var iti = window.intlTelInput(input, {
      initialCountry: geoCountry,
      preferredCountries: preferred,
      separateDialCode: true,
      nationalMode: true,
      formatOnDisplay: true,
      strictMode: false
    });

    var initialRaw = input.getAttribute('data-initial-phone');
    if (initialRaw === null || initialRaw === '') {
      initialRaw = input.value;
    }
    applyInitialPhone(iti, input, initialRaw);

    var form = input.closest('form');
    if (form) {
      form.addEventListener('submit', function () {
        var n = getE164(iti);
        if (n) {
          input.value = n;
        }
      });
    }

    return iti;
  }

  window.initAuthIntlTel = initAuthIntlTel;
})();
