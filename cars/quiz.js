/* =========================================
   Car quiz (homepage).
   Every question is generated from the site's own
   fact-checked data, so the answers are always right:
   - /cars/data/catalog.json  (EPA specs + checked prices)
   - RACE_CARS from /cars/racing.js
   10 random questions per round; best score is kept
   in this visitor's browser.
   ========================================= */
(function () {
  var root = document.getElementById('quiz');
  if (!root) return;
  var stage = document.getElementById('quizStage');
  var ROUND = 10;
  var BEST_KEY = 'quizBest';

  var catalog = null;  // current versions, loaded on first start
  var questions = [], index = 0, score = 0;

  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function pick(list) { return list[Math.floor(Math.random() * list.length)]; }
  function shuffle(list) {
    var a = list.slice();
    for (var i = a.length - 1; i > 0; i--) { var j = Math.floor(Math.random() * (i + 1)); var t = a[i]; a[i] = a[j]; a[j] = t; }
    return a;
  }
  function sample(list, n, avoid) {
    return shuffle(list.filter(function (x) { return !avoid || !avoid(x); })).slice(0, n);
  }
  function money(n) { return '$' + Math.round(n / 1000).toLocaleString() + ',000'; }
  function getBest() { try { return +localStorage.getItem(BEST_KEY) || 0; } catch (e) { return 0; } }
  function setBest(n) { try { localStorage.setItem(BEST_KEY, String(n)); } catch (e) {} }

  // ---------- Data helpers ----------
  var TYPE = { car: 'Car', suv: 'SUV', truck: 'Pickup truck', sports: 'Two-seat sports car', van: 'Minivan / van', wagon: 'Wagon / hatchback' };
  var AS = { car: 'a car', suv: 'an SUV', truck: 'a pickup truck', sports: 'a two-seat sports car', van: 'a minivan or van', wagon: 'a wagon' };

  // One entry per model (make + base) with its starting price, built from the versions.
  function models() {
    var byKey = {};
    catalog.forEach(function (c) {
      var k = c.make + '|' + c.base;
      var m = byKey[k] || (byKey[k] = { make: c.make, base: c.base, types: {}, start: Infinity });
      c.types.forEach(function (t) { m.types[t] = 1; });
      var p = c.price || c.modelPrice;
      if (p) m.start = Math.min(m.start, p[0]);
    });
    return Object.keys(byKey).map(function (k) { return byKey[k]; });
  }

  // Names that only one brand uses and that read clearly in a question ("Which brand makes the 3?" doesn't).
  function clearNames(list) {
    var count = {};
    list.forEach(function (m) { count[m.base] = (count[m.base] || 0) + 1; });
    return list.filter(function (m) { return count[m.base] === 1 && m.base.length >= 2 && !/^\d{1,2}$/.test(m.base); });
  }

  function name(c) { return c.make + ' ' + c.name; }

  // ---------- Question makers (each returns null if it can't build a fair question) ----------
  function brandQuestion() {
    var all = models();
    var m = pick(clearNames(all));
    var makes = Object.keys(all.reduce(function (o, x) { o[x.make] = 1; return o; }, {}));
    var wrong = sample(makes, 3, function (x) { return x === m.make; });
    return {
      kind: 'Brands',
      q: 'Which brand makes the ' + m.base + '?',
      options: shuffle([m.make].concat(wrong)),
      answer: m.make,
      why: 'The ' + m.base + ' is made by ' + m.make + '.'
    };
  }

  function priceQuestion() {
    var list = models().filter(function (m) { return isFinite(m.start); });
    var m = pick(list);
    var right = Math.round(m.start / 1000) * 1000;
    // Distractors well apart from the answer and from each other
    var factors = shuffle([0.55, 0.7, 1.35, 1.6, 1.9]).slice(0, 3);
    var opts = [right].concat(factors.map(function (f) { return Math.round(right * f / 1000) * 1000; }));
    return {
      kind: 'Prices',
      q: 'About how much does a new ' + m.make + ' ' + m.base + ' start at?',
      options: shuffle(opts.map(money)),
      answer: money(right),
      why: 'A new ' + m.make + ' ' + m.base + ' starts at about ' + money(right) + ' (approximate base MSRP, before destination fees).'
    };
  }

  function mpgQuestion() {
    // Gas or hybrid versions with a single combined rating, all different from each other
    var pool = catalog.filter(function (c) {
      return c.mpg && c.mpg[0] === c.mpg[1] && !c.mpge && c.fuels.indexOf('ev') === -1;
    });
    var chosen = [], used = {};
    shuffle(pool).some(function (c) {
      if (used[c.mpg[1]] || chosen.some(function (x) { return x.make === c.make && x.base === c.base; })) return false;
      used[c.mpg[1]] = 1; chosen.push(c);
      return chosen.length === 4;
    });
    if (chosen.length < 4) return null;
    var best = chosen.reduce(function (a, b) { return b.mpg[1] > a.mpg[1] ? b : a; });
    return {
      kind: 'Fuel economy',
      q: 'Which of these gets the best combined fuel economy?',
      options: chosen.map(name),
      answer: name(best),
      why: chosen.slice().sort(function (a, b) { return b.mpg[1] - a.mpg[1]; })
        .map(function (c) { return name(c) + ': ' + c.mpg[1] + ' mpg'; }).join(' · ') + ' (EPA combined).'
    };
  }

  function evQuestion() {
    var evs = catalog.filter(function (c) { return c.fuels.length === 1 && c.fuels[0] === 'ev'; });
    var gas = catalog.filter(function (c) { return c.fuels.length === 1 && c.fuels[0] === 'gas'; });
    var ev = pick(evs);
    var wrong = sample(gas, 3, function (c) { return c.make === ev.make && c.base === ev.base; });
    return {
      kind: 'Electric cars',
      q: 'Which one of these is fully electric?',
      options: shuffle([name(ev)].concat(wrong.map(name))),
      answer: name(ev),
      why: 'The ' + name(ev) + ' runs only on battery power' + (ev.range ? ', with an EPA range of up to ' + ev.range + ' miles.' : '.')
    };
  }

  function typeQuestion() {
    // EPA calls some small crossovers "wagons" — accurate but confusing, so those aren't asked about.
    var all = models().filter(function (m) { var t = Object.keys(m.types); return t.length === 1 && t[0] !== 'wagon'; });
    var m = pick(clearNames(all));
    var t = Object.keys(m.types)[0];
    var wrong = sample(Object.keys(TYPE), 3, function (x) { return x === t; });
    return {
      kind: 'Vehicle types',
      q: 'What kind of vehicle is the ' + m.make + ' ' + m.base + '?',
      options: shuffle([t].concat(wrong)).map(function (x) { return TYPE[x]; }),
      answer: TYPE[t],
      why: 'The EPA classes the ' + m.make + ' ' + m.base + ' as ' + AS[t] + '.'
    };
  }

  function raceQuestion() {
    if (!window.RACE_CARS) return null;
    var r = pick(RACE_CARS);
    // A spec that only this series has, and that doesn't give away the answer
    var specs = r.specs.filter(function (s) {
      var v = s[1].toLowerCase();
      return v.indexOf(r.name.toLowerCase()) === -1 && RACE_CARS.every(function (o) {
        return o === r || !o.specs.some(function (x) { return x[0] === s[0] && x[1] === s[1]; });
      });
    });
    if (!specs.length) return null;
    var s = pick(specs);
    var wrong = sample(RACE_CARS, 3, function (o) { return o === r; });
    return {
      kind: 'Racing',
      q: 'Which racing car has this spec? ' + s[0] + ': ' + s[1],
      options: shuffle([r.name].concat(wrong.map(function (o) { return o.name; }))),
      answer: r.name,
      why: r.name + ' — ' + s[0].toLowerCase() + ': ' + s[1] + '. ' + r.desc
    };
  }

  var MAKERS = [brandQuestion, brandQuestion, priceQuestion, priceQuestion, mpgQuestion, mpgQuestion, evQuestion, typeQuestion, raceQuestion, raceQuestion];

  function buildRound() {
    var out = [], seen = {};
    var makers = shuffle(MAKERS);
    for (var tries = 0; out.length < ROUND && tries < 60; tries++) {
      var q = makers[tries % makers.length]();
      if (!q || seen[q.q] || new Set(q.options).size !== q.options.length) continue;
      seen[q.q] = 1;
      out.push(q);
    }
    return out;
  }

  // ---------- Screens ----------
  function intro() {
    var best = getBest();
    stage.innerHTML =
      '<div class="quiz-card quiz-intro">' +
        '<p class="quiz-lead">' + ROUND + ' questions on brands, prices, fuel economy, EVs and race cars — all built from the real data on this site, so every answer is checked.</p>' +
        (best ? '<p class="quiz-best">Your best: <b>' + best + ' / ' + ROUND + '</b></p>' : '') +
        '<button type="button" class="finder-btn finder-go quiz-start" data-quiz="start">Start quiz</button>' +
      '</div>';
  }

  function ask() {
    var q = questions[index];
    stage.innerHTML =
      '<div class="quiz-card">' +
        '<div class="quiz-top"><span class="quiz-kind">' + esc(q.kind) + '</span>' +
          '<span class="quiz-progress">Question ' + (index + 1) + ' of ' + questions.length + ' · Score ' + score + '</span></div>' +
        '<div class="quiz-bar" aria-hidden="true"><span style="width:' + (index / questions.length * 100) + '%"></span></div>' +
        '<h3 class="quiz-q" id="quizQ" tabindex="-1">' + esc(q.q) + '</h3>' +
        '<div class="quiz-options" role="group" aria-labelledby="quizQ">' +
          q.options.map(function (o, i) {
            return '<button type="button" class="quiz-option" data-quiz-answer="' + i + '"><span class="quiz-letter">' + 'ABCD'[i] + '</span>' + esc(o) + '</button>';
          }).join('') +
        '</div>' +
        '<div class="quiz-feedback" id="quizFeedback" aria-live="polite"></div>' +
      '</div>';
    document.getElementById('quizQ').focus({ preventScroll: true });
  }

  function answer(i) {
    var q = questions[index];
    var picked = q.options[i];
    var right = picked === q.answer;
    if (right) score++;
    stage.querySelectorAll('.quiz-option').forEach(function (b, j) {
      b.disabled = true;
      if (q.options[j] === q.answer) b.classList.add('is-right');
      else if (j === i) b.classList.add('is-wrong');
    });
    var last = index === questions.length - 1;
    document.getElementById('quizFeedback').innerHTML =
      '<p class="quiz-verdict ' + (right ? 'is-right' : 'is-wrong') + '">' + (right ? 'Correct!' : 'Not quite — the answer is ' + esc(q.answer) + '.') + '</p>' +
      '<p class="quiz-why">' + esc(q.why) + '</p>' +
      '<button type="button" class="finder-btn finder-go" data-quiz="next">' + (last ? 'See my score' : 'Next question') + '</button>';
    stage.querySelector('[data-quiz="next"]').focus({ preventScroll: true });
  }

  function finish() {
    var best = getBest();
    var newBest = score > best;
    if (newBest) setBest(score);
    var msg = score === questions.length ? 'Perfect score — you really know your cars!'
      : score >= questions.length * 0.7 ? 'Great job — you know your stuff.'
      : score >= questions.length * 0.4 ? 'Not bad! Play again to beat it.'
      : 'Tough round — try another, the questions change every time.';
    stage.innerHTML =
      '<div class="quiz-card quiz-done">' +
        '<p class="quiz-score" tabindex="-1" id="quizScore"><b>' + score + '</b> / ' + questions.length + '</p>' +
        '<p class="quiz-lead">' + msg + '</p>' +
        '<p class="quiz-best">' + (newBest ? 'New best score!' : 'Your best: <b>' + Math.max(best, score) + ' / ' + ROUND + '</b>') + '</p>' +
        '<button type="button" class="finder-btn finder-go" data-quiz="start">Play again</button>' +
      '</div>';
    document.getElementById('quizScore').focus({ preventScroll: true });
  }

  function start() {
    var go = function () {
      questions = buildRound();
      index = 0; score = 0;
      ask();
    };
    if (catalog) return go();
    stage.innerHTML = '<div class="quiz-card"><div class="result-msg"><div class="spinner"></div>Loading questions…</div></div>';
    fetch('/cars/data/catalog.json')
      .then(function (r) { return r.json(); })
      .then(function (d) { catalog = d.cars; go(); })
      .catch(function () {
        stage.innerHTML = '<div class="quiz-card"><p class="quiz-lead">Couldn’t load the questions. Check your connection and try again.</p>' +
          '<button type="button" class="finder-btn finder-go" data-quiz="start">Try again</button></div>';
      });
  }

  stage.addEventListener('click', function (e) {
    var b = e.target.closest('button');
    if (!b || b.disabled) return;
    if (b.dataset.quiz === 'start') start();
    else if (b.dataset.quiz === 'next') { index++; if (index < questions.length) ask(); else finish(); }
    else if (b.hasAttribute('data-quiz-answer')) answer(+b.dataset.quizAnswer);
  });

  intro();
})();
