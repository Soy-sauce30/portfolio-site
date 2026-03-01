(() => {
  const API = 'https://statsapi.mlb.com/api/v1';

  // Division ID → name
  const DIV_MAP = {
    201: 'AL East',  202: 'AL Central', 200: 'AL West',
    204: 'NL East',  205: 'NL Central', 203: 'NL West',
  };

  // Stat metadata
  const STAT_META = {
    avg:         { label: 'AVG', rate: true },
    homeRuns:    { label: 'HR',  rate: false },
    rbi:         { label: 'RBI', rate: false },
    ops:         { label: 'OPS', rate: true },
    hits:        { label: 'H',   rate: false },
    runs:        { label: 'R',   rate: false },
    stolenBases: { label: 'SB',  rate: false },
    obp:         { label: 'OBP', rate: true },
    slg:         { label: 'SLG', rate: true },
    doubles:     { label: '2B',  rate: false },
    baseOnBalls: { label: 'BB',  rate: false },
  };

  // Columns shown in the table
  const COLUMNS = ['avg', 'homeRuns', 'rbi', 'hits', 'runs', 'stolenBases', 'ops'];

  // ── State ──────────────────────────────────
  let teams = {};
  let playerPositions = {};
  let allPlayers = [];
  let state = { pos: 'all', division: 'all', team: 'all', stat: 'homeRuns', season: 2025 };

  // ── DOM ────────────────────────────────────
  const $ = id => document.getElementById(id);
  const els = {
    posTabs:    $('posTabs'),
    divFilter:  $('divFilter'),
    teamFilter: $('teamFilter'),
    statSort:   $('statSort'),
    seasonSel:  $('seasonSel'),
    content:    $('content'),
    loading:    $('loading'),
    empty:      $('empty'),
    error:      $('error'),
  };

  // ── API ────────────────────────────────────
  async function fetchTeams() {
    try {
      const res = await fetch(`${API}/teams?sportId=1`);
      const data = await res.json();
      teams = {};
      data.teams.forEach(t => {
        if (t.sport?.id === 1 && t.division) {
          teams[t.id] = {
            name: t.name,
            abbr: t.abbreviation,
            division: DIV_MAP[t.division.id] || '',
          };
        }
      });
      rebuildTeamDropdown();
    } catch (e) {
      console.error('Failed to fetch teams:', e);
    }
  }

  async function fetchPlayers(season) {
    try {
      const res = await fetch(`${API}/sports/1/players?season=${season}`);
      const data = await res.json();
      playerPositions = {};
      if (data.people) {
        data.people.forEach(p => {
          playerPositions[p.id] = p.primaryPosition?.abbreviation || '?';
        });
      }
    } catch (e) {
      console.warn('Player position lookup unavailable:', e);
    }
  }

  async function fetchStats(season) {
    show('loading');

    const url =
      `${API}/stats?stats=season&sportId=1&group=hitting&season=${season}` +
      `&gameType=R&playerPool=ALL&limit=800&sortStat=plateAppearances&order=desc`;

    try {
      const res = await fetch(url);
      const data = await res.json();

      if (!data.stats?.[0]?.splits?.length) {
        allPlayers = [];
        show('empty');
        return;
      }

      // Build player map, deduplicate by keeping entry with most PA
      const map = {};
      data.stats[0].splits.forEach(s => {
        const pid = s.player?.id;
        const pa  = s.stat?.plateAppearances || 0;
        if (!pid) return;
        if (map[pid] && (map[pid].stats.plateAppearances || 0) >= pa) return;

        const tid = s.team?.id;
        const pos = s.position?.abbreviation
                 || s.player?.primaryPosition?.abbreviation
                 || playerPositions[pid]
                 || '?';

        map[pid] = {
          id: pid,
          name: s.player.fullName || '?',
          pos,
          teamId: tid,
          team: teams[tid]?.abbr || '?',
          teamName: teams[tid]?.name || s.team?.name || '?',
          division: teams[tid]?.division || '?',
          stats: s.stat || {},
        };
      });

      allPlayers = Object.values(map);
      show(null);
      render();
    } catch (e) {
      console.error('Failed to fetch stats:', e);
      allPlayers = [];
      show('error');
    }
  }

  function show(which) {
    els.loading.style.display = which === 'loading' ? '' : 'none';
    els.empty.style.display   = which === 'empty'   ? '' : 'none';
    els.error.style.display   = which === 'error'   ? '' : 'none';
  }

  // ── Filtering & Sorting ────────────────────
  function getFiltered() {
    let list = [...allPlayers];

    // Position
    if (state.pos !== 'all') {
      list = list.filter(p => {
        if (p.pos === state.pos) return true;
        // Include generic OF in outfield position filters
        if (['LF', 'CF', 'RF'].includes(state.pos) && p.pos === 'OF') return true;
        return false;
      });
    }

    // Division
    if (state.division !== 'all') {
      list = list.filter(p => p.division === state.division);
    }

    // Team
    if (state.team !== 'all') {
      list = list.filter(p => String(p.teamId) === String(state.team));
    }

    // Sort
    const key = state.stat;
    list.sort((a, b) => {
      let va = a.stats[key];
      let vb = b.stats[key];
      if (typeof va === 'string') va = parseFloat(va) || 0;
      if (typeof vb === 'string') vb = parseFloat(vb) || 0;
      if (va == null) va = 0;
      if (vb == null) vb = 0;
      return vb - va;
    });

    return list;
  }

  // ── Render ─────────────────────────────────
  function formatStat(key, val) {
    if (val == null || val === '') return '-';
    if (STAT_META[key]?.rate) {
      const n = typeof val === 'string' ? parseFloat(val) : val;
      if (isNaN(n)) return val;
      return n.toFixed(3).replace(/^0/, '');
    }
    return val;
  }

  function esc(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
  }

  function render() {
    const players = getFiltered();

    // Remove old table
    const old = els.content.querySelector('.stats-table');
    if (old) old.remove();

    if (players.length === 0) {
      els.empty.style.display = '';
      return;
    }
    els.empty.style.display = 'none';

    let h = '<table class="stats-table"><thead><tr>';
    h += '<th class="col-rank">#</th>';
    h += '<th class="col-name">Player</th>';
    h += '<th class="col-team">Team</th>';
    h += '<th class="col-pos">Pos</th>';

    COLUMNS.forEach(k => {
      const cls = k === state.stat ? ' active' : '';
      h += `<th class="col-stat${cls}" data-stat="${k}">${STAT_META[k].label}</th>`;
    });

    h += '</tr></thead><tbody>';

    players.forEach((p, i) => {
      const medal = i < 3 ? ['gold', 'silver', 'bronze'][i] : '';
      h += '<tr>';
      h += `<td class="col-rank ${medal}">${i + 1}</td>`;
      h += `<td class="col-name">${esc(p.name)}</td>`;
      h += `<td class="col-team">${esc(p.team)}</td>`;
      h += `<td class="col-pos">${esc(p.pos)}</td>`;

      COLUMNS.forEach(k => {
        const cls = k === state.stat ? ' active' : '';
        h += `<td class="col-stat${cls}">${formatStat(k, p.stats[k])}</td>`;
      });

      h += '</tr>';
    });

    h += '</tbody></table>';
    els.content.insertAdjacentHTML('beforeend', h);

    // Clickable column headers to change sort
    els.content.querySelectorAll('th[data-stat]').forEach(th => {
      th.addEventListener('click', () => {
        state.stat = th.dataset.stat;
        els.statSort.value = state.stat;
        render();
      });
    });
  }

  // ── Team dropdown ──────────────────────────
  function rebuildTeamDropdown() {
    const sel = els.teamFilter;
    sel.innerHTML = '<option value="all">All Teams</option>';

    Object.entries(teams)
      .filter(([, t]) => {
        if (!t.division) return false;
        if (state.division !== 'all' && t.division !== state.division) return false;
        return true;
      })
      .sort((a, b) => a[1].name.localeCompare(b[1].name))
      .forEach(([id, t]) => {
        const opt = document.createElement('option');
        opt.value = id;
        opt.textContent = t.name;
        sel.appendChild(opt);
      });
  }

  // ── Events ─────────────────────────────────
  els.posTabs.addEventListener('click', e => {
    const tab = e.target.closest('.pos-tab');
    if (!tab) return;
    els.posTabs.querySelectorAll('.pos-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    state.pos = tab.dataset.pos;
    render();
  });

  els.divFilter.addEventListener('change', () => {
    state.division = els.divFilter.value;
    state.team = 'all';
    rebuildTeamDropdown();
    render();
  });

  els.teamFilter.addEventListener('change', () => {
    state.team = els.teamFilter.value;
    render();
  });

  els.statSort.addEventListener('change', () => {
    state.stat = els.statSort.value;
    render();
  });

  els.seasonSel.addEventListener('change', async () => {
    state.season = parseInt(els.seasonSel.value);
    await fetchPlayers(state.season);
    await fetchStats(state.season);
  });

  // ── Init ───────────────────────────────────
  async function init() {
    await fetchTeams();
    await fetchPlayers(state.season);
    await fetchStats(state.season);
  }

  init();
})();