(function(w){
  const wp = w.wp || window.wp || {};
  const { createElement: h, useState } = (wp.element || {});
  const { Card, CardBody, TabPanel, TextControl, __experimentalNumberControl: NumberControl, ColorPicker } = (wp.components || {});

  const i18n = (s) => s || '';
  const data = w.PWPL_AdminV1 || { postId: 0, layout: { widths: {}, columns: {} }, card: {}, i18n: {} };

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
        active === 'table' ? h(TableLayoutBlock) : null,
        active === 'card'  ? h(PlanCardBlock)   : null,
        active === 'typography' ? h(TypographyBlock) : null,
        active === 'colors' ? h(ColorsSurfacesBlock) : null,
      ])
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
                    onChangeComplete: (value)=>{ const hex = (typeof value==='string')? value : (value && value.hex) ? value.hex : ''; setTopColor(hex); },
                    disableAlpha: true,
                  }),
                  HiddenInput({ name:'pwpl_table[card][text][top][color]', value: topColor })
                ]),
                h(TextControl, { label:'Font family', value: topFamily, onChange:setTopFamily, placeholder:'system-ui, -apple-system, sans-serif' }),
                HiddenInput({ name:'pwpl_table[card][text][top][family]', value: topFamily }),
                h(NumberControl || TextControl, { label:'Font size (px)', value:topSize, min:10, max:28, onChange:(v)=> setTopSize(parseInt(v||0,10)||0) }),
                HiddenInput({ name:'pwpl_table[card][text][top][size]', value: topSize }),
                h(NumberControl || TextControl, { label:'Font weight', value:topWeight, min:300, max:900, step:50, onChange:(v)=> setTopWeight(parseInt(v||0,10)||0) }),
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

    return h('section', { className:'pwpl-v1-block' }, [
      SectionHeader({ title: i18n(data.i18n.sidebar.colors), description: 'Surface colors. Gradients can be added in a later pass.' }),
      h(Card, null, h(CardBody, null,
        h(TabPanel, { tabs:[
          { name:'topBg',   title: i18n(data.i18n.tabs.topBg) },
          { name:'specsBg', title: i18n(data.i18n.tabs.specsBg) },
          { name:'keyline', title: i18n(data.i18n.tabs.keyline) },
        ]}, (tab)=>{
          if (tab.name==='topBg'){
            return h('div', { className:'pwpl-v1-grid' }, [
              h('div', { className:'pwpl-v1-color' }, [
                h('label', { className:'components-base-control__label' }, 'Top background'),
                h(ColorPicker, { color: topBg || '#fff6e0', disableAlpha:true,
                  onChangeComplete:(value)=>{ const hex=(typeof value==='string')?value:(value&&value.hex)?value.hex:''; setTopBg(hex);} }),
                HiddenInput({ name:'pwpl_table[card][colors][top_bg]', value: topBg }),
              ])
            ]);
          }
          if (tab.name==='specsBg'){
            return h('div', { className:'pwpl-v1-grid' }, [
              h('div', { className:'pwpl-v1-color' }, [
                h('label', { className:'components-base-control__label' }, 'Specs background'),
                h(ColorPicker, { color: specsBg || '#cf7a1a', disableAlpha:true,
                  onChangeComplete:(value)=>{ const hex=(typeof value==='string')?value:(value&&value.hex)?value.hex:''; setSpecsBg(hex);} }),
                HiddenInput({ name:'pwpl_table[card][colors][specs_bg]', value: specsBg }),
              ])
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

  function mount(){
    const root = document.getElementById('pwpl-admin-v1-root');
    if (!root || !wp || !wp.element || !h) return;
    if (wp.element.render) {
      wp.element.render(h(App), root);
    } else if (wp.element.createRoot) {
      const rootApi = wp.element.createRoot(root);
      rootApi.render(h(App));
    }
  }

  if (document.readyState === 'complete' || document.readyState === 'interactive') {
    setTimeout(mount, 0);
  } else {
    document.addEventListener('DOMContentLoaded', mount);
  }

})(window);
