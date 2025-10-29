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
      ])
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
