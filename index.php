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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&family=Fraunces:ital,opsz,wght@0,9..144,600;1,9..144,600&family=Barlow+Condensed:ital,wght@1,700;1,800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <script>
    (function(){var t=localStorage.getItem('theme')||(window.matchMedia('(prefers-color-scheme:light)').matches?'light':'dark');document.documentElement.setAttribute('data-theme',t)})();
  </script>
  <style>
    /* =========================================
       Homepage — car finder
       Base layout + defaults. The light (Magazine) and
       dark (Racing) looks live in /cars/themes.css.
       ========================================= */

    :root {
      --bg: #0a0812;
      --bg-card: rgba(255, 255, 255, 0.035);
      --bg-raised: rgba(255, 255, 255, 0.06);
      --border: rgba(167, 139, 250, 0.14);
      --text: #ecebf3;
      --text-muted: #9a96ad;
      --accent: #a78bfa;
      --accent-2: #e879f9;
      --accent-dim: rgba(167, 139, 250, 0.12);
      --accent-glow: rgba(167, 139, 250, 0.3);
      --grad: linear-gradient(120deg, #8b5cf6 0%, #c084fc 50%, #e879f9 100%);
      --on-accent: #ffffff;
      --good: #4ade80;
    }

    .grad-text {
      background: var(--grad);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }

    /* Nav background when scrolled: match the violet backdrop. */
    .nav.scrolled { background: rgba(10, 8, 18, 0.82); }
    [data-theme="light"] .nav.scrolled { background: rgba(247, 245, 252, 0.88); }

    /* ---------- Hero ---------- */

    section.home-hero {
      min-height: 72vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 7.5rem 1rem 3.5rem;
      position: relative;
    }

    .home-kicker {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.78rem;
      font-weight: 600;
      color: var(--text);
      padding: 0.35rem 0.85rem 0.35rem 0.4rem;
      border: 1px solid var(--border);
      border-radius: 999px;
      background: var(--bg-card);
      backdrop-filter: blur(8px);
      margin-bottom: 1.5rem;
    }

    .home-kicker span {
      background: var(--grad);
      color: #fff;
      font-size: 0.68rem;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      padding: 0.15rem 0.5rem;
      border-radius: 999px;
    }

    .home-name {
      font-family: 'Space Grotesk', sans-serif;
      font-size: clamp(2.5rem, 8vw, 4.8rem);
      font-weight: 700;
      letter-spacing: -0.045em;
      line-height: 1.02;
      margin-bottom: 1.1rem;
    }

    .home-tagline {
      font-size: clamp(1rem, 2.4vw, 1.15rem);
      color: var(--text-muted);
      margin-bottom: 2.25rem;
      max-width: 500px;
      line-height: 1.65;
    }

    /* ---------- Search bar ---------- */

    .finder {
      width: 100%;
      max-width: 620px;
      display: flex;
      gap: 0.5rem;
      padding: 0.45rem;
      background: var(--bg-raised);
      border: 1px solid var(--border);
      border-radius: 16px;
      box-shadow: 0 20px 60px -20px rgba(139, 92, 246, 0.45);
      backdrop-filter: blur(12px);
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    .finder:focus-within {
      border-color: var(--accent);
      box-shadow: 0 0 0 4px var(--accent-dim), 0 20px 60px -20px rgba(139, 92, 246, 0.6);
    }

    .finder-icon {
      display: flex;
      align-items: center;
      padding-left: 0.7rem;
      color: var(--text-muted);
    }

    .finder input[type="text"] {
      flex: 1;
      min-width: 0;
      background: none;
      border: 0;
      outline: none;
      color: var(--text);
      font: inherit;
      font-size: 1rem;
      padding: 0 0.5rem;
    }

    .finder input::placeholder { color: var(--text-muted); opacity: 0.8; }

    .finder-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      border: 0;
      border-radius: 11px;
      padding: 0.75rem 1.2rem;
      font: inherit;
      font-size: 0.92rem;
      font-weight: 600;
      cursor: pointer;
      white-space: nowrap;
      transition: filter 0.2s, background 0.2s, transform 0.1s;
    }

    .finder-btn:active { transform: scale(0.97); }
    .finder-btn:disabled { opacity: 0.55; cursor: wait; }

    .finder-go { background: var(--grad); color: var(--on-accent); }
    .finder-go:hover { filter: brightness(1.1); }

    .finder-photo { background: var(--accent-dim); color: var(--accent); }
    .finder-photo:hover { background: var(--accent-glow); }

    .finder-hint {
      margin-top: 1.1rem;
      font-size: 0.82rem;
      color: var(--text-muted);
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      align-items: center;
      gap: 0.4rem;
    }

    .finder-hint button {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 999px;
      color: var(--text);
      font: inherit;
      padding: 0.3rem 0.75rem;
      cursor: pointer;
      transition: border-color 0.2s, color 0.2s;
    }
    .finder-hint button:hover { border-color: var(--accent); color: var(--accent); }

    @media (max-width: 520px) {
      .finder { flex-wrap: wrap; }
      .finder-icon { display: none; }
      .finder input[type="text"] { flex-basis: 100%; padding: 0.75rem 0.75rem; }
      .finder-btn { flex: 1; justify-content: center; }
    }

    /* ---------- Result panel ---------- */

    .result {
      max-width: 900px;
      margin: 0 auto;
      padding: 0 1rem;
      scroll-margin-top: 5rem;
    }

    .result:empty { display: none; }

    .result-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 20px;
      overflow: hidden;
      margin-bottom: 3.5rem;
      backdrop-filter: blur(12px);
      box-shadow: 0 30px 80px -40px rgba(139, 92, 246, 0.5);
      animation: rise 0.35s ease;
    }

    @keyframes rise { from { opacity: 0; transform: translateY(10px); } }

    .result-head {
      display: flex;
      gap: 1.25rem;
      align-items: center;
      padding: 1.5rem;
      background: linear-gradient(120deg, var(--accent-dim), transparent 70%);
      border-bottom: 1px solid var(--border);
    }

    .result-photo {
      width: 160px;
      height: 108px;
      object-fit: cover;
      border-radius: 12px;
      flex-shrink: 0;
      border: 1px solid var(--border);
    }

    .result-head .car-art { width: 120px; height: 64px; margin: 0; flex-shrink: 0; }

    .result-title {
      font-family: 'Space Grotesk', sans-serif;
      font-size: clamp(1.45rem, 4vw, 2rem);
      font-weight: 700;
      letter-spacing: -0.025em;
      line-height: 1.15;
    }

    .result-sub {
      color: var(--text-muted);
      font-size: 0.86rem;
      margin-top: 0.35rem;
    }

    .badge {
      display: inline-block;
      font-size: 0.68rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      padding: 0.2rem 0.55rem;
      border-radius: 999px;
      background: var(--accent-dim);
      color: var(--accent);
      margin-left: 0.5rem;
      vertical-align: middle;
    }

    .result-body { padding: 1.5rem; }

    .result-summary {
      color: var(--text);
      opacity: 0.9;
      line-height: 1.75;
      margin-bottom: 1.5rem;
    }

    .price-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 0.75rem;
      margin-bottom: 1.5rem;
    }

    .price {
      padding: 1rem 1.1rem;
      border-radius: 14px;
      background: var(--accent-dim);
      border: 1px solid var(--border);
    }

    .price-label, .section-label {
      font-size: 0.72rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      color: var(--text-muted);
      margin-bottom: 0.35rem;
    }

    .price-value {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.35rem;
      font-weight: 700;
    }

    .spec-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 0.6rem;
      margin-bottom: 1.75rem;
    }

    .spec {
      background: var(--bg-raised);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 0.8rem 0.95rem;
    }

    .spec-label {
      font-size: 0.72rem;
      color: var(--text-muted);
      margin-bottom: 0.25rem;
    }

    .spec-value { font-weight: 600; font-size: 0.95rem; line-height: 1.4; }

    .versions { overflow-x: auto; margin-bottom: 1.75rem; }
    .versions table { width: 100%; border-collapse: collapse; font-size: 0.86rem; }
    .versions th {
      text-align: left;
      font-weight: 600;
      color: var(--text-muted);
      font-size: 0.74rem;
      padding: 0.45rem 0.9rem 0.45rem 0;
      border-bottom: 1px solid var(--border);
    }
    .versions td { padding: 0.6rem 0.9rem 0.6rem 0; border-bottom: 1px solid var(--border); white-space: nowrap; }
    .versions tr:last-child td { border-bottom: 0; }

    .procon {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 1.5rem;
      margin-bottom: 1.5rem;
    }

    .procon ul { list-style: none; padding: 0; margin: 0; }
    .procon li {
      font-size: 0.9rem;
      line-height: 1.5;
      padding: 0.3rem 0 0.3rem 1.3rem;
      position: relative;
    }
    .procon .pros li::before { content: '+'; color: var(--good); font-weight: 700; position: absolute; left: 0; }
    .procon .cons li::before { content: '–'; color: var(--accent-2); font-weight: 700; position: absolute; left: 0; }

    .rivals { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem; }
    .rivals button {
      background: none;
      border: 1px solid var(--border);
      border-radius: 999px;
      color: var(--text);
      font: inherit;
      padding: 0.25rem 0.75rem;
      margin: 0.25rem 0.25rem 0 0;
      cursor: pointer;
    }
    .rivals button:hover { border-color: var(--accent); color: var(--accent); }

    .result-links { display: flex; flex-wrap: wrap; gap: 0.5rem; }
    .result-links a {
      display: inline-flex;
      align-items: center;
      padding: 0.6rem 1rem;
      border-radius: 11px;
      border: 1px solid var(--border);
      background: var(--bg-raised);
      color: var(--text);
      font-size: 0.86rem;
      font-weight: 600;
      text-decoration: none;
      transition: border-color 0.2s, color 0.2s;
    }
    .result-links a:first-child { background: var(--grad); color: var(--on-accent); border-color: transparent; }
    .result-links a:hover { border-color: var(--accent); color: var(--accent); }
    .result-links a:first-child:hover { color: var(--on-accent); filter: brightness(1.1); }

    .result-note {
      font-size: 0.78rem;
      color: var(--text-muted);
      margin-top: 1.25rem;
      line-height: 1.6;
    }

    .result-msg {
      text-align: center;
      padding: 2.75rem 1.5rem;
      color: var(--text-muted);
    }

    .spinner {
      width: 30px;
      height: 30px;
      border: 3px solid var(--accent-dim);
      border-top-color: var(--accent);
      border-radius: 50%;
      margin: 0 auto 1rem;
      animation: spin 0.8s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ---------- Top rated ---------- */

    section.home-section {
      max-width: 1080px;
      margin: 0 auto;
      padding: 2rem 1rem 4.5rem;
    }

    .section-head {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: flex-end;
      gap: 1rem 2rem;
      margin-bottom: 1.75rem;
    }

    .home-heading {
      font-family: 'Space Grotesk', sans-serif;
      font-size: clamp(1.6rem, 4vw, 2.1rem);
      font-weight: 700;
      letter-spacing: -0.03em;
      margin-bottom: 0.4rem;
    }

    .home-sub {
      color: var(--text-muted);
      font-size: 0.93rem;
      line-height: 1.6;
    }

    .filters {
      display: flex;
      flex-wrap: wrap;
      gap: 0.3rem;
      padding: 0.3rem;
      border: 1px solid var(--border);
      border-radius: 999px;
      background: var(--bg-card);
    }

    .filters button {
      background: none;
      border: 0;
      border-radius: 999px;
      color: var(--text-muted);
      font: inherit;
      font-size: 0.83rem;
      font-weight: 500;
      padding: 0.4rem 0.9rem;
      cursor: pointer;
      transition: color 0.2s, background 0.2s;
    }
    .filters button:hover { color: var(--text); }
    .filters button[aria-pressed="true"] { background: var(--grad); color: #fff; }

    .car-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      gap: 1rem;
    }

    .car-card {
      display: flex;
      flex-direction: column;
      text-align: left;
      padding: 0;
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 18px;
      overflow: hidden;
      color: inherit;
      font: inherit;
      cursor: pointer;
      transition: border-color 0.25s, transform 0.25s, box-shadow 0.25s;
    }

    .car-card:hover {
      border-color: var(--accent-glow);
      transform: translateY(-4px);
      box-shadow: 0 24px 50px -28px rgba(139, 92, 246, 0.65);
    }

    .car-card[hidden] { display: none; }

    .car-stage {
      position: relative;
      height: 128px;
      display: flex;
      align-items: flex-end;
      justify-content: center;
      padding-bottom: 10px;
      background:
        radial-gradient(120px 50px at 50% 100%, var(--accent-glow), transparent 70%),
        linear-gradient(160deg, var(--accent-dim), transparent 75%);
      border-bottom: 1px solid var(--border);
    }

    .car-tag {
      position: absolute;
      top: 12px;
      left: 12px;
      font-size: 0.66rem;
      font-weight: 600;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--accent);
      background: var(--bg-raised);
      border: 1px solid var(--border);
      padding: 0.2rem 0.55rem;
      border-radius: 999px;
    }

    .car-art { width: 176px; height: 76px; color: var(--accent); transition: transform 0.35s ease; }
    .car-art svg { width: 100%; height: 100%; overflow: visible; }
    .car-card:hover .car-art { transform: translateX(6px); }

    .car-info { padding: 1.1rem 1.2rem 1.25rem; display: flex; flex-direction: column; flex: 1; }

    .car-name {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.12rem;
      font-weight: 600;
      letter-spacing: -0.01em;
      margin-bottom: 0.4rem;
    }

    .car-desc {
      color: var(--text-muted);
      font-size: 0.85rem;
      line-height: 1.55;
      flex: 1;
    }

    .car-more {
      margin-top: 1rem;
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--accent);
      display: inline-flex;
      gap: 0.3rem;
      transition: gap 0.2s;
    }
    .car-card:hover .car-more { gap: 0.55rem; }

    .home-rule {
      max-width: 1080px;
      margin: 0 auto;
      border: 0;
      height: 1px;
      background: linear-gradient(90deg, transparent, var(--border), transparent);
    }

    .home-foot {
      max-width: 1080px;
      margin: 0 auto;
      padding: 2.5rem 1rem;
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 1rem;
      color: var(--text-muted);
      font-size: 0.88rem;
    }
    .home-foot a { color: var(--accent); text-decoration: none; }
    .home-foot a:hover { text-decoration: underline; }

    /* ---------- Race cars ---------- */

    .race-card .car-stage {
      height: 150px;
      padding: 0;
      align-items: stretch;
      overflow: hidden;
      background: #1a1626;
    }

    .race-card .car-stage img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.4s ease;
    }
    .race-card:hover .car-stage img { transform: scale(1.06); }

    .badge-3d {
      position: absolute;
      right: 12px;
      bottom: 12px;
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
      font-size: 0.7rem;
      font-weight: 700;
      color: #fff;
      background: rgba(10, 8, 18, 0.7);
      backdrop-filter: blur(6px);
      border: 1px solid rgba(255, 255, 255, 0.15);
      padding: 0.25rem 0.55rem;
      border-radius: 999px;
    }

    .race-card .car-tag {
      z-index: 1;
      color: #fff;
      background: rgba(10, 8, 18, 0.72);
      border-color: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(6px);
    }

    .facts { list-style: none; padding: 0; margin: 0 0 1.5rem; display: grid; gap: 0.5rem; }
    .facts li {
      font-size: 0.9rem;
      line-height: 1.55;
      padding: 0.7rem 0.9rem 0.7rem 2.3rem;
      position: relative;
      border-radius: 12px;
      background: var(--accent-dim);
    }
    .facts li::before {
      content: '\2605';
      position: absolute;
      left: 0.9rem;
      top: 0.7rem;
      color: var(--accent);
    }

    /* ---------- 3D viewer ---------- */

    .viewer-wrap { margin-bottom: 1.75rem; }
    .viewer-wrap:empty { display: none; }

    .viewer {
      position: relative;
      aspect-ratio: 16 / 9;
      border-radius: 16px;
      overflow: hidden;
      background: #14111f;
      border: 1px solid var(--border);
    }

    .viewer iframe { width: 100%; height: 100%; border: 0; display: block; }

    .viewer-poster {
      width: 100%;
      height: 100%;
      padding: 0;
      border: 0;
      background: none;
      cursor: pointer;
      position: relative;
      display: block;
    }
    .viewer-poster img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .viewer-poster::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, transparent 40%, rgba(10, 8, 18, 0.65));
    }

    .viewer-play {
      position: absolute;
      left: 50%;
      top: 50%;
      transform: translate(-50%, -50%);
      z-index: 1;
      display: inline-flex;
      align-items: center;
      gap: 0.55rem;
      padding: 0.85rem 1.3rem;
      border-radius: 999px;
      background: var(--grad);
      color: #fff;
      font-weight: 700;
      font-size: 0.95rem;
      box-shadow: 0 12px 40px -8px rgba(139, 92, 246, 0.8);
      transition: transform 0.2s;
    }
    .viewer-poster:hover .viewer-play { transform: translate(-50%, -50%) scale(1.05); }

    .viewer-hint {
      position: absolute;
      left: 0;
      right: 0;
      bottom: 0.9rem;
      z-index: 1;
      text-align: center;
      color: rgba(255, 255, 255, 0.85);
      font-size: 0.8rem;
    }

    .viewer-meta {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      gap: 0.6rem 1rem;
      margin-top: 0.6rem;
      font-size: 0.76rem;
      color: var(--text-muted);
    }
    .viewer-meta a { color: var(--accent); text-decoration: none; }
    .viewer-meta a:hover { text-decoration: underline; }

    .viewer-picks { display: flex; gap: 0.4rem; }
    .viewer-picks button {
      width: 56px;
      height: 36px;
      padding: 0;
      border-radius: 8px;
      overflow: hidden;
      border: 2px solid transparent;
      background: var(--bg-raised);
      cursor: pointer;
      opacity: 0.6;
      transition: opacity 0.2s, border-color 0.2s;
    }
    .viewer-picks button:hover { opacity: 1; }
    .viewer-picks button[aria-pressed="true"] { border-color: var(--accent); opacity: 1; }
    .viewer-picks img { width: 100%; height: 100%; object-fit: cover; display: block; }

    .viewer-loading {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.6rem;
      aspect-ratio: 16 / 9;
      border-radius: 16px;
      border: 1px dashed var(--border);
      color: var(--text-muted);
      font-size: 0.85rem;
    }
    .viewer-loading .spinner { width: 20px; height: 20px; border-width: 2px; margin: 0; }

    @media (prefers-reduced-motion: reduce) {
      .result-card, .spinner { animation: none; }
      .car-card, .car-art, .race-card .car-stage img { transition: none; }
    }
  </style>
  <link rel="stylesheet" href="/cars/themes.css">
</head>
<body>

  <!-- Header -->
  <?php include 'header.php';?>

  <!-- Hero + finder -->
  <section class="home-hero" id="home">
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
  <script>
  (function () {
    // ---------- Top rated list ----------
    var CARS = [
      { name: 'Mazda MX-5 Miata', cat: 'Roadster',       type: 'sports',        desc: 'Light, cheap, and endlessly fun. The benchmark for pure driving joy.' },
      { name: 'Porsche 911',      cat: 'Sports car',     type: 'sports',        desc: 'Six decades of refinement. Daily-drivable and a weapon on track.' },
      { name: 'Chevrolet Corvette', cat: 'Sports car',   type: 'sports',        desc: 'Mid-engine supercar numbers at a fraction of supercar money.' },
      { name: 'BMW M3',           cat: 'Sport sedan',    type: 'sedan',         desc: 'Four doors, rear seats, and serious straight-six muscle.' },
      { name: 'Toyota Camry',     cat: 'Midsize sedan',  type: 'sedan',         desc: 'Hybrid-only now, with great mileage and a reputation for lasting forever.' },
      { name: 'Honda Civic',      cat: 'Compact',        type: 'sedan',         desc: 'Roomy, efficient, well built — and the Type R is a legend.' },
      { name: 'Toyota RAV4',      cat: 'Compact SUV',    type: 'suv',           desc: 'America’s best-selling SUV for a reason: practical, reliable, efficient.' },
      { name: 'Kia Telluride',    cat: 'Three-row SUV',  type: 'suv',           desc: 'Luxury-feeling family hauler with loads of standard features.' },
      { name: 'Honda CR-V',       cat: 'Compact SUV',    type: 'suv',           desc: 'Huge cargo space, smooth hybrid, and a comfortable ride.' },
      { name: 'Ford F-150',       cat: 'Full-size truck', type: 'truck',        desc: 'The best-selling truck in America, from work spec to Raptor.' },
      { name: 'Toyota Tacoma',    cat: 'Midsize truck',  type: 'truck',         desc: 'Off-road ready and famous for holding its value.' },
      { name: 'Tesla Model Y',    cat: 'Electric SUV',   type: 'suv ev',        desc: 'Big range, quick charging, and the Supercharger network.' },
      { name: 'Hyundai Ioniq 5',  cat: 'Electric SUV',   type: 'suv ev',        desc: 'Retro-future looks and some of the fastest charging on sale.' },
      { name: 'Rivian R1S',       cat: 'Electric SUV',   type: 'suv ev',        desc: 'Seven seats, serious off-road chops, and wild acceleration.' }
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
          '<span class="car-more">Full specs <span>&rarr;</span></span>' +
        '</div>' +
      '</button>';
    }).join('');

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
              '<div class="price"><div class="price-label">New (MSRP)</div><div class="price-value">' + esc(car.price_new || '—') + '</div></div>' +
              '<div class="price"><div class="price-label">Used</div><div class="price-value">' + esc(car.price_used || '—') + '</div></div>' +
            '</div>' : '') +
            '<div class="spec-grid">' + car.specs.map(function (s) {
              return '<div class="spec"><div class="spec-label">' + esc(s.label) + '</div><div class="spec-value">' + esc(s.value) + '</div></div>';
            }).join('') + '</div>' +
            (car.versions && car.versions.length ? '<div class="section-label">Engine options</div><div class="versions"><table>' +
              '<tr><th>Engine</th><th>Transmission</th><th>Fuel economy</th></tr>' +
              car.versions.map(function (v) {
                return '<tr><td>' + esc(v.engine) + '</td><td>' + esc(v.transmission) + '</td><td>' + esc(v.mpg) + '</td></tr>';
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
      else if (car.identified && car.model) findModels(car.make, car.model);
    }

    // ---------- 3D viewer (Sketchfab) ----------
    // The poster is just an image; the heavy 3D player only loads when tapped.
    var viewerModels = [];

    function findModels(make, model) {
      var box = document.getElementById('viewer3d');
      box.innerHTML = '<div class="viewer-loading"><div class="spinner"></div>Looking for a 3D model\u2026</div>';
      fetch('/cars/models.php?make=' + encodeURIComponent(make) + '&model=' + encodeURIComponent(model))
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
        models: [r.model]
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
