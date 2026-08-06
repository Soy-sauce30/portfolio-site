<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pong — Sawyer</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/style.css">
  <link rel="stylesheet" href="/games/game.css">
  <script>
    (function(){var t=localStorage.getItem('theme')||(window.matchMedia('(prefers-color-scheme:light)').matches?'light':'dark');document.documentElement.setAttribute('data-theme',t)})();
  </script>
  <style>
    .pg-modes {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
      justify-content: center;
    }
    .pg-modes .game-btn.active {
      background: var(--accent);
      border-color: var(--accent);
      color: #fff;
    }
    [data-theme="light"] .pg-modes .game-btn.active { color: #fff; }

    .pg-score {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 2.5rem;
      font-weight: 700;
      font-variant-numeric: tabular-nums;
      letter-spacing: 0.1em;
    }
    .pg-score span { color: var(--text-muted); font-size: 1.5rem; }
  </style>
</head>
<body>

  <?php include '../../header.php'; ?>

  <div class="game-wrap">

    <div class="game-head">
      <a class="game-back" href="/games/">&larr; All games</a>
      <h1 class="game-title">Pong</h1>
      <p class="game-blurb">The classic paddle duel. First to 11 wins.</p>
    </div>

    <div class="game-stage">

      <div class="pg-score"><span id="pgP1">0</span> &middot; <span id="pgP2">0</span></div>

      <canvas class="game-canvas" id="pgCanvas" width="800" height="500"></canvas>

      <div class="game-msg" id="pgMsg">Press Space or tap the board to serve</div>

      <div class="pg-modes">
        <button class="game-btn active" data-mode="easy">Easy</button>
        <button class="game-btn" data-mode="normal">Normal</button>
        <button class="game-btn" data-mode="hard">Hard</button>
        <button class="game-btn" data-mode="two">2 Player</button>
      </div>

      <button class="game-btn ghost" id="pgReset">Reset match</button>

    </div>

    <p class="game-help">
      Left paddle: <kbd>W</kbd> / <kbd>S</kbd> or drag on the left half.
      Right paddle (2P): <kbd>&uarr;</kbd> / <kbd>&darr;</kbd> or drag on the right half.
      <kbd>Space</kbd> serves and pauses.
    </p>

  </div>

  <?php include '../../footer.php'; ?>

  <script src="/script.js"></script>
  <script src="/theme.js"></script>
  <script>
  (function () {
    'use strict';

    var canvas = document.getElementById('pgCanvas');
    var ctx = canvas.getContext('2d');
    var msgEl = document.getElementById('pgMsg');

    var W = canvas.width, H = canvas.height;
    var PADDLE_W = 12, PADDLE_H = 90, PADDLE_INSET = 26;
    var BALL_R = 8;
    var WIN_SCORE = 11;
    var PLAYER_SPEED = 520;   // px per second

    var AI = {
      easy:   { speed: 300, error: 60, react: 0.55 },
      normal: { speed: 420, error: 32, react: 0.75 },
      hard:   { speed: 560, error: 12, react: 0.92 }
    };

    var mode = 'easy';
    var left  = { y: H / 2 - PADDLE_H / 2, score: 0, vy: 0 };
    var right = { y: H / 2 - PADDLE_H / 2, score: 0, vy: 0 };
    var ball = { x: W / 2, y: H / 2, vx: 0, vy: 0, speed: 340 };

    var running = false;     // ball in play
    var paused = false;
    var matchOver = false;
    var serveTo = 1;         // 1 = toward right player, -1 = toward left
    var aimTarget = H / 2;   // AI's believed intercept point
    var keys = Object.create(null);
    var drag = { left: null, right: null };

    /* ---------- drawing ---------- */

    function themeColors() {
      var css = getComputedStyle(document.documentElement);
      return {
        fg: css.getPropertyValue('--text').trim() || '#e4e4e7',
        accent: css.getPropertyValue('--accent').trim() || '#a78bfa',
        muted: css.getPropertyValue('--text-muted').trim() || '#8a8a9a'
      };
    }

    function draw() {
      var col = themeColors();
      ctx.clearRect(0, 0, W, H);

      // center line
      ctx.strokeStyle = col.muted;
      ctx.globalAlpha = 0.3;
      ctx.lineWidth = 3;
      ctx.setLineDash([12, 16]);
      ctx.beginPath();
      ctx.moveTo(W / 2, 0);
      ctx.lineTo(W / 2, H);
      ctx.stroke();
      ctx.setLineDash([]);
      ctx.globalAlpha = 1;

      // paddles
      ctx.fillStyle = col.fg;
      roundRect(PADDLE_INSET, left.y, PADDLE_W, PADDLE_H, 6);
      ctx.fillStyle = mode === 'two' ? col.fg : col.accent;
      roundRect(W - PADDLE_INSET - PADDLE_W, right.y, PADDLE_W, PADDLE_H, 6);

      // ball
      ctx.fillStyle = col.accent;
      ctx.beginPath();
      ctx.arc(ball.x, ball.y, BALL_R, 0, Math.PI * 2);
      ctx.fill();
    }

    function roundRect(x, y, w, h, r) {
      ctx.beginPath();
      ctx.moveTo(x + r, y);
      ctx.arcTo(x + w, y, x + w, y + h, r);
      ctx.arcTo(x + w, y + h, x, y + h, r);
      ctx.arcTo(x, y + h, x, y, r);
      ctx.arcTo(x, y, x + w, y, r);
      ctx.closePath();
      ctx.fill();
    }

    /* ---------- game flow ---------- */

    function say(t) { msgEl.textContent = t; }

    function resetBall() {
      ball.x = W / 2;
      ball.y = H / 2;
      ball.vx = 0;
      ball.vy = 0;
      ball.speed = 340;
      running = false;
      aimTarget = H / 2;
    }

    function serve() {
      if (matchOver || paused) return;
      var angle = (Math.random() * 0.6 - 0.3); // shallow opening angle
      ball.vx = Math.cos(angle) * ball.speed * serveTo;
      ball.vy = Math.sin(angle) * ball.speed;
      running = true;

      // The AI has to read the serve too, or it just parks at center court.
      if (mode !== 'two' && ball.vx > 0) {
        var cfg = AI[mode];
        aimTarget = predictIntercept();
        if (Math.random() > cfg.react) aimTarget = H / 2;
        aimTarget += (Math.random() * 2 - 1) * cfg.error;
      }

      say('');
    }

    function point(winner) {
      if (winner === 'left') { left.score++; serveTo = 1; }
      else { right.score++; serveTo = -1; }

      document.getElementById('pgP1').textContent = left.score;
      document.getElementById('pgP2').textContent = right.score;

      resetBall();

      if (left.score >= WIN_SCORE || right.score >= WIN_SCORE) {
        matchOver = true;
        var youWon = left.score > right.score;
        if (mode === 'two') {
          say((youWon ? 'Left' : 'Right') + ' player wins ' + left.score + '–' + right.score + '. Reset to play again.');
        } else {
          say(youWon
            ? 'You win ' + left.score + '–' + right.score + '. Reset to play again.'
            : 'Computer wins ' + right.score + '–' + left.score + '. Reset to play again.');
        }
      } else {
        say('Press Space or tap to serve');
      }
    }

    function resetMatch() {
      left.score = 0;
      right.score = 0;
      left.y = right.y = H / 2 - PADDLE_H / 2;
      document.getElementById('pgP1').textContent = '0';
      document.getElementById('pgP2').textContent = '0';
      matchOver = false;
      paused = false;
      serveTo = Math.random() < 0.5 ? 1 : -1;
      resetBall();
      say('Press Space or tap the board to serve');
    }

    /* ---------- physics ---------- */

    function clampPaddle(p) {
      if (p.y < 0) p.y = 0;
      if (p.y > H - PADDLE_H) p.y = H - PADDLE_H;
    }

    // Where will the ball cross the right paddle's plane? Used by the AI.
    function predictIntercept() {
      if (ball.vx <= 0) return H / 2;
      var x = ball.x, y = ball.y, vx = ball.vx, vy = ball.vy;
      var targetX = W - PADDLE_INSET - PADDLE_W - BALL_R;
      var t = (targetX - x) / vx;
      y += vy * t;
      // reflect off the top and bottom walls
      var span = H - BALL_R * 2;
      y = (y - BALL_R) % (span * 2);
      if (y < 0) y += span * 2;
      if (y > span) y = span * 2 - y;
      return y + BALL_R;
    }

    function updatePaddles(dt) {
      // Left paddle — always human
      var lv = 0;
      if (keys['w']) lv -= 1;
      if (keys['s']) lv += 1;
      if (drag.left !== null) {
        left.y = drag.left - PADDLE_H / 2;
      } else {
        left.y += lv * PLAYER_SPEED * dt;
      }
      clampPaddle(left);

      if (mode === 'two') {
        var rv = 0;
        if (keys['arrowup']) rv -= 1;
        if (keys['arrowdown']) rv += 1;
        if (drag.right !== null) {
          right.y = drag.right - PADDLE_H / 2;
        } else {
          right.y += rv * PLAYER_SPEED * dt;
        }
      } else {
        var cfg = AI[mode];
        var center = right.y + PADDLE_H / 2;
        var goal;
        if (running && ball.vx > 0) {
          goal = aimTarget;
        } else {
          goal = H / 2; // recover to center between rallies
        }
        var diff = goal - center;
        var step = cfg.speed * dt;
        if (Math.abs(diff) <= step) right.y = goal - PADDLE_H / 2;
        else right.y += Math.sign(diff) * step;
      }
      clampPaddle(right);
    }

    function bounceOffPaddle(paddle, dir) {
      // Contact point on the paddle sets the outgoing angle.
      var rel = (ball.y - (paddle.y + PADDLE_H / 2)) / (PADDLE_H / 2);
      rel = Math.max(-1, Math.min(1, rel));
      var angle = rel * (Math.PI / 3.4);        // up to ~53 degrees
      ball.speed = Math.min(ball.speed * 1.045, 780);
      ball.vx = Math.cos(angle) * ball.speed * dir;
      ball.vy = Math.sin(angle) * ball.speed;

      if (mode !== 'two' && dir > 0) {
        // Recompute the AI's read on the return, with difficulty-scaled error.
        var cfg = AI[mode];
        aimTarget = predictIntercept();
        if (Math.random() > cfg.react) aimTarget = H / 2;
        aimTarget += (Math.random() * 2 - 1) * cfg.error;
      }
    }

    function step(dt) {
      updatePaddles(dt);
      if (!running) return;

      ball.x += ball.vx * dt;
      ball.y += ball.vy * dt;

      // walls
      if (ball.y - BALL_R < 0) { ball.y = BALL_R; ball.vy = Math.abs(ball.vy); }
      if (ball.y + BALL_R > H) { ball.y = H - BALL_R; ball.vy = -Math.abs(ball.vy); }

      // left paddle
      var lx = PADDLE_INSET + PADDLE_W;
      if (ball.vx < 0 && ball.x - BALL_R <= lx && ball.x - BALL_R > lx - 24) {
        if (ball.y >= left.y - BALL_R && ball.y <= left.y + PADDLE_H + BALL_R) {
          ball.x = lx + BALL_R;
          bounceOffPaddle(left, 1);
        }
      }

      // right paddle
      var rx = W - PADDLE_INSET - PADDLE_W;
      if (ball.vx > 0 && ball.x + BALL_R >= rx && ball.x + BALL_R < rx + 24) {
        if (ball.y >= right.y - BALL_R && ball.y <= right.y + PADDLE_H + BALL_R) {
          ball.x = rx - BALL_R;
          bounceOffPaddle(right, -1);
        }
      }

      // scoring
      if (ball.x + BALL_R < 0) point('right');
      else if (ball.x - BALL_R > W) point('left');
    }

    var last = 0;
    function loop(ts) {
      if (!last) last = ts;
      var dt = Math.min((ts - last) / 1000, 0.033); // clamp so tab-switches don't teleport the ball
      last = ts;
      if (!paused) step(dt);
      draw();
      requestAnimationFrame(loop);
    }

    /* ---------- input ---------- */

    document.addEventListener('keydown', function (e) {
      var k = e.key.toLowerCase();
      if (k === ' ' || k === 'spacebar' || e.code === 'Space') {
        e.preventDefault();
        if (matchOver) return;
        if (!running) serve();
        else { paused = !paused; say(paused ? 'Paused' : ''); }
        return;
      }
      if (['w', 's', 'arrowup', 'arrowdown'].indexOf(k) !== -1) {
        e.preventDefault();
        keys[k] = true;
      }
    });

    document.addEventListener('keyup', function (e) {
      keys[e.key.toLowerCase()] = false;
    });

    function pointerY(e) {
      var r = canvas.getBoundingClientRect();
      return ((e.clientY - r.top) / r.height) * H;
    }

    function pointerX(e) {
      var r = canvas.getBoundingClientRect();
      return ((e.clientX - r.left) / r.width) * W;
    }

    var activePointers = Object.create(null);

    canvas.addEventListener('pointerdown', function (e) {
      e.preventDefault();
      canvas.setPointerCapture(e.pointerId);
      var side = pointerX(e) < W / 2 ? 'left' : 'right';
      if (side === 'right' && mode !== 'two') side = 'left'; // vs computer, whole board drives you
      activePointers[e.pointerId] = side;
      drag[side] = pointerY(e);
      if (!running && !matchOver && !paused) serve();
    });

    canvas.addEventListener('pointermove', function (e) {
      var side = activePointers[e.pointerId];
      if (!side) return;
      e.preventDefault();
      drag[side] = pointerY(e);
    });

    function releasePointer(e) {
      var side = activePointers[e.pointerId];
      if (!side) return;
      drag[side] = null;
      delete activePointers[e.pointerId];
    }

    canvas.addEventListener('pointerup', releasePointer);
    canvas.addEventListener('pointercancel', releasePointer);

    document.querySelectorAll('.pg-modes .game-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.querySelectorAll('.pg-modes .game-btn').forEach(function (b) {
          b.classList.remove('active');
        });
        btn.classList.add('active');
        mode = btn.dataset.mode;
        drag.left = drag.right = null;
        resetMatch();
      });
    });

    document.getElementById('pgReset').addEventListener('click', resetMatch);

    resetMatch();
    requestAnimationFrame(loop);
  })();
  </script>
</body>
</html>
