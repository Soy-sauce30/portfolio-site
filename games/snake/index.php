<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Snake — Sawyer</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/style.css">
  <link rel="stylesheet" href="/games/game.css">
  <script>
    (function(){var t=localStorage.getItem('theme')||(window.matchMedia('(prefers-color-scheme:light)').matches?'light':'dark');document.documentElement.setAttribute('data-theme',t)})();
  </script>
  <style>
    #snCanvas { max-width: 460px; }
    .sn-speeds { display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: center; }
    .sn-speeds .game-btn.active {
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
      <h1 class="game-title">Snake</h1>
      <p class="game-blurb">Eat, grow, and don't run into yourself.</p>
    </div>

    <div class="game-stage">

      <div class="game-bar">
        <div class="game-stat">
          <span class="game-stat-label">Score</span>
          <span class="game-stat-value" id="snScore">0</span>
        </div>
        <div class="game-stat">
          <span class="game-stat-label">Best</span>
          <span class="game-stat-value" id="snBest">0</span>
        </div>
        <div class="game-stat">
          <span class="game-stat-label">Length</span>
          <span class="game-stat-value" id="snLen">3</span>
        </div>
      </div>

      <canvas class="game-canvas" id="snCanvas" width="460" height="460"></canvas>

      <div class="game-msg" id="snMsg">Press an arrow key to start</div>

      <div class="sn-speeds">
        <button class="game-btn" data-speed="140">Slow</button>
        <button class="game-btn active" data-speed="100">Normal</button>
        <button class="game-btn" data-speed="65">Fast</button>
      </div>

      <button class="game-btn ghost" id="snNew">New game</button>

    </div>

    <p class="game-help">
      Steer with the <kbd>&larr;</kbd> <kbd>&uarr;</kbd> <kbd>&darr;</kbd> <kbd>&rarr;</kbd> keys or
      <kbd>WASD</kbd>. Swipe on the board on touch devices. <kbd>Space</kbd> pauses.
    </p>

  </div>

  <?php include '../../footer.php'; ?>

  <script src="/script.js"></script>
  <script src="/theme.js"></script>
  <script>
  (function () {
    'use strict';

    var canvas = document.getElementById('snCanvas');
    var ctx = canvas.getContext('2d');
    var msgEl = document.getElementById('snMsg');

    var GRID = 23;                       // cells per side
    var CELL = canvas.width / GRID;
    var BEST_KEY = 'snake-best';

    var snake, dir, nextDir, food, score, alive, started, paused, tickMs, timer;

    function loadBest() {
      var v = parseInt(localStorage.getItem(BEST_KEY), 10);
      return isNaN(v) ? 0 : v;
    }

    function saveBest(v) {
      try { localStorage.setItem(BEST_KEY, String(v)); } catch (e) {}
    }

    function colors() {
      var css = getComputedStyle(document.documentElement);
      return {
        accent: css.getPropertyValue('--accent').trim() || '#a78bfa',
        fg: css.getPropertyValue('--text').trim() || '#e4e4e7',
        muted: css.getPropertyValue('--text-muted').trim() || '#8a8a9a'
      };
    }

    function reset() {
      snake = [{ x: 11, y: 11 }, { x: 10, y: 11 }, { x: 9, y: 11 }];
      dir = { x: 1, y: 0 };
      nextDir = { x: 1, y: 0 };
      score = 0;
      alive = true;
      started = false;
      paused = false;
      placeFood();
      updateStats();
      msgEl.textContent = 'Press an arrow key to start';
      draw();
    }

    function placeFood() {
      var open = [];
      for (var y = 0; y < GRID; y++) {
        for (var x = 0; x < GRID; x++) {
          if (!snake.some(function (s) { return s.x === x && s.y === y; })) open.push({ x: x, y: y });
        }
      }
      food = open.length ? open[Math.floor(Math.random() * open.length)] : null;
    }

    function updateStats() {
      document.getElementById('snScore').textContent = score;
      document.getElementById('snBest').textContent = loadBest();
      document.getElementById('snLen').textContent = snake.length;
    }

    function tick() {
      if (!alive || paused || !started) return;

      dir = nextDir;
      var head = { x: snake[0].x + dir.x, y: snake[0].y + dir.y };

      if (head.x < 0 || head.x >= GRID || head.y < 0 || head.y >= GRID) return die();

      // The tail cell frees up this tick, so running into it is legal.
      var hitBody = snake.some(function (s, i) {
        return i < snake.length - 1 && s.x === head.x && s.y === head.y;
      });
      if (hitBody) return die();

      snake.unshift(head);

      if (food && head.x === food.x && head.y === food.y) {
        score += 10;
        placeFood();
        if (!food) { // board filled — perfect game
          alive = false;
          msgEl.textContent = 'Board cleared. Nothing left to eat.';
        }
      } else {
        snake.pop();
      }

      updateStats();
      draw();
    }

    function die() {
      alive = false;
      if (score > loadBest()) { saveBest(score); updateStats(); }
      msgEl.textContent = 'Game over — score ' + score + '. Press Space or New game.';
      draw();
    }

    function draw() {
      var col = colors();
      ctx.clearRect(0, 0, canvas.width, canvas.height);

      // grid
      ctx.strokeStyle = col.muted;
      ctx.globalAlpha = 0.08;
      ctx.lineWidth = 1;
      for (var i = 1; i < GRID; i++) {
        ctx.beginPath();
        ctx.moveTo(i * CELL, 0); ctx.lineTo(i * CELL, canvas.height);
        ctx.moveTo(0, i * CELL); ctx.lineTo(canvas.width, i * CELL);
        ctx.stroke();
      }
      ctx.globalAlpha = 1;

      // food
      if (food) {
        ctx.fillStyle = col.accent;
        ctx.beginPath();
        ctx.arc(food.x * CELL + CELL / 2, food.y * CELL + CELL / 2, CELL * 0.32, 0, Math.PI * 2);
        ctx.fill();
      }

      // snake — head solid, body fades toward the tail
      snake.forEach(function (s, i) {
        ctx.fillStyle = i === 0 ? col.fg : col.accent;
        ctx.globalAlpha = i === 0 ? 1 : Math.max(0.35, 1 - i / (snake.length + 4));
        var pad = i === 0 ? 1.5 : 2.5;
        ctx.beginPath();
        var x = s.x * CELL + pad, y = s.y * CELL + pad, w = CELL - pad * 2, r = 4;
        ctx.moveTo(x + r, y);
        ctx.arcTo(x + w, y, x + w, y + w, r);
        ctx.arcTo(x + w, y + w, x, y + w, r);
        ctx.arcTo(x, y + w, x, y, r);
        ctx.arcTo(x, y, x + w, y, r);
        ctx.closePath();
        ctx.fill();
      });
      ctx.globalAlpha = 1;
    }

    function setDir(x, y) {
      // Ignore reversals into our own neck.
      if (dir.x === -x && dir.y === -y) return;
      nextDir = { x: x, y: y };
      if (!started && alive) {
        started = true;
        msgEl.textContent = '';
      }
    }

    function restart() {
      clearInterval(timer);
      reset();
      timer = setInterval(tick, tickMs);
    }

    document.addEventListener('keydown', function (e) {
      var k = e.key.toLowerCase();
      if (k === 'arrowup' || k === 'w') { e.preventDefault(); setDir(0, -1); }
      else if (k === 'arrowdown' || k === 's') { e.preventDefault(); setDir(0, 1); }
      else if (k === 'arrowleft' || k === 'a') { e.preventDefault(); setDir(-1, 0); }
      else if (k === 'arrowright' || k === 'd') { e.preventDefault(); setDir(1, 0); }
      else if (k === ' ' || e.code === 'Space') {
        e.preventDefault();
        if (!alive) { restart(); return; }
        if (!started) return;
        paused = !paused;
        msgEl.textContent = paused ? 'Paused' : '';
      }
    });

    // Swipe controls
    var touchStart = null;
    canvas.addEventListener('pointerdown', function (e) {
      touchStart = { x: e.clientX, y: e.clientY };
    });

    canvas.addEventListener('pointerup', function (e) {
      if (!touchStart) return;
      var dx = e.clientX - touchStart.x;
      var dy = e.clientY - touchStart.y;
      touchStart = null;
      if (Math.abs(dx) < 20 && Math.abs(dy) < 20) return;
      if (Math.abs(dx) > Math.abs(dy)) setDir(dx > 0 ? 1 : -1, 0);
      else setDir(0, dy > 0 ? 1 : -1);
    });

    document.querySelectorAll('.sn-speeds .game-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.querySelectorAll('.sn-speeds .game-btn').forEach(function (b) {
          b.classList.remove('active');
        });
        btn.classList.add('active');
        tickMs = parseInt(btn.dataset.speed, 10);
        restart();
      });
    });

    document.getElementById('snNew').addEventListener('click', restart);

    tickMs = 100;
    reset();
    timer = setInterval(tick, tickMs);
  })();
  </script>
</body>
</html>
