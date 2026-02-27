/* =========================================
   MCTiers Leaderboard
   ========================================= */
const API = 'https://mctiers.com/api/v2';
const CRAFATAR = 'https://crafatar.com/avatars/';
const PLAYERDB = 'https://playerdb.co/api/player/minecraft/';
const PER_PAGE = 25;

let currentMode = 'overall';
let currentPage = 0;
let currentTierFilter = 'all'; // 'all', 'high' (1-2), 'low' (3-5)
let gamemodes = {};

const body = document.getElementById('leaderboardBody');
const loading = document.getElementById('loading');
const emptyState = document.getElementById('emptyState');
const pagination = document.getElementById('pagination');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
const pageInfo = document.getElementById('pageInfo');
const tabs = document.getElementById('gamemodeTabs');
const tierFilter = document.getElementById('tierFilter');
const searchInput = document.getElementById('playerSearch');
const playerCard = document.getElementById('playerCard');
const table = document.getElementById('leaderboardTable');

const HIGH_TIERS = [1, 2];
const LOW_TIERS = [3, 4, 5];

/* =========================================
   Init — Load gamemodes then leaderboard
   ========================================= */
async function init() {
  try {
    const res = await fetch(`${API}/mode/list`);
    if (!res.ok) throw new Error('Failed to fetch gamemodes');
    gamemodes = await res.json();

    // Build gamemode tabs
    Object.entries(gamemodes).forEach(([slug, mode]) => {
      const btn = document.createElement('button');
      btn.className = 'lb-tab';
      btn.dataset.mode = slug;
      btn.textContent = mode.title;
      tabs.appendChild(btn);
    });

    // Gamemode tab clicks
    tabs.addEventListener('click', (e) => {
      if (!e.target.classList.contains('lb-tab')) return;
      tabs.querySelectorAll('.lb-tab').forEach(t => t.classList.remove('active'));
      e.target.classList.add('active');
      currentMode = e.target.dataset.mode;
      currentPage = 0;
      currentTierFilter = 'all';
      updateTierFilterUI();
      loadLeaderboard();
    });

    // Tier sub-filter clicks
    tierFilter.addEventListener('click', (e) => {
      if (!e.target.classList.contains('lb-tier-btn')) return;
      tierFilter.querySelectorAll('.lb-tier-btn').forEach(b => b.classList.remove('active'));
      e.target.classList.add('active');
      currentTierFilter = e.target.dataset.filter;
      currentPage = 0;
      loadLeaderboard();
    });

    loadLeaderboard();
  } catch (err) {
    showError('Could not load gamemodes. Try refreshing.');
  }
}

/* =========================================
   Tier Filter Visibility
   ========================================= */
function updateTierFilterUI() {
  if (currentMode === 'overall') {
    tierFilter.style.display = 'none';
  } else {
    tierFilter.style.display = 'flex';
    // Reset active button
    tierFilter.querySelectorAll('.lb-tier-btn').forEach(b => {
      b.classList.toggle('active', b.dataset.filter === currentTierFilter);
    });
  }
}

function getTiersForFilter() {
  if (currentTierFilter === 'high') return HIGH_TIERS;
  if (currentTierFilter === 'low') return LOW_TIERS;
  return [1, 2, 3, 4, 5];
}

/* =========================================
   Load Leaderboard Data
   ========================================= */
async function loadLeaderboard() {
  showLoading();
  hideEmpty();
  body.innerHTML = '';
  updateTierFilterUI();

  try {
    if (currentMode === 'overall') {
      await loadOverall();
    } else {
      await loadGamemode(currentMode);
    }
  } catch (err) {
    showError('Failed to load leaderboard data.');
  }

  hideLoading();
}

async function loadOverall() {
  const from = currentPage * PER_PAGE;
  const res = await fetch(`${API}/mode/overall?count=${PER_PAGE}&from=${from}`);
  if (!res.ok) throw new Error();
  const players = await res.json();

  updateHeaders(['#', 'Player', 'Region', 'Points']);

  if (players.length === 0) {
    showEmpty();
    hidePagination();
    return;
  }

  players.forEach((p, i) => {
    const rank = from + i + 1;
    const row = document.createElement('tr');
    row.innerHTML = `
      <td class="col-rank">${rankBadge(rank)}</td>
      <td class="col-player">
        <div class="player-cell">
          <img src="${CRAFATAR}${p.uuid}?size=28&overlay" alt="" loading="lazy">
          <span>${escHtml(p.name)}</span>
        </div>
      </td>
      <td class="col-region">${escHtml(p.region || '—')}</td>
      <td class="col-score">${p.points.toLocaleString()}</td>
    `;
    row.style.cursor = 'pointer';
    row.addEventListener('click', () => searchPlayer(p.name));
    body.appendChild(row);
  });

  showPagination(players.length);
}

async function loadGamemode(mode) {
  const from = currentPage * PER_PAGE;
  const res = await fetch(`${API}/mode/${mode}?count=${PER_PAGE}&from=${from}`);
  if (!res.ok) throw new Error();
  const tiers = await res.json();

  updateHeaders(['#', 'Player', 'Region', 'Tier']);

  const visibleTiers = getTiersForFilter();
  let hasAny = false;

  for (const tier of visibleTiers) {
    const players = tiers[tier];
    if (!players || players.length === 0) continue;
    hasAny = true;

    // Tier separator
    const tierLabel = HIGH_TIERS.includes(tier) ? 'High' : 'Low';
    const sep = document.createElement('tr');
    sep.innerHTML = `<td colspan="4" class="tier-header"><span>Tier ${tier} — ${tierLabel}</span></td>`;
    body.appendChild(sep);

    players.forEach((p, i) => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td class="col-rank">${from + i + 1}</td>
        <td class="col-player">
          <div class="player-cell">
            <img src="${CRAFATAR}${p.uuid}?size=28&overlay" alt="" loading="lazy">
            <span>${escHtml(p.name)}</span>
          </div>
        </td>
        <td class="col-region">${escHtml(p.region || '—')}</td>
        <td class="col-score">Tier ${tier}</td>
      `;
      row.style.cursor = 'pointer';
      row.addEventListener('click', () => searchPlayer(p.name));
      body.appendChild(row);
    });
  }

  if (!hasAny) showEmpty();
  showPagination(hasAny ? PER_PAGE : 0);
}

/* =========================================
   Player Search
   ========================================= */
let searchTimeout;
searchInput.addEventListener('input', () => {
  clearTimeout(searchTimeout);
  const query = searchInput.value.trim();
  if (query.length < 2) {
    playerCard.style.display = 'none';
    return;
  }
  searchTimeout = setTimeout(() => searchPlayer(query), 400);
});

async function searchPlayer(name) {
  playerCard.style.display = 'none';

  try {
    // Get UUID from PlayerDB
    const dbRes = await fetch(`${PLAYERDB}${encodeURIComponent(name)}`);
    if (!dbRes.ok) throw new Error('Player not found');
    const dbData = await dbRes.json();
    const uuid = dbData.data.player.id;
    const username = dbData.data.player.username;

    // Get MCTiers profile + rankings
    let rankings = {};
    let profile = null;
    try {
      const [profRes, rankRes] = await Promise.all([
        fetch(`${API}/profile/${uuid}?badges`),
        fetch(`${API}/profile/${uuid}/rankings`)
      ]);
      if (profRes.ok) profile = await profRes.json();
      if (rankRes.ok) rankings = await rankRes.json();
    } catch (e) {
      // MCTiers data optional
    }

    const points = profile ? profile.points : null;
    const overallRank = profile ? profile.overall : null;

    let rankingsHtml = '';
    Object.entries(rankings).forEach(([mode, data]) => {
      const modeName = gamemodes[mode] ? gamemodes[mode].title : mode;
      const tierLabel = HIGH_TIERS.includes(data.tier) ? 'HT' : 'LT';
      rankingsHtml += `<span class="lb-player-rank">${escHtml(modeName)}: Tier ${data.tier} (${tierLabel})</span>`;
    });

    playerCard.innerHTML = `
      <img src="${CRAFATAR}${uuid}?size=64&overlay" alt="${escHtml(username)}">
      <div class="lb-player-info">
        <h3>${escHtml(username)}</h3>
        <p>${points !== null ? `${points.toLocaleString()} points` : 'No MCTiers data'}${overallRank ? ` · #${overallRank} overall` : ''}</p>
        ${rankingsHtml ? `<div class="lb-player-rankings">${rankingsHtml}</div>` : ''}
      </div>
      <button class="close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
    `;
    playerCard.style.display = 'flex';
  } catch (err) {
    playerCard.innerHTML = `
      <div class="lb-player-info">
        <h3>Player not found</h3>
        <p>Could not find "${escHtml(name)}". Check the spelling.</p>
      </div>
      <button class="close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
    `;
    playerCard.style.display = 'flex';
  }
}

/* =========================================
   Pagination
   ========================================= */
prevBtn.addEventListener('click', () => {
  if (currentPage > 0) {
    currentPage--;
    loadLeaderboard();
  }
});

nextBtn.addEventListener('click', () => {
  currentPage++;
  loadLeaderboard();
});

function showPagination(count) {
  pagination.style.display = 'flex';
  prevBtn.disabled = currentPage === 0;
  nextBtn.disabled = count < PER_PAGE;
  pageInfo.textContent = `Page ${currentPage + 1}`;
}

function hidePagination() {
  pagination.style.display = 'none';
}

/* =========================================
   Helpers
   ========================================= */
function updateHeaders(labels) {
  const ths = table.querySelectorAll('thead th');
  labels.forEach((label, i) => {
    if (ths[i]) ths[i].textContent = label;
  });
}

function rankBadge(rank) {
  if (rank <= 3) {
    return `<span class="rank-badge rank-${rank}">${rank}</span>`;
  }
  return rank;
}

function escHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

function showLoading() { loading.style.display = 'block'; table.style.display = 'none'; }
function hideLoading() { loading.style.display = 'none'; table.style.display = 'table'; }
function showEmpty() { emptyState.style.display = 'block'; }
function hideEmpty() { emptyState.style.display = 'none'; }
function showError(msg) {
  hideLoading();
  emptyState.textContent = msg;
  showEmpty();
}

// Go
init();