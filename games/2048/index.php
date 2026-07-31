<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>2048 — Sawyer</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/style.css">
  <link rel="stylesheet" href="/games/game.css">
  <script>
    (function(){var t=localStorage.getItem('theme')||(window.matchMedia('(prefers-color-scheme:light)').matches?'light':'dark');document.documentElement.setAttribute('data-theme',t)})();
  </script>
  <style>
    .tf-board {
      position: relative;
      width: 100%;
      max-width: 420px;
      aspect-ratio: 1;
      background: rgba(0, 0, 0, 0.28);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 10px;
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      grid-template-rows: repeat(4, 1fr);
      gap: 10px;
      touch-action: none;
      user-select: none;
    }

    [data-theme="light"] .tf-board { background: rgba(0, 0, 0, 0.05); }

    .tf-cell {
      background: rgba(255, 255, 255, 0.04);
      border-radius: 7px;
    }

    [data-theme="light"] .tf-cell { background: rgba(0, 0, 0, 0.04); }

    .tf-tile {
      position: absolute;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Space Grotesk', sans-serif;
      font-weight: 700;
      border-radius: 7px;
      color: #1a1a2e;
      transition: transform 0.12s ease-out;
      will-change: transform;
    }

    .tf-tile.new  { animation: tf-appear 0.15s ease-out; }
    .tf-tile.merged { animation: tf-merge 0.18s ease-out; }

    @keyframes tf-appear {
      from { transform: scale(0.3); opacity: 0; }
    }

    @keyframes tf-merge {
      0%   { transform: scale(1); }
      50%  { transform: scale(1.16); }
      100% { transform: scale(1); }
    }

    /* Values scale from pale lavender to deep violet as they grow. */
    .v2    { background: #ede9fe; }
    .v4    { background: #ddd6fe; }
    .v8    { background: #c4b5fd; }
    .v16   { background: #a78bfa; color: #fff; }
    .v32   { background: #8b5cf6; color: #fff; }
    .v64   { background: #7c3aed; color: #fff; }
    .v128  { background: #6d28d9; color: #fff; }
    .v256  { background: #5b21b6; color: #fff; }
    .v512  { background: #4c1d95; color: #fff; }
    .v1024 { background: #3b1173; color: #fff; }
    .v2048 { background: #2e0d5c; color: #fff; box-shadow: 0 0 24px rgba(167,139,250,0.55); }
    .vbig  { background: #1a0736; color: #fff; box-shadow: 0 0 24px rgba(167,139,250,0.7); }
  </style>
</head>
<body>

  <?php include '../../header.php'; ?>

  <div class="game-wrap">

    <div class="game-head">
      <a class="game-back" href="/games/">&larr; All games</a>
      <h1 class="game-title">2048</h1>
      <p class="game-blurb">Slide the tiles, merge the matches, reach 2048.</p>
    </div>

    <div class="game-stage">

      <div class="game-bar">
        <div class="game-stat">
          <span class="game-stat-label">Score</span>
          <span class="game-stat-value" id="tfScore">0</span>
        </div>
        <div class="game-stat">
          <span class="game-stat-label">Best</span>
          <span class="game-stat-value" id="tfBest">0</span>
        </div>
        <div class="game-stat">
          <span class="game-stat-label">Top tile</span>
          <span class="game-stat-value" id="tfTop">2</span>
        </div>
      </div>

      <div class="tf-board" id="tfBoard"></div>

      <div class="game-msg" id="tfMsg"></div>

      <div class="game-bar">
        <button class="game-btn ghost" id="tfUndo">Undo</button>
        <button class="game-btn ghost" id="tfNew">New game</button>
      </div>

    </div>

    <p class="game-help">
      Slide with the arrow keys or <kbd>WASD</kbd>. Swipe on touch devices.
      Merging two equal tiles adds their values to your score.
    </p>

  </div>

  <?php include '../../footer.php'; ?>

  <script src="/script.js"></script>
  <script src="/theme.js"></script>
  <script>
  (function () {
    'use strict';

    var N = 4;
    var BEST_KEY = '2048-best';

    var boardEl = document.getElementById('tfBoard');
    var msgEl = document.getElementById('tfMsg');

    var grid = [];        // N x N of numbers, 0 = empty
    var score = 0;
    var over = false;
    var won = false;
    var history = null;   // single-step undo

    // Background cells
    for (var i = 0; i < N * N; i++) {
      var c = document.createElement('div');
      c.className = 'tf-cell';
      boardEl.appendChild(c);
    }

    var tileLayer = document.createElement('div');
    tileLayer.style.position = 'absolute';
    tileLayer.style.inset = '0';
    tileLayer.style.pointerEvents = 'none';
    boardEl.appendChild(tileLayer);

    function loadBest() {
      var v = parseInt(localStorage.getItem(BEST_KEY), 10);
      return isNaN(v) ? 0 : v;
    }

    function saveBest(v) {
      try { localStorage.setItem(BEST_KEY, String(v)); } catch (e) {}
    }

    function emptyGrid() {
      var g = [];
      for (var r = 0; r < N; r++) {
        g.push([]);
        for (var c = 0; c < N; c++) g[r].push(0);
      }
      return g;
    }

    function cloneGrid(g) {
      return g.map(function (row) { return row.slice(); });
    }

    function emptyCells() {
      var out = [];
      for (var r = 0; r < N; r++) {
        for (var c = 0; c < N; c++) if (!grid[r][c]) out.push({ r: r, c: c });
      }
      return out;
    }

    function addTile(flagNew) {
      var open = emptyCells();
      if (!open.length) return null;
      var spot = open[Math.floor(Math.random() * open.length)];
      grid[spot.r][spot.c] = Math.random() < 0.9 ? 2 : 4;
      return spot;
    }

    /* ---------- rendering ---------- */

    // Tiles are absolutely positioned so CSS transitions can animate the slide.
    function render(newSpot, mergedCells) {
      var pad = 10;
      var size = (boardEl.clientWidth - pad * 2 - pad * (N - 1)) / N;
      tileLayer.innerHTML = '';

      for (var r = 0; r < N; r++) {
        for (var c = 0; c < N; c++) {
          var v = grid[r][c];
          if (!v) continue;

          var t = document.createElement('div');
          t.className = 'tf-tile ' + (v > 2048 ? 'vbig' : 'v' + v);
          t.textContent = v;
          t.style.width = size + 'px';
          t.style.height = size + 'px';
          t.style.left = pad + 'px';
          t.style.top = pad + 'px';
          t.style.transform = 'translate(' + (c * (size + pad)) + 'px,' + (r * (size + pad)) + 'px)';
          t.style.fontSize = (v >= 1024 ? size * 0.3 : v >= 128 ? size * 0.36 : size * 0.44) + 'px';

          if (newSpot && newSpot.r === r && newSpot.c === c) t.classList.add('new');
          if (mergedCells && mergedCells.some(function (m) { return m.r === r && m.c === c; })) {
            t.classList.add('merged');
          }

          tileLayer.appendChild(t);
        }
      }

      document.getElementById('tfScore').textContent = score;
      document.getElementById('tfBest').textContent = loadBest();
      document.getElementById('tfTop').textContent = topTile();
    }

    function topTile() {
      var max = 0;
      for (var r = 0; r < N; r++) for (var c = 0; c < N; c++) if (grid[r][c] > max) max = grid[r][c];
      return max;
    }

    /* ---------- movement ---------- */

    // Collapse one line toward index 0. Returns the new line, points gained,
    // and which output indices were the product of a merge.
    function collapse(line) {
      var vals = line.filter(function (v) { return v !== 0; });
      var out = [];
      var merged = [];
      var gained = 0;

      for (var i = 0; i < vals.length; i++) {
        if (i + 1 < vals.length && vals[i] === vals[i + 1]) {
          var sum = vals[i] * 2;
          out.push(sum);
          merged.push(out.length - 1);
          gained += sum;
          i++; // consume the partner — each tile merges at most once per move
        } else {
          out.push(vals[i]);
        }
      }
      while (out.length < N) out.push(0);
      return { line: out, gained: gained, merged: merged };
    }

    // dir: 'left' | 'right' | 'up' | 'down'
    function move(dir) {
      if (over) return false;

      var before = cloneGrid(grid);
      var beforeScore = score;
      var mergedCells = [];
      var r, c, i;

      for (i = 0; i < N; i++) {
        var line = [];
        var coords = [];

        for (var j = 0; j < N; j++) {
          if (dir === 'left')       { r = i; c = j; }
          else if (dir === 'right') { r = i; c = N - 1 - j; }
          else if (dir === 'up')    { r = j; c = i; }
          else                      { r = N - 1 - j; c = i; }
          line.push(grid[r][c]);
          coords.push({ r: r, c: c });
        }

        var res = collapse(line);
        score += res.gained;

        for (var k = 0; k < N; k++) {
          grid[coords[k].r][coords[k].c] = res.line[k];
        }
        res.merged.forEach(function (idx) { mergedCells.push(coords[idx]); });
      }

      var changed = false;
      for (r = 0; r < N && !changed; r++) {
        for (c = 0; c < N; c++) if (before[r][c] !== grid[r][c]) { changed = true; break; }
      }

      if (!changed) {
        score = beforeScore;
        return false;
      }

      history = { grid: before, score: beforeScore, won: won };

      var spot = addTile();

      if (score > loadBest()) saveBest(score);
      render(spot, mergedCells);

      if (!won && topTile() >= 2048) {
        won = true;
        msgEl.textContent = 'You made 2048. Keep going for a higher tile.';
      }

      if (!canMove()) {
        over = true;
        msgEl.textContent = 'No moves left — final score ' + score + '.';
      }

      return true;
    }

    function canMove() {
      for (var r = 0; r < N; r++) {
        for (var c = 0; c < N; c++) {
          if (!grid[r][c]) return true;
          if (c + 1 < N && grid[r][c] === grid[r][c + 1]) return true;
          if (r + 1 < N && grid[r][c] === grid[r + 1][c]) return true;
        }
      }
      return false;
    }

    /* ---------- controls ---------- */

    function newGame() {
      grid = emptyGrid();
      score = 0;
      over = false;
      won = false;
      history = null;
      addTile();
      addTile();
      msgEl.textContent = '';
      render();
    }

    function undo() {
      if (!history) { msgEl.textContent = 'Nothing to undo'; return; }
      grid = history.grid;
      score = history.score;
      won = history.won;
      over = false;
      history = null;
      msgEl.textContent = '';
      render();
    }

    var KEYMAP = {
      arrowleft: 'left', a: 'left',
      arrowright: 'right', d: 'right',
      arrowup: 'up', w: 'up',
      arrowdown: 'down', s: 'down'
    };

    document.addEventListener('keydown', function (e) {
      if (e.ctrlKey || e.metaKey) return;
      var dir = KEYMAP[e.key.toLowerCase()];
      if (!dir) return;
      e.preventDefault();
      move(dir);
    });

    // Swipe
    var start = null;
    boardEl.addEventListener('pointerdown', function (e) {
      start = { x: e.clientX, y: e.clientY };
    });

    boardEl.addEventListener('pointerup', function (e) {
      if (!start) return;
      var dx = e.clientX - start.x;
      var dy = e.clientY - start.y;
      start = null;
      if (Math.abs(dx) < 24 && Math.abs(dy) < 24) return;
      if (Math.abs(dx) > Math.abs(dy)) move(dx > 0 ? 'right' : 'left');
      else move(dy > 0 ? 'down' : 'up');
    });

    document.getElementById('tfNew').addEventListener('click', newGame);
    document.getElementById('tfUndo').addEventListener('click', undo);

    window.addEventListener('resize', function () { render(); });

    newGame();
  })();
  </script>
</body>
</html>
