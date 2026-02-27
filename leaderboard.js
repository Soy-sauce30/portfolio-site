/* =========================================
   MCTiers Leaderboard
   ========================================= */
const API = 'https://mctiers.com/api/v2';
const HEAD = 'https://crafatar.com/renders/head/';
const PLAYERDB = 'https://playerdb.co/api/player/minecraft/';
const PER_PAGE = 25;

let mode = 'overall';
let page = 0;
let tierFilter = 'all';
let gamemodes = {};

const $ = id => document.getElementById(id);
const rankings = $('rankings');
const loading = $('loading');
const empty = $('emptyState');
const pager = $('pagination');
const prevBtn = $('prevBtn');
const nextBtn = $('nextBtn');
const pageInfo = $('pageInfo');
const tabs = $('gamemodeTabs');
const filter = $('tierFilter');
const search = $('playerSearch');
const pCard = $('playerCard');

function head(uuid, s) { return `${HEAD}${uuid}?size=${s}&overlay`; }

/* =========================================
   Init
   ========================================= */
async function init() {
  try {
    const r = await fetch(`${API}/mode/list`);
    if (!r.ok) throw 0;
    gamemodes = await r.json();

    for (const [slug, m] of Object.entries(gamemodes)) {
      const b = document.createElement('button');
      b.className = 'tab';
      b.dataset.mode = slug;
      b.textContent = m.title;
      tabs.appendChild(b);
    }

    tabs.addEventListener('click', e => {
      const b = e.target.closest('.tab');
      if (!b) return;
      tabs.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
      b.classList.add('active');
      mode = b.dataset.mode;
      page = 0;
      tierFilter = 'all';
      syncFilter();
      load();
    });

    filter.addEventListener('click', e => {
      if (!e.target.classList.contains('filter-btn')) return;
      filter.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      e.target.classList.add('active');
      tierFilter = e.target.dataset.filter;
      page = 0;
      load();
    });

    load();
  } catch (e) {
    err('Could not load gamemodes.');
  }
}

function syncFilter() {
  filter.style.display = mode === 'overall' ? 'none' : 'flex';
  filter.querySelectorAll('.filter-btn').forEach(b =>
    b.classList.toggle('active', b.dataset.filter === tierFilter));
}

/* =========================================
   Load
   ========================================= */
async function load() {
  showLoading();
  clear();
  syncFilter();
  try {
    mode === 'overall' ? await loadOverall() : await loadMode(mode);
  } catch (e) {
    err('Failed to load rankings.');
  }
  hideLoading();
}

/* --- Overall --- */
async function loadOverall() {
  const from = page * PER_PAGE;
  const r = await fetch(`${API}/mode/overall?count=${PER_PAGE}&from=${from}`);
  if (!r.ok) throw 0;
  const data = await r.json();

  if (!data.length) { showEmpty(); hidePager(); return; }

  // Column header
  const hdr = el('div', 'col-header');
  hdr.innerHTML = `
    <span class="ch-num">#</span>
    <span class="ch-head"></span>
    <span class="ch-name">Player</span>
    <span class="ch-region">Region</span>
    <span class="ch-pts">Points</span>`;
  rankings.appendChild(hdr);

  data.forEach((p, i) => {
    const rank = from + i + 1;
    const row = el('div', 'p-row');
    row.innerHTML = `
      <span class="p-num">${rankFmt(rank)}</span>
      <img class="p-head" src="${head(p.uuid, 28)}" alt="" loading="lazy">
      <span class="p-name">${esc(p.name)}</span>
      <span class="p-region">${esc(p.region || '—')}</span>
      <span class="p-pts">${p.points.toLocaleString()}</span>`;
    row.onclick = () => lookup(p.name);
    rankings.appendChild(row);
  });

  showPager(data.length);
}

function rankFmt(n) {
  if (n === 1) return '<span class="gold">1</span>';
  if (n === 2) return '<span class="silver">2</span>';
  if (n === 3) return '<span class="bronze">3</span>';
  return n;
}

/* --- Gamemode --- */
async function loadMode(m) {
  const from = page * PER_PAGE;
  const r = await fetch(`${API}/mode/${m}?count=${PER_PAGE}&from=${from}`);
  if (!r.ok) throw 0;
  const tiers = await r.json();

  let any = false;

  for (let t = 1; t <= 5; t++) {
    const players = tiers[t];
    if (!players || !players.length) continue;

    const hi = players.filter(p => p.pos === 0);
    const lo = players.filter(p => p.pos === 1);

    const groups = [];
    if (tierFilter !== 'low' && hi.length) groups.push({ sub: 'High', players: hi });
    if (tierFilter !== 'high' && lo.length) groups.push({ sub: 'Low', players: lo });

    for (const g of groups) {
      any = true;
      const sec = el('div', 'tier-section');

      // Heading
      const hd = el('div', 'tier-heading');
      hd.innerHTML = `
        <span class="tier-pill tp-${t}">T${t}</span>
        <span class="tier-label">Tier ${t}</span>
        <span class="tier-sub">${g.sub}</span>
        <span class="tier-count">${g.players.length} player${g.players.length !== 1 ? 's' : ''}</span>`;
      sec.appendChild(hd);

      // Rows
      g.players.forEach((p, i) => {
        const row = el('div', 'p-row');
        const hlCls = g.sub === 'High' ? 'high' : 'low';
        row.innerHTML = `
          <span class="p-num">${from + i + 1}</span>
          <img class="p-head" src="${head(p.uuid, 28)}" alt="" loading="lazy">
          <span class="p-name">${esc(p.name)}</span>
          <span class="p-region">${esc(p.region || '—')}</span>
          <span class="p-hl ${hlCls}">${g.sub}</span>`;
        row.onclick = () => lookup(p.name);
        sec.appendChild(row);
      });

      rankings.appendChild(sec);
    }
  }

  if (!any) showEmpty();
  showPager(any ? PER_PAGE : 0);
}

/* =========================================
   Player Search
   ========================================= */
let timer;
search.addEventListener('input', () => {
  clearTimeout(timer);
  const q = search.value.trim();
  if (q.length < 2) { pCard.style.display = 'none'; return; }
  timer = setTimeout(() => lookup(q), 400);
});

async function lookup(name) {
  pCard.style.display = 'none';
  try {
    const r = await fetch(`${PLAYERDB}${encodeURIComponent(name)}`);
    if (!r.ok) throw 0;
    const d = await r.json();
    const uuid = d.data.player.id;
    const uname = d.data.player.username;

    let prof = null, ranks = {};
    try {
      const [a, b] = await Promise.all([
        fetch(`${API}/profile/${uuid}?badges`),
        fetch(`${API}/profile/${uuid}/rankings`)
      ]);
      if (a.ok) prof = await a.json();
      if (b.ok) ranks = await b.json();
    } catch (e) {}

    let rHtml = '';
    for (const [m, d] of Object.entries(ranks)) {
      const nm = gamemodes[m] ? gamemodes[m].title : m;
      const h = d.pos === 0;
      rHtml += `<span class="pc-rank ${h ? 'high' : 'low'}">${esc(nm)}: T${d.tier} ${h ? 'High' : 'Low'}</span>`;
    }

    const pts = prof ? prof.points : null;
    const ov = prof ? prof.overall : null;

    pCard.innerHTML = `
      <img class="pc-head" src="${head(uuid, 56)}" alt="${esc(uname)}">
      <div class="pc-info">
        <h3>${esc(uname)}</h3>
        <p>${pts !== null ? pts.toLocaleString() + ' points' : 'No MCTiers data'}${ov ? ' &middot; #' + ov + ' overall' : ''}</p>
        ${rHtml ? '<div class="pc-ranks">' + rHtml + '</div>' : ''}
      </div>
      <button class="pc-close" onclick="this.parentElement.style.display='none'">&times;</button>`;
    pCard.style.display = 'flex';
  } catch (e) {
    pCard.innerHTML = `
      <div class="pc-info">
        <h3>Not found</h3>
        <p>"${esc(name)}" — check spelling</p>
      </div>
      <button class="pc-close" onclick="this.parentElement.style.display='none'">&times;</button>`;
    pCard.style.display = 'flex';
  }
}

/* =========================================
   Pagination
   ========================================= */
prevBtn.onclick = () => { if (page > 0) { page--; load(); } };
nextBtn.onclick = () => { page++; load(); };

function showPager(n) {
  pager.style.display = 'flex';
  prevBtn.disabled = page === 0;
  nextBtn.disabled = n < PER_PAGE;
  pageInfo.textContent = `Page ${page + 1}`;
}
function hidePager() { pager.style.display = 'none'; }

/* =========================================
   Helpers
   ========================================= */
function el(tag, cls) { const e = document.createElement(tag); e.className = cls; return e; }

function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function clear() {
  for (const c of [...rankings.children]) {
    if (c !== loading && c !== empty) c.remove();
  }
}

function showLoading() { loading.style.display = 'block'; }
function hideLoading() { loading.style.display = 'none'; }
function showEmpty() { empty.style.display = 'block'; }
function hideEmpty() { empty.style.display = 'none'; }
function err(msg) { hideLoading(); empty.textContent = msg; showEmpty(); }

// Go
init();