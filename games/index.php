<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Games — Sawyer</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/style.css">
  <script>
    (function(){var t=localStorage.getItem('theme')||(window.matchMedia('(prefers-color-scheme:light)').matches?'light':'dark');document.documentElement.setAttribute('data-theme',t)})();
  </script>
  <style>
    .games-wrap {
      max-width: 960px;
      margin: 0 auto;
      padding: 6rem 2rem 4rem;
    }

    .games-header {
      text-align: center;
      margin-bottom: 3rem;
    }

    .games-title {
      font-family: 'Space Grotesk', sans-serif;
      font-size: clamp(2rem, 5vw, 3rem);
      font-weight: 700;
      letter-spacing: -0.03em;
      margin-bottom: 0.75rem;
    }

    .games-subtitle {
      color: var(--text-muted);
      font-size: 1rem;
      max-width: 480px;
      margin: 0 auto;
      line-height: 1.6;
    }

    .games-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 1.25rem;
    }

    .game-card {
      display: flex;
      flex-direction: column;
      padding: 1.75rem;
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 12px;
      text-decoration: none;
      color: inherit;
      transition: border-color 0.2s, transform 0.2s;
      position: relative;
      overflow: hidden;
    }

    .game-card:hover {
      border-color: var(--accent-glow);
      transform: translateY(-2px);
    }

    .game-card.disabled {
      cursor: default;
      opacity: 0.55;
    }

    .game-card.disabled:hover {
      transform: none;
      border-color: var(--border);
    }

    .game-icon {
      font-size: 2rem;
      margin-bottom: 0.75rem;
    }

    .game-name {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.15rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }

    .game-desc {
      color: var(--text-muted);
      font-size: 0.85rem;
      line-height: 1.6;
      flex-grow: 1;
    }

    .game-tag {
      display: inline-block;
      font-size: 0.65rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--accent);
      background: var(--accent-dim);
      padding: 0.2rem 0.55rem;
      border-radius: 4px;
      margin-top: 1rem;
      align-self: flex-start;
    }

    .game-tag.soon {
      color: var(--text-muted);
      background: rgba(255,255,255,0.05);
    }
  </style>
</head>
<body>

  <?php include '../header.php'; ?>

  <div class="games-wrap">

    <div class="games-header">
      <h1 class="games-title">Games</h1>
      <p class="games-subtitle">A collection of small games and interactive experiments.</p>
    </div>

    <div class="games-grid">

      <a class="game-card" href="/games/worddle/">
        <div class="game-icon">🟩</div>
        <div class="game-name">Worddle</div>
        <p class="game-desc">Guess the five-letter word in six tries. A new puzzle every day.</p>
        <span class="game-tag">Play</span>
      </a>

      <a class="game-card" href="/games/pong/">
        <div class="game-icon">🏓</div>
        <div class="game-name">Pong</div>
        <p class="game-desc">The classic paddle duel. Play against the computer or a friend.</p>
        <span class="game-tag">Play</span>
      </a>

      <a class="game-card" href="/games/snake/">
        <div class="game-icon">🐍</div>
        <div class="game-name">Snake</div>
        <p class="game-desc">Eat, grow, and don't run into yourself. Three speeds.</p>
        <span class="game-tag">Play</span>
      </a>

      <a class="game-card" href="/games/2048/">
        <div class="game-icon">🔢</div>
        <div class="game-name">2048</div>
        <p class="game-desc">Slide the tiles, merge the matches, reach 2048.</p>
        <span class="game-tag">Play</span>
      </a>

      <a class="game-card" href="/games/minesweeper/">
        <div class="game-icon">💣</div>
        <div class="game-name">Minesweeper</div>
        <p class="game-desc">Clear every safe square without detonating a mine.</p>
        <span class="game-tag">Play</span>
      </a>

      <a class="game-card" href="/games/memory/">
        <div class="game-icon">🧠</div>
        <div class="game-name">Memory Match</div>
        <p class="game-desc">Flip cards two at a time and clear the board in as few moves as you can.</p>
        <span class="game-tag">Play</span>
      </a>

    </div>

  </div>

  <?php include '../footer.php'; ?>

  <script src="/script.js"></script>
  <script src="/theme.js"></script>
</body>
</html>
