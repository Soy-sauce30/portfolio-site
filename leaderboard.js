/* =========================================
   MCTiers Leaderboard — 5-Column Layout
   ========================================= */
const API = 'https://mctiers.com/api/v2';
const BODY = 'https://crafatar.com/renders/body/';
const HEAD = 'https://crafatar.com/renders/head/';
const PLAYERDB = 'https://playerdb.co/api/player/minecraft/';
const PER_PAGE = 50;

// Trophy/icon per tier
const TIER_ICONS = {
  1: '\u{1F3C6}',  // gold trophy
  2: '\u{1F3C6}',  // trophy (silver style via CSS)
  3: '\u{1F3C6}',  // trophy (bronze style via CSS)
  4: '',
  5: ''
};

let mode = 'overall';
let pg = 0;
let tierFilter = 'all';
let gamemodes = {};

const $ = id => document.getElementById(id);
const content = $('rankingsContent');
const loading = $('loading');
const empty = $('empty');
const pager = $('pager');
const prevBtn = $('prevBtn');
const nextBtn = $('nextBtn');
const pageInfo = $('pageInfo');
const tabs = $('modeTabs');
const filter = $('tierFilter');
const search = $('playerSearch');
const lCard = $('lookupCard');

function skinHead(uuid) { return `${HEAD}${uuid}?size=28&overlay`; }
function skinBody(uuid) { return `${BODY}${uuid}?size=52&overlay`; }

/* =========================================
   Init
   ========================================= */
async function init() {
  try {
    const r = await fetch(`${API}/mode/list`);
    if (!r.ok) throw 0;
    gamemodes = await r.json();

    // Mode icon mapping (approximate)
    const icons = {
      vanilla: '\u2694\uFE0F', uhc: '\u2764\uFE0F', pot: '\u{1F9EA}',
      nethop: '\u{1F7E2}', smp: '\u{1F30D}', sword: '\u{1F5E1}\uFE0F',
      axe: '\u{1FA93}', mace: '\u{1F528}', ltm: '\u2728'
    };

    for (const [slug, m] of Object.entries(gamemodes)) {
      const b = document.createElement('button');
      b.className = 'mode-tab';
      b.dataset.mode = slug;
      b.innerHTML = `<span class="mode-icon">${icons[slug] || '\u{1F3AE}'}</span><span class="mode-name">${esc(m.title)}</span>`;
      tabs.appendChild(b);
    }

    tabs.addEventListener('click', e => {
      const b = e.target.closest('.mode-tab');
      if (!b) return;
      tabs.querySelectorAll('.mode-tab').forEach(t => t.classList.remove('active'));
      b.classList.add('active');
      mode = b.dataset.mode;
      pg = 0;
      tierFilter = 'all';
      syncFilter();
      load();
    });

    filter.addEventListener('click', e => {
      if (!e.target.classList.contains('fbtn')) return;
      filter.querySelectorAll('.fbtn').forEach(b => b.classList.remove('active'));
      e.target.classList.add('active');
      tierFilter = e.target.dataset.filter;
      pg = 0;
      load();
    });

    load();
  } catch (e) {
    showErr('Could not load gamemodes.');
  }
}

function syncFilter() {
  filter.style.display = mode === 'overall' ? 'none' : 'flex';
  filter.querySelectorAll('.fbtn').forEach(b =>
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
    showErr('Failed to load rankings.');
  }
  hideLoading();
}

/* --- Overall --- */
async function loadOverall() {
  const from = pg * PER_PAGE;
  const r = await fetch(`${API}/mode/overall?count=${PER_PAGE}&from=${from}`);
  if (!r.ok) throw 0;
  const data = await r.json();
  if (!data.length) { showEmpty(); hidePager(); return; }

  const list = el('div', 'overall-list');

  const hdr = el('div', 'ov-hdr');
  hdr.innerHTML = '<span class="oh-rank">#</span><span class="oh-skin"></span><span class="oh-name">Player</span><span class="oh-region">Region</span><span class="oh-pts">Points</span>';
  list.appendChild(hdr);

  data.forEach((p, i) => {
    const rank = from + i + 1;
    const row = el('div', 'ov-row');
    let rcls = '';
    if (rank === 1) rcls = 'gold';
    else if (rank === 2) rcls = 'silver';
    else if (rank === 3) rcls = 'bronze';

    row.innerHTML = `
      <span class="ov-rank ${rcls}">${rank}</span>
      <img class="ov-skin" src="${skinHead(p.uuid)}" alt="" loading="lazy">
      <span class="ov-name">${esc(p.name)}</span>
      <span class="ov-region">${esc(p.region || '—')}</span>
      <span class="ov-pts">${p.points.toLocaleString()}</span>`;
    row.onclick = () => lookup(p.name);
    list.appendChild(row);
  });

  content.appendChild(list);
  showPager(data.length);
}

/* --- Gamemode: 5-column grid --- */
async function loadMode(m) {
  const from = pg * PER_PAGE;
  const r = await fetch(`${API}/mode/${m}?count=${PER_PAGE}&from=${from}`);
  if (!r.ok) throw 0;
  const tiers = await r.json();

  const grid = el('div', 'tier-grid');
  let any = false;

  for (let t = 1; t <= 5; t++) {
    const col = el('div', 'tier-col');

    // Header
    const hdr = el('div', `tier-hdr t${t}`);
    const icon = TIER_ICONS[t];
    hdr.innerHTML = `${icon ? '<span class="tier-hdr-icon">' + icon + '</span> ' : ''}<span>Tier ${t}</span>`;
    col.appendChild(hdr);

    // Rows container
    const rows = el('div', 'tier-rows');
    const players = tiers[t] || [];

    const hi = players.filter(p => p.pos === 0);
    const lo = players.filter(p => p.pos === 1);

    const showHi = tierFilter !== 'low' && hi.length > 0;
    const showLo = tierFilter !== 'high' && lo.length > 0;

    if (showHi) {
      any = true;
      if (showLo || tierFilter === 'all') {
        const label = el('div', 'tier-sub-label');
        label.textContent = 'HIGH';
        rows.appendChild(label);
      }
      hi.forEach(p => rows.appendChild(makeRow(p, 'high')));
    }

    if (showLo) {
      any = true;
      if (showHi || tierFilter === 'all') {
        const label = el('div', 'tier-sub-label');
        label.textContent = 'LOW';
        rows.appendChild(label);
      }
      lo.forEach(p => rows.appendChild(makeRow(p, 'low')));
    }

    if (!showHi && !showLo) {
      const empty = el('div', 'state');
      empty.textContent = '—';
      empty.style.padding = '1.5rem';
      rows.appendChild(empty);
    }

    col.appendChild(rows);
    grid.appendChild(col);
  }

  content.appendChild(grid);
  if (!any) showEmpty();
  showPager(any ? PER_PAGE : 0);
}

function makeRow(p, hl) {
  const row = el('div', 'tr-row');
  row.innerHTML = `
    <img class="tr-head" src="${skinHead(p.uuid)}" alt="" loading="lazy">
    <span class="tr-name">${esc(p.name)}</span>
    <span class="tr-hl ${hl}">${hl === 'high' ? '\u25B2' : '\u25BC'}</span>`;
  row.onclick = () => lookup(p.name);
  return row;
}

/* =========================================
   Player Lookup
   ========================================= */
let timer;
search.addEventListener('input', () => {
  clearTimeout(timer);
  const q = search.value.trim();
  if (q.length < 2) { lCard.style.display = 'none'; return; }
  timer = setTimeout(() => lookup(q), 400);
});

async function lookup(name) {
  lCard.style.display = 'none';
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
      rHtml += `<span class="lc-rank ${h ? 'high' : 'low'}">${esc(nm)}: T${d.tier} ${h ? 'High' : 'Low'}</span>`;
    }

    const pts = prof ? prof.points : null;
    const ov = prof ? prof.overall : null;

    lCard.innerHTML = `
      <img class="lc-head" src="${skinHead(uuid)}" alt="">
      <div class="lc-info">
        <h3>${esc(uname)}</h3>
        <p>${pts !== null ? pts.toLocaleString() + ' pts' : 'No MCTiers data'}${ov ? ' &middot; #' + ov : ''}</p>
        ${rHtml ? '<div class="lc-ranks">' + rHtml + '</div>' : ''}
      </div>
      <button class="lc-close" onclick="this.parentElement.style.display='none'">&times;</button>`;
    lCard.style.display = 'flex';
  } catch (e) {
    lCard.innerHTML = `
      <div class="lc-info"><h3>Not found</h3><p>"${esc(name)}"</p></div>
      <button class="lc-close" onclick="this.parentElement.style.display='none'">&times;</button>`;
    lCard.style.display = 'flex';
  }
}

/* =========================================
   Pagination
   ========================================= */
prevBtn.onclick = () => { if (pg > 0) { pg--; load(); } };
nextBtn.onclick = () => { pg++; load(); };

function showPager(n) {
  pager.style.display = 'flex';
  prevBtn.disabled = pg === 0;
  nextBtn.disabled = n < PER_PAGE;
  pageInfo.textContent = `Page ${pg + 1}`;
}
function hidePager() { pager.style.display = 'none'; }

/* =========================================
   Helpers
   ========================================= */
function el(tag, cls) {
  const e = document.createElement(tag);
  e.className = cls;
  return e;
}

function esc(s) {
  const d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}

function clear() {
  [...content.children].forEach(c => {
    if (c !== loading && c !== empty) c.remove();
  });
}

function showLoading() { loading.style.display = 'block'; }
function hideLoading() { loading.style.display = 'none'; }
function showEmpty() { empty.style.display = 'block'; }
function hideEmpty() { empty.style.display = 'none'; }
function showErr(msg) { hideLoading(); empty.textContent = msg; showEmpty(); }

init();