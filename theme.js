/* =========================================
   Theme Toggle (shared across pages)
   Light ↔ dark everywhere. Pages that support the HUD
   look (the car pages mark <html data-hud-ok>) cycle
   light → dark → HUD. HUD is stored separately ("look")
   so every other page just treats it as dark.
   ========================================= */
(function () {
  const toggle = document.getElementById('themeToggle');
  if (!toggle) return;
  const html = document.documentElement;
  const order = html.hasAttribute('data-hud-ok') ? ['light', 'dark', 'hud'] : ['light', 'dark'];
  const NAMES = { light: 'light', dark: 'dark', hud: 'HUD' };

  function label() {
    const cur = html.getAttribute('data-theme');
    const next = order[(order.indexOf(cur) + 1) % order.length];
    toggle.setAttribute('aria-label', 'Theme: ' + NAMES[cur] + '. Switch to ' + NAMES[next]);
  }

  toggle.addEventListener('click', () => {
    const current = html.getAttribute('data-theme');
    const i = order.indexOf(current);
    const next = order[(i + 1) % order.length];
    html.setAttribute('data-theme', next);
    try {
      localStorage.setItem('theme', next === 'light' ? 'light' : 'dark');
      localStorage.setItem('look', next === 'hud' ? 'hud' : '');
    } catch (e) {}
    label();
    document.dispatchEvent(new CustomEvent('themechange', { detail: next }));
  });
  label();

  // Sync with OS preference changes (only when user hasn't manually chosen)
  window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', (e) => {
    if (!localStorage.getItem('theme')) {
      html.setAttribute('data-theme', e.matches ? 'light' : 'dark');
      label();
    }
  });
})();
