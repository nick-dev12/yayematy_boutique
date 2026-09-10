/**
 * Checkout commande.php — modes livraison / retrait + carte GPS
 */
(function () {
  function boot() {
    var cfg = window.COMMANDE_CHECKOUT || {};
    var DEFAULT_CENTER = [14.7167, -17.4677];
    var map = null;
    var marker = null;
    var isCapturing = false;
    var saveLocationTimer = null;
    var coordsArePlaceholder = true;
    var autoLocateAttempted = false;

    var form = document.getElementById('form-commande');
    if (!form) {
      return;
    }

    var modeInput = document.getElementById('mode_livraison');
    var tabRetrait = document.getElementById('tab-mode-retrait');
    var tabLivraison = document.getElementById('tab-mode-livraison');
    var panelLivraison = document.getElementById('panel-livraison');
    var retraitInfo = document.getElementById('retrait-info');
    var zoneSelect = document.getElementById('zone_livraison_id');
    var latInput = document.getElementById('delivery_latitude');
    var lngInput = document.getElementById('delivery_longitude');
    var locStatus = document.getElementById('location-status');
    var locLabel = document.getElementById('location-label');
    var mapBlock = document.getElementById('commande-map-block');
    var summaryMode = document.getElementById('summary-mode-label');
    var spanLivraison = document.getElementById('summary-livraison');
    var spanTotal = document.getElementById('summary-total');
    var panierTotal = parseFloat(cfg.panierTotal);
    if (isNaN(panierTotal)) {
      panierTotal = 0;
    }

    function formatNumber(n) {
      var value = isNaN(n) ? 0 : n;
      return Math.round(value).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }

    function hasStoredCoords() {
      if (coordsArePlaceholder) {
        return false;
      }
      if (!latInput || !lngInput) {
        return false;
      }
      if (latInput.value === '' || lngInput.value === '') {
        return false;
      }
      var lat = parseFloat(latInput.value);
      var lng = parseFloat(lngInput.value);
      return !isNaN(lat) && !isNaN(lng);
    }

    function isLivraisonMode() {
      return modeInput && modeInput.value === 'livraison';
    }

    function setLocationStatus(message, state) {
      if (!locStatus) {
        return;
      }
      locStatus.textContent = message;
      locStatus.classList.remove('is-ok', 'is-pending');
      if (state === 'ok') {
        locStatus.classList.add('is-ok');
      } else if (state === 'pending') {
        locStatus.classList.add('is-pending');
      }
    }

    function ensureFallbackCoords() {
      if (!hasStoredCoords()) {
        setCoords(
          DEFAULT_CENTER[0],
          DEFAULT_CENTER[1],
          'Touchez la carte ou déplacez le marqueur pour confirmer',
          true,
          true
        );
      }
    }

    function bindMapInteractions() {
      if (!map) {
        return;
      }

      map.on('click', function (event) {
        if (!event || !event.latlng) {
          return;
        }
        setCoords(
          event.latlng.lat,
          event.latlng.lng,
          'Adresse sélectionnée sur la carte',
          false
        );
      });
    }

    function getZonePrice() {
      if (!zoneSelect || zoneSelect.selectedIndex < 0) {
        return 0;
      }
      var opt = zoneSelect.options[zoneSelect.selectedIndex];
      if (!opt || !opt.value) {
        return 0;
      }
      var raw = opt.getAttribute('data-prix');
      if (raw === null || raw === '') {
        raw = opt.dataset ? opt.dataset.prix : '';
      }
      var prix = parseFloat(raw);
      return isNaN(prix) ? 0 : prix;
    }

    function updateTotaux() {
      var isRetrait = modeInput && modeInput.value === 'retrait';
      var frais = isRetrait ? 0 : getZonePrice();
      if (spanLivraison) {
        spanLivraison.textContent = formatNumber(frais) + ' FCFA';
      }
      if (spanTotal) {
        spanTotal.textContent = formatNumber(panierTotal + frais) + ' FCFA';
      }
    }

    function saveLocationToProfile(lat, lng, accuracy) {
      if (!cfg.canSaveLocation || !cfg.saveLocationUrl) {
        return Promise.resolve(null);
      }
      return fetch(cfg.saveLocationUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({
          latitude: lat,
          longitude: lng,
          accuracy: accuracy
        })
      })
        .then(function (r) { return r.json(); })
        .catch(function () { return null; });
    }

    function scheduleSaveFromMarker(lat, lng) {
      if (!cfg.canSaveLocation) {
        return;
      }
      if (saveLocationTimer) {
        window.clearTimeout(saveLocationTimer);
      }
      saveLocationTimer = window.setTimeout(function () {
        saveLocationToProfile(lat, lng, null).then(function (data) {
          if (data && data.ok && data.label) {
            setCoords(lat, lng, data.label);
          }
        });
      }, 500);
    }

    function setCoords(lat, lng, label, skipSave, isPlaceholder) {
      if (isPlaceholder !== true) {
        coordsArePlaceholder = false;
      }
      if (latInput) {
        latInput.value = String(lat);
      }
      if (lngInput) {
        lngInput.value = String(lng);
      }
      if (locStatus) {
        setLocationStatus(
          label || ('Position : ' + Number(lat).toFixed(5) + ', ' + Number(lng).toFixed(5)),
          'ok'
        );
      }
      if (locLabel) {
        locLabel.textContent = label || 'Position confirmée sur la carte';
      }
      if (map && marker) {
        marker.setLatLng([lat, lng]);
        if (!skipSave) {
          map.panTo([lat, lng], { animate: true, duration: 0.35 });
        }
      }
      if (!skipSave) {
        scheduleSaveFromMarker(lat, lng);
      }
    }

    function requestAutoLocation(force) {
      if (!isLivraisonMode()) {
        return;
      }
      if (isCapturing) {
        return;
      }
      if (!force && autoLocateAttempted && hasStoredCoords()) {
        return;
      }
      autoLocateAttempted = true;
      captureGeolocation(!!cfg.canSaveLocation, true);
    }

    function maybeAutoLocate(force) {
      requestAutoLocation(!!force);
    }

    function setMode(mode) {
      var isRetrait = mode === 'retrait';
      if (modeInput) {
        modeInput.value = isRetrait ? 'retrait' : 'livraison';
      }
      if (tabRetrait) {
        tabRetrait.classList.toggle('is-active', isRetrait);
        tabRetrait.setAttribute('aria-selected', isRetrait ? 'true' : 'false');
      }
      if (tabLivraison) {
        tabLivraison.classList.toggle('is-active', !isRetrait);
        tabLivraison.setAttribute('aria-selected', isRetrait ? 'false' : 'true');
      }
      if (panelLivraison) {
        panelLivraison.hidden = isRetrait;
      }
      if (retraitInfo) {
        retraitInfo.hidden = !isRetrait;
      }
      if (zoneSelect) {
        zoneSelect.required = !isRetrait;
      }
      if (summaryMode) {
        summaryMode.textContent = isRetrait ? 'Retrait sur place' : 'Livraison';
      }
      updateTotaux();
      if (!isRetrait && map) {
        window.setTimeout(function () {
          try {
            map.invalidateSize();
          } catch (e) { }
          requestAutoLocation(true);
        }, 150);
      }
    }

    function initMap() {
      var mapEl = document.getElementById('commande-map');
      if (!mapEl || typeof L === 'undefined') {
        return;
      }

      try {
        var lat = parseFloat(cfg.userLat);
        var lng = parseFloat(cfg.userLng);
        var hasSaved = !!cfg.hasSavedLocation && !isNaN(lat) && !isNaN(lng);
        var center = hasSaved ? [lat, lng] : DEFAULT_CENTER;

        map = L.map(mapEl, { zoomControl: true }).setView(center, hasSaved ? 15 : 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        marker = L.marker(center, { draggable: true }).addTo(map);

        if (hasSaved) {
          coordsArePlaceholder = false;
          setCoords(lat, lng, cfg.userLabel || 'Position enregistrée', true);
        } else {
          coordsArePlaceholder = true;
          if (latInput) {
            latInput.value = '';
          }
          if (lngInput) {
            lngInput.value = '';
          }
          setLocationStatus(
            'Autorisez la localisation pour afficher votre position exacte sur la carte.',
            'pending'
          );
          if (locLabel) {
            locLabel.textContent = 'Détection GPS en cours…';
          }
        }

        marker.on('dragend', function () {
          var pos = marker.getLatLng();
          setCoords(pos.lat, pos.lng, 'Adresse ajustée sur la carte');
        });

        bindMapInteractions();

        window.requestAnimationFrame(function () {
          window.requestAnimationFrame(function () {
            try {
              map.invalidateSize();
            } catch (e) { }
            if (isLivraisonMode()) {
              requestAutoLocation(true);
            }
          });
        });
      } catch (err) {
        map = null;
        marker = null;
        if (locStatus) {
          locStatus.textContent = 'Carte indisponible. Vous pouvez continuer en confirmant votre zone de livraison.';
        }
      }
    }

    function captureGeolocation(saveToProfile, isAuto, useLowAccuracy) {
      if (isCapturing) {
        return;
      }
      if (!navigator.geolocation) {
        ensureFallbackCoords();
        setLocationStatus(
          'Géolocalisation non disponible. Touchez la carte pour choisir votre adresse.',
          ''
        );
        return;
      }

      isCapturing = true;
      setLocationStatus(
        'Détection automatique de votre position…',
        'pending'
      );

      navigator.geolocation.getCurrentPosition(
        function (pos) {
          var lat = pos.coords.latitude;
          var lng = pos.coords.longitude;
          var accuracy = pos.coords.accuracy;
          var accuracyLabel = accuracy && !isNaN(accuracy)
            ? ' (précision ~' + Math.round(accuracy) + ' m)'
            : '';
          setCoords(lat, lng, 'Position GPS détectée' + accuracyLabel, !saveToProfile);

          if (map) {
            try {
              map.setView([lat, lng], 17, { animate: true });
            } catch (e) { }
          }

          if (saveToProfile) {
            saveLocationToProfile(lat, lng, accuracy).then(function (data) {
              if (data && data.ok && data.label) {
                setCoords(lat, lng, data.label, true);
              }
            });
          }

          isCapturing = false;
        },
        function (err) {
          if (!useLowAccuracy) {
            isCapturing = false;
            captureGeolocation(saveToProfile, isAuto, true);
            return;
          }

          if (!hasStoredCoords()) {
            ensureFallbackCoords();
          }
          var denied = err && err.code === 1;
          setLocationStatus(
            denied
              ? 'Accès à la localisation refusé. Touchez la carte pour indiquer votre adresse.'
              : 'GPS indisponible. Touchez la carte ou déplacez le marqueur pour confirmer votre adresse.',
            ''
          );
          if (locLabel && !hasStoredCoords()) {
            locLabel.textContent = 'Touchez la carte pour confirmer votre adresse';
          }
          isCapturing = false;
        },
        {
          enableHighAccuracy: !useLowAccuracy,
          timeout: useLowAccuracy ? 10000 : 15000,
          maximumAge: isAuto ? 0 : 60000
        }
      );
    }

    if (tabRetrait) {
      tabRetrait.addEventListener('click', function (e) {
        e.preventDefault();
        setMode('retrait');
      });
    }
    if (tabLivraison) {
      tabLivraison.addEventListener('click', function (e) {
        e.preventDefault();
        setMode('livraison');
      });
    }
    if (zoneSelect) {
      zoneSelect.addEventListener('change', updateTotaux);
      zoneSelect.addEventListener('input', updateTotaux);
    }

    form.addEventListener('submit', function (e) {
      if (modeInput && modeInput.value === 'livraison') {
        if (!latInput || !lngInput || latInput.value === '' || lngInput.value === '') {
          e.preventDefault();
          ensureFallbackCoords();
          setLocationStatus(
            'Veuillez confirmer votre adresse sur la carte.',
            ''
          );
          maybeAutoLocate(true);
          return false;
        }
      }
    });

    var initialMode = modeInput && modeInput.value === 'retrait' ? 'retrait' : 'livraison';
    if (!modeInput && cfg.defaultMode) {
      initialMode = cfg.defaultMode === 'retrait' ? 'retrait' : 'livraison';
    }
    setMode(initialMode);
    initMap();
    updateTotaux();

    window.addEventListener('load', function () {
      if (!map) {
        return;
      }
      try {
        map.invalidateSize();
      } catch (e) { }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
