(function(w){
  const wp = w.wp || window.wp || {};
  const element = wp.element || {};
  const { createElement: h, useState, useEffect } = element;

  const REQUIRED_COMPONENTS = [ 'Card', 'CardBody', 'TabPanel', 'TextControl', 'ColorPicker' ];
  const getWPComponents = () => (w.wp && w.wp.components) || {};
  const hasRequiredComponents = () => {
    const cmp = getWPComponents();
    return REQUIRED_COMPONENTS.every((key) => typeof cmp[ key ] === 'function');
  };
  const classNames = (base, extra) => extra ? base + ' ' + extra : base;

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
  const FallbackColorPicker = (props = {}) => {
    const { color, onChangeComplete, onChange, className = '' } = props;
    const handleChange = ( event ) => {
      const next = event && event.target ? event.target.value : event;
      if ( typeof onChangeComplete === 'function' ) {
        onChangeComplete( next );
      } else if ( typeof onChange === 'function' ) {
        onChange( next );
      }
    };
    return h('input', {
      type: 'text',
      value: typeof color === 'string' ? color : '',
      onChange: handleChange,
      placeholder: '#FFFFFF',
      className: classNames( 'pwpl-colorpicker-fallback components-color-picker__input', className ),
    });
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
  const ColorPicker = createComponentProxy( 'ColorPicker', FallbackColorPicker );
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
  const ColorPaletteControl = typeof BaseColorPalette === 'function' ? BaseColorPalette : PaletteFallback;

  const BaseAccordion = w.PWPL_Accordion;
  const BaseAccordionItem = w.PWPL_AccordionItem;
  const AccordionFallback = function AccordionFallback( props ) {
    const { children, searchValue, onSearchChange } = props || {};
    return h('div', null, [
      h('div', null, [
        h('label', null, 'Search Options'),
        h('input', {
          type: 'search',
          value: searchValue || '',
          onChange: ( event ) => onSearchChange && onSearchChange( event.target.value ),
        }),
      ]),
      children
    ]);
  };
  const AccordionItemFallback = function AccordionItemFallback( props ) {
    const { title, children } = props || {};
    return h('div', null, [ h('h3', null, title ), children ]);
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
    return h('div', { className: 'pwpl-row' }, [
      h('div', { className: 'pwpl-row__left' }, [
        label ? h('label', { className: 'pwpl-row__label', htmlFor: inputId, id: labelId }, label) : null,
        h('div', { className: 'pwpl-range' }, h(RangeControl, {
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
    children.push(h('div', { className: 'pwpl-typo__row' }, [fontField, weightFieldOptions]));

    if (ENABLE_TYPO_STYLE_FLAGS && showStyle) {
      const styleItems = [
        { id: `${idKey}-italic`, label: 'I', ariaLabel: `${label} italic`, isActive: italicActive, onClick: () => toggleFlag('italic', italic, setItalic) },
        { id: `${idKey}-uppercase`, label: 'TT', ariaLabel: `${label} uppercase`, isActive: uppercaseActive, onClick: () => toggleFlag('uppercase', uppercase, setUppercase) },
        { id: `${idKey}-smallcaps`, label: 'Tr', ariaLabel: `${label} small caps`, isActive: smallcapsActive, onClick: () => toggleFlag('smallcaps', smallcaps, setSmallcaps) },
        { id: `${idKey}-underline`, label: 'U', ariaLabel: `${label} underline`, isActive: underlineActive, onClick: () => toggleFlag('underline', underline, setUnderline) },
        { id: `${idKey}-strike`, label: 'S', ariaLabel: `${label} strikethrough`, isActive: strikeActive, onClick: () => toggleFlag('strike', strike, setStrike) },
      ];
      children.push(h('div', { className: 'pwpl-typo__group' }, [
        h('span', { className: 'pwpl-typo__label' }, `${label} Font Style`),
        h(SegmentedButtonGroup, { items: styleItems, ariaLabel: `${label} font style` }),
      ]));
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
      children.push(h('div', { className: 'pwpl-typo__group' }, [
        h('span', { className: 'pwpl-typo__label' }, `${label} Text Alignment`),
        h(SegmentedButtonGroup, { items: alignItems, role: 'radiogroup', className: 'pwpl-typo__seg--align', ariaLabel: `${label} alignment` }),
      ]));
    }

    if (showColor) {
      const colorTabs = ['Saved', 'Global', 'Recent'];
      children.push(h('div', { className: 'pwpl-typo__colors' }, [
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
          allowAlpha: false,
          'aria-label': `${label} text color`,
        })),
        h('div', { className: 'pwpl-typo__swatches' },
          DEFAULT_COLOR_SWATCHES.map((swatch) => h('button', {
            type: 'button',
            key: `${idKey}-${swatch.value || 'none'}`,
            className: classNames('pwpl-typo__swatch', (color || '') === (swatch.value || '') ? 'is-active' : '', !swatch.value ? 'is-none' : ''),
            'aria-label': swatch.value ? `${swatch.label} (${swatch.value})` : `${swatch.label} (reset color)`,
            style: { '--pwpl-swatch-color': swatch.value || 'transparent' },
            onClick: () => handleColorChange(swatch.value || ''),
          }, swatch.value ? null : h('span', { className: 'pwpl-typo__swatch-clear' }, '×')))
        ),
      ]));
    }

    children.push(RangeValueRow({
      label: `${label} Text Size`,
      name: names.size || null,
      value: size,
      onChange: handleSizeChange,
      min: resolvedSizeRange.min,
      max: resolvedSizeRange.max,
      step: resolvedSizeRange.step,
      placeholder: resolvedSizeRange.placeholder,
      unit: resolvedSizeRange.unit,
    }));

    if (ENABLE_TYPO_TRACKING && showTracking) {
      children.push(RangeValueRow({
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
      }));
    }

    if (ENABLE_TYPO_LINE_HEIGHT && showLineHeight) {
      children.push(RangeValueRow({
        label: `${label} Line Height`,
        name: names.lineHeight || null,
        value: lineHeight,
        onChange: handleLineHeightChange,
        min: resolvedLineHeightRange.min,
        max: resolvedLineHeightRange.max,
        step: resolvedLineHeightRange.step,
        placeholder: resolvedLineHeightRange.placeholder,
        unit: resolvedLineHeightRange.unit,
      }));
    }

    const hiddenFields = [];
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

    return h('div', { className: 'pwpl-typo', 'data-typo-id': idKey }, hiddenFields.length ? children.concat(hiddenFields) : children);
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
          h('div', { className: 'pwpl-sides__lock' }, [
            h('button', {
              type: 'button',
              className: 'pwpl-lock' + (locked ? ' is-locked' : ''),
              onClick: () => onToggleLock && onToggleLock(!locked),
              'aria-pressed': locked,
              'aria-label': locked ? 'Unlock values' : 'Lock values',
            }, locked ? 'Locked' : 'Unlocked'),
          ]),
        ]),
      ]),
      presets && presets.length ? h('div', { className: 'pwpl-sides-presets' },
        presets.map((preset) => h('button', {
          type: 'button',
          key: String(preset),
          className: 'pwpl-presets__chip',
          onClick: () => handlePreset(preset),
        }, `${preset}${unit}`))
      ) : null,
    ]);
  }
  function Help({ text }){
    // Lightweight tooltip using title attribute for hover
    return h('span', { className: 'pwpl-help', title: text || '' , style:{ marginLeft:6, cursor:'help', fontSize:'12px', opacity:.7 } }, '❓');
  }
  // Guard ColorPicker to avoid initial render errors if not available yet

  function SectionHeader({ title, description }){
    return h('div', { className: 'pwpl-v1-section-header' }, [
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

    const deviceOrder = ['xxl','xl','lg','md','sm'];
    const DEVICE_LABELS = {
      xxl: 'Big screens (≥ 1536px)',
      xl:  'Desktop (1280–1535px)',
      lg:  'Laptop (1024–1279px)',
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
      'layout-table': [globalWidthLabel, `${formatCount(globalColumns)} cols`, `min ${formatNumber(globalCardMin)}`],
      'layout-card': [
        `Container r ${formatNumber(radius)} · b ${formatNumber(borderW)} · ${summarizeColor(borderColor)}`,
        `Spacing Pad ${formatSides(pads)} · Mar ${formatSides(margins)}`,
        `${spacingLockSummary} · Split: ${formatSplit(split)}`,
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

    const tableSection = h('div', { className: 'pwpl-tabs pwpl-tabs--segmented' },
      h(TabPanel, {
        tabs: [
          { name: 'widths', title: i18n((data.i18n.tabs || {}).widths) || 'Widths' },
          { name: 'breakpoints', title: i18n((data.i18n.tabs || {}).breakpoints) || 'Breakpoints' },
        ],
      }, (tab) => {
        if (tab.name === 'widths') {
          return h('div', null, [
            RangeValueRow({
              label: 'Global width',
              name: 'pwpl_table[layout][widths][global]',
              value: globalWidth,
              onChange: (val) => setGlobalWidth(val),
              min: 0,
              max: 2000,
              step: 10,
              placeholder: 'auto',
            }),
            presetChips(WIDTH_PRESETS.concat(['fluid']), (preset) => {
              if (preset === 'fluid') {
                setGlobalWidth('0');
                return;
              }
              setGlobalWidth(String(preset));
            }, (preset) => preset === 'fluid' ? '100%' : `${preset}px`),
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
              label: 'Global card minimum width',
              name: 'pwpl_table[layout][card_widths][global]',
              value: globalCardMin,
              onChange: (val) => setGlobalCardMin(val),
              min: 0,
              max: 1200,
              step: 10,
              placeholder: 'inherit',
            }),
          ]);
        }
        const breakpointToolbar = h('div', { className: 'pwpl-tab-actions' }, [
          h('button', { type: 'button', className: 'pwpl-presets__chip', onClick: copyGlobalToBreakpoints }, 'Copy Global'),
          h('button', {
            type: 'button',
            className: 'pwpl-presets__chip' + (linkDevices ? ' is-active' : ''),
            onClick: () => setLinkDevices((prev) => !prev),
            'aria-pressed': linkDevices,
          }, linkDevices ? 'Link on' : 'Link off'),
        ]);
        return h('div', null, [
          breakpointToolbar,
          h('div', { className: 'pwpl-breakpoints' }, deviceOrder.map((key) => {
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
          }))
        ]);
      })
    );

    const planCardSection = h('div', { className: 'pwpl-card-groups' }, [
      h('div', { className: 'pwpl-group' }, [
        h('div', { className: 'pwpl-group__title' }, 'Card Container'),
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
            allowAlpha: false,
          })
        ),
        HiddenInput({ name: 'pwpl_table[card][colors][border]', value: borderColor }),
      ]),
      h('div', { className: 'pwpl-group' }, [
        h('div', { className: 'pwpl-group__title' }, 'Card Spacing'),
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
      ]),
      h('div', { className: 'pwpl-group' }, [
        h('div', { className: 'pwpl-group__title' }, 'Card Layout'),
        h('label', { className: 'components-base-control__label' }, 'Split layout'),
        h('select', { value: split, onChange: (e) => setSplit(e.target.value) }, [
          h('option', { value: 'two_tone' }, 'Two-tone (header & CTA vs. specs)'),
        ]),
        HiddenInput({ name: 'pwpl_table[card][layout][split]', value: split }),
      ]),
    ]);

    const ctaSizeSection = h('div', null, [
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
    ]);

    const specsStyleSection = h('div', { className:'pwpl-form-grid' }, [
      h('div', null, [
        h('label', { className:'components-base-control__label' }, 'Specs style'),
        h('select', { value:specStyle, onChange:(e)=> setSpecStyle(e.target.value) }, [
          h('option', { value:'default' }, 'Default'),
          h('option', { value:'flat' }, 'Flat'),
          h('option', { value:'segmented' }, 'Segmented'),
          h('option', { value:'chips' }, 'Chips'),
        ]),
        HiddenInput({ name:'pwpl_table[ui][specs_style]', value: specStyle }),
      ])
    ]);

    const sidebar = (data.i18n && data.i18n.sidebar) ? data.i18n.sidebar : {};
    const layoutLabel = i18n(sidebar.layoutSpacing) || 'Layout & Spacing';

    const sections = [
      { id: 'layout-table', title: 'Table Width & Columns', content: tableSection },
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
      { id: 'animation-interactions', title: 'Interactions', content: interactionsSection },
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
        onClick: () => onChange(item.key)
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

    return h('div', { className:'pwpl-v1-topbar' }, [
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
      h('main', { className: 'pwpl-v1-main' }, [
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
      { id: 'filters-enable', title: 'Enable Filters', content: enabledSection },
      { id: 'filters-allowed', title: 'Allowed Lists', content: listsSection },
      { id: 'filters-platform-order', title: 'Platform Order', content: platformSection },
    ];

    return h('section', { className:'pwpl-v1-block' }, [
      SectionHeader({ title:'Filters', description:'Enable dimensions and choose allowed values.' }),
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

    const titleText = text.title || {};
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
        allowAlpha: false,
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

    const sections = [
      {
        id: 'top-base',
        title: 'Top Base',
        content: h('div', { className: 'pwpl-typo-panel' }, [
          paletteControl(
            h('span', null, [ 'Top base color ', Help({ text:'Default color for top area texts when no per-element color is set.' }) ]),
            topColor,
            handleTopColorChange
          ),
          HiddenInput({ name:'pwpl_table[card][text][top][color]', value: topColor }),
          h(TextControl, {
            label: h('span', null, ['Top base font family ', Help({ text:'Font stack applied to top area as a base.' }) ]),
            value: topFamily,
            onChange:(v)=>{ setTopFamily(v); updatePreviewVars({ 'card.text.top.family': v }); },
            placeholder:'system-ui, -apple-system, sans-serif'
          }),
          HiddenInput({ name:'pwpl_table[card][text][top][family]', value: topFamily }),
        ]),
      },
      {
        id: 'title-text',
        title: 'Title Text',
        content: h(TypographySection, {
          idKey: 'title',
          label: 'Title',
          names: {
            color: 'pwpl_table[card][text][title][color]',
            family: 'pwpl_table[card][text][title][family]',
            size: 'pwpl_table[card][typo][title][size]',
            weight: 'pwpl_table[card][typo][title][weight]',
          },
          values: makeValues(titleText, titleTypos),
          onPreviewPatch: previewMap('title'),
        }),
      },
      {
        id: 'subtitle-text',
        title: 'Subtitle Text',
        content: h(TypographySection, {
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
        }),
      },
      {
        id: 'price-text',
        title: 'Price Text',
        content: h('div', { className: 'pwpl-typo-stack' }, [
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
          priceExtras,
        ]),
      },
      {
        id: 'billing-text',
        title: 'Billing Text',
        content: h(TypographySection, {
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
        }),
      },
      {
        id: 'specs-text',
        title: 'Specs Text',
        content: h(TypographySection, {
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
        }),
      },
      {
        id: 'cta-typography',
        title: 'CTA Typography',
        content: h(TypographySection, {
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
        }),
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
        allowAlpha: false,
      })
    );

    const handleSearchKeyDown = (event)=>{
      if (event.key !== 'Enter') {
        return;
      }
      openFirstMatch(sections);
    };

    const showTopGrad = !!topType;
    const showTopAngle = topType === 'linear';
    const topSection = h('div', { className:'pwpl-v1-grid' }, [
      paletteField('Top background', topBg, handleColorChange(setTopBg, 'card.colors.top_bg')),
      HiddenInput({ name:'pwpl_table[card][colors][top_bg]', value: topBg }),
      h('div', null, [
        h('label', { className:'components-base-control__label' }, 'Top gradient type'),
        h('select', {
          value: topType,
          onChange:(e)=> {
            const next = e.target.value;
            setTopType(next);
            updatePreviewVars({ 'card.colors.top_grad.type': next || '' });
          }
        }, [
          h('option', { value:'' }, 'None'),
          h('option', { value:'linear' }, 'Linear'),
          h('option', { value:'radial' }, 'Radial'),
          h('option', { value:'conic' }, 'Conic'),
        ]),
        HiddenInput({ name:'pwpl_table[card][colors][top_grad][type]', value: topType }),
      ]),
      showTopGrad ? paletteField('Top gradient start', topStart, handleColorChange(setTopStart, 'card.colors.top_grad.start')) : null,
      HiddenInput({ name:'pwpl_table[card][colors][top_grad][start]', value: showTopGrad ? topStart : '' }),
      showTopGrad ? paletteField('Top gradient end', topEnd, handleColorChange(setTopEnd, 'card.colors.top_grad.end')) : null,
      HiddenInput({ name:'pwpl_table[card][colors][top_grad][end]', value: showTopGrad ? topEnd : '' }),
      showTopGrad && showTopAngle ? RangeValueRow({
        label: 'Angle (deg)',
        name: 'pwpl_table[card][colors][top_grad][angle]',
        value: topAngle,
        onChange: setTopAngle,
        min: 0,
        max: 360,
        step: 1,
        unit: '°',
      }) : HiddenInput({ name:'pwpl_table[card][colors][top_grad][angle]', value: '' }),
      showTopGrad ? RangeValueRow({
        label: 'Start position (%)',
        name: 'pwpl_table[card][colors][top_grad][start_pos]',
        value: topSP,
        onChange: setTopSP,
        min: 0,
        max: 100,
        step: 1,
        unit: '%',
      }) : HiddenInput({ name:'pwpl_table[card][colors][top_grad][start_pos]', value: '' }),
      showTopGrad ? RangeValueRow({
        label: 'End position (%)',
        name: 'pwpl_table[card][colors][top_grad][end_pos]',
        value: topEP,
        onChange: setTopEP,
        min: 0,
        max: 100,
        step: 1,
        unit: '%',
      }) : HiddenInput({ name:'pwpl_table[card][colors][top_grad][end_pos]', value: '' }),
    ]);

    const showSpecsGrad = !!specsType;
    const showSpecsAngle = specsType === 'linear';
    const specsSection = h('div', { className:'pwpl-v1-grid' }, [
      paletteField('Specs background', specsBg, handleColorChange(setSpecsBg, 'card.colors.specs_bg')),
      HiddenInput({ name:'pwpl_table[card][colors][specs_bg]', value: specsBg }),
      h('div', null, [
        h('label', { className:'components-base-control__label' }, 'Specs gradient type'),
        h('select', {
          value: specsType,
          onChange:(e)=> {
            const next = e.target.value;
            setSpecsType(next);
            updatePreviewVars({ 'card.colors.specs_grad.type': next || '' });
          }
        }, [
          h('option', { value:'' }, 'None'),
          h('option', { value:'linear' }, 'Linear'),
          h('option', { value:'radial' }, 'Radial'),
          h('option', { value:'conic' }, 'Conic'),
        ]),
        HiddenInput({ name:'pwpl_table[card][colors][specs_grad][type]', value: specsType }),
      ]),
      showSpecsGrad ? paletteField('Specs gradient start', specsStart, handleColorChange(setSpecsStart, 'card.colors.specs_grad.start')) : null,
      HiddenInput({ name:'pwpl_table[card][colors][specs_grad][start]', value: showSpecsGrad ? specsStart : '' }),
      showSpecsGrad ? paletteField('Specs gradient end', specsEnd, handleColorChange(setSpecsEnd, 'card.colors.specs_grad.end')) : null,
      HiddenInput({ name:'pwpl_table[card][colors][specs_grad][end]', value: showSpecsGrad ? specsEnd : '' }),
      showSpecsGrad && showSpecsAngle ? RangeValueRow({
        label: 'Angle (deg)',
        name: 'pwpl_table[card][colors][specs_grad][angle]',
        value: specsAngle,
        onChange: setSpecsAngle,
        min: 0,
        max: 360,
        step: 1,
        unit: '°',
      }) : HiddenInput({ name:'pwpl_table[card][colors][specs_grad][angle]', value: '' }),
      showSpecsGrad ? RangeValueRow({
        label: 'Start position (%)',
        name: 'pwpl_table[card][colors][specs_grad][start_pos]',
        value: specsSP,
        onChange: setSpecsSP,
        min: 0,
        max: 100,
        step: 1,
        unit: '%',
      }) : HiddenInput({ name:'pwpl_table[card][colors][specs_grad][start_pos]', value: '' }),
      showSpecsGrad ? RangeValueRow({
        label: 'End position (%)',
        name: 'pwpl_table[card][colors][specs_grad][end_pos]',
        value: specsEP,
        onChange: setSpecsEP,
        min: 0,
        max: 100,
        step: 1,
        unit: '%',
      }) : HiddenInput({ name:'pwpl_table[card][colors][specs_grad][end_pos]', value: '' }),
    ]);

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

    const handleCtaColorChange = (setter, key)=> (value)=>{
      const hex = typeof value === 'string' ? value : '';
      setter(hex);
      updatePreviewVars({ [key]: hex });
    };

    const ctaPalette = (label, value, handler, hiddenName)=> h('div', { className:'pwpl-v1-color pwpl-v1-color--palette' }, [
      h(ColorPaletteControl, {
        label,
        value: value || '',
        onChange: handler,
        allowAlpha: false,
      }),
      HiddenInput({ name: hiddenName, value })
    ]);

    const ctaColorsSection = h('div', { className:'pwpl-v1-grid pwpl-v1-cta-colors' }, [
      h('div', { className:'pwpl-v1-cta-color-state' }, [
        h('strong', null, 'Normal'),
        h('div', { className:'pwpl-v1-cta-color-row' }, [
          ctaPalette('Background', ctaNormalBg, handleCtaColorChange(setCtaNormalBg, 'ui.cta.normal.bg'), 'pwpl_table[ui][cta][normal][bg]'),
          ctaPalette('Text', ctaNormalColor, handleCtaColorChange(setCtaNormalColor, 'ui.cta.normal.color'), 'pwpl_table[ui][cta][normal][color]'),
          ctaPalette('Border', ctaNormalBorder, handleCtaColorChange(setCtaNormalBorder, 'ui.cta.normal.border'), 'pwpl_table[ui][cta][normal][border]'),
        ])
      ]),
      h('div', { className:'pwpl-v1-cta-color-state' }, [
        h('strong', null, 'Hover'),
        h('div', { className:'pwpl-v1-cta-color-row' }, [
          ctaPalette('Background', ctaHoverBg, handleCtaColorChange(setCtaHoverBg, 'ui.cta.hover.bg'), 'pwpl_table[ui][cta][hover][bg]'),
          ctaPalette('Text', ctaHoverColor, handleCtaColorChange(setCtaHoverColor, 'ui.cta.hover.color'), 'pwpl_table[ui][cta][hover][color]'),
          ctaPalette('Border', ctaHoverBorder, handleCtaColorChange(setCtaHoverBorder, 'ui.cta.hover.border'), 'pwpl_table[ui][cta][hover][border]'),
        ])
      ]),
      h('div', { className:'pwpl-v1-cta-color-state' }, [
        h('strong', null, 'Focus'),
        h('div', { className:'pwpl-v1-cta-color-row' }, [
          ctaPalette('Outline', ctaFocusColor, handleCtaColorChange(setCtaFocusColor, 'ui.cta.focus'), 'pwpl_table[ui][cta][focus]'),
        ])
      ]),
    ]);

    const sections = [
      { id: 'colors-top', title: 'Top Background', content: topSection },
      { id: 'colors-specs', title: 'Specs Background', content: specsSection },
      { id: 'colors-keyline', title: 'Keyline', content: keylineSection },
      { id: 'colors-presets', title: 'Theme Palettes', content: themePalettesSection },
      { id: 'colors-cta', title: 'CTA Colors', content: ctaColorsSection },
    ];

    return h('section', { className:'pwpl-v1-block' }, [
      SectionHeader({ title: i18n(data.i18n.sidebar.colors), description: 'Surface colors. Gradients can be added in a later pass.' }),
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
          h('label', { className:'components-base-control__label' }, 'Badge color'),
          h(ColorPicker, { color:item.color||'', disableAlpha:true, onChangeComplete:(val)=> update('color', (typeof val==='string')?val:(val&&val.hex)?val.hex:'') }),
          HiddenInput({ name:`pwpl_table_badges[${dim.key}][${idx}][color]`, value:item.color||'' }),
        ]),
        h('div', { className:'pwpl-v1-color' }, [
          h('label', { className:'components-base-control__label' }, 'Text color'),
          h(ColorPicker, { color:item.text_color||'', disableAlpha:true, onChangeComplete:(val)=> update('text_color', (typeof val==='string')?val:(val&&val.hex)?val.hex:'') }),
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
          if (tab.name==='priority') return Priority();
          const dim = dims.find(d=> d.key===tab.name) || dims[0];
          return h('div', null, [
            (dim.state||[]).map((row,idx)=> Row({ dim, idx, item: row })),
            h('div', { style:{ marginTop:'10px' } }, h('button', { type:'button', className:'button button-primary', onClick:()=> addRow(dim) }, 'Add Promotion')),
          ]);
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
      { id: 'advanced-trust', title: 'Trust & Inline CTA', content: trustSection },
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
