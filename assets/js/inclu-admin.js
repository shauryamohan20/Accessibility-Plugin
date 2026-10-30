/**
 * IncluAccess — Admin JS
 * Tabs, live preview, color fields, page picker, unsaved-changes bar.
 * Vanilla JS, no dependencies.
 */
(function () {
  'use strict';

  const form = document.getElementById('inclu-form');
  if (!form) return;

  const I18N    = (window.IncluAccessAdmin && window.IncluAccessAdmin.i18n) || {};
  const $       = (sel, ctx) => (ctx || document).querySelector(sel);
  const $$      = (sel, ctx) => Array.from((ctx || document).querySelectorAll(sel));
  const browser = $('#inclu-browser');

  // ── Tabs (roving tabindex, arrow-key support) ─────────────────────────────
  function setupTabs(tabs, getPanel, onSelect) {
    function select(tab, focus) {
      tabs.forEach(function (t) {
        const on = t === tab;
        t.setAttribute('aria-selected', on ? 'true' : 'false');
        t.tabIndex = on ? 0 : -1;
        getPanel(t).hidden = !on;
      });
      if (focus) tab.focus();
      if (onSelect) onSelect(tab);
    }
    tabs.forEach(function (tab, i) {
      tab.addEventListener('click', function () { select(tab, false); });
      tab.addEventListener('keydown', function (e) {
        const keys = { ArrowDown: 1, ArrowRight: 1, ArrowUp: -1, ArrowLeft: -1 };
        let next = null;
        if (e.key in keys) next = tabs[(i + keys[e.key] + tabs.length) % tabs.length];
        if (e.key === 'Home') next = tabs[0];
        if (e.key === 'End')  next = tabs[tabs.length - 1];
        if (next) { e.preventDefault(); select(next, true); }
      });
    });
    return select;
  }

  const navTabs  = $$('.inclu-nav-item');
  const tabField = $('#inclu-tab-field');
  const selectNav = setupTabs(
    navTabs,
    function (t) { return document.getElementById(t.getAttribute('aria-controls')); },
    function (t) {
      tabField.value = t.dataset.tab;
      history.replaceState(null, '', '#' + t.dataset.tab);
    }
  );
  const initial = navTabs.find(function (t) { return '#' + t.dataset.tab === location.hash; });
  if (initial) selectNav(initial, false);
  window.addEventListener('hashchange', function () {
    const tab = navTabs.find(function (t) { return '#' + t.dataset.tab === location.hash; });
    if (tab && tab.getAttribute('aria-selected') !== 'true') selectNav(tab, false);
  });

  // ── Color math (WCAG contrast) ────────────────────────────────────────────
  function isHex(v) { return /^#[0-9a-f]{6}$/i.test(v); }
  function luminance(hex) {
    const c = [1, 3, 5].map(function (i) {
      const v = parseInt(hex.substr(i, 2), 16) / 255;
      return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
  }
  function contrast(a, b) {
    const la = luminance(a), lb = luminance(b);
    return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
  }

  const bgHex   = $('input[name="btn_bg_color"]');
  const iconHex = $('input[name="btn_icon_color"]');
  const ratioEl = $('#inclu-contrast');

  function updateColors() {
    const bg = isHex(bgHex.value) ? bgHex.value : '#2346d3';
    const fg = isHex(iconHex.value) ? iconHex.value : '#ffffff';
    form.style.setProperty('--inclu-bg', bg); // shared by the preview and the icon choices
    form.style.setProperty('--inclu-fg', fg);

    const r = contrast(bg, fg);
    ratioEl.classList.toggle('is-low', r < 3);
    $('.inclu-contrast-ratio', ratioEl).textContent = r.toFixed(1) + ':1';
    $('.inclu-contrast-text', ratioEl).textContent = r < 3 ? I18N.contrastLow : I18N.contrastOk;
  }

  // ── Color fields: swatches + native picker + hex input stay in sync ───────
  $$('[data-color-field]').forEach(function (field) {
    const picker   = $('[data-color-picker]', field);
    const hex      = $('[data-color-hex]', field);
    const swatches = $$('.inclu-swatch', field);

    function set(value, from) {
      value = value.toLowerCase();
      if (from !== hex) hex.value = value;
      if (isHex(value)) {
        if (from !== picker) picker.value = value;
        swatches.forEach(function (s) {
          s.setAttribute('aria-pressed', s.dataset.color.toLowerCase() === value ? 'true' : 'false');
        });
      }
      updateColors();
      markDirty();
    }

    swatches.forEach(function (s) {
      s.addEventListener('click', function () { set(s.dataset.color, s); });
    });
    picker.addEventListener('input', function () { set(picker.value, picker); });
    hex.addEventListener('input', function () {
      let v = hex.value.trim();
      if (v && v[0] !== '#') v = '#' + v;
      set(v, hex);
    });
    hex.addEventListener('blur', function () {
      if (!isHex(hex.value)) set(picker.value, null);
    });
  });

  // ── Button icon ───────────────────────────────────────────────────────────
  // Built-in choices, or an image from the WordPress media library. Only the
  // attachment ID is submitted; the server re-checks the file on save.
  const ICON_CFG   = (window.IncluAccessAdmin && window.IncluAccessAdmin.icon) || { mimes: [], maxBytes: 0 };
  const iconRadios = $$('input[name="btn_icon"]');
  const customId   = $('#inclu-custom-icon');
  const customFace = $('#inclu-custom-face');
  const uploadBox  = $('#inclu-upload');
  const pickBtn    = $('#inclu-icon-pick');
  const removeBtn  = $('#inclu-icon-remove');
  const iconError  = $('#inclu-icon-error');
  const mockBtn    = $('#inclu-mock-btn');
  const UPLOAD_SVG = $('#inclu-upload-icon').innerHTML; // static icon markup from the page
  let mediaFrame   = null;

  function showIconError(msg) {
    iconError.textContent = msg || '';
    iconError.hidden = !msg;
  }

  function setCustomImage(url) {
    customFace.textContent = '';
    if (url) {
      const img = document.createElement('img');
      img.alt = '';
      img.src = url; // set as a property, never as HTML
      customFace.appendChild(img);
    } else {
      customFace.innerHTML = UPLOAD_SVG;
    }
    removeBtn.hidden = !url;
  }

  function renderIconPreview() {
    const checked = $('input[name="btn_icon"]:checked');
    const face    = checked && checked.parentNode.querySelector('[data-icon-face]');
    uploadBox.hidden = !checked || checked.value !== 'custom';
    mockBtn.textContent = '';
    if (face && (checked.value !== 'custom' || customFace.querySelector('img'))) {
      mockBtn.appendChild(face.firstElementChild.cloneNode(true));
    }
  }

  function openMediaFrame() {
    if (!window.wp || !wp.media) return;
    if (!mediaFrame) {
      mediaFrame = wp.media({
        title: I18N.iconTitle,
        button: { text: I18N.iconUse },
        multiple: false,
        library: { type: ICON_CFG.mimes }
      });
      mediaFrame.on('select', function () {
        const a = mediaFrame.state().get('selection').first().toJSON();
        // Friendly early checks; the real check happens on the server.
        if (ICON_CFG.mimes.indexOf(a.mime) === -1) return showIconError(I18N.iconBadType);
        if (a.filesizeInBytes && a.filesizeInBytes > ICON_CFG.maxBytes) return showIconError(I18N.iconTooBig);
        showIconError('');
        const sized = a.sizes && (a.sizes.medium || a.sizes.full);
        customId.value = String(parseInt(a.id, 10) || 0);
        setCustomImage((sized && sized.url) || a.url);
        $('#inclu-icon-custom').checked = true;
        renderIconPreview();
        markDirty();
      });
    }
    mediaFrame.open();
  }

  iconRadios.forEach(function (r) {
    r.addEventListener('change', function () {
      showIconError('');
      renderIconPreview();
    });
  });
  pickBtn.addEventListener('click', openMediaFrame);
  removeBtn.addEventListener('click', function () {
    customId.value = '0';
    setCustomImage('');
    iconRadios[0].checked = true;
    renderIconPreview();
    markDirty();
    iconRadios[0].focus();
  });

  // "Change" / "Choose pages" links in the header jump to a settings tab.
  $$('[data-goto]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      const tab = navTabs.find(function (t) { return t.dataset.tab === a.dataset.goto; });
      if (!tab) return;
      e.preventDefault();
      selectNav(tab, true);
    });
  });

  // ── Size / position / offset → preview ────────────────────────────────────
  form.addEventListener('change', function (e) {
    const t = e.target;
    if (t.name === 'btn_size')     browser.dataset.size = t.value;
    if (t.name === 'btn_position') browser.dataset.pos  = t.value;
  });

  const offset    = $('#inclu-offset');
  const offsetOut = $('#inclu-offset-out');
  offset.addEventListener('input', function () {
    offsetOut.textContent = offset.value + 'px';
    browser.style.setProperty('--inclu-offset', offset.value);
  });

  $('#inclu-preview-toggle').addEventListener('click', function () {
    const open = browser.dataset.open !== '1';
    browser.dataset.open = open ? '1' : '0';
    this.setAttribute('aria-pressed', open ? 'true' : 'false');
  });

  // ── Features ──────────────────────────────────────────────────────────────
  const switches = $$('.inclu-switch');
  const featCount = $('#inclu-feat-count');

  function updateFeatures() {
    let on = 0;
    switches.forEach(function (sw) {
      if (sw.checked) on++;
      const tile = $('.inclu-mock-tile[data-feature="' + sw.dataset.feature + '"]');
      if (tile) tile.hidden = !sw.checked;
    });
    featCount.textContent = (I18N.featCount || '%1$d of %2$d turned on').replace('%1$d', on).replace('%2$d', switches.length);
  }
  switches.forEach(function (sw) { sw.addEventListener('change', updateFeatures); });

  $$('[data-bulk]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const val = btn.dataset.bulk === 'on';
      switches.forEach(function (sw) { sw.checked = val; });
      updateFeatures();
      markDirty();
    });
  });

  // ── Languages ─────────────────────────────────────────────────────────────
  // Built-in labels come from inclu-i18n.js. The model only stores what the site
  // owner changed (overrides) plus their own languages, and is submitted as
  // one JSON field.
  const CAT   = window.IncluAccessI18n;
  const SAVED = (window.IncluAccessAdmin && window.IncluAccessAdmin.langs) || {};
  const model = {
    fallback: SAVED.fallback || 'en',
    custom:   Object.assign({}, SAVED.custom),
    strings:  JSON.parse(JSON.stringify(SAVED.strings || {}))
  };
  const MAX_CUSTOM = 50;

  const langField  = $('#inclu-languages-field');
  const langSelect = $('#inclu-lang-select');
  const fallbackEl = $('#inclu-fallback-lang');
  const langInputs = $$('#inclu-lang-grid [data-string]');
  const langMeta   = $('#inclu-lang-meta');
  const langHint   = $('#inclu-lang-hint');
  const restoreBtn = $('#inclu-lang-restore');
  const deleteBtn  = $('#inclu-lang-delete');

  function isBuiltin(code) { return Object.prototype.hasOwnProperty.call(CAT.langs, code); }
  function exists(code)    { return isBuiltin(code) || Object.prototype.hasOwnProperty.call(model.custom, code); }
  function langName(code)  { return isBuiltin(code) ? CAT.langs[code].n : model.custom[code].name; }
  function langDir(code)   { return isBuiltin(code) ? CAT.langs[code].d : model.custom[code].dir; }
  function builtinLabel(code, key) {
    return isBuiltin(code) ? CAT.langs[code].s[CAT.KEYS.indexOf(key)] : '';
  }
  function override(code, key) { return (model.strings[code] && model.strings[code][key]) || ''; }
  // What visitors will actually see for this language and key.
  function effective(code, key) {
    return override(code, key) || builtinLabel(code, key) ||
      (code !== model.fallback ? effective(model.fallback, key) : '') || builtinLabel('en', key);
  }
  function isEdited(code) {
    return isBuiltin(code) && !!model.strings[code] && Object.keys(model.strings[code]).length > 0;
  }

  // Pick the language to show first: the site's language if we have it.
  let current = (CAT.candidates(SAVED.site).filter(exists)[0]) || model.fallback;

  function option(code, selected) {
    const o = document.createElement('option');
    o.value = code;
    o.textContent = langName(code) + '  (' + code + ')' + (isEdited(code) ? '  • ' + I18N.edited : '');
    o.selected = code === selected;
    return o;
  }

  function fillSelect(sel, selected, withGroups) {
    sel.innerHTML = '';
    const groups = [
      [I18N.builtIn, Object.keys(CAT.langs)],
      [I18N.customGroup, Object.keys(model.custom).sort()]
    ];
    groups.forEach(function (g) {
      if (!g[1].length) return;
      const parent = withGroups ? document.createElement('optgroup') : sel;
      if (withGroups) { parent.label = g[0]; sel.appendChild(parent); }
      g[1].forEach(function (code) { parent.appendChild(option(code, selected)); });
    });
  }

  function syncLangField() {
    langField.value = JSON.stringify(model);
  }

  function renderPreviewLabels() {
    const mock = $('.inclu-mock-panel');
    mock.setAttribute('dir', langDir(current));
    mock.setAttribute('lang', current);
    $('#inclu-mock-title').textContent = effective(current, 'panel_title');
    $$('.inclu-mock-tile [data-string]').forEach(function (span) {
      span.textContent = effective(current, span.dataset.string);
    });
  }

  function renderLangEditor() {
    const builtin = isBuiltin(current);
    const dir     = langDir(current);
    langInputs.forEach(function (input) {
      const key = input.dataset.string;
      input.setAttribute('dir', dir);
      input.setAttribute('lang', current);
      if (builtin) {
        input.value       = override(current, key) || builtinLabel(current, key);
        input.placeholder = builtinLabel(current, key);
      } else {
        input.value       = override(current, key);
        input.placeholder = effective(model.fallback, key);
      }
      input.classList.toggle('is-edited', builtin && !!override(current, key));
    });

    langMeta.textContent = (dir === 'rtl' ? I18N.rtl : I18N.ltr) + ' · ' + current;
    langHint.textContent = builtin ? I18N.builtinHint : I18N.customHint;
    restoreBtn.hidden = !builtin;
    restoreBtn.disabled = !isEdited(current);
    $('span', restoreBtn).textContent = I18N.restore.replace('%s', langName(current));
    deleteBtn.hidden = builtin;
    $('span', deleteBtn).textContent = I18N.delete.replace('%s', langName(current));
    renderPreviewLabels();
  }

  function refreshLanguages() {
    fillSelect(langSelect, current, true);
    fillSelect(fallbackEl, model.fallback, true);
    syncLangField();
    renderLangEditor();
  }

  function langChanged() {
    syncLangField();
    // Keep "• edited" markers current without rebuilding the open select.
    const opt = langSelect.querySelector('option[value="' + CSS.escape(current) + '"]');
    if (opt) opt.replaceWith(option(current, current));
    restoreBtn.disabled = !isEdited(current);
    renderPreviewLabels();
    markDirty();
  }

  langInputs.forEach(function (input) {
    input.addEventListener('input', function (e) {
      e.stopPropagation(); // the model, not this input, is what gets submitted
      const key   = input.dataset.string;
      const value = input.value.trim();
      const map   = model.strings[current] || (model.strings[current] = {});
      if (!value || (isBuiltin(current) && value === builtinLabel(current, key))) delete map[key];
      else map[key] = value;
      if (!Object.keys(map).length) delete model.strings[current];
      input.classList.toggle('is-edited', isBuiltin(current) && !!map[key]);
      langChanged();
    });
  });

  langSelect.addEventListener('change', function (e) {
    e.stopPropagation();
    current = langSelect.value;
    renderLangEditor();
  });

  fallbackEl.addEventListener('change', function (e) {
    e.stopPropagation();
    model.fallback = fallbackEl.value;
    langChanged();
    renderLangEditor();
  });

  restoreBtn.addEventListener('click', function () {
    delete model.strings[current];
    renderLangEditor();
    langChanged();
  });

  deleteBtn.addEventListener('click', function () {
    if (!window.confirm(I18N.confirmDelete)) return;
    delete model.custom[current];
    delete model.strings[current];
    if (model.fallback === current) model.fallback = 'en';
    current = model.fallback;
    refreshLanguages();
    markDirty();
    langSelect.focus();
  });

  // Add-a-language form
  const addPanel  = $('#inclu-lang-add');
  const addToggle = $('#inclu-lang-add-toggle');
  const newCode   = $('#inclu-new-code');
  const newName   = $('#inclu-new-name');
  const newError  = $('#inclu-new-error');

  function showAdd(open) {
    addPanel.hidden = !open;
    addToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    newError.hidden = true;
    if (open) newCode.focus();
    else { newCode.value = ''; newName.value = ''; }
  }
  function addError(msg, field) {
    newError.textContent = msg;
    newError.hidden = false;
    field.focus();
  }
  function addLanguage() {
    const code = newCode.value.trim().toLowerCase().replace(/_/g, '-');
    const name = newName.value.trim();
    if (!/^[a-z]{2,3}(-[a-z0-9]{2,8}){0,2}$/.test(code)) return addError(I18N.codeInvalid, newCode);
    if (exists(code)) return addError(I18N.codeExists, newCode);
    if (!name) return addError(I18N.nameRequired, newName);
    if (Object.keys(model.custom).length >= MAX_CUSTOM) return addError(I18N.tooMany, newCode);

    const dir = ($('input[name="incluaccess_new_dir"]:checked') || {}).value === 'rtl' ? 'rtl' : 'ltr';
    model.custom[code] = { name: name, dir: dir };
    current = code;
    showAdd(false);
    refreshLanguages();
    markDirty();
    langInputs[0].focus();
  }

  addToggle.addEventListener('click', function () { showAdd(addPanel.hidden); });
  $('#inclu-lang-add-cancel').addEventListener('click', function () { showAdd(false); addToggle.focus(); });
  $('#inclu-lang-add-save').addEventListener('click', addLanguage);
  [newCode, newName].forEach(function (input) {
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); addLanguage(); }
      if (e.key === 'Escape') { showAdd(false); addToggle.focus(); }
    });
    input.addEventListener('input', function (e) { e.stopPropagation(); });
  });

  refreshLanguages();

  // ── Visibility ────────────────────────────────────────────────────────────
  const picker     = $('#inclu-page-picker');
  const pageItems  = $$('.inclu-page-item');
  const pickCount  = $('#inclu-picker-count');
  const emptyMsg   = $('#inclu-page-empty');

  function updatePickCount() {
    const n = pageItems.filter(function (i) { return $('input', i).checked; }).length;
    pickCount.textContent = (I18N.selected || '%d selected').replace('%d', n);
  }

  $$('input[name="visibility_mode"]').forEach(function (r) {
    r.addEventListener('change', function () { picker.hidden = r.value !== 'selected'; });
  });

  const search = $('#inclu-page-search');
  search.addEventListener('input', function () {
    const q = search.value.trim().toLowerCase();
    let shown = 0;
    pageItems.forEach(function (item) {
      const match = item.textContent.toLowerCase().indexOf(q) !== -1;
      item.hidden = !match;
      if (match) shown++;
    });
    if (emptyMsg) emptyMsg.hidden = shown > 0;
  });
  search.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') e.preventDefault(); // don't submit the form
  });

  function bulkPages(val) {
    pageItems.forEach(function (item) { if (!item.hidden) $('input', item).checked = val; });
    updatePickCount();
    markDirty();
  }
  $('#inclu-select-all').addEventListener('click', function () { bulkPages(true); });
  $('#inclu-select-none').addEventListener('click', function () { bulkPages(false); });
  pageItems.forEach(function (i) { $('input', i).addEventListener('change', updatePickCount); });

  // ── Unsaved changes ───────────────────────────────────────────────────────
  const savebar  = $('#inclu-savebar');
  const saveMsg  = $('#inclu-savebar-msg');
  const discard  = $('#inclu-discard');
  const saveBtn  = $('#inclu-save');
  const snapshot = new URLSearchParams(new FormData(form)).toString();
  let dirty      = false;
  let submitting = false;

  function serialize() {
    const data = new FormData(form);
    data.delete('incluaccess_tab');
    return new URLSearchParams(data).toString();
  }
  const clean = (function () {
    const d = new URLSearchParams(snapshot);
    d.delete('incluaccess_tab');
    return d.toString();
  }());

  function markDirty() {
    const now = serialize() !== clean;
    if (now === dirty) return;
    dirty = now;
    savebar.classList.toggle('is-dirty', dirty);
    saveMsg.textContent = dirty ? I18N.unsaved : I18N.upToDate;
    discard.disabled = !dirty;
  }
  form.addEventListener('input', markDirty);
  form.addEventListener('change', markDirty);

  discard.addEventListener('click', function () {
    submitting = true; // reload without the leave prompt
    location.reload();
  });

  form.addEventListener('submit', function () {
    submitting = true;
    saveBtn.disabled = true;
    saveBtn.textContent = I18N.saving;
  });

  window.addEventListener('beforeunload', function (e) {
    if (dirty && !submitting) {
      e.preventDefault();
      e.returnValue = I18N.leave;
    }
  });

  // Ctrl/Cmd + S saves.
  document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && (e.key || '').toLowerCase() === 's') {
      e.preventDefault();
      form.requestSubmit ? form.requestSubmit(saveBtn) : form.submit();
    }
  });

  // ── After a save redirect: show the "Saved" toast, then drop the one-time
  // flags so a reload doesn't show it (or the notices) again. ───────────────
  if (form.dataset.saved === '1') {
    const url = new URL(location.href);
    if (!url.searchParams.has('incluaccess-icon-rejected')) {
      const toast = $('#inclu-toast');
      toast.hidden = false;
      setTimeout(function () {
        toast.classList.add('is-leaving');
        toast.addEventListener('animationend', function () { toast.hidden = true; }, { once: true });
      }, 2600);
    }
    url.searchParams.delete('incluaccess-saved');
    url.searchParams.delete('incluaccess-icon-rejected');
    history.replaceState(null, '', url.toString());
  }

  // ── Initial paint ─────────────────────────────────────────────────────────
  updateColors();
  updateFeatures();
  updatePickCount();
}());
