(() => {
  const STATS_API = 'https://api.nhle.com/stats/rest/en';
  const WEB_API   = 'https://api-web.nhle.com/v1';
  const SEASON    = 20252026;

  // Skater stat metadata
  const SKATER_STATS = {
    points:           { label: 'PTS', key: 'points' },
    goals:            { label: 'G',   key: 'goals' },
    assists:          { label: 'A',   key: 'assists' },
    plusMinus:        { label: '+/-', key: 'plusMinus' },
    shots:            { label: 'SOG', key: 'shots' },
    ppGoals:          { label: 'PPG', key: 'ppGoals' },
    shGoals:          { label: 'SHG', key: 'shGoals' },
    gameWinningGoals: { label: 'GWG', key: 'gameWinningGoals' },
    penaltyMinutes:   { label: 'PIM', key: 'penaltyMinutes' },
    gamesPlayed:      { label: 'GP',  key: 'gamesPlayed' },
  };

  const SKATER_COLUMNS = ['gamesPlayed', 'goals', 'assists', 'points', 'plusMinus', 'shots', 'penaltyMinutes'];

  // Goalie stat metadata
  const GOALIE_STATS = {
    wins:                { label: 'W',    key: 'wins' },
    losses:              { label: 'L',    key: 'losses' },
    otLosses:            { label: 'OTL',  key: 'otLosses' },
    savePct:             { label: 'SV%',  key: 'savePct',  rate: true, decimals: 3 },
    goalsAgainstAverage: { label: 'GAA',  key: 'goalsAgainstAverage', decimals: 2 },
    shutouts:            { label: 'SO',   key: 'shutouts' },
    saves:               { label: 'SV',   key: 'saves' },
    shotsAgainst:        { label: 'SA',   key: 'shotsAgainst' },
    gamesPlayed:         { label: 'GP',   key: 'gamesPlayed' },
  };

  const GOALIE_COLUMNS = ['gamesPlayed', 'wins', 'losses', 'otLosses', 'savePct', 'goalsAgainstAverage', 'shutouts'];

  // State
  let skaters = [];
  let goalies = [];
  let standings = [];
  let teamMeta = {};  // abbr -> { conference, division, name, logo }

  let state = {
    view: 'players',         // 'players' | 'standings'
    pos: 'all',              // 'all' | 'C' | 'L' | 'R' | 'D' | 'G'
    conference: 'all',
    division: 'all',
    team: 'all',
    stat: 'points',
    standingsView: 'division', // 'division' | 'conference' | 'league'
  };

  // DOM
  const $ = id => document.getElementById(id);
  const els = {
    viewTabs:       $('viewTabs'),
    playersView:    $('playersView'),
    standingsView:  $('standingsView'),
    posTabs:        $('posTabs'),
    confFilter:     $('confFilter'),
    divFilter:      $('divFilter'),
    teamFilter:     $('teamFilter'),
    statSort:       $('statSort'),
    playersContent: $('playersContent'),
    playersLoading: $('playersLoading'),
    playersEmpty:   $('playersEmpty'),
    playersError:   $('playersError'),
    standingsTabs:    $('standingsTabs'),
    standingsContent: $('standingsContent'),
    standingsLoading: $('standingsLoading'),
    standingsError:   $('standingsError'),
  };

  const esc = s => { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; };

  // ── API ────────────────────────────────────
  async function fetchSkaters() {
    const url = `${STATS_API}/skater/summary?limit=-1&cayenneExp=seasonId=${SEASON}%20and%20gameTypeId=2`;
    const res = await fetch(url);
    const data = await res.json();
    skaters = (data.data || []).map(p => ({
      id: p.playerId,
      name: p.skaterFullName || '?',
      pos: p.positionCode || '?',
      team: p.teamAbbrevs || '?',
      stats: p,
    }));
  }

  async function fetchGoalies() {
    const url = `${STATS_API}/goalie/summary?limit=-1&cayenneExp=seasonId=${SEASON}%20and%20gameTypeId=2`;
    const res = await fetch(url);
    const data = await res.json();
    goalies = (data.data || []).map(p => ({
      id: p.playerId,
      name: p.goalieFullName || '?',
      pos: 'G',
      team: p.teamAbbrevs || '?',
      stats: p,
    }));
  }

  async function fetchStandings() {
    const res = await fetch(`${WEB_API}/standings/now`);
    const data = await res.json();
    standings = (data.standings || []).map(t => ({
      name: t.teamName?.default || t.placeName?.default || '?',
      abbr: t.teamAbbrev?.default || t.teamAbbrev || '?',
      logo: t.teamLogo || '',
      conference: t.conferenceName || '',
      division: t.divisionName || '',
      gp: t.gamesPlayed || 0,
      w: t.wins || 0,
      l: t.losses || 0,
      otl: t.otLosses || 0,
      pts: t.points || 0,
      gf: t.goalFor || 0,
      ga: t.goalAgainst || 0,
      diff: t.goalDifferential || 0,
      streak: (t.streakCode || '') + (t.streakCount || ''),
    }));

    // Build team metadata lookup
    teamMeta = {};
    standings.forEach(t => {
      teamMeta[t.abbr] = {
        name: t.name,
        logo: t.logo,
        conference: t.conference,
        division: t.division,
      };
    });

    populateTeamDropdown();
  }

  function populateTeamDropdown() {
    const sel = els.teamFilter;
    sel.innerHTML = '<option value="all">All Teams</option>';

    Object.entries(teamMeta)
      .filter(([, t]) => {
        if (state.conference !== 'all' && t.conference !== state.conference) return false;
        if (state.division !== 'all' && t.division !== state.division) return false;
        return true;
      })
      .sort((a, b) => a[1].name.localeCompare(b[1].name))
      .forEach(([abbr, t]) => {
        const opt = document.createElement('option');
        opt.value = abbr;
        opt.textContent = t.name;
        sel.appendChild(opt);
      });
  }

  // ── Players filter/sort ────────────────────
  function getPlayerList() {
    const isGoalie = state.pos === 'G';
    let list = isGoalie ? [...goalies] : [...skaters];

    // Position filter
    if (!isGoalie && state.pos !== 'all') {
      list = list.filter(p => p.pos === state.pos);
    }

    // Team meta filters
    list = list.filter(p => {
      const meta = teamMeta[p.team];
      if (!meta) return true; // no meta yet, keep
      if (state.conference !== 'all' && meta.conference !== state.conference) return false;
      if (state.division !== 'all' && meta.division !== state.division) return false;
      if (state.team !== 'all' && p.team !== state.team) return false;
      return true;
    });

    // Sort
    const stat = isGoalie ? getValidGoalieStat(state.stat) : state.stat;
    const meta = (isGoalie ? GOALIE_STATS : SKATER_STATS)[stat];
    const key = meta?.key || stat;

    list.sort((a, b) => {
      let va = a.stats[key] ?? 0;
      let vb = b.stats[key] ?? 0;
      // GAA sorts ascending (lower is better)
      if (stat === 'goalsAgainstAverage') return va - vb;
      return vb - va;
    });

    return { list, isGoalie };
  }

  function getValidGoalieStat(s) {
    if (GOALIE_STATS[s]) return s;
    return 'wins'; // default when switching to goalie view
  }

  // ── Render players ─────────────────────────
  function renderPlayers() {
    const { list, isGoalie } = getPlayerList();

    // Remove old table
    const old = els.playersContent.querySelector('.stats-table');
    if (old) old.remove();

    hideStates('players');

    if (list.length === 0) {
      els.playersEmpty.style.display = '';
      return;
    }

    const META = isGoalie ? GOALIE_STATS : SKATER_STATS;
    const COLS = isGoalie ? GOALIE_COLUMNS : SKATER_COLUMNS;
    const sortStat = isGoalie ? getValidGoalieStat(state.stat) : state.stat;

    let h = '<table class="stats-table"><thead><tr>';
    h += '<th class="col-rank">#</th>';
    h += '<th class="col-name">Player</th>';
    h += '<th class="col-team">Team</th>';
    h += '<th class="col-pos">Pos</th>';

    COLS.forEach(k => {
      const cls = k === sortStat ? ' active' : '';
      h += `<th class="col-stat${cls}" data-stat="${k}">${META[k].label}</th>`;
    });

    h += '</tr></thead><tbody>';

    list.slice(0, 300).forEach((p, i) => {
      const medal = i < 3 ? ['gold', 'silver', 'bronze'][i] : '';
      h += '<tr>';
      h += `<td class="col-rank ${medal}">${i + 1}</td>`;
      h += `<td class="col-name">${esc(p.name)}</td>`;
      h += `<td class="col-team">${esc(p.team)}</td>`;
      h += `<td class="col-pos">${esc(formatPos(p.pos))}</td>`;

      COLS.forEach(k => {
        const cls = k === sortStat ? ' active' : '';
        const val = formatStat(k, p.stats[META[k].key], META[k]);
        let extra = '';
        if (k === 'plusMinus' && p.stats.plusMinus > 0) extra = ' plus';
        if (k === 'plusMinus' && p.stats.plusMinus < 0) extra = ' minus';
        h += `<td class="col-stat${cls}${extra}">${val}</td>`;
      });

      h += '</tr>';
    });

    h += '</tbody></table>';
    els.playersContent.insertAdjacentHTML('beforeend', h);

    els.playersContent.querySelectorAll('th[data-stat]').forEach(th => {
      th.addEventListener('click', () => {
        state.stat = th.dataset.stat;
        if (els.statSort.querySelector(`option[value="${state.stat}"]`)) {
          els.statSort.value = state.stat;
        }
        renderPlayers();
      });
    });
  }

  function formatPos(pos) {
    if (pos === 'L') return 'LW';
    if (pos === 'R') return 'RW';
    return pos;
  }

  function formatStat(key, val, meta) {
    if (val == null) return '-';
    if (meta?.rate) {
      // save percentage, shown as .XXX
      const n = typeof val === 'string' ? parseFloat(val) : val;
      return n.toFixed(meta.decimals || 3).replace(/^0/, '');
    }
    if (meta?.decimals) {
      const n = typeof val === 'string' ? parseFloat(val) : val;
      return n.toFixed(meta.decimals);
    }
    if (key === 'plusMinus' && val > 0) return '+' + val;
    return val;
  }

  // ── Render standings ───────────────────────
  function renderStandings() {
    const old = els.standingsContent.querySelector('.standings-wrap');
    if (old) old.remove();

    hideStates('standings');

    if (standings.length === 0) return;

    let h = '<div class="standings-wrap">';

    if (state.standingsView === 'division') {
      const order = ['Atlantic', 'Metropolitan', 'Central', 'Pacific'];
      order.forEach(div => {
        const teams = standings
          .filter(t => t.division === div)
          .sort((a, b) => b.pts - a.pts);
        if (teams.length === 0) return;
        h += `<div class="group-header">${div}</div>`;
        h += buildStandingsTable(teams);
      });
    } else if (state.standingsView === 'conference') {
      ['Eastern', 'Western'].forEach(conf => {
        const teams = standings
          .filter(t => t.conference === conf)
          .sort((a, b) => b.pts - a.pts);
        if (teams.length === 0) return;
        h += `<div class="group-header">${conf}</div>`;
        h += buildStandingsTable(teams);
      });
    } else {
      const teams = [...standings].sort((a, b) => b.pts - a.pts);
      h += buildStandingsTable(teams);
    }

    h += '</div>';
    els.standingsContent.insertAdjacentHTML('beforeend', h);
  }

  function buildStandingsTable(teams) {
    let h = '<table class="stats-table"><thead><tr>';
    h += '<th class="col-rank">#</th>';
    h += '<th>Team</th>';
    h += '<th class="col-stat">GP</th>';
    h += '<th class="col-stat">W</th>';
    h += '<th class="col-stat">L</th>';
    h += '<th class="col-stat">OTL</th>';
    h += '<th class="col-stat active">PTS</th>';
    h += '<th class="col-stat">GF</th>';
    h += '<th class="col-stat">GA</th>';
    h += '<th class="col-stat">DIFF</th>';
    h += '<th class="col-stat">STRK</th>';
    h += '</tr></thead><tbody>';

    teams.forEach((t, i) => {
      const medal = i < 3 ? ['gold', 'silver', 'bronze'][i] : '';
      const diff = t.diff > 0 ? '+' + t.diff : t.diff;
      const diffCls = t.diff > 0 ? ' plus' : t.diff < 0 ? ' minus' : '';
      h += '<tr>';
      h += `<td class="col-rank ${medal}">${i + 1}</td>`;
      h += '<td>';
      h += '<div class="col-team-cell">';
      if (t.logo) h += `<img class="team-logo" src="${esc(t.logo)}" alt="" loading="lazy">`;
      h += `${esc(t.name)}</div></td>`;
      h += `<td class="col-stat">${t.gp}</td>`;
      h += `<td class="col-stat">${t.w}</td>`;
      h += `<td class="col-stat">${t.l}</td>`;
      h += `<td class="col-stat">${t.otl}</td>`;
      h += `<td class="col-stat active">${t.pts}</td>`;
      h += `<td class="col-stat">${t.gf}</td>`;
      h += `<td class="col-stat">${t.ga}</td>`;
      h += `<td class="col-stat${diffCls}">${diff}</td>`;
      h += `<td class="col-stat">${esc(t.streak || '-')}</td>`;
      h += '</tr>';
    });

    h += '</tbody></table>';
    return h;
  }

  function hideStates(view) {
    if (view === 'players') {
      els.playersLoading.style.display = 'none';
      els.playersEmpty.style.display = 'none';
      els.playersError.style.display = 'none';
    } else {
      els.standingsLoading.style.display = 'none';
      els.standingsError.style.display = 'none';
    }
  }

  // ── Stat dropdown swap for goalies ─────────
  function updateStatDropdownForPosition() {
    const isGoalie = state.pos === 'G';
    const entries = isGoalie
      ? [['wins','W'],['savePct','SV%'],['goalsAgainstAverage','GAA'],['shutouts','SO'],['saves','SV'],['losses','L'],['otLosses','OTL'],['gamesPlayed','GP']]
      : [['points','PTS'],['goals','G'],['assists','A'],['plusMinus','+/-'],['shots','SOG'],['ppGoals','PPG'],['shGoals','SHG'],['gameWinningGoals','GWG'],['penaltyMinutes','PIM'],['gamesPlayed','GP']];

    els.statSort.innerHTML = '';
    entries.forEach(([v, l]) => {
      const opt = document.createElement('option');
      opt.value = v;
      opt.textContent = l;
      els.statSort.appendChild(opt);
    });

    // Adjust state.stat to be valid for the current role
    if (isGoalie && !GOALIE_STATS[state.stat]) state.stat = 'wins';
    if (!isGoalie && !SKATER_STATS[state.stat]) state.stat = 'points';
    els.statSort.value = state.stat;
  }

  // ── Events ─────────────────────────────────
  els.viewTabs.addEventListener('click', e => {
    const tab = e.target.closest('.view-tab');
    if (!tab) return;
    els.viewTabs.querySelectorAll('.view-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    state.view = tab.dataset.view;

    if (state.view === 'players') {
      els.playersView.style.display = '';
      els.standingsView.style.display = 'none';
    } else {
      els.playersView.style.display = 'none';
      els.standingsView.style.display = '';
      renderStandings();
    }
  });

  els.posTabs.addEventListener('click', e => {
    const tab = e.target.closest('.pos-tab');
    if (!tab) return;
    els.posTabs.querySelectorAll('.pos-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    state.pos = tab.dataset.pos;
    updateStatDropdownForPosition();
    renderPlayers();
  });

  els.confFilter.addEventListener('change', () => {
    state.conference = els.confFilter.value;
    state.division = 'all';
    state.team = 'all';
    els.divFilter.value = 'all';
    populateTeamDropdown();
    renderPlayers();
  });

  els.divFilter.addEventListener('change', () => {
    state.division = els.divFilter.value;
    state.team = 'all';
    populateTeamDropdown();
    renderPlayers();
  });

  els.teamFilter.addEventListener('change', () => {
    state.team = els.teamFilter.value;
    renderPlayers();
  });

  els.statSort.addEventListener('change', () => {
    state.stat = els.statSort.value;
    renderPlayers();
  });

  els.standingsTabs.addEventListener('click', e => {
    const tab = e.target.closest('.pos-tab');
    if (!tab) return;
    els.standingsTabs.querySelectorAll('.pos-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    state.standingsView = tab.dataset.view;
    renderStandings();
  });

  // ── Init ───────────────────────────────────
  async function init() {
    try {
      // Fetch standings first so teamMeta is populated before player render
      await fetchStandings();
      await Promise.all([fetchSkaters(), fetchGoalies()]);
      renderPlayers();
    } catch (e) {
      console.error('NHL init failed:', e);
      els.playersLoading.style.display = 'none';
      els.playersError.style.display = '';
      els.standingsLoading.style.display = 'none';
      els.standingsError.style.display = '';
    }
  }

  init();
})();
