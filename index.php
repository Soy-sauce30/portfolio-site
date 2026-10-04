<?php
// Photo ID needs an Anthropic API key. Without one, search uses free EPA + Wikipedia data instead.
$keyFile = dirname(__DIR__) . '/sawyerabrahani-secrets/anthropic_key';
$aiEnabled = (bool)getenv('ANTHROPIC_API_KEY') || is_readable($keyFile);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sawyer's Garage — Car Finder</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&family=Fraunces:ital,opsz,wght@0,9..144,600;1,9..144,600&family=Barlow+Condensed:ital,wght@1,700;1,800&family=Rajdhani:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <script>
    // Car pages also offer a third "HUD" look (dark only), stored separately so other pages stay light/dark.
    (function(){var d=document.documentElement,t,l;try{t=localStorage.getItem('theme');l=localStorage.getItem('look')}catch(e){}t=t||(window.matchMedia('(prefers-color-scheme:light)').matches?'light':'dark');if(t!=='light'&&l==='hud')t='hud';d.setAttribute('data-theme',t);d.setAttribute('data-hud-ok','')})();
  </script>
  <link rel="stylesheet" href="/cars/site.css">
  <link rel="stylesheet" href="/cars/themes.css">
  <link rel="stylesheet" href="/cars/hud.css">
</head>
<body>

  <!-- Header -->
  <?php include 'header.php';?>

  <?php include __DIR__ . '/cars/hud-bar.php'; ?>

  <!-- Hero + finder -->
  <section class="home-hero" id="home">
    <!-- HUD theme only: live site status and real catalog numbers -->
    <aside class="hud-col hud-left hud-only" aria-label="Site status">
      <div class="hud-panel">
        <h2 class="hud-panel-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2 4 6v6c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V6z"/></svg>System status</h2>
        <ul class="hud-rows" id="hudSources"><li class="hud-row"><span>Checking data sources…</span></li></ul>
      </div>
      <div class="hud-panel">
        <h2 class="hud-panel-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v6c0 1.7 3.6 3 8 3s8-1.3 8-3V6M4 12v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/></svg>Garage data</h2>
        <ul class="hud-rows" id="hudCatalog"></ul>
      </div>
    </aside>

    <div class="hero-core">
    <svg class="hud-reticle hud-only" viewBox="0 0 400 400" aria-hidden="true">
      <g class="hud-reticle-spin">
        <circle cx="200" cy="200" r="182" fill="none" stroke="currentColor" stroke-width="1" stroke-dasharray="2 10" opacity=".7"/>
        <path d="M200 40a160 160 0 0 1 138 80M338 280a160 160 0 0 1-138 80M62 280a160 160 0 0 1 0-160" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round" opacity=".55"/>
      </g>
      <circle cx="200" cy="200" r="130" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="70 12" opacity=".5"/>
      <circle cx="200" cy="200" r="34" fill="none" stroke="currentColor" stroke-width="1.5" opacity=".6"/>
      <path d="M200 0v60M200 340v60M0 200h60M340 200h60M200 186v-12M200 214v12M186 200h-12M214 200h12" stroke="currentColor" stroke-width="1.5" opacity=".7"/>
    </svg>
    <div class="home-kicker"><span>Free</span> Look up any car sold in the US since 1984</div>
    <h1 class="home-name">Know any car<br><span class="grad-text">in seconds.</span></h1>
    <p class="home-tagline"><?php if ($aiEnabled): ?>Type a make and model, or snap a photo of one on the street — get the specs, price, and how it stacks up.<?php else: ?>Type any make and model — get the engine, fuel economy, running costs, and where to find prices.<?php endif; ?></p>

    <form class="finder" id="finder" autocomplete="off">
      <span class="finder-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></span>
      <input type="text" id="query" placeholder="e.g. 2024 Toyota Supra" aria-label="Search for a car" maxlength="120">
      <?php if ($aiEnabled): ?>
      <button type="button" class="finder-btn finder-photo" id="photoBtn" aria-label="Identify a car from a photo">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
        Photo
      </button>
      <button type="submit" class="finder-btn finder-go" id="goBtn">Look up</button>
      <input type="file" id="photoInput" accept="image/*" hidden>
      <?php else: ?>
      <button type="submit" class="finder-btn finder-go" id="goBtn">Look up</button>
      <?php endif; ?>
    </form>

    <p class="finder-hint">
      Try <button type="button" data-car="Porsche 911 GT3">Porsche 911 GT3</button>
      <button type="button" data-car="Ford Bronco">Ford Bronco</button>
      <button type="button" data-car="Toyota Supra">Toyota Supra</button>
    </p>
    </div>

    <!-- HUD theme only: what the last scan found, your own recent lookups, shortcuts -->
    <aside class="hud-col hud-right hud-only" aria-label="Scan report and shortcuts">
      <div class="hud-panel">
        <h2 class="hud-panel-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3"/></svg>Scan report</h2>
        <div class="hud-bubble" id="hudReport" aria-live="polite">Ready. Type any make and model into the scanner, or pick a car below.</div>
      </div>
      <div class="hud-panel">
        <h2 class="hud-panel-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 8v4l3 2"/><circle cx="12" cy="12" r="9"/></svg>Recent lookups</h2>
        <ul class="hud-recent" id="hudRecent"></ul>
      </div>
      <div class="hud-panel">
        <h2 class="hud-panel-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>Quick access</h2>
        <nav class="hud-quick" aria-label="Quick access">
          <a href="#home" data-hud-focus><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>Finder</a>
          <a href="#racing"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 21V4M4 4h14l-3 4 3 4H4"/></svg>Racing</a>
          <a href="/cars/"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 13l2-6h14l2 6v5H3z"/><circle cx="7.5" cy="16" r="1.5"/><circle cx="16.5" cy="16" r="1.5"/></svg>All cars</a>
          <a href="/games/"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="2" y="7" width="20" height="11" rx="4"/><path d="M7 11v3M5.5 12.5h3M15 12h.01M18 13h.01"/></svg>Games</a>
        </nav>
      </div>
    </aside>
  </section>

  <div class="result" id="result" aria-live="polite"></div>

  <hr class="home-rule">

  <!-- Top rated -->
  <section class="home-section" id="top">
    <div class="section-head">
      <div>
        <h2 class="home-heading">Top rated cars</h2>
        <p class="home-sub">The cars owners and reviewers keep coming back to. Tap one for the full rundown.</p>
      </div>

    <div class="filters" id="filters">
      <button type="button" data-filter="all" aria-pressed="true">All</button>
      <button type="button" data-filter="sports" aria-pressed="false">Sports</button>
      <button type="button" data-filter="sedan" aria-pressed="false">Sedans</button>
      <button type="button" data-filter="suv" aria-pressed="false">SUVs</button>
      <button type="button" data-filter="truck" aria-pressed="false">Trucks</button>
      <button type="button" data-filter="ev" aria-pressed="false">Electric</button>
    </div>
    </div>

    <div class="car-grid" id="carGrid"></div>
  </section>

  <hr class="home-rule">

  <!-- Race cars -->
  <section class="home-section" id="racing">
    <div class="section-head">
      <div>
        <h2 class="home-heading">Race cars</h2>
        <p class="home-sub">From F1 to monster trucks. Tap one for the specs, then spin it around in 3D.</p>
      </div>

      <div class="filters" id="raceFilters">
        <button type="button" data-filter="all" aria-pressed="true">All</button>
        <button type="button" data-filter="open" aria-pressed="false">Open-wheel</button>
        <button type="button" data-filter="stock" aria-pressed="false">Stock &amp; drift</button>
        <button type="button" data-filter="drag" aria-pressed="false">Drag</button>
        <button type="button" data-filter="endurance" aria-pressed="false">Endurance</button>
        <button type="button" data-filter="offroad" aria-pressed="false">Off-road</button>
      </div>
    </div>

    <div class="car-grid" id="raceGrid"></div>
  </section>

  <hr class="home-rule">

  <div class="home-foot" id="contact">
    <span><?php echo $aiEnabled ? 'Specs and prices are estimates — always confirm with a dealer.' : 'Specs from the U.S. EPA (fueleconomy.gov) &middot; photos and descriptions from Wikipedia.'; ?></span>
    <span>Questions? <a href="mailto:sawyerabrahani@gmail.com">sawyerabrahani@gmail.com</a> &middot; <a href="/games/">Play my games &rarr;</a></span>
  </div>

<!-- Footer -->
<?php include 'footer.php';?>


  <script src="script.js"></script>
  <script src="theme.js"></script>
  <script src="/cars/racing.js"></script>
  <script src="/cars/explain.js"></script>
  <script src="/cars/hud.js"></script>
  <script>
  (function () {
    // ---------- Top rated list ----------
    var CARS = [
      { name: 'Mazda MX-5 Miata', key: 'Mazda|MX-5', cat: 'Roadster',       type: 'sports',        desc: 'Light, cheap, and endlessly fun. The benchmark for pure driving joy.' },
      { name: 'Porsche 911', key: 'Porsche|911',      cat: 'Sports car',     type: 'sports',        desc: 'Six decades of refinement. Daily-drivable and a weapon on track.' },
      { name: 'Chevrolet Corvette', key: 'Chevrolet|Corvette', cat: 'Sports car',   type: 'sports',        desc: 'Mid-engine supercar numbers at a fraction of supercar money.' },
      { name: 'BMW M3', key: 'BMW|M3',           cat: 'Sport sedan',    type: 'sedan',         desc: 'Four doors, rear seats, and serious straight-six muscle.' },
      { name: 'Toyota Camry', key: 'Toyota|Camry',     cat: 'Midsize sedan',  type: 'sedan',         desc: 'Hybrid-only now, with great mileage and a reputation for lasting forever.' },
      { name: 'Honda Civic', key: 'Honda|Civic',      cat: 'Compact',        type: 'sedan',         desc: 'Roomy, efficient, well built — and the Type R is a legend.' },
      { name: 'Toyota RAV4', key: 'Toyota|RAV4',      cat: 'Compact SUV',    type: 'suv',           desc: 'America’s best-selling SUV for a reason: practical, reliable, efficient.' },
      { name: 'Kia Telluride', key: 'Kia|Telluride',    cat: 'Three-row SUV',  type: 'suv',           desc: 'Luxury-feeling family hauler with loads of standard features.' },
      { name: 'Honda CR-V', key: 'Honda|CR-V',       cat: 'Compact SUV',    type: 'suv',           desc: 'Huge cargo space, smooth hybrid, and a comfortable ride.' },
      { name: 'Ford F-150', key: 'Ford|F150',       cat: 'Full-size truck', type: 'truck',        desc: 'The best-selling truck in America, from work spec to Raptor.' },
      { name: 'Toyota Tacoma', key: 'Toyota|Tacoma',    cat: 'Midsize truck',  type: 'truck',         desc: 'Off-road ready and famous for holding its value.' },
      { name: 'Tesla Model Y', key: 'Tesla|Model Y',    cat: 'Electric SUV',   type: 'suv ev',        desc: 'Big range, quick charging, and the Supercharger network.' },
      { name: 'Hyundai Ioniq 5', key: 'Hyundai|Ioniq 5',  cat: 'Electric SUV',   type: 'suv ev',        desc: 'Retro-future looks and some of the fastest charging on sale.' },
      { name: 'Rivian R1S', key: 'Rivian|R1S',       cat: 'Electric SUV',   type: 'suv ev',        desc: 'Seven seats, serious off-road chops, and wild acceleration.' }
    ];

    // Simple side-profile silhouettes by body style.
    // Outline side profiles by body style (gaps in the body line sit behind the wheels).
    function car(body, glass, rear, front, y) {
      return '<svg viewBox="0 0 150 64" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">' +
        '<path d="' + body + '"/><path d="' + glass + '" fill="currentColor" fill-opacity=".14" stroke-width="1.6"/>' +
        '<circle cx="' + rear + '" cy="' + y + '" r="9"/><circle cx="' + rear + '" cy="' + y + '" r="3.5" fill="currentColor"/>' +
        '<circle cx="' + front + '" cy="' + y + '" r="9"/><circle cx="' + front + '" cy="' + y + '" r="3.5" fill="currentColor"/>' +
        '<path d="M10 ' + (y + 11) + 'h130" stroke-opacity=".25" stroke-width="1.5"/></svg>';
    }
    var ART = {
      sports: car('M20 50H9c-2 0-3-1-3-3v-3c0-3 2-5 6-6l28-5c8-8 18-13 32-13h10c12 0 21 5 29 12l15 3c5 1 8 4 8 8v4c0 2-1 3-3 3h-11M100 50H48',
                  'M46 32c7-6 15-9 26-9h8c9 0 16 3 22 9z', 34, 114, 50),
      sedan:  car('M20 50H8c-2 0-3-1-3-3v-4c0-4 3-7 8-8l22-4 15-11c4-3 9-4 14-4h24c7 0 12 2 17 6l11 9c11 1 21 4 21 10v6c0 2-1 3-3 3h-12M101 50H48',
                  'M40 31l13-10c3-2 6-3 10-3h26c5 0 9 2 13 5l9 8z', 34, 115, 50),
      suv:    car('M20 50H8c-2 0-3-1-3-3V34c0-4 2-6 6-7l18-4 12-11c3-3 6-4 10-4h52c5 0 9 2 12 6l9 12c11 1 19 4 19 10v11c0 2-1 3-3 3h-12M101 50H48',
                  'M34 24l11-10c2-2 4-3 7-3h48c3 0 6 2 8 4l7 9z', 34, 115, 50),
      truck:  car('M20 50H7c-2 0-3-1-3-3V33h72V12c0-3 2-5 5-5h30c4 0 7 2 9 4l11 15c11 1 17 4 17 10v11c0 2-1 3-3 3h-12M101 50H48',
                  'M84 13h26l10 13H84z', 34, 115, 50)
    };

    // Specs keep their normal look; these attributes just make them tappable (cars/explain.js).
    function explainAttrs(label, value) {
      if (!window.Explain || !Explain.describe(label, value)) return ''; // nothing to explain → leave it plain
      return ' data-explain="' + esc(label) + '"' + (value ? ' data-value="' + esc(value) + '"' : '') + ' role="button" tabindex="0" aria-expanded="false"';
    }
    var CUBE = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"><path d="M12 2 3 7v10l9 5 9-5V7z"/><path d="m3 7 9 5 9-5M12 12v10"/></svg>';

    function esc(s) {
      return String(s).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
      });
    }

    var grid = document.getElementById('carGrid');
    grid.innerHTML = CARS.map(function (c) {
      var art = ART[c.type.split(' ')[0]] || ART.sedan;
      return '<button type="button" class="car-card" data-car="' + esc(c.name) + '" data-type="' + c.type + '">' +
        '<div class="car-stage"><span class="car-tag">' + esc(c.cat) + '</span><div class="car-art">' + art + '</div></div>' +
        '<div class="car-info">' +
          '<div class="car-name">' + esc(c.name) + '</div>' +
          '<p class="car-desc">' + esc(c.desc) + '</p>' +
          '<div class="car-foot"><span class="car-more">Full specs <span>&rarr;</span></span><span class="car-price" data-price-key="' + esc(c.key) + '"></span></div>' +
        '</div>' +
      '</button>';
    }).join('');

    // "From $29k" on each top-rated card, from the shared price list.
    function money(n) { return n >= 1000000 ? '$' + (n / 1000000).toFixed(1).replace('.0', '') + 'M' : '$' + Math.round(n / 1000) + 'k'; }
    fetch('/cars/data/prices.json').then(function (r) { return r.json(); }).then(function (prices) {
      grid.querySelectorAll('[data-price-key]').forEach(function (el) {
        var p = prices[el.dataset.priceKey];
        if (p) el.textContent = 'From ' + money(p[0]);
      });
    }).catch(function () {});

    var raceGrid = document.getElementById('raceGrid');
    raceGrid.innerHTML = RACE_CARS.map(function (r) {
      return '<button type="button" class="car-card race-card" data-race="' + r.id + '" data-type="' + r.type + '">' +
        '<div class="car-stage"><span class="car-tag">' + esc(r.tag) + '</span>' +
          '<img src="' + esc(r.model.thumb) + '" alt="' + esc(r.name) + ' 3D model" loading="lazy">' +
          '<span class="badge-3d">' + CUBE + ' 3D</span></div>' +
        '<div class="car-info">' +
          '<div class="car-name">' + esc(r.name) + '</div>' +
          '<p class="car-desc">' + esc(r.desc) + '</p>' +
          '<span class="car-more">Specs &amp; 3D <span>&rarr;</span></span>' +
        '</div>' +
      '</button>';
    }).join('');

    function wireFilters(bar, cards) {
      bar.addEventListener('click', function (e) {
        var btn = e.target.closest('button');
        if (!btn) return;
        var f = btn.dataset.filter;
        bar.querySelectorAll('button').forEach(function (b) { b.setAttribute('aria-pressed', b === btn); });
        cards.querySelectorAll('.car-card').forEach(function (card) {
          card.hidden = f !== 'all' && card.dataset.type.split(' ').indexOf(f) === -1;
        });
      });
    }
    wireFilters(document.getElementById('filters'), grid);
    wireFilters(document.getElementById('raceFilters'), raceGrid);

    // ---------- Lookup ----------
    var form = document.getElementById('finder');
    var queryEl = document.getElementById('query');
    var result = document.getElementById('result');
    var photoInput = document.getElementById('photoInput');
    var busy = false;
    var AI = <?php echo $aiEnabled ? 'true' : 'false'; ?>;

    function setBusy(on) {
      busy = on;
      document.getElementById('goBtn').disabled = on;
      if (AI) document.getElementById('photoBtn').disabled = on;
    }

    function showMessage(html) {
      result.innerHTML = '<div class="result-card"><div class="result-msg">' + html + '</div></div>';
      if (window.HUD) HUD.note(result.textContent);
    }

    function lookup(payload, photoUrl) {
      if (busy) return;
      setBusy(true);
      showMessage('<div class="spinner"></div>' + (payload.image ? 'Identifying your car…' : 'Looking up ' + esc(payload.query) + '…'));
      result.scrollIntoView({ behavior: 'smooth', block: 'start' });

      fetch(AI ? '/cars/api.php' : '/cars/lookup.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
        .then(function (r) { return r.json().catch(function () { return { error: 'Something went wrong. Try again.' }; }); })
        .then(function (car) {
          if (car.error) return showMessage(esc(car.error));
          if (!car.identified) return showMessage('Couldn’t spot a car there.' + (car.note ? '<br><small>' + esc(car.note) + '</small>' : ''));
          car._query = payload.query || '';
          render(car, photoUrl);
        })
        .catch(function () { showMessage('Couldn’t reach the server. Check your connection and try again.'); })
        .then(function () { setBusy(false); });
    }

    function render(car, photoUrl) {
      var title = [car.make, car.model].filter(Boolean).join(' ');
      var sub = [car.years, car.trim].filter(Boolean).join(' · ');
      var list = function (items) { return items.map(function (i) { return '<li>' + esc(i) + '</li>'; }).join(''); };
      var pic = photoUrl || car.image;
      var hasPrices = car.price_new || car.price_used;

      result.innerHTML =
        '<article class="result-card">' +
          '<div class="result-head">' +
            (pic ? '<img class="result-photo" src="' + esc(pic) + '" alt="' + esc(title) + '">' : '<div class="car-art">' + (ART[car.body_style] || ART.sedan) + '</div>') +
            '<div>' +
              '<h2 class="result-title">' + esc(title) + (photoUrl ? '<span class="badge">' + esc(car.confidence) + ' match</span>' : '') + '</h2>' +
              '<div class="result-sub">' + esc(sub) + '</div>' +
            '</div>' +
          '</div>' +
          '<div class="result-body">' +
            '<p class="result-summary">' + esc(car.summary) + '</p>' +
            '<div class="viewer-wrap" id="viewer3d"></div>' +
            (hasPrices ? '<div class="price-row">' +
              (car.price_new ? '<div class="price"' + explainAttrs('New price (approx. MSRP)', car.price_new) + '><div class="price-label">New price (approx. MSRP)</div><div class="price-value">' + esc(car.price_new) + '</div></div>' : '') +
              (car.price_used ? '<div class="price"><div class="price-label">Used</div><div class="price-value">' + esc(car.price_used) + '</div></div>' : '') +
            '</div>' : '') +
            '<div class="spec-grid">' + car.specs.map(function (s) {
              return '<div class="spec"' + explainAttrs(s.label, s.value) + '><div class="spec-label">' + esc(s.label) + '</div><div class="spec-value">' + esc(s.value) + '</div></div>';
            }).join('') + '</div>' +
            (car.versions && car.versions.length ? '<div class="section-label">Engine options</div><div class="versions"><table>' +
              '<tr><th><span' + explainAttrs('Engine') + '>Engine</span></th><th><span' + explainAttrs('Transmission') + '>Transmission</span></th><th><span' + explainAttrs('Fuel economy') + '>Fuel economy</span></th></tr>' +
              car.versions.map(function (v) {
                return '<tr><td><span' + explainAttrs('Engine', v.engine) + '>' + esc(v.engine) + '</span></td>' +
                  '<td><span' + explainAttrs('Transmission', v.transmission) + '>' + esc(v.transmission) + '</span></td>' +
                  '<td><span' + explainAttrs('Fuel economy', v.mpg) + '>' + esc(v.mpg) + '</span></td></tr>';
              }).join('') + '</table></div>' : '') +
            (car.facts && car.facts.length ? '<div class="section-label">Did you know</div><ul class="facts">' + list(car.facts) + '</ul>' : '') +
            (car.pros.length ? '<div class="procon">' +
              '<div class="pros"><div class="section-label">Pros</div><ul>' + list(car.pros) + '</ul></div>' +
              '<div class="cons"><div class="section-label">Cons</div><ul>' + list(car.cons) + '</ul></div>' +
            '</div>' : '') +
            (car.links && car.links.length ? '<div class="result-links">' + car.links.map(function (l) {
              return '<a href="' + esc(l.url) + '" target="_blank" rel="noopener">' + esc(l.label) + ' &nearr;</a>';
            }).join('') + '</div>' : '') +
            (car.rivals.length ? '<div class="rivals">Compare with: ' + car.rivals.map(function (r) {
              return '<button type="button" data-car="' + esc(r) + '">' + esc(r) + '</button>';
            }).join('') + '</div>' : '') +
            '<p class="result-note">' + (car.note ? esc(car.note) + ' ' : '') + (AI ? 'Figures are estimates for the US market.' : '') + '</p>' +
          '</div>' +
        '</article>';

      if (car.models) showViewer(car.models);
      else if (car.identified && car.model) findModels(car.make, car.model, car.trim, car.years);
      if (window.HUD) HUD.result(car);
    }

    // ---------- 3D viewer (Sketchfab) ----------
    // The poster is just an image; the heavy 3D player only loads when tapped.
    var viewerModels = [];

    function findModels(make, model, version, year) {
      var box = document.getElementById('viewer3d');
      box.innerHTML = '<div class="viewer-loading"><div class="spinner"></div>Looking for a 3D model\u2026</div>';
      fetch('/cars/models.php?make=' + encodeURIComponent(make) + '&model=' + encodeURIComponent(model) +
        '&version=' + encodeURIComponent(version || '') + '&year=' + encodeURIComponent(parseInt(year, 10) || ''))
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (box !== document.getElementById('viewer3d')) return; // a newer result replaced this one
          if (d.models && d.models.length) showViewer(d.models);
          else box.innerHTML = '';
        })
        .catch(function () { box.innerHTML = ''; });
    }

    function showViewer(models, index) {
      var box = document.getElementById('viewer3d');
      if (!box) return;
      viewerModels = models;
      index = index || 0;
      var m = models[index];
      box.innerHTML =
        '<div class="viewer" id="viewerStage">' +
          '<button type="button" class="viewer-poster" data-play="' + index + '" aria-label="Load 3D model of ' + esc(m.name) + '">' +
            (m.thumb ? '<img src="' + esc(m.thumb) + '" alt="">' : '') +
            '<span class="viewer-play">' + CUBE.replace(/12/g, '18') + ' View in 3D</span>' +
            '<span class="viewer-hint">Drag to spin &middot; scroll or pinch to zoom</span>' +
          '</button>' +
        '</div>' +
        '<div class="viewer-meta">' +
          '<span>3D model: <a href="https://sketchfab.com/models/' + esc(m.uid) + '" target="_blank" rel="noopener">' + esc(m.name) + '</a> by ' + esc(m.author) +
            (m.license ? ' &middot; ' + esc(m.license) : '') + ' &middot; via Sketchfab</span>' +
          (models.length > 1 ? '<span class="viewer-picks">' + models.map(function (o, i) {
            return '<button type="button" data-pick="' + i + '" aria-pressed="' + (i === index) + '" aria-label="' + esc(o.name) + '">' +
              (o.thumb ? '<img src="' + esc(o.thumb) + '" alt="">' : '') + '</button>';
          }).join('') + '</span>' : '') +
        '</div>';
    }

    result.addEventListener('click', function (e) {
      var play = e.target.closest('[data-play]');
      if (play) {
        var m = viewerModels[+play.dataset.play];
        document.getElementById('viewerStage').innerHTML =
          '<iframe title="3D model of ' + esc(m.name) + '" src="https://sketchfab.com/models/' + encodeURIComponent(m.uid) +
          '/embed?autostart=1&preload=1&ui_theme=dark&ui_infos=0&ui_hint=2&dnt=1" allow="autoplay; fullscreen; xr-spatial-tracking" allowfullscreen></iframe>';
        return;
      }
      var pick = e.target.closest('[data-pick]');
      if (pick) showViewer(viewerModels, +pick.dataset.pick);
    });

    function showRace(id) {
      var r = RACE_CARS.filter(function (x) { return x.id === id; })[0];
      if (!r) return;
      render({
        identified: false,
        model: r.name,
        years: r.tag,
        summary: r.summary,
        image: r.model.thumb,
        specs: r.specs.map(function (s) { return { label: s[0], value: s[1] }; }),
        facts: r.facts,
        pros: [], cons: [], rivals: [],
        links: [{ label: 'Learn more on Wikipedia', url: r.wiki }],
        note: 'Race car figures are approximate and change with the rules each season.',
        models: [r.model],
        raceId: r.id
      });
      result.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var q = queryEl.value.trim();
      if (q) lookup({ query: q });
      else queryEl.focus();
    });

    // Any element with data-car (top rated cards, hints, rivals) triggers a lookup.
    document.addEventListener('click', function (e) {
      var race = e.target.closest('[data-race]');
      if (race) return showRace(race.dataset.race);
      var el = e.target.closest('[data-car]');
      if (!el) return;
      queryEl.value = el.dataset.car;
      lookup({ query: el.dataset.car });
    });

    // Deep link from the All Cars page: /?car=Mazda+MX-5
    var linked = new URLSearchParams(location.search).get('car');
    if (linked) { queryEl.value = linked; lookup({ query: linked }); }

    // ---------- Photo (only when an API key is set up) ----------
    if (!AI) return;
    document.getElementById('photoBtn').addEventListener('click', function () { photoInput.click(); });

    photoInput.addEventListener('change', function () {
      var file = photoInput.files[0];
      photoInput.value = '';
      if (!file) return;
      shrink(file, 1280, function (dataUrl) {
        if (!dataUrl) return showMessage('That file doesn’t look like a photo.');
        lookup({ image: dataUrl.split(',')[1] }, dataUrl);
      });
    });

    // Phone photos are huge — scale down to keep uploads small and fast.
    function shrink(file, max, done) {
      var img = new Image();
      var url = URL.createObjectURL(file);
      img.onload = function () {
        var scale = Math.min(1, max / Math.max(img.width, img.height));
        var canvas = document.createElement('canvas');
        canvas.width = Math.round(img.width * scale);
        canvas.height = Math.round(img.height * scale);
        canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
        URL.revokeObjectURL(url);
        done(canvas.toDataURL('image/jpeg', 0.85));
      };
      img.onerror = function () { URL.revokeObjectURL(url); done(null); };
      img.src = url;
    }
  })();
  </script>
</body>
</html>
