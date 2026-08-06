<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Minesweeper — Sawyer</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/style.css">
  <link rel="stylesheet" href="/games/game.css">
  <script>
    (function(){var t=localStorage.getItem('theme')||(window.matchMedia('(prefers-color-scheme:light)').matches?'light':'dark');document.documentElement.setAttribute('data-theme',t)})();
  </script>
  <style>
    .ms-board {
      display: grid;
      gap: 3px;
      touch-action: manipulation;
      user-select: none;
      max-width: 100%;
      overflow-x: auto;
    }

    .ms-cell {
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Space Grotesk', sans-serif;
      font-size: 0.95rem;
      font-weight: 700;
      background: var(--accent-dim);
      border: 1px solid var(--border);
      border-radius: 4px;
      cursor: pointer;
      padding: 0;
      color: var(--text);
      transition: background 0.12s;
    }

    .ms-cell:hover:not(.open) { background: var(--accent-glow); }

    .ms-cell.open {
      background: rgba(255, 255, 255, 0.04);
      border-color: rgba(255, 255, 255, 0.06);
      cursor: default;
    }

    [data-theme="light"] .ms-cell.open {
      background: rgba(0, 0, 0, 0.04);
      border-color: rgba(0, 0, 0, 0.06);
    }

    .ms-cell.mine { background: #b91c1c; border-color: #b91c1c; color: #fff; }
    .ms-cell.exploded { background: #ef4444; border-color: #ef4444; color: #fff; }
    .ms-cell.flagged { color: var(--accent); }
    .ms-cell.wrong { background: #7f1d1d; color: #fff; }

    /* Classic minesweeper number colors, tuned for both themes */
    .n1 { color: #60a5fa; }
    .n2 { color: #4ade80; }
    .n3 { color: #f87171; }
    .n4 { color: #c084fc; }
    .n5 { color: #fbbf24; }
    .n6 { color: #22d3ee; }
    .n7 { color: #e879f9; }
    .n8 { color: #94a3b8; }

    [data-theme="light"] .n1 { color: #1d4ed8; }
    [data-theme="light"] .n2 { color: #15803d; }
    [data-theme="light"] .n3 { color: #b91c1c; }
    [data-theme="light"] .n4 { color: #7c3aed; }
    [data-theme="light"] .n5 { color: #b45309; }
    [data-theme="light"] .n6 { color: #0e7490; }
    [data-theme="light"] .n7 { color: #a21caf; }
    [data-theme="light"] .n8 { color: #475569; }

    .ms-levels { display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: center; }
    .ms-levels .game-btn.active {
      background: var(--accent);
      border-color: var(--accent);
      color: #fff;
    }

    .ms-flagmode.on {
      background: var(--accent);
      border-color: var(--accent);
      color: #fff;
    }

    @media (max-width: 560px) {
      .ms-cell { width: 26px; height: 26px; font-size: 0.82rem; }
    }
  </style>
</head>
<body>

  <?php include '../../header.php'; ?>

  <div class="game-wrap">

    <div class="game-head">
      <a class="game-back" href="/games/">&larr; All games</a>
      <h1 class="game-title">Minesweeper</h1>
      <p class="game-blurb">Clear every safe square without detonating a mine.</p>
    </div>

    <div class="game-stage">

      <div class="game-bar">
        <div class="game-stat">
          <span class="game-stat-label">Mines left</span>
          <span class="game-stat-value" id="msMines">10</span>
        </div>
        <div class="game-stat">
          <span class="game-stat-label">Time</span>
          <span class="game-stat-value" id="msTime">0:00</span>
        </div>
        <div class="game-stat">
          <span class="game-stat-label">Best</span>
          <span class="game-stat-value" id="msBest">—</span>
        </div>
      </div>

      <div class="ms-board" id="msBoard"></div>

      <div class="game-msg" id="msMsg"></div>

      <div class="ms-levels">
        <button class="game-btn active" data-level="beginner">Beginner</button>
        <button class="game-btn" data-level="intermediate">Intermediate</button>
        <button class="game-btn" data-level="expert">Expert</button>
      </div>

      <div class="game-bar">
        <button class="game-btn ghost ms-flagmode" id="msFlagMode">🚩 Flag mode: off</button>
        <button class="game-btn ghost" id="msNew">New game</button>
      </div>

    </div>

    <p class="game-help">
      Click to reveal, right-click (or flag mode) to place a 🚩.
      Clicking a revealed number with the right number of flags around it clears its neighbours.
      Your first click is always safe.
    </p>

  </div>

  <?php include '../../footer.php'; ?>

  <script src="/script.js"></script>
  <script src="/theme.js"></script>
  <script>
  (function () {
    'use strict';

    var LEVELS = {
      beginner:     { cols: 9,  rows: 9,  mines: 10 },
      intermediate: { cols: 16, rows: 16, mines: 40 },
      expert:       { cols: 22, rows: 16, mines: 80 }
    };

    var boardEl = document.getElementById('msBoard');
    var msgEl = document.getElementById('msMsg');

    var level = 'beginner';
    var cfg = LEVELS[level];
    var cells = [];        // flat array of {mine, open, flag, count, el}
    var placed = false;    // mines are laid after the first click
    var over = false;
    var won = false;
    var flagMode = false;
    var flagsUsed = 0;
    var startTime = null;
    var timer = null;

    function bestKey() { return 'minesweeper-best-' + level; }

    function loadBest() {
      var v = parseInt(localStorage.getItem(bestKey()), 10);
      return isNaN(v) ? null : v;
    }

    function saveBest(v) {
      try { localStorage.setItem(bestKey(), String(v)); } catch (e) {}
    }

    function idx(c, r) { return r * cfg.cols + c; }
    function inBounds(c, r) { return c >= 0 && c < cfg.cols && r >= 0 && r < cfg.rows; }

    function neighbours(i) {
      var c = i % cfg.cols, r = Math.floor(i / cfg.cols);
      var out = [];
      for (var dr = -1; dr <= 1; dr++) {
        for (var dc = -1; dc <= 1; dc++) {
          if (!dr && !dc) continue;
          if (inBounds(c + dc, r + dr)) out.push(idx(c + dc, r + dr));
        }
      }
      return out;
    }

    /* ---------- setup ---------- */

    function build() {
      cfg = LEVELS[level];
      boardEl.style.gridTemplateColumns = 'repeat(' + cfg.cols + ', auto)';
      boardEl.innerHTML = '';
      cells = [];

      for (var i = 0; i < cfg.cols * cfg.rows; i++) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ms-cell';
        boardEl.appendChild(btn);

        var cell = { mine: false, open: false, flag: false, count: 0, el: btn };
        cells.push(cell);

        (function (index) {
          btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (flagMode) toggleFlag(index);
            else reveal(index);
          });
          btn.addEventListener('contextmenu', function (e) {
            e.preventDefault();
            toggleFlag(index);
          });
        })(i);
      }
    }

    // Mines are placed after the first click so it can never be a loss.
    function placeMines(safeIndex) {
      var safe = neighbours(safeIndex).concat([safeIndex]);
      var pool = [];
      for (var i = 0; i < cells.length; i++) {
        if (safe.indexOf(i) === -1) pool.push(i);
      }
      for (var j = pool.length - 1; j > 0; j--) {
        var k = Math.floor(Math.random() * (j + 1));
        var t = pool[j]; pool[j] = pool[k]; pool[k] = t;
      }
      pool.slice(0, Math.min(cfg.mines, pool.length)).forEach(function (i) {
        cells[i].mine = true;
      });

      cells.forEach(function (cell, i) {
        cell.count = neighbours(i).filter(function (n) { return cells[n].mine; }).length;
      });

      placed = true;
    }

    /* ---------- play ---------- */

    function reveal(i) {
      if (over) return;
      var cell = cells[i];
      if (cell.flag) return;

      if (!placed) {
        placeMines(i);
        startClock();
      }

      if (cell.open) { chord(i); return; }

      cell.open = true;

      if (cell.mine) return lose(i);

      paint(cell);

      // Flood-fill outward through cells with no adjacent mines.
      if (cell.count === 0) {
        var queue = neighbours(i);
        while (queue.length) {
          var n = queue.pop();
          var nc = cells[n];
          if (nc.open || nc.flag || nc.mine) continue;
          nc.open = true;
          paint(nc);
          if (nc.count === 0) queue = queue.concat(neighbours(n));
        }
      }

      checkWin();
    }

    // Clicking an open number with matching flags clears its unflagged neighbours.
    function chord(i) {
      var cell = cells[i];
      if (!cell.count) return;
      var ns = neighbours(i);
      var flags = ns.filter(function (n) { return cells[n].flag; }).length;
      if (flags !== cell.count) return;
      ns.forEach(function (n) {
        if (!cells[n].flag && !cells[n].open) reveal(n);
      });
    }

    function toggleFlag(i) {
      if (over) return;
      var cell = cells[i];
      if (cell.open) return;
      if (!placed) return; // nothing to flag before the board exists

      cell.flag = !cell.flag;
      flagsUsed += cell.flag ? 1 : -1;
      cell.el.textContent = cell.flag ? '🚩' : '';
      cell.el.classList.toggle('flagged', cell.flag);
      updateMineCount();
    }

    function paint(cell) {
      cell.el.classList.add('open');
      cell.el.textContent = cell.count ? cell.count : '';
      if (cell.count) cell.el.classList.add('n' + cell.count);
    }

    function updateMineCount() {
      document.getElementById('msMines').textContent = Math.max(0, cfg.mines - flagsUsed);
    }

    function lose(hitIndex) {
      over = true;
      clearInterval(timer);
      cells.forEach(function (cell, i) {
        if (cell.mine) {
          cell.el.classList.add(i === hitIndex ? 'exploded' : 'mine');
          cell.el.textContent = '💣';
        } else if (cell.flag) {
          cell.el.classList.add('wrong'); // flagged a safe square
          cell.el.textContent = '✕';
        }
      });
      msgEl.textContent = 'Boom. Hit a mine after ' + elapsed() + '.';
    }

    function checkWin() {
      var safeLeft = cells.filter(function (c) { return !c.mine && !c.open; }).length;
      if (safeLeft > 0) return;

      over = true;
      won = true;
      clearInterval(timer);

      cells.forEach(function (cell) {
        if (cell.mine && !cell.flag) {
          cell.flag = true;
          cell.el.textContent = '🚩';
          cell.el.classList.add('flagged');
        }
      });
      flagsUsed = cfg.mines;
      updateMineCount();

      var secs = Math.floor((Date.now() - startTime) / 1000);
      var best = loadBest();
      var record = best === null || secs < best;
      if (record) saveBest(secs);
      renderBest();

      msgEl.textContent = 'Cleared in ' + elapsed() + (record ? ' — new best.' : '.');
    }

    /* ---------- clock ---------- */

    function startClock() {
      startTime = Date.now();
      timer = setInterval(function () {
        document.getElementById('msTime').textContent = elapsed();
      }, 1000);
    }

    function elapsed() {
      if (!startTime) return '0:00';
      var s = Math.floor((Date.now() - startTime) / 1000);
      return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
    }

    function renderBest() {
      var b = loadBest();
      document.getElementById('msBest').textContent =
        b === null ? '—' : Math.floor(b / 60) + ':' + String(b % 60).padStart(2, '0');
    }

    /* ---------- controls ---------- */

    function newGame() {
      clearInterval(timer);
      placed = false;
      over = false;
      won = false;
      flagsUsed = 0;
      startTime = null;
      timer = null;
      msgEl.textContent = '';
      document.getElementById('msTime').textContent = '0:00';
      build();
      updateMineCount();
      renderBest();
    }

    document.querySelectorAll('.ms-levels .game-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.querySelectorAll('.ms-levels .game-btn').forEach(function (b) {
          b.classList.remove('active');
        });
        btn.classList.add('active');
        level = btn.dataset.level;
        newGame();
      });
    });

    var flagBtn = document.getElementById('msFlagMode');
    flagBtn.addEventListener('click', function () {
      flagMode = !flagMode;
      flagBtn.classList.toggle('on', flagMode);
      flagBtn.textContent = '🚩 Flag mode: ' + (flagMode ? 'on' : 'off');
    });

    document.getElementById('msNew').addEventListener('click', newGame);

    // Right-clicking the board shouldn't open the browser menu.
    boardEl.addEventListener('contextmenu', function (e) { e.preventDefault(); });

    newGame();
  })();
  </script>
</body>
</html>
