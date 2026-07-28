/**
 * Capture GPS optionnelle avant soumission formulaire auth.
 */
(function () {
  function ensureHidden(form, name) {
    var el = form.querySelector('input[name="' + name + '"]');
    if (!el) {
      el = document.createElement('input');
      el.type = 'hidden';
      el.name = name;
      form.appendChild(el);
    }
    return el;
  }

  function attachGeoCapture(form) {
    if (!form || form.dataset.geoCaptureAttached === '1') return;
    form.dataset.geoCaptureAttached = '1';

    form.addEventListener('submit', function (e) {
      var latEl = ensureHidden(form, 'user_latitude');
      var lngEl = ensureHidden(form, 'user_longitude');
      var accEl = ensureHidden(form, 'user_accuracy');

      if (latEl.value !== '' && lngEl.value !== '') {
        return;
      }

      if (!navigator.geolocation) {
        return;
      }

      e.preventDefault();
      var submitted = false;

      function doSubmit() {
        if (submitted) return;
        submitted = true;
        form.submit();
      }

      var timeout = setTimeout(doSubmit, 5000);

      navigator.geolocation.getCurrentPosition(
        function (pos) {
          clearTimeout(timeout);
          latEl.value = String(pos.coords.latitude);
          lngEl.value = String(pos.coords.longitude);
          if (pos.coords.accuracy != null) {
            accEl.value = String(pos.coords.accuracy);
          }
          doSubmit();
        },
        function () {
          clearTimeout(timeout);
          doSubmit();
        },
        { enableHighAccuracy: true, timeout: 4500, maximumAge: 120000 }
      );
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    ['loginFormPhone', 'loginFormEmail', 'inscriptionForm'].forEach(function (id) {
      var form = document.getElementById(id);
      attachGeoCapture(form);
    });
  });
})();
