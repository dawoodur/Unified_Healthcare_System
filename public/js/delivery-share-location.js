/**
 * Delivery agent side of live tracking: shares this device's position with the
 * patient while an order is out for delivery.
 *
 * Plain IIFE, no build step — same convention as public/js/blood-countdown.js
 * and booking-calendar.js. Each out-for-delivery card on /delivery/my-deliveries
 * carries its own [data-delivery-share] panel, so one page can be sharing for
 * several orders at once, independently.
 *
 * Two sources, both posted through the same endpoint and always labelled:
 *   - 'gps'       — navigator.geolocation.watchPosition, the real thing.
 *   - 'simulated' — the demo button, which walks a straight line from the
 *                   pharmacy to the patient's pinned door. A desktop's GPS
 *                   never moves, so without this there is nothing to show.
 */
(function () {
  'use strict';

  // At most one write per 10s per order, so a chatty GPS cannot hammer the
  // server — but send immediately if the agent has actually moved.
  var POST_INTERVAL_MS = 10000;
  var MIN_MOVE_METRES = 20;

  // The simulator takes ~1 minute to cover the whole route.
  var SIM_STEP_MS = 2000;
  var SIM_STEPS = 30;

  var EARTH_RADIUS_M = 6371008.8;

  function metresBetween(lat1, lng1, lat2, lng2) {
    var dLat = (lat2 - lat1) * Math.PI / 180;
    var dLng = (lng2 - lng1) * Math.PI / 180;
    var a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
      + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180)
      * Math.sin(dLng / 2) * Math.sin(dLng / 2);
    return EARTH_RADIUS_M * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }

  function number(value) {
    var n = parseFloat(value);
    return isNaN(n) ? null : n;
  }

  function setup(panel) {
    var postUrl = panel.getAttribute('data-post-url');
    var csrf = panel.getAttribute('data-csrf');
    var toggle = panel.querySelector('[data-share-toggle]');
    var simulateBtn = panel.querySelector('[data-simulate]');
    var statusEl = panel.querySelector('[data-share-status]');

    var watchId = null;
    var simTimer = null;
    var lastSent = null;      // { lat, lng, at }
    var pending = null;       // newest reading not yet sent
    var flushTimer = null;
    var stopped = false;      // set when the server says tracking has ended

    function status(text, kind) {
      if (!statusEl) return;
      statusEl.textContent = text;
      statusEl.className = 'muted delivery-share-status'
        + (kind ? ' delivery-share-status-' + kind : '');
    }

    function send(lat, lng, accuracy, source) {
      if (stopped) return;

      var body = { latitude: lat, longitude: lng, source: source };
      if (accuracy !== null && accuracy !== undefined) {
        body.accuracy_m = Math.round(accuracy);
      }

      fetch(postUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify(body)
      }).then(function (res) {
        if (res.status === 409) {
          // The order moved on (delivered or cancelled) — stop for good.
          stopped = true;
          stopSharing();
          stopSimulation();
          status('Tracking ended — this order is no longer out for delivery.', 'ended');
          if (toggle) { toggle.checked = false; toggle.disabled = true; }
          if (simulateBtn) simulateBtn.disabled = true;
          return;
        }
        if (!res.ok) {
          status('Could not send your location (server said ' + res.status + ').', 'error');
          return;
        }
        lastSent = { lat: lat, lng: lng, at: Date.now() };
        status(source === 'simulated'
          ? 'Simulated position sent just now.'
          : 'Sharing — position sent just now.', 'live');
      }).catch(function () {
        status('Could not reach the server — will retry on the next update.', 'error');
      });
    }

    /**
     * Rate-limits real GPS: sends at once on a genuine move, otherwise lets the
     * interval timer pick up the newest reading.
     */
    function offer(lat, lng, accuracy) {
      pending = { lat: lat, lng: lng, accuracy: accuracy };

      var moved = !lastSent
        || metresBetween(lastSent.lat, lastSent.lng, lat, lng) >= MIN_MOVE_METRES;
      var due = !lastSent || (Date.now() - lastSent.at) >= POST_INTERVAL_MS;

      if (moved || due) {
        send(lat, lng, accuracy, 'gps');
        pending = null;
      }
    }

    function startSharing() {
      if (!navigator.geolocation) {
        status('This browser cannot share a location.', 'error');
        if (toggle) toggle.checked = false;
        return;
      }

      // Chrome and Firefox refuse geolocation outside a secure context. That
      // includes serving this app over a plain-http LAN address — localhost is
      // treated as secure, 192.168.x.x is not — and the failure is otherwise a
      // silent permission error that looks like the patient's fault.
      if (window.isSecureContext === false) {
        status('Location needs https or localhost — open the app at http://localhost to share GPS, or use Simulate route.', 'error');
        if (toggle) toggle.checked = false;
        return;
      }

      status('Waiting for a GPS fix…');

      watchId = navigator.geolocation.watchPosition(function (pos) {
        offer(pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy);
      }, function (err) {
        var message = 'Could not get your location.';
        if (err && err.code === 1) message = 'Location permission was denied.';
        if (err && err.code === 2) message = 'Your position is unavailable right now.';
        if (err && err.code === 3) message = 'Getting your position timed out.';
        status(message, 'error');
        if (toggle) toggle.checked = false;
        stopSharing();
      }, { enableHighAccuracy: true, maximumAge: 5000, timeout: 20000 });

      // Flushes the newest reading even when the agent is standing still, so
      // the patient's page keeps showing a live (not stale) marker.
      flushTimer = window.setInterval(function () {
        if (pending) {
          send(pending.lat, pending.lng, pending.accuracy, 'gps');
          pending = null;
        }
      }, POST_INTERVAL_MS);
    }

    function stopSharing() {
      if (watchId !== null) {
        navigator.geolocation.clearWatch(watchId);
        watchId = null;
      }
      if (flushTimer !== null) {
        window.clearInterval(flushTimer);
        flushTimer = null;
      }
      pending = null;
      if (!stopped) status('Not sharing.');
    }

    function stopSimulation() {
      if (simTimer !== null) {
        window.clearInterval(simTimer);
        simTimer = null;
      }
      if (simulateBtn && !stopped) simulateBtn.textContent = 'Simulate route';
    }

    function startSimulation() {
      var fromLat = number(panel.getAttribute('data-origin-lat'));
      var fromLng = number(panel.getAttribute('data-origin-lng'));
      var toLat = number(panel.getAttribute('data-dest-lat'));
      var toLng = number(panel.getAttribute('data-dest-lng'));

      if (fromLat === null || fromLng === null) {
        status('No pharmacy coordinates for this order, so there is nothing to simulate from.', 'error');
        return;
      }

      // No pinned door yet: walk a short arbitrary leg from the pharmacy so the
      // patient still sees movement, rather than refusing outright.
      if (toLat === null || toLng === null) {
        toLat = fromLat + 0.012;
        toLng = fromLng + 0.009;
      }

      var step = 0;
      if (simulateBtn) simulateBtn.textContent = 'Stop simulation';
      status('Simulating a route to the patient…', 'live');

      simTimer = window.setInterval(function () {
        step += 1;
        var progress = Math.min(1, step / SIM_STEPS);
        var lat = fromLat + (toLat - fromLat) * progress;
        var lng = fromLng + (toLng - fromLng) * progress;

        send(lat, lng, null, 'simulated');

        if (progress >= 1) {
          stopSimulation();
          if (!stopped) status('Simulation finished — arrived at the patient.', 'live');
        }
      }, SIM_STEP_MS);
    }

    if (toggle) {
      toggle.addEventListener('change', function () {
        if (toggle.checked) {
          // Real GPS wins over the demo route. Since a claimed order now
          // starts simulating on its own, an agent switching on real sharing
          // would otherwise have the simulator overwriting their true
          // position every two seconds.
          stopSimulation();
          startSharing();
        } else {
          stopSharing();
        }
      });
    }

    if (simulateBtn) {
      simulateBtn.addEventListener('click', function () {
        if (simTimer !== null) stopSimulation();
        else startSimulation();
      });
    }

    // Leaving the page stops both — no background tracking after the agent
    // navigates away.
    window.addEventListener('pagehide', function () {
      stopSharing();
      stopSimulation();
    });

    status('Not sharing.');

    // The agent has just claimed this order, so begin the route immediately
    // rather than making them find the button — see DeliveryOrderController
    // ::accept(). Cleared on both the panel and the window so that only the
    // first setup of the first matching panel ever fires, however many times
    // React re-renders or re-mounts the card.
    if (panel.getAttribute('data-auto-simulate') === '1') {
      panel.removeAttribute('data-auto-simulate');
      window.DELIVERY_AUTO_SIMULATE = null;
      startSimulation();
    }
  }

  /**
   * Initialises every panel that has not been wired up yet.
   *
   * Exposed on window because the delivery section is now a React SPA
   * (resources/js/delivery/) whose cards mount long after DOMContentLoaded
   * has fired — React calls this once its cards are on the page. The
   * data-share-ready flag makes it safe to call repeatedly: re-renders and
   * StrictMode's double-invoked effects must not attach a second watcher to
   * the same order, which would double-post positions.
   */
  function initDeliveryShare(root) {
    var scope = root && root.querySelectorAll ? root : document;
    var panels = scope.querySelectorAll('[data-delivery-share]');

    for (var i = 0; i < panels.length; i += 1) {
      if (panels[i].getAttribute('data-share-ready') === '1') continue;
      panels[i].setAttribute('data-share-ready', '1');
      setup(panels[i]);
    }
  }

  window.initDeliveryShare = initDeliveryShare;

  document.addEventListener('DOMContentLoaded', function () {
    initDeliveryShare(document);
  });
})();
