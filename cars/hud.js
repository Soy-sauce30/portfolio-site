/* =========================================
   HUD theme panels (car pages).
   Everything shown is real:
   - clock/date from the visitor's device
   - weather from Open-Meteo, only after the visitor shares
     their location (rounded to ~1 km before it's sent)
   - data-source status + catalog numbers from /cars/status.php
   - "Recent lookups" = this visitor's own searches (kept in their browser)
   - "Scan report" = a summary of the car that was actually looked up
   Panels only run while the HUD theme is on.
   ========================================= */
(function () {
  var html = document.documentElement;
  var timer = null, started = false, statusLoaded = false, weatherStarted = false;
  var RECENT_KEY = 'hudRecent';

  function $(id) { return document.getElementById(id); }
  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function isOn() { return html.getAttribute('data-theme') === 'hud'; }

  // ---------- Clock ----------
  function tick() {
    var now = new Date();
    if ($('hudTime')) {
      $('hudTime').textContent = now.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
      $('hudTime').setAttribute('datetime', now.toISOString());
    }
    if ($('hudDate')) $('hudDate').textContent = now.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
    if (now.getSeconds() % 30 === 0) renderRecent(); // keep "2m ago" fresh
  }

  // ---------- Weather (Open-Meteo, no key) ----------
  var WMO = {
    0: ['Clear', 'sun'], 1: ['Mostly clear', 'sun'], 2: ['Partly cloudy', 'partly'], 3: ['Cloudy', 'cloud'],
    45: ['Fog', 'fog'], 48: ['Freezing fog', 'fog'],
    51: ['Light drizzle', 'rain'], 53: ['Drizzle', 'rain'], 55: ['Heavy drizzle', 'rain'],
    56: ['Freezing drizzle', 'rain'], 57: ['Freezing drizzle', 'rain'],
    61: ['Light rain', 'rain'], 63: ['Rain', 'rain'], 65: ['Heavy rain', 'rain'],
    66: ['Freezing rain', 'rain'], 67: ['Freezing rain', 'rain'],
    71: ['Light snow', 'snow'], 73: ['Snow', 'snow'], 75: ['Heavy snow', 'snow'], 77: ['Snow grains', 'snow'],
    80: ['Showers', 'rain'], 81: ['Showers', 'rain'], 82: ['Heavy showers', 'rain'],
    85: ['Snow showers', 'snow'], 86: ['Snow showers', 'snow'],
    95: ['Thunderstorm', 'storm'], 96: ['Thunderstorm, hail', 'storm'], 99: ['Thunderstorm, hail', 'storm']
  };
  var ICONS = {
    sun: '<circle cx="12" cy="12" r="4.5"/><path d="M12 2v2.5M12 19.5V22M2 12h2.5M19.5 12H22M4.9 4.9l1.8 1.8M17.3 17.3l1.8 1.8M4.9 19.1l1.8-1.8M17.3 6.7l1.8-1.8"/>',
    moon: '<path d="M20 14.5A8 8 0 1 1 9.5 4a6.5 6.5 0 0 0 10.5 10.5z"/>',
    partly: '<path d="M8 4.5V3M3.5 9H2M4.8 5.8 3.8 4.8M12.2 5.8l1-1"/><path d="M6 11.5a4 4 0 0 1 7.6-1.7"/><path d="M8 20h10a3.5 3.5 0 0 0 0-7 5 5 0 0 0-9.6 1.5A3 3 0 0 0 8 20z"/>',
    cloud: '<path d="M7 19h11a4 4 0 0 0 0-8 6 6 0 0 0-11.6 1.6A3.3 3.3 0 0 0 7 19z"/>',
    fog: '<path d="M4 10h16M3 14h18M6 18h12"/>',
    rain: '<path d="M7 15h11a4 4 0 0 0 0-8 6 6 0 0 0-11.6 1.6A3.3 3.3 0 0 0 7 15z"/><path d="M9 18l-1 3M13 18l-1 3M17 18l-1 3"/>',
    snow: '<path d="M7 14h11a4 4 0 0 0 0-8 6 6 0 0 0-11.6 1.6A3.3 3.3 0 0 0 7 14z"/><path d="M9 18h.01M13 20h.01M17 18h.01M11 22h.01M15 22h.01"/>',
    storm: '<path d="M7 14h11a4 4 0 0 0 0-8 6 6 0 0 0-11.6 1.6A3.3 3.3 0 0 0 7 14z"/><path d="m12 15-2 4h3l-2 4"/>'
  };

  function show(id, on) { if ($(id)) $(id).hidden = !on; }

  function weatherNote(text) {
    show('hudWeatherBtn', false);
    show('hudWeatherNow', false);
    if ($('hudWeatherNote')) $('hudWeatherNote').textContent = text;
    show('hudWeatherNote', !!text);
  }

  function showWeather(w) {
    var info = WMO[w.code] || ['', 'cloud'];
    var icon = info[1] === 'sun' && !w.day ? 'moon' : info[1];
    $('hudWeatherIcon').innerHTML = '<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">' + ICONS[icon] + '</svg>';
    $('hudTemp').textContent = Math.round(w.temp) + '°F';
    $('hudCond').textContent = info[0];
    show('hudWeatherBtn', false);
    show('hudWeatherNote', false);
    show('hudWeatherNow', true);
  }

  function fetchWeather(lat, lon) {
    // Rounded to 2 decimals (about 1 km) — enough for weather, less precise than GPS.
    lat = lat.toFixed(2); lon = lon.toFixed(2);
    try {
      var cached = JSON.parse(sessionStorage.getItem('hudWeather') || 'null');
      if (cached && cached.lat === lat && cached.lon === lon && Date.now() - cached.at < 15 * 60 * 1000) return showWeather(cached);
    } catch (e) {}
    weatherNote('Getting weather…');
    fetch('https://api.open-meteo.com/v1/forecast?latitude=' + lat + '&longitude=' + lon +
          '&current=temperature_2m,weather_code,is_day&temperature_unit=fahrenheit')
      .then(function (r) { if (!r.ok) throw r; return r.json(); })
      .then(function (d) {
        var w = { lat: lat, lon: lon, at: Date.now(), temp: d.current.temperature_2m, code: d.current.weather_code, day: d.current.is_day === 1 };
        try { sessionStorage.setItem('hudWeather', JSON.stringify(w)); } catch (e) {}
        showWeather(w);
      })
      .catch(function () { weatherNote('Weather unavailable'); });
  }

  function locate() {
    weatherNote('Finding your location…');
    navigator.geolocation.getCurrentPosition(
      function (pos) { fetchWeather(pos.coords.latitude, pos.coords.longitude); },
      function (err) { weatherNote(err.code === 1 ? 'Location is off' : 'Location unavailable'); },
      { maximumAge: 30 * 60 * 1000, timeout: 15000 }
    );
  }

  function startWeather() {
    if (weatherStarted || !$('hudWeather')) return;
    weatherStarted = true;
    if (!navigator.geolocation) return weatherNote('');
    var btn = $('hudWeatherBtn');
    btn.addEventListener('click', locate);
    // Only ask for location when the visitor clicks; reuse permission they already gave.
    if (navigator.permissions && navigator.permissions.query) {
      navigator.permissions.query({ name: 'geolocation' }).then(function (p) {
        if (p.state === 'granted') locate();
        else if (p.state === 'denied') weatherNote('Location is off');
        else show('hudWeatherBtn', true);
      }).catch(function () { show('hudWeatherBtn', true); });
    } else {
      show('hudWeatherBtn', true);
    }
  }

  // ---------- Live status + catalog numbers ----------
  function row(label, value, cls) {
    return '<li class="hud-row"><span class="hud-row-label">' + esc(label) + '</span><span class="hud-row-value' + (cls ? ' ' + cls : '') + '">' + esc(value) + '</span></li>';
  }

  function loadStatus() {
    if (statusLoaded || !$('hudSources')) return;
    statusLoaded = true;
    fetch('/cars/status.php')
      .then(function (r) { return r.json(); })
      .then(function (d) {
        $('hudSources').innerHTML =
          d.sources.map(function (s) { return row(s.name, s.ok ? 'Online' : 'Offline', s.ok ? 'is-ok' : 'is-off'); }).join('') +
          row('Photo ID', d.photoId ? 'Online' : 'Off — needs API key', d.photoId ? 'is-ok' : 'is-off');
        var c = d.catalog;
        if (c && $('hudCatalog')) {
          var built = new Date(c.built + 'T12:00:00');
          $('hudCatalog').innerHTML =
            row('Versions on sale', c.onSale.toLocaleString()) +
            row('Versions since 1984', c.total.toLocaleString()) +
            row('Brands on sale', String(c.brands)) +
            row('Updated', built.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' }));
        }
      })
      .catch(function () {
        statusLoaded = false;
        $('hudSources').innerHTML = row('Status check', 'Couldn’t reach the server', 'is-off');
      });
  }

  // ---------- Recent lookups (this visitor's own, stored in their browser) ----------
  function getRecent() {
    try { return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]'); } catch (e) { return []; }
  }

  function ago(ms) {
    var s = Math.max(0, Math.round((Date.now() - ms) / 1000));
    if (s < 60) return 'just now';
    if (s < 3600) return Math.floor(s / 60) + 'm ago';
    if (s < 86400) return Math.floor(s / 3600) + 'h ago';
    return Math.floor(s / 86400) + 'd ago';
  }

  function renderRecent() {
    var list = $('hudRecent');
    if (!list) return;
    var items = getRecent();
    list.innerHTML = items.length ? items.map(function (r) {
      var attr = r.race ? ' data-race="' + esc(r.race) + '"' : ' data-car="' + esc(r.q || r.title) + '"';
      return '<li><button type="button" class="hud-recent-item"' + attr + '>' +
        '<span class="hud-recent-title">' + esc(r.title) + '</span><span class="hud-recent-time">' + ago(r.at) + '</span></button></li>';
    }).join('') : '<li class="hud-empty">Cars you look up will show here.</li>';
  }

  function remember(car) {
    var title = [car.make, car.model].filter(Boolean).join(' ');
    if (!title) return;
    var entry = { title: title, q: car._query || title, race: car.raceId || '', at: Date.now() };
    var items = getRecent().filter(function (r) { return r.title !== title; });
    items.unshift(entry);
    try { localStorage.setItem(RECENT_KEY, JSON.stringify(items.slice(0, 5))); } catch (e) {}
    renderRecent();
  }

  // ---------- Scan report: what the lookup actually returned ----------
  function spec(car, re) {
    var s = (car.specs || []).filter(function (x) { return re.test(x.label); })[0];
    return s ? s.value : '';
  }

  function result(car) {
    remember(car);
    var box = $('hudReport');
    if (!box) return;
    var title = [car.years, car.make, car.model].filter(Boolean).join(' ');
    var lines = [];
    if (car.raceId) {
      lines.push(['Class', car.years]);
      ['Power', 'Top speed', 'Engine'].forEach(function (k) {
        var v = spec(car, new RegExp('^' + k));
        if (v) lines.push([k, v]);
      });
      title = car.model;
    } else {
      if (car.trim) lines.push(['Version', car.trim]);
      if (car.price_new) lines.push(['New price', car.price_new + ' (approx.)']);
      [['Engine', /^Engine/], ['Drivetrain', /^Drive/], ['Fuel economy', /^Fuel economy|^Efficiency/], ['Range', /range/i]].forEach(function (k) {
        var v = spec(car, k[1]);
        if (v) lines.push([k[0], v]);
      });
    }
    box.innerHTML = '<p>Here’s what I found:</p><p class="hud-report-title">' + esc(title) + '</p>' +
      (lines.length ? '<ul>' + lines.map(function (l) { return '<li><b>' + esc(l[0]) + ':</b> ' + esc(l[1]) + '</li>'; }).join('') + '</ul>' : '') +
      '<p class="hud-report-more">Full details are below.</p>';
  }

  function note(text) {
    var box = $('hudReport');
    if (box) box.textContent = text.trim();
  }

  // ---------- Quick access: "Finder" focuses the search box ----------
  document.addEventListener('click', function (e) {
    var a = e.target.closest('[data-hud-focus]');
    if (!a) return;
    e.preventDefault();
    var q = $('query');
    if (q) { q.scrollIntoView({ behavior: 'smooth', block: 'center' }); q.focus({ preventScroll: true }); }
  });

  // ---------- Start / stop with the theme ----------
  function start() {
    if (started) return;
    started = true;
    tick();
    timer = setInterval(tick, 1000);
    startWeather();
    loadStatus();
    renderRecent();
  }
  function stop() {
    started = false;
    clearInterval(timer);
  }

  document.addEventListener('themechange', function (e) { if (e.detail === 'hud') start(); else stop(); });
  if (isOn()) start();

  window.HUD = { result: result, note: note };
})();
