/**
 * EZ Accessibility — Frontend JS
 * Namespace: EZAccessibility
 * No dependencies. Vanilla JS only.
 *
 * The widget UI is rendered inside a Shadow DOM so theme CSS can't style it.
 * Page adjustments are applied as classes on <html> (see eza-frontend.css).
 */
(function () {
  'use strict';

  // ── Guard ──────────────────────────────────────────────────────────────────
  if (typeof EZAccessibilityConfig === 'undefined' || window.EZAccessibility) return;

  const CFG  = EZAccessibilityConfig;
  const HTML = document.documentElement;

  // ── Storage helpers (validated, never trusts raw values) ──────────────────
  const LS = {
    PREFIX: 'ez_accessibility_',
    KEYS: [
      'fontStep', 'highContrast', 'invert', 'grayscale',
      'highlightLinks', 'readableFont', 'letterStep',
      'pauseAnim', 'largeCursor'
    ],
    set: function (key, val) {
      try { localStorage.setItem(this.PREFIX + key, JSON.stringify(val)); } catch (e) {}
    },
    get: function (key, fallback) {
      try {
        const raw = localStorage.getItem(this.PREFIX + key);
        return raw === null ? fallback : JSON.parse(raw);
      } catch (e) { return fallback; }
    },
    clearAll: function () {
      this.KEYS.forEach(function (k) {
        try { localStorage.removeItem(LS.PREFIX + k); } catch (e) {}
      });
    }
  };

  function safeInt(val, min, max, fallback) {
    const n = parseInt(val, 10);
    return (isNaN(n) || n < min || n > max) ? fallback : n;
  }
  function safeBool(val, fallback) {
    return (val === true || val === false) ? val : fallback;
  }

  // ── Language ──────────────────────────────────────────────────────────────
  // Built-in labels come from eza-i18n.js; CFG.strings holds the site owner's
  // overrides and custom languages (CFG.custom maps code → direction).
  const I18N      = window.EZAccessibilityI18n || { KEYS: [], langs: {}, candidates: function () { return []; } };
  const OVERRIDES = CFG.strings || {};
  const CUSTOM    = CFG.custom || {};

  // Own-property lookups only, so a page lang like "constructor" or
  // "__proto__" can never match something inherited from Object.prototype.
  function own(obj, key) {
    return obj && Object.prototype.hasOwnProperty.call(obj, key) ? obj[key] : undefined;
  }

  function hasLang(code) {
    return !!(own(I18N.langs, code) || own(CUSTOM, code));
  }

  // Most specific match for the page's lang attribute (e.g. pt-BR → pt-br → pt),
  // then the site language, then the configured fallback, then English.
  function detectLang() {
    const tags = [HTML.getAttribute('lang'), CFG.siteLang];
    for (let i = 0; i < tags.length; i++) {
      const c = I18N.candidates(tags[i]).filter(hasLang);
      if (c.length) return c[0];
      if (tags[i]) break; // page declared a language we don't have: use the fallback
    }
    return hasLang(CFG.fallback) ? CFG.fallback : 'en';
  }

  function isRTL() {
    const dir = (HTML.getAttribute('dir') || '').toLowerCase();
    if (dir === 'rtl' || dir === 'ltr') return dir === 'rtl';
    const lang = detectLang();
    const builtin = own(I18N.langs, lang);
    return (own(CUSTOM, lang) || (builtin && builtin.d)) === 'rtl';
  }

  function label(lang, key) {
    const custom = own(own(OVERRIDES, lang), key);
    if (typeof custom === 'string' && custom) return custom;
    const builtin = own(I18N.langs, lang);
    const idx = I18N.KEYS.indexOf(key);
    return (builtin && builtin.s[idx]) || '';
  }

  function t(key) {
    return label(detectLang(), key) || label(CFG.fallback, key) || label('en', key) || key;
  }

  // ── Icons ─────────────────────────────────────────────────────────────────
  const ICONS = {
    logo:     '<circle cx="12" cy="4.6" r="1.9" fill="currentColor" stroke="none"/><path d="M4.5 8.4c2.5.8 5 1.1 7.5 1.1s5-.3 7.5-1.1"/><path d="M12 9.6v4.6"/><path d="M12 14.2 9 20.5"/><path d="m12 14.2 3 6.3"/>',
    personCircle: '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="7.3" r="1.4" fill="currentColor" stroke="none"/><path d="M7.5 10.2c1.5.4 3 .6 4.5.6s3-.2 4.5-.6"/><path d="M12 10.8v3"/><path d="m12 13.8-2 3.7"/><path d="m12 13.8 2 3.7"/>',
    eye:      '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
    adjust:   '<path d="M4 6h9M17 6h3M4 12h3M11 12h9M4 18h11M19 18h1"/><circle cx="15" cy="6" r="2"/><circle cx="9" cy="12" r="2"/><circle cx="17" cy="18" r="2"/>',
    close:    '<path d="M18 6 6 18M6 6l12 12"/>',
    minus:    '<path d="M5 12h14"/>',
    plus:     '<path d="M12 5v14M5 12h14"/>',
    check:    '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
    reset:    '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>',
    textSize: '<path d="m3 19 5-14 5 14"/><path d="M5 14h6"/><path d="m14 19 3.5-9 3.5 9"/><path d="M15.2 16h4.6"/>',
    contrast: '<circle cx="12" cy="12" r="9"/><path d="M12 3a9 9 0 0 1 0 18z" fill="currentColor"/>',
    invert:   '<rect x="3" y="3" width="18" height="18" rx="4"/><path d="M21 7v10a4 4 0 0 1-4 4H7z" fill="currentColor"/>',
    drop:     '<path d="M12 2.8c3.6 4.3 6 7.6 6 10.7a6 6 0 0 1-12 0c0-3.1 2.4-6.4 6-10.7z"/><path d="M12 19.5a6 6 0 0 0 6-6c0-.6-.1-1.2-.3-1.8H6.3c-.2.6-.3 1.2-.3 1.8a6 6 0 0 0 6 6z" fill="currentColor"/>',
    link:     '<path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/>',
    type:     '<path d="M4 7V5h16v2"/><path d="M12 5v14"/><path d="M9 19h6"/>',
    spacing:  '<path d="m8 14 4-10 4 10"/><path d="M9.6 10.5h4.8"/><path d="M3 19h18"/><path d="m6 16.5-3 2.5 3 2.5"/><path d="m18 16.5 3 2.5-3 2.5"/>',
    pause:    '<circle cx="12" cy="12" r="9"/><path d="M10 9v6M14 9v6"/>',
    cursor:   '<path d="M5 3.5 19 11l-6.2 1.7L9.6 19z" fill="currentColor" fill-opacity=".15"/>'
  };
  // Button icon choice (from the admin) → ICONS key.
  const BUTTON_ICONS = { person: 'logo', 'person-circle': 'personCircle', eye: 'eye', text: 'textSize', adjust: 'adjust' };

  // The site's chosen icon as a DOM node. A custom image is only used when
  // it is a plain http(s) URL, and it is set as a property, never as HTML.
  function buttonIcon() {
    const url = typeof CFG.iconUrl === 'string' ? CFG.iconUrl : '';
    if (/^https?:\/\//i.test(url)) {
      const img = document.createElement('img');
      img.alt = '';
      img.decoding = 'async';
      img.src = url;
      return img;
    }
    const holder = document.createElement('span');
    holder.innerHTML = svg(BUTTON_ICONS[CFG.icon] || 'logo', 2.2); // static icon markup
    return holder.firstChild;
  }

  function svg(name, width) {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' + (width || 2) +
      '" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' + ICONS[name] + '</svg>';
  }

  // ── State ─────────────────────────────────────────────────────────────────
  const FONT_MIN = -2, FONT_MAX = 5;   // steps of 2px on the natural base size
  const LETTER_MAX = 3;

  const state = {
    fontStep:       safeInt(LS.get('fontStep', 0), FONT_MIN, FONT_MAX, 0),
    highContrast:   safeBool(LS.get('highContrast', false), false),
    invert:         safeBool(LS.get('invert', false), false),
    grayscale:      safeBool(LS.get('grayscale', false), false),
    highlightLinks: safeBool(LS.get('highlightLinks', false), false),
    readableFont:   safeBool(LS.get('readableFont', false), false),
    letterStep:     safeInt(LS.get('letterStep', 0), 0, LETTER_MAX, 0),
    pauseAnim:      safeBool(LS.get('pauseAnim', false), false),
    largeCursor:    safeBool(LS.get('largeCursor', false), false)
  };
  const DEFAULTS = {
    fontStep: 0, highContrast: false, invert: false, grayscale: false,
    highlightLinks: false, readableFont: false, letterStep: 0,
    pauseAnim: false, largeCursor: false
  };

  // Toggle features → <html> class
  const TOGGLES = {
    highContrast:   { cls: 'eza-high-contrast',   icon: 'contrast', label: 'high_contrast',   group: 'visual'  },
    invert:         { cls: 'eza-invert',          icon: 'invert',   label: 'invert',          group: 'visual'  },
    grayscale:      { cls: 'eza-grayscale',       icon: 'drop',     label: 'grayscale',       group: 'visual'  },
    highlightLinks: { cls: 'eza-highlight-links', icon: 'link',     label: 'highlight_links', group: 'reading' },
    readableFont:   { cls: null,                  icon: 'type',     label: 'readable_font',   group: 'reading' },
    pauseAnim:      { cls: 'eza-pause-anim',      icon: 'pause',    label: 'pause_anim',      group: 'motion'  },
    largeCursor:    { cls: 'eza-large-cursor',    icon: 'cursor',   label: 'large_cursor',    group: 'motion'  }
  };

  function enabled(feature) {
    return !!(CFG.features && CFG.features[feature]);
  }

  function activeCount() {
    let n = 0;
    Object.keys(DEFAULTS).forEach(function (k) {
      if (state[k] !== DEFAULTS[k] && enabled(k === 'fontStep' ? 'fontSize' : k === 'letterStep' ? 'letterSpacing' : k)) n++;
    });
    return n;
  }

  // ── Font size (scales page-builder inline sizes too) ──────────────────────
  // Natural font size of <html> before any changes (captured at init time).
  let naturalFontSize = 16;

  function captureNaturalFontSize() {
    const computed = parseFloat(getComputedStyle(HTML).fontSize);
    if (!isNaN(computed) && computed > 0) naturalFontSize = computed;
  }

  function fontScale() {
    return (naturalFontSize + state.fontStep * 2) / naturalFontSize;
  }

  // Text-bearing tags we capture computed font-sizes for.
  const FONT_TAGS = new Set([
    'H1', 'H2', 'H3', 'H4', 'H5', 'H6',
    'P', 'SPAN', 'A', 'STRONG', 'EM', 'B', 'I', 'U', 'S',
    'LI', 'TD', 'TH', 'CAPTION', 'DT', 'DD',
    'BUTTON', 'LABEL', 'INPUT', 'TEXTAREA', 'SELECT',
    'BLOCKQUOTE', 'CITE', 'Q', 'MARK', 'SMALL', 'SUB', 'SUP',
    'FIGCAPTION', 'LEGEND', 'SUMMARY', 'DETAILS'
  ]);

  // Record each text element's original computed font-size.
  // Must run BEFORE html.style.fontSize changes so values are natural.
  function captureFontOriginals() {
    const all = document.body ? document.body.getElementsByTagName('*') : [];
    for (let i = 0; i < all.length; i++) {
      const el = all[i];
      if (!FONT_TAGS.has(el.tagName) || el.hasAttribute('data-eza-fspx')) continue;
      const px = parseFloat(getComputedStyle(el).fontSize);
      if (isNaN(px) || px <= 0) continue;
      el.setAttribute('data-eza-fs', el.style.fontSize || '');
      el.setAttribute('data-eza-fspx', String(px));
    }
  }

  function applyFontSize() {
    if (state.fontStep === 0) {
      document.querySelectorAll('[data-eza-fspx]').forEach(function (el) {
        const orig = el.getAttribute('data-eza-fs');
        if (orig === '') el.style.removeProperty('font-size');
        else el.style.fontSize = orig;
        el.removeAttribute('data-eza-fs');
        el.removeAttribute('data-eza-fspx');
      });
      HTML.style.removeProperty('font-size');
      return;
    }

    captureFontOriginals();
    const scale = fontScale();
    HTML.style.fontSize = (naturalFontSize * scale) + 'px'; // rem-based designs
    document.querySelectorAll('[data-eza-fspx]').forEach(function (el) {
      const origPx = parseFloat(el.getAttribute('data-eza-fspx'));
      if (!isNaN(origPx)) el.style.fontSize = (origPx * scale) + 'px';
    });
  }

  function applyReadableFont() {
    HTML.classList.toggle('eza-readable-font', state.readableFont && enabled('readableFont'));
  }

  function applyLetterSpacing() {
    HTML.classList.remove('eza-letter-1', 'eza-letter-2', 'eza-letter-3');
    if (state.letterStep > 0 && enabled('letterSpacing')) HTML.classList.add('eza-letter-' + state.letterStep);
  }

  function applyAllStates() {
    if (enabled('fontSize') || state.fontStep === 0) applyFontSize();
    Object.keys(TOGGLES).forEach(function (k) {
      if (TOGGLES[k].cls) HTML.classList.toggle(TOGGLES[k].cls, state[k] && enabled(k));
    });
    applyReadableFont();
    applyLetterSpacing();
  }

  // ── Widget DOM ────────────────────────────────────────────────────────────
  let host, shadow, ui, trigger, badge, panel, body, resetBtn, live;

  function el(tag, cls, html) {
    const node = document.createElement(tag);
    if (cls) node.className = cls;
    if (html) node.innerHTML = html;
    return node;
  }

  function buildShell() {
    host = document.createElement('div');
    host.id = 'eza-root';
    host.style.visibility = 'hidden'; // until our stylesheet loads
    shadow = host.attachShadow({ mode: 'open' });

    const link = document.createElement('link');
    link.rel  = 'stylesheet';
    link.href = CFG.cssUrl;
    const reveal = function () { host.style.removeProperty('visibility'); };
    link.addEventListener('load', reveal);
    link.addEventListener('error', reveal);
    setTimeout(reveal, 3000);
    shadow.appendChild(link);

    ui = el('div', 'ui');

    trigger = el('button', 'trigger');
    trigger.appendChild(buttonIcon());
    trigger.type = 'button';
    trigger.setAttribute('aria-expanded', 'false');
    trigger.setAttribute('aria-controls', 'eza-panel');
    badge = el('span', 'badge');
    badge.setAttribute('aria-hidden', 'true');
    badge.hidden = true;
    trigger.appendChild(badge);

    panel = el('div', 'panel');
    panel.id = 'eza-panel';
    panel.hidden = true;
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-modal', 'false');
    panel.setAttribute('aria-labelledby', 'eza-title');

    live = el('div', 'sr-only');
    live.setAttribute('aria-live', 'polite');

    ui.appendChild(panel);
    ui.appendChild(trigger);
    ui.appendChild(live);
    shadow.appendChild(ui);
    document.body.appendChild(host);

    applyHostStyles();
  }

  // Position, colors, size — re-run when language direction changes.
  function applyHostStyles() {
    const sizes = { small: 48, medium: 56, large: 64 };
    const off   = safeInt(CFG.offset, 0, 80, 20);
    let pos     = /^(top|bottom)-(left|right)$/.test(CFG.position) ? CFG.position : 'bottom-right';

    // RTL pages mirror the horizontal side.
    if (isRTL()) {
      pos = pos.replace(/left|right/, function (s) { return s === 'left' ? 'right' : 'left'; });
    }
    const v = pos.split('-');

    host.style.zIndex = String(safeInt(CFG.zIndex, 1, 2147483647, 99999));
    host.style.top = host.style.bottom = host.style.left = host.style.right = 'auto';
    host.style[v[0]] = off + 'px';
    host.style[v[1]] = off + 'px';

    ui.dataset.pos = pos;
    ui.setAttribute('dir', isRTL() ? 'rtl' : 'ltr');
    ui.setAttribute('lang', detectLang());
    ui.style.setProperty('--accent',    /^#[0-9a-f]{3,8}$/i.test(CFG.btnBg) ? CFG.btnBg : '#2346d3');
    ui.style.setProperty('--on-accent', /^#[0-9a-f]{3,8}$/i.test(CFG.btnIcon) ? CFG.btnIcon : '#ffffff');
    ui.style.setProperty('--btn', (sizes[CFG.btnSize] || 56) + 'px');
    ui.style.setProperty('--off', off + 'px');

    trigger.setAttribute('aria-label', t('open_menu'));
  }

  function updateBadge() {
    const n = activeCount();
    badge.textContent = String(n);
    badge.hidden = n === 0;
    if (resetBtn) resetBtn.disabled = n === 0;
  }

  function announce(msg) {
    live.textContent = '';
    setTimeout(function () { live.textContent = msg; }, 30);
  }

  // ── Panel content ─────────────────────────────────────────────────────────
  function buildPanel() {
    panel.innerHTML = '';

    const head = el('div', 'head');
    const mark = el('span', 'head-mark');
    mark.appendChild(buttonIcon());
    head.appendChild(mark);
    const title = el('h2', 'title');
    title.id = 'eza-title';
    title.textContent = t('panel_title');
    head.appendChild(title);
    const closeBtn = el('button', 'close', svg('close'));
    closeBtn.type = 'button';
    closeBtn.setAttribute('aria-label', t('close'));
    closeBtn.addEventListener('click', function () { closePanel(true); });
    head.appendChild(closeBtn);

    body = el('div', 'body');

    const groups = {
      visual:  { label: t('group_visual'),  items: [] },
      reading: { label: t('group_reading'), items: [] },
      motion:  { label: t('group_motion'),  items: [] }
    };

    if (enabled('fontSize')) {
      groups.visual.items.push(makeStepper({
        key: 'fontStep', icon: 'textSize', label: t('font_size'),
        min: FONT_MIN, max: FONT_MAX,
        format: function () { return Math.round(fontScale() * 100) + '%'; },
        apply: applyFontSize
      }));
    }
    if (enabled('letterSpacing')) {
      groups.reading.items.push(makeStepper({
        key: 'letterStep', icon: 'spacing', label: t('letter_spacing'),
        min: 0, max: LETTER_MAX,
        format: function () { return ['0', '+5%', '+10%', '+16%'][state.letterStep]; },
        apply: applyLetterSpacing
      }));
    }

    Object.keys(TOGGLES).forEach(function (k) {
      if (enabled(k)) groups[TOGGLES[k].group].items.push(makeTile(k));
    });

    Object.keys(groups).forEach(function (g) {
      const items = groups[g].items;
      if (!items.length) return;
      const section = el('section', 'group');
      const h = el('h3', 'group-title');
      h.textContent = groups[g].label;
      section.appendChild(h);
      // Steppers sit full-width; tiles flow in a 2-column grid.
      let grid = null;
      items.forEach(function (item) {
        if (item.classList.contains('tile')) {
          if (!grid) { grid = el('div', 'tiles'); section.appendChild(grid); }
          grid.appendChild(item);
        } else {
          grid = null;
          section.appendChild(item);
        }
      });
      body.appendChild(section);
    });

    const foot = el('div', 'foot');
    resetBtn = el('button', 'reset', svg('reset'));
    resetBtn.type = 'button';
    resetBtn.appendChild(document.createTextNode(t('reset')));
    resetBtn.addEventListener('click', function () {
      resetAll();
      announce(t('reset'));
    });
    foot.appendChild(resetBtn);

    panel.appendChild(head);
    panel.appendChild(body);
    panel.appendChild(foot);
    updateBadge();
  }

  function makeTile(key) {
    const def = TOGGLES[key];
    const btn = el('button', 'tile');
    btn.type = 'button';
    btn.setAttribute('aria-pressed', state[key] ? 'true' : 'false');
    btn.innerHTML =
      '<span class="tile-icon">' + svg(def.icon, 1.9) + '</span>' +
      '<span class="tile-check">' + svg('check', 3) + '</span>';
    const label = el('span', 'tile-label');
    label.textContent = t(def.label);
    btn.appendChild(label);

    btn.addEventListener('click', function () {
      state[key] = !state[key];
      LS.set(key, state[key]);
      btn.setAttribute('aria-pressed', state[key] ? 'true' : 'false');
      if (key === 'readableFont') applyReadableFont();
      else HTML.classList.toggle(def.cls, state[key]);
      updateBadge();
    });
    return btn;
  }

  function makeStepper(o) {
    const wrap = el('div', 'stepper');
    const head = el('div', 'stepper-head');
    head.appendChild(el('span', 'tile-icon', svg(o.icon, 1.9)));
    const label = el('span', 'tile-label');
    label.textContent = o.label;
    label.id = 'eza-lbl-' + o.key;
    head.appendChild(label);
    const val = el('span', 'stepper-val');
    val.setAttribute('aria-hidden', 'true');
    head.appendChild(val);

    const ctrl  = el('div', 'stepper-ctrl');
    const dec   = el('button', 'step', svg('minus', 2.4));
    const inc   = el('button', 'step', svg('plus', 2.4));
    const track = el('div', 'track');
    dec.type = inc.type = 'button';
    dec.setAttribute('aria-label', t('decrease') + ' — ' + o.label);
    inc.setAttribute('aria-label', t('increase') + ' — ' + o.label);
    track.setAttribute('aria-hidden', 'true');

    const steps = o.max - o.min;
    for (let i = 0; i < steps; i++) track.appendChild(el('span'));
    const segs = track.children;

    function render() {
      const v = state[o.key];
      val.textContent = o.format();
      dec.disabled = v <= o.min;
      inc.disabled = v >= o.max;
      wrap.classList.toggle('is-active', v !== 0);
      // Fill segments between the default (0) and the current value.
      for (let i = 0; i < segs.length; i++) {
        const lo = o.min + i, hi = lo + 1;
        segs[i].className = ((v > 0 && lo >= 0 && hi <= v) || (v < 0 && hi <= 0 && lo >= v) ? 'on' : '') +
          (o.min < 0 && lo === -1 ? ' mid' : '');
      }
    }
    function change(delta) {
      const next = state[o.key] + delta;
      if (next < o.min || next > o.max) return;
      state[o.key] = next;
      LS.set(o.key, next);
      o.apply();
      render();
      updateBadge();
      announce(o.label + ': ' + o.format());
    }
    dec.addEventListener('click', function () { change(-1); });
    inc.addEventListener('click', function () { change(1); });
    render();

    ctrl.appendChild(dec);
    ctrl.appendChild(track);
    ctrl.appendChild(inc);
    wrap.appendChild(head);
    wrap.appendChild(ctrl);
    return wrap;
  }

  // ── Reset ─────────────────────────────────────────────────────────────────
  function resetAll() {
    Object.keys(DEFAULTS).forEach(function (k) { state[k] = DEFAULTS[k]; });
    LS.clearAll();
    applyAllStates();
    if (isPanelOpen()) {
      buildPanel();
      resetBtn.focus({ preventScroll: true });
    } else {
      updateBadge();
    }
  }

  // ── Open / close ──────────────────────────────────────────────────────────
  let closeTimer = null;

  function isPanelOpen() {
    return !!panel && panel.dataset.state === 'open';
  }

  function openPanel() {
    if (!panel || isPanelOpen()) return;
    clearTimeout(closeTimer);
    buildPanel(); // re-render in case language changed
    panel.hidden = false;
    void panel.offsetWidth; // commit start state so the transition runs
    panel.dataset.state = 'open';
    trigger.setAttribute('aria-expanded', 'true');
    requestAnimationFrame(function () {
      const first = panel.querySelector('.body button:not([disabled])') || panel.querySelector('.close');
      if (first) first.focus({ preventScroll: true });
    });
  }

  function closePanel(restoreFocus) {
    if (!isPanelOpen()) return;
    panel.dataset.state = 'closed';
    trigger.setAttribute('aria-expanded', 'false');
    closeTimer = setTimeout(function () { panel.hidden = true; }, 300);
    if (restoreFocus) trigger.focus({ preventScroll: true });
  }

  function togglePanel() {
    isPanelOpen() ? closePanel(true) : openPanel();
  }

  // ── Events ────────────────────────────────────────────────────────────────
  function setupEvents() {
    trigger.addEventListener('click', togglePanel);

    // Close on outside click
    document.addEventListener('click', function (e) {
      if (isPanelOpen() && e.composedPath().indexOf(host) === -1) closePanel(false);
    });

    shadow.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && isPanelOpen()) {
        e.stopPropagation();
        closePanel(true);
        return;
      }
      // Keep Tab inside the panel while it is open.
      if (e.key !== 'Tab' || !isPanelOpen()) return;
      const focusable = Array.prototype.slice.call(panel.querySelectorAll('button:not([disabled])'));
      if (!focusable.length) return;
      const first = focusable[0];
      const last  = focusable[focusable.length - 1];
      const active = shadow.activeElement;
      if (e.shiftKey && (active === first || active === trigger)) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && (active === last || active === trigger)) {
        e.preventDefault();
        first.focus();
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && isPanelOpen()) closePanel(true);
    });
  }

  // ── Init ──────────────────────────────────────────────────────────────────
  function init() {
    captureNaturalFontSize(); // before applyAllStates modifies font size
    buildShell();
    applyAllStates();
    updateBadge();
    setupEvents();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // ── Public API ────────────────────────────────────────────────────────────
  window.EZAccessibility = {
    open:   openPanel,
    close:  function () { closePanel(false); },
    toggle: togglePanel,
    reset:  resetAll,
    // Call after a multilingual plugin switches the page language, to
    // re-apply direction, labels and language-dependent features.
    refresh: function () {
      if (!host) return;
      applyHostStyles();
      applyAllStates();
      if (isPanelOpen()) buildPanel();
      updateBadge();
    }
  };
}());
