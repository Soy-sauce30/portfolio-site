<?php
// Asset links carry the file's modified time (?v=…) so browsers always pick up the latest version.
function asset($path) { return $path . '?v=' . (@filemtime($_SERVER['DOCUMENT_ROOT'] . $path) ?: 1); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All Cars — Sawyer's Garage</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&family=Fraunces:ital,opsz,wght@0,9..144,600;1,9..144,600&family=Barlow+Condensed:ital,wght@1,700;1,800&family=Rajdhani:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('/style.css') ?>">
  <script>
    // Car pages also offer a third "HUD" look (dark only), stored separately so other pages stay light/dark.
    (function(){var d=document.documentElement,t,l;try{t=localStorage.getItem('theme');l=localStorage.getItem('look')}catch(e){}t=t||(window.matchMedia('(prefers-color-scheme:light)').matches?'light':'dark');if(t!=='light'&&l==='hud')t='hud';d.setAttribute('data-theme',t);d.setAttribute('data-hud-ok','')})();
  </script>
  <link rel="stylesheet" href="<?= asset('/cars/site.css') ?>">
  <link rel="stylesheet" href="<?= asset('/cars/themes.css') ?>">
  <link rel="stylesheet" href="<?= asset('/cars/hud.css') ?>">
</head>
<body>

  <?php include __DIR__ . '/../header.php'; ?>
  <?php include __DIR__ . '/hud-bar.php'; ?>

  <section class="home-hero cat-hero" id="home">
    <div class="home-kicker"><span>EPA data</span> Every model sold in the US since 1984</div>
    <h1 class="home-name">Every car.<br><span class="grad-text">Every price.</span></h1>
    <p class="home-tagline" id="catTagline">Browse every make and model, filter by type, fuel and budget, and tap any car for the full specs.</p>
  </section>

  <section class="home-section cat-section" id="all">
    <div class="cat-toolbar">
      <div class="finder cat-search">
        <span class="finder-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></span>
        <input type="text" id="catQuery" placeholder="Search make or model…" aria-label="Search make or model" autocomplete="off">
      </div>
      <select class="cat-select" id="catMake" aria-label="Make"><option value="">All makes</option></select>
      <select class="cat-select" id="catBase" aria-label="Model" disabled><option value="">All models</option></select>
      <select class="cat-select" id="catSort" aria-label="Sort">
        <option value="name">A–Z</option>
        <option value="price-asc">Price: low to high</option>
        <option value="price-desc">Price: high to low</option>
        <option value="mpg">Best fuel economy</option>
        <option value="newest">Newest first</option>
      </select>
    </div>

    <div class="cat-filters">
      <div class="filters" data-group="era">
        <button type="button" data-value="current" aria-pressed="true">On sale now</button>
        <button type="button" data-value="all" aria-pressed="false">All years</button>
      </div>
      <div class="filters" data-group="type">
        <button type="button" data-value="" aria-pressed="true">All types</button>
        <button type="button" data-value="car" aria-pressed="false">Cars</button>
        <button type="button" data-value="suv" aria-pressed="false">SUVs</button>
        <button type="button" data-value="truck" aria-pressed="false">Trucks</button>
        <button type="button" data-value="sports" aria-pressed="false">Sports</button>
        <button type="button" data-value="van" aria-pressed="false">Vans</button>
        <button type="button" data-value="wagon" aria-pressed="false">Wagons</button>
      </div>
      <div class="filters" data-group="fuel">
        <button type="button" data-value="" aria-pressed="true">Any fuel</button>
        <button type="button" data-value="gas" aria-pressed="false">Gas</button>
        <button type="button" data-value="hybrid" aria-pressed="false">Hybrid</button>
        <button type="button" data-value="phev" aria-pressed="false">Plug-in</button>
        <button type="button" data-value="ev" aria-pressed="false">Electric</button>
      </div>
      <div class="filters" data-group="price">
        <button type="button" data-value="" aria-pressed="true">Any price</button>
        <button type="button" data-value="0-30000" aria-pressed="false">Under $30k</button>
        <button type="button" data-value="30000-50000" aria-pressed="false">$30–50k</button>
        <button type="button" data-value="50000-100000" aria-pressed="false">$50–100k</button>
        <button type="button" data-value="100000-" aria-pressed="false">$100k+</button>
      </div>
    </div>

    <p class="cat-count" id="catCount" aria-live="polite">Loading every car…</p>
    <div class="cat-grid" id="catGrid"></div>
    <div class="cat-more"><button type="button" class="finder-btn finder-go" id="catMore" hidden>Show more</button></div>
  </section>

  <hr class="home-rule">

  <div class="home-foot" id="contact">
    <span>Specs: U.S. EPA (fueleconomy.gov). Prices are approximate MSRPs: “~” is that version’s price, a range with a model name is the whole model’s base-to-top-trim range. Check a dealer for exact pricing.</span>
    <span><a href="/">&larr; Car finder</a></span>
  </div>

<?php include __DIR__ . '/../footer.php'; ?>

  <script src="<?= asset('/script.js') ?>"></script>
  <script src="<?= asset('/theme.js') ?>"></script>
  <script src="<?= asset('/cars/explain.js') ?>"></script>
  <script src="<?= asset('/cars/hud.js') ?>"></script>
  <script>
  (function () {
    var PAGE = 60;
    var TYPE_LABEL = { car: 'Car', suv: 'SUV', truck: 'Truck', sports: 'Sports', van: 'Van', wagon: 'Wagon' };
    var FUEL_LABEL = { gas: 'Gas', hybrid: 'Hybrid', phev: 'Plug-in hybrid', ev: 'Electric', diesel: 'Diesel', hydrogen: 'Hydrogen' };

    var state = { q: '', make: '', base: '', sort: 'name', era: 'current', type: '', fuel: '', price: '', shown: PAGE };
    var sets = { current: null, all: null }; // "all" (every year, ~1 MB) only loads if asked for
    var cars = [];

    var grid = document.getElementById('catGrid');
    var count = document.getElementById('catCount');
    var more = document.getElementById('catMore');
    var makeSel = document.getElementById('catMake');
    var baseSel = document.getElementById('catBase');

    function esc(s) {
      return String(s).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
      });
    }
    function norm(s) { return s.toLowerCase().replace(/[^a-z0-9]/g, ''); }
    function money(n) { return n >= 1000000 ? '$' + (n / 1000000).toFixed(1).replace('.0', '') + 'M' : '$' + Math.round(n / 1000) + 'k'; }
    function priceText(p) { return p[0] === p[1] ? '~' + money(p[0]) : money(p[0]) + ' – ' + money(p[1]); }
    function anyPrice(c) { return c.price || c.modelPrice; }
    function best(c) { return Math.max(c.mpg ? c.mpg[1] : 0, c.mpge ? c.mpge[1] : 0); }

    function matches(c) {
      if (state.make && c.make !== state.make) return false;
      if (state.base && c.base !== state.base) return false;
      if (state.type && c.types.indexOf(state.type) === -1) return false;
      if (state.fuel && c.fuels.indexOf(state.fuel) === -1) return false;
      if (state.price) {
        var p = anyPrice(c);
        if (!p) return false;
        var r = state.price.split('-'), lo = +r[0], hi = r[1] ? +r[1] : Infinity;
        if (p[0] >= hi || p[0] < lo) return false; // by starting price
      }
      if (state.q && c._search.indexOf(state.q) === -1) return false;
      return true;
    }

    var SORTS = {
      name: function (a, b) { return a._name < b._name ? -1 : 1; },
      'price-asc': function (a, b) { return (anyPrice(a) ? anyPrice(a)[0] : Infinity) - (anyPrice(b) ? anyPrice(b)[0] : Infinity); },
      'price-desc': function (a, b) { return (anyPrice(b) ? anyPrice(b)[1] : -1) - (anyPrice(a) ? anyPrice(a)[1] : -1); },
      mpg: function (a, b) { return best(b) - best(a); },
      newest: function (a, b) { return b.years[1] - a.years[1] || a.years[0] - b.years[0]; }
    };

    // Specs keep their normal look; these attributes just make them tappable (cars/explain.js).
    function explainAttrs(label, value) {
      if (!window.Explain || !Explain.describe(label, value)) return ''; // nothing to explain → leave it plain
      return ' data-explain="' + esc(label) + '"' + (value ? ' data-value="' + esc(value) + '"' : '') + ' role="button" tabindex="0" aria-expanded="false"';
    }
    function x(label, text) { return '<span' + explainAttrs(label, text) + '>' + esc(text) + '</span>'; }

    function card(c) {
      var years = c.years[0] === c.years[1] ? c.years[0] : c.years[0] + '–' + c.years[1];
      function span(r, unit) { return r ? (r[0] === r[1] ? r[1] : r[0] + '–' + r[1]) + unit : ''; }
      var eff = [
        c.mpg ? x('MPG', span(c.mpg, ' mpg')) : '',
        c.mpge ? x('MPGe', span(c.mpge, ' MPGe')) : '',
        c.range ? x('Range', c.range + ' mi range') : ''
      ].filter(Boolean).join(' · ');
      var p = c.price || c.modelPrice;
      var price = p ? '<span class="cat-price"' + explainAttrs('Price', priceText(p)) + '>' + priceText(p) + (c.price ? '' : '<small>' + esc(c.base) + ' range</small>') + '</span>'
        : '<span class="cat-price cat-price-none">' + (c.current ? 'Price not listed' : 'No longer sold new') + '</span>';
      // Older versions link with their last model year so the lookup finds the right car.
      var q = (c.current ? '' : c.years[1] + ' ') + c.make + ' ' + c.name;
      // The name is the real link; a click anywhere else on the card is forwarded to it (see below).
      return '<article class="car-card cat-card">' +
        '<div class="car-info">' +
          '<div class="cat-make">' + esc(c.make) + (c.base !== c.name ? ' &middot; ' + esc(c.base) : '') + '</div>' +
          '<a class="car-name cat-link" href="/?car=' + encodeURIComponent(q) + '">' + esc(c.name) + '</a>' +
          '<div class="cat-meta">' + years + (c.current ? ' &middot; <b>On sale</b>' : '') + '</div>' +
          '<div class="cat-spec">' + [c.engine ? x('Engine', c.engine) : '', c.drive ? x('Drivetrain', c.drive) : ''].filter(Boolean).join(' · ') + '</div>' +
          '<div class="cat-tags">' +
            c.types.map(function (t) { return '<span>' + TYPE_LABEL[t] + '</span>'; }).join('') +
            c.fuels.filter(function (f) { return f !== 'gas' && !(f === 'ev' && c.engine === 'Electric'); }).map(function (f) {
              return '<span class="cat-fuel-' + f + '"' + explainAttrs(FUEL_LABEL[f]) + '>' + FUEL_LABEL[f] + '</span>';
            }).join('') +
          '</div>' +
          '<div class="cat-foot">' + price + (eff ? '<span class="cat-eff">' + eff + '</span>' : '') + '</div>' +
        '</div>' +
      '</article>';
    }

    // Clicking anywhere on a card opens that car — except the name link itself and the
    // tappable specs (cars/explain.js handles those). Done in JS rather than by wrapping
    // the card in a link, so the specs aren't buttons nested inside a link.
    function cardLink(e) {
      if (e.target.closest('a, [data-explain]')) return null;
      if (String(window.getSelection && window.getSelection()).trim()) return null; // selecting text, not clicking
      var card = e.target.closest('.cat-card');
      return card && card.querySelector('.cat-link');
    }
    grid.addEventListener('click', function (e) {
      var link = cardLink(e);
      if (!link) return;
      if (e.ctrlKey || e.metaKey || e.shiftKey) window.open(link.href, '_blank', 'noopener');
      else location.href = link.href;
    });
    grid.addEventListener('auxclick', function (e) { // middle-click → new tab, like a normal link
      if (e.button !== 1) return;
      var link = cardLink(e);
      if (link) { e.preventDefault(); window.open(link.href, '_blank', 'noopener'); }
    });

    function render() {
      var list = cars.filter(matches).sort(SORTS[state.sort]);
      grid.innerHTML = list.slice(0, state.shown).map(card).join('');
      var own = list.filter(function (c) { return c.price; }).length;
      count.textContent = list.length
        ? list.length.toLocaleString() + (list.length === 1 ? ' version' : ' versions') + (own ? ' · ' + own + ' with their own price' : '')
        : 'No cars match — try removing a filter.';
      more.hidden = list.length <= state.shown;
      more.textContent = 'Show more (' + (list.length - state.shown).toLocaleString() + ' left)';
    }

    function refilter() { state.shown = PAGE; render(); }

    function fillMakes() {
      var makes = {};
      cars.forEach(function (c) { makes[c.make] = 1; });
      makeSel.innerHTML = '<option value="">All makes</option>' + Object.keys(makes).sort().map(function (m) {
        return '<option' + (m === state.make ? ' selected' : '') + '>' + esc(m) + '</option>';
      }).join('');
      if (state.make && !makes[state.make]) state.make = '';
      fillBases();
    }

    function fillBases() {
      var bases = {};
      cars.forEach(function (c) { if (c.make === state.make) bases[c.base] = (bases[c.base] || 0) + 1; });
      var names = Object.keys(bases).sort(function (a, b) { return a.localeCompare(b, undefined, { numeric: true }); });
      baseSel.disabled = !state.make;
      baseSel.innerHTML = '<option value="">' + (state.make ? 'All ' + esc(state.make) + ' models' : 'All models') + '</option>' +
        names.map(function (b) { return '<option value="' + esc(b) + '"' + (b === state.base ? ' selected' : '') + '>' + esc(b) + ' (' + bases[b] + ')</option>'; }).join('');
      if (state.base && !bases[state.base]) state.base = '';
    }

    function load(era) {
      if (sets[era]) return Promise.resolve(sets[era]);
      count.textContent = era === 'all' ? 'Loading every version since 1984…' : 'Loading every car…';
      return fetch('/cars/data/' + (era === 'all' ? 'catalog-all.json' : 'catalog.json'))
        .then(function (r) { return r.json(); })
        .then(function (data) {
          data.cars.forEach(function (c) { c._name = (c.make + ' ' + c.name).toLowerCase(); c._search = norm(c.make + c.name) + ' ' + norm(c.name); });
          sets[era] = data;
          return data;
        });
    }

    function useEra(era) {
      return load(era).then(function (data) {
        state.era = era;
        cars = data.cars;
        fillMakes();
        refilter();
      }).catch(function () { count.textContent = 'Couldn’t load the car list. Try refreshing.'; });
    }

    document.getElementById('catQuery').addEventListener('input', function () { state.q = norm(this.value); refilter(); });
    makeSel.addEventListener('change', function () { state.make = this.value; state.base = ''; fillBases(); refilter(); });
    baseSel.addEventListener('change', function () { state.base = this.value; refilter(); });
    document.getElementById('catSort').addEventListener('change', function () { state.sort = this.value; refilter(); });
    more.addEventListener('click', function () { state.shown += PAGE; render(); });

    function press(group, value) {
      document.querySelectorAll('[data-group="' + group + '"] button').forEach(function (x) { x.setAttribute('aria-pressed', x.dataset.value === value); });
    }

    document.querySelectorAll('.cat-filters .filters').forEach(function (bar) {
      bar.addEventListener('click', function (e) {
        var b = e.target.closest('button');
        if (!b) return;
        var group = bar.dataset.group, value = b.dataset.value;
        press(group, value);
        if (group === 'era') return useEra(value);
        state[group] = value;
        // Prices are only for cars on sale now.
        if (group === 'price' && value && state.era !== 'current') { press('era', 'current'); return useEra('current'); }
        refilter();
      });
    });

    useEra('current').then(function () {
      var d = sets.current;
      document.getElementById('catTagline').textContent = d.totalCurrent.toLocaleString() + ' versions on sale today — every trim and drivetrain listed separately — and ' +
        d.total.toLocaleString() + ' since 1984. Filter by type, fuel and budget, and tap any car for the full specs.';
    });
  })();
  </script>
</body>
</html>
