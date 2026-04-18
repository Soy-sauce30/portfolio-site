(() => {
  const SEASON = 2026;
  const STATS_URL    = 'https://site.web.api.espn.com/apis/common/v3/sports/hockey/nhl/statistics/byathlete';
  const STANDINGS_URL = 'https://site.api.espn.com/apis/v2/sports/hockey/nhl/standings?level=3';

  // Skater stat metadata (ESPN name -> label)
  const SKATER_STATS = {
    points:           { label: 'PTS' },
    goals:            { label: 'G' },
    assists:          { label: 'A' },
    plusMinus:        { label: '+/-' },
    shotsTotal:       { label: 'SOG' },
    powerPlayGoals:   { label: 'PPG' },
    gameWinningGoals: { label: 'GWG' },
    shootingPct:      { label: 'SH%', rate: true, decimals: 1, percent: true },
    penalties:        { label: 'PIM' },  // we alias from penalties array
    games:            { label: 'GP' },
  };

  const SKATER_COLUMNS = ['games', 'goals', 'assists', 'points', 'plusMinus', 'shotsTotal', 'penalties'];

  // Goalie stat metadata
  const GOALIE_STATS = {
    wins:        { label: 'W' },
    losses:      { label: 'L' },
    otLosses:    { label: 'OTL' },
    savePct:     { label: 'SV%', rate: true, decimals: 3 },
    goalsAgainstAvg: { label: 'GAA', decimals: 2 },
    shutouts:    { label: 'SO' },
    saves:       { label: 'SV' },
    shotsAgainst:{ label: 'SA' },
    games:       { label: 'GP' },
  };

  const GOALIE_COLUMNS = ['games', 'wins', 'losses', 'otLosses', 'savePct', 'goalsAgainstAvg', 'shutouts'];

  // State
  let skaters = [];
  let goalies = [];
  let standings = [];
  let teamMeta = {};  // abbr -> { name, logo, conference, division }

  let state = {
    view: 'players',
    pos: 'all',
    conference: 'all',
    division: 'all',
    team: 'all',
    stat: 'points',
    standingsView: 'division',
  };

  // DOM
  const $ = id => document.getElementById(id);
  const els = {
    viewTabs:         $('viewTabs'),
    playersView:      $('playersView'),
    standingsView:    $('standingsView'),
    posTabs:          $('posTabs'),
    confFilter:       $('confFilter'),
    divFilter:        $('divFilter'),
    teamFilter:       $('teamFilter'),
    statSort:         $('statSort'),
    playersContent:   $('playersContent'),
    playersLoading:   $('playersLoading'),
    playersEmpty:     $('playersEmpty'),
    playersError:     $('playersError'),
    standingsTabs:    $('standingsTabs'),
    standingsContent: $('standingsContent'),
    standingsLoading: $('standingsLoading'),
    standingsError:   $('standingsError'),
  };

  const esc = s => { const d = document.createElement('div'); d.textContent = String(s); return d.innerHTML; };

  // ── API ────────────────────────────────────
  async function fetchStandings() {
    const res = await fetch(STANDINGS_URL);
    const data = await res.json();

    standings = []; // flat list with meta
    teamMeta = {};

    (data.children || []).forEach(conf => {
      const conferenceName = (conf.name || '').replace(' Conference', ''); // Eastern / Western
      (conf.children || []).forEach(div => {
        const divisionName = (div.name || '').replace(' Division', ''); // Atlantic / Metropolitan / Central / Pacific
        const entries = (div.standings || {}).entries || [];
        entries.forEach(e => {
          const team = e.team || {};
          const stats = {};
          (e.stats || []).forEach(s => { stats[s.name] = s.value; stats[s.name + '_d'] = s.displayValue; });

          const entry = {
            name: team.displayName || team.name || '?',
            abbr: (team.abbreviation || '').toUpperCase(),
            logo: (team.logos || [{}])[0].href || '',
            conference: conferenceName,
            division: divisionName,
            gp: stats.gamesPlayed || 0,
            w: stats.wins || 0,
            l: stats.losses || 0,
            otl: stats.otLosses || stats.overtimeLosses || 0,
            pts: stats.points || 0,
            gf: stats.pointsFor || 0,
            ga: stats.pointsAgainst || 0,
            diff: stats.pointDifferential || 0,
            streak: stats.streak_d || '',
          };
          standings.push(entry);
          if (entry.abbr) teamMeta[entry.abbr] = {
            name: entry.name,
            logo: entry.logo,
            conference: entry.conference,
            division: entry.division,
          };
        });
      });
    });

    populateTeamDropdown();
  }

  async function fetchAthletesPage(page) {
    const url = `${STATS_URL}?limit=500&page=${page}&season=${SEASON}&seasontype=2`;
    const res = await fetch(url);
    return res.json();
  }

  async function fetchAthletes() {
    // Fetch all 3 pages in parallel (1038 athletes, 500 per page)
    const [p1, p2, p3] = await Promise.all([
      fetchAthletesPage(1),
      fetchAthletesPage(2),
      fetchAthletesPage(3),
    ]);

    // Build category name mappings from page 1 (same across all pages)
    const catNames = {};  // catName -> array of stat names
    ((p1.categories) || []).forEach(c => { catNames[c.name] = c.names || []; });

    skaters = [];
    goalies = [];

    [p1, p2, p3].forEach(page => {
      (page.athletes || []).forEach(a => {
        const person = a.athlete || {};
        const pos = person.position?.abbreviation || '?';
        const teamAbbr = (person.teamShortName || '').toUpperCase();

        // Merge category values into a flat stats object
        const stats = {};
        (a.categories || []).forEach(cat => {
          const names = catNames[cat.name] || [];
          (cat.values || []).forEach((v, i) => {
            if (names[i]) stats[names[i]] = v;
          });
        });

        // PIM comes as its own category "penalties" with one value
        const pen = (a.categories || []).find(c => c.name === 'penalties');
        if (pen) stats.penalties = (pen.values || [])[0] || 0;

        // Goaltending stats are under "defensive" — alias GAA for clarity
        if (stats['goalsAgainstAverage'] != null) stats.goalsAgainstAvg = stats['goalsAgainstAverage'];

        const entry = {
          name: person.displayName || '?',
          pos,
          team: teamAbbr,
          stats,
        };

        if (pos === 'G') goalies.push(entry);
        else skaters.push(entry);
      });
    });
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

  // ── Filter / Sort ──────────────────────────
  function getPlayerList() {
    const isGoalie = state.pos === 'G';
    let list = isGoalie ? [...goalies] : [...skaters];

    // Skater position filter
    if (!isGoalie && state.pos !== 'all') {
      list = list.filter(p => p.pos === state.pos);
    }

    // Conference / division / team filters (via teamMeta)
    list = list.filter(p => {
      const meta = teamMeta[p.team];
      if (!meta) return state.conference === 'all' && state.division === 'all' && state.team === 'all';
      if (state.conference !== 'all' && meta.conference !== state.conference) return false;
      if (state.division !== 'all' && meta.division !== state.division) return false;
      if (state.team !== 'all' && p.team !== state.team) return false;
      return true;
    });

    // Sort
    const validStat = isGoalie
      ? (GOALIE_STATS[state.stat] ? state.stat : 'wins')
      : (SKATER_STATS[state.stat] ? state.stat : 'points');

    list.sort((a, b) => {
      const va = a.stats[validStat] ?? 0;
      const vb = b.stats[validStat] ?? 0;
      // GAA: lower is better
      if (validStat === 'goalsAgainstAvg') return va - vb;
      return vb - va;
    });

    return { list, isGoalie, sortStat: validStat };
  }

  // ── Render Players ─────────────────────────
  function renderPlayers() {
    const { list, isGoalie, sortStat } = getPlayerList();

    const old = els.playersContent.querySelector('.stats-table');
    if (old) old.remove();

    hideStates('players');

    if (list.length === 0) {
      els.playersEmpty.style.display = '';
      return;
    }

    const META = isGoalie ? GOALIE_STATS : SKATER_STATS;
    const COLS = isGoalie ? GOALIE_COLUMNS : SKATER_COLUMNS;

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
      h += `<td class="col-pos">${esc(p.pos)}</td>`;

      COLS.forEach(k => {
        const cls = k === sortStat ? ' active' : '';
        const meta = META[k];
        const val = formatStat(p.stats[k], meta);
        let extra = '';
        if (k === 'plusMinus' && (p.stats.plusMinus || 0) > 0) extra = ' plus';
        if (k === 'plusMinus' && (p.stats.plusMinus || 0) < 0) extra = ' minus';
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

  function formatStat(val, meta) {
    if (val == null) return '-';
    if (meta?.rate) {
      const n = +val;
      if (meta.percent) return (n).toFixed(meta.decimals || 1) + '%';
      return n.toFixed(meta.decimals || 3).replace(/^0/, '');
    }
    if (meta?.decimals) {
      return (+val).toFixed(meta.decimals);
    }
    if (typeof val === 'number' && val > 0 && meta?.label === '+/-') return '+' + val;
    return val;
  }

  // ── Render Standings ───────────────────────
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
    h += '</tr></thead><tbody>';

    teams.forEach((t, i) => {
      const medal = i < 3 ? ['gold', 'silver', 'bronze'][i] : '';
      const diff = t.diff > 0 ? '+' + t.diff : t.diff;
      const diffCls = t.diff > 0 ? ' plus' : t.diff < 0 ? ' minus' : '';
      h += '<tr>';
      h += `<td class="col-rank ${medal}">${i + 1}</td>`;
      h += `<td><div class="col-team-cell">`;
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

  // ── Swap stat dropdown for goalies ─────────
  function updateStatDropdownForPosition() {
    const isGoalie = state.pos === 'G';
    const entries = isGoalie
      ? [['wins','W'],['savePct','SV%'],['goalsAgainstAvg','GAA'],['shutouts','SO'],['saves','SV'],['losses','L'],['otLosses','OTL'],['games','GP']]
      : [['points','PTS'],['goals','G'],['assists','A'],['plusMinus','+/-'],['shotsTotal','SOG'],['powerPlayGoals','PPG'],['gameWinningGoals','GWG'],['penalties','PIM'],['games','GP']];

    els.statSort.innerHTML = '';
    entries.forEach(([v, l]) => {
      const opt = document.createElement('option');
      opt.value = v;
      opt.textContent = l;
      els.statSort.appendChild(opt);
    });

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
      // Fetch standings first so teamMeta is ready
      await fetchStandings();
      await fetchAthletes();
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
