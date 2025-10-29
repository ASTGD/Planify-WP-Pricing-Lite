(function(wp){
  const { createElement: h, useState } = wp.element;
  const { Card, CardBody, TabPanel, TextControl, __experimentalNumberControl: NumberControl, ColorPicker, PanelBody, Button } = wp.components;

  const i18n = (w) => w || '';
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
            return h('div', { className: 'pwpl-v1-grid' }, [
              h('p', null, 'Breakpoint controls are unchanged (see legacy box below).'),
            ]);
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
                HiddenInput({ name: 'pwpl_table[card][layout][radius]', value: radius }),
                HiddenInput({ name: 'pwpl_table[card][layout][border_w]', value: borderW }),
              ]);
            }
            return h('div', { className: 'pwpl-v1-grid' }, [
              h('div', { className: 'pwpl-v1-color' }, [
                h('label', { className: 'components-base-control__label' }, 'Border color'),
                h(ColorPicker, {
                  color: borderColor || '#e5e7eb',
                  onChangeComplete: (value) => setBorderColor(value.hex || ''),
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
    if (root && wp.element){
      wp.element.render(h(App), root);
    }
  }

  document.addEventListener('DOMContentLoaded', mount);

})(window);

