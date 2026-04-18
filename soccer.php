<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Soccer Standings</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="soccer.css">
  <script>
    (function(){var t=localStorage.getItem('theme')||(window.matchMedia('(prefers-color-scheme:light)').matches?'light':'dark');document.documentElement.setAttribute('data-theme',t)})();
  </script>
</head>
<body>

  <?php include 'header.php'; ?>

  <div class="wrap" style="padding-top: 5rem;">

    <h1 class="page-title">Soccer Standings</h1>

    <!-- League tabs -->
    <nav class="league-tabs" id="leagueTabs">
      <button class="league-tab active" data-league="eng.1">
        <span class="tab-name">Premier League</span>
      </button>
      <button class="league-tab" data-league="esp.1">
        <span class="tab-name">La Liga</span>
      </button>
      <button class="league-tab" data-league="ger.1">
        <span class="tab-name">Bundesliga</span>
      </button>
      <button class="league-tab" data-league="ita.1">
        <span class="tab-name">Serie A</span>
      </button>
      <button class="league-tab" data-league="fra.1">
        <span class="tab-name">Ligue 1</span>
      </button>
      <button class="league-tab" data-league="usa.1">
        <span class="tab-name">MLS</span>
      </button>
    </nav>

    <!-- Zone filter tabs -->
    <nav class="zone-tabs" id="zoneTabs">
      <button class="zone-tab active" data-zone="all">All</button>
      <button class="zone-tab" data-zone="ucl">Champions League</button>
      <button class="zone-tab" data-zone="uel">Europa League</button>
      <button class="zone-tab" data-zone="uecl">Conference League</button>
      <button class="zone-tab" data-zone="rel">Relegation</button>
    </nav>

    <!-- Info bar -->
    <div class="info-bar">
      <div class="fg">
        <label>Sort by</label>
        <select id="statSort">
          <option value="rank">Rank</option>
          <option value="points">Pts</option>
          <option value="wins">W</option>
          <option value="pointDifferential">GD</option>
          <option value="pointsFor">GF</option>
          <option value="gamesPlayed">GP</option>
        </select>
      </div>
      <div class="fg search-fg">
        <label>Search</label>
        <input type="text" id="teamSearch" placeholder="Search team..." autocomplete="off">
      </div>
    </div>

    <!-- Standings -->
    <div class="rankings-card">
      <div id="content">
        <div class="state" id="loading">Loading...</div>
        <div class="state" id="empty" style="display:none;">No standings found.</div>
        <div class="state" id="error" style="display:none;">Failed to load data.</div>
      </div>
    </div>

    <footer class="foot">
      Data from <a href="https://www.espn.com" target="_blank">ESPN</a>
    </footer>

  </div>

  <?php include 'footer.php'; ?>

  <script src="script.js"></script>
  <script src="soccer.js"></script>
  <script src="theme.js"></script>
</body>
</html>
