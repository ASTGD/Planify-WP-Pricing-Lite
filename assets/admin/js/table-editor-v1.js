(function(w){
  const wp = w.wp || window.wp || {};
  const { createElement: h, useState } = (wp.element || {});
  const { Card, CardBody, TabPanel, TextControl, __experimentalNumberControl: NumberControl, ColorPicker } = (wp.components || {});

  const i18n = (s) => s || '';
  const data = w.PWPL_AdminV1 || { postId: 0, layout: { widths: {}, columns: {} }, card: {}, i18n: {} };

  // Shared preview state + event bus
  w.PWPL_PreviewVars = w.PWPL_PreviewVars || {};
  function updatePreviewVars(patch){
    try { Object.assign(w.PWPL_PreviewVars, patch || {}); } catch(e){}
    document.dispatchEvent(new CustomEvent('pwpl:v1:update'));
  }

  function setDeep(target, path, value){
    if (!path) return;
    const parts = path.split('.');
    let obj = target; for (let i=0;i<parts.length-1;i++){ const k=parts[i]; obj[k]=obj[k]||{}; obj=obj[k]; }
    obj[parts[parts.length-1]] = value;
  }

  function deepClone(obj){ try { return JSON.parse(JSON.stringify(obj||{})); } catch(e){ return {}; } }

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

  function HiddenInput({ name, value }){
    return h('input', { type: 'hidden', name, value: value == null ? '' : value });
  }

  function SectionHeader({ title, description }){
    return h('div', { className: 'pwpl-v1-section-header' }, [
      h('h2', { className: 'pwpl-v1-title' }, title),
      description ? h('p', { className: 'pwpl-v1-desc' }, description) : null,
    ]);
  }

  function TableLayoutBlock(props){
    const [globalWidth, setGlobalWidth] = useState(parseInt(data.layout.widths.global || 0, 10) || 0);
    const [globalColumns, setGlobalColumns] = useState(parseInt(data.layout.columns.global || 0, 10) || 0);
    const deviceOrder = ['xxl','xl','lg','md','sm'];
    const [widths, setWidths] = useState(Object.assign({xxl:0,xl:0,lg:0,md:0,sm:0}, data.layout.widths||{}));
    const [columns, setColumns] = useState(Object.assign({xxl:0,xl:0,lg:0,md:0,sm:0}, data.layout.columns||{}));
    const [cardW, setCardW] = useState(Object.assign({xxl:0,xl:0,lg:0,md:0,sm:0}, data.layout.cardWidths||{}));

    return h('section', { className: 'pwpl-v1-block' }, [
      SectionHeader({ title: i18n(data.i18n.sidebar.tableLayout), description: 'Control table width and columns. Values are optional; blank inherits theme defaults.' }),
      h(Card, null,
        h(CardBody, null,
          h(TabPanel, {
            tabs: [
              { name: 'widths', title: i18n(data.i18n.tabs.widths) },
              { name: 'breakpoints', title: i18n(data.i18n.tabs.breakpoints) },
            ],
          }, (tab) => {
            if (tab.name === 'widths'){
              return h('div', { className: 'pwpl-v1-grid' }, [
                h(NumberControl || TextControl, {
                  label: 'Global width (px)',
                  value: globalWidth,
                  onChange: (val) => setGlobalWidth(parseInt(val || 0, 10) || 0),
                  min: 0, max: 4000,
                }),
                h(NumberControl || TextControl, {
                  label: 'Preferred columns',
                  value: globalColumns,
                  onChange: (val) => setGlobalColumns(parseInt(val || 0, 10) || 0),
                  min: 0, max: 20,
                }),
                // Hidden inputs that actually submit with the post
                HiddenInput({ name: 'pwpl_table[layout][widths][global]', value: globalWidth }),
                HiddenInput({ name: 'pwpl_table[layout][columns][global]', value: globalColumns }),
              ]);
            }
            return h('div', { className: 'pwpl-v1-grid' }, deviceOrder.map((key)=>{
              const w = parseInt(widths[key]||0,10)||0;
              const c = parseInt(columns[key]||0,10)||0;
              const cw= parseInt(cardW[key]||0,10)||0;
              const setW = (val)=>{
                const v = parseInt(val||0,10)||0; const next = Object.assign({}, widths, {[key]:v}); setWidths(next);
              };
              const setC = (val)=>{
                const v = parseInt(val||0,10)||0; const next = Object.assign({}, columns, {[key]:v}); setColumns(next);
              };
              const setCW = (val)=>{
                const v = parseInt(val||0,10)||0; const next = Object.assign({}, cardW, {[key]:v}); setCardW(next);
              };
              return h('div', { key, className:'pwpl-v1-grid' }, [
                h('div', null, [
                  h(NumberControl || TextControl, { label: key.toUpperCase()+ ' width (px)', value:w, min:0, max:4000, onChange:setW }),
                  HiddenInput({ name: `pwpl_table[layout][widths][${key}]`, value:w })
                ]),
                h('div', null, [
                  h(NumberControl || TextControl, { label: key.toUpperCase()+ ' columns', value:c, min:0, max:20, onChange:setC }),
                  HiddenInput({ name: `pwpl_table[layout][columns][${key}]`, value:c })
                ]),
                h('div', null, [
                  h(NumberControl || TextControl, { label: key.toUpperCase()+ ' card min width (px)', value:cw, min:0, max:4000, onChange:setCW }),
                  HiddenInput({ name: `pwpl_table[layout][card_widths][${key}]`, value:cw })
                ]),
              ]);
            }));
          })
        )
      )
    ]);
  }

  function PlanCardBlock(props){
    const layout = data.card.layout || {};
    const colors = data.card.colors || {};
    const [radius, setRadius] = useState(parseInt(layout.radius || 0, 10) || 0);
    const [borderW, setBorderW] = useState(parseFloat(layout.border_w || 0) || 0);
    const [borderColor, setBorderColor] = useState(colors.border || '');
    const [padT, setPadT] = useState(parseInt((layout.pad_t||0),10)||0);
    const [padR, setPadR] = useState(parseInt((layout.pad_r||0),10)||0);
    const [padB, setPadB] = useState(parseInt((layout.pad_b||0),10)||0);
    const [padL, setPadL] = useState(parseInt((layout.pad_l||0),10)||0);
    const allEqual = (a,b,c,d)=> (a===b && a===c && a===d);
    const [lockPads, setLockPads] = useState(allEqual(padT,padR,padB,padL));
    const [split, setSplit] = useState(layout.split || 'two_tone');

    return h('section', { className: 'pwpl-v1-block' }, [
      SectionHeader({ title: i18n(data.i18n.sidebar.planCard), description: 'Card layout and border settings.' }),
      h(Card, null,
        h(CardBody, null,
          h(TabPanel, {
            tabs: [
              { name: 'layout', title: i18n(data.i18n.tabs.layout) },
              { name: 'border', title: i18n(data.i18n.tabs.border) },
            ],
          }, (tab) => {
            if (tab.name === 'layout'){
              return h('div', { className: 'pwpl-v1-grid' }, [
                h(NumberControl || TextControl, {
                  label: 'Card radius (px)',
                  value: radius,
                  onChange: (val) => setRadius(parseInt(val || 0, 10) || 0),
                  min: 0, max: 24,
                }),
                h(NumberControl || TextControl, {
                  label: 'Border width (px)',
                  value: borderW,
                  onChange: (val) => setBorderW(parseFloat(val || 0) || 0),
                  min: 0, max: 12, step: 0.5,
                }),
                // Padding controls
                h(NumberControl || TextControl, {
                  label: 'Padding top (px)', value: padT, min:0, max:32,
                  onChange: (val)=>{ const v=parseInt(val||0,10)||0; if(lockPads){ setPadT(v); setPadR(v); setPadB(v); setPadL(v);} else { setPadT(v);} }
                }),
                h(NumberControl || TextControl, {
                  label: 'Padding right (px)', value: padR, min:0, max:32,
                  onChange: (val)=>{ const v=parseInt(val||0,10)||0; if(lockPads){ setPadT(v); setPadR(v); setPadB(v); setPadL(v);} else { setPadR(v);} }
                }),
                h(NumberControl || TextControl, {
                  label: 'Padding bottom (px)', value: padB, min:0, max:32,
                  onChange: (val)=>{ const v=parseInt(val||0,10)||0; if(lockPads){ setPadT(v); setPadR(v); setPadB(v); setPadL(v);} else { setPadB(v);} }
                }),
                h(NumberControl || TextControl, {
                  label: 'Padding left (px)', value: padL, min:0, max:32,
                  onChange: (val)=>{ const v=parseInt(val||0,10)||0; if(lockPads){ setPadT(v); setPadR(v); setPadB(v); setPadL(v);} else { setPadL(v);} }
                }),
                h('label', { className: 'components-base-control__label' }, [
                  h('input', { type:'checkbox', checked: !!lockPads, onChange: (e)=>{ const ch=e.target.checked; setLockPads(ch); if(ch){ const v=padT; setPadR(v); setPadB(v); setPadL(v);} } }),
                  ' Lock padding values together'
                ]),
                // Split layout
                h('div', null, [
                  h('label', { className:'components-base-control__label' }, 'Split layout'),
                  h('select', { value: split, onChange:(e)=> setSplit(e.target.value) }, [
                    h('option', { value:'two_tone' }, 'Two-tone (header & CTA vs. specs)')
                  ])
                ]),
                HiddenInput({ name: 'pwpl_table[card][layout][radius]', value: radius }),
                HiddenInput({ name: 'pwpl_table[card][layout][border_w]', value: borderW }),
                HiddenInput({ name: 'pwpl_table[card][layout][pad_t]', value: padT }),
                HiddenInput({ name: 'pwpl_table[card][layout][pad_r]', value: padR }),
                HiddenInput({ name: 'pwpl_table[card][layout][pad_b]', value: padB }),
                HiddenInput({ name: 'pwpl_table[card][layout][pad_l]', value: padL }),
                HiddenInput({ name: 'pwpl_table[card][layout][split]', value: split }),
              ]);
            }
            return h('div', { className: 'pwpl-v1-grid' }, [
              h('div', { className: 'pwpl-v1-color' }, [
                h('label', { className: 'components-base-control__label' }, 'Border color'),
                h(ColorPicker, {
                  color: borderColor || '#e5e7eb',
                  onChangeComplete: (value) => {
                    var hex = (typeof value === 'string') ? value : (value && value.hex) ? value.hex : '';
                    setBorderColor(hex);
                  },
                  disableAlpha: true,
                }),
                HiddenInput({ name: 'pwpl_table[card][colors][border]', value: borderColor }),
              ]),
            ]);
          })
        )
      )
    ]);
  }

  function Sidebar({ active, onChange }){
    const items = [
      { key: 'table', label: i18n(data.i18n.sidebar.tableLayout) },
      { key: 'card',  label: i18n(data.i18n.sidebar.planCard) },
      { key: 'typography', label: i18n(data.i18n.sidebar.typography) },
      { key: 'colors', label: i18n(data.i18n.sidebar.colors) },
      { key: 'cta', label: i18n(data.i18n.sidebar.cta) },
      { key: 'specs', label: i18n(data.i18n.sidebar.specs) },
      { key: 'badges', label: i18n(data.i18n.sidebar.badges) },
      { key: 'advanced', label: i18n(data.i18n.sidebar.advanced) },
    ];
    return h('nav', { className: 'pwpl-v1-sidebar' },
      items.map(item => h('button', {
        type: 'button',
        className: 'pwpl-v1-nav' + (active === item.key ? ' is-active' : ''),
        onClick: () => onChange(item.key)
      }, item.label))
    );
  }

  function App(){
    const [active, setActive] = useState('table');
    return h('div', { className: 'pwpl-v1' }, [
      h(Sidebar, { active, onChange: setActive }),
      h('main', { className: 'pwpl-v1-main' }, [
        h(PreviewPane),
        active === 'table' ? h(TableLayoutBlock) : null,
        active === 'card'  ? h(PlanCardBlock)   : null,
        active === 'typography' ? h(TypographyBlock) : null,
        active === 'colors' ? h(ColorsSurfacesBlock) : null,
        active === 'cta' ? h(CTABlock) : null,
        active === 'specs' ? h(SpecsBlock) : null,
        active === 'badges' ? h(BadgesBlock) : null,
        active === 'advanced' ? h(AdvancedBlock) : null,
      ])
    ]);
  }

  function PreviewPane(){
    const [tick, setTick] = useState(0);
    function handler(){ setTick((t)=> t+1); }
    if (wp.element && wp.element.useEffect){ wp.element.useEffect(()=>{ document.addEventListener('pwpl:v1:update', handler); return ()=> document.removeEventListener('pwpl:v1:update', handler); },[]); }

    // Merge base card config with preview patches
    const base = deepClone(data.card);
    const patches = w.PWPL_PreviewVars || {};
    Object.keys(patches).forEach((k)=>{ if (k.indexOf('card.')===0){ setDeep(base, k.replace(/^card\./,''), patches[k]); } });

    const topGrad = (base.colors && base.colors.top_grad) || {};
    const specsGrad = (base.colors && base.colors.specs_grad) || {};

    const headerBg = (topGrad && topGrad.type) ? composeGradient(topGrad) : (base.colors ? base.colors.top_bg : '');
    const specsBg  = (specsGrad && specsGrad.type) ? composeGradient(specsGrad) : (base.colors ? base.colors.specs_bg : '');

    const topColor = (base.text && base.text.top && base.text.top.color) || '#111214';
    const topFont  = (base.text && base.text.top && base.text.top.family) || 'system-ui, -apple-system, sans-serif';
    const topSize  = (base.text && base.text.top && base.text.top.size) || 0;
    const topWeight= (base.text && base.text.top && base.text.top.weight) || 0;

    const radius   = (base.layout && base.layout.radius) || 16;
    const borderW  = (base.layout && base.layout.border_w) || 0;
    const borderC  = (base.colors && base.colors.border) || 'transparent';

    const vars = {
      '--card-top-text-color': topColor,
      '--card-top-font': topFont,
      '--card-top-font-size': topSize? (topSize + 'px') : undefined,
      '--card-top-font-weight': topWeight || undefined,
      '--card-radius': radius + 'px',
      '--card-border-width': (borderW||0) + 'px',
      '--card-border-color': borderC,
    };

    const headStyle = { background: headerBg || '#fff8e6', color: topColor, fontFamily: topFont };
    const specsStyle = { background: specsBg || '#cf7a1a' };
    const cardStyle = { borderRadius: (radius||16) + 'px', border: (borderW||0) + 'px solid ' + (borderC||'transparent') };

    return h('section', { className:'pwpl-v1-block' }, [
      SectionHeader({ title:'Preview', description:'Live preview of key settings (approximate).' }),
      h('div', { className:'pwpl-v1-preview' },
        h('div', { className:'pwpl-v1-card', style: cardStyle }, [
          h('div', { className:'pwpl-v1-card-top', style: headStyle }, [
            h('div', { className:'pwpl-v1-title' }, 'Starter Plan'),
            h('div', { className:'pwpl-v1-price' }, [ h('span', null, '$'), h('strong', null, '12.99'), h('span', null, '/mo') ])
          ]),
          h('div', { className:'pwpl-v1-specs', style: specsStyle }, [
            h('div', { className:'pwpl-v1-spec' }, [ h('span', null, 'Memory'), h('strong', null, '2GB DDR4') ]),
            h('div', { className:'pwpl-v1-spec' }, [ h('span', null, 'Storage'), h('strong', null, '25GB SSD') ]),
          ])
        ])
      )
    ]);
  }

  function TypographyBlock(){
    const text = (data.card.text || {});
    const top  = (text.top || {});
    const typo = (data.card.typo || {});
    const [topColor, setTopColor]   = useState(top.color || '');
    const [topFamily, setTopFamily] = useState(top.family || '');
    const [topSize, setTopSize]     = useState(parseInt(top.size||0,10)||0);
    const [topWeight, setTopWeight] = useState(parseInt(top.weight||0,10)||0);
    const t = {
      title: typo.title || {},
      subtitle: typo.subtitle || {},
      price: typo.price || {},
    };
    const [tTitleSize,setTTitleSize]     = useState(parseInt(t.title.size||0,10)||0);
    const [tTitleWeight,setTTitleWeight] = useState(parseInt(t.title.weight||0,10)||0);
    const [tSubSize,setTSubSize]         = useState(parseInt(t.subtitle.size||0,10)||0);
    const [tSubWeight,setTSubWeight]     = useState(parseInt(t.subtitle.weight||0,10)||0);
    const [tPriceSize,setTPriceSize]     = useState(parseInt(t.price.size||0,10)||0);
    const [tPriceWeight,setTPriceWeight] = useState(parseInt(t.price.weight||0,10)||0);

    return h('section', { className: 'pwpl-v1-block' }, [
      SectionHeader({ title: i18n(data.i18n.sidebar.typography), description: 'Typography controls for Top area and sizes/weights for title, subtitle and price.' }),
      h(Card, null,
        h(CardBody, null,
          h(TabPanel, { tabs: [
            { name: 'topText', title: i18n(data.i18n.tabs.topText) },
            { name: 'sizes', title: i18n(data.i18n.tabs.sizes) },
          ]}, (tab)=>{
            if (tab.name === 'topText'){
              return h('div', { className:'pwpl-v1-grid' }, [
                h('div', { className:'pwpl-v1-color' }, [
                  h('label', { className:'components-base-control__label' }, 'Top text color'),
                h(ColorPicker, {
                  color: topColor || '#111214',
                  onChangeComplete: (value)=>{ const hex = (typeof value==='string')? value : (value && value.hex) ? value.hex : ''; setTopColor(hex); updatePreviewVars({ 'card.text.top.color': hex }); },
                  disableAlpha: true,
                }),
                  HiddenInput({ name:'pwpl_table[card][text][top][color]', value: topColor })
                ]),
                h(TextControl, { label:'Font family', value: topFamily, onChange:(v)=>{ setTopFamily(v); updatePreviewVars({ 'card.text.top.family': v }); }, placeholder:'system-ui, -apple-system, sans-serif' }),
                HiddenInput({ name:'pwpl_table[card][text][top][family]', value: topFamily }),
                h(NumberControl || TextControl, { label:'Font size (px)', value:topSize, min:10, max:28, onChange:(v)=> { const n=parseInt(v||0,10)||0; setTopSize(n); updatePreviewVars({ 'card.text.top.size': n }); } }),
                HiddenInput({ name:'pwpl_table[card][text][top][size]', value: topSize }),
                h(NumberControl || TextControl, { label:'Font weight', value:topWeight, min:300, max:900, step:50, onChange:(v)=> { const n=parseInt(v||0,10)||0; setTopWeight(n); updatePreviewVars({ 'card.text.top.weight': n }); } }),
                HiddenInput({ name:'pwpl_table[card][text][top][weight]', value: topWeight }),
              ]);
            }
            return h('div', { className:'pwpl-v1-grid' }, [
              h(NumberControl || TextControl, { label:'Title size (px)', value:tTitleSize, min:16, max:36, onChange:(v)=> setTTitleSize(parseInt(v||0,10)||0) }),
              HiddenInput({ name:'pwpl_table[card][typo][title][size]', value:tTitleSize }),
              h(NumberControl || TextControl, { label:'Title weight', value:tTitleWeight, min:600, max:800, step:50, onChange:(v)=> setTTitleWeight(parseInt(v||0,10)||0) }),
              HiddenInput({ name:'pwpl_table[card][typo][title][weight]', value:tTitleWeight }),

              h(NumberControl || TextControl, { label:'Subtitle size (px)', value:tSubSize, min:12, max:18, onChange:(v)=> setTSubSize(parseInt(v||0,10)||0) }),
              HiddenInput({ name:'pwpl_table[card][typo][subtitle][size]', value:tSubSize }),
              h(NumberControl || TextControl, { label:'Subtitle weight', value:tSubWeight, min:400, max:600, step:50, onChange:(v)=> setTSubWeight(parseInt(v||0,10)||0) }),
              HiddenInput({ name:'pwpl_table[card][typo][subtitle][weight]', value:tSubWeight }),

              h(NumberControl || TextControl, { label:'Price size (px)', value:tPriceSize, min:24, max:44, onChange:(v)=> setTPriceSize(parseInt(v||0,10)||0) }),
              HiddenInput({ name:'pwpl_table[card][typo][price][size]', value:tPriceSize }),
              h(NumberControl || TextControl, { label:'Price weight', value:tPriceWeight, min:700, max:900, step:50, onChange:(v)=> setTPriceWeight(parseInt(v||0,10)||0) }),
              HiddenInput({ name:'pwpl_table[card][typo][price][weight]', value:tPriceWeight }),
            ]);
          })
        )
      )
    ]);
  }

  function ColorsSurfacesBlock(){
    const colors = (data.card.colors || {});
    const keyline = (colors.keyline || {});
    const [topBg, setTopBg] = useState(colors.top_bg || '');
    const [specsBg, setSpecsBg] = useState(colors.specs_bg || '');
    const [kColor, setKColor] = useState(keyline.color || '');
    const [kOpacity, setKOpacity] = useState(typeof keyline.opacity === 'number' ? keyline.opacity : '');
    const topGrad = colors.top_grad || {};
    const specsGrad = colors.specs_grad || {};
    const [topType, setTopType] = useState(topGrad.type || '');
    const [topStart, setTopStart] = useState(topGrad.start || '');
    const [topEnd, setTopEnd] = useState(topGrad.end || '');
    const [topAngle, setTopAngle] = useState(parseInt(topGrad.angle||180,10)||180);
    const [topSP, setTopSP] = useState(parseInt(topGrad.start_pos||0,10)||0);
    const [topEP, setTopEP] = useState(parseInt(topGrad.end_pos||100,10)||100);

    const [specsType, setSpecsType] = useState(specsGrad.type || '');
    const [specsStart, setSpecsStart] = useState(specsGrad.start || '');
    const [specsEnd, setSpecsEnd] = useState(specsGrad.end || '');
    const [specsAngle, setSpecsAngle] = useState(parseInt(specsGrad.angle||180,10)||180);
    const [specsSP, setSpecsSP] = useState(parseInt(specsGrad.start_pos||0,10)||0);
    const [specsEP, setSpecsEP] = useState(parseInt(specsGrad.end_pos||100,10)||100);

    return h('section', { className:'pwpl-v1-block' }, [
      SectionHeader({ title: i18n(data.i18n.sidebar.colors), description: 'Surface colors. Gradients can be added in a later pass.' }),
      h(Card, null, h(CardBody, null,
        h(TabPanel, { tabs:[
          { name:'topBg',   title: i18n(data.i18n.tabs.topBg) },
          { name:'specsBg', title: i18n(data.i18n.tabs.specsBg) },
          { name:'keyline', title: i18n(data.i18n.tabs.keyline) },
        ]}, (tab)=>{
          if (tab.name==='topBg'){
            const showGrad = !!topType;
            const showAngle = topType === 'linear';
            return h('div', { className:'pwpl-v1-grid' }, [
              h('div', { className:'pwpl-v1-color' }, [
                h('label', { className:'components-base-control__label' }, 'Top background'),
                h(ColorPicker, { color: topBg || '#fff6e0', disableAlpha:true,
                  onChangeComplete:(value)=>{ const hex=(typeof value==='string')?value:(value&&value.hex)?value.hex:''; setTopBg(hex); updatePreviewVars({ 'card.colors.top_bg': hex }); } }),
                HiddenInput({ name:'pwpl_table[card][colors][top_bg]', value: topBg }),
              ]),
              h('div', null, [
                h('label', { className:'components-base-control__label' }, 'Top gradient type'),
                h('select', { value: topType, onChange:(e)=> { setTopType(e.target.value); updatePreviewVars({ 'card.colors.top_grad.type': e.target.value||'' }); } }, [
                  h('option', { value:'' }, 'None'),
                  h('option', { value:'linear' }, 'Linear'),
                  h('option', { value:'radial' }, 'Radial'),
                  h('option', { value:'conic' }, 'Conic'),
                ]),
                HiddenInput({ name:'pwpl_table[card][colors][top_grad][type]', value: topType }),
              ]),
              showGrad ? h('div', { className:'pwpl-v1-color' }, [
                h('label', { className:'components-base-control__label' }, 'Top gradient start'),
                h(ColorPicker, { color: topStart || '#ffe8c4', disableAlpha:true,
                  onChangeComplete:(value)=>{ const hex=(typeof value==='string')?value:(value&&value.hex)?value.hex:''; setTopStart(hex); updatePreviewVars({ 'card.colors.top_grad.start': hex }); } }),
                HiddenInput({ name:'pwpl_table[card][colors][top_grad][start]', value: showGrad ? topStart : '' }),
              ]) : null,
              showGrad ? h('div', { className:'pwpl-v1-color' }, [
                h('label', { className:'components-base-control__label' }, 'Top gradient end'),
                h(ColorPicker, { color: topEnd || '#ffd3b1', disableAlpha:true,
                  onChangeComplete:(value)=>{ const hex=(typeof value==='string')?value:(value&&value.hex)?value.hex:''; setTopEnd(hex); updatePreviewVars({ 'card.colors.top_grad.end': hex }); } }),
                HiddenInput({ name:'pwpl_table[card][colors][top_grad][end]', value: showGrad ? topEnd : '' }),
              ]) : null,
              showGrad && showAngle ? h(NumberControl || TextControl, { label:'Angle (deg)', value: topAngle, min:0, max:360, onChange:(v)=> setTopAngle(parseInt(v||0,10)||0) }) : null,
              HiddenInput({ name:'pwpl_table[card][colors][top_grad][angle]', value: (showGrad && showAngle) ? topAngle : '' }),
              showGrad ? h(NumberControl || TextControl, { label:'Start position (%)', value: topSP, min:0, max:100, onChange:(v)=> setTopSP(parseInt(v||0,10)||0) }) : null,
              HiddenInput({ name:'pwpl_table[card][colors][top_grad][start_pos]', value: showGrad ? topSP : '' }),
              showGrad ? h(NumberControl || TextControl, { label:'End position (%)', value: topEP, min:0, max:100, onChange:(v)=> setTopEP(parseInt(v||0,10)||0) }) : null,
              HiddenInput({ name:'pwpl_table[card][colors][top_grad][end_pos]', value: showGrad ? topEP : '' }),
            ]);
          }
          if (tab.name==='specsBg'){
            const showGrad = !!specsType;
            const showAngle = specsType === 'linear';
            return h('div', { className:'pwpl-v1-grid' }, [
              h('div', { className:'pwpl-v1-color' }, [
                h('label', { className:'components-base-control__label' }, 'Specs background'),
                h(ColorPicker, { color: specsBg || '#cf7a1a', disableAlpha:true,
                  onChangeComplete:(value)=>{ const hex=(typeof value==='string')?value:(value&&value.hex)?value.hex:''; setSpecsBg(hex); updatePreviewVars({ 'card.colors.specs_bg': hex }); } }),
                HiddenInput({ name:'pwpl_table[card][colors][specs_bg]', value: specsBg }),
              ]),
              h('div', null, [
                h('label', { className:'components-base-control__label' }, 'Specs gradient type'),
                h('select', { value: specsType, onChange:(e)=> { setSpecsType(e.target.value); updatePreviewVars({ 'card.colors.specs_grad.type': e.target.value||'' }); } }, [
                  h('option', { value:'' }, 'None'),
                  h('option', { value:'linear' }, 'Linear'),
                  h('option', { value:'radial' }, 'Radial'),
                  h('option', { value:'conic' }, 'Conic'),
                ]),
                HiddenInput({ name:'pwpl_table[card][colors][specs_grad][type]', value: specsType }),
              ]),
              showGrad ? h('div', { className:'pwpl-v1-color' }, [
                h('label', { className:'components-base-control__label' }, 'Specs gradient start'),
                h(ColorPicker, { color: specsStart || '#cf7a1a', disableAlpha:true,
                  onChangeComplete:(value)=>{ const hex=(typeof value==='string')?value:(value&&value.hex)?value.hex:''; setSpecsStart(hex); updatePreviewVars({ 'card.colors.specs_grad.start': hex }); } }),
                HiddenInput({ name:'pwpl_table[card][colors][specs_grad][start]', value: showGrad ? specsStart : '' }),
              ]) : null,
              showGrad ? h('div', { className:'pwpl-v1-color' }, [
                h('label', { className:'components-base-control__label' }, 'Specs gradient end'),
                h(ColorPicker, { color: specsEnd || '#8a3f00', disableAlpha:true,
                  onChangeComplete:(value)=>{ const hex=(typeof value==='string')?value:(value&&value.hex)?value.hex:''; setSpecsEnd(hex); updatePreviewVars({ 'card.colors.specs_grad.end': hex }); } }),
                HiddenInput({ name:'pwpl_table[card][colors][specs_grad][end]', value: showGrad ? specsEnd : '' }),
              ]) : null,
              showGrad && showAngle ? h(NumberControl || TextControl, { label:'Angle (deg)', value: specsAngle, min:0, max:360, onChange:(v)=> setSpecsAngle(parseInt(v||0,10)||0) }) : null,
              HiddenInput({ name:'pwpl_table[card][colors][specs_grad][angle]', value: (showGrad && showAngle) ? specsAngle : '' }),
              showGrad ? h(NumberControl || TextControl, { label:'Start position (%)', value: specsSP, min:0, max:100, onChange:(v)=> setSpecsSP(parseInt(v||0,10)||0) }) : null,
              HiddenInput({ name:'pwpl_table[card][colors][specs_grad][start_pos]', value: showGrad ? specsSP : '' }),
              showGrad ? h(NumberControl || TextControl, { label:'End position (%)', value: specsEP, min:0, max:100, onChange:(v)=> setSpecsEP(parseInt(v||0,10)||0) }) : null,
              HiddenInput({ name:'pwpl_table[card][colors][specs_grad][end_pos]', value: showGrad ? specsEP : '' }),
            ]);
          }
          return h('div', { className:'pwpl-v1-grid' }, [
            h('div', { className:'pwpl-v1-color' }, [
              h('label', { className:'components-base-control__label' }, 'Keyline color'),
              h(ColorPicker, { color: kColor || '#1c1a16', disableAlpha:true,
                onChangeComplete:(value)=>{ const hex=(typeof value==='string')?value:(value&&value.hex)?value.hex:''; setKColor(hex);} }),
              HiddenInput({ name:'pwpl_table[card][colors][keyline][color]', value: kColor }),
            ]),
            h(NumberControl || TextControl, { label:'Keyline opacity (0–1)', value: kOpacity, min:0, max:1, step:0.01,
              onChange:(v)=>{ const n = (v===''? '' : Math.max(0, Math.min(1, parseFloat(v)||0))); setKOpacity(n);} }),
            HiddenInput({ name:'pwpl_table[card][colors][keyline][opacity]', value: kOpacity }),
          ]);
        })
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

    return h('section', { className:'pwpl-v1-block' }, [
      SectionHeader({ title: i18n(data.i18n.sidebar.advanced), description: 'Trust row and sticky CTA controls.' }),
      h(Card, null, h(CardBody, null,
        h('div', { className:'pwpl-v1-grid' }, [
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
        ])
      ))
    ]);
  }

  // Hide legacy sections once parity is achieved (non-destructive)
  function hideLegacyOnMount(){
    // Hide legacy Badges meta box to avoid duplicate UIs
    const ids = ['pwpl_table_badges', 'pwpl_table_layout'];
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

  function CTABlock(){
    const cta = (data.ui && data.ui.cta) ? data.ui.cta : {};
    const [widthSel, setWidthSel]   = useState(cta.width || 'full');
    const [height, setHeight]       = useState(parseInt(cta.height||48,10)||48);
    const [padX, setPadX]           = useState(parseInt(cta.pad_x||22,10)||22);
    const [radius, setRadius]       = useState(parseInt(cta.radius||12,10)||12);
    const [borderW, setBorderW]     = useState(parseFloat(cta.border_width||1.5)||1.5);
    const [minW, setMinW]           = useState(parseInt(cta.min_w||0,10)||0);
    const [maxW, setMaxW]           = useState(parseInt(cta.max_w||0,10)||0);
    const [lift, setLift]           = useState(parseInt(cta.lift||1,10)||1);

    return h('section', { className:'pwpl-v1-block' }, [
      SectionHeader({ title: i18n(data.i18n.sidebar.cta), description: 'CTA size and layout.' }),
      h(Card, null, h(CardBody, null,
        h(TabPanel, { tabs:[ { name:'sizeLayout', title:i18n(data.i18n.tabs.sizeLayout) } ] }, (tab)=>{
          return h('div', { className:'pwpl-v1-grid' }, [
            h('div', null, [
              h('label', { className:'components-base-control__label' }, 'Width'),
              h('select', { value: widthSel, onChange:(e)=> setWidthSel(e.target.value) }, [
                h('option', { value:'auto' }, 'Auto'),
                h('option', { value:'full' }, 'Full'),
              ]),
              HiddenInput({ name:'pwpl_table[ui][cta][width]', value: widthSel }),
            ]),
            h(NumberControl || TextControl, { label:'Height (px)', value:height, min:36, max:64, onChange:(v)=> setHeight(parseInt(v||0,10)||0) }),
            HiddenInput({ name:'pwpl_table[ui][cta][height]', value:height }),
            h(NumberControl || TextControl, { label:'Padding X (px)', value:padX, min:10, max:32, onChange:(v)=> setPadX(parseInt(v||0,10)||0) }),
            HiddenInput({ name:'pwpl_table[ui][cta][pad_x]', value:padX }),
            h(NumberControl || TextControl, { label:'Radius (px)', value:radius, min:0, max:999, onChange:(v)=> setRadius(parseInt(v||0,10)||0) }),
            HiddenInput({ name:'pwpl_table[ui][cta][radius]', value:radius }),
            h(NumberControl || TextControl, { label:'Border width (px)', value:borderW, min:0, max:4, step:0.5, onChange:(v)=> setBorderW(parseFloat(v||0)||0) }),
            HiddenInput({ name:'pwpl_table[ui][cta][border_width]', value:borderW }),
            h(NumberControl || TextControl, { label:'Min width (px)', value:minW, min:0, max:4000, onChange:(v)=> setMinW(parseInt(v||0,10)||0) }),
            HiddenInput({ name:'pwpl_table[ui][cta][min_w]', value:minW }),
            h(NumberControl || TextControl, { label:'Max width (px)', value:maxW, min:0, max:4000, onChange:(v)=> setMaxW(parseInt(v||0,10)||0) }),
            HiddenInput({ name:'pwpl_table[ui][cta][max_w]', value:maxW }),
            h(NumberControl || TextControl, { label:'Hover lift (px)', value:lift, min:0, max:3, onChange:(v)=> setLift(parseInt(v||0,10)||0) }),
            HiddenInput({ name:'pwpl_table[ui][cta][lift]', value:lift }),
          ]);
        })
      ))
    ]);
  }

  function SpecsBlock(){
    const specs = (data.ui && data.ui.specs) ? data.ui.specs : { style:'default', anim:{ flags:[], intensity:45, mobile:0 } };
    const [styleSel, setStyleSel] = useState(specs.style || 'default');
    const initFlags = (specs.anim && Array.isArray(specs.anim.flags))? specs.anim.flags : [];
    const [flags, setFlags] = useState(new Set(initFlags));
    const [intensity, setIntensity] = useState(parseInt((specs.anim && specs.anim.intensity)||45,10)||45);
    const [mobile, setMobile] = useState((specs.anim && specs.anim.mobile) ? 1 : 0);

    const toggleFlag = (key)=>{
      const next = new Set(flags);
      if (next.has(key)) next.delete(key); else next.add(key);
      setFlags(next);
    };

    const flagKeys = ['row','icon','divider','chip','stagger'];

    return h('section', { className:'pwpl-v1-block' }, [
      SectionHeader({ title: i18n(data.i18n.sidebar.specs), description: 'Specifications style and interaction controls.' }),
      h(Card, null, h(CardBody, null,
        h(TabPanel, { tabs:[
          { name:'style', title: i18n(data.i18n.tabs.style) },
          { name:'interact', title: i18n(data.i18n.tabs.interact) },
        ]}, (tab)=>{
          if (tab.name==='style'){
            return h('div', { className:'pwpl-v1-grid' }, [
              h('div', null, [
                h('label', { className:'components-base-control__label' }, 'Specs style'),
                h('select', { value:styleSel, onChange:(e)=> setStyleSel(e.target.value) }, [
                  h('option', { value:'default' }, 'Default'),
                  h('option', { value:'flat' }, 'Flat'),
                  h('option', { value:'segmented' }, 'Segmented'),
                  h('option', { value:'chips' }, 'Chips'),
                ]),
                HiddenInput({ name:'pwpl_table[ui][specs_style]', value: styleSel }),
              ])
            ]);
          }
          return h('div', { className:'pwpl-v1-grid' }, [
            h('div', { style:{ display:'grid', gridTemplateColumns:'repeat(2, minmax(120px, 1fr))', gap:'10px' } },
              flagKeys.map((k)=> h('label', { key:k, className:'components-base-control__label' }, [
                h('input', { type:'checkbox', checked: flags.has(k), onChange:()=> toggleFlag(k) }),
                ' ', k
              ]))
            ),
            // Emit selected flags as multiple hidden inputs
            Array.from(flags).map((k)=> HiddenInput({ key:k, name:'pwpl_table[ui][specs_anim][flags][]', value:k })),
            h(NumberControl || TextControl, { label:'Intensity (0–100)', value:intensity, min:0, max:100, onChange:(v)=> setIntensity(parseInt(v||0,10)||0) }),
            HiddenInput({ name:'pwpl_table[ui][specs_anim][intensity]', value:intensity }),
            h('label', { className:'components-base-control__label' }, [
              h('input', { type:'checkbox', checked: !!mobile, onChange:(e)=> setMobile(e.target.checked ? 1 : 0) }), ' Enable on mobile'
            ]),
            HiddenInput({ name:'pwpl_table[ui][specs_anim][mobile]', value: mobile ? 1 : '' }),
          ]);
        })
      ))
    ]);
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
