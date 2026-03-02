<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MLB Leaders</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="baseball.css">
  <script>
    (function(){var t=localStorage.getItem('theme')||(window.matchMedia('(prefers-color-scheme:light)').matches?'light':'dark');document.documentElement.setAttribute('data-theme',t)})();
  </script>
</head>
<body>

  <div class="wrap">

    <h1 class="page-title">MLB Leaders</h1>

    <!-- Position tabs -->
    <nav class="pos-tabs" id="posTabs">
      <button class="pos-tab active" data-pos="all">All Hitters</button>
      <button class="pos-tab" data-pos="C">C</button>
      <button class="pos-tab" data-pos="1B">1B</button>
      <button class="pos-tab" data-pos="2B">2B</button>
      <button class="pos-tab" data-pos="SS">SS</button>
      <button class="pos-tab" data-pos="3B">3B</button>
      <button class="pos-tab" data-pos="OF">OF</button>
      <button class="pos-tab" data-pos="LF">LF</button>
      <button class="pos-tab" data-pos="CF">CF</button>
      <button class="pos-tab" data-pos="RF">RF</button>
      <button class="pos-tab" data-pos="DH">DH</button>
    </nav>

    <!-- Filters -->
    <div class="filter-bar">
      <div class="fg">
        <label>Division</label>
        <select id="divFilter">
          <option value="all">All Divisions</option>
          <option value="AL East">AL East</option>
          <option value="AL Central">AL Central</option>
          <option value="AL West">AL West</option>
          <option value="NL East">NL East</option>
          <option value="NL Central">NL Central</option>
          <option value="NL West">NL West</option>
        </select>
      </div>
      <div class="fg">
        <label>Team</label>
        <select id="teamFilter">
          <option value="all">All Teams</option>
        </select>
      </div>
      <div class="fg">
        <label>Sort by</label>
        <select id="statSort">
          <option value="overall">Overall</option>
          <option value="homeRuns">HR</option>
          <option value="avg">AVG</option>
          <option value="rbi">RBI</option>
          <option value="ops">OPS</option>
          <option value="hits">H</option>
          <option value="runs">R</option>
          <option value="stolenBases">SB</option>
          <option value="obp">OBP</option>
          <option value="slg">SLG</option>
          <option value="doubles">2B</option>
          <option value="baseOnBalls">BB</option>
        </select>
      </div>
      <div class="fg">
        <label>Season</label>
        <select id="seasonSel">
          <option value="2025">2025</option>
          <option value="2024">2024</option>
          <option value="2023">2023</option>
        </select>
      </div>
      <a href="index.html" class="back-link">&larr; Home</a>
      <button id="themeToggle" class="theme-toggle" aria-label="Toggle theme">
        <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
        <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
      </button>
    </div>

    <!-- Rankings -->
    <div class="rankings-card">
      <div id="content">
        <div class="state" id="loading">Loading...</div>
        <div class="state" id="empty" style="display:none;">No players found.</div>
        <div class="state" id="error" style="display:none;">Failed to load data. Try another season.</div>
      </div>
    </div>

    <footer class="foot">
      Data from <a href="https://www.mlb.com" target="_blank">MLB</a>
    </footer>

  </div>

  <script src="baseball.js"></script>
  <script src="theme.js"></script>
</body>
</html>