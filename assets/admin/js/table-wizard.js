( function() {
    if ( typeof window.PWPL_TableWizard === 'undefined' ) {
        return;
    }
    var config = window.PWPL_TableWizard;
    var root = document.getElementById( 'pwpl-table-wizard-root' );
    if ( ! root ) {
        return;
    }

    var templates = Array.isArray( config.templates ) ? config.templates : [];
    if ( ! templates.length ) {
        root.innerHTML = '<p>' + ( config.i18n && config.i18n.selectTemplate ? config.i18n.selectTemplate : 'No templates available.' ) + '</p>';
        return;
    }

    var state = {
        selectedTemplateId: null,
        selectedLayoutId: null,
        selectedCardStyleId: null,
    };

    var layoutEl = document.createElement( 'div' );
    layoutEl.className = 'pwpl-table-wizard-layout';

    var sidebarEl = document.createElement( 'div' );
    sidebarEl.className = 'pwpl-table-wizard__sidebar';
    var templatesWrap = document.createElement( 'div' );
    templatesWrap.className = 'pwpl-templates';

    var previewEl = document.createElement( 'div' );
    previewEl.className = 'pwpl-table-wizard__preview';

    var iframeEl = document.createElement( 'iframe' );
    iframeEl.className = 'pwpl-table-wizard__iframe';
    iframeEl.id = 'pwpl-table-wizard-preview';
    iframeEl.setAttribute( 'title', 'Table preview' );

    previewEl.appendChild( iframeEl );
    // Assemble
    root.appendChild( stepsBar );
    layoutEl.appendChild( sidebarEl );
    layoutEl.appendChild( previewEl );
    root.appendChild( layoutEl );

    var footer = document.createElement( 'div' );
    footer.className = 'pwpl-table-wizard__footer';
    var createBtn = document.createElement( 'button' );
    createBtn.type = 'button';
    createBtn.className = 'button button-primary pwpl-table-wizard__submit';
    createBtn.textContent = ( config.i18n && config.i18n.createLabel ) || 'Create table';
    createBtn.disabled = ! templates.length;
    footer.appendChild( createBtn );
    root.appendChild( footer );

    var stepsBar = document.createElement( 'div' );
    stepsBar.className = 'pwpl-table-wizard__steps';

    var stepTemplate = document.createElement( 'button' );
    stepTemplate.type = 'button';
    stepTemplate.className = 'pwpl-step-indicator';
    stepTemplate.dataset.step = '1';
    stepTemplate.textContent = ( config.i18n && config.i18n.stepTemplate ) || 'Step 1 · Template';

    var stepLayout = document.createElement( 'button' );
    stepLayout.type = 'button';
    stepLayout.className = 'pwpl-step-indicator';
    stepLayout.dataset.step = '2';
    stepLayout.textContent = ( config.i18n && config.i18n.stepLayout ) || 'Step 2 · Layout';

    var stepCardStyle = document.createElement( 'button' );
    stepCardStyle.type = 'button';
    stepCardStyle.className = 'pwpl-step-indicator';
    stepCardStyle.dataset.step = '3';
    stepCardStyle.textContent = ( config.i18n && config.i18n.stepCardStyle ) || 'Step 3 · Card style';

    stepsBar.appendChild( stepTemplate );
    stepsBar.appendChild( stepLayout );
    stepsBar.appendChild( stepCardStyle );

    var layoutSectionTitle = document.createElement( 'div' );
    layoutSectionTitle.className = 'pwpl-wizard-section-title';
    layoutSectionTitle.textContent = ( config.i18n && config.i18n.layout ) || 'Layout';
    var layoutList = document.createElement( 'div' );
    layoutList.className = 'pwpl-layout-list';

    var cardStyleSectionTitle = document.createElement( 'div' );
    cardStyleSectionTitle.className = 'pwpl-wizard-section-title';
    cardStyleSectionTitle.textContent = ( config.i18n && config.i18n.cardStyle ) || 'Card style';
    var cardStyleList = document.createElement( 'div' );
    cardStyleList.className = 'pwpl-card-style-list';

    var TEMPLATE_VISUALS = {
        'saas-3-col': { type: 'cols', cols: 3, featured: true },
        'comparison-table': { type: 'compare', cols: 4 },
        'service-plans': { type: 'cols', cols: 3, featured: false },
    };
    var RECOMMENDED_TEMPLATES = [ 'saas-3-col' ];

    var LAYOUT_VISUALS = {
        'default': { cols: 3, featured: false },
        'featured-middle': { cols: 3, featured: true },
        'comparison': { cols: 4, featured: false, compare: true },
    };

    var CARD_STYLE_VISUALS = {
        'default': { headerBand: false, shadow: 'soft' },
        'featured-middle': { headerBand: true, shadow: 'strong' },
    };

    function renderTemplates() {
        sidebarEl.innerHTML = '';
        templatesWrap.innerHTML = '';
        templates.forEach( function( tpl ) {
            var btn = document.createElement( 'button' );
            btn.type = 'button';
            btn.className = 'pwpl-template-card';
            btn.dataset.templateId = tpl.id;

            var thumb = document.createElement( 'div' );
            thumb.className = 'pwpl-template-card__thumb';
            thumb.setAttribute( 'aria-hidden', 'true' );
            thumb.appendChild( buildTemplateThumb( tpl.id ) );
            btn.appendChild( thumb );

            var body = document.createElement( 'div' );
            body.className = 'pwpl-template-card__body';

            var title = document.createElement( 'div' );
            title.className = 'pwpl-template-card__title';
            title.textContent = tpl.label || tpl.id;
            body.appendChild( title );

            if ( tpl.description ) {
                var desc = document.createElement( 'p' );
                desc.className = 'pwpl-template-card__description';
                desc.textContent = tpl.description;
                body.appendChild( desc );
            }

            if ( RECOMMENDED_TEMPLATES.indexOf( tpl.id ) !== -1 ) {
                var badge = document.createElement( 'span' );
                badge.className = 'pwpl-template-card__badge pwpl-template-card__badge--recommended';
                badge.textContent = ( config.i18n && config.i18n.recommended ) || 'Recommended';
                body.appendChild( badge );
            }

            btn.appendChild( body );

            btn.addEventListener( 'click', function() {
                selectTemplate( tpl.id );
            } );

            templatesWrap.appendChild( btn );
        } );
        sidebarEl.appendChild( templatesWrap );
        sidebarEl.appendChild( layoutSectionTitle );
        sidebarEl.appendChild( layoutList );
        sidebarEl.appendChild( cardStyleSectionTitle );
        sidebarEl.appendChild( cardStyleList );
    }

    function getTemplateById( id ) {
        for ( var i = 0; i < templates.length; i++ ) {
            if ( templates[ i ].id === id ) {
                return templates[ i ];
            }
        }
        return null;
    }

    function buildTemplateThumb( templateId ) {
        var visual = TEMPLATE_VISUALS[ templateId ] || { type: 'cols', cols: 3, featured: false };
        var wrap = document.createElement( 'div' );
        wrap.className = 'pwpl-thumb pwpl-thumb--' + visual.type;

        if ( visual.type === 'compare' ) {
            var grid = document.createElement( 'div' );
            grid.className = 'pwpl-thumb-compare';
            for ( var i = 0; i < ( visual.cols || 4 ); i++ ) {
                var col = document.createElement( 'div' );
                col.className = 'pwpl-thumb-col';
                if ( i === 0 ) {
                    col.classList.add( 'is-featured' );
                }
                grid.appendChild( col );
            }
            wrap.appendChild( grid );
        } else {
            var cols = visual.cols || 3;
            var row = document.createElement( 'div' );
            row.className = 'pwpl-thumb-row pwpl-thumb-cols--' + cols;
            for ( var j = 0; j < cols; j++ ) {
                var c = document.createElement( 'div' );
                c.className = 'pwpl-thumb-col';
                if ( visual.featured && j === Math.floor( cols / 2 ) ) {
                    c.classList.add( 'is-featured' );
                }
                row.appendChild( c );
            }
            wrap.appendChild( row );
        }
        return wrap;
    }

    function renderLayouts() {
        layoutList.innerHTML = '';
        var tpl = getTemplateById( state.selectedTemplateId );
        var layouts = tpl && tpl.layouts ? tpl.layouts : {};
        var layoutIds = Object.keys( layouts );
        if ( ! layoutIds.length ) {
            return;
        }
        layoutIds.forEach( function( lid ) {
            var tile = document.createElement( 'button' );
            tile.type = 'button';
            tile.className = 'pwpl-layout-tile';
            tile.dataset.layoutId = lid;
            var pill = buildLayoutPill( lid );
            tile.appendChild( pill );
            var lbl = document.createElement( 'span' );
            lbl.className = 'pwpl-layout-label';
            lbl.textContent = layouts[ lid ].label || lid;
            tile.appendChild( lbl );

            if ( state.selectedLayoutId === lid ) {
                tile.classList.add( 'is-selected' );
            }
            tile.addEventListener( 'click', function() {
                selectLayout( lid );
            } );
            layoutList.appendChild( tile );
        } );
    }

    function selectTemplate( templateId ) {
        if ( ! templateId ) {
            return;
        }
        state.selectedTemplateId = templateId;
        var tpl = getTemplateById( templateId );
        var layouts = tpl && tpl.layouts ? tpl.layouts : {};
        var layoutIds = Object.keys( layouts );
        var defaultLayoutId = null;
        if ( layouts.default ) {
            defaultLayoutId = 'default';
        } else if ( layoutIds.length ) {
            defaultLayoutId = layoutIds[0];
        }
        state.selectedLayoutId = defaultLayoutId;

        var cardStyles = tpl && tpl.card_styles ? tpl.card_styles : {};
        var styleIds = Object.keys( cardStyles );
        var defaultCardStyleId = null;
        if ( cardStyles.default ) {
            defaultCardStyleId = 'default';
        } else if ( styleIds.length ) {
            defaultCardStyleId = styleIds[0];
        }
        state.selectedCardStyleId = defaultCardStyleId;

        Array.prototype.forEach.call( sidebarEl.querySelectorAll( '.pwpl-template-card' ), function( card ) {
            card.classList.toggle( 'is-selected', card.dataset.templateId === templateId );
        } );
        renderLayouts();
        renderCardStyles();
        loadPreview( templateId, state.selectedLayoutId, state.selectedCardStyleId );
        updateSteps();
    }

    function selectLayout( layoutId ) {
        state.selectedLayoutId = layoutId || null;
        Array.prototype.forEach.call( layoutList.querySelectorAll( '.pwpl-layout-tile' ), function( tile ) {
            tile.classList.toggle( 'is-selected', tile.dataset.layoutId === layoutId );
        } );
        loadPreview( state.selectedTemplateId, state.selectedLayoutId, state.selectedCardStyleId );
        updateSteps();
    }

    function selectCardStyle( styleId ) {
        state.selectedCardStyleId = styleId || null;
        Array.prototype.forEach.call( cardStyleList.querySelectorAll( '.pwpl-card-style-tile' ), function( tile ) {
            tile.classList.toggle( 'is-selected', tile.dataset.cardStyleId === styleId );
        } );
        loadPreview( state.selectedTemplateId, state.selectedLayoutId, state.selectedCardStyleId );
        updateSteps();
    }

    function loadPreview( templateId, layoutId, cardStyleId ) {
        if ( ! templateId ) {
            return;
        }

        previewEl.classList.add( 'is-loading' );
        sidebarEl.classList.add( 'is-loading' );

        var apiFetch = window.wp && wp.apiFetch ? wp.apiFetch : null;
        if ( apiFetch && config.rest && config.rest.previewUrl ) {
            apiFetch( {
                path: config.rest.previewUrl.replace( restUrlRoot(), '' ),
                method: 'POST',
                headers: { 'X-WP-Nonce': config.rest.nonce },
                data: {
                    template_id: templateId,
                    layout_id: layoutId || '',
                    card_style_id: cardStyleId || '',
                },
            } ).catch( function( err ) {
                // eslint-disable-next-line no-console
                console.error( 'Preview fetch failed', err );
            } );
        }

        var frameUrl = buildPreviewFrameUrl( templateId, layoutId, cardStyleId );
        iframeEl.src = frameUrl;
        iframeEl.onload = function() {
            previewEl.classList.remove( 'is-loading' );
            sidebarEl.classList.remove( 'is-loading' );
        };
    }

    function restUrlRoot() {
        if ( config.rest && config.rest.root ) {
            return config.rest.root.replace( /\/+$/, '' ) + '/';
        }
        return '/wp-json/';
    }

    function buildPreviewFrameUrl( templateId, layoutId, cardStyleId ) {
        var base = config.previewFrame && config.previewFrame.url ? config.previewFrame.url : '';
        try {
            var url = new URL( base, window.location.origin );
            url.searchParams.set( 'template_id', templateId );
            if ( layoutId ) {
                url.searchParams.set( 'layout_id', layoutId );
            }
            if ( cardStyleId ) {
                url.searchParams.set( 'card_style_id', cardStyleId );
            }
            return url.toString();
        } catch (e) {
            var qs = 'template_id=' + encodeURIComponent( templateId );
            if ( layoutId ) {
                qs += '&layout_id=' + encodeURIComponent( layoutId );
            }
            if ( cardStyleId ) {
                qs += '&card_style_id=' + encodeURIComponent( cardStyleId );
            }
            return base + ( base.indexOf( '?' ) === -1 ? '?' : '&' ) + qs;
        }
    }

    function buildLayoutPill( layoutId ) {
        var visual = LAYOUT_VISUALS[ layoutId ] || { cols: 3, featured: false };
        var pill = document.createElement( 'span' );
        pill.className = 'pwpl-layout-pill';
        for ( var i = 0; i < ( visual.cols || 3 ); i++ ) {
            var bar = document.createElement( 'span' );
            bar.className = 'pwpl-layout-pill-col';
            if ( visual.featured && i === Math.floor( ( visual.cols || 3 ) / 2 ) ) {
                bar.classList.add( 'is-featured' );
            }
            if ( visual.compare ) {
                bar.classList.add( 'is-compare' );
            }
            pill.appendChild( bar );
        }
        return pill;
    }

    function buildCardStylePill( styleId ) {
        var visual = CARD_STYLE_VISUALS[ styleId ] || { headerBand: false, shadow: 'soft' };
        var pill = document.createElement( 'span' );
        pill.className = 'pwpl-card-style-pill';
        var card = document.createElement( 'span' );
        card.className = 'pwpl-card-style-pill__card';
        if ( visual.headerBand ) {
            card.classList.add( 'has-band' );
        }
        card.dataset.shadow = visual.shadow || 'soft';
        pill.appendChild( card );
        return pill;
    }

    function renderCardStyles() {
        cardStyleList.innerHTML = '';
        var tpl = getTemplateById( state.selectedTemplateId );
        var styles = tpl && tpl.card_styles ? tpl.card_styles : {};
        var styleIds = Object.keys( styles );
        if ( ! styleIds.length ) {
            return;
        }
        styleIds.forEach( function( sid ) {
            var tile = document.createElement( 'button' );
            tile.type = 'button';
            tile.className = 'pwpl-card-style-tile';
            tile.dataset.cardStyleId = sid;
            var pill = buildCardStylePill( sid );
            tile.appendChild( pill );
            var lbl = document.createElement( 'span' );
            lbl.className = 'pwpl-card-style-label';
            lbl.textContent = styles[ sid ].label || sid;
            tile.appendChild( lbl );
            if ( state.selectedCardStyleId === sid ) {
                tile.classList.add( 'is-selected' );
            }
            tile.addEventListener( 'click', function() {
                selectCardStyle( sid );
            } );
            cardStyleList.appendChild( tile );
        } );
    }

    renderTemplates();
    if ( templates[0] ) {
        selectTemplate( templates[0].id );
    }

    function updateSteps() {
        var hasLayout = !! state.selectedLayoutId;
        var hasCardStyle = !! state.selectedCardStyleId;
        stepTemplate.classList.add( 'is-active' );
        stepLayout.classList.toggle( 'is-active', hasLayout );
        stepCardStyle.classList.toggle( 'is-active', hasCardStyle );
    }

    updateSteps();

    createBtn.addEventListener( 'click', function() {
        if ( ! state.selectedTemplateId ) {
            return;
        }
        createBtn.disabled = true;
        createBtn.classList.add( 'is-busy' );

        var apiFetch = window.wp && wp.apiFetch ? wp.apiFetch : null;
        if ( ! apiFetch || ! config.rest || ! config.rest.createUrl ) {
            createBtn.disabled = false;
            createBtn.classList.remove( 'is-busy' );
            return;
        }

        apiFetch( {
            path: config.rest.createUrl.replace( restUrlRoot(), '' ),
            method: 'POST',
            headers: { 'X-WP-Nonce': config.rest.nonce },
            data: {
                template_id:   state.selectedTemplateId,
                layout_id:     state.selectedLayoutId || '',
                card_style_id: state.selectedCardStyleId || '',
            },
        } ).then( function( response ) {
            if ( response && response.edit_url ) {
                window.location = response.edit_url;
            } else {
                createBtn.disabled = false;
                createBtn.classList.remove( 'is-busy' );
                // eslint-disable-next-line no-console
                console.error( 'Unexpected create-table response', response );
            }
        } ).catch( function( err ) {
            createBtn.disabled = false;
            createBtn.classList.remove( 'is-busy' );
            // eslint-disable-next-line no-console
            console.error( 'Create-table failed', err );
            alert( ( config.i18n && config.i18n.createError ) || 'Unable to create table. Please try again.' );
        } );
    } );
}() );
