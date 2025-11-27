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

    var previewEl = document.createElement( 'div' );
    previewEl.className = 'pwpl-table-wizard__preview';

    var iframeEl = document.createElement( 'iframe' );
    iframeEl.className = 'pwpl-table-wizard__iframe';
    iframeEl.id = 'pwpl-table-wizard-preview';
    iframeEl.setAttribute( 'title', 'Table preview' );

    previewEl.appendChild( iframeEl );
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

    function renderTemplates() {
        sidebarEl.innerHTML = '';
        var templatesWrap = document.createElement( 'div' );
        templatesWrap.className = 'pwpl-templates';
        templates.forEach( function( tpl ) {
            var btn = document.createElement( 'button' );
            btn.type = 'button';
            btn.className = 'pwpl-template-card';
            btn.dataset.templateId = tpl.id;

            var title = document.createElement( 'div' );
            title.className = 'pwpl-template-card__title';
            title.textContent = tpl.label || tpl.id;
            btn.appendChild( title );

            if ( tpl.description ) {
                var desc = document.createElement( 'p' );
                desc.className = 'pwpl-template-card__description';
                desc.textContent = tpl.description;
                btn.appendChild( desc );
            }

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
            tile.textContent = layouts[ lid ].label || lid;
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
    }

    function selectLayout( layoutId ) {
        state.selectedLayoutId = layoutId || null;
        Array.prototype.forEach.call( layoutList.querySelectorAll( '.pwpl-layout-tile' ), function( tile ) {
            tile.classList.toggle( 'is-selected', tile.dataset.layoutId === layoutId );
        } );
        loadPreview( state.selectedTemplateId, state.selectedLayoutId, state.selectedCardStyleId );
    }

    function selectCardStyle( styleId ) {
        state.selectedCardStyleId = styleId || null;
        Array.prototype.forEach.call( cardStyleList.querySelectorAll( '.pwpl-card-style-tile' ), function( tile ) {
            tile.classList.toggle( 'is-selected', tile.dataset.cardStyleId === styleId );
        } );
        loadPreview( state.selectedTemplateId, state.selectedLayoutId, state.selectedCardStyleId );
    }

    function loadPreview( templateId, layoutId, cardStyleId ) {
        if ( ! templateId ) {
            return;
        }

        previewEl.classList.add( 'is-loading' );

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
            tile.textContent = styles[ sid ].label || sid;
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
