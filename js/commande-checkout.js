/**
 * Checkout commande.php — modes livraison / retrait + carte GPS
 */
(function () {
  function boot() {
    var cfg = window.COMMANDE_CHECKOUT || {};
    var DEFAULT_CENTER = [14.7167, -17.4677];
    var map = null;
    var marker = null;

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
    var btnUpdateLoc = document.getElementById('btn-update-location');
    var locStatus = document.getElementById('location-status');
    var locLabel = document.getElementById('location-label');
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
        setTimeout(function () {
          try {
            map.invalidateSize();
          } catch (e) { }
        }, 120);
      }
    }

    function setCoords(lat, lng, label) {
      if (latInput) {
        latInput.value = lat;
      }
      if (lngInput) {
        lngInput.value = lng;
      }
      if (locStatus) {
        locStatus.textContent = label || ('Position : ' + lat.toFixed(5) + ', ' + lng.toFixed(5));
        locStatus.classList.add('is-ok');
      }
      if (locLabel && label) {
        locLabel.textContent = label;
      }
      if (map && marker) {
        marker.setLatLng([lat, lng]);
        map.setView([lat, lng], Math.max(map.getZoom(), 15));
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
      } catch (err) {
        map = null;
        marker = null;
        if (locStatus) {
          locStatus.textContent = 'Carte indisponible. Vous pouvez continuer en confirmant votre zone de livraison.';
        }
      }
    }

    function captureGeolocation(saveToProfile) {
      if (!navigator.geolocation) {
        if (locStatus) {
          locStatus.textContent = 'Geolocalisation non supportee par votre navigateur.';
        }
        return;
      }
      if (locStatus) {
        locStatus.textContent = 'Capture de la position en cours…';
        locStatus.classList.remove('is-ok');
      }
      if (btnUpdateLoc) {
        btnUpdateLoc.disabled = true;
      }

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
              .catch(function () { });
          }

          if (btnUpdateLoc) {
            btnUpdateLoc.disabled = false;
          }
        },
        function () {
          if (locStatus) {
            locStatus.textContent = 'Impossible d\'obtenir votre position. Autorisez le GPS ou deplacez le marqueur sur la carte.';
          }
          if (btnUpdateLoc) {
            btnUpdateLoc.disabled = false;
          }
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
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
    if (btnUpdateLoc) {
      btnUpdateLoc.addEventListener('click', function () {
        captureGeolocation(true);
      });
    }

    form.addEventListener('submit', function (e) {
      if (modeInput && modeInput.value === 'livraison') {
        if (!latInput || !lngInput || latInput.value === '' || lngInput.value === '') {
          e.preventDefault();
          if (locStatus) {
            locStatus.textContent = 'Veuillez confirmer votre position (bouton « Mettre a jour la localisation » ou deplacez le marqueur).';
          }
          return false;
        }
      }
    });

    setMode(modeInput && modeInput.value === 'retrait' ? 'retrait' : 'livraison');
    initMap();
    updateTotaux();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
