(() => {
  const ESPN = 'https://site.api.espn.com/apis/v2/sports/soccer';

  const LEAGUES = {
    'eng.1': { name: 'Premier League', zones: { ucl: 4, uel: 5, uecl: 6, rel: -3 } },
    'esp.1': { name: 'La Liga',        zones: { ucl: 4, uel: 5, uecl: 6, rel: -3 } },
    'ger.1': { name: 'Bundesliga',     zones: { ucl: 4, uel: 5, uecl: 6, rel: -2 } },
    'ita.1': { name: 'Serie A',        zones: { ucl: 4, uel: 5, uecl: 6, rel: -3 } },
    'fra.1': { name: 'Ligue 1',       zones: { ucl: 3, uel: 4, uecl: 5, rel: -3 } },
    'usa.1': { name: 'MLS',           zones: {} },
  };

  const STAT_LABELS = {
    rank: '#', gamesPlayed: 'GP', wins: 'W', ties: 'D', losses: 'L',
    pointsFor: 'GF', pointsAgainst: 'GA', pointDifferential: 'GD', points: 'Pts',
  };

  const COLUMNS = ['gamesPlayed', 'wins', 'ties', 'losses', 'pointsFor', 'pointsAgainst', 'pointDifferential', 'points'];

  // State
  let currentLeague = 'eng.1';
  let currentSort = 'rank';
  let entries = [];

  // DOM
  const $ = id => document.getElementById(id);
  const els = {
    tabs:     $('leagueTabs'),
    statSort: $('statSort'),
    content:  $('content'),
    loading:  $('loading'),
    empty:    $('empty'),
    error:    $('error'),
  };

  function show(which) {
    els.loading.style.display = which === 'loading' ? '' : 'none';
    els.empty.style.display   = which === 'empty'   ? '' : 'none';
    els.error.style.display   = which === 'error'   ? '' : 'none';
  }

  // ── API ────────────────────────────────────
  async function fetchStandings(league) {
    show('loading');

    try {
      const res = await fetch(`${ESPN}/${league}/standings`);
      const data = await res.json();

      // ESPN can return multiple groups (conferences for MLS)
      const groups = data.children || [];
      entries = [];

      groups.forEach(group => {
        const standings = group.standings || {};
        const groupEntries = standings.entries || [];

        groupEntries.forEach(e => {
          const team = e.team || {};
          const stats = {};
          (e.stats || []).forEach(s => { stats[s.name] = s.value; });

          entries.push({
            name: team.displayName || '?',
            abbr: team.abbreviation || '?',
            logo: (team.logos || [{}])[0].href || '',
            rank: stats.rank || 0,
            gamesPlayed: stats.gamesPlayed || 0,
            wins: stats.wins || 0,
            ties: stats.ties || 0,
            losses: stats.losses || 0,
            pointsFor: stats.pointsFor || 0,
            pointsAgainst: stats.pointsAgainst || 0,
            pointDifferential: stats.pointDifferential || 0,
            points: stats.points || 0,
          });
        });
      });

      show(null);
      render();
    } catch (e) {
      console.error('Failed to fetch standings:', e);
      entries = [];
      show('error');
    }
  }

  // ── Sorting ────────────────────────────────
  function getSorted() {
    const list = [...entries];
    const key = currentSort;

    if (key === 'rank') {
      list.sort((a, b) => a.rank - b.rank);
    } else {
      list.sort((a, b) => b[key] - a[key]);
    }

    return list;
  }

  // ── Zone color (Champions League, relegation, etc.) ──
  function getZone(rank, total) {
    const z = LEAGUES[currentLeague]?.zones || {};
    if (z.ucl && rank <= z.ucl) return 'zone-ucl';
    if (z.uel && rank <= z.uel) return 'zone-uel';
    if (z.uecl && rank <= z.uecl) return 'zone-uecl';
    if (z.rel && rank > total + z.rel) return 'zone-rel';
    return '';
  }

  // ── Render ─────────────────────────────────
  function esc(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
  }

  function render() {
    const sorted = getSorted();

    // Remove old table
    const old = els.content.querySelector('.standings-table');
    if (old) old.remove();

    if (sorted.length === 0) {
      els.empty.style.display = '';
      return;
    }
    els.empty.style.display = 'none';

    const total = sorted.length;
    let h = '<table class="standings-table"><thead><tr>';
    h += '<th class="col-rank active">#</th>';
    h += '<th></th>'; // zone
    h += '<th>Club</th>';

    COLUMNS.forEach(k => {
      const cls = k === currentSort ? ' active' : '';
      h += `<th class="col-stat${cls}" data-stat="${k}">${STAT_LABELS[k]}</th>`;
    });

    h += '</tr></thead><tbody>';

    sorted.forEach((t, i) => {
      const displayRank = currentSort === 'rank' ? t.rank : i + 1;
      const medal = displayRank <= 3 ? ['gold', 'silver', 'bronze'][displayRank - 1] : '';
      const zone = getZone(t.rank, total);

      h += '<tr>';
      h += `<td class="col-rank ${medal}">${displayRank}</td>`;
      h += `<td class="col-zone ${zone}"></td>`;
      h += `<td class="col-team">`;
      if (t.logo) h += `<img class="team-logo" src="${esc(t.logo)}" alt="" loading="lazy">`;
      h += `${esc(t.name)}</td>`;

      COLUMNS.forEach(k => {
        const cls = k === currentSort ? ' active' : '';
        const isPts = k === 'points' ? ' pts' : '';
        const val = k === 'pointDifferential' && t[k] > 0 ? '+' + t[k] : t[k];
        h += `<td class="col-stat${cls}${isPts}">${val}</td>`;
      });

      h += '</tr>';
    });

    h += '</tbody></table>';
    els.content.insertAdjacentHTML('beforeend', h);

    // Clickable headers
    els.content.querySelectorAll('th[data-stat]').forEach(th => {
      th.addEventListener('click', () => {
        currentSort = th.dataset.stat;
        els.statSort.value = currentSort;
        render();
      });
    });
  }

  // ── Events ─────────────────────────────────
  els.tabs.addEventListener('click', e => {
    const tab = e.target.closest('.league-tab');
    if (!tab) return;
    els.tabs.querySelectorAll('.league-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    currentLeague = tab.dataset.league;
    currentSort = 'rank';
    els.statSort.value = 'rank';
    fetchStandings(currentLeague);
  });

  els.statSort.addEventListener('change', () => {
    currentSort = els.statSort.value;
    render();
  });

  // ── Init ───────────────────────────────────
  fetchStandings(currentLeague);
})();
