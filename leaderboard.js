/* =========================================
   MCTiers Leaderboard
   ========================================= */
const API = 'https://mctiers.com/api/v2';
const HEAD_URL = 'https://crafatar.com/renders/head/';
const PLAYERDB = 'https://playerdb.co/api/player/minecraft/';
const PER_PAGE = 25;

let currentMode = 'overall';
let currentPage = 0;
let currentTierFilter = 'all';
let gamemodes = {};

const content = document.getElementById('lbContent');
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

function playerHead(uuid, size) {
  return `${HEAD_URL}${uuid}?size=${size}&overlay`;
}

/* =========================================
   Init
   ========================================= */
async function init() {
  try {
    const res = await fetch(`${API}/mode/list`);
    if (!res.ok) throw new Error();
    gamemodes = await res.json();

    Object.entries(gamemodes).forEach(([slug, mode]) => {
      const btn = document.createElement('button');
      btn.className = 'lb-mode';
      btn.dataset.mode = slug;
      btn.innerHTML = `<span class="lb-mode-label">${escHtml(mode.title)}</span>`;
      tabs.appendChild(btn);
    });

    tabs.addEventListener('click', (e) => {
      const btn = e.target.closest('.lb-mode');
      if (!btn) return;
      tabs.querySelectorAll('.lb-mode').forEach(t => t.classList.remove('active'));
      btn.classList.add('active');
      currentMode = btn.dataset.mode;
      currentPage = 0;
      currentTierFilter = 'all';
      updateTierFilterUI();
      loadLeaderboard();
    });

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
   Tier Filter
   ========================================= */
function updateTierFilterUI() {
  if (currentMode === 'overall') {
    tierFilter.style.display = 'none';
  } else {
    tierFilter.style.display = 'flex';
    tierFilter.querySelectorAll('.lb-tier-btn').forEach(b => {
      b.classList.toggle('active', b.dataset.filter === currentTierFilter);
    });
  }
}

/* =========================================
   Load Leaderboard
   ========================================= */
async function loadLeaderboard() {
  showLoading();
  hideEmpty();
  clearContent();
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

/* =========================================
   Overall Rankings
   ========================================= */
async function loadOverall() {
  const from = currentPage * PER_PAGE;
  const res = await fetch(`${API}/mode/overall?count=${PER_PAGE}&from=${from}`);
  if (!res.ok) throw new Error();
  const players = await res.json();

  if (players.length === 0) {
    showEmpty();
    hidePagination();
    return;
  }

  const card = document.createElement('div');
  card.className = 'overall-card';

  // Header
  const header = document.createElement('div');
  header.className = 'overall-header';
  header.innerHTML = '<span>#</span><span>Player</span><span>Region</span><span>Points</span>';
  card.appendChild(header);

  // Rows
  players.forEach((p, i) => {
    const rank = from + i + 1;
    const row = document.createElement('div');
    row.className = 'overall-row';
    row.innerHTML = `
      <div class="overall-rank">${overallRankBadge(rank)}</div>
      <div class="overall-player">
        <img src="${playerHead(p.uuid, 32)}" alt="${escHtml(p.name)}" loading="lazy">
        <span>${escHtml(p.name)}</span>
      </div>
      <div class="overall-region"><span>${escHtml(p.region || '—')}</span></div>
      <div class="overall-points">${p.points.toLocaleString()}</div>
    `;
    row.addEventListener('click', () => searchPlayer(p.name));
    card.appendChild(row);
  });

  content.appendChild(card);
  showPagination(players.length);
}

function overallRankBadge(rank) {
  if (rank === 1) return '<span class="rank-gold">1</span>';
  if (rank === 2) return '<span class="rank-silver">2</span>';
  if (rank === 3) return '<span class="rank-bronze">3</span>';
  return `<span class="rank-default">${rank}</span>`;
}

/* =========================================
   Gamemode Rankings (tier cards)
   ========================================= */
async function loadGamemode(mode) {
  const from = currentPage * PER_PAGE;
  const res = await fetch(`${API}/mode/${mode}?count=${PER_PAGE}&from=${from}`);
  if (!res.ok) throw new Error();
  const tiers = await res.json();

  let hasAny = false;

  for (let tier = 1; tier <= 5; tier++) {
    const players = tiers[tier];
    if (!players || players.length === 0) continue;

    const highPlayers = players.filter(p => p.pos === 0);
    const lowPlayers = players.filter(p => p.pos === 1);

    // Build sections based on filter
    const sections = [];
    if (currentTierFilter === 'all' || currentTierFilter === 'high') {
      if (highPlayers.length > 0) sections.push({ sub: 'High', players: highPlayers });
    }
    if (currentTierFilter === 'all' || currentTierFilter === 'low') {
      if (lowPlayers.length > 0) sections.push({ sub: 'Low', players: lowPlayers });
    }

    for (const section of sections) {
      hasAny = true;
      const card = buildTierCard(tier, section.sub, section.players, from);
      content.appendChild(card);
    }
  }

  if (!hasAny) showEmpty();
  showPagination(hasAny ? PER_PAGE : 0);
}

function buildTierCard(tier, sub, players, from) {
  const card = document.createElement('div');
  card.className = 'tier-card';

  // Header
  const header = document.createElement('div');
  header.className = 'tier-card-header';
  header.innerHTML = `
    <span class="tier-badge tier-${tier}">T${tier}</span>
    <span class="tier-card-title">Tier ${tier} — ${sub}</span>
    <span class="tier-card-subtitle">${players.length} player${players.length !== 1 ? 's' : ''}</span>
  `;
  card.appendChild(header);

  // Player rows
  const list = document.createElement('div');
  list.className = 'tier-players';

  players.forEach((p, i) => {
    const row = document.createElement('div');
    row.className = 'player-row';
    const hlClass = sub === 'High' ? 'hl-high' : 'hl-low';
    row.innerHTML = `
      <span class="player-rank">${from + i + 1}</span>
      <img class="player-head" src="${playerHead(p.uuid, 32)}" alt="${escHtml(p.name)}" loading="lazy">
      <span class="player-name">${escHtml(p.name)}</span>
      <span class="player-region">${escHtml(p.region || '—')}</span>
      <span class="player-hl ${hlClass}">${sub}</span>
    `;
    row.addEventListener('click', () => searchPlayer(p.name));
    list.appendChild(row);
  });

  card.appendChild(list);
  return card;
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
    const dbRes = await fetch(`${PLAYERDB}${encodeURIComponent(name)}`);
    if (!dbRes.ok) throw new Error('Player not found');
    const dbData = await dbRes.json();
    const uuid = dbData.data.player.id;
    const username = dbData.data.player.username;

    let rankings = {};
    let profile = null;
    try {
      const [profRes, rankRes] = await Promise.all([
        fetch(`${API}/profile/${uuid}?badges`),
        fetch(`${API}/profile/${uuid}/rankings`)
      ]);
      if (profRes.ok) profile = await profRes.json();
      if (rankRes.ok) rankings = await rankRes.json();
    } catch (e) { /* MCTiers data optional */ }

    const points = profile ? profile.points : null;
    const overallRank = profile ? profile.overall : null;

    let rankingsHtml = '';
    Object.entries(rankings).forEach(([mode, data]) => {
      const modeName = gamemodes[mode] ? gamemodes[mode].title : mode;
      const isHigh = data.pos === 0;
      const cls = isHigh ? 'ht' : 'lt';
      const label = isHigh ? 'High' : 'Low';
      rankingsHtml += `<span class="lb-player-rank ${cls}">${escHtml(modeName)}: T${data.tier} ${label}</span>`;
    });

    playerCard.innerHTML = `
      <img src="${playerHead(uuid, 64)}" alt="${escHtml(username)}">
      <div class="lb-player-info">
        <h3>${escHtml(username)}</h3>
        <p>${points !== null ? `${points.toLocaleString()} points` : 'No MCTiers data'}${overallRank ? ` &middot; #${overallRank} overall` : ''}</p>
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
  if (currentPage > 0) { currentPage--; loadLeaderboard(); }
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

function hidePagination() { pagination.style.display = 'none'; }

/* =========================================
   Helpers
   ========================================= */
function clearContent() {
  // Keep loading/empty elements, remove everything else
  Array.from(content.children).forEach(el => {
    if (el !== loading && el !== emptyState) el.remove();
  });
}

function escHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

function showLoading() { loading.style.display = 'block'; }
function hideLoading() { loading.style.display = 'none'; }
function showEmpty() { emptyState.style.display = 'block'; }
function hideEmpty() { emptyState.style.display = 'none'; }
function showError(msg) {
  hideLoading();
  emptyState.textContent = msg;
  showEmpty();
}

// Go
init();