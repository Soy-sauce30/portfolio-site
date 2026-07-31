<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Memory Match — Sawyer</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/style.css">
  <link rel="stylesheet" href="/games/game.css">
  <script>
    (function(){var t=localStorage.getItem('theme')||(window.matchMedia('(prefers-color-scheme:light)').matches?'light':'dark');document.documentElement.setAttribute('data-theme',t)})();
  </script>
  <style>
    .mm-grid {
      display: grid;
      gap: 10px;
      width: 100%;
      max-width: 440px;
    }

    .mm-card {
      aspect-ratio: 1;
      perspective: 700px;
      background: none;
      border: none;
      padding: 0;
      cursor: pointer;
    }

    .mm-inner {
      position: relative;
      width: 100%;
      height: 100%;
      transform-style: preserve-3d;
      transition: transform 0.35s cubic-bezier(0.4, 0.1, 0.3, 1.2);
    }

    .mm-card.flipped .mm-inner,
    .mm-card.matched .mm-inner { transform: rotateY(180deg); }

    .mm-face {
      position: absolute;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 9px;
      backface-visibility: hidden;
      -webkit-backface-visibility: hidden;
      border: 1px solid var(--border);
    }

    .mm-back {
      background: var(--accent-dim);
      color: var(--accent);
      font-size: 1.3rem;
      font-weight: 700;
    }

    .mm-front {
      background: var(--bg-card);
      transform: rotateY(180deg);
      font-size: clamp(1.4rem, 7vw, 2rem);
      border-color: var(--accent-glow);
    }

    .mm-card.matched .mm-front {
      border-color: var(--accent);
      box-shadow: 0 0 14px var(--accent-glow);
    }

    .mm-card.matched { cursor: default; }
    .mm-card:disabled { cursor: default; }

    .mm-sizes { display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: center; }
    .mm-sizes .game-btn.active {
      background: var(--accent);
      border-color: var(--accent);
      color: #fff;
    }
  </style>
</head>
<body>

  <?php include '../../header.php'; ?>

  <div class="game-wrap">

    <div class="game-head">
      <a class="game-back" href="/games/">&larr; All games</a>
      <h1 class="game-title">Memory Match</h1>
      <p class="game-blurb">Flip two cards at a time and clear the board in as few moves as you can.</p>
    </div>

    <div class="game-stage">

      <div class="game-bar">
        <div class="game-stat">
          <span class="game-stat-label">Moves</span>
          <span class="game-stat-value" id="mmMoves">0</span>
        </div>
        <div class="game-stat">
          <span class="game-stat-label">Pairs</span>
          <span class="game-stat-value" id="mmPairs">0/8</span>
        </div>
        <div class="game-stat">
          <span class="game-stat-label">Time</span>
          <span class="game-stat-value" id="mmTime">0:00</span>
        </div>
        <div class="game-stat">
          <span class="game-stat-label">Best</span>
          <span class="game-stat-value" id="mmBest">—</span>
        </div>
      </div>

      <div class="mm-grid" id="mmGrid"></div>

      <div class="game-msg" id="mmMsg"></div>

      <div class="mm-sizes">
        <button class="game-btn active" data-pairs="8">4 &times; 4</button>
        <button class="game-btn" data-pairs="10">4 &times; 5</button>
        <button class="game-btn" data-pairs="18">6 &times; 6</button>
      </div>

      <button class="game-btn ghost" id="mmNew">New game</button>

    </div>

    <p class="game-help">
      Click or tap a card to flip it. Two matching cards stay face up.
      Best score is the fewest moves for the current board size.
    </p>

  </div>

  <?php include '../../footer.php'; ?>

  <script src="/script.js"></script>
  <script src="/theme.js"></script>
  <script>
  (function () {
    'use strict';

    var SYMBOLS = ['🍄','🌵','🐙','🦀','🍕','🚀','🎲','🎧','🪐','🦊','🍑','⚡','🧊','🎯','🐝','🌙','🔮','🍉'];

    var gridEl = document.getElementById('mmGrid');
    var msgEl = document.getElementById('mmMsg');

    var pairs = 8;
    var cards = [];        // {symbol, el, matched}
    var first = null;
    var lock = false;
    var moves = 0;
    var matched = 0;
    var startTime = null;
    var timer = null;

    function bestKey() { return 'memory-best-' + pairs; }

    function loadBest() {
      var v = parseInt(localStorage.getItem(bestKey()), 10);
      return isNaN(v) ? null : v;
    }

    function saveBest(v) {
      try { localStorage.setItem(bestKey(), String(v)); } catch (e) {}
    }

    function shuffle(arr) {
      for (var i = arr.length - 1; i > 0; i--) {
        var j = Math.floor(Math.random() * (i + 1));
        var t = arr[i]; arr[i] = arr[j]; arr[j] = t;
      }
      return arr;
    }

    function columnsFor(n) {
      // n = total cards. Pick a column count that lays out evenly.
      if (n === 16) return 4;
      if (n === 20) return 5;
      if (n === 36) return 6;
      return 4;
    }

    function build() {
      var deck = [];
      SYMBOLS.slice(0, pairs).forEach(function (s) { deck.push(s, s); });
      shuffle(deck);

      gridEl.style.gridTemplateColumns = 'repeat(' + columnsFor(deck.length) + ', 1fr)';
      gridEl.innerHTML = '';
      cards = [];

      deck.forEach(function (symbol, i) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'mm-card';
        btn.setAttribute('aria-label', 'Card ' + (i + 1));

        var inner = document.createElement('div');
        inner.className = 'mm-inner';

        var back = document.createElement('div');
        back.className = 'mm-face mm-back';
        back.textContent = '?';

        var front = document.createElement('div');
        front.className = 'mm-face mm-front';
        front.textContent = symbol;

        inner.appendChild(back);
        inner.appendChild(front);
        btn.appendChild(inner);
        gridEl.appendChild(btn);

        var card = { symbol: symbol, el: btn, matched: false };
        cards.push(card);
        btn.addEventListener('click', function () { flip(card); });
      });
    }

    function flip(card) {
      if (lock || card.matched || card === first) return;
      if (card.el.classList.contains('flipped')) return;

      if (!startTime) startClock();

      card.el.classList.add('flipped');

      if (!first) { first = card; return; }

      moves++;
      document.getElementById('mmMoves').textContent = moves;

      if (first.symbol === card.symbol) {
        first.matched = card.matched = true;
        first.el.classList.add('matched');
        card.el.classList.add('matched');
        first.el.disabled = card.el.disabled = true;
        first = null;
        matched++;
        document.getElementById('mmPairs').textContent = matched + '/' + pairs;
        if (matched === pairs) win();
      } else {
        lock = true;
        var a = first, b = card;
        first = null;
        setTimeout(function () {
          a.el.classList.remove('flipped');
          b.el.classList.remove('flipped');
          lock = false;
        }, 750);
      }
    }

    function startClock() {
      startTime = Date.now();
      timer = setInterval(function () {
        document.getElementById('mmTime').textContent = elapsed();
      }, 1000);
    }

    function elapsed() {
      if (!startTime) return '0:00';
      var s = Math.floor((Date.now() - startTime) / 1000);
      return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
    }

    function win() {
      clearInterval(timer);
      var best = loadBest();
      var record = best === null || moves < best;
      if (record) saveBest(moves);
      renderBest();
      msgEl.textContent = 'Cleared in ' + moves + ' moves and ' + elapsed()
        + (record ? ' — new best.' : '.');
    }

    function renderBest() {
      var b = loadBest();
      document.getElementById('mmBest').textContent = b === null ? '—' : b;
    }

    function newGame() {
      clearInterval(timer);
      first = null;
      lock = false;
      moves = 0;
      matched = 0;
      startTime = null;
      timer = null;
      document.getElementById('mmMoves').textContent = '0';
      document.getElementById('mmPairs').textContent = '0/' + pairs;
      document.getElementById('mmTime').textContent = '0:00';
      msgEl.textContent = '';
      renderBest();
      build();
    }

    document.querySelectorAll('.mm-sizes .game-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.querySelectorAll('.mm-sizes .game-btn').forEach(function (b) {
          b.classList.remove('active');
        });
        btn.classList.add('active');
        pairs = parseInt(btn.dataset.pairs, 10);
        newGame();
      });
    });

    document.getElementById('mmNew').addEventListener('click', newGame);

    newGame();
  })();
  </script>
</body>
</html>
