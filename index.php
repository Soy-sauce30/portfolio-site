<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sawyer — Developer</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <script>
    (function(){var t=localStorage.getItem('theme')||(window.matchMedia('(prefers-color-scheme:light)').matches?'light':'dark');document.documentElement.setAttribute('data-theme',t)})();
  </script>
  <style>
    /* =========================================
       Homepage — kept deliberately short
       ========================================= */

    .home-hero {
      min-height: 88vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 7rem 1.5rem 3rem;
      position: relative;
      overflow: hidden;
    }

    /* Soft glow that drifts toward the cursor. Purely decorative. */
    .home-glow {
      position: absolute;
      width: 480px;
      height: 480px;
      border-radius: 50%;
      background: radial-gradient(circle, var(--accent-glow) 0%, transparent 65%);
      filter: blur(40px);
      pointer-events: none;
      opacity: 0.55;
      left: 50%;
      top: 45%;
      transform: translate(-50%, -50%);
      transition: opacity 0.4s ease;
      z-index: -1;
    }

    @media (prefers-reduced-motion: reduce) {
      .home-glow { display: none; }
    }

    .home-name {
      font-family: 'Space Grotesk', sans-serif;
      font-size: clamp(2.6rem, 9vw, 5rem);
      font-weight: 700;
      letter-spacing: -0.04em;
      line-height: 1.05;
      margin-bottom: 1rem;
    }

    .home-name .accent { color: var(--accent); }

    .home-tagline {
      font-size: clamp(1rem, 2.6vw, 1.35rem);
      color: var(--text-muted);
      margin-bottom: 2.25rem;
      min-height: 1.6em;
    }

    .home-tagline .typed-text { color: var(--text); font-weight: 500; }

    .cursor {
      color: var(--accent);
      animation: blink 1s step-end infinite;
    }

    .home-ctas {
      display: flex;
      gap: 0.75rem;
      flex-wrap: wrap;
      justify-content: center;
    }

    .home-scroll {
      position: absolute;
      bottom: 1.75rem;
      left: 50%;
      transform: translateX(-50%);
      color: var(--text-muted);
      font-size: 0.72rem;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      text-decoration: none;
      opacity: 0.6;
      transition: opacity 0.2s, color 0.2s;
    }

    .home-scroll:hover { opacity: 1; color: var(--accent); }

    /* ---------- Things I've built ---------- */

    .home-section {
      max-width: 880px;
      margin: 0 auto;
      padding: 4rem 1.5rem;
    }

    .home-heading {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.6rem;
      font-weight: 600;
      letter-spacing: -0.02em;
      margin-bottom: 0.5rem;
    }

    .home-sub {
      color: var(--text-muted);
      font-size: 0.92rem;
      margin-bottom: 2rem;
      line-height: 1.7;
    }

    .home-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
      gap: 1rem;
    }

    .home-card {
      display: flex;
      flex-direction: column;
      padding: 1.5rem;
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 12px;
      text-decoration: none;
      color: inherit;
      transition: border-color 0.2s, transform 0.2s;
    }

    .home-card:hover {
      border-color: var(--accent-glow);
      transform: translateY(-2px);
    }

    .home-card-icon { font-size: 1.6rem; margin-bottom: 0.6rem; }

    .home-card-name {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.05rem;
      font-weight: 600;
      margin-bottom: 0.35rem;
    }

    .home-card-desc {
      color: var(--text-muted);
      font-size: 0.82rem;
      line-height: 1.6;
    }

    /* ---------- About + contact ---------- */

    .home-about p {
      color: var(--text-muted);
      font-size: 0.95rem;
      line-height: 1.8;
      margin-bottom: 1rem;
    }

    .home-contact {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 0.75rem 1.5rem;
    }

    .home-mail {
      font-family: 'Space Grotesk', sans-serif;
      font-size: clamp(1.1rem, 3.5vw, 1.6rem);
      font-weight: 600;
      color: var(--accent);
      text-decoration: none;
      border-bottom: 1px solid transparent;
      transition: border-color 0.2s;
    }

    .home-mail:hover { border-bottom-color: var(--accent); }

    .home-links { display: flex; gap: 1rem; }

    .home-links a {
      color: var(--text-muted);
      font-size: 0.85rem;
      text-decoration: none;
      transition: color 0.2s;
    }

    .home-links a:hover { color: var(--accent); }

    .home-rule {
      max-width: 880px;
      margin: 0 auto;
      border: 0;
      border-top: 1px solid var(--border);
    }
  </style>
</head>
<body>

  <!-- Header -->
  <?php include 'header.php';?>

  <!-- Hero -->
  <section class="home-hero" id="home">
    <div class="home-glow" id="homeGlow"></div>

    <h1 class="home-name">Sawyer <span class="accent">Abrahani</span></h1>

    <div class="home-tagline">
      I build <span class="typed-text" id="typedText"></span><span class="cursor">|</span>
    </div>

    <div class="home-ctas">
      <a href="/games/" class="btn btn-primary">Play my games</a>
      <a href="#contact" class="btn btn-outline">Get in touch</a>
    </div>

    <a href="#built" class="home-scroll">Scroll &darr;</a>
  </section>

  <hr class="home-rule">

  <!-- Things I've built -->
  <section class="home-section" id="built">
    <h2 class="home-heading">Things I've built</h2>
    <p class="home-sub">Six browser games, written from scratch in plain JavaScript — no engines, no frameworks.</p>

    <div class="home-grid">
      <a class="home-card" href="/games/worddle/">
        <div class="home-card-icon">🟩</div>
        <div class="home-card-name">Worddle</div>
        <p class="home-card-desc">A daily five-letter word puzzle with streaks and shareable results.</p>
      </a>

      <a class="home-card" href="/games/pong/">
        <div class="home-card-icon">🏓</div>
        <div class="home-card-name">Pong</div>
        <p class="home-card-desc">Three AI difficulties plus two-player, with real paddle physics.</p>
      </a>

      <a class="home-card" href="/games/minesweeper/">
        <div class="home-card-icon">💣</div>
        <div class="home-card-name">Minesweeper</div>
        <p class="home-card-desc">Three board sizes, flood-fill reveals, and a first click that's always safe.</p>
      </a>

      <a class="home-card" href="/games/">
        <div class="home-card-icon">🎮</div>
        <div class="home-card-name">All six &rarr;</div>
        <p class="home-card-desc">Snake, 2048 and Memory Match are in here too.</p>
      </a>
    </div>
  </section>

  <hr class="home-rule">

  <!-- About -->
  <section class="home-section home-about" id="about">
    <h2 class="home-heading">About</h2>
    <p>
      I'm a developer who likes small, self-contained projects — things that load fast,
      run in a single tab, and don't need a build step to be worth making.
    </p>
    <p>
      This site is hand-written PHP and CSS with no framework behind it. The games are
      plain JavaScript and canvas. It's all deliberately simple.
    </p>
  </section>

  <hr class="home-rule">

  <!-- Contact -->
  <section class="home-section" id="contact">
    <h2 class="home-heading">Get in touch</h2>
    <p class="home-sub">Happy to talk about a project, or about anything I've built here.</p>
    <div class="home-contact">
      <a href="mailto:sawyerabrahani@gmail.com" class="home-mail">sawyerabrahani@gmail.com</a>
      <div class="home-links">
        <a href="https://github.com/Soy-sauce30" target="_blank" rel="noopener">GitHub &rarr;</a>
      </div>
    </div>
  </section>

<!-- Footer -->
<?php include 'footer.php';?>

  <script src="script.js"></script>
  <script src="theme.js"></script>
  <script>
  (function () {
    // Drift the hero glow toward the pointer.
    var glow = document.getElementById('homeGlow');
    var hero = document.getElementById('home');
    if (!glow || !hero || !window.matchMedia('(pointer: fine)').matches) return;

    var targetX = 0, targetY = 0, x = 0, y = 0, seeded = false;

    hero.addEventListener('pointermove', function (e) {
      var r = hero.getBoundingClientRect();
      targetX = e.clientX - r.left;
      targetY = e.clientY - r.top;
      if (!seeded) { x = targetX; y = targetY; seeded = true; }
    });

    (function follow() {
      x += (targetX - x) * 0.06;
      y += (targetY - y) * 0.06;
      if (seeded) glow.style.left = x + 'px', glow.style.top = y + 'px';
      requestAnimationFrame(follow);
    })();
  })();
  </script>
</body>
</html>
