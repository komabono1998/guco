/* Gulu map: places, directions and optional live location. The visitor's position stays in their browser. */
(function () {
  var root = document.getElementById('gmap'); if (!root) return;
  var data = JSON.parse(root.getAttribute('data-places') || '[]'), groups = JSON.parse(root.getAttribute('data-groups') || '{}');
  var el = {
    map: document.getElementById('gmapCanvas'), list: document.getElementById('gmList'), q: document.getElementById('gmQ'), chips: root.querySelectorAll('[data-group]'),
    from: document.getElementById('gmFrom'), locate: document.getElementById('gmLocate'), track: document.getElementById('gmTrack'), clear: document.getElementById('gmClear'),
    status: document.getElementById('gmStatus'), all: document.getElementById('gmAll')
  };
  if (!window.L) { el.map.innerHTML = '<p class="gm-fallback">The map could not load. Use the list and the Google Maps links to find each place.</p>'; }
  var places = data.map(function (p) { return { id: p[0], name: p[1], group: p[2], lat: p[3], lng: p[4], note: p[5] }; });
  var byId = {}; places.forEach(function (p) { byId[p.id] = p; });
  var map, markers = {}, me = null, meMarker, meCircle, route, watchId = null, dest = null, lastRoute = 0, lastRouteAt = null, group = '';

  function say(t, bad) { el.status.textContent = t; el.status.classList.toggle('bad', !!bad); }
  function km(m) { return m >= 1000 ? (m / 1000).toFixed(1) + ' km' : Math.round(m) + ' m'; }
  function mins(s) { var m = Math.max(1, Math.round(s / 60)); return m < 60 ? m + ' min' : Math.floor(m / 60) + ' h ' + (m % 60) + ' min'; }
  function dist(a, b) { var R = 6371000, r = Math.PI / 180, dLat = (b.lat - a.lat) * r, dLng = (b.lng - a.lng) * r, x = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(a.lat * r) * Math.cos(b.lat * r) * Math.sin(dLng / 2) * Math.sin(dLng / 2); return 2 * R * Math.atan2(Math.sqrt(x), Math.sqrt(1 - x)); }
  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function gLink(p) { return 'https://www.google.com/maps/dir/?api=1&destination=' + p.lat + ',' + p.lng + (me ? '&origin=' + me.lat + ',' + me.lng : '') + '&travelmode=driving'; }

  function popup(p) {
    return '<div class="gm-pop"><strong>' + esc(p.name) + '</strong><small>' + esc(groups[p.group] || '') + '</small><p>' + esc(p.note) + '</p>' +
      '<div class="gm-pop-btns"><button type="button" data-route="' + p.id + '">Show route</button><a href="' + gLink(p) + '" target="_blank" rel="noopener">Google Maps</a></div></div>';
  }

  function render() {
    var q = el.q.value.trim().toLowerCase();
    el.list.innerHTML = '';
    var shown = places.filter(function (p) { return (!group || p.group === group) && (!q || (p.name + ' ' + p.note).toLowerCase().indexOf(q) > -1); });
    if (me) shown.sort(function (a, b) { return dist(me, a) - dist(me, b); });
    shown.forEach(function (p) {
      var li = document.createElement('li');
      li.innerHTML = '<button type="button" data-id="' + p.id + '"><span class="gm-dot g-' + p.group + '"></span><span class="gm-it"><b>' + esc(p.name) + '</b><small>' + esc(groups[p.group] || '') + (me ? ' &middot; ' + km(dist(me, p)) + ' away' : '') + '</small></span></button>';
      el.list.appendChild(li);
    });
    if (!shown.length) el.list.innerHTML = '<li class="gm-none">No place matches that search.</li>';
    Object.keys(markers).forEach(function (id) { var on = shown.indexOf(byId[id]) > -1; if (on) markers[id].addTo(map); else map.removeLayer(markers[id]); });
  }

  function fillFrom() {
    var keep = el.from.value;
    el.from.innerHTML = (me ? '<option value="me">My location</option>' : '<option value="me" disabled>My location (turn on location first)</option>') + places.map(function (p) { return '<option value="' + p.id + '">' + esc(p.name) + '</option>'; }).join('');
    el.from.value = keep && el.from.querySelector('option[value="' + keep + '"]') && !(keep === 'me' && !me) ? keep : (me ? 'me' : 'guc');
  }

  function startPoint() { var v = el.from.value; return v === 'me' ? me : byId[v]; }

  function showRoute(p, quiet) {
    dest = p; var a = startPoint();
    if (!a) { say('Turn on your location, or choose another starting place.', true); return; }
    if (a === p) { say('That is your starting place. Choose a different destination.', true); return; }
    if (!window.L) return;
    var url = 'https://router.project-osrm.org/route/v1/driving/' + a.lng + ',' + a.lat + ';' + p.lng + ',' + p.lat + '?overview=full&geometries=geojson';
    if (!quiet) say('Finding the route to ' + p.name + '…');
    fetch(url).then(function (r) { if (!r.ok) throw new Error('route'); return r.json(); }).then(function (j) {
      if (!j.routes || !j.routes[0]) throw new Error('none');
      var r = j.routes[0]; if (route) map.removeLayer(route);
      route = L.geoJSON(r.geometry, { style: { color: '#14532d', weight: 6, opacity: .85 } }).addTo(map);
      if (!quiet) map.fitBounds(route.getBounds(), { padding: [50, 50] });
      say('To ' + p.name + ': ' + km(r.distance) + ', about ' + mins(r.duration) + ' by car.');
      el.clear.hidden = false; lastRouteAt = a.lat ? { lat: a.lat, lng: a.lng } : null; lastRoute = Date.now();
    }).catch(function () {
      say('The route service is not available right now. Use the Google Maps link instead.', true);
    });
  }

  function select(id, open) {
    var p = byId[id]; if (!p) return;
    if (window.L) { map.flyTo([p.lat, p.lng], Math.max(map.getZoom(), 16), { duration: .8 }); if (markers[id]) markers[id].openPopup(); }
    dest = p;
  }

  function placeMe(pos, follow) {
    var firstFix = !me;
    me = { lat: pos.coords.latitude, lng: pos.coords.longitude };
    if (window.L) {
      if (!meMarker) {
        meMarker = L.marker([me.lat, me.lng], { icon: L.divIcon({ className: 'gm-me-wrap', html: '<span class="gm-me"></span>', iconSize: [22, 22], iconAnchor: [11, 11] }), zIndexOffset: 1000 }).addTo(map).bindTooltip('You are here');
        meCircle = L.circle([me.lat, me.lng], { radius: pos.coords.accuracy || 30, color: '#1d6fd8', weight: 1, fillOpacity: .1 }).addTo(map);
      } else { meMarker.setLatLng([me.lat, me.lng]); meCircle.setLatLng([me.lat, me.lng]).setRadius(pos.coords.accuracy || 30); }
      if (follow) map.setView([me.lat, me.lng], Math.max(map.getZoom(), 15));
    }
    fillFrom(); if (firstFix) el.from.value = 'me'; render();
    if (watchId !== null && dest && el.from.value === 'me') {
      var moved = lastRouteAt ? dist(lastRouteAt, me) : 1e9;
      if (moved > 80 && Date.now() - lastRoute > 10000) showRoute(dest, true);
    }
  }

  function geoError(e) {
    var msg = e && e.code === 1 ? 'Location permission was declined. You can still pick a starting place from the list.' : 'Your location could not be found. Try again, or pick a starting place.';
    say(msg, true); stopTrack();
  }

  function locateOnce() {
    if (!navigator.geolocation) { say('This browser cannot share a location.', true); return; }
    say('Finding your location…');
    navigator.geolocation.getCurrentPosition(function (pos) { placeMe(pos, true); say('Location found. Choose a place to see the route.'); if (dest) showRoute(dest); }, geoError, { enableHighAccuracy: true, timeout: 15000 });
  }

  function startTrack() {
    if (!navigator.geolocation) { say('This browser cannot share a location.', true); return; }
    if (watchId !== null) return;
    say('Starting live location…');
    watchId = navigator.geolocation.watchPosition(function (pos) { var first = !me; placeMe(pos, first); if (first) say('Live location on. Choose a place for a route that follows you.'); }, geoError, { enableHighAccuracy: true, maximumAge: 5000, timeout: 20000 });
    el.track.classList.add('on'); el.track.textContent = 'Stop live location'; el.track.setAttribute('aria-pressed', 'true');
  }
  function stopTrack() {
    if (watchId !== null) { navigator.geolocation.clearWatch(watchId); watchId = null; }
    el.track.classList.remove('on'); el.track.textContent = 'Track my location live'; el.track.setAttribute('aria-pressed', 'false');
  }

  if (window.L) {
    map = L.map(el.map, { scrollWheelZoom: false, zoomControl: true }).setView([2.7786, 32.2990], 14);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors' }).addTo(map);
    places.forEach(function (p) {
      var m = L.marker([p.lat, p.lng], { icon: L.divIcon({ className: 'gm-pin-wrap', html: '<span class="gm-pin g-' + p.group + (p.id === 'guc' ? ' home' : '') + '"></span>', iconSize: [30, 38], iconAnchor: [15, 36], popupAnchor: [0, -32] }), title: p.name, keyboard: true }).bindPopup(popup(p), { minWidth: 210 });
      m.on('click', function () { dest = p; });
      markers[p.id] = m;
    });
    map.on('popupopen', function (e) {
      var b = e.popup.getElement().querySelector('[data-route]');
      if (b) b.addEventListener('click', function () { showRoute(byId[b.getAttribute('data-route')]); });
    });
    map.once('focus', function () { map.scrollWheelZoom.enable(); });
    map.on('click', function () { map.scrollWheelZoom.enable(); });
  }

  el.list.addEventListener('click', function (e) { var b = e.target.closest('[data-id]'); if (b) select(b.getAttribute('data-id'), true); });
  el.q.addEventListener('input', render);
  [].forEach.call(el.chips, function (c) { c.addEventListener('click', function () { group = c.getAttribute('data-group'); [].forEach.call(el.chips, function (x) { x.classList.toggle('on', x === c); }); render(); }); });
  el.locate.addEventListener('click', locateOnce);
  el.track.addEventListener('click', function () { watchId === null ? startTrack() : (stopTrack(), say('Live location stopped.')); });
  el.clear.addEventListener('click', function () { if (route && window.L) { map.removeLayer(route); route = null; } el.clear.hidden = true; say('Route cleared.'); });
  el.from.addEventListener('change', function () { if (dest && route) showRoute(dest); });
  el.all.addEventListener('click', function () { if (window.L) { var pts = places.map(function (p) { return [p.lat, p.lng]; }); map.fitBounds(pts, { padding: [40, 40] }); } });

  fillFrom(); render();
  if (window.L) el.all.click();
  window.addEventListener('pagehide', stopTrack);
})();
