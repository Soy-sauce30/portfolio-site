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
</body>
</html>