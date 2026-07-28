/**
 * Checkout commande.php — modes livraison / retrait + carte GPS
 */
(function () {
  var cfg = window.COMMANDE_CHECKOUT || {};
  var DEFAULT_CENTER = [14.7167, -17.4677];
  var map = null;
  var marker = null;

  var form = document.getElementById('form-commande');
  if (!form) return;

  var modeInput = document.getElementById('mode_livraison');
  var tabRetrait = document.getElementById('tab-mode-retrait');
  var tabLivraison = document.getElementById('tab-mode-livraison');
  var panelLivraison = document.getElementById('panel-livraison');
  var retraitInfo = document.getElementById('retrait-info');
  var zoneSelect = document.getElementById('zone_livraison_id');
  var latInput = document.getElementById('delivery_latitude');
  var lngInput = document.getElementById('delivery_longitude');
  var btnUpdateLoc = document.getElementById('btn-update-location');
  var locStatus = document.getElementById('location-status');
  var locLabel = document.getElementById('location-label');
  var summaryMode = document.getElementById('summary-mode-label');
  var spanLivraison = document.getElementById('summary-livraison');
  var spanTotal = document.getElementById('summary-total');
  var panierTotal = parseFloat(cfg.panierTotal || 0);

  function formatNumber(n) {
    return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
  }

  function setMode(mode) {
    var isRetrait = mode === 'retrait';
    if (modeInput) modeInput.value = isRetrait ? 'retrait' : 'livraison';
    if (tabRetrait) tabRetrait.classList.toggle('is-active', isRetrait);
    if (tabLivraison) tabLivraison.classList.toggle('is-active', !isRetrait);
    if (panelLivraison) panelLivraison.hidden = isRetrait;
    if (retraitInfo) retraitInfo.hidden = !isRetrait;
    if (zoneSelect) zoneSelect.required = !isRetrait;
    if (summaryMode) {
      summaryMode.textContent = isRetrait ? 'Retrait sur place' : 'Livraison';
    }
    updateTotaux();
    if (!isRetrait && map) {
      setTimeout(function () {
        map.invalidateSize();
      }, 120);
    }
  }

  function updateTotaux() {
    var isRetrait = modeInput && modeInput.value === 'retrait';
    var frais = 0;
    if (!isRetrait && zoneSelect) {
      var opt = zoneSelect.options[zoneSelect.selectedIndex];
      frais = opt && opt.dataset.prix ? parseFloat(opt.dataset.prix) : 0;
    }
    if (spanLivraison) spanLivraison.textContent = formatNumber(frais) + ' FCFA';
    if (spanTotal) spanTotal.textContent = formatNumber(panierTotal + frais) + ' FCFA';
  }

  function setCoords(lat, lng, label) {
    if (latInput) latInput.value = lat;
    if (lngInput) lngInput.value = lng;
    if (locStatus) {
      locStatus.textContent = label || ('Position : ' + lat.toFixed(5) + ', ' + lng.toFixed(5));
      locStatus.classList.add('is-ok');
    }
    if (locLabel && label) locLabel.textContent = label;
    if (map && marker) {
      marker.setLatLng([lat, lng]);
      map.setView([lat, lng], Math.max(map.getZoom(), 15));
    }
  }

  function initMap() {
    var mapEl = document.getElementById('commande-map');
    if (!mapEl || typeof L === 'undefined') return;

    var lat = parseFloat(cfg.userLat);
    var lng = parseFloat(cfg.userLng);
    var hasUser = !isNaN(lat) && !isNaN(lng);
    var center = hasUser ? [lat, lng] : DEFAULT_CENTER;

    map = L.map(mapEl, { zoomControl: true }).setView(center, hasUser ? 15 : 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    marker = L.marker(center, { draggable: true }).addTo(map);
    if (hasUser) {
      setCoords(lat, lng, cfg.userLabel || '');
    }

    marker.on('dragend', function () {
      var pos = marker.getLatLng();
      setCoords(pos.lat, pos.lng, '');
    });
  }

  function captureGeolocation(saveToProfile) {
    if (!navigator.geolocation) {
      if (locStatus) locStatus.textContent = 'Géolocalisation non supportée par votre navigateur.';
      return;
    }
    if (locStatus) {
      locStatus.textContent = 'Capture de la position en cours…';
      locStatus.classList.remove('is-ok');
    }
    if (btnUpdateLoc) btnUpdateLoc.disabled = true;

    navigator.geolocation.getCurrentPosition(
      function (pos) {
        var lat = pos.coords.latitude;
        var lng = pos.coords.longitude;
        setCoords(lat, lng, '');

        if (saveToProfile) {
          fetch('/api/user/save-location.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
              latitude: lat,
              longitude: lng,
              accuracy: pos.coords.accuracy
            })
          })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              if (data && data.ok && data.label) {
                setCoords(lat, lng, data.label);
              }
            })
            .catch(function () {});
        }

        if (btnUpdateLoc) btnUpdateLoc.disabled = false;
      },
      function () {
        if (locStatus) locStatus.textContent = 'Impossible d\'obtenir votre position. Autorisez le GPS ou déplacez le marqueur sur la carte.';
        if (btnUpdateLoc) btnUpdateLoc.disabled = false;
      },
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
    );
  }

  if (tabRetrait) {
    tabRetrait.addEventListener('click', function () { setMode('retrait'); });
  }
  if (tabLivraison) {
    tabLivraison.addEventListener('click', function () { setMode('livraison'); });
  }
  if (zoneSelect) {
    zoneSelect.addEventListener('change', updateTotaux);
  }
  if (btnUpdateLoc) {
    btnUpdateLoc.addEventListener('click', function () {
      captureGeolocation(true);
    });
  }

  form.addEventListener('submit', function (e) {
    if (modeInput && modeInput.value === 'livraison') {
      if (!latInput || !lngInput || latInput.value === '' || lngInput.value === '') {
        e.preventDefault();
        if (locStatus) locStatus.textContent = 'Veuillez confirmer votre position (bouton « Mettre à jour la localisation » ou déplacez le marqueur).';
        return false;
      }
    }
  });

  setMode(modeInput && modeInput.value === 'retrait' ? 'retrait' : 'livraison');
  initMap();
  updateTotaux();
})();
