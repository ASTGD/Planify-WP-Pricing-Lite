(function(w){
  const wp = w.wp || window.wp || {};
  const element = wp.element || {};
  const { createElement: h, useState, useEffect, useRef } = element;

  const REQUIRED_COMPONENTS = [ 'Card', 'CardBody', 'TabPanel', 'TextControl' ];
  const getWPComponents = () => (w.wp && w.wp.components) || {};
  const hasRequiredComponents = () => {
    const cmp = getWPComponents();
    return REQUIRED_COMPONENTS.every((key) => typeof cmp[ key ] === 'function');
  };
  const classNames = (base, extra) => extra ? base + ' ' + extra : base;
  const clampChannel = (value) => {
    const num = Number(value);
    if (!Number.isFinite(num)) {
      return 0;
    }
    return Math.max(0, Math.min(255, Math.round(num)));
  };
  const clampAlpha = (value) => {
    const num = Number(value);
    if (!Number.isFinite(num)) {
      return 1;
    }
    if (num <= 0) {
      return 0;
    }
    if (num >= 1) {
      return 1;
    }
    return Number(num.toFixed(2));
  };
  const rgbaString = (rgb = {}) => {
    const { r = 0, g = 0, b = 0, a = 1 } = rgb;
    return `rgba(${clampChannel(r)}, ${clampChannel(g)}, ${clampChannel(b)}, ${clampAlpha(a)})`;
  };
  const parseCssColor = (value) => {
    if (typeof value !== 'string') {
      return { r: 0, g: 0, b: 0, a: 1 };
    }
    const raw = value.trim();
    const rgbaMatch = raw.match(/^rgba?\(([^)]+)\)/i);
    if (rgbaMatch) {
      const parts = rgbaMatch[1].split(',').map((v) => v.trim());
      const r = clampChannel(parseFloat(parts[0]));
      const g = clampChannel(parseFloat(parts[1]));
      const b = clampChannel(parseFloat(parts[2]));
      const a = parts[3] !== undefined ? clampAlpha(parseFloat(parts[3])) : 1;
      return { r, g, b, a };
    }
    if (/^#/.test(raw) || /^[0-9a-f]{3,8}$/i.test(raw)) {
      const rgb = hexToRgb(raw);
      return { ...rgb, a: 1 };
    }
    return { r: 0, g: 0, b: 0, a: 1 };
  };
  const rgbaFromHsv = (h, s, v, a = 1) => {
    const { r, g, b } = hsvToRgb(h, s, v);
    return rgbaString({ r, g, b, a });
  };
  const normalizeColorValue = (value, allowAlpha = false) => {
    if (value == null) {
      return '';
    }
    if (typeof value === 'string') {
      const trimmed = value.trim();
      if (!trimmed) {
        return '';
      }
      if (/^#/.test(trimmed) || /^rgb(a)?\(/i.test(trimmed)) {
        return trimmed;
      }
      if (/^[0-9a-f]{3,8}$/i.test(trimmed)) {
        return '#' + trimmed;
      }
      return trimmed;
    }
    if (typeof value === 'object') {
      if (allowAlpha && value.rgb) {
        return rgbaString(value.rgb);
      }
      if (typeof value.hex === 'string') {
        return value.hex;
      }
      if (typeof value.color === 'string') {
        return normalizeColorValue(value.color, allowAlpha);
      }
    }
    return '';
  };
  const clamp01 = (value) => {
    if (!Number.isFinite(value)) {
      return 0;
    }
    if (value <= 0) {
      return 0;
    }
    if (value >= 1) {
      return 1;
    }
    return value;
  };
  const hexToRgb = (hex) => {
    if (typeof hex !== 'string') {
      return { r: 0, g: 0, b: 0 };
    }
    let raw = hex.trim();
    if (!raw) {
      return { r: 0, g: 0, b: 0 };
    }
    if (raw[0] === '#') {
      raw = raw.slice(1);
    }
    if (raw.length === 3) {
      raw = raw.split('').map((c) => c + c).join('');
    }
    if (raw.length !== 6) {
      return { r: 0, g: 0, b: 0 };
    }
    const r = parseInt(raw.slice(0, 2), 16);
    const g = parseInt(raw.slice(2, 4), 16);
    const b = parseInt(raw.slice(4, 6), 16);
    if ([r, g, b].some((v) => Number.isNaN(v))) {
      return { r: 0, g: 0, b: 0 };
    }
    return { r, g, b };
  };
  const rgbToHex = ({ r = 0, g = 0, b = 0 }) => {
    const toHex = (value) => {
      const clamped = Math.max(0, Math.min(255, Math.round(value)));
      return clamped.toString(16).padStart(2, '0');
    };
    return `#${toHex(r)}${toHex(g)}${toHex(b)}`;
  };
  const rgbToHsv = (r = 0, g = 0, b = 0) => {
    const rn = r / 255;
    const gn = g / 255;
    const bn = b / 255;
    const max = Math.max(rn, gn, bn);
    const min = Math.min(rn, gn, bn);
    const delta = max - min;
    let h = 0;
    if (delta !== 0) {
      if (max === rn) {
        h = ((gn - bn) / delta) % 6;
      } else if (max === gn) {
        h = (bn - rn) / delta + 2;
      } else {
        h = (rn - gn) / delta + 4;
      }
      h *= 60;
      if (h < 0) {
        h += 360;
      }
    }
    const s = max === 0 ? 0 : delta / max;
    const v = max;
    return { h, s, v };
  };
  const hsvToRgb = (h = 0, s = 0, v = 0) => {
    const hh = ((h % 360) + 360) % 360;
    const c = v * s;
    const x = c * (1 - Math.abs((hh / 60) % 2 - 1));
    const m = v - c;
    let rp = 0, gp = 0, bp = 0;
    if (hh >= 0 && hh < 60) { rp = c; gp = x; bp = 0; }
    else if (hh >= 60 && hh < 120) { rp = x; gp = c; bp = 0; }
    else if (hh >= 120 && hh < 180) { rp = 0; gp = c; bp = x; }
    else if (hh >= 180 && hh < 240) { rp = 0; gp = x; bp = c; }
    else if (hh >= 240 && hh < 300) { rp = x; gp = 0; bp = c; }
    else { rp = c; gp = 0; bp = x; }
    return {
      r: Math.round((rp + m) * 255),
      g: Math.round((gp + m) * 255),
      b: Math.round((bp + m) * 255),
    };
  };
  const hsvToHex = (h = 0, s = 0, v = 0) => rgbToHex(hsvToRgb(h, s, v));
  const rgbaToHex8 = (r = 0, g = 0, b = 0, a = 1) => {
    const toHex = (value) => {
      const clamped = Math.max(0, Math.min(255, Math.round(value)));
      return clamped.toString(16).padStart(2, '0');
    };
    const alphaByte = Math.max(0, Math.min(255, Math.round(clamp01(a) * 255)));
    return `#${toHex(r)}${toHex(g)}${toHex(b)}${toHex(alphaByte)}`;
  };
  const hsvToHex8 = (h = 0, s = 0, v = 0, a = 1) => {
    const { r, g, b } = hsvToRgb(h, s, v);
    return rgbaToHex8(r, g, b, a);
  };
  const hexToHsv = (hex) => {
    const rgb = hexToRgb(hex);
    return rgbToHsv(rgb.r, rgb.g, rgb.b);
  };

  const FallbackCard = (props = {}) => {
    const { className = '', children, ...rest } = props;
    return h('div', { ...rest, className: classNames( 'pwpl-card-fallback', className ) }, children);
  };
  const FallbackCardBody = (props = {}) => {
    const { className = '', children, ...rest } = props;
    return h('div', { ...rest, className: classNames( 'pwpl-card-body-fallback', className ) }, children);
  };
  const FallbackTabPanel = (props = {}) => {
    const { tabs = [], children, className = '' } = props;
    const active = tabs.length ? tabs[0] : { name: 'default' };
    const body = typeof children === 'function' ? children( active ) : children;
    return h('div', { className: classNames( 'pwpl-tabpanel-fallback', className ) }, body);
  };
  const FallbackTextControl = (props = {}) => {
    const { label, value, onChange, className = '', help, type = 'text', ...rest } = props;
    const handleChange = ( event ) => {
      if ( typeof onChange === 'function' ) {
        onChange( event && event.target ? event.target.value : event );
      }
    };
    return h('div', { className: classNames( 'components-base-control pwpl-textcontrol-fallback', className ) }, [
      label ? h('label', { className: 'components-base-control__label' }, label ) : null,
      h('input', { type, value: value == null ? '' : value, onChange: handleChange, className: 'components-text-control__input', ...rest }),
      help ? h('p', { className: 'components-base-control__help' }, help ) : null,
    ]);
  };
  const FallbackNumberControl = (props = {}) => {
    return FallbackTextControl( { ...props, type: 'number' } );
  };
  const FallbackRangeControl = (props = {}) => {
    const { value, onChange, min, max, step, disabled, className = '' } = props;
    const handleChange = (event) => {
      const next = event && event.target ? parseFloat(event.target.value) : event;
      if ( typeof onChange === 'function' ) {
        onChange( Number.isFinite(next) ? next : 0 );
      }
    };
    return h('input', {
      type: 'range',
      value: value == null ? '' : value,
      onChange: handleChange,
      min,
      max,
      step,
      disabled,
      className: classNames('pwpl-range-fallback', className),
    });
  };

  function createComponentProxy( name, fallback ) {
    const FallbackComp = fallback || ( ( props ) => h( 'div', props, props && props.children ) );
    function Proxy( props ) {
      const comps = getWPComponents();
      const Target = comps[ name ] || FallbackComp;
      return h( Target, props || {} );
    }
    Proxy.displayName = `PWPLProxy(${ name })`;
    return Proxy;
  }

  const Card = createComponentProxy( 'Card', FallbackCard );
  const CardBody = createComponentProxy( 'CardBody', FallbackCardBody );
  const TabPanel = createComponentProxy( 'TabPanel', FallbackTabPanel );
  const TextControl = createComponentProxy( 'TextControl', FallbackTextControl );
  const NumberControl = createComponentProxy( '__experimentalNumberControl', FallbackNumberControl );
  const RangeControl = createComponentProxy( 'RangeControl', FallbackRangeControl );
  const BaseColorPalette = w.PWPL_ColorPalette;
  const PaletteFallback = function PaletteFallback( props ) {
    const { label, value, onChange } = props || {};
    return h( 'div', { className: 'pwpl-palette' }, [
      label ? h( 'div', { className: 'pwpl-palette__label' }, label ) : null,
      h( TextControl, {
        label: null,
        value: value || '',
        onChange: ( val ) => {
          if ( typeof onChange === 'function' ) {
            onChange( val );
          }
        },
      } ),
    ] );
  };
  let ColorPaletteControl = typeof BaseColorPalette === 'function' ? BaseColorPalette : null;

  // Inlined accordion implementation (used unless a shared global exists)
  const BaseAccordion = w.PWPL_Accordion;
  const BaseAccordionItem = w.PWPL_AccordionItem;
  const AccordionFallback = function AccordionFallback( props ) {
    const { children, searchValue, onSearchChange, onSearchKeyDown, onClear } = props || {};
    const handleKeyDown = (event) => {
      if (typeof onSearchKeyDown === 'function') {
        onSearchKeyDown(event);
      }
    };
    return h('div', { className: 'pwpl-acc' }, [
      h('div', { className: 'pwpl-acc__toolbar' }, [
        h('div', { className: 'pwpl-acc__search' }, [
          h('span', { className: 'pwpl-acc__search-label' }, 'Search'),
          h('div', { className: 'pwpl-acc__search-field' }, [
            h('input', {
              type: 'search',
              value: searchValue || '',
              onChange: ( event ) => onSearchChange && onSearchChange( event.target.value ),
              onKeyDown: handleKeyDown,
              placeholder: 'Filter settings…',
            }),
            h('button', { type: 'button', className: 'pwpl-acc__search-clear', onClick: () => onClear && onClear() }, 'Clear')
          ]),
        ]),
      ]),
      h('div', { className: 'pwpl-acc__list' }, children)
    ]);
  };
  const AccordionItemFallback = function AccordionItemFallback( props ) {
    const { id, title, isOpen, onToggle, hidden, children } = props || {};
    const open = !!isOpen;
    const itemCls = ['pwpl-acc__item', open ? 'is-open' : '', hidden ? 'is-hidden' : ''].filter(Boolean).join(' ');
    const panelId = (id || 'acc-item') + '__panel';
    const btnId = (id || 'acc-item') + '__btn';
    const handleKey = (event) => {
      if (event.key === ' ' || event.key === 'Enter'){
        event.preventDefault();
        if (typeof onToggle === 'function') onToggle(id);
      }
    };
    return h('div', { className: itemCls, id }, [
      h('button', {
        id: btnId,
        type: 'button',
        className: 'pwpl-acc__btn',
        'aria-expanded': open,
        'aria-controls': panelId,
        onClick: () => typeof onToggle === 'function' && onToggle(id),
        onKeyDown: handleKey,
      }, [
        h('div', { className: 'pwpl-acc__head' }, [
          h('div', { className: 'pwpl-acc__title-wrap' }, [
            h('span', { className: 'pwpl-acc__title' }, title || ''),
          ]),
          h('div', { className: 'pwpl-acc__summary-chips' }),
        ]),
        h('span', { className: 'pwpl-acc__chev', 'aria-hidden': 'true' }, '⌄')
      ]),
      h('div', { className: ['pwpl-acc__panel', open ? 'is-open' : ''].join(' '), id: panelId, role: 'region', 'aria-labelledby': btnId }, children)
    ]);
  };
  const Accordion = typeof BaseAccordion === 'function' ? BaseAccordion : AccordionFallback;
  const AccordionItem = typeof BaseAccordionItem === 'function' ? BaseAccordionItem : AccordionItemFallback;

  const i18n = (s) => s || '';
  const data = w.PWPL_AdminV1 || { postId: 0, layout: { widths: {}, columns: {} }, card: {}, i18n: {} };
  const PREVIEW_ENABLED = false; // Disabled for now

  // -----------------------------
  // Persistent form aggregator
  // -----------------------------
  const AGGREGATE_ID = 'pwpl-v1-aggregate';
  function ensureAggregateContainer(){
    const form = document.getElementById('post');
    if (!form) return null;
    let agg = document.getElementById(AGGREGATE_ID);
    if (!agg){
      agg = document.createElement('div');
      agg.id = AGGREGATE_ID;
      agg.style.display = 'none';
      form.appendChild(agg);
    }
    return agg;
  }
  const ARRAY_FIELD_REGEX = /\[\]$/;
  function isArrayFieldName(name){
    return ARRAY_FIELD_REGEX.test(name || '');
  }
  function collectNodeValues(node){
    if (!node || !node.name) return [];
    const tag = (node.tagName || '').toLowerCase();
    const type = (node.type || '').toLowerCase();
    const valueToString = (val) => (val == null ? '' : String(val));
    if (tag === 'input'){
      if (type === 'checkbox'){
        if (node.checked){
          return [valueToString(node.value || '1')];
        }
        return isArrayFieldName(node.name) ? [] : [''];
      }
      if (type === 'radio'){
        return node.checked ? [valueToString(node.value)] : [];
      }
      if (type === 'file'){
        return [];
      }
      return [valueToString(node.value)];
    }
    if (tag === 'select'){
      if (node.multiple){
        return Array.from(node.options || [])
          .filter((opt) => opt.selected)
          .map((opt) => valueToString(opt.value));
      }
      return [valueToString(node.value)];
    }
    if (tag === 'textarea'){
      return [valueToString(node.value)];
    }
    return [];
  }
  function syncAggregateAll(){
    const root = document.getElementById('pwpl-admin-v1-root') || document;
    const agg = ensureAggregateContainer();
    if (!root || !agg) return;
    const selector = [
      'input[name^="pwpl_table["]',
      'select[name^="pwpl_table["]',
      'textarea[name^="pwpl_table["]',
      'input[name^="pwpl_table_badges["]',
      'select[name^="pwpl_table_badges["]',
      'textarea[name^="pwpl_table_badges["]'
    ].join(',');
    const nodes = root.querySelectorAll(selector);
    const byName = new Map();
    nodes.forEach(function(node){
      if (!node || !node.name || node.disabled) return;
      if (agg.contains(node)) return;
      const values = collectNodeValues(node);
      if (!values.length) return;
      const name = node.name;
      if (!byName.has(name)) byName.set(name, []);
      const existing = byName.get(name);
      values.forEach((val) => existing.push(val));
    });
    byName.forEach(function(values, name){
      // Remove previous mirrors for this name
      agg.querySelectorAll('input[name="' + CSS.escape(name) + '"]').forEach(function(el){ el.remove(); });
      // Recreate full set
      values.forEach(function(v){
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = v;
        agg.appendChild(input);
      });
    });
  }
  function bindAggregateAutoSync(){
    const root = document.getElementById('pwpl-admin-v1-root') || document;
    // Initial sync
    try { syncAggregateAll(); } catch(e){}
    // Listen to value changes bubbling from inputs
    function onAnyInput(){ try { syncAggregateAll(); } catch(e){} }
    root.addEventListener('input', onAnyInput, true);
    root.addEventListener('change', onAnyInput, true);
    // Observe DOM for hidden inputs appearing/disappearing (tab switches)
    if ('MutationObserver' in window){
      const mo = new MutationObserver(function(){
        syncAggregateAll();
      });
      mo.observe(root, { childList: true, subtree: true });
    }
    // Ensure aggregate is present on submit
    const form = document.getElementById('post');
    if (form){
      form.addEventListener('submit', function(){ try { syncAggregateAll(); } catch(e){} }, true);
    }
  }

  // Shared preview state + event bus (no-op when preview disabled)
  w.PWPL_PreviewVars = w.PWPL_PreviewVars || {};
  function updatePreviewVars(patch){ if (!PREVIEW_ENABLED) return; try { Object.assign(w.PWPL_PreviewVars, patch || {}); } catch(e){} document.dispatchEvent(new CustomEvent('pwpl:v1:update')); }

  function setDeep(target, path, value){
    if (!path) return;
    const parts = path.split('.');
    let obj = target; for (let i=0;i<parts.length-1;i++){ const k=parts[i]; obj[k]=obj[k]||{}; obj=obj[k]; }
    obj[parts[parts.length-1]] = value;
  }

  function deepClone(obj){ try { return JSON.parse(JSON.stringify(obj||{})); } catch(e){ return {}; } }

  function useAccordionSections(storageKey){
    const [searchTerm, setSearchTerm] = useState('');
    const LEGACY_CARD_SECTION_IDS = ['layout-container', 'layout-spacing', 'layout-card-layout'];
    const [openSections, setOpenSections] = useState(() => {
      if (!storageKey) {
        return {};
      }
      try {
        const raw = w.localStorage ? w.localStorage.getItem(storageKey) : null;
        if (raw) {
          const parsed = JSON.parse(raw);
          if (Array.isArray(parsed)) {
            const map = parsed.reduce((acc, slug) => {
              if (typeof slug === 'string' && slug.length) {
                acc[slug] = true;
              }
              return acc;
            }, {});
            if (LEGACY_CARD_SECTION_IDS.some((slug) => map[slug])) {
              LEGACY_CARD_SECTION_IDS.forEach((slug) => { delete map[slug]; });
              map['layout-card'] = true;
            }
            return map;
          }
        }
      } catch (e) {}
      return {};
    });

    useEffect(() => {
      if (!storageKey) {
        return;
      }
      try {
        if (w.localStorage) {
          const list = Object.keys(openSections).filter((slug) => openSections[slug]);
          w.localStorage.setItem(storageKey, JSON.stringify(list));
        }
      } catch (e) {}
    }, [openSections, storageKey]);

    const normalizedSearch = (searchTerm || '').trim().toLowerCase();
    const matchesSearch = (title, keywords = []) => {
      if (!normalizedSearch) {
        return true;
      }
      const inTitle = (title || '').toLowerCase().includes(normalizedSearch);
      if (inTitle) {
        return true;
      }
      if (!Array.isArray(keywords) || !keywords.length) {
        return false;
      }
      return keywords.some((keyword) => (keyword || '').toLowerCase().includes(normalizedSearch));
    };

    const toggleSection = (slug) => {
      if (!slug) {
        return;
      }
      setOpenSections((prev) => {
        const next = Object.assign({}, prev);
        next[slug] = !next[slug];
        return next;
      });
    };

    const openFirstMatch = (sections = []) => {
      if (!sections.length) {
        return;
      }
      const target = sections.find((section) => matchesSearch(section.title, section && section.keywords));
      if (target) {
        setOpenSections((prev) => Object.assign({}, prev, { [target.id]: true }));
      }
    };

    return {
      searchTerm,
      setSearchTerm,
      openSections,
      matchesSearch,
      toggleSection,
      openFirstMatch,
      setOpenSections,
    };
  }

  function composeGradient(g){
    if (!g || !g.type || !g.start || !g.end) return '';
    const sp = isFinite(g.start_pos) ? g.start_pos : 0;
    const ep = isFinite(g.end_pos) ? g.end_pos : 100;
    const angle = isFinite(g.angle) ? g.angle : 180;
    switch (g.type){
      case 'radial': return `radial-gradient(circle, ${g.start} ${sp}%, ${g.end} ${ep}%)`;
      case 'conic': return `conic-gradient(${g.start} ${sp}%, ${g.end} ${ep}%)`;
      case 'linear':
      default: return `linear-gradient(${angle}deg, ${g.start} ${sp}%, ${g.end} ${ep}%)`;
    }
  }

  const CTA_FONT_PRESETS = {
    system: 'system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif',
    inter: '"Inter", system-ui, -apple-system, sans-serif',
    poppins: '"Poppins", system-ui, -apple-system, sans-serif',
    open_sans: '"Open Sans", system-ui, -apple-system, sans-serif',
    montserrat: '"Montserrat", system-ui, -apple-system, sans-serif',
    lato: '"Lato", system-ui, -apple-system, sans-serif',
    space_grotesk: '"Space Grotesk", system-ui, -apple-system, sans-serif',
    rubik: '"Rubik", system-ui, -apple-system, sans-serif',
  };

  const PREVIEW_THEME_PRESETS = {
    light: {
      surface: '#f8fafc',
      card: '#ffffff',
      headerBg: '#fff8e6',
      headerText: '#111827',
      text: '#111827',
      headerFont: 'system-ui, -apple-system, sans-serif',
      specsBg: '#f1f5f9',
      specsText: '#0f172a',
      border: '#d1d5db',
      accent: '#2563eb',
      accentText: '#f8fafc',
      focus: '#2563eb',
      muted: '#475569',
    },
    dark: {
      surface: '#0f172a',
      card: '#111827',
      headerBg: '#1e293b',
      headerText: '#e2e8f0',
      text: '#e2e8f0',
      headerFont: '"Inter", system-ui, sans-serif',
      specsBg: '#1e293b',
      specsText: '#cbd5f5',
      border: '#1f2937',
      accent: '#60a5fa',
      accentText: '#0f172a',
      focus: '#60a5fa',
      muted: '#94a3b8',
    },
  };

  function normalizeTracking(value){
    if (value == null) return '';
    const raw = String(value).trim();
    if (!raw) return '';
    if (/[a-z%]+$/i.test(raw)) return raw;
    if (/^[-+]?[0-9]*\.?[0-9]+$/.test(raw)) return raw + 'em';
    return raw;
  }

  function HiddenInput({ name, value }){
    return h('input', { type: 'hidden', name, value: value == null ? '' : value });
  }
  let PWPL_FIELD_ID = 0;
  function makeDomId(name, prefix = 'pwpl-field'){
    if (name) {
      return name.replace(/[^a-zA-Z0-9_-]/g, '_');
    }
    PWPL_FIELD_ID += 1;
    return `${prefix}-${PWPL_FIELD_ID}`;
  }
  const NUMERIC_PATTERN = /^-?\d+(?:\.\d+)?$/;
  function normalizeUnitless(value){
    if (value === undefined || value === null) {
      return '';
    }
    if (typeof value === 'number') {
      return String(value);
    }
    let raw = String(value).trim();
    if (!raw) {
      return '';
    }
    const pxMatch = raw.match(/^(-?\d+(?:\.\d+)?)px$/i);
    if (pxMatch) {
      return pxMatch[1];
    }
    return raw;
  }
  function toNumberOrToken(value){
    return normalizeUnitless(value);
  }
  function isNumericValue(value){
    const raw = normalizeUnitless(value);
    if (!raw) {
      return false;
    }
    return NUMERIC_PATTERN.test(raw);
  }
  function isToken(value){
    const raw = normalizeUnitless(value);
    if (!raw) {
      return false;
    }
    return !NUMERIC_PATTERN.test(raw);
  }
  function formatDisplay(value, suffix = 'px'){
    const raw = normalizeUnitless(value);
    if (!raw) {
      return '';
    }
    if (isToken(value) || !suffix) {
      return raw;
    }
    return `${raw}${suffix}`;
  }
  function clampNumber(value, min, max, step){
    let next = parseFloat(value);
    if (!Number.isFinite(next)) {
      next = Number.isFinite(min) ? Number(min) : 0;
    }
    if (Number.isFinite(min)) {
      next = Math.max(next, Number(min));
    }
    if (Number.isFinite(max)) {
      next = Math.min(next, Number(max));
    }
    if (Number.isFinite(step) && step > 0) {
      const decimals = String(step).includes('.') ? String(step).split('.')[1].length : 0;
      next = parseFloat(next.toFixed(decimals));
    }
    return next;
  }
  function RangeValueRow({
    label,
    name,
    value,
    onChange,
    min = 0,
    max = 100,
    step = 1,
    placeholder,
    unit = 'px',
    disabledWhenToken = true,
  }){
    const sanitizedValue = toNumberOrToken(value);
    const numericValue = isNumericValue(sanitizedValue) ? parseFloat(sanitizedValue) : (Number(min) || 0);
    const inputId = makeDomId(name);
    const labelId = `${inputId}__label`;
    const handleSliderChange = (next) => {
      if (typeof onChange !== 'function') {
        return;
      }
      const numeric = clampNumber(next, Number(min), Number(max), Number(step));
      onChange(numeric);
    };
    const handleInputChange = (event) => {
      if (typeof onChange !== 'function') {
        return;
      }
      const nextVal = event && event.target ? event.target.value : event;
      onChange(toNumberOrToken(nextVal));
    };
    const suffix = (!disabledWhenToken || !isToken(sanitizedValue)) ? (unit || '') : '';
    const hiddenValue = sanitizedValue;
    // percent fill for range track
    const pct = (Number(max) > Number(min)) ? ((numericValue - Number(min)) / (Number(max) - Number(min))) * 100 : 0;
    const fillPct = Math.max(0, Math.min(100, pct));
    return h('div', { className: 'pwpl-row' }, [
      h('div', { className: 'pwpl-row__left' }, [
        label ? h('label', { className: 'pwpl-row__label', htmlFor: inputId, id: labelId }, label) : null,
        h('div', { className: 'pwpl-range', style: { '--pwpl-fill': `${fillPct}%` } }, h(RangeControl, {
          label: null,
          value: numericValue,
          min,
          max,
          step,
          onChange: handleSliderChange,
          disabled: disabledWhenToken && isToken(sanitizedValue) && sanitizedValue !== '',
          'aria-labelledby': label ? labelId : undefined,
          withInputField: false,
        }))
      ]),
      h('div', { className: 'pwpl-row__control' }, [
        h('div', { className: 'pwpl-value-pill', 'data-suffix': (!isToken(sanitizedValue) ? suffix : '') }, [
          h('input', { id: inputId, type: 'text', value: hiddenValue, placeholder, onChange: handleInputChange })
        ]),
        name ? HiddenInput({ name, value: hiddenValue }) : null,
      ]),
    ]);
  }

  const BACKGROUND_TABS = [
    { id: 'color', label: 'Background Color' },
    { id: 'gradient', label: 'Background Gradient' },
  ];
  const GRADIENT_TYPE_OPTIONS = [
    { label: 'Linear', value: 'linear' },
    { label: 'Radial', value: 'radial' },
    { label: 'Conic', value: 'conic' },
  ];
  const clampPercent = (value, fallback = 0) => {
    const num = parseFloat(value);
    if (!Number.isFinite(num)) {
      return fallback;
    }
    return Math.max(0, Math.min(100, num));
  };
  const buildGradientPreviewStyle = ({
    type = 'linear',
    start,
    end,
    angle,
    startPos,
    endPos,
  } = {}) => {
    const startColor = start || '#2563eb';
    const endColor = end || '#34d399';
    const startStop = clampPercent(startPos, 0);
    const endStop = clampPercent(endPos, 100);
    const angleValue = Number.isFinite(parseFloat(angle)) ? parseFloat(angle) : 180;
    switch (type) {
      case 'radial':
        return {
          backgroundImage: `radial-gradient(circle, ${startColor} ${startStop}%, ${endColor} ${endStop}%)`,
        };
      case 'conic':
        return {
          backgroundImage: `conic-gradient(from ${angleValue}deg, ${startColor}, ${endColor})`,
        };
      default:
        return {
          backgroundImage: `linear-gradient(${angleValue}deg, ${startColor} ${startStop}%, ${endColor} ${endStop}%)`,
        };
    }
  };
  const DEFAULT_CUSTOM_COLOR = '#2563eb';

  function BackgroundColorPanel({
    label = 'Background color',
    value = '',
    onChange,
    autoOpen = false,
    onAfterSave,
    onAfterCancel,
  } = {}){
    const normalizedValue = normalizeColorValue(value);
    const parsed = parseCssColor(normalizedValue || DEFAULT_CUSTOM_COLOR);
    const initialHsv = rgbToHsv(parsed.r, parsed.g, parsed.b);
    const [isEditing, setIsEditing] = useState(false);
    const [draftHue, setDraftHue] = useState(initialHsv.h || 0);
    const [draftSat, setDraftSat] = useState(initialHsv.s || 0);
    const [draftVal, setDraftVal] = useState(initialHsv.v || 0);
    const [draftAlpha, setDraftAlpha] = useState(parsed.a != null ? parsed.a : 1);
    const [draggingCanvas, setDraggingCanvas] = useState(false);
    const canvasRef = useRef(null);
    const [hexField, setHexField] = useState(hsvToHex(initialHsv.h || 0, initialHsv.s || 0, initialHsv.v || 0).toUpperCase());
    const aRailRef = useRef(null);
    const satRailRef = useRef(null);
    const [copied, setCopied] = useState(false);
    const [showSaved, setShowSaved] = useState(false);
    const savedTickRef = useRef(null);
    const autoOpenRef = useRef(false);

    useEffect(() => {
      const normalized = normalizeColorValue(value);
      const col = parseCssColor(normalized || DEFAULT_CUSTOM_COLOR);
      const hsv = rgbToHsv(col.r, col.g, col.b);
      setDraftHue(hsv.h || 0);
      setDraftSat(hsv.s || 0);
      setDraftVal(hsv.v || 0);
      setDraftAlpha(col.a != null ? clampAlpha(col.a) : 1);
      setHexField(hsvToHex(hsv.h || 0, hsv.s || 0, hsv.v || 0).toUpperCase());
      if (!normalized) {
        setIsEditing(false);
      }
    }, [value]);

    const emitChange = (next) => {
      const normalized = normalizeColorValue(next);
      if (typeof onChange === 'function') {
        onChange(normalized);
      }
    };

    const openEditor = () => {
      const col = parseCssColor(normalizedValue || DEFAULT_CUSTOM_COLOR);
      const hsv = rgbToHsv(col.r, col.g, col.b);
      setDraftHue(hsv.h || 0);
      setDraftSat(hsv.s || 0);
      setDraftVal(hsv.v || 0);
      setDraftAlpha(col.a != null ? clampAlpha(col.a) : 1);
      setHexField(hsvToHex(hsv.h || 0, hsv.s || 0, hsv.v || 0).toUpperCase());
      try { document.body.classList.add('pwpl-bg-editing'); } catch(e) {}
      setIsEditing(true);
    };
    useEffect(() => {
      if (autoOpen && !autoOpenRef.current) {
        autoOpenRef.current = true;
        openEditor();
      }
    }, [autoOpen]);

    const handleAddClick = () => {
      openEditor();
    };

    const handleSwatchClick = (swatchValue) => {
      const normalized = normalizeColorValue(swatchValue);
      const col = parseCssColor(normalized || DEFAULT_CUSTOM_COLOR);
      const hsv = rgbToHsv(col.r, col.g, col.b);
      setDraftHue(hsv.h || 0);
      setDraftSat(hsv.s || 0);
      setDraftVal(hsv.v || 0);
      setDraftAlpha(col.a != null ? clampAlpha(col.a) : 1);
      setHexField(hsvToHex(hsv.h || 0, hsv.s || 0, hsv.v || 0).toUpperCase());
      emitChange(normalized);
      setIsEditing(false);
    };

    const handleSave = () => {
      const hex = hsvToHex(draftHue || 0, draftSat || 0, draftVal || 0);
      const out = (draftAlpha != null && draftAlpha < 1) ? rgbaFromHsv(draftHue || 0, draftSat || 0, draftVal || 0, draftAlpha) : hex;
      emitChange(out);
      if (savedTickRef.current) {
        clearTimeout(savedTickRef.current);
      }
      setShowSaved(true);
      savedTickRef.current = setTimeout(() => setShowSaved(false), 1500);
      setCopied(false);
      try { document.body.classList.remove('pwpl-bg-editing'); } catch(e) {}
      setIsEditing(false);
      if (typeof onAfterSave === 'function') {
        onAfterSave(out);
      }
    };

    const handleCancel = () => {
      const col = parseCssColor(normalizedValue || DEFAULT_CUSTOM_COLOR);
      const hsv = rgbToHsv(col.r, col.g, col.b);
      setDraftHue(hsv.h || 0);
      setDraftSat(hsv.s || 0);
      setDraftVal(hsv.v || 0);
      setDraftAlpha(col.a != null ? clampAlpha(col.a) : 1);
      setHexField(hsvToHex(hsv.h || 0, hsv.s || 0, hsv.v || 0).toUpperCase());
      setShowSaved(false);
      setCopied(false);
      try { document.body.classList.remove('pwpl-bg-editing'); } catch(e) {}
      setIsEditing(false);
      if (typeof onAfterCancel === 'function') {
        onAfterCancel();
      }
    };

    const handleFillClick = () => {
      if (!isEditing) {
        openEditor();
      }
    };

    const rawHue = Number.isFinite(draftHue) ? draftHue : 0;
    const safeHue = ((rawHue % 360) + 360) % 360;
    const pointerHue = Math.max(0, Math.min(360, rawHue));
    const safeSat = clamp01(draftSat || 0);
    const safeVal = clamp01(draftVal || 0);
    const safeAlpha = clamp01(draftAlpha != null ? draftAlpha : 1);
    const currentDraftHex = hsvToHex(safeHue, safeSat, safeVal);
    const currentDraftHex8 = hsvToHex8(safeHue, safeSat, safeVal, safeAlpha).toUpperCase();
    const currentDraftRgba = rgbaFromHsv(safeHue, safeSat, safeVal, safeAlpha);
    useEffect(() => {
      setHexField(currentDraftHex.toUpperCase());
    }, [currentDraftHex]);

    const valueMatchesSwatch = DEFAULT_COLOR_SWATCHES.some((swatch) => {
      if (!swatch.value || !normalizedValue) {
        return false;
      }
      return swatch.value.toLowerCase() === normalizedValue.toLowerCase();
    });
    const isCustomActive = isEditing || (!!normalizedValue && !valueMatchesSwatch);

    const handleCanvasPointer = (event) => {
      if (!canvasRef || !canvasRef.current) {
        return;
      }
      const rect = canvasRef.current.getBoundingClientRect();
      const x = clamp01((event.clientX - rect.left) / rect.width);
      const y = clamp01((event.clientY - rect.top) / rect.height);
      // Panel X controls Hue (0..360), Y controls Brightness/Value (1..0)
      setDraftHue(x * 360);
      setDraftVal(1 - y);
    };

    const handleCanvasPointerDown = (event) => {
      event.preventDefault();
      if (!isEditing) {
        openEditor();
      }
      setDraggingCanvas(true);
      handleCanvasPointer(event);
    };

    useEffect(() => {
      if (!draggingCanvas) {
        return undefined;
      }
      const handleMove = (event) => handleCanvasPointer(event);
      const handleUp = () => setDraggingCanvas(false);
      document.addEventListener('pointermove', handleMove);
      document.addEventListener('pointerup', handleUp);
      return () => {
        document.removeEventListener('pointermove', handleMove);
        document.removeEventListener('pointerup', handleUp);
      };
    }, [draggingCanvas]);

    // Rail pointer utilities
    const getRailValue = (event, ref) => {
      if (!ref || !ref.current) return 0;
      const rect = ref.current.getBoundingClientRect();
      let y = (event.clientY - rect.top) / rect.height;
      y = Math.max(0, Math.min(1, y));
      return y;
    };
    const [dragSatRail, setDragSatRail] = useState(false);
    const [dragAlphaRail, setDragAlphaRail] = useState(false);
    const onSatDown = (event) => {
      event.preventDefault();
      event.stopPropagation();
      setDragSatRail(true);
      const y = getRailValue(event, satRailRef);
      setDraftSat(1 - y);
    };
    const onAlphaDown = (event) => {
      event.preventDefault();
      event.stopPropagation();
      setDragAlphaRail(true);
      const y = getRailValue(event, aRailRef);
      setDraftAlpha(1 - y);
    };
    useEffect(() => {
      if (!dragSatRail && !dragAlphaRail) {
        return undefined;
      }
      const handleMove = (event) => {
        if (dragSatRail) {
          const y = getRailValue(event, satRailRef);
          setDraftSat(1 - y);
        }
        if (dragAlphaRail) {
          const y = getRailValue(event, aRailRef);
          setDraftAlpha(1 - y);
        }
      };
      const handleUp = () => {
        setDragSatRail(false);
        setDragAlphaRail(false);
      };
      document.addEventListener('pointermove', handleMove);
      document.addEventListener('pointerup', handleUp);
      return () => {
        document.removeEventListener('pointermove', handleMove);
        document.removeEventListener('pointerup', handleUp);
      };
    }, [dragSatRail, dragAlphaRail]);

    useEffect(() => () => { try { document.body.classList.remove('pwpl-bg-editing'); } catch(e) {} }, []);

    const handleHexFieldChange = (event) => {
      const raw = event && event.target ? event.target.value : '';
      setHexField(raw.toUpperCase());
      const normalized = normalizeColorValue(raw);
      if (/^#[0-9A-Fa-f]{6}$/.test(normalized)) {
        const hsv = hexToHsv(normalized);
        setDraftHue(hsv.h || 0);
        setDraftSat(hsv.s || 0);
        setDraftVal(hsv.v || 0);
      }
    };

    const swatchButtons = DEFAULT_COLOR_SWATCHES.map((swatch) => {
      const swatchValue = swatch.value || '';
      const isActive = (normalizedValue || '') === swatchValue;
      const className = classNames('pwpl-bgctrl__swatch', isActive ? 'is-active' : '', !swatchValue ? 'is-none' : '');
      return h('button', {
        key: `bgcolor-${swatch.label}`,
        type: 'button',
        className,
        style: swatchValue ? { backgroundColor: swatchValue } : undefined,
        'aria-pressed': isActive,
        onClick: () => handleSwatchClick(swatchValue),
        title: swatch.value ? `${swatch.label} (${swatch.value})` : 'No color',
      }, swatch.value ? null : h('span', { className: 'pwpl-bgctrl__swatch-clear', 'aria-hidden': 'true' }, '×'));
    });

    // Editor-specific swatches: apply to draft (do not commit / close)
    const applyDraftFromColor = (colorValue) => {
      const normalized = normalizeColorValue(colorValue);
      const col = parseCssColor(normalized || DEFAULT_CUSTOM_COLOR);
      const hsv = rgbToHsv(col.r, col.g, col.b);
      setDraftHue(hsv.h || 0);
      setDraftSat(hsv.s || 0);
      setDraftVal(hsv.v || 0);
      setDraftAlpha(col.a != null ? clampAlpha(col.a) : 1);
      setHexField(hsvToHex(hsv.h || 0, hsv.s || 0, hsv.v || 0).toUpperCase());
    };
    const swatchButtonsEditing = DEFAULT_COLOR_SWATCHES.map((swatch) => {
      const swatchValue = swatch.value || '';
      const isActive = (!!swatchValue && swatchValue.toLowerCase() === currentDraftHex.toLowerCase());
      const className = classNames('pwpl-bgctrl__swatch', isActive ? 'is-active' : '', !swatchValue ? 'is-none' : '');
      return h('button', {
        key: `bgcolor-edit-${swatch.label}`,
        type: 'button',
        className,
        style: swatchValue ? { backgroundColor: swatchValue } : undefined,
        'aria-pressed': isActive,
        onClick: () => applyDraftFromColor(swatchValue),
        title: swatch.value ? `${swatch.label} (${swatch.value})` : 'No color',
      }, swatch.value ? null : h('span', { className: 'pwpl-bgctrl__swatch-clear', 'aria-hidden': 'true' }, '×'));
    });

    const rootClass = classNames('pwpl-bgctrl__color', isEditing ? 'is-editing' : '');
    const baseHueBand = 'linear-gradient(90deg, #f00 0%, #ff0 16%, #0f0 33%, #0ff 50%, #00f 66%, #f0f 83%, #f00 100%)';
    const valueOverlay = 'linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,1) 100%)';
    const satFade = Math.max(0, Math.min(1, 1 - safeSat));
    const satOverlay = `linear-gradient(0deg, rgba(255,255,255,${satFade}), rgba(255,255,255,${satFade}))`;
    // Order matters: top-most first. Put saturation overlay on top,
    // then fixed value darkening, with the hue band at the bottom.
    const pickerBg = [satOverlay, valueOverlay, baseHueBand].join(', ');
    const pointerStyle = {
      left: `${(pointerHue / 360) * 100}%`,
      top: `${(1 - safeVal) * 100}%`,
    };
    // Show HEX for both cases; if alpha < 1 use 8-digit HEX (#RRGGBBAA)
    const displayValue = safeAlpha < 1 ? currentDraftHex8 : hexField;
    const saturationGradientStart = hsvToHex(safeHue, 1, safeVal);
    const saturationTrack = `linear-gradient(to bottom, ${saturationGradientStart}, #ffffff)`;
    const alphaGradient = `linear-gradient(to bottom, ${rgbaFromHsv(safeHue, safeSat, safeVal, 1)}, ${rgbaFromHsv(safeHue, safeSat, safeVal, 0)})`;

    const copyText = (text) => {
      if (!text) return false;
      const markCopied = () => { setCopied(true); setTimeout(() => setCopied(false), 1200); };
      const fallback = (val) => {
        try {
          const el = document.createElement('textarea');
          el.value = String(val || '');
          el.setAttribute('readonly', '');
          el.style.position = 'absolute';
          el.style.left = '-9999px';
          document.body.appendChild(el);
          el.select();
          el.setSelectionRange(0, el.value.length);
          document.execCommand('copy');
          document.body.removeChild(el);
          markCopied();
          return true;
        } catch (e) { return false; }
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
          markCopied();
        }).catch(() => { fallback(text); });
        return true;
      }
      return fallback(text);
    };

    const handleCopyValue = (event) => {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }
      copyText(displayValue);
    };
    const handleCopyOverlayValue = (event) => {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }
      const base = normalizedValue || currentDraftHex;
      const col = parseCssColor(base);
      const out = (col.a != null && col.a < 1)
        ? rgbaToHex8(col.r, col.g, col.b, col.a).toUpperCase()
        : rgbToHex({ r: col.r, g: col.g, b: col.b }).toUpperCase();
      copyText(out);
    };

    const handlePanelKeyDown = (event) => {
      if (!isEditing) {
        return;
      }
      const stepHue = 4;
      const stepVal = 0.02;
      switch (event.key) {
        case 'ArrowRight':
          event.preventDefault();
          setDraftHue((prev) => (((prev || 0) + stepHue) % 360));
          break;
        case 'ArrowLeft':
          event.preventDefault();
          setDraftHue((prev) => {
            const next = ((prev || 0) - stepHue);
            return next < 0 ? (next + 360) : next;
          });
          break;
        case 'ArrowUp':
          event.preventDefault();
          setDraftVal((prev) => Math.min(1, (prev || 0) + stepVal));
          break;
        case 'ArrowDown':
          event.preventDefault();
          setDraftVal((prev) => Math.max(0, (prev || 0) - stepVal));
          break;
        case 'Escape':
          event.preventDefault();
          handleCancel();
          break;
        case 'Enter':
          event.preventDefault();
          handleSave();
          break;
        default:
          break;
      }
    };

    useEffect(() => () => {
      if (savedTickRef.current) {
        clearTimeout(savedTickRef.current);
      }
    }, []);

    return h('div', { className: rootClass }, [
      h('div', {
        ref: canvasRef,
        className: classNames('pwpl-bgctrl__fill', isEditing ? 'is-editing' : ''),
        style: isEditing ? { backgroundImage: pickerBg } : { background: normalizedValue || '#f8fafc' },
        onClick: (!isEditing) ? handleFillClick : undefined,
        onPointerDown: isEditing ? handleCanvasPointerDown : undefined,
        role: isEditing ? 'application' : 'button',
        tabIndex: 0,
        onKeyDown: isEditing ? handlePanelKeyDown : (event) => {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            handleFillClick();
          }
        },
      }, isEditing ? [
        h('div', {
          className: 'pwpl-bgctrl__pill-row',
          onPointerDown: (event) => event.stopPropagation(),
        }, [
          h('span', {
            className: 'pwpl-bgctrl__swatch-preview',
            'aria-hidden': 'true',
          }, h('span', {
            className: 'pwpl-bgctrl__swatch-preview-fill',
            style: { background: currentDraftRgba },
          })),
          h('button', {
            type: 'button',
            className: classNames('pwpl-bgctrl__pill', copied ? 'is-copied' : '', showSaved ? 'is-saved' : ''),
            onClick: handleCopyValue,
            onPointerUp: handleCopyValue,
            title: 'Click to copy color',
          }, [
            h('span', { className: 'pwpl-bgctrl__pill-value' }, displayValue),
            showSaved ? h('span', { className: 'pwpl-bgctrl__pill-tick', 'aria-hidden': 'true' }, '✓') : null,
            copied ? h('span', { className: 'pwpl-bgctrl__pill-copy' }, 'Copied!') : null,
          ]),
        ]),
        h('div', { className: 'pwpl-bgctrl__rails' }, [
          h('div', {
            className: 'pwpl-bgctrl__rail pwpl-bgctrl__rail--sat',
            ref: satRailRef,
            onPointerDown: onSatDown,
            tabIndex: 0,
            role: 'slider',
            'aria-label': 'Saturation',
            'aria-valuemin': 0,
            'aria-valuemax': 100,
            'aria-valuenow': Math.round(safeSat * 100),
            onKeyDown: (event) => {
              if (event.key === 'ArrowUp') {
                event.preventDefault();
                setDraftSat((prev) => Math.min(1, (prev || 0) + 0.05));
              } else if (event.key === 'ArrowDown') {
                event.preventDefault();
                setDraftSat((prev) => Math.max(0, (prev || 0) - 0.05));
              }
            },
            style: { backgroundImage: saturationTrack },
          }, h('span', { className: 'pwpl-bgctrl__rail-thumb', style: { top: `${(1 - safeSat) * 100}%` } })),
          h('div', {
            className: 'pwpl-bgctrl__rail pwpl-bgctrl__rail--alpha',
            ref: aRailRef,
            onPointerDown: onAlphaDown,
            tabIndex: 0,
            role: 'slider',
            'aria-label': 'Alpha',
            'aria-valuemin': 0,
            'aria-valuemax': 100,
            'aria-valuenow': Math.round(safeAlpha * 100),
            onKeyDown: (event) => {
              if (event.key === 'ArrowUp') {
                event.preventDefault();
                setDraftAlpha((prev) => Math.min(1, (prev || 0) + 0.05));
              } else if (event.key === 'ArrowDown') {
                event.preventDefault();
                setDraftAlpha((prev) => Math.max(0, (prev || 0) - 0.05));
              }
            },
          }, [
            h('span', { className: 'pwpl-bgctrl__rail-check' }),
            h('span', { className: 'pwpl-bgctrl__rail-overlay', style: { backgroundImage: alphaGradient } }),
            h('span', { className: 'pwpl-bgctrl__rail-thumb', style: { top: `${(1 - safeAlpha) * 100}%` } }),
          ]),
        ]),
        h('span', { className: 'pwpl-bgctrl__pointer', style: pointerStyle }),
      ] : (normalizedValue ?
        h('button', {
          type: 'button',
          className: classNames('pwpl-bgctrl__pill','pwpl-bgctrl__pill--overlay', copied ? 'is-copied' : ''),
          onPointerDown: (e) => e.stopPropagation(),
          onClick: handleCopyOverlayValue,
          onPointerUp: handleCopyOverlayValue,
          title: 'Click to copy color',
        }, [
          (function(){
            const col = parseCssColor(normalizedValue);
            const label = (col.a != null && col.a < 1)
              ? rgbaToHex8(col.r, col.g, col.b, col.a).toUpperCase()
              : rgbToHex({ r: col.r, g: col.g, b: col.b }).toUpperCase();
            return h('span', { className: 'pwpl-bgctrl__pill-value' }, label);
          })(),
          copied ? h('span', { className: 'pwpl-bgctrl__pill-copy' }, 'Copied!') : null,
        ]) :
        h('button', {
          type: 'button',
          className: 'pwpl-bgctrl__add-btn',
          onClick: handleAddClick,
        }, '+ ' + (label ? `Add ${label.toLowerCase()}` : 'Add color'))
      )),
      isEditing ? [
        h('div', { className: 'pwpl-bgctrl__swatches-row' }, [
          h('button', {
            type: 'button',
            className: classNames('pwpl-bgctrl__swatch', 'is-custom', isCustomActive ? 'is-active' : ''),
            onClick: handleAddClick,
            'aria-pressed': isCustomActive,
          }, 'Custom'),
          ...swatchButtonsEditing,
        ]),
        h('div', { className: 'pwpl-bgctrl__actions' }, [
          h('button', { type: 'button', className: 'button button-primary pwpl-bgctrl__action', onClick: handleSave }, 'Save color'),
          h('button', { type: 'button', className: 'button pwpl-bgctrl__action', onClick: handleCancel }, 'Cancel'),
        ])
      ] : h('div', { className: 'pwpl-bgctrl__swatches-row' }, [
        h('button', {
          type: 'button',
          className: classNames('pwpl-bgctrl__swatch', 'is-custom', isCustomActive ? 'is-active' : ''),
          onClick: handleAddClick,
          'aria-pressed': isCustomActive,
        }, 'Custom'),
        ...swatchButtons,
      ]),
    ]);
  }

  function BackgroundTabsSection({
    id,
    label = '',
    colorLabel = '',
    colorValue = '',
    onColorChange,
    colorName,
    gradient = {},
  } = {}){
    const gradientNames = gradient.names || {};
    const gradientType = gradient.type || '';
    const defaultType = gradient.defaultType || 'linear';
    const [activeTab, setActiveTab] = useState(gradientType ? 'gradient' : 'color');

    useEffect(() => {
      const next = gradientType ? 'gradient' : 'color';
      if (next !== activeTab) {
        setActiveTab(next);
      }
    }, [gradientType]);

    const handleTabChange = (tabId) => {
      if (tabId === activeTab) {
        return;
      }
      if (tabId === 'color') {
        setActiveTab('color');
        if (typeof gradient.onTypeChange === 'function') {
          gradient.onTypeChange('');
        }
        return;
      }
      setActiveTab('gradient');
      if (!gradient.type && typeof gradient.onTypeChange === 'function') {
        gradient.onTypeChange(defaultType);
      }
    };

    const colorPanel = h('div', { className: 'pwpl-bgctrl__panel is-color' }, [
      h(BackgroundColorPanel, {
        label: colorLabel || 'Background color',
        value: colorValue,
        onChange: onColorChange,
      }),
    ]);

    const gradientActive = activeTab === 'gradient' && !!gradient.type;
    const gradientPreviewStyle = buildGradientPreviewStyle({
      type: gradient.type || defaultType,
      start: gradient.start,
      end: gradient.end,
      angle: gradient.angle,
      startPos: gradient.startPos,
      endPos: gradient.endPos,
    });
    const gradientOptions = gradient.typeOptions || GRADIENT_TYPE_OPTIONS;
    const previewLabel = (!gradient.start && !gradient.end) ? 'Add gradient colors' : null;
    const renderRangeControl = (config, shouldRender) => {
      if (!config || !config.name) {
        return null;
      }
      if (shouldRender) {
        return RangeValueRow(config);
      }
      return HiddenInput({ name: config.name, value: '' });
    };
    const gradientPanel = h('div', { className: 'pwpl-bgctrl__panel is-gradient' }, [
      h('div', { className: 'pwpl-bgctrl__preview', style: gradientPreviewStyle },
        previewLabel ? h('span', { className: 'pwpl-bgctrl__preview-text' }, previewLabel) : null
      ),
      h('div', { className: 'pwpl-bgctrl__gradient-swatches' }, [
        h('div', { className: 'pwpl-bgctrl__gradient-field' },
          h(ColorPaletteControl, {
            label: 'Start color',
            value: gradient.start || '',
            onChange: gradient.onStartChange,
            allowAlpha: true,
            className: 'pwpl-inlinecolor--compact',
          })
        ),
        h('div', { className: 'pwpl-bgctrl__gradient-field' },
          h(ColorPaletteControl, {
            label: 'End color',
            value: gradient.end || '',
            onChange: gradient.onEndChange,
            allowAlpha: true,
            className: 'pwpl-inlinecolor--compact',
          })
        ),
      ]),
      h('div', { className: 'pwpl-bgctrl__field' }, [
        h('label', { className: 'components-base-control__label' }, 'Gradient Type'),
        h('select', {
          className: 'pwpl-v1-select',
          value: gradient.type || defaultType,
          onChange: (event) => {
            const nextType = event && event.target ? event.target.value : '';
            if (typeof gradient.onTypeChange === 'function') {
              gradient.onTypeChange(nextType);
            }
          },
        }, gradientOptions.map((option) =>
          h('option', { key: option.value, value: option.value }, option.label)
        )),
      ]),
      renderRangeControl({
        label: 'Gradient direction',
        name: gradientNames.angle,
        value: gradient.angle,
        onChange: gradient.onAngleChange,
        min: 0,
        max: 360,
        step: 1,
        unit: '°',
      }, gradientActive && gradient.showAngle),
      renderRangeControl({
        label: 'Start position (%)',
        name: gradientNames.startPos,
        value: gradient.startPos,
        onChange: gradient.onStartPosChange,
        min: 0,
        max: 100,
        step: 1,
        unit: '%',
      }, gradientActive),
      renderRangeControl({
        label: 'End position (%)',
        name: gradientNames.endPos,
        value: gradient.endPos,
        onChange: gradient.onEndPosChange,
        min: 0,
        max: 100,
        step: 1,
        unit: '%',
      }, gradientActive),
    ]);

    const tabs = h('div', { className: 'pwpl-bgctrl__tabs', role: 'tablist' },
      BACKGROUND_TABS.map((tab) => h('button', {
        key: tab.id,
        type: 'button',
        role: 'tab',
        className: classNames('pwpl-bgctrl__tab', activeTab === tab.id ? 'is-active' : ''),
        'aria-selected': activeTab === tab.id,
        onClick: () => handleTabChange(tab.id),
      }, tab.label))
    );

    const hiddenFields = [
      colorName ? HiddenInput({ name: colorName, value: colorValue || '' }) : null,
      gradientNames.type ? HiddenInput({ name: gradientNames.type, value: gradientActive ? (gradient.type || defaultType) : '' }) : null,
      gradientNames.start ? HiddenInput({ name: gradientNames.start, value: gradientActive ? (gradient.start || '') : '' }) : null,
      gradientNames.end ? HiddenInput({ name: gradientNames.end, value: gradientActive ? (gradient.end || '') : '' }) : null,
    ];

    return h('div', { className: 'pwpl-bgctrl', id }, [
      h('div', { className: 'pwpl-bgctrl__head' }, [
        h('span', { className: 'pwpl-bgctrl__title' }, label || 'Background'),
      ]),
      tabs,
      activeTab === 'gradient' ? gradientPanel : colorPanel,
      ...hiddenFields,
    ]);
  }

  const ENABLE_TYPO_STYLE_FLAGS = true;
  const ENABLE_TYPO_ALIGNMENT = true;
  const ENABLE_TYPO_TRACKING = true;
  const ENABLE_TYPO_LINE_HEIGHT = true;

  const TYPO_WEIGHT_OPTIONS = [
    { label: 'Default (inherit)', value: '' },
    { label: 'Thin · 100', value: '100' },
    { label: 'ExtraLight · 200', value: '200' },
    { label: 'Light · 300', value: '300' },
    { label: 'Regular · 400', value: '400' },
    { label: 'Medium · 500', value: '500' },
    { label: 'Semibold · 600', value: '600' },
    { label: 'Bold · 700', value: '700' },
    { label: 'ExtraBold · 800', value: '800' },
    { label: 'Black · 900', value: '900' },
  ];
  const CTA_WEIGHT_OPTIONS = [
    { label: 'Regular (400)', value: '400' },
    { label: 'Medium (500)', value: '500' },
    { label: 'Semibold (600)', value: '600' },
    { label: 'Bold (700)', value: '700' },
    { label: 'ExtraBold (800)', value: '800' },
  ];
  const CTA_FONT_OPTIONS = [
    { label: 'Custom', value: '' },
    { label: 'System UI', value: 'system' },
    { label: 'Inter', value: 'inter' },
    { label: 'Poppins', value: 'poppins' },
    { label: 'Open Sans', value: 'open_sans' },
    { label: 'Montserrat', value: 'montserrat' },
    { label: 'Lato', value: 'lato' },
    { label: 'Space Grotesk', value: 'space_grotesk' },
    { label: 'Rubik', value: 'rubik' },
  ];
  const CUSTOM_WEIGHT_OPTION = { label: 'Custom value…', value: '__custom__' };
  const DEFAULT_COLOR_SWATCHES = [
    { label: 'Ink', value: '#0f172a' },
    { label: 'Night', value: '#000000' },
    { label: 'Paper', value: '#ffffff' },
    { label: 'Mist', value: '#e2e8f0' },
    { label: 'Coral', value: '#f97316' },
    { label: 'Sun', value: '#facc15' },
    { label: 'Lime', value: '#84cc16' },
    { label: 'Sea', value: '#0ea5e9' },
    { label: 'Iris', value: '#6366f1' },
    { label: 'Violet', value: '#a855f7' },
    { label: 'No color', value: '' },
  ];
  const shadowColorLayer = (color, alphaOverride) => {
    const parsed = parseCssColor(color || '');
    const finalAlpha = alphaOverride != null ? alphaOverride : (parsed.a != null ? parsed.a : 1);
    return `rgba(${parsed.r}, ${parsed.g}, ${parsed.b}, ${finalAlpha})`;
  };
  const buildGlowShadow = (color) => {
    return `0 0 6px ${shadowColorLayer(color, 0.6)}, 0 0 12px ${shadowColorLayer(color, 0.35)}`;
  };
  const buildLongShadow = (color) => {
    return `2px 2px 0 ${shadowColorLayer(color, 0.25)}, 4px 4px 0 ${shadowColorLayer(color, 0.18)}, 6px 6px 0 ${shadowColorLayer(color, 0.12)}`;
  };
  const TEXT_SHADOW_PRESETS = {
    none: { single: false, type: 'none' },
    soft: { single: true, x: 0, y: 2, blur: 4 },
    medium: { single: true, x: 0, y: 4, blur: 8 },
    deep: { single: true, x: 0, y: 6, blur: 12 },
    glow: { single: false, builder: buildGlowShadow },
    long: { single: false, builder: buildLongShadow },
  };
  function InlineColorPalette(props = {}) {
    const {
      label,
      value,
      onChange,
      allowAlpha = true,
      className = '',
    } = props;
    const normalizedValue = normalizeColorValue(value, allowAlpha) || '';
    const [isEditing, setIsEditing] = useState(false);
    const previewColor = normalizedValue ? rgbaString(parseCssColor(normalizedValue)) : 'transparent';
    const normalizedLower = normalizedValue.toLowerCase();
    const commit = (nextValue) => {
      const normalized = normalizeColorValue(nextValue, allowAlpha);
      if (typeof onChange === 'function') {
        onChange(normalized);
      }
    };
    const handleOpen = () => setIsEditing(true);
    const handleClose = () => setIsEditing(false);
    const handleSwatchClick = (swatchValue) => {
      commit(swatchValue || '');
      setIsEditing(false);
    };
    const hasSwatchMatch = DEFAULT_COLOR_SWATCHES.some((swatch) => {
      const swatchValue = normalizeColorValue(swatch.value || '', allowAlpha);
      return swatchValue.toLowerCase() === normalizedLower;
    });
    const swatchButtons = DEFAULT_COLOR_SWATCHES.map((swatch) => {
      const swatchValue = swatch.value || '';
      const normalizedSwatch = normalizeColorValue(swatchValue, allowAlpha);
      const isActive = normalizedSwatch.toLowerCase() === normalizedLower;
      const btnClass = classNames('pwpl-bgctrl__swatch', isActive ? 'is-active' : '', !swatchValue ? 'is-none' : '');
      return h('button', {
        key: swatchValue || swatch.label || 'none',
        type: 'button',
        className: btnClass,
        style: swatchValue ? { backgroundColor: swatchValue } : undefined,
        'aria-pressed': isActive,
        onClick: () => handleSwatchClick(swatchValue),
        title: swatchValue ? `${swatch.label} (${swatchValue})` : 'No color',
      }, swatch.value ? null : h('span', { className: 'pwpl-bgctrl__swatch-clear', 'aria-hidden': 'true' }, '×'));
    });
    const customButton = h('button', {
      type: 'button',
      className: classNames('pwpl-bgctrl__swatch', 'is-custom', (!hasSwatchMatch || isEditing) ? 'is-active' : ''),
      onClick: handleOpen,
      'aria-pressed': !hasSwatchMatch || isEditing,
    }, 'Custom');
    const containerExtra = (className ? className : '') + (isEditing ? ((className ? ' ' : '') + 'is-open') : '');
    return h('div', { className: classNames('pwpl-inlinecolor', containerExtra) }, [
      label ? h('div', { className: 'pwpl-inlinecolor__label' }, label) : null,
      h('button', {
        type: 'button',
        className: 'pwpl-inlinecolor__preview',
        onClick: handleOpen,
        'aria-label': label ? `${label} color` : 'Edit color',
      }, h('span', { className: 'pwpl-inlinecolor__preview-fill', style: { background: previewColor } })),
      isEditing ? h('div', { className: 'pwpl-inlinecolor__editor' },
        h(BackgroundColorPanel, {
          label,
          value: normalizedValue,
          onChange: commit,
          autoOpen: true,
          onAfterSave: handleClose,
          onAfterCancel: handleClose,
        })
      ) : h('div', { className: 'pwpl-inlinecolor__swatches' }, [
        ...swatchButtons,
        customButton,
      ]),
    ]);
  }
  ColorPaletteControl = InlineColorPalette;
  w.PWPL_ColorPalette = InlineColorPalette;

  function SegmentedButtonGroup({ items = [], role = 'group', className = '', ariaLabel }){
    if (!items.length) {
      return null;
    }
    const containerProps = {
      className: classNames('pwpl-typo__seg', className),
    };
    if (role) {
      containerProps.role = role;
    }
    if (ariaLabel) {
      containerProps['aria-label'] = ariaLabel;
    }
    const handleActivate = (event, handler, value) => {
      if (typeof handler !== 'function') {
        return;
      }
      event.preventDefault();
      handler(value);
    };
    const handleKeyDown = (event, handler, value) => {
      if (event.key === ' ' || event.key === 'Enter') {
        event.preventDefault();
        if (typeof handler === 'function') {
          handler(value);
        }
      }
    };
    return h('div', containerProps,
      items.map((item) => {
        if (!item) {
          return null;
        }
        const buttonProps = {
          type: 'button',
          key: item.id || item.value || item.label,
          className: classNames('pwpl-typo__btn', item.isActive ? 'is-active' : ''),
          title: item.title || item.ariaLabel || item.label,
          onClick: (event) => handleActivate(event, item.onClick, item.value),
          onKeyDown: (event) => handleKeyDown(event, item.onClick, item.value),
          'aria-label': item.ariaLabel || item.title || item.label,
        };
        if (role === 'radiogroup') {
          buttonProps.role = 'radio';
          buttonProps['aria-checked'] = !!item.isActive;
        } else {
          buttonProps['aria-pressed'] = !!item.isActive;
        }
        return h('button', buttonProps, item.content || h('span', { className: 'pwpl-typo__btn-text', 'aria-hidden': 'true' }, item.label));
      })
    );
  }

  function TypographySection({
    idKey = '',
    label = '',
    names = {},
    values = {},
    onPreviewPatch = {},
    fontOptions = [],
    sizeRange = {},
    trackingRange = {},
    lineHeightRange = {},
    showColor = true,
    showTracking = true,
    showLineHeight = true,
    showAlignment = true,
    showStyle = true,
    weightOptions = TYPO_WEIGHT_OPTIONS,
    flagValueMap = {},
    layoutVariant = '',
  } = {}){
    const resolvedSizeRange = Object.assign({ min: 8, max: 120, step: 1, unit: 'px', placeholder: 'inherit' }, sizeRange || {});
    const resolvedTrackingRange = Object.assign({ min: -2, max: 20, step: 0.5, unit: 'px', placeholder: '0px' }, trackingRange || {});
    const resolvedLineHeightRange = Object.assign({ min: 0.8, max: 3, step: 0.05, unit: '', placeholder: '1.2' }, lineHeightRange || {});
    const [color, setColor] = useState(values.color || '');
    const [family, setFamily] = useState(values.family || '');
    const [weight, setWeight] = useState(toNumberOrToken(values.weight));
    const [size, setSize] = useState(toNumberOrToken(values.size));
    const [tracking, setTracking] = useState(toNumberOrToken(values.tracking));
    const [lineHeight, setLineHeight] = useState(toNumberOrToken(values.lineHeight));
    const [align, setAlign] = useState(values.align || '');

    const getFlagMap = (fieldKey) => (flagValueMap[fieldKey] || { on: '1', off: '' });
    const resolveFlagInitial = (fieldKey) => {
      const map = getFlagMap(fieldKey);
      const raw = values[fieldKey];
      if (raw === undefined || raw === null || raw === '') {
        return map.off;
      }
      return String(raw);
    };
    const [italic, setItalic] = useState(resolveFlagInitial('italic'));
    const [uppercase, setUppercase] = useState(resolveFlagInitial('uppercase'));
    const [smallcaps, setSmallcaps] = useState(resolveFlagInitial('smallcaps'));
    const [underline, setUnderline] = useState(resolveFlagInitial('underline'));
    const [strike, setStrike] = useState(resolveFlagInitial('strike'));
    const emitPreview = (fieldKey, nextValue) => {
      const previewKey = onPreviewPatch[fieldKey];
      if (previewKey) {
        updatePreviewVars({ [previewKey]: nextValue });
      }
    };
    const handleColorChange = (next) => {
      const normalized = typeof next === 'string' ? next : '';
      setColor(normalized);
      emitPreview('color', normalized);
    };
    const handleFamilyChange = (event) => {
      const next = event && event.target ? event.target.value : event;
      const normalized = next == null ? '' : String(next);
      setFamily(normalized);
      emitPreview('family', normalized);
    };
    const handleWeightChange = (event) => {
      const next = event && event.target ? event.target.value : event;
      if (next === '__custom__') {
        return;
      }
      const normalized = toNumberOrToken(next);
      setWeight(normalized);
      emitPreview('weight', normalized);
    };
    const handleCustomWeightChange = (event) => {
      const value = event && event.target ? event.target.value : event;
      const normalized = toNumberOrToken(value);
      setWeight(normalized);
      emitPreview('weight', normalized);
    };
    const handleSizeChange = (next) => {
      const normalized = toNumberOrToken(next);
      setSize(normalized);
      emitPreview('size', normalized);
    };
    const handleTrackingChange = (next) => {
      const normalized = toNumberOrToken(next);
      setTracking(normalized);
      emitPreview('tracking', normalized);
    };
    const handleLineHeightChange = (next) => {
      const normalized = toNumberOrToken(next);
      setLineHeight(normalized);
      emitPreview('lineHeight', normalized);
    };
    const handleAlignChange = (value) => {
      setAlign(value);
      emitPreview('align', value);
    };
    const toggleFlag = (fieldKey, currentValue, setter) => {
      const map = getFlagMap(fieldKey);
      const next = currentValue === map.on ? map.off : map.on;
      setter(next);
      emitPreview(fieldKey, next);
    };
    const italicActive = italic === getFlagMap('italic').on;
    const uppercaseActive = uppercase === getFlagMap('uppercase').on;
    const smallcapsActive = smallcaps === getFlagMap('smallcaps').on;
    const underlineActive = underline === getFlagMap('underline').on;
    const strikeActive = strike === getFlagMap('strike').on;

    const familyInputId = makeDomId(names.family || `${idKey}-family`);
    // Shadow state (only used when provided via names/values)
    const [shadowEnabled, setShadowEnabled] = useState(values.shadowEnable || '');
    const [shadowX, setShadowX] = useState(toNumberOrToken(values.shadowX));
    const [shadowY, setShadowY] = useState(toNumberOrToken(values.shadowY));
    const [shadowBlur, setShadowBlur] = useState(toNumberOrToken(values.shadowBlur));
    const [shadowColor, setShadowColor] = useState(values.shadowColor || 'rgba(0,0,0,.5)');
    const [shadowStyle, setShadowStyle] = useState(values.shadowStyle || 'custom');
    const computeShadowCss = (style = shadowStyle, en = shadowEnabled, x = shadowX, y = shadowY, b = shadowBlur, col = shadowColor) => {
      if (!en || style === 'none') {
        return 'none';
      }
      const presetMeta = TEXT_SHADOW_PRESETS[style];
      const color = normalizeColorValue(col, true) || 'rgba(0,0,0,.5)';
      if (presetMeta) {
        if (presetMeta.single && typeof presetMeta.x === 'number') {
          const px = presetMeta.x ?? 0;
          const py = presetMeta.y ?? 0;
          const pb = presetMeta.blur ?? 0;
          return `${px}px ${py}px ${pb}px ${color}`;
        }
        if (!presetMeta.single && typeof presetMeta.builder === 'function') {
          return presetMeta.builder(color);
        }
      }
      const sx = toNumberOrToken(x) || 0;
      const sy = toNumberOrToken(y) || 0;
      const sb = toNumberOrToken(b) || 0;
      return `${sx}px ${sy}px ${sb}px ${color}`;
    };
    const pushShadowPreview = (style = shadowStyle, en = shadowEnabled, x = shadowX, y = shadowY, b = shadowBlur, col = shadowColor) => {
      emitPreview('shadow_style', style);
      emitPreview('shadow_enabled', en ? '1' : '');
      emitPreview('shadow_x', toNumberOrToken(x) || 0);
      emitPreview('shadow_y', toNumberOrToken(y) || 0);
      emitPreview('shadow_blur', toNumberOrToken(b) || 0);
      emitPreview('shadow_color', normalizeColorValue(col, true) || 'rgba(0,0,0,.5)');
      emitPreview('shadow', computeShadowCss(style, en, x, y, b, col));
    };
    const applyShadowPreset = (styleKey) => {
      const preset = TEXT_SHADOW_PRESETS[styleKey];
      setShadowStyle(styleKey);
      if (styleKey === 'custom') {
        pushShadowPreview('custom', shadowEnabled, shadowX, shadowY, shadowBlur, shadowColor);
        return;
      }
      if (styleKey === 'none') {
        pushShadowPreview('none', shadowEnabled, shadowX, shadowY, shadowBlur, shadowColor);
        return;
      }
      if (preset && preset.single) {
        const nx = preset.x ?? 0;
        const ny = preset.y ?? 0;
        const nb = preset.blur ?? 0;
        setShadowX(nx);
        setShadowY(ny);
        setShadowBlur(nb);
        pushShadowPreview(styleKey, shadowEnabled, nx, ny, nb, shadowColor);
        return;
      }
      pushShadowPreview(styleKey, shadowEnabled, shadowX, shadowY, shadowBlur, shadowColor);
    };
    const shadowSlidersDisabled = (style) => style === 'none' || (style !== 'custom' && TEXT_SHADOW_PRESETS[style] && !TEXT_SHADOW_PRESETS[style].single);
    const weightFieldId = makeDomId(names.weight || `${idKey}-weight`);
    const effectiveWeightOptions = (weightOptions && weightOptions.length ? weightOptions : TYPO_WEIGHT_OPTIONS).slice();
    effectiveWeightOptions.push(CUSTOM_WEIGHT_OPTION);
    const hasExactWeight = effectiveWeightOptions.some((option) => option.value === weight);
    const selectedWeightValue = hasExactWeight ? weight : '__custom__';

    const pushHidden = (collection, fieldKey, fieldValue) => {
      const fieldName = names[fieldKey];
      if (!fieldName) {
        // TODO: Persist this typography field once backend exposes a meta key.
        return;
      }
      collection.push(HiddenInput({ name: fieldName, value: fieldValue == null ? '' : fieldValue }));
    };

    const children = [];
    const layoutBasics = [];
    const layoutStyles = [];
    const pushNode = (node, bucket) => {
      if (layoutVariant === 'title-two-col') {
        if (bucket === 'basics') {
          layoutBasics.push(node);
          return;
        }
        if (bucket === 'styles') {
          layoutStyles.push(node);
          return;
        }
      }
      children.push(node);
    };
    const fontField = h('label', { className: 'pwpl-typo__field', htmlFor: familyInputId }, [
      h('span', { className: 'pwpl-typo__label' }, `${label} Font`),
      fontOptions.length ? h('select', {
        id: familyInputId,
        className: 'pwpl-typo__select',
        value: family,
        onChange: handleFamilyChange,
      }, fontOptions.map((option) => h('option', { key: option.value, value: option.value }, option.label || option.value || 'Option'))) :
        h('input', {
          id: familyInputId,
          type: 'text',
          value: family,
          onChange: handleFamilyChange,
          placeholder: 'inherit',
          className: 'pwpl-typo__input',
        })
    ]);
    const weightFieldOptions = h('label', { className: 'pwpl-typo__field', htmlFor: weightFieldId }, [
      h('span', { className: 'pwpl-typo__label' }, `${label} Font Weight`),
      h('select', {
        id: weightFieldId,
        className: 'pwpl-typo__select',
        value: selectedWeightValue,
        onChange: handleWeightChange,
      }, effectiveWeightOptions.map((option) => h('option', { key: option.value, value: option.value }, option.label))),
      selectedWeightValue === '__custom__' ? h('input', {
        type: 'text',
        className: 'pwpl-typo__input',
        value: weight,
        onChange: handleCustomWeightChange,
        placeholder: 'inherit',
      }) : null,
    ]);
    if (layoutVariant === 'title-two-col') {
      pushNode(h('div', { className: 'pwpl-typo__row' }, [fontField]), 'basics');
      pushNode(h('div', { className: 'pwpl-typo__row' }, [weightFieldOptions]), 'basics');
    } else {
      pushNode(h('div', { className: 'pwpl-typo__row' }, [fontField, weightFieldOptions]));
    }

    if (ENABLE_TYPO_STYLE_FLAGS && showStyle) {
      const styleItems = [
        { id: `${idKey}-italic`, label: 'I', ariaLabel: `${label} italic`, isActive: italicActive, onClick: () => toggleFlag('italic', italic, setItalic) },
        { id: `${idKey}-uppercase`, label: 'TT', ariaLabel: `${label} uppercase`, isActive: uppercaseActive, onClick: () => toggleFlag('uppercase', uppercase, setUppercase) },
        { id: `${idKey}-smallcaps`, label: 'Tr', ariaLabel: `${label} small caps`, isActive: smallcapsActive, onClick: () => toggleFlag('smallcaps', smallcaps, setSmallcaps) },
        { id: `${idKey}-underline`, label: 'U', ariaLabel: `${label} underline`, isActive: underlineActive, onClick: () => toggleFlag('underline', underline, setUnderline) },
        { id: `${idKey}-strike`, label: 'S', ariaLabel: `${label} strikethrough`, isActive: strikeActive, onClick: () => toggleFlag('strike', strike, setStrike) },
      ];
      pushNode(h('div', { className: 'pwpl-typo__group' }, [
        h('span', { className: 'pwpl-typo__label' }, `${label} Font Style`),
        h(SegmentedButtonGroup, { items: styleItems, ariaLabel: `${label} font style` }),
      ]), layoutVariant === 'title-two-col' ? 'basics' : undefined);
    }

    if (ENABLE_TYPO_ALIGNMENT && showAlignment) {
      const alignIcon = (position) => h('span', { className: classNames('pwpl-typo__align-icon', `is-${position}`) }, [
        h('span'),
        h('span'),
        h('span'),
      ]);
      const alignItems = [
        { id: `${idKey}-align-left`, value: 'left', ariaLabel: `${label} align left`, isActive: align === 'left', onClick: () => handleAlignChange('left'), content: alignIcon('left') },
        { id: `${idKey}-align-center`, value: 'center', ariaLabel: `${label} align center`, isActive: align === 'center', onClick: () => handleAlignChange('center'), content: alignIcon('center') },
        { id: `${idKey}-align-right`, value: 'right', ariaLabel: `${label} align right`, isActive: align === 'right', onClick: () => handleAlignChange('right'), content: alignIcon('right') },
      ];
      pushNode(h('div', { className: 'pwpl-typo__group' }, [
        h('span', { className: 'pwpl-typo__label' }, `${label} Text Alignment`),
        h(SegmentedButtonGroup, { items: alignItems, role: 'radiogroup', className: 'pwpl-typo__seg--align', ariaLabel: `${label} alignment` }),
      ]), layoutVariant === 'title-two-col' ? 'basics' : undefined);
    }

    if (showColor) {
      const colorTabs = ['Saved', 'Global', 'Recent'];
      const colorNode = h('div', { className: 'pwpl-typo__colors' }, [
        h('div', { className: 'pwpl-typo__color-head' }, [
          h('span', { className: 'pwpl-typo__label' }, `${label} Text Color`),
          h('div', { className: 'pwpl-typo__color-tabs' },
            colorTabs.map((tab, index) => h('a', {
              key: tab,
              href: '#',
              className: classNames('pwpl-typo__tabs-link', index === 0 ? 'is-active' : ''),
              onClick: (event) => event.preventDefault(),
            }, tab))
          ),
        ]),
        h('div', { className: 'pwpl-typo__palette' }, h(ColorPaletteControl, {
          label: null,
          value: color,
          onChange: handleColorChange,
          allowAlpha: true,
          className: 'pwpl-inlinecolor--compact',
        })),
      ]);
      pushNode(colorNode, 'basics');
    }

    const sizeNode = RangeValueRow({
      label: `${label} Text Size`,
      name: names.size || null,
      value: size,
      onChange: handleSizeChange,
      min: resolvedSizeRange.min,
      max: resolvedSizeRange.max,
      step: resolvedSizeRange.step,
      placeholder: resolvedSizeRange.placeholder,
      unit: resolvedSizeRange.unit,
    });
    pushNode(sizeNode, layoutVariant === 'title-two-col' ? 'basics' : undefined);

    if (ENABLE_TYPO_TRACKING && showTracking) {
      const trackingNode = RangeValueRow({
        label: `${label} Letter Spacing`,
        name: names.tracking || null,
        value: tracking,
        onChange: handleTrackingChange,
        min: resolvedTrackingRange.min,
        max: resolvedTrackingRange.max,
        step: resolvedTrackingRange.step,
        placeholder: resolvedTrackingRange.placeholder,
        unit: resolvedTrackingRange.unit,
        disabledWhenToken: resolvedTrackingRange.disabledWhenToken !== undefined ? resolvedTrackingRange.disabledWhenToken : true,
      });
    pushNode(trackingNode, layoutVariant === 'title-two-col' ? 'basics' : undefined);
    }

    if (ENABLE_TYPO_LINE_HEIGHT && showLineHeight) {
      const lineHeightNode = RangeValueRow({
        label: `${label} Line Height`,
        name: names.lineHeight || null,
        value: lineHeight,
        onChange: handleLineHeightChange,
        min: resolvedLineHeightRange.min,
        max: resolvedLineHeightRange.max,
        step: resolvedLineHeightRange.step,
        placeholder: resolvedLineHeightRange.placeholder,
        unit: resolvedLineHeightRange.unit,
      });
    pushNode(lineHeightNode, layoutVariant === 'title-two-col' ? 'basics' : undefined);
    }

    // Hidden inputs collection (declare before any pushHidden usage below)
    const hiddenFields = [];

    // Title-only Text Shadow UI when names.* provided (placed in styles column)
    if (layoutVariant === 'title-two-col' && names.shadowEnable) {
      const shadowToggle = h('label', { className: 'pwpl-typo__field' }, [
        h('span', { className: 'pwpl-typo__label' }, `${label} Text Shadow`),
        h('div', { className: 'pwpl-typo__row' }, [
          h('label', { style: { display:'inline-flex', alignItems:'center', gap:'8px' } }, [
            h('input', {
              type: 'checkbox',
              checked: !!shadowEnabled,
              onChange: (e) => { const en = e && e.target && e.target.checked; const style = shadowStyle; setShadowEnabled(en ? '1' : ''); pushShadowPreview(style, en ? '1' : '', shadowX, shadowY, shadowBlur, shadowColor); },
            }),
            'Enable text shadow'
          ])
        ])
      ]);
      const shadowStyleControl = h('div', { className: 'pwpl-typo__row' }, [
        h('label', { className: 'pwpl-typo__label' }, 'Shadow style'),
        h('div', { className: 'pwpl-typo__styles' }, [
          h(SegmentedButtonGroup, {
            items: [
              { id: `${idKey}-shadow-none`, label: 'None', value: 'none', isActive: shadowStyle === 'none', onClick: () => applyShadowPreset('none') },
              { id: `${idKey}-shadow-soft`, label: 'Soft', value: 'soft', isActive: shadowStyle === 'soft', onClick: () => applyShadowPreset('soft') },
              { id: `${idKey}-shadow-medium`, label: 'Medium', value: 'medium', isActive: shadowStyle === 'medium', onClick: () => applyShadowPreset('medium') },
              { id: `${idKey}-shadow-deep`, label: 'Deep', value: 'deep', isActive: shadowStyle === 'deep', onClick: () => applyShadowPreset('deep') },
              { id: `${idKey}-shadow-glow`, label: 'Glow', value: 'glow', isActive: shadowStyle === 'glow', onClick: () => applyShadowPreset('glow') },
              { id: `${idKey}-shadow-long`, label: 'Long', value: 'long', isActive: shadowStyle === 'long', onClick: () => applyShadowPreset('long') },
              { id: `${idKey}-shadow-custom`, label: 'Custom', value: 'custom', isActive: shadowStyle === 'custom', onClick: () => applyShadowPreset('custom') },
            ],
            role: 'radiogroup',
          })
        ])
      ]);
      const shadowColorPicker = h('div', { className: 'pwpl-v1-color' },
        h(ColorPaletteControl, {
          label: 'Shadow color',
          value: shadowColor || '',
          onChange: (v) => {
            const nv = normalizeColorValue(v, true) || '';
            if (shadowStyle !== 'custom') {
              setShadowStyle('custom');
            }
            setShadowColor(nv);
            pushShadowPreview('custom', shadowEnabled, shadowX, shadowY, shadowBlur, nv);
          },
          allowAlpha: true,
          className: 'pwpl-inlinecolor--compact',
        })
      );
      const handleShadowXChange = (value) => {
        const normalized = toNumberOrToken(value);
        if (shadowStyle !== 'custom') {
          setShadowStyle('custom');
        }
        setShadowX(normalized);
        pushShadowPreview('custom', shadowEnabled, normalized, shadowY, shadowBlur, shadowColor);
      };
      const handleShadowYChange = (value) => {
        const normalized = toNumberOrToken(value);
        if (shadowStyle !== 'custom') {
          setShadowStyle('custom');
        }
        setShadowY(normalized);
        pushShadowPreview('custom', shadowEnabled, shadowX, normalized, shadowBlur, shadowColor);
      };
      const handleShadowBlurChange = (value) => {
        const normalized = toNumberOrToken(value);
        if (shadowStyle !== 'custom') {
          setShadowStyle('custom');
        }
        setShadowBlur(normalized);
        pushShadowPreview('custom', shadowEnabled, shadowX, shadowY, normalized, shadowColor);
      };
      const slidersDisabled = shadowSlidersDisabled(shadowStyle);
      const shadowXYZ = h('div', {
        className: classNames('pwpl-typo__grid-3', slidersDisabled ? 'is-shadow-disabled' : ''),
        'aria-disabled': slidersDisabled ? 'true' : undefined,
      }, [
        RangeValueRow({ label: 'X offset (px)', name: names.shadowX, value: shadowX, onChange: handleShadowXChange, min: -50, max: 50, step: 1, unit:'px' }),
        RangeValueRow({ label: 'Y offset (px)', name: names.shadowY, value: shadowY, onChange: handleShadowYChange, min: -50, max: 50, step: 1, unit:'px' }),
        RangeValueRow({ label: 'Blur (px)', name: names.shadowBlur, value: shadowBlur, onChange: handleShadowBlurChange, min: 0, max: 100, step: 1, unit:'px' }),
      ]);
      const shadowHint = h('p', { className: 'pwpl-typo__help' }, 'Adjusting color or sliders switches style to Custom.');
      // Place Text Shadow in the left card (Basics) to use available space
      pushNode(shadowToggle, 'styles');
      pushNode(shadowStyleControl, 'styles');
      pushNode(shadowColorPicker, 'styles');
      pushNode(shadowXYZ, 'styles');
      pushNode(shadowHint, 'styles');
      // Hidden inputs
      pushHidden(hiddenFields, 'shadowEnable', shadowEnabled ? '1' : '');
      pushHidden(hiddenFields, 'shadowX', shadowX);
      pushHidden(hiddenFields, 'shadowY', shadowY);
      pushHidden(hiddenFields, 'shadowBlur', shadowBlur);
      pushHidden(hiddenFields, 'shadowColor', shadowColor);
      pushHidden(hiddenFields, 'shadowStyle', shadowStyle || 'custom');
    }

    pushHidden(hiddenFields, 'color', color);
    pushHidden(hiddenFields, 'family', family);
    pushHidden(hiddenFields, 'weight', weight);
    pushHidden(hiddenFields, 'size', size);
    pushHidden(hiddenFields, 'tracking', tracking);
    pushHidden(hiddenFields, 'lineHeight', lineHeight);
    pushHidden(hiddenFields, 'align', align);
    pushHidden(hiddenFields, 'italic', italic);
    pushHidden(hiddenFields, 'uppercase', uppercase);
    pushHidden(hiddenFields, 'smallcaps', smallcaps);
    pushHidden(hiddenFields, 'underline', underline);
    pushHidden(hiddenFields, 'strike', strike);

    const panelChildren = [];
    if (layoutVariant === 'title-two-col') {
      const columns = [];
      if (layoutBasics.length) {
        columns.push(
          h('div', { className: 'pwpl-col' },
            h('div', { className: 'pwpl-card' }, layoutBasics )
          )
        );
      }
      if (layoutStyles.length) {
        columns.push(
          h('div', { className: 'pwpl-col' },
            h('div', { className: 'pwpl-card' }, layoutStyles )
          )
        );
      }
      if (columns.length) {
        panelChildren.push(
          h('div', { className: 'pwpl-two' }, columns )
        );
      }
    }
    panelChildren.push(...children);
    const fullChildren = hiddenFields.length ? panelChildren.concat(hiddenFields) : panelChildren;
    return h('div', { className: 'pwpl-typo', 'data-typo-id': idKey }, fullChildren);
  }
  function FourSidesControl({
    label,
    values = {},
    names = {},
    presets = [],
    locked = false,
    onToggleLock,
    onChange,
    unit = 'px',
  }){
    const [activeSide, setActiveSide] = useState('t');
    const safeValues = Object.assign({ t:'', r:'', b:'', l:'' }, {
      t: toNumberOrToken(values.t),
      r: toNumberOrToken(values.r),
      b: toNumberOrToken(values.b),
      l: toNumberOrToken(values.l),
    });
    const setValues = (next) => {
      if (typeof onChange === 'function') {
        onChange(next);
      }
    };
    const sides = [
      { key: 't', label: 'Top' },
      { key: 'r', label: 'Right' },
      { key: 'b', label: 'Bottom' },
      { key: 'l', label: 'Left' },
    ];
    const handleInput = (side) => (event) => {
      const nextVal = toNumberOrToken(event && event.target ? event.target.value : event);
      setActiveSide(side);
      if (locked) {
        setValues({ t: nextVal, r: nextVal, b: nextVal, l: nextVal });
        return;
      }
      setValues(Object.assign({}, safeValues, { [side]: nextVal }));
    };
    const handlePreset = (presetValue) => {
      const nextVal = toNumberOrToken(presetValue);
      if (locked) {
        setValues({ t: nextVal, r: nextVal, b: nextVal, l: nextVal });
        return;
      }
      const target = activeSide || 't';
      setValues(Object.assign({}, safeValues, { [target]: nextVal }));
    };
    return h('div', { className: 'pwpl-row pwpl-row--sides' }, [
      label ? h('div', { className: 'pwpl-row__label' }, label) : null,
      h('div', { className: 'pwpl-row__control' }, [
        h('div', { className: 'pwpl-sides' }, [
          ...sides.map((side) => h('div', { key: side.key, className: 'pwpl-side' }, [
            h('label', { className: 'pwpl-side__label', htmlFor: makeDomId(names[side.key] || `${side.key}-side`) }, side.label),
            h('div', { className: 'pwpl-value-pill', 'data-suffix': unit || '' }, [
              h('input', {
                id: makeDomId(names[side.key] || `${side.key}-side`),
                type: 'text',
                value: safeValues[side.key] || '',
                onChange: handleInput(side.key),
                onFocus: () => setActiveSide(side.key),
              }),
            ]),
            names[side.key] ? HiddenInput({ name: names[side.key], value: safeValues[side.key] || '' }) : null,
          ])),
        ]),
      ]),
      h('div', { className: 'pwpl-sides-footer' }, [
        presets && presets.length ? h('div', { className: 'pwpl-sides-presets' },
          presets.map((preset) => h('button', {
            type: 'button',
            key: String(preset),
            className: 'pwpl-presets__chip',
            onClick: () => handlePreset(preset),
          }, `${preset}${unit}`))
        ) : h('div'),
        h('div', { className: 'pwpl-sides__lock' }, [
          h('label', { className: 'pwpl-lockchk' }, [
            h('input', {
              type: 'checkbox',
              checked: !!locked,
              onChange: (e) => onToggleLock && onToggleLock(!!e.target.checked),
              'aria-label': 'Lock values',
            }),
            ' Locked'
          ])
        ])
      ]),
    ]);
  }
  function Help({ text }){
    // Lightweight tooltip using title attribute for hover
    return h('span', { className: 'pwpl-help', title: text || '' , style:{ marginLeft:6, cursor:'help', fontSize:'12px', opacity:.7 } }, '❓');
  }
  function SectionHeader({ title, description, dataTour }){
    const attrs = { className: 'pwpl-v1-section-header' };
    if (dataTour) {
      attrs['data-pwpl-tour'] = dataTour;
    }
    return h('div', attrs, [
      h('div', { className: 'pwpl-v1-section-left' }, [
        h('h1', { className: 'pwpl-v1-title' }, title),
        description ? h('p', { className: 'pwpl-v1-desc' }, description) : null,
      ])
    ]);
  }



  function LayoutSpacingBlock(){
    const layoutConfig = data.layout || {};
    const cardLayout = data.card.layout || {};
    const cardColors = data.card.colors || {};
    const cta = (data.ui && data.ui.cta) ? data.ui.cta : {};
    const specsConfig = (data.ui && data.ui.specs) ? data.ui.specs : { style: 'default' };

    const ENABLE_PRESETS = true;
    const WIDTH_PRESETS = [1140, 1200, 1320];
    const SPACING_PRESETS = [0, 12, 16, 20];
    const scalar = (val, fallback = '') => {
      const normalized = toNumberOrToken(val);
      if (normalized === '' && fallback !== undefined) {
        return fallback;
      }
      return normalized;
    };

    const [globalWidth, setGlobalWidth] = useState(scalar(layoutConfig.widths && layoutConfig.widths.global, '0'));
    const [globalColumns, setGlobalColumns] = useState(scalar(layoutConfig.columns && layoutConfig.columns.global, '0'));
    const [globalCardMin, setGlobalCardMin] = useState(scalar(layoutConfig.cardWidths && layoutConfig.cardWidths.global, '0'));
    const [tableHeight, setTableHeight] = useState(scalar(layoutConfig.height, '0'));
    const [columnGap, setColumnGap] = useState(scalar(layoutConfig.gap_x, '0'));
    const [cardHeight, setCardHeight] = useState(scalar(cardLayout.height, '0'));

    const deviceOrder = ['xxl','xl','md','sm'];
    const DEVICE_LABELS = {
      xxl: 'Big screens (≥ 1536px)',
      xl:  'Desktop (1280–1535px)',
      md:  'Tablet (768–1023px)',
      sm:  'Mobile (≤ 767px)'
    };
    const baseDeviceState = deviceOrder.reduce((acc, key) => {
      acc[key] = '';
      return acc;
    }, {});
    const mapDeviceState = (source = {}) => {
      const next = {};
      deviceOrder.forEach((key) => {
        next[key] = scalar(source[key], '');
      });
      return Object.assign({}, baseDeviceState, next);
    };
    const [widths, setWidths] = useState(mapDeviceState(layoutConfig.widths));
    const [columns, setColumns] = useState(mapDeviceState(layoutConfig.columns));
    const [cardW, setCardW] = useState(mapDeviceState(layoutConfig.cardWidths));
    const [linkDevices, setLinkDevices] = useState(false);

    const [radius, setRadius] = useState(scalar(cardLayout.radius, '0'));
    const [borderW, setBorderW] = useState(scalar(cardLayout.border_w, '0'));
    const [borderColor, setBorderColor] = useState(cardColors.border || '');
    const allEqual = (a, b, c, d) => (a === b && a === c && a === d);
    const padsInitial = {
      t: scalar(cardLayout.pad_t, '0'),
      r: scalar(cardLayout.pad_r, '0'),
      b: scalar(cardLayout.pad_b, '0'),
      l: scalar(cardLayout.pad_l, '0'),
    };
    const marginsInitial = {
      t: scalar(cardLayout.margin_t, '0'),
      r: scalar(cardLayout.margin_r, '0'),
      b: scalar(cardLayout.margin_b, '0'),
      l: scalar(cardLayout.margin_l, '0'),
    };
    const [pads, setPads] = useState(padsInitial);
    const [margins, setMargins] = useState(marginsInitial);
    const [lockPads, setLockPads] = useState(allEqual(padsInitial.t, padsInitial.r, padsInitial.b, padsInitial.l));
    const [lockMargins, setLockMargins] = useState(allEqual(marginsInitial.t, marginsInitial.r, marginsInitial.b, marginsInitial.l));
    const [split, setSplit] = useState(cardLayout.split || 'two_tone');

    const [ctaWidthSel, setCtaWidthSel] = useState(cta.width || 'full');
    const [ctaHeight, setCtaHeight] = useState(scalar(cta.height, '48'));
    const [ctaPadX, setCtaPadX] = useState(scalar(cta.pad_x, '22'));
    const [ctaRadius, setCtaRadius] = useState(scalar(cta.radius, '12'));
    const [ctaBorderW, setCtaBorderW] = useState(scalar(cta.border_width, '1.5'));
    const [ctaMinW, setCtaMinW] = useState(scalar(cta.min_w, '0'));
    const [ctaMaxW, setCtaMaxW] = useState(scalar(cta.max_w, '0'));
    const [ctaLift, setCtaLift] = useState(scalar(cta.lift, '1'));

    const [specStyle, setSpecStyle] = useState(specsConfig.style || 'default');

    const postId = parseInt(data.postId || 0, 10) || 0;
    const layoutStorageKey = 'pwpl_v1_layout_open_sections_' + postId;
    const expandStorageKey = 'pwpl_v1_layout_expand_all_' + postId;
    const {
      searchTerm,
      setSearchTerm,
      openSections,
      matchesSearch,
      toggleSection,
      openFirstMatch,
      setOpenSections,
    } = useAccordionSections(layoutStorageKey);

    const [expandAll, setExpandAll] = useState(() => {
      try {
        const raw = window.localStorage ? window.localStorage.getItem(expandStorageKey) : null;
        return raw === 'true';
      } catch (e) {}
      return false;
    });

    useEffect(() => {
      try {
        if (window.localStorage) {
          window.localStorage.setItem(expandStorageKey, expandAll ? 'true' : 'false');
        }
      } catch (e) {}
    }, [expandAll, expandStorageKey]);

    const handleSearchKeyDown = (event) => {
      if (event.key !== 'Enter') {
        return;
      }
      openFirstMatch(sections);
    };
    const unifySides = (values) => {
      const base = values.t || values.r || values.b || values.l || '0';
      return { t: base, r: base, b: base, l: base };
    };
    const handlePadLockToggle = (nextLock) => {
      setLockPads(nextLock);
      if (nextLock) {
        setPads(unifySides(pads));
      }
    };
    const handleMarginLockToggle = (nextLock) => {
      setLockMargins(nextLock);
      if (nextLock) {
        setMargins(unifySides(margins));
      }
    };

    const formatNumber = (value, suffix = 'px') => {
      if (suffix === 'cols') {
        const display = formatDisplay(value, ' cols');
        return display || '—';
      }
      if (suffix === 'raw') {
        const display = formatDisplay(value, '');
        return display || '—';
      }
      const display = formatDisplay(value, suffix);
      return display || '—';
    };

    const formatCount = (value) => {
      const normalized = toNumberOrToken(value);
      return normalized || '—';
    };
    const formatSides = (obj) => {
      return [obj.t, obj.r, obj.b, obj.l].map((val) => formatNumber(val)).join('/');
    };

    const updateBreakpointField = (setter, current, key, value) => {
      if (linkDevices) {
        const next = Object.assign({}, current);
        deviceOrder.forEach((deviceKey) => {
          next[deviceKey] = value;
        });
        setter(next);
        return;
      }
      setter(Object.assign({}, current, { [key]: value }));
    };

    const copyGlobalToBreakpoints = () => {
      const widthVal = toNumberOrToken(globalWidth);
      const colVal = toNumberOrToken(globalColumns);
      const cardVal = toNumberOrToken(globalCardMin);
      const nextWidths = {};
      const nextColumns = {};
      const nextCard = {};
      deviceOrder.forEach((key) => {
        nextWidths[key] = widthVal;
        nextColumns[key] = colVal;
        nextCard[key] = cardVal;
      });
      setWidths(nextWidths);
      setColumns(nextColumns);
      setCardW(nextCard);
    };

    const expandAllSections = () => {
      setExpandAll(true);
      setOpenSections((prev) => {
        const next = Object.assign({}, prev);
        sections.forEach((section) => {
          next[section.id] = true;
        });
        return next;
      });
    };

    const collapseAllSections = () => {
      setExpandAll(false);
      setOpenSections(() => ({}));
    };

    const summarizeColor = (value) => {
      if (!value) {
        return '—';
      }
      return value.toUpperCase();
    };

    const formatSplit = (value) => {
      if (!value) {
        return '—';
      }
      return value.replace(/_/g, ' ');
    };

    const globalWidthLabel = (!globalWidth || globalWidth === '0') ? 'Fluid' : formatNumber(globalWidth);

    const spacingLockSummary = lockPads ? 'Spacing lock: linked' : 'Spacing lock: free';
    const sectionSummaries = {
      'layout-table': [globalWidthLabel, `${formatCount(globalColumns)} cols`, `gap ${formatNumber(columnGap)}`],
      'layout-card': [
        `Container r ${formatNumber(radius)} · b ${formatNumber(borderW)} · ${summarizeColor(borderColor)}`,
        `W ${formatNumber(globalCardMin)} · H ${formatNumber(cardHeight)}`,
        `Pad ${formatSides(pads)} · Mar ${formatSides(margins)} · ${spacingLockSummary} · Split: ${formatSplit(split)}`,
      ],
      'layout-cta': [
        `Width ${ctaWidthSel || 'auto'}`,
        `H ${formatNumber(ctaHeight)}`,
        `Pad ${formatNumber(ctaPadX)}`,
        `R ${formatNumber(ctaRadius)}`,
        `B ${formatNumber(ctaBorderW)}`,
      ],
      'layout-specs-style': [specStyle ? specStyle.charAt(0).toUpperCase() + specStyle.slice(1) : '—'],
    };

    const sectionTooltips = {
      'layout-table': 'Manage overall table width, columns, and responsive breakpoints.',
      'layout-card': 'Card container, spacing, and split layout controls.',
      'layout-cta': 'Size the CTA pill including width, height, padding, and hover lift.',
      'layout-specs-style': 'Pick the visual style for the specs list.',
    };

    const toolbarActions = [
      { key: 'expand', label: 'Expand all', onClick: expandAllSections, disabled: expandAll },
      { key: 'collapse', label: 'Collapse all', onClick: collapseAllSections },
    ];

    const presetChips = (values, onSelect, formatter = (val) => val) => {
      if (!ENABLE_PRESETS) {
        return null;
      }
      return h('div', { className: 'pwpl-presets' },
        values.map((preset) => h('button', {
          key: String(preset),
          type: 'button',
          className: 'pwpl-presets__chip',
          onClick: () => onSelect(preset),
        }, formatter(preset)))
      );
    };

    const renderCard = (child) => h('div', { className: 'pwpl-card' }, child);
    const renderTwoCards = (leftChild, rightChild) => {
      const cols = [];
      if (leftChild) {
        cols.push(h('div', { className: 'pwpl-col' }, renderCard(leftChild)));
      }
      if (rightChild) {
        cols.push(h('div', { className: 'pwpl-col' }, renderCard(rightChild)));
      }
      if (!cols.length) {
        return null;
      }
      return h('div', { className: 'pwpl-two' }, cols);
    };

    // Build widths/columns two cards
    const widthsLeft = h('div', null, [
      h('h3', { className: 'pwpl-card__title' }, 'Table Size'),
      RangeValueRow({
        label: 'Table width',
        name: 'pwpl_table[layout][widths][global]',
        value: globalWidth,
        onChange: (val) => setGlobalWidth(val),
        min: 0,
        max: 2000,
        step: 10,
        placeholder: 'auto',
      }),
      presetChips(WIDTH_PRESETS.concat(['fluid']), (preset) => {
        if (preset === 'fluid') { setGlobalWidth('0'); return; }
        setGlobalWidth(String(preset));
      }, (preset) => preset === 'fluid' ? '100%' : `${preset}px`),
      RangeValueRow({
        label: 'Table height',
        name: 'pwpl_table[layout][height]',
        value: tableHeight,
        onChange: (val) => setTableHeight(val),
        min: 0,
        max: 4000,
        step: 10,
        placeholder: 'auto',
      }),
    ]);
    const widthsRight = h('div', null, [
      h('h3', { className: 'pwpl-card__title' }, 'Columns'),
      RangeValueRow({
        label: 'Preferred columns',
        name: 'pwpl_table[layout][columns][global]',
        value: globalColumns,
        onChange: (val) => setGlobalColumns(val),
        min: 0,
        max: 12,
        step: 1,
        unit: 'cols',
      }),
      RangeValueRow({
        label: 'Column gap (px)',
        name: 'pwpl_table[layout][gap_x]',
        value: columnGap,
        onChange: (val) => setColumnGap(val),
        min: 0,
        max: 96,
        step: 2,
        placeholder: 'auto',
      }),
    ]);
    const widthsTwoCards = renderTwoCards(widthsLeft, widthsRight);
    // Breakpoints card (now inline below widths)
    const breakpointsBody = h('div', { className: 'pwpl-breakpoints' }, deviceOrder.map((key) => {
      const readable = DEVICE_LABELS[key] || key.toUpperCase();
      return h('div', { key, className: 'pwpl-breakpoint-card' }, [
        h('div', { className: 'pwpl-breakpoint-card__title' }, readable),
        RangeValueRow({
          label: 'Width',
          name: `pwpl_table[layout][widths][${key}]`,
          value: widths[key],
          onChange: (val) => updateBreakpointField(setWidths, widths, key, val),
          min: 0,
          max: 2000,
          step: 10,
          placeholder: 'inherit',
        }),
        RangeValueRow({
          label: 'Columns',
          name: `pwpl_table[layout][columns][${key}]`,
          value: columns[key],
          onChange: (val) => updateBreakpointField(setColumns, columns, key, val),
          min: 0,
          max: 12,
          step: 1,
          unit: 'cols',
          placeholder: 'inherit',
        }),
        RangeValueRow({
          label: 'Card min width',
          name: `pwpl_table[layout][card_widths][${key}]`,
          value: cardW[key],
          onChange: (val) => updateBreakpointField(setCardW, cardW, key, val),
          min: 0,
          max: 2000,
          step: 10,
          placeholder: 'inherit',
        }),
      ]);
    }));
    const tableSection = h('div', null, [ widthsTwoCards, h('div', { className: 'pwpl-breakpoints-section' }, breakpointsBody) ]);

    const planCardSection = (() => {
      const containerFields = [
        h('h3', { className: 'pwpl-card__title' }, 'Card Container'),
        RangeValueRow({
          label: 'Card Width',
          name: 'pwpl_table[layout][card_widths][global]',
          value: globalCardMin,
          onChange: (val) => setGlobalCardMin(val),
          min: 0,
          max: 1200,
          step: 10,
          placeholder: 'inherit',
        }),
        RangeValueRow({
          label: 'Card Height',
          name: 'pwpl_table[card][layout][height]',
          value: cardHeight,
          onChange: (val) => setCardHeight(val),
          min: 0,
          max: 2000,
          step: 10,
          placeholder: 'auto',
        }),
        RangeValueRow({
          label: 'Card radius',
          name: 'pwpl_table[card][layout][radius]',
          value: radius,
          onChange: (val) => setRadius(val),
          min: 0,
          max: 64,
          step: 1,
        }),
        RangeValueRow({
          label: 'Border width',
          name: 'pwpl_table[card][layout][border_w]',
          value: borderW,
          onChange: (val) => setBorderW(val),
          min: 0,
          max: 12,
          step: 0.5,
        }),
        h('div', { className: 'pwpl-v1-color pwpl-v1-color--palette' },
          h(ColorPaletteControl, {
            label: 'Border color',
            value: borderColor || '',
            onChange: (val) => setBorderColor(typeof val === 'string' ? val : ''),
            allowAlpha: true,
            className: 'pwpl-inlinecolor--compact',
          })
        ),
        HiddenInput({ name: 'pwpl_table[card][colors][border]', value: borderColor }),
      ];
      const layoutFields = [
        h('h3', { className: 'pwpl-card__title' }, 'Card Layout & Margins'),
        FourSidesControl({
          label: 'Padding (px)',
          values: pads,
          names: {
            t: 'pwpl_table[card][layout][pad_t]',
            r: 'pwpl_table[card][layout][pad_r]',
            b: 'pwpl_table[card][layout][pad_b]',
            l: 'pwpl_table[card][layout][pad_l]',
          },
          presets: SPACING_PRESETS,
          locked: lockPads,
          onToggleLock: handlePadLockToggle,
          onChange: setPads,
        }),
        FourSidesControl({
          label: 'Margin (px)',
          values: margins,
          names: {
            t: 'pwpl_table[card][layout][margin_t]',
            r: 'pwpl_table[card][layout][margin_r]',
            b: 'pwpl_table[card][layout][margin_b]',
            l: 'pwpl_table[card][layout][margin_l]',
          },
          presets: SPACING_PRESETS,
          locked: lockMargins,
          onToggleLock: handleMarginLockToggle,
          onChange: setMargins,
        }),
        h('div', { className: 'pwpl-card__section' }, [
          h('span', { className: 'pwpl-card__section-title' }, 'Card Style'),
          h('div', { className: 'pwpl-row' }, [
            h('div', { className: 'pwpl-row__left' }, [
              h('label', { className: 'pwpl-row__label', htmlFor: makeDomId('pwpl_table[card][layout][split]') }, 'Split layout'),
            ]),
            h('div', { className: 'pwpl-row__control' }, [
              h('select', { id: makeDomId('pwpl_table[card][layout][split]'), value: split, onChange: (e) => setSplit(e.target.value) }, [
                h('option', { value: 'two_tone' }, 'Two-tone (header & CTA vs. specs)'),
              ]),
              HiddenInput({ name: 'pwpl_table[card][layout][split]', value: split }),
            ]),
          ]),
        ]),
      ];
      return renderTwoCards(
        h('div', null, containerFields),
        h('div', null, layoutFields)
      );
    })();

    const ctaSizeSection = renderCard(h('div', null, [
      h('h3', { className: 'pwpl-card__title' }, 'CTA Size & Layout'),
      h('div', null, [
        h('label', { className:'components-base-control__label' }, 'Width'),
        h('select', {
          value: ctaWidthSel,
          onChange:(e)=> {
            const next = e.target.value;
            setCtaWidthSel(next);
            updatePreviewVars({ 'ui.cta.width': next });
          }
        }, [
          h('option', { value:'auto' }, 'Auto'),
          h('option', { value:'full' }, 'Full'),
        ]),
        HiddenInput({ name:'pwpl_table[ui][cta][width]', value: ctaWidthSel }),
      ]),
      RangeValueRow({
        label: 'Height',
        name: 'pwpl_table[ui][cta][height]',
        value: ctaHeight,
        onChange: (val) => {
          setCtaHeight(val);
          const next = parseInt(val || 0, 10) || 0;
          updatePreviewVars({ 'ui.cta.height': next });
        },
        min: 36,
        max: 200,
        step: 2,
      }),
      RangeValueRow({
        label: 'Padding X',
        name: 'pwpl_table[ui][cta][pad_x]',
        value: ctaPadX,
        onChange: (val) => {
          setCtaPadX(val);
          const next = parseInt(val || 0, 10) || 0;
          updatePreviewVars({ 'ui.cta.pad_x': next });
        },
        min: 0,
        max: 64,
        step: 2,
      }),
      RangeValueRow({
        label: 'Radius',
        name: 'pwpl_table[ui][cta][radius]',
        value: ctaRadius,
        onChange: (val) => {
          setCtaRadius(val);
          const next = parseInt(val || 0, 10) || 0;
          updatePreviewVars({ 'ui.cta.radius': next });
        },
        min: 0,
        max: 64,
      }),
      RangeValueRow({
        label: 'Border width',
        name: 'pwpl_table[ui][cta][border_width]',
        value: ctaBorderW,
        onChange: (val) => {
          setCtaBorderW(val);
          const next = parseFloat(val || 0) || 0;
          updatePreviewVars({ 'ui.cta.border_width': next });
        },
        min: 0,
        max: 12,
        step: 0.5,
      }),
      RangeValueRow({
        label: 'Min width',
        name: 'pwpl_table[ui][cta][min_w]',
        value: ctaMinW,
        onChange: (val) => {
          setCtaMinW(val);
          const next = parseInt(val || 0, 10) || 0;
          updatePreviewVars({ 'ui.cta.min_w': next });
        },
        min: 0,
        max: 2000,
        step: 10,
      }),
      RangeValueRow({
        label: 'Max width',
        name: 'pwpl_table[ui][cta][max_w]',
        value: ctaMaxW,
        onChange: (val) => {
          setCtaMaxW(val);
          const next = parseInt(val || 0, 10) || 0;
          updatePreviewVars({ 'ui.cta.max_w': next });
        },
        min: 0,
        max: 2000,
        step: 10,
      }),
      RangeValueRow({
        label: 'Hover lift',
        name: 'pwpl_table[ui][cta][lift]',
        value: ctaLift,
        onChange: (val) => {
          setCtaLift(val);
          const next = parseInt(val || 0, 10) || 0;
          updatePreviewVars({ 'ui.cta.lift': next });
        },
        min: 0,
        max: 10,
        step: 1,
      }),
    ]));

    const specsStyleSection = renderCard(h('div', { className:'pwpl-form-grid' }, [
      h('div', null, [
        h('h3', { className: 'pwpl-card__title' }, 'Specs Style'),
        h('label', { className:'components-base-control__label' }, 'Specs style'),
        h('select', { value:specStyle, onChange:(e)=> setSpecStyle(e.target.value) }, [
          h('option', { value:'default' }, 'Default'),
          h('option', { value:'flat' }, 'Flat'),
          h('option', { value:'segmented' }, 'Segmented'),
          h('option', { value:'chips' }, 'Chips'),
        ]),
        HiddenInput({ name:'pwpl_table[ui][specs_style]', value: specStyle }),
      ])
    ]));

    const sidebar = (data.i18n && data.i18n.sidebar) ? data.i18n.sidebar : {};
    const layoutLabel = i18n(sidebar.layoutSpacing) || 'Layout & Spacing';

    const tableCard = tableSection;

    const sections = [
      { id: 'layout-table', title: 'Table Width & Columns', content: tableCard },
      {
        id: 'layout-card',
        title: 'Plan Card Layout, Sizing & Spacing',
        content: planCardSection,
        keywords: ['container','spacing','layout','padding','margin','split','radius','border'],
      },
      { id: 'layout-cta', title: 'CTA Size & Layout', content: ctaSizeSection },
      { id: 'layout-specs-style', title: 'Specs Style', content: specsStyleSection },
    ];

    const accordionItems = sections.map((section) => h(AccordionItem, {
      key: section.id,
      id: section.id,
      title: section.title,
      isOpen: expandAll ? true : !!openSections[section.id],
      onToggle: toggleSection,
      hidden: !matchesSearch(section.title, section.keywords),
      summary: sectionSummaries[section.id],
      tooltip: sectionTooltips[section.id],
    }, section.content));

    return h('section', { className: 'pwpl-v1-block' }, [
      SectionHeader({ title: layoutLabel, description: 'All layout, spacing, CTA sizing, and specs style controls.' }),
      h(Card, null, h(CardBody, null,
        h(Accordion, {
          searchValue: searchTerm,
          onSearchChange: setSearchTerm,
          onSearchKeyDown: handleSearchKeyDown,
          onClear: () => setSearchTerm(''),
          actions: toolbarActions,
        }, accordionItems)
      ))
    ]);

  }


  function AnimationBlock(){
    const specs = (data.ui && data.ui.specs) ? data.ui.specs : { style:'default', anim:{ flags:[], intensity:45, mobile:0 } };
    const initFlags = (specs.anim && Array.isArray(specs.anim.flags)) ? specs.anim.flags : [];
    const [flags, setFlags] = useState(new Set(initFlags));
    const [intensity, setIntensity] = useState(toNumberOrToken((specs.anim && specs.anim.intensity) || '45'));
    const [mobile, setMobile] = useState((specs.anim && specs.anim.mobile) ? 1 : 0);
    const flagKeys = ['row','icon','divider','chip','stagger'];

    const toggleFlag = (key) => {
      const next = new Set(flags);
      if (next.has(key)) {
        next.delete(key);
      } else {
        next.add(key);
      }
      setFlags(next);
    };

    const postId = parseInt(data.postId || 0, 10) || 0;
    const {
      searchTerm,
      setSearchTerm,
      openSections,
      matchesSearch,
      toggleSection,
      openFirstMatch,
    } = useAccordionSections('pwpl_v1_animation_open_sections_' + postId);

    const handleSearchKeyDown = (event) => {
      if (event.key !== 'Enter') {
        return;
      }
      openFirstMatch(sections);
    };

    const renderCard = (child, title) => h('div', { className:'pwpl-card' }, title ? [h('h3', { className: 'pwpl-card__title' }, title), child] : child);

    const interactionsSection = h('div', { className:'pwpl-v1-grid' }, [
      h('div', { style:{ display:'grid', gridTemplateColumns:'repeat(2, minmax(120px, 1fr))', gap:'10px' } },
        flagKeys.map((k)=> h('label', { key:k, className:'components-base-control__label' }, [
          h('input', { type:'checkbox', checked: flags.has(k), onChange:()=> toggleFlag(k) }), ' ', k
        ]))
      ),
      Array.from(flags).map((k)=> HiddenInput({ key:k, name:'pwpl_table[ui][specs_anim][flags][]', value:k })),
      RangeValueRow({
        label: 'Intensity (0–100)',
        name: 'pwpl_table[ui][specs_anim][intensity]',
        value: intensity,
        onChange: setIntensity,
        min: 0,
        max: 100,
        step: 1,
        unit: '',
      }),
      h('label', { className:'components-base-control__label' }, [
        h('input', { type:'checkbox', checked: !!mobile, onChange:(e)=> setMobile(e.target.checked ? 1 : 0) }), ' Enable on mobile'
      ]),
      HiddenInput({ name:'pwpl_table[ui][specs_anim][mobile]', value: mobile ? 1 : '' }),
    ]);

    const sections = [
      { id: 'animation-interactions', title: 'Interactions', content: renderCard(interactionsSection, 'Specs Interactions') },
    ];

    const sidebar = (data.i18n && data.i18n.sidebar) ? data.i18n.sidebar : {};
    const animationLabel = i18n(sidebar.animation) || 'Animation';

    return h('section', { className:'pwpl-v1-block' }, [
      SectionHeader({ title: animationLabel, description: 'Specs interaction flags and animation controls.' }),
      h(Card, null, h(CardBody, null,
        h(Accordion, {
          searchValue: searchTerm,
          onSearchChange: setSearchTerm,
          onSearchKeyDown: handleSearchKeyDown,
          onClear: () => setSearchTerm(''),
        },
          sections.map((section)=> h(AccordionItem, {
            key: section.id,
            id: section.id,
            title: section.title,
            isOpen: !!openSections[section.id],
            onToggle: toggleSection,
            hidden: !matchesSearch(section.title, section.keywords),
          }, section.content ))
        )
      ))
    ]);
  }

  function Sidebar({ active, onChange }){
    const sidebar = (data.i18n && data.i18n.sidebar) ? data.i18n.sidebar : {};
    const layoutLabel = i18n(sidebar.layoutSpacing) || 'Layout & Spacing';
    const items = [
      { key: 'layout', label: layoutLabel },
      { key: 'typography', label: i18n(sidebar.typography) },
      { key: 'colors', label: i18n(sidebar.colors) },
      { key: 'animation', label: i18n(sidebar.animation) || 'Animation' },
      { key: 'badges', label: i18n(sidebar.badges) },
      { key: 'advanced', label: i18n(sidebar.advanced) },
      { key: 'filters', label: i18n(sidebar.filters) },
    ];
    return h('nav', { className: 'pwpl-v1-sidebar' },
      items.map(item => h('button', {
        type: 'button',
        className: 'pwpl-v1-nav' + (active === item.key ? ' is-active' : ''),
        onClick: () => onChange(item.key),
        'data-pwpl-tour': item.key === 'layout' ? 'table-layout-nav' : undefined,
      }, item.label))
    );
  }

  function TopBar(){
    const postId = parseInt(data.postId || 0, 10) || 0;
    const shortcode = `[pwpl_table id="${postId}"]`;
    const [copied, setCopied] = useState(false);
    const [saving, setSaving] = useState(false);
    const [status, setStatus] = useState('');

    function detectStatus(){
      try {
        if (w.wp && wp.data && wp.data.select){
          const sel = wp.data.select('core/editor');
          if (sel && sel.getEditedPostAttribute){
            const s = sel.getEditedPostAttribute('status') || '';
            return s;
          }
          if (sel && sel.getCurrentPost){
            const p = sel.getCurrentPost && sel.getCurrentPost();
            if (p && p.status) return p.status;
          }
        }
      } catch(e){}
      const el = document.getElementById('original_post_status');
      return (el && el.value) ? String(el.value) : '';
    }

    if (useEffect){ useEffect(()=>{ setStatus(detectStatus()); }, []); }

    async function doCopy(){
      try {
        if (navigator.clipboard && navigator.clipboard.writeText){
          await navigator.clipboard.writeText(shortcode);
          setCopied(true); setTimeout(()=> setCopied(false), 1600);
          return;
        }
      } catch(e){}
      try {
        const ta = document.createElement('textarea');
        ta.value = shortcode; ta.setAttribute('readonly',''); ta.style.position='fixed'; ta.style.opacity='0';
        document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
        setCopied(true); setTimeout(()=> setCopied(false), 1600);
      } catch(e){}
    }

    function submitClassicUpdate(){
      try {
        const form = document.getElementById('post');
        if (!form) return false;
        const original = document.getElementById('original_post_status');
        const current = original ? String(original.value) : '';
        if (current !== 'publish'){
          const st = document.getElementById('post_status');
          if (st) { st.value = 'publish'; }
        }
        const publishBtn = document.getElementById('publish');
        if (publishBtn) {
          publishBtn.click();
        } else if (typeof form.requestSubmit === 'function') {
          form.requestSubmit();
        } else {
          form.submit();
        }
        return true;
      } catch(e){}
      return false;
    }

    async function publishOrUpdate(){
      if (saving) return;
      setSaving(true);

      try { syncAggregateAll(); } catch(e){}

      if (submitClassicUpdate()){
        return;
      }

      let ok = false;
      try {
        if (w.wp && wp.data && wp.data.dispatch){
          const sel = wp.data.select('core/editor');
          const dispatch = wp.data.dispatch('core/editor');
          if (dispatch && sel){
            const current = sel.getEditedPostAttribute ? sel.getEditedPostAttribute('status') : '';
            if (current && current !== 'publish' && dispatch.editPost){
              dispatch.editPost({ status: 'publish' });
            }
            if (dispatch.savePost){ await dispatch.savePost(); ok = true; }
          }
        }
      } catch(e){}

      if (!ok){
        ok = submitClassicUpdate();
      }

      setSaving(false);
      setStatus(detectStatus());
    }

    const isPublished = String(status) === 'publish';
    const primaryLabel = saving ? (isPublished ? 'Updating…' : 'Publishing…') : (isPublished ? 'Update' : 'Publish');

    return h('div', { className:'pwpl-v1-topbar', 'data-pwpl-tour':'table-shortcode-area' }, [
      h('button', { type:'button', className:'button', onClick: doCopy }, copied ? 'Shortcode Copied' : 'Copy Shortcode'),
      h('button', { type:'button', className:'button button-primary', onClick: publishOrUpdate, disabled: saving }, primaryLabel),
    ]);
  }

  function App(){
    const postId = parseInt(data.postId || 0, 10) || 0;
    const LS_KEY = 'pwpl_v1_active_tab_' + postId;
    const initialTab = (function(){
      try { const v = window.localStorage && window.localStorage.getItem(LS_KEY); return v || 'layout'; } catch(e){ return 'layout'; }
    })();
    const normalizeTab = (value) => {
      if (value === 'table' || value === 'card' || value === 'cta') {
        return 'layout';
      }
      if (value === 'specs') {
        return 'animation';
      }
      return value;
    };
    const normalizedInitial = normalizeTab(initialTab);
    const [active, setActive] = useState(normalizedInitial);
    const [componentsReady, setComponentsReady] = useState(hasRequiredComponents());

    if (useEffect){
      useEffect(() => {
        if (componentsReady) {
          return;
        }
        let cancelled = false;
        let attempts = 0;
        const attemptComponents = () => {
          if (cancelled) { return; }
          if (hasRequiredComponents()) {
            setComponentsReady(true);
            return;
          }
          attempts += 1;
          if (attempts >= 80) {
            console.warn('PWPL Admin: wp.components not ready, continuing with fallbacks.');
            setComponentsReady(true);
            return;
          }
          w.requestAnimationFrame(attemptComponents);
        };
        attemptComponents();
        return () => { cancelled = true; };
      }, [componentsReady]);

      useEffect(()=>{ if (!componentsReady) { return; } try { bindAggregateAutoSync(); } catch(e){} }, [componentsReady]);
      useEffect(()=>{ try { window.localStorage && window.localStorage.setItem(LS_KEY, active); } catch(e){} }, [active]);
    }

    if ( ! componentsReady ) {
      return h('div', { className:'pwpl-v1-loading', 'aria-live':'polite' }, 'Loading editor…');
    }
    return h('div', { className: 'pwpl-v1' }, [
      h(TopBar),
      h(Sidebar, { active, onChange: setActive }),
      h('main', { className: 'pwpl-v1-main', 'data-pwpl-tour':'table-editor-main' }, [
        active === 'layout' ? h(LayoutSpacingBlock) : null,
        active === 'typography' ? h(TypographyBlock) : null,
        active === 'colors' ? h(ColorsSurfacesBlock) : null,
        active === 'animation' ? h(AnimationBlock) : null,
        active === 'badges' ? h(BadgesBlock) : null,
        active === 'advanced' ? h(AdvancedBlock) : null,
        active === 'filters' ? h(FiltersBlock) : null,
      ])
    ]);
  }

  function FiltersBlock(){
    const initEnabled = Array.isArray(data.filters && data.filters.enabled) ? data.filters.enabled : [];
    const [enabled, setEnabled] = useState(new Set(initEnabled));
    const allowedMeta = (data.filters && data.filters.allowed) ? data.filters.allowed : { platform:[], period:[], location:[] };
    const catalog = (data.filters && data.filters.catalog) ? data.filters.catalog : { platform:[], period:[], location:[] };
    const dims = ['platform','period','location'];
    const initialAllowed = {
      platform: Array.isArray(allowedMeta.platform) ? allowedMeta.platform.slice() : [],
      period: Array.isArray(allowedMeta.period) ? allowedMeta.period.slice() : [],
      location: Array.isArray(allowedMeta.location) ? allowedMeta.location.slice() : [],
    };
    const [allowedValues, setAllowedValues] = useState(initialAllowed);
    const [platformOrder, setPlatformOrder] = useState(initialAllowed.platform.slice());
    const [draggingPlatform, setDraggingPlatform] = useState('');
    const [dragOverPlatform, setDragOverPlatform] = useState('');

    const toggleEnabled = (key)=>{
      const next = new Set(enabled); if (next.has(key)) next.delete(key); else next.add(key); setEnabled(next);
    };

    const labelMaps = dims.reduce((acc, key)=>{
      const list = Array.isArray(catalog[key]) ? catalog[key] : [];
      const map = {};
      list.forEach((item)=>{
        if (!item) return;
        const slug = item.slug || item.value || item.key || '';
        if (!slug) return;
        const label = item.label || item.name || slug;
        map[slug] = label;
      });
      acc[key] = map;
      return acc;
    }, {});

    const toggleAllowed = (dim, slug)=>{
      if (!slug) return;
      setAllowedValues((prev)=>{
        const current = Array.isArray(prev[dim]) ? prev[dim] : [];
        const has = current.includes(slug);
        const filtered = current.filter((val)=> val !== slug);
        const nextList = has ? filtered : [...filtered, slug];
        if (dim === 'platform'){
          setPlatformOrder(nextList.slice());
        }
        return Object.assign({}, prev, { [dim]: nextList });
      });
    };

    const renderSection = (key)=>{
      const currentList = Array.isArray(allowedValues[key]) ? allowedValues[key] : [];
      const currentSet = new Set(currentList);
      const options = Array.isArray(catalog[key]) ? catalog[key] : [];
      const title = (labelMaps[key] && labelMaps[key][key]) ? labelMaps[key][key] : key.charAt(0).toUpperCase()+key.slice(1);
      return h('div', { className:'pwpl-v1-grid', key:'sec-'+key }, [
        h('div', { style:{ gridColumn:'1 / -1' } }, h('strong', null, title)),
        options.map((item)=>{
          const slug = item && (item.slug || item.value || item.key) ? (item.slug || item.value || item.key) : '';
          if (!slug) return null;
          const label = (item && (item.label || item.name)) ? (item.label || item.name) : slug;
          const checked = currentSet.has(slug);
          return h('label', { key:key+'-'+slug, className:'components-base-control__label' }, [
            h('input', { type:'checkbox', checked, onChange:()=> toggleAllowed(key, slug) }), ' ', label,
            checked ? HiddenInput({ key:`allowed-${key}-${slug}`, name:`pwpl_table[allowed][${key}][]`, value: slug }) : null,
          ]);
        })
      ]);
    };

    const allowedPlatformSet = new Set(allowedValues.platform || []);
    const platformOrderDisplay = (()=> {
      const base = (platformOrder || []).filter((slug)=> allowedPlatformSet.has(slug));
      (allowedValues.platform || []).forEach((slug)=>{
        if (slug && !base.includes(slug)){
          base.push(slug);
        }
      });
      return base;
    })();

    const commitPlatformOrder = (nextList)=>{
      const clean = [];
      const seen = new Set();
      (nextList || []).forEach((slug)=>{
        if (allowedPlatformSet.has(slug) && !seen.has(slug)){
          clean.push(slug);
          seen.add(slug);
        }
      });
      (allowedValues.platform || []).forEach((slug)=>{
        if (!seen.has(slug) && slug){
          clean.push(slug);
          seen.add(slug);
        }
      });
      setPlatformOrder(clean);
      setAllowedValues((prev)=> Object.assign({}, prev, { platform: clean.slice() }));
    };

    const handleDropOn = (targetSlug)=> (event)=>{
      event.preventDefault();
      event.stopPropagation();
      const source = draggingPlatform;
      setDragOverPlatform('');
      setDraggingPlatform('');
      if (!source || !targetSlug || source === targetSlug) return;
      const filtered = platformOrderDisplay.filter((slug)=> slug !== source);
      const targetIndex = filtered.indexOf(targetSlug);
      if (targetIndex === -1){
        filtered.push(source);
      } else {
        filtered.splice(targetIndex, 0, source);
      }
      commitPlatformOrder(filtered);
    };

    const handleDropEnd = (event)=>{
      event.preventDefault();
      event.stopPropagation();
      const source = draggingPlatform;
      setDragOverPlatform('');
      setDraggingPlatform('');
      if (!source) return;
      const filtered = platformOrderDisplay.filter((slug)=> slug !== source);
      filtered.push(source);
      commitPlatformOrder(filtered);
    };

    const postId = parseInt(data.postId || 0, 10) || 0;
    const {
      searchTerm,
      setSearchTerm,
      openSections,
      matchesSearch,
      toggleSection,
      openFirstMatch,
    } = useAccordionSections('pwpl_v1_filters_open_sections_' + postId);

    const handleSearchKeyDown = (event)=>{
      if (event.key !== 'Enter') {
        return;
      }
      openFirstMatch(sections);
    };

    const renderCard = (content, title) => h('div', { className: 'pwpl-card' }, title ? [h('h3', { className: 'pwpl-card__title' }, title), content] : content);

    const enabledSection = h('div', { className:'pwpl-v1-grid' }, [
      dims.map((d)=> h('label', { key:'en-'+d, className:'components-base-control__label' }, [
        h('input', { type:'checkbox', checked: enabled.has(d), onChange:()=> toggleEnabled(d) }), ' Enable ', d
      ])),
      Array.from(enabled).map((d)=> HiddenInput({ key:'dim-'+d, name:'pwpl_table[dimensions][]', value:d })),
    ]);

    const listsSection = h('div', { className:'pwpl-v1-grid' }, [
      dims.map((d)=> enabled.has(d) ? renderSection(d) : null),
      enabled.size ? null : h('p', { style:{ color:'#475569', margin:0 } }, 'Enable a dimension to manage its allowed values.')
    ]);

    const platformSection = h('div', { className:'pwpl-v1-platform-order' }, [
      h('div', { className:'pwpl-v1-platform-order-header' }, [
        h('strong', null, 'Platform order'),
        h('span', null, 'Drag to reorder the allowed platforms.'),
      ]),
      platformOrderDisplay.length
        ? h('ul', { className:'pwpl-v1-platform-order-list' },
            platformOrderDisplay.map((slug)=>{
              const label = (labelMaps.platform && labelMaps.platform[slug]) ? labelMaps.platform[slug] : slug;
              const isDragging = draggingPlatform === slug;
              const isOver = dragOverPlatform === slug && draggingPlatform && draggingPlatform !== slug;
              return h('li', {
                key: 'order-'+slug,
                draggable: true,
                className: [
                  'pwpl-v1-platform-order-item',
                  isDragging ? 'is-dragging' : '',
                  isOver ? 'is-drag-over' : '',
                ].filter(Boolean).join(' '),
                onDragStart: (event)=> {
                  setDraggingPlatform(slug);
                  event.dataTransfer.effectAllowed = 'move';
                },
                onDragEnd: ()=> {
                  setDraggingPlatform('');
                  setDragOverPlatform('');
                },
                onDragOver: (event)=>{
                  event.preventDefault();
                  if (draggingPlatform && draggingPlatform !== slug){
                    setDragOverPlatform(slug);
                  }
                  event.dataTransfer.dropEffect = 'move';
                },
                onDragLeave: ()=>{
                  setDragOverPlatform((current)=> current === slug ? '' : current);
                },
                onDrop: handleDropOn(slug),
              }, label);
            })
          )
        : h('p', { className:'pwpl-v1-platform-order-empty' }, enabled.has('platform') ? 'Select allowed platforms to enable ordering.' : 'Enable the platform filter to set an order.'),
      platformOrderDisplay.length ? h('div', {
        className:'pwpl-v1-platform-dropzone',
        onDragOver:(event)=> { event.preventDefault(); event.dataTransfer.dropEffect = 'move'; },
        onDrop: handleDropEnd,
      }, 'Drop here to move to the end') : null,
      HiddenInput({ name:'pwpl_table[allowed_order][platform]', value: platformOrderDisplay.join(',') }),
    ]);

    const sections = [
      { id: 'filters-enable', title: 'Enable Filters', content: renderCard(enabledSection) },
      { id: 'filters-allowed', title: 'Allowed Lists', content: renderCard(listsSection) },
      { id: 'filters-platform-order', title: 'Platform Order', content: renderCard(platformSection) },
    ];

    return h('section', { className:'pwpl-v1-block' }, [
      SectionHeader({ title:'Filters', description:'Enable dimensions and choose allowed values.', dataTour:'table-filters-section' }),
      h(Card, null, h(CardBody, null,
        h(Accordion, {
          searchValue: searchTerm,
          onSearchChange: setSearchTerm,
          onSearchKeyDown: handleSearchKeyDown,
          onClear: () => setSearchTerm(''),
        },
          sections.map((section)=> h(AccordionItem, {
            key: section.id,
            id: section.id,
            title: section.title,
            isOpen: !!openSections[section.id],
            onToggle: toggleSection,
            hidden: !matchesSearch(section.title, section.keywords),
          }, section.content ))
        )
      ))
    ]);
  }

  function TypographyBlock(){
    const text = (data.card.text || {});
    const top  = (text.top || {});
    const typo = (data.card.typo || {});
    const scalar = (val) => toNumberOrToken(val);

    // Title visual color/family are sourced from the shared top text config
    const titleText = text.top || {};
    const subtitleText = text.subtitle || {};
    const priceText = text.price || {};
    const billingText = text.billing || {};
    const specsText = text.specs || {};

    const titleTypos = typo.title || {};
    const subtitleTypos = typo.subtitle || {};
    const priceTypos = typo.price || {};
    const billingTypos = typo.billing || {};
    const specsTypos = typo.specs || {};

    const priceCur = text.price_currency || {};
    const priceUnit = text.price_unit || {};
    const priceMuted = text.price_muted || {};

    const [topColor, setTopColor] = useState(top.color || '');
    const [topFamily, setTopFamily] = useState(top.family || '');
    const [priceCurColor, setPriceCurColor] = useState(priceCur.color || '');
    const [priceUnitColor, setPriceUnitColor] = useState(priceUnit.color || '');
    const [priceMutedColor, setPriceMutedColor] = useState(priceMuted.color || '');

    const cta = (data.ui && data.ui.cta) ? data.ui.cta : {};
    const ctaFont = cta.font || {};
    const ctaWeightInitial = ctaFont.weight != null ? String(ctaFont.weight) : (cta.weight != null ? String(cta.weight) : '');

    const updateColor = (setter, previewKey) => (next) => {
      const normalized = typeof next === 'string' ? next : '';
      setter(normalized);
      if (previewKey) {
        updatePreviewVars({ [previewKey]: normalized });
      }
    };

    const paletteControl = (labelNode, valueRef, handler) => h('div', { className: 'pwpl-v1-color' },
      h(ColorPaletteControl, {
        label: labelNode,
        value: valueRef || '',
        onChange: handler,
        allowAlpha: true,
        className: 'pwpl-inlinecolor--compact',
      })
    );

    const handleTopColorChange = updateColor(setTopColor, 'card.text.top.color');
    const handlePriceCurrencyColorChange = updateColor(setPriceCurColor);
    const handlePriceUnitColorChange = updateColor(setPriceUnitColor);
    const handlePriceMutedColorChange = updateColor(setPriceMutedColor);

    const makeValues = (textSource = {}, typoSource = {}) => ({
      color: textSource.color || '',
      family: textSource.family || '',
      size: scalar(typoSource.size),
      weight: scalar(typoSource.weight),
      tracking: scalar(typoSource.tracking),
      lineHeight: scalar(typoSource.line_height),
      italic: typoSource.italic,
      uppercase: typoSource.uppercase,
      smallcaps: typoSource.smallcaps,
      underline: typoSource.underline,
      strike: typoSource.strike,
      align: textSource.align || '',
    });

    const previewMap = (key, overrides = {}) => Object.assign({
      color: `card.text.${key}.color`,
      family: `card.text.${key}.family`,
      size: `card.typo.${key}.size`,
      weight: `card.typo.${key}.weight`,
    }, overrides);

    const priceExtras = h('div', { className: 'pwpl-typo__extras' }, [
      paletteControl('Currency color', priceCurColor, handlePriceCurrencyColorChange),
      HiddenInput({ name:'pwpl_table[card][text][price_currency][color]', value: priceCurColor }),
      paletteControl('Unit color', priceUnitColor, handlePriceUnitColorChange),
      HiddenInput({ name:'pwpl_table[card][text][price_unit][color]', value: priceUnitColor }),
      paletteControl('Original (muted) color', priceMutedColor, handlePriceMutedColorChange),
      HiddenInput({ name:'pwpl_table[card][text][price_muted][color]', value: priceMutedColor }),
    ]);

    const specsValues = Object.assign({}, makeValues(specsText, specsTypos), {
      size: scalar(specsText.size),
      weight: scalar(specsText.weight),
    });

    const ctaValues = Object.assign({}, makeValues({}, {}), {
      family: ctaFont.preset || '',
      size: scalar(ctaFont.size),
      weight: scalar(ctaWeightInitial),
      tracking: scalar(ctaFont.tracking || ''),
      lineHeight: scalar(ctaFont.line_height),
      uppercase: ctaFont.transform || 'none',
    });

    const titleShadowStyleInitial = titleTypos.shadow_style || 'custom';
    const titleShadowPresetInit = TEXT_SHADOW_PRESETS[titleShadowStyleInitial];
    const shadowDefaultValue = (rawValue, prop, fallback = 0) => {
      if (rawValue != null && rawValue !== '') {
        return scalar(rawValue, fallback);
      }
      if (titleShadowPresetInit && titleShadowPresetInit.single && typeof titleShadowPresetInit[prop] === 'number') {
        return titleShadowPresetInit[prop];
      }
      return fallback;
    };

    const renderCard = (child) => h('div', { className: 'pwpl-card' }, child);
    const renderTwoCards = (leftChild, rightChild) => {
      const columns = [];
      if (leftChild) {
        columns.push(
          h('div', { className: 'pwpl-col' }, renderCard(leftChild))
        );
      }
      if (rightChild) {
        columns.push(
          h('div', { className: 'pwpl-col' }, renderCard(rightChild))
        );
      }
      if (! columns.length) {
        return null;
      }
      return h('div', { className: 'pwpl-two' }, columns);
    };

    const sections = [
      {
        id: 'title-text',
        title: 'Title Text',
        content: h(TypographySection, {
          idKey: 'title',
          label: 'Title',
          names: {
            // Map title color to top text color so frontend can apply consistently
            color: 'pwpl_table[card][text][top][color]',
            family: 'pwpl_table[card][text][top][family]',
            size: 'pwpl_table[card][typo][title][size]',
            weight: 'pwpl_table[card][typo][title][weight]',
            align: 'pwpl_table[card][typo][title][align]',
            shadowEnable: 'pwpl_table[card][typo][title][shadow_enable]',
          shadowX: 'pwpl_table[card][typo][title][shadow_x]',
          shadowY: 'pwpl_table[card][typo][title][shadow_y]',
          shadowBlur: 'pwpl_table[card][typo][title][shadow_blur]',
          shadowColor: 'pwpl_table[card][typo][title][shadow_color]',
            shadowStyle: 'pwpl_table[card][typo][title][shadow_style]',
          },
          values: Object.assign({}, makeValues(titleText, titleTypos), {
            // Align is stored in typo.title
            align: (titleTypos.align || ''),
            shadowEnable: (titleTypos.shadow_enable ? '1' : ''),
            shadowX: shadowDefaultValue(titleTypos.shadow_x, 'x'),
            shadowY: shadowDefaultValue(titleTypos.shadow_y, 'y'),
            shadowBlur: shadowDefaultValue(titleTypos.shadow_blur, 'blur'),
            shadowColor: titleTypos.shadow_color || 'rgba(0,0,0,.5)',
            shadowStyle: titleShadowStyleInitial,
          }),
          onPreviewPatch: previewMap('title', {
            // Ensure title color/family preview target the shared top text config
            color: 'card.text.top.color',
            family: 'card.text.top.family',
            shadow_enabled: 'card.typo.title.shadow_enabled',
            shadow_x: 'card.typo.title.shadow_x',
            shadow_y: 'card.typo.title.shadow_y',
            shadow_blur: 'card.typo.title.shadow_blur',
            shadow_color: 'card.typo.title.shadow_color',
            shadow_style: 'card.typo.title.shadow_style',
            shadow: 'card.typo.title.shadow',
          }),
          layoutVariant: 'title-two-col',
        }),
      },
      {
        id: 'subtitle-text',
        title: 'Subtitle Text',
        content: renderCard(h(TypographySection, {
          idKey: 'subtitle',
          label: 'Subtitle',
          names: {
            color: 'pwpl_table[card][text][subtitle][color]',
            family: 'pwpl_table[card][text][subtitle][family]',
            size: 'pwpl_table[card][typo][subtitle][size]',
            weight: 'pwpl_table[card][typo][subtitle][weight]',
          },
          values: makeValues(subtitleText, subtitleTypos),
          onPreviewPatch: previewMap('subtitle'),
        })),
      },
      {
        id: 'price-text',
        title: 'Price Text',
        content: renderTwoCards(
          h(TypographySection, {
            idKey: 'price',
            label: 'Price',
            names: {
              color: 'pwpl_table[card][text][price][color]',
              family: 'pwpl_table[card][text][price][family]',
              size: 'pwpl_table[card][typo][price][size]',
              weight: 'pwpl_table[card][typo][price][weight]',
            },
            values: makeValues(priceText, priceTypos),
            onPreviewPatch: previewMap('price'),
          }),
          priceExtras
        ),
      },
      {
        id: 'billing-text',
        title: 'Billing Text',
        content: renderCard(h(TypographySection, {
          idKey: 'billing',
          label: 'Billing',
          names: {
            color: 'pwpl_table[card][text][billing][color]',
            family: 'pwpl_table[card][text][billing][family]',
            size: 'pwpl_table[card][typo][billing][size]',
            weight: 'pwpl_table[card][typo][billing][weight]',
          },
          values: makeValues(billingText, billingTypos),
          onPreviewPatch: previewMap('billing'),
        })),
      },
      {
        id: 'specs-text',
        title: 'Specs Text',
        content: renderCard(h(TypographySection, {
          idKey: 'specs',
          label: 'Specs',
          names: {
            color: 'pwpl_table[card][text][specs][color]',
            family: 'pwpl_table[card][text][specs][family]',
            size: 'pwpl_table[card][text][specs][size]',
            weight: 'pwpl_table[card][text][specs][weight]',
          },
          values: specsValues,
          onPreviewPatch: previewMap('specs', {
            size: 'card.text.specs.size',
            weight: 'card.text.specs.weight',
          }),
        })),
      },
      {
        id: 'cta-typography',
        title: 'CTA Typography',
        content: renderCard(h(TypographySection, {
          idKey: 'cta',
          label: 'CTA Label',
          names: {
            family: 'pwpl_table[ui][cta][font][preset]',
            size: 'pwpl_table[ui][cta][font][size]',
            weight: 'pwpl_table[ui][cta][font][weight]',
            tracking: 'pwpl_table[ui][cta][font][tracking]',
            uppercase: 'pwpl_table[ui][cta][font][transform]',
          },
          values: ctaValues,
          fontOptions: CTA_FONT_OPTIONS,
          weightOptions: CTA_WEIGHT_OPTIONS,
          sizeRange: { min: 10, max: 48, step: 1, unit: 'px', placeholder: 'inherit' },
          trackingRange: { min: -0.2, max: 0.4, step: 0.01, unit: 'em', placeholder: '0.05' },
          showColor: false,
          flagValueMap: {
            uppercase: { on: 'uppercase', off: 'none' },
          },
          onPreviewPatch: {
            family: 'ui.cta.font.preset',
            size: 'ui.cta.font.size',
            weight: 'ui.cta.font.weight',
            tracking: 'ui.cta.font.tracking',
            uppercase: 'ui.cta.font.transform',
          },
        })),
      },
    ].map((section) => Object.assign({}, section, { keywords: [section.title] }));

    const postId = parseInt(data.postId || 0, 10) || 0;
    const { searchTerm, setSearchTerm, openSections, matchesSearch, toggleSection, openFirstMatch } = useAccordionSections('pwpl_v1_typo_open_sections_' + postId);

    const handleSearchKeyDown = ( event ) => {
      if ( event.key !== 'Enter' ) { return; }
      openFirstMatch( sections );
    };

    return h('section', { className: 'pwpl-v1-block' }, [
      SectionHeader({ title: i18n(data.i18n.sidebar.typography), description: 'All typography: Top area, per-element overrides, Price/Billing, and Specs.' }),
      h(Card, null, h(CardBody, null,
        h(Accordion, {
          searchValue: searchTerm,
          onSearchChange: setSearchTerm,
          onSearchKeyDown: handleSearchKeyDown,
          onClear: () => setSearchTerm(''),
        },
          sections.map( ( section ) => h( AccordionItem, {
            key: section.id,
            id: section.id,
            title: section.title,
            isOpen: !! openSections[ section.id ],
            onToggle: toggleSection,
            hidden: ! matchesSearch( section.title ),
          }, section.content ) )
        )
      ))
    ]);
  }

  function ColorsSurfacesBlock(){
    const colors = (data.card.colors || {});
    const keyline = (colors.keyline || {});
    const scalar = (val, fallback = '') => {
      const normalized = toNumberOrToken(val);
      if (normalized === '' && fallback !== undefined) {
        return fallback;
      }
      return normalized;
    };
    const [topBg, setTopBg] = useState(colors.top_bg || '');
    const [specsBg, setSpecsBg] = useState(colors.specs_bg || '');
    const [kColor, setKColor] = useState(keyline.color || '');
    const [kOpacity, setKOpacity] = useState(scalar(keyline.opacity, ''));
    const topGrad = colors.top_grad || {};
    const specsGrad = colors.specs_grad || {};
    const [topType, setTopType] = useState(topGrad.type || '');
    const [topStart, setTopStart] = useState(topGrad.start || '');
    const [topEnd, setTopEnd] = useState(topGrad.end || '');
    const [topAngle, setTopAngle] = useState(scalar(topGrad.angle, '180'));
    const [topSP, setTopSP] = useState(scalar(topGrad.start_pos, '0'));
    const [topEP, setTopEP] = useState(scalar(topGrad.end_pos, '100'));

    const [specsType, setSpecsType] = useState(specsGrad.type || '');
    const [specsStart, setSpecsStart] = useState(specsGrad.start || '');
    const [specsEnd, setSpecsEnd] = useState(specsGrad.end || '');
    const [specsAngle, setSpecsAngle] = useState(scalar(specsGrad.angle, '180'));
    const [specsSP, setSpecsSP] = useState(scalar(specsGrad.start_pos, '0'));
    const [specsEP, setSpecsEP] = useState(scalar(specsGrad.end_pos, '100'));

    const cta = (data.ui && data.ui.cta) ? data.ui.cta : {};
    const ctaNormal = cta.normal || {};
    const ctaHover = cta.hover || {};
    const [ctaNormalBg, setCtaNormalBg] = useState(ctaNormal.bg || '');
    const [ctaNormalColor, setCtaNormalColor] = useState(ctaNormal.color || '');
    const [ctaNormalBorder, setCtaNormalBorder] = useState(ctaNormal.border || '');
    const [ctaHoverBg, setCtaHoverBg] = useState(ctaHover.bg || '');
    const [ctaHoverColor, setCtaHoverColor] = useState(ctaHover.color || '');
    const [ctaHoverBorder, setCtaHoverBorder] = useState(ctaHover.border || '');
    const [ctaFocusColor, setCtaFocusColor] = useState(cta.focus || '');
    const [openCtaEditors, setOpenCtaEditors] = useState({ normal: null, hover: null, focus: null });
    const ctaRowRefs = useRef({});

    const postId = parseInt(data.postId || 0, 10) || 0;
    const {
      searchTerm,
      setSearchTerm,
      openSections,
      matchesSearch,
      toggleSection,
      openFirstMatch,
    } = useAccordionSections('pwpl_v1_colors_open_sections_' + postId);

    const handleColorChange = (setter, previewKey)=> (value)=>{
      const normalized = typeof value === 'string' ? value : '';
      setter(normalized);
      if (previewKey) {
        updatePreviewVars({ [previewKey]: normalized });
      }
    };

    const paletteField = (label, value, handler)=> h('div', { className:'pwpl-v1-color' },
      h(ColorPaletteControl, {
        label,
        value: value || '',
        onChange: handler,
        allowAlpha: true,
        className: 'pwpl-inlinecolor--compact',
      })
    );

    const handleSearchKeyDown = (event)=>{
      if (event.key !== 'Enter') {
        return;
      }
      openFirstMatch(sections);
    };

    const showTopAngle = topType === 'linear';
    const topSection = BackgroundTabsSection({
      id: 'pwpl-bg-top',
      label: 'Top Background',
      colorLabel: 'Top background',
      colorValue: topBg,
      onColorChange: handleColorChange(setTopBg, 'card.colors.top_bg'),
      colorName: 'pwpl_table[card][colors][top_bg]',
      gradient: {
        type: topType,
        defaultType: 'linear',
        onTypeChange: (next) => {
          setTopType(next);
          updatePreviewVars({ 'card.colors.top_grad.type': next || '' });
        },
        names: {
          type: 'pwpl_table[card][colors][top_grad][type]',
          start: 'pwpl_table[card][colors][top_grad][start]',
          end: 'pwpl_table[card][colors][top_grad][end]',
          angle: 'pwpl_table[card][colors][top_grad][angle]',
          startPos: 'pwpl_table[card][colors][top_grad][start_pos]',
          endPos: 'pwpl_table[card][colors][top_grad][end_pos]',
        },
        start: topStart,
        onStartChange: handleColorChange(setTopStart, 'card.colors.top_grad.start'),
        end: topEnd,
        onEndChange: handleColorChange(setTopEnd, 'card.colors.top_grad.end'),
        angle: topAngle,
        onAngleChange: setTopAngle,
        showAngle: showTopAngle,
        startPos: topSP,
        onStartPosChange: setTopSP,
        endPos: topEP,
        onEndPosChange: setTopEP,
      },
    });

    const showSpecsAngle = specsType === 'linear';
    const specsSection = BackgroundTabsSection({
      id: 'pwpl-bg-specs',
      label: 'Specs Background',
      colorLabel: 'Specs background',
      colorValue: specsBg,
      onColorChange: handleColorChange(setSpecsBg, 'card.colors.specs_bg'),
      colorName: 'pwpl_table[card][colors][specs_bg]',
      gradient: {
        type: specsType,
        defaultType: 'linear',
        onTypeChange: (next) => {
          setSpecsType(next);
          updatePreviewVars({ 'card.colors.specs_grad.type': next || '' });
        },
        names: {
          type: 'pwpl_table[card][colors][specs_grad][type]',
          start: 'pwpl_table[card][colors][specs_grad][start]',
          end: 'pwpl_table[card][colors][specs_grad][end]',
          angle: 'pwpl_table[card][colors][specs_grad][angle]',
          startPos: 'pwpl_table[card][colors][specs_grad][start_pos]',
          endPos: 'pwpl_table[card][colors][specs_grad][end_pos]',
        },
        start: specsStart,
        onStartChange: handleColorChange(setSpecsStart, 'card.colors.specs_grad.start'),
        end: specsEnd,
        onEndChange: handleColorChange(setSpecsEnd, 'card.colors.specs_grad.end'),
        angle: specsAngle,
        onAngleChange: setSpecsAngle,
        showAngle: showSpecsAngle,
        startPos: specsSP,
        onStartPosChange: setSpecsSP,
        endPos: specsEP,
        onEndPosChange: setSpecsEP,
      },
    });

    const keylineSection = h('div', { className:'pwpl-v1-grid' }, [
      paletteField('Keyline color', kColor, handleColorChange(setKColor)),
      HiddenInput({ name:'pwpl_table[card][colors][keyline][color]', value: kColor }),
      RangeValueRow({
        label:'Keyline opacity (0–1)',
        name:'pwpl_table[card][colors][keyline][opacity]',
        value: kOpacity,
        onChange: setKOpacity,
        min: 0,
        max: 1,
        step: 0.01,
        unit: '',
      }),
    ]);

    const themePalettesSection = h('div', { className:'pwpl-v1-grid' }, [
      h('p', { style:{ color:'#475569', fontSize:'13px', margin:0 } }, 'Use saved and recent swatches inside each palette to build quick brand themes. Presets land soon.')
    ]);

    const handleCtaColorChange = (setter, key)=> (value = '')=>{
      const normalized = normalizeColorValue(value, true);
      setter(normalized);
      updatePreviewVars({ [key]: normalized });
    };

    const CTA_SECTION_CONFIG = {
      normal: [
        { key: 'bg', label: 'Background', value: ctaNormalBg, setter: setCtaNormalBg, previewKey: 'ui.cta.normal.bg', hiddenName: 'pwpl_table[ui][cta][normal][bg]' },
        { key: 'text', label: 'Text', value: ctaNormalColor, setter: setCtaNormalColor, previewKey: 'ui.cta.normal.color', hiddenName: 'pwpl_table[ui][cta][normal][color]' },
        { key: 'border', label: 'Border', value: ctaNormalBorder, setter: setCtaNormalBorder, previewKey: 'ui.cta.normal.border', hiddenName: 'pwpl_table[ui][cta][normal][border]' },
      ],
      hover: [
        { key: 'bg', label: 'Background', value: ctaHoverBg, setter: setCtaHoverBg, previewKey: 'ui.cta.hover.bg', hiddenName: 'pwpl_table[ui][cta][hover][bg]' },
        { key: 'text', label: 'Text', value: ctaHoverColor, setter: setCtaHoverColor, previewKey: 'ui.cta.hover.color', hiddenName: 'pwpl_table[ui][cta][hover][color]' },
        { key: 'border', label: 'Border', value: ctaHoverBorder, setter: setCtaHoverBorder, previewKey: 'ui.cta.hover.border', hiddenName: 'pwpl_table[ui][cta][hover][border]' },
      ],
      focus: [
        { key: 'outline', label: 'Outline', value: ctaFocusColor, setter: setCtaFocusColor, previewKey: 'ui.cta.focus', hiddenName: 'pwpl_table[ui][cta][focus]' },
      ],
    };
    const getCtaRowKey = (sectionKey, fieldKey) => `${sectionKey}__${fieldKey}`;
    const compareColorValue = (colorValue) => (normalizeColorValue(colorValue, true) || '').toLowerCase();
    const renderCtaField = (sectionKey) => (field) => {
      const normalizedValue = normalizeColorValue(field.value, true) || '';
      const normalizedValueKey = normalizedValue.toLowerCase();
      const isEditing = openCtaEditors[sectionKey] === field.key;
      const commitColor = handleCtaColorChange(field.setter, field.previewKey);
      const rowKey = getCtaRowKey(sectionKey, field.key);
      const previewCss = (() => {
        const col = parseCssColor(normalizedValue);
        return rgbaString(col);
      })();
      const copyPreview = () => {
        const col = parseCssColor(normalizedValue);
        const out = (col.a != null && col.a < 1)
          ? rgbaToHex8(col.r, col.g, col.b, col.a).toUpperCase()
          : rgbToHex({ r: col.r, g: col.g, b: col.b }).toUpperCase();
        try {
          if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(out);
          } else {
            const el = document.createElement('textarea');
            el.value = out; el.setAttribute('readonly',''); el.style.position='absolute'; el.style.left='-9999px';
            document.body.appendChild(el); el.select(); document.execCommand('copy'); document.body.removeChild(el);
          }
        } catch(e){}
      };
      const handleOpen = () => {
        setOpenCtaEditors((prev) => {
          if (prev[sectionKey] === field.key) {
            return prev;
          }
          return { ...prev, [sectionKey]: field.key };
        });
        requestAnimationFrame(() => {
          const node = ctaRowRefs.current[rowKey];
          if (node && node.scrollIntoView) {
            node.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
          }
        });
      };
      const handleClose = () => {
        setOpenCtaEditors((prev) => ({ ...prev, [sectionKey]: null }));
      };
      const swatchButtons = DEFAULT_COLOR_SWATCHES.map((swatch) => {
        const swatchValue = compareColorValue(swatch.value || '');
        const isActive = normalizedValueKey === swatchValue;
        const className = classNames('pwpl-cta-swatch', isActive ? 'is-active' : '', !swatch.value ? 'is-none' : '');
        const style = swatch.value ? { backgroundColor: swatch.value } : undefined;
        return h('button', {
          key: `${rowKey}-${swatch.label}`,
          type: 'button',
          className,
          style,
          'aria-pressed': isActive,
          onClick: () => {
            if (isActive) {
              handleOpen();
              return;
            }
            commitColor(swatch.value || '');
          },
        }, swatch.value ? null : h('span', { className: 'pwpl-cta-swatch-clear', 'aria-hidden': 'true' }, '×'));
      });
      const hasSwatchMatch = DEFAULT_COLOR_SWATCHES.some((swatch) => compareColorValue(swatch.value || '') === normalizedValueKey);
      const customButton = h('button', {
        type: 'button',
        className: classNames('pwpl-cta-swatch', 'is-custom', (!hasSwatchMatch || isEditing) ? 'is-active' : ''),
        onClick: handleOpen,
        'aria-pressed': isEditing || !hasSwatchMatch,
      }, 'Custom');
      const editor = isEditing ? h('div', { className: 'pwpl-cta-color-field__editor' },
        h(BackgroundColorPanel, {
          label: `${field.label} color`,
          value: normalizedValue || '',
          onChange: commitColor,
          autoOpen: true,
          onAfterSave: handleClose,
          onAfterCancel: handleClose,
        })
      ) : null;
      return h('div', {
        key: rowKey,
        className: classNames('pwpl-cta-color-field', isEditing ? 'is-editing' : ''),
        ref: (node) => {
          if (node) {
            ctaRowRefs.current[rowKey] = node;
          } else {
            delete ctaRowRefs.current[rowKey];
          }
        },
      }, [
        h('div', { className: 'pwpl-cta-color-field__head' }, [
          h('span', { className: 'pwpl-cta-color-field__label' }, field.label),
          h('button', {
            type: 'button',
            className: 'pwpl-cta-color-preview',
            title: 'Click to edit color',
            onClick: handleOpen,
            onKeyDown: (e)=>{ if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); handleOpen(); } },
            'aria-label': 'Click to edit color',
          }, h('span', { className: 'pwpl-cta-color-preview__fill', style: { background: previewCss } })),
          h('div', { className: 'pwpl-cta-color-field__swatches' }, [
            ...swatchButtons,
            customButton,
          ]),
        ]),
        editor,
        HiddenInput({ name: field.hiddenName, value: field.value || '' }),
      ]);
    };
    const renderCtaSection = (sectionKey, title) => h('div', { className:'pwpl-v1-cta-color-state' }, [
      h('strong', null, title),
      h('div', { className:'pwpl-v1-cta-color-row' },
        (CTA_SECTION_CONFIG[sectionKey] || []).map(renderCtaField(sectionKey))
      )
    ]);
    const ctaColorsSection = h('div', { className:'pwpl-v1-grid pwpl-v1-cta-colors' }, [
      renderCtaSection('normal', 'Normal'),
      renderCtaSection('hover', 'Hover'),
      renderCtaSection('focus', 'Focus'),
    ]);

    const wrapCard = (content) => h('div', { className: 'pwpl-card' }, content);
    const twoCard = (left, right) => {
      const cols = [];
      if (left) cols.push(h('div', { className: 'pwpl-col' }, wrapCard(left)));
      if (right) cols.push(h('div', { className: 'pwpl-col' }, wrapCard(right)));
      if (!cols.length) return null;
      return h('div', { className: 'pwpl-two' }, cols);
    };

    const topSpecsCards = twoCard(
      h(React.Fragment, null, [
        h('h3', { className: 'pwpl-card__title' }, 'Top Background'),
        topSection,
      ]),
      h(React.Fragment, null, [
        h('h3', { className: 'pwpl-card__title' }, 'Specs Background'),
        specsSection,
      ])
    );

    const keylineCard = wrapCard(h(React.Fragment, null, [
      h('h3', { className: 'pwpl-card__title' }, 'Keyline'),
      keylineSection,
    ]));

    const presetCard = wrapCard(h(React.Fragment, null, [
      h('h3', { className: 'pwpl-card__title' }, 'Theme Palettes'),
      themePalettesSection,
    ]));

    const ctaCard = wrapCard(h(React.Fragment, null, [
      h('h3', { className: 'pwpl-card__title' }, 'CTA Colors'),
      ctaColorsSection,
    ]));

    const sections = [
      { id: 'colors-top', title: 'Top & Specs Background', content: topSpecsCards },
      { id: 'colors-keyline', title: 'Keyline', content: keylineCard },
      { id: 'colors-presets', title: 'Theme Palettes', content: presetCard },
      { id: 'colors-cta', title: 'CTA Colors', content: ctaCard },
    ];

    return h('section', { className:'pwpl-v1-block' }, [
      SectionHeader({ title: i18n(data.i18n.sidebar.colors), description: 'Surface colors. Gradients can be added in a later pass.', dataTour:'table-theme-section' }),
      h(Card, null, h(CardBody, null,
        h(Accordion, {
          searchValue: searchTerm,
          onSearchChange: setSearchTerm,
          onSearchKeyDown: handleSearchKeyDown,
          onClear: () => setSearchTerm(''),
        },
          sections.map((section)=> h(AccordionItem, {
            key: section.id,
            id: section.id,
            title: section.title,
            isOpen: !!openSections[section.id],
            onToggle: toggleSection,
            hidden: !matchesSearch(section.title, section.keywords),
          }, section.content ))
        )
      ))
    ]);
  }


  function BadgesBlock(){
    const init = (data.badges || { period:[], location:[], platform:[], priority:['period','location','platform'], shadow:0 });
    const [shadow, setShadow] = useState(parseInt(init.shadow||0,10)||0);
    const [period, setPeriod] = useState([...(init.period||[])]);
    const [location, setLocation] = useState([...(init.location||[])]);
    const [platform, setPlatform] = useState([...(init.platform||[])]);
    const dims = [
      { key:'period',   label:'Period',   state: period,   set: setPeriod },
      { key:'location', label:'Location', state: location, set: setLocation },
      { key:'platform', label:'Platform', state: platform, set: setPlatform },
    ];
    const tones = ['', 'success','info','warning','danger','neutral'];

    const Row = ({dim, idx, item})=>{
      const update = (field, value)=>{
        const list = [...dim.state];
        list[idx] = Object.assign({}, list[idx]||{}, { [field]: value });
        dim.set(list);
      };
      return h('div', { className:'pwpl-v1-grid', key: dim.key+idx }, [
        h(TextControl, { label:'Value (slug)', value:item.slug||'', onChange:(v)=> update('slug', v) }),
        HiddenInput({ name:`pwpl_table_badges[${dim.key}][${idx}][slug]`, value:item.slug||'' }),
        h(TextControl, { label:'Badge label', value:item.label||'', onChange:(v)=> update('label', v) }),
        HiddenInput({ name:`pwpl_table_badges[${dim.key}][${idx}][label]`, value:item.label||'' }),
        h('div', { className:'pwpl-v1-color' }, [
          h(ColorPaletteControl, {
            label: 'Badge color',
            value: item.color || '',
            onChange: (val) => update('color', typeof val === 'string' ? val : ''),
            allowAlpha: true,
            className: 'pwpl-inlinecolor--compact',
          }),
          HiddenInput({ name:`pwpl_table_badges[${dim.key}][${idx}][color]`, value:item.color||'' }),
        ]),
        h('div', { className:'pwpl-v1-color' }, [
          h(ColorPaletteControl, {
            label: 'Text color',
            value: item.text_color || '',
            onChange: (val) => update('text_color', typeof val === 'string' ? val : ''),
            allowAlpha: true,
            className: 'pwpl-inlinecolor--compact',
          }),
          HiddenInput({ name:`pwpl_table_badges[${dim.key}][${idx}][text_color]`, value:item.text_color||'' }),
        ]),
        h(TextControl, { label:'Icon', value:item.icon||'', onChange:(v)=> update('icon', v) }),
        HiddenInput({ name:`pwpl_table_badges[${dim.key}][${idx}][icon]`, value:item.icon||'' }),
        h('div', null, [
          h('label', { className:'components-base-control__label' }, 'Tone'),
          h('select', { value:item.tone||'', onChange:(e)=> update('tone', e.target.value) }, tones.map(t=> h('option', { key:t||'none', value:t }, t? t[0].toUpperCase()+t.slice(1) : 'Auto'))),
          HiddenInput({ name:`pwpl_table_badges[${dim.key}][${idx}][tone]`, value:item.tone||'' }),
        ]),
        h(TextControl, { label:'Start (YYYY-MM-DD)', value:item.start||'', onChange:(v)=> update('start', v) }),
        HiddenInput({ name:`pwpl_table_badges[${dim.key}][${idx}][start]`, value:item.start||'' }),
        h(TextControl, { label:'End (YYYY-MM-DD)', value:item.end||'', onChange:(v)=> update('end', v) }),
        HiddenInput({ name:`pwpl_table_badges[${dim.key}][${idx}][end]`, value:item.end||'' }),
        h('button', { type:'button', className:'button', onClick:()=>{
          const list = [...dim.state]; list.splice(idx,1); dim.set(list);
        }}, 'Remove')
      ]);
    };

    const addRow = (dim)=>{
      dim.set([...(dim.state||[]), { slug:'', label:'', color:'', text_color:'', icon:'', tone:'', start:'', end:'' }]);
    };

    const renderCard = (content, title) => h('div', { className: 'pwpl-card' }, title ? [h('h3', { className: 'pwpl-card__title' }, title), content] : content);

    const Priority = ()=>{
      const dims = ['period','location','platform'];
      const initP = (init.priority && init.priority.length) ? init.priority : dims;
      const [p1,setP1] = useState(initP[0]||'period');
      const [p2,setP2] = useState(initP[1]||'location');
      const [p3,setP3] = useState(initP[2]||'platform');
      const Select = ({value,onChange})=> h('select', { value, onChange }, dims.map(d=> h('option', { key:d, value:d }, d)));
      return h('div', { className:'pwpl-v1-grid' }, [
        h('div', null, [ h('label', { className:'components-base-control__label' }, 'Priority 1'), Select({value:p1,onChange:(e)=>setP1(e.target.value)}), HiddenInput({ name:'pwpl_table_badges[priority][0]', value:p1 }) ]),
        h('div', null, [ h('label', { className:'components-base-control__label' }, 'Priority 2'), Select({value:p2,onChange:(e)=>setP2(e.target.value)}), HiddenInput({ name:'pwpl_table_badges[priority][1]', value:p2 }) ]),
        h('div', null, [ h('label', { className:'components-base-control__label' }, 'Priority 3'), Select({value:p3,onChange:(e)=>setP3(e.target.value)}), HiddenInput({ name:'pwpl_table_badges[priority][2]', value:p3 }) ]),
        h(NumberControl || TextControl, { label:'Badge shadow intensity', value:shadow, min:0, max:60, onChange:(v)=> setShadow(parseInt(v||0,10)||0) }),
        HiddenInput({ name:'pwpl_table_badges[shadow]', value:shadow }),
      ]);
    };

    return h('section', { className:'pwpl-v1-block' }, [
      SectionHeader({ title: i18n(data.i18n.sidebar.badges), description: 'Table-level promotions by period/location/platform.' }),
      h(Card, null, h(CardBody, null,
        h(TabPanel, { tabs:[
          { name:'period', title: i18n(data.i18n.tabs.period) },
          { name:'location', title: i18n(data.i18n.tabs.location) },
          { name:'platform', title: i18n(data.i18n.tabs.platform) },
          { name:'priority', title: i18n(data.i18n.tabs.priority) },
        ]}, (tab)=>{
          if (tab.name==='priority') return renderCard(Priority(), 'Priority & Shadow');
          const dim = dims.find(d=> d.key===tab.name) || dims[0];
          return renderCard(h('div', null, [
            (dim.state||[]).map((row,idx)=> Row({ dim, idx, item: row })),
            h('div', { style:{ marginTop:'10px' } }, h('button', { type:'button', className:'button button-primary', onClick:()=> addRow(dim) }, 'Add Promotion')),
          ]), dim.label);
        })
      ))
    ]);
  }

  function AdvancedBlock(){
    const adv = (data.ui && data.ui.advanced) ? data.ui.advanced : { trust_trio:0, sticky_cta:0, trust_items:[] };
    const [trustTrio, setTrustTrio] = useState(adv.trust_trio ? 1 : 0);
    const [sticky, setSticky]       = useState(adv.sticky_cta ? 1 : 0);
    const [items, setItems]         = useState(Array.isArray(adv.trust_items) ? adv.trust_items.join('\n') : '');

    const postId = parseInt(data.postId || 0, 10) || 0;
    const {
      searchTerm,
      setSearchTerm,
      openSections,
      matchesSearch,
      toggleSection,
      openFirstMatch,
    } = useAccordionSections('pwpl_v1_advanced_open_sections_' + postId);

    const handleSearchKeyDown = (event)=>{
      if (event.key !== 'Enter') {
        return;
      }
      openFirstMatch(sections);
    };

    const renderCard = (content, title) => h('div', { className: 'pwpl-card' }, title ? [h('h3', { className: 'pwpl-card__title' }, title), content] : content);

    const trustSection = h('div', { className:'pwpl-v1-grid' }, [
      h('label', { className:'components-base-control__label' }, [
        h('input', { type:'checkbox', checked: !!trustTrio, onChange:(e)=> setTrustTrio(e.target.checked?1:0) }), ' Show trust row under CTA'
      ]),
      HiddenInput({ name:'pwpl_table[ui][trust_trio]', value: trustTrio ? 1 : '' }),
      h('label', { className:'components-base-control__label' }, [
        h('input', { type:'checkbox', checked: !!sticky, onChange:(e)=> setSticky(e.target.checked?1:0) }), ' Enable sticky mobile summary bar'
      ]),
      HiddenInput({ name:'pwpl_table[ui][sticky_cta]', value: sticky ? 1 : '' }),
      h('div', { style:{ gridColumn:'1 / -1' } }, [
        h('label', { className:'components-base-control__label' }, 'Trust items (one per line)'),
        h('textarea', { name:'pwpl_table[ui][trust_items]', value: items, onChange:(e)=> setItems(e.target.value), rows: 4, style:{ width:'100%' } }),
      ]),
    ]);

    const sections = [
      { id: 'advanced-trust', title: 'Trust & Inline CTA', content: renderCard(trustSection) },
    ];

    return h('section', { className:'pwpl-v1-block' }, [
      SectionHeader({ title: i18n(data.i18n.sidebar.advanced), description: 'Trust row and sticky CTA controls.' }),
      h(Card, null, h(CardBody, null,
        h(Accordion, {
          searchValue: searchTerm,
          onSearchChange: setSearchTerm,
          onSearchKeyDown: handleSearchKeyDown,
          onClear: () => setSearchTerm(''),
        },
          sections.map((section)=> h(AccordionItem, {
            key: section.id,
            id: section.id,
            title: section.title,
            isOpen: !!openSections[section.id],
            onToggle: toggleSection,
            hidden: !matchesSearch(section.title, section.keywords),
          }, section.content ))
        )
      ))
    ]);
  }


  // Hide legacy sections once parity is achieved (non-destructive)
  function hideLegacyOnMount(){
    // Hide legacy Badges meta box to avoid duplicate UIs
    const ids = ['pwpl_table_badges', 'pwpl_table_layout', 'pwpl_table_shortcode'];
    ids.forEach((id)=>{ const el = document.getElementById(id); if (el) el.style.display = 'none'; });

    // Best-effort: hide legacy Text styles and Colors & surfaces blocks within the old meta UI
    const hideClosest = (selector)=>{
      const el = document.querySelector(selector);
      if (!el) return;
      const block = el.closest('.pwpl-field') || el.closest('.pwpl-meta') || el.parentElement;
      if (block) block.style.display = 'none';
    };
    hideClosest('input[name="pwpl_table[card][text][top][color]"]'); // Text styles block
    hideClosest('input[name="pwpl_table[card][colors][top_bg]"]');    // Colors & surfaces block
    // Typography sizes/weights
    const typoNames = [
      'pwpl_table[card][typo][title][size]','pwpl_table[card][typo][title][weight]',
      'pwpl_table[card][typo][subtitle][size]','pwpl_table[card][typo][subtitle][weight]',
      'pwpl_table[card][typo][price][size]','pwpl_table[card][typo][price][weight]'
    ];
    typoNames.forEach((n)=> hideClosest(`[name="${n}"]`));

    // Hide legacy CTA size/layout now that CTA block exists in V1
    const ctaNames = [
      'pwpl_table[ui][cta][height]',
      'pwpl_table[ui][cta][pad_x]',
      'pwpl_table[ui][cta][radius]',
      'pwpl_table[ui][cta][border_width]',
      'pwpl_table[ui][cta][min_w]',
      'pwpl_table[ui][cta][max_w]',
      'pwpl_table[ui][cta][lift]'
    ];
    ctaNames.forEach((n)=> hideClosest(`[name="${n}"]`));
    // CTA width is a select; handle it separately
    hideClosest('select[name="pwpl_table[ui][cta][width]"]');
    const ctaColorNames = [
      'pwpl_table[ui][cta][normal][bg]',
      'pwpl_table[ui][cta][normal][color]',
      'pwpl_table[ui][cta][normal][border]',
      'pwpl_table[ui][cta][hover][bg]',
      'pwpl_table[ui][cta][hover][color]',
      'pwpl_table[ui][cta][hover][border]',
      'pwpl_table[ui][cta][focus]'
    ];
    ctaColorNames.forEach((n)=> hideClosest(`[name="${n}"]`));
    const ctaFontNames = [
      'pwpl_table[ui][cta][font][preset]',
      'pwpl_table[ui][cta][font][size]',
      'pwpl_table[ui][cta][font][transform]',
      'pwpl_table[ui][cta][font][weight]',
      'pwpl_table[ui][cta][font][tracking]'
    ];
    ctaFontNames.forEach((n)=> hideClosest(`[name="${n}"]`));

    // Hide legacy Specs style/interactions now that Specs block exists in V1
    hideClosest('select[name="pwpl_table[ui][specs_style]"]');
    const specAnimNames = [
      'pwpl_table[ui][specs_anim][intensity]',
      'pwpl_table[ui][specs_anim][mobile]'
    ];
    specAnimNames.forEach((n)=> hideClosest(`[name="${n}"]`));
    // Flags checkboxes: generic selector by name starts-with
    const flagInputs = document.querySelectorAll('input[type="checkbox"][name^="pwpl_table[ui][specs_anim][flags]"]');
    flagInputs.forEach((inp)=>{ const block = inp.closest('.pwpl-field') || inp.closest('.pwpl-meta'); if (block) block.style.display = 'none'; });
  }

  function mount(){
    const root = document.getElementById('pwpl-admin-v1-root');
    if (!root || !wp || !wp.element || !h) return;
    if (wp.element.render) {
      wp.element.render(h(App), root);
    } else if (wp.element.createRoot) {
      const rootApi = wp.element.createRoot(root);
      rootApi.render(h(App));
    }
    hideLegacyOnMount();
  }

  if (document.readyState === 'complete' || document.readyState === 'interactive') {
    setTimeout(mount, 0);
  } else {
    document.addEventListener('DOMContentLoaded', mount);
  }

})(window);
