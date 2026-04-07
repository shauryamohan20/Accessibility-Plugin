/**
 * Qudra AccessKit WP — Frontend JS
 * Namespace: AccessKitWP
 * No dependencies. Vanilla JS only.
 */
(function () {
  'use strict';

  // ── Guard ──────────────────────────────────────────────────────────────────
  if (typeof QAKConfig === 'undefined') return;

  const CFG = QAKConfig;

  // ── Storage helpers (validated, never trusts raw values) ──────────────────
  const LS = {
    PREFIX: 'qak_',
    set: function (key, val) {
      try { localStorage.setItem(this.PREFIX + key, JSON.stringify(val)); } catch (e) {}
    },
    get: function (key, fallback) {
      try {
        const raw = localStorage.getItem(this.PREFIX + key);
        if (raw === null) return fallback;
        return JSON.parse(raw);
      } catch (e) { return fallback; }
    },
    remove: function (key) {
      try { localStorage.removeItem(this.PREFIX + key); } catch (e) {}
    },
    clearAll: function () {
      const keys = [
        'fontStep', 'highContrast', 'invert', 'grayscale',
        'highlightLinks', 'readableFont', 'letterStep',
        'pauseAnim', 'largeCursor'
      ];
      keys.forEach(k => this.remove(k));
    }
  };

  // Safe int read
  function safeInt(val, min, max, fallback) {
    const n = parseInt(val, 10);
    if (isNaN(n) || n < min || n > max) return fallback;
    return n;
  }

  // Safe bool read
  function safeBool(val, fallback) {
    if (val === true || val === false) return val;
    return fallback;
  }

  // ── Detect current language ────────────────────────────────────────────────
  function detectLang() {
    const htmlEl = document.documentElement;
    const dir    = (htmlEl.getAttribute('dir') || '').toLowerCase();
    const lang   = (htmlEl.getAttribute('lang') || 'en').toLowerCase();

    if (dir === 'rtl' || lang.startsWith('ar')) return 'ar';
    if (lang.startsWith('he'))                  return 'he';
    return 'en';
  }

  function isRTL() {
    const l = detectLang();
    return l === 'ar' || l === 'he';
  }

  // Get translated string, fallback to 'en'
  function t(key) {
    const lang    = detectLang();
    const strings = CFG.strings || {};
    return (strings[lang] && strings[lang][key])
      ? strings[lang][key]
      : (strings['en'] && strings['en'][key]) || key;
  }

  // ── HTML element ──────────────────────────────────────────────────────────
  const HTML = document.documentElement;

  // Natural font size of <html> before any qak changes (captured at init time).
  // Used as the base for relative font-size adjustments.
  let naturalFontSize = 16;

  function captureNaturalFontSize() {
    const computed = parseFloat(getComputedStyle(HTML).fontSize);
    if (!isNaN(computed) && computed > 0) {
      naturalFontSize = computed;
    }
  }

  // ── Apply CSS custom properties from config ───────────────────────────────
  function applyButtonStyles() {
    const root = document.getElementById('qak-widget-root');
    const btn  = document.getElementById('qak-trigger-btn');
    if (!root || !btn) return;

    // Position class
    const pos = CFG.position || 'bottom-right';
    root.className = 'qak-pos--' + pos;

    // Z-index
    root.style.zIndex = String(safeInt(CFG.zIndex, 1, 2147483647, 99999));

    // Button color/size via inline style (CSS vars)
    const sizes = { small: '48px', medium: '56px', large: '64px' };
    root.style.setProperty('--qak-btn-bg',   CFG.btnBg   || '#1E6264');
    root.style.setProperty('--qak-btn-icon', CFG.btnIcon || '#ffffff');
    root.style.setProperty('--qak-btn-size', sizes[CFG.btnSize] || '56px');

    // Apply icon color to SVG elements
    const svgStrokes = btn.querySelectorAll('svg');
    svgStrokes.forEach(function (svg) {
      svg.style.stroke = CFG.btnIcon || '#ffffff';
      const fill = svg.querySelector('.qak-svg-fill');
      if (fill) fill.style.fill = CFG.btnIcon || '#ffffff';
    });
  }

  // ── State ─────────────────────────────────────────────────────────────────
  const state = {
    fontStep:      safeInt(LS.get('fontStep', 0), -3, 3, 0),
    highContrast:  safeBool(LS.get('highContrast', false), false),
    invert:        safeBool(LS.get('invert', false), false),
    grayscale:     safeBool(LS.get('grayscale', false), false),
    highlightLinks:safeBool(LS.get('highlightLinks', false), false),
    readableFont:  safeBool(LS.get('readableFont', false), false),
    letterStep:    safeInt(LS.get('letterStep', 0), 0, 3, 0),
    pauseAnim:     safeBool(LS.get('pauseAnim', false), false),
    largeCursor:   safeBool(LS.get('largeCursor', false), false),
  };

  // ── Apply all persisted states on load ────────────────────────────────────
  function applyAllStates() {
    applyFontSize();
    applyClass('qak-high-contrast',   state.highContrast);
    applyClass('qak-invert',          state.invert);
    applyClass('qak-grayscale',       state.grayscale);
    applyClass('qak-highlight-links', state.highlightLinks);
    applyReadableFont(state.readableFont);
    applyLetterSpacing();
    applyClass('qak-pause-anim',      state.pauseAnim);
    applyClass('qak-large-cursor',    state.largeCursor);
  }

  function applyClass(cls, active) {
    HTML.classList.toggle(cls, active);
  }

  // Tags we capture computed font-sizes for (text-bearing elements Elementor touches).
  // Skipping pure layout containers keeps the walk fast and restore clean.
  const FONT_TAGS = new Set([
    'H1','H2','H3','H4','H5','H6',
    'P','SPAN','A','STRONG','EM','B','I','U','S',
    'LI','TD','TH','CAPTION','DT','DD',
    'BUTTON','LABEL','INPUT','TEXTAREA','SELECT',
    'BLOCKQUOTE','CITE','Q','MARK','SMALL','SUB','SUP',
    'FIGCAPTION','LEGEND','SUMMARY','DETAILS',
  ]);

  // Walk the live DOM and record each text element's original computed font-size.
  // Must be called BEFORE html.style.fontSize is changed so values are natural.
  function captureFontOriginals() {
    const widgetRoot = document.getElementById('qak-widget-root');
    const all = document.querySelectorAll('*');
    for (let i = 0; i < all.length; i++) {
      const el = all[i];
      if (el === HTML) continue;
      if (widgetRoot && (el === widgetRoot || widgetRoot.contains(el))) continue;
      if (!FONT_TAGS.has(el.tagName)) continue;
      if (el.hasAttribute('data-qak-fspx')) continue; // already captured

      const px = parseFloat(getComputedStyle(el).fontSize);
      if (isNaN(px) || px <= 0) continue;

      // data-qak-fs  → original inline font-size value (for clean restore)
      // data-qak-fspx → original computed px value (for scaling)
      el.setAttribute('data-qak-fs',  el.style.fontSize || '');
      el.setAttribute('data-qak-fspx', String(px));
    }
  }

  function applyFontSize() {
    const widgetRoot = document.getElementById('qak-widget-root');

    if (state.fontStep === 0) {
      // Restore every element we touched, then remove our attributes
      document.querySelectorAll('[data-qak-fspx]').forEach(function(el) {
        const orig = el.getAttribute('data-qak-fs');
        if (orig === '') {
          el.style.removeProperty('font-size');
        } else {
          el.style.fontSize = orig;
        }
        el.removeAttribute('data-qak-fs');
        el.removeAttribute('data-qak-fspx');
      });
      HTML.style.removeProperty('font-size');
      HTML.classList.remove('qak-font-active');
      return;
    }

    // Capture originals BEFORE changing html font-size (so computed values are natural)
    captureFontOriginals();

    const scale = (naturalFontSize + state.fontStep * 2) / naturalFontSize;

    // Change html font-size so rem-based designs scale too
    HTML.style.fontSize = (naturalFontSize * scale) + 'px';
    HTML.classList.add('qak-font-active');

    // Scale every captured element from its stored original
    document.querySelectorAll('[data-qak-fspx]').forEach(function(el) {
      if (widgetRoot && widgetRoot.contains(el)) return;
      const origPx = parseFloat(el.getAttribute('data-qak-fspx'));
      if (!isNaN(origPx)) {
        el.style.fontSize = (origPx * scale) + 'px';
      }
    });
  }

  function applyReadableFont(active) {
    if (active) {
      if (isRTL()) {
        // RTL: skip OpenDyslexic, use clean sans-serif fallback
        HTML.classList.remove('qak-readable-font');
        HTML.classList.add('qak-readable-font-rtl');
      } else {
        HTML.classList.remove('qak-readable-font-rtl');
        HTML.classList.add('qak-readable-font');
      }
    } else {
      HTML.classList.remove('qak-readable-font');
      HTML.classList.remove('qak-readable-font-rtl');
    }
  }

  function applyLetterSpacing() {
    HTML.classList.remove('qak-letter-1', 'qak-letter-2', 'qak-letter-3');
    if (state.letterStep > 0) {
      HTML.classList.add('qak-letter-' + state.letterStep);
    }
  }

  // ── Build Panel HTML ──────────────────────────────────────────────────────
  function buildPanel() {
    const panel  = document.getElementById('qak-panel');
    const feats  = CFG.features || {};
    const rtl    = isRTL();

    // Header
    const header = document.createElement('div');
    header.className = 'qak-panel-header';
    header.style.setProperty('background', CFG.btnBg || '#1E6264');

    const titleEl = document.createElement('p');
    titleEl.className  = 'qak-panel-title';
    titleEl.id         = 'qak-panel-title';
    titleEl.textContent = t('panelTitle');

    const closeBtn = document.createElement('button');
    closeBtn.type      = 'button';
    closeBtn.className = 'qak-panel-close';
    closeBtn.setAttribute('aria-label', 'Close accessibility menu');
    closeBtn.innerHTML = '<svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
    closeBtn.addEventListener('click', closePanel);

    header.appendChild(titleEl);
    header.appendChild(closeBtn);

    // Body
    const body = document.createElement('div');
    body.className = 'qak-panel-body';

    // ── Visual Group ─────────────────────────────────────────────
    if (feats.fontSize || feats.highContrast || feats.invert || feats.grayscale) {
      addGroupLabel(body, 'Visual');
    }

    if (feats.fontSize) {
      const row = createRow(t('fontSize'));
      const stepper = document.createElement('div');
      stepper.className = 'qak-font-stepper';

      const decBtn = makeStepBtn('−', 'Decrease font size', function () {
        if (state.fontStep <= -3) return;
        state.fontStep--;
        LS.set('fontStep', state.fontStep);
        applyFontSize();
        updateFontVal();
        updateStepBtns();
      });

      const valEl = document.createElement('span');
      valEl.className = 'qak-font-val';
      valEl.id = 'qak-font-val';

      const incBtn = makeStepBtn('+', 'Increase font size', function () {
        if (state.fontStep >= 3) return;
        state.fontStep++;
        LS.set('fontStep', state.fontStep);
        applyFontSize();
        updateFontVal();
        updateStepBtns();
      });

      function updateFontVal() {
        if (state.fontStep === 0) {
          valEl.textContent = 'Default';
        } else {
          const pct = Math.round(((naturalFontSize + state.fontStep * 2) / naturalFontSize) * 100);
          valEl.textContent = pct + '%';
        }
      }
      function updateStepBtns() {
        decBtn.disabled = state.fontStep <= -3;
        incBtn.disabled = state.fontStep >= 3;
      }
      updateFontVal();
      updateStepBtns();

      stepper.appendChild(decBtn);
      stepper.appendChild(valEl);
      stepper.appendChild(incBtn);
      row.appendChild(stepper);
      body.appendChild(row);
    }

    if (feats.highContrast) {
      body.appendChild(createToggleRow(
        t('highContrast'), state.highContrast,
        function (active) {
          state.highContrast = active;
          LS.set('highContrast', active);
          applyClass('qak-high-contrast', active);
        }
      ));
    }

    if (feats.invert) {
      body.appendChild(createToggleRow(
        t('invert'), state.invert,
        function (active) {
          state.invert = active;
          LS.set('invert', active);
          applyClass('qak-invert', active);
        }
      ));
    }

    if (feats.grayscale) {
      body.appendChild(createToggleRow(
        t('grayscale'), state.grayscale,
        function (active) {
          state.grayscale = active;
          LS.set('grayscale', active);
          applyClass('qak-grayscale', active);
        }
      ));
    }

    // ── Reading Group ────────────────────────────────────────────
    if (feats.highlightLinks || feats.readableFont || feats.letterSpacing) {
      addGroupLabel(body, 'Reading');
    }

    if (feats.highlightLinks) {
      body.appendChild(createToggleRow(
        t('highlightLinks'), state.highlightLinks,
        function (active) {
          state.highlightLinks = active;
          LS.set('highlightLinks', active);
          applyClass('qak-highlight-links', active);
        }
      ));
    }

    // Readable font: hide entirely for RTL
    if (feats.readableFont && !rtl) {
      body.appendChild(createToggleRow(
        t('readableFont'), state.readableFont,
        function (active) {
          state.readableFont = active;
          LS.set('readableFont', active);
          applyReadableFont(active);
        }
      ));
    }

    if (feats.letterSpacing) {
      const row = createRow(t('letterSpacing'));
      const stepper = document.createElement('div');
      stepper.className = 'qak-font-stepper';

      const decBtn2 = makeStepBtn('−', 'Decrease letter spacing', function () {
        if (state.letterStep <= 0) return;
        state.letterStep--;
        LS.set('letterStep', state.letterStep);
        applyLetterSpacing();
        updateLsVal();
        updateLsBtns();
      });

      const lsVal = document.createElement('span');
      lsVal.className = 'qak-font-val';

      const incBtn2 = makeStepBtn('+', 'Increase letter spacing', function () {
        if (state.letterStep >= 3) return;
        state.letterStep++;
        LS.set('letterStep', state.letterStep);
        applyLetterSpacing();
        updateLsVal();
        updateLsBtns();
      });

      function updateLsVal() {
        lsVal.textContent = state.letterStep === 0 ? 'Normal'
          : ['', '+S', '+M', '+L'][state.letterStep];
      }
      function updateLsBtns() {
        decBtn2.disabled = state.letterStep <= 0;
        incBtn2.disabled = state.letterStep >= 3;
      }
      updateLsVal();
      updateLsBtns();

      stepper.appendChild(decBtn2);
      stepper.appendChild(lsVal);
      stepper.appendChild(incBtn2);
      row.appendChild(stepper);
      body.appendChild(row);
    }

    // ── Motion Group ─────────────────────────────────────────────
    if (feats.pauseAnim || feats.largeCursor) {
      addGroupLabel(body, 'Motion & Sensory');
    }

    if (feats.pauseAnim) {
      body.appendChild(createToggleRow(
        t('pauseAnim'), state.pauseAnim,
        function (active) {
          state.pauseAnim = active;
          LS.set('pauseAnim', active);
          applyClass('qak-pause-anim', active);
        }
      ));
    }

    if (feats.largeCursor) {
      body.appendChild(createToggleRow(
        t('largeCursor'), state.largeCursor,
        function (active) {
          state.largeCursor = active;
          LS.set('largeCursor', active);
          applyClass('qak-large-cursor', active);
        }
      ));
    }

    // ── Reset ────────────────────────────────────────────────────
    const resetBtn = document.createElement('button');
    resetBtn.type      = 'button';
    resetBtn.className = 'qak-reset-btn';
    resetBtn.textContent = t('reset');
    resetBtn.addEventListener('click', resetAll);
    body.appendChild(resetBtn);

    // Assemble
    panel.innerHTML = '';
    panel.appendChild(header);
    panel.appendChild(body);
    panel.setAttribute('aria-labelledby', 'qak-panel-title');
  }

  // ── DOM helpers ───────────────────────────────────────────────────────────
  function createRow(labelText) {
    const row = document.createElement('div');
    row.className = 'qak-feat-row';
    const lbl = document.createElement('span');
    lbl.className   = 'qak-feat-label';
    lbl.textContent = labelText;
    row.appendChild(lbl);
    return row;
  }

  function createToggleRow(labelText, checked, onChange) {
    const row = createRow(labelText);
    const uid = 'qak-toggle-' + Math.random().toString(36).slice(2, 8);

    const wrap   = document.createElement('label');
    wrap.className     = 'qak-toggle';
    wrap.htmlFor       = uid;

    const input  = document.createElement('input');
    input.type   = 'checkbox';
    input.id     = uid;
    input.checked = !!checked;
    input.setAttribute('role', 'switch');
    input.setAttribute('aria-checked', checked ? 'true' : 'false');

    const track  = document.createElement('span');
    track.className = 'qak-toggle-track';
    const thumb  = document.createElement('span');
    thumb.className = 'qak-toggle-thumb';
    track.appendChild(thumb);

    input.addEventListener('change', function () {
      input.setAttribute('aria-checked', input.checked ? 'true' : 'false');
      onChange(input.checked);
    });

    wrap.appendChild(input);
    wrap.appendChild(track);
    row.appendChild(wrap);
    return row;
  }

  function makeStepBtn(symbol, ariaLabel, onClick) {
    const btn = document.createElement('button');
    btn.type        = 'button';
    btn.className   = 'qak-step-btn';
    btn.textContent = symbol;
    btn.setAttribute('aria-label', ariaLabel);
    btn.addEventListener('click', onClick);
    return btn;
  }

  function addGroupLabel(parent, text) {
    const sep = document.createElement('p');
    sep.className   = 'qak-group-sep';
    sep.textContent = text;
    parent.appendChild(sep);
  }

  // ── Reset all ─────────────────────────────────────────────────────────────
  function resetAll() {
    state.fontStep      = 0;
    state.highContrast  = false;
    state.invert        = false;
    state.grayscale     = false;
    state.highlightLinks= false;
    state.readableFont  = false;
    state.letterStep    = 0;
    state.pauseAnim     = false;
    state.largeCursor   = false;

    LS.clearAll();
    applyAllStates();

    // Rebuild panel to reset UI controls
    buildPanel();
  }

  // ── Open / Close panel ────────────────────────────────────────────────────
  function openPanel() {
    const panel  = document.getElementById('qak-panel');
    const trigger = document.getElementById('qak-trigger-btn');
    if (!panel || !trigger) return;

    // Re-render in case language changed
    buildPanel();

    panel.removeAttribute('hidden');
    trigger.setAttribute('aria-expanded', 'true');

    // Focus first interactive element inside panel
    requestAnimationFrame(function () {
      const first = panel.querySelector('button, input, [tabindex]');
      if (first) first.focus();
    });
  }

  function closePanel() {
    const panel  = document.getElementById('qak-panel');
    const trigger = document.getElementById('qak-trigger-btn');
    if (!panel || !trigger) return;
    panel.setAttribute('hidden', '');
    trigger.setAttribute('aria-expanded', 'false');
    trigger.focus();
  }

  function isPanelOpen() {
    const panel = document.getElementById('qak-panel');
    return panel && !panel.hasAttribute('hidden');
  }

  // ── Events ────────────────────────────────────────────────────────────────
  function setupEvents() {
    const trigger = document.getElementById('qak-trigger-btn');
    if (!trigger) return;

    trigger.addEventListener('click', function () {
      if (isPanelOpen()) {
        closePanel();
      } else {
        openPanel();
      }
    });

    // Close on outside click
    document.addEventListener('click', function (e) {
      const root = document.getElementById('qak-widget-root');
      if (isPanelOpen() && root && !root.contains(e.target)) {
        closePanel();
      }
    });

    // Close on Escape
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && isPanelOpen()) {
        closePanel();
      }
    });

    // Trap focus within panel when open
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Tab' || !isPanelOpen()) return;
      const panel      = document.getElementById('qak-panel');
      const focusable  = panel.querySelectorAll(
        'button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])'
      );
      if (!focusable.length) return;
      const first = focusable[0];
      const last  = focusable[focusable.length - 1];
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    });
  }

  // ── Init ──────────────────────────────────────────────────────────────────
  function init() {
    captureNaturalFontSize(); // must run before applyAllStates modifies font size
    applyButtonStyles();
    applyAllStates();
    setupEvents();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // ── Expose public API for multilingual plugin trigger ─────────────────────
  // Trigger: window.AccessKitWP.setLang('ar') — call after language switches.
  // The plugin will re-detect lang from <html> automatically on next panel open.
  window.AccessKitWP = {
    open:    openPanel,
    close:   closePanel,
    toggle:  function () { isPanelOpen() ? closePanel() : openPanel(); },
    reset:   resetAll,
    // Call this after your multilingual plugin switches the page language
    // to re-apply language-dependent features (e.g. readable font RTL check).
    refresh: function () {
      applyReadableFont(state.readableFont);
      if (isPanelOpen()) buildPanel();
    }
  };

}());
