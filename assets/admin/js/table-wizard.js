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
    templates = templates.filter( function( tpl ) {
        return ! tpl.wizard_hidden;
    } );
    if ( ! templates.length ) {
        root.innerHTML = '<p>' + ( config.i18n && config.i18n.selectTemplate ? config.i18n.selectTemplate : 'No templates available.' ) + '</p>';
        return;
    }

    var MAX_PLAN_COUNT = 8;
    var MIN_PLAN_COUNT = 1;

    var CATEGORY_LABELS = {
        hosting: 'Hosting',
        saas: 'SaaS',
        services: 'Services',
        comparison: 'Comparison',
        classic: 'Classic',
        carousel: 'Carousel',
        uncategorized: 'General',
    };

    var LAYOUT_TYPE_LABELS = {
        grid: 'Grid',
        carousel: 'Carousel',
        comparison: 'Comparison',
        classic: 'Classic',
    };

    var TEMPLATE_VISUALS = {
        'saas-3-col': { type: 'grid', cols: 3, featured: true },
        'app-soft-cards': { type: 'grid', cols: 3, featured: true },
        'comparison-table': { type: 'compare', cols: 4 },
        'service-plans': { type: 'columns', cols: 3, featured: false },
        'saas-grid-v2': { type: 'grid', cols: 3, featured: true },
        'comparison-matrix': { type: 'compare', cols: 4 },
        'image-hero': { type: 'image', cols: 3, featured: true },
        'minimal-focus': { type: 'minimal', cols: 3, featured: false },
    };

    var LAYOUT_VISUALS = {
        'default': { cols: 3, featured: false },
        'featured-middle': { cols: 3, featured: true },
        'comparison': { cols: 4, featured: false, compare: true },
    };

    var CARD_STYLE_VISUALS = {
        'default': { headerBand: false, shadow: 'soft' },
        'featured-middle': { headerBand: true, shadow: 'strong' },
    };

    var META_KEYS = {
        FEATURED: '_pwpl_featured',
        SPECS: '_pwpl_specs',
        VARIANTS: '_pwpl_variants',
        BADGES: '_pwpl_badges_override',
        TRUST_OVERRIDE: '_pwpl_plan_trust_items_override',
    };

    var state = {
        step: 1,
        selectedTemplateId: null,
        selectedLayoutId: null,
        selectedCardStyleId: null,
        layoutType: 'grid',
        plans: [],
        templatePlanDefaults: [],
        activePlanIndex: 0,
        editingPlanIndex: null,
        editingFeatureIndex: null,
        dimensions: {
            platform: true,
            period: true,
            location: false,
        },
        openTemplateDetailsId: null,
        selectedCategoryFilter: '',
    };

    var nameInput;
    var themeSelect;
    var openMenuIndex = null;
    var openFeatureMenuIndex = null;
    var draggingFeatureIndex = null;
    function moveFeature( plan, from, to ) {
        if ( from === to || from < 0 || to < 0 || ! plan.meta[ META_KEYS.SPECS ] ) {
            return;
        }
        if ( from >= plan.meta[ META_KEYS.SPECS ].length || to >= plan.meta[ META_KEYS.SPECS ].length ) {
            return;
        }
        var item = plan.meta[ META_KEYS.SPECS ].splice( from, 1 )[0];
        plan.meta[ META_KEYS.SPECS ].splice( to, 0, item );
        draggingFeatureIndex = null;
        openFeatureMenuIndex = null;
        state.editingFeatureIndex = null;
        syncPlanChange();
        renderPlanEditor();
    }

    function applySampleExtrasToPlan( plan, sampleSet ) {
        if ( ! plan || ! plan.meta || ! sampleSet ) {
            return;
        }
        if ( sampleSet.cta ) {
            var variants = Array.isArray( plan.meta[ META_KEYS.VARIANTS ] ) ? plan.meta[ META_KEYS.VARIANTS ] : [ defaultVariant() ];
            if ( sampleSet.cta.label ) {
                variants[0].cta_label = sampleSet.cta.label;
            }
            if ( sampleSet.cta.url ) {
                variants[0].cta_url = sampleSet.cta.url;
            }
            if ( sampleSet.cta.target ) {
                variants[0].target = sampleSet.cta.target;
            }
            plan.meta[ META_KEYS.VARIANTS ] = variants;
        }
        if ( sampleSet.hero_image ) {
            plan.meta.hero_image_url = sampleSet.hero_image;
            if ( plan.meta.hero_image ) {
                delete plan.meta.hero_image;
            }
        }
        if ( sampleSet.pricing ) {
            var variantsPricing = Array.isArray( plan.meta[ META_KEYS.VARIANTS ] ) ? plan.meta[ META_KEYS.VARIANTS ] : [ defaultVariant() ];
            if ( sampleSet.pricing.price ) {
                variantsPricing[0].price = sampleSet.pricing.price;
            }
            if ( sampleSet.pricing.sale_price ) {
                variantsPricing[0].sale_price = sampleSet.pricing.sale_price;
            }
            if ( sampleSet.pricing.period ) {
                variantsPricing[0].period = sampleSet.pricing.period;
            }
            plan.meta[ META_KEYS.VARIANTS ] = variantsPricing;
            if ( sampleSet.pricing.billing ) {
                plan.meta.billing = sampleSet.pricing.billing;
            }
        }
        if ( sampleSet.badge && Object.keys( sampleSet.badge ).length ) {
            plan.meta[ META_KEYS.BADGES ] = [
                {
                    label: sampleSet.badge.label || '',
                    color: sampleSet.badge.color || '',
                    text_color: sampleSet.badge.text_color || '',
                },
            ];
        }
        if ( typeof sampleSet.featured !== 'undefined' ) {
            plan.meta[ META_KEYS.FEATURED ] = !! sampleSet.featured;
        }
        if ( sampleSet.trust_items && sampleSet.trust_items.length ) {
            plan.meta[ META_KEYS.TRUST_OVERRIDE ] = sampleSet.trust_items.slice( 0, 3 );
        }
    }

    // Step indicator
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
    stepCardStyle.textContent = ( config.i18n && config.i18n.stepCardStyle ) || 'Step 3 · Create';

    stepsBar.appendChild( stepTemplate );
    stepsBar.appendChild( stepLayout );
    stepsBar.appendChild( stepCardStyle );

    // Layout containers
    var layoutEl = document.createElement( 'div' );
    layoutEl.className = 'pwpl-table-wizard-layout';

    var sidebarEl = document.createElement( 'div' );
    sidebarEl.className = 'pwpl-table-wizard__sidebar';

    var previewEl = document.createElement( 'div' );
    previewEl.className = 'pwpl-table-wizard__preview';

    var previewErrorEl = document.createElement( 'div' );
    previewErrorEl.className = 'pwpl-table-wizard__preview-error';
    previewErrorEl.setAttribute( 'role', 'status' );
    previewErrorEl.setAttribute( 'aria-live', 'polite' );

    var iframeEl = document.createElement( 'iframe' );
    iframeEl.className = 'pwpl-table-wizard__iframe';
    iframeEl.id = 'pwpl-table-wizard-preview';
    iframeEl.setAttribute( 'title', 'Table preview' );

    previewEl.appendChild( previewErrorEl );
    previewEl.appendChild( iframeEl );

    layoutEl.appendChild( sidebarEl );
    layoutEl.appendChild( previewEl );

    root.appendChild( stepsBar );
    root.appendChild( layoutEl );

    // Step containers
    var stepContainer = document.createElement( 'div' );
    stepContainer.className = 'pwpl-table-wizard__steps-container';

    var step1Wrap = document.createElement( 'div' );
    step1Wrap.className = 'pwpl-step-panel pwpl-step-panel--templates';

    var step2Wrap = document.createElement( 'div' );
    step2Wrap.className = 'pwpl-step-panel pwpl-step-panel--layout';

    var step3Wrap = document.createElement( 'div' );
    step3Wrap.className = 'pwpl-step-panel pwpl-step-panel--summary';

    function buildTemplateThumb( templateId ) {
        var visual = TEMPLATE_VISUALS[ templateId ] || { type: 'grid', cols: 3, featured: false };
        var wrap = document.createElement( 'div' );
        wrap.className = 'pwpl-thumb pwpl-thumb--' + visual.type;

        if ( visual.type === 'compare' ) {
            var compare = document.createElement( 'div' );
            compare.className = 'pwpl-thumb-compare';
            for ( var i = 0; i < ( visual.cols || 4 ); i++ ) {
                var col = document.createElement( 'div' );
                col.className = 'pwpl-thumb-compare__col';
                if ( i === 0 ) {
                    col.classList.add( 'is-left' );
                }
                compare.appendChild( col );
            }
            wrap.appendChild( compare );
            return wrap;
        }

        var cols = visual.cols || 3;
        if ( visual.type === 'image' || visual.type === 'grid' || visual.type === 'columns' || visual.type === 'minimal' ) {
            var row = document.createElement( 'div' );
            row.className = 'pwpl-thumb-row pwpl-thumb-cols--' + cols;
            for ( var j = 0; j < cols; j++ ) {
                var card = document.createElement( 'div' );
                card.className = 'pwpl-thumb-card';
                if ( visual.featured && j === Math.floor( cols / 2 ) ) {
                    card.classList.add( 'is-featured' );
                }

                if ( visual.type === 'image' ) {
                    var img = document.createElement( 'div' );
                    img.className = 'pwpl-thumb-card__image';
                    card.appendChild( img );
                }
                var lines = document.createElement( 'div' );
                lines.className = 'pwpl-thumb-card__lines';
                lines.innerHTML = '<span></span><span></span><span></span>';
                card.appendChild( lines );

                row.appendChild( card );
            }
            wrap.appendChild( row );
        }
        return wrap;
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

    // Step 1 content
    var templateFilterWrap = document.createElement( 'div' );
    templateFilterWrap.className = 'pwpl-template-filter';

    var categoryFilter = document.createElement( 'div' );
    categoryFilter.className = 'pwpl-template-category-filter';
    templateFilterWrap.appendChild( categoryFilter );

    var templatesWrap = document.createElement( 'div' );
    templatesWrap.className = 'pwpl-templates';

    var templateDetailsPane = document.createElement( 'div' );
    templateDetailsPane.className = 'pwpl-template-details';

    var step1Actions = document.createElement( 'div' );
    step1Actions.className = 'pwpl-step-actions';
    var step1Next = document.createElement( 'button' );
    step1Next.type = 'button';
    step1Next.className = 'button button-primary pwpl-step-next';
    step1Next.textContent = ( config.i18n && config.i18n.continueTemplate ) || 'Continue with this template';
    step1Next.disabled = true;
    step1Actions.appendChild( step1Next );

    step1Wrap.appendChild( templateFilterWrap );
    step1Wrap.appendChild( templatesWrap );
    step1Wrap.appendChild( step1Actions );

    // Step 2 content
    var layoutSectionTitle = document.createElement( 'div' );
    layoutSectionTitle.className = 'pwpl-wizard-section-title';
    layoutSectionTitle.textContent = ( config.i18n && config.i18n.layout ) || 'Layout';

    var layoutTypeList = document.createElement( 'div' );
    layoutTypeList.className = 'pwpl-layout-type-list';

    var dimsSectionTitle = document.createElement( 'div' );
    dimsSectionTitle.className = 'pwpl-wizard-section-title';
    dimsSectionTitle.textContent = ( config.i18n && config.i18n.dimensionsLabel ) || 'Table tabs';

    var dimGroup = document.createElement( 'div' );
    dimGroup.className = 'pwpl-details__dims';
    [ 'platform', 'period', 'location' ].forEach( function( dimKey ) {
        var btn = document.createElement( 'button' );
        btn.type = 'button';
        btn.className = 'pwpl-dim-toggle';
        btn.dataset.dim = dimKey;
        btn.textContent = dimKey.charAt( 0 ).toUpperCase() + dimKey.slice( 1 );
        btn.addEventListener( 'click', function() {
            var isOn = ! btn.classList.contains( 'is-on' );
            btn.classList.toggle( 'is-on', isOn );
            btn.setAttribute( 'aria-pressed', isOn ? 'true' : 'false' );
            state.dimensions[ dimKey ] = isOn;
            loadPreview();
            renderSummary();
        } );
        dimGroup.appendChild( btn );
    } );

    var cardStyleSectionTitle = document.createElement( 'div' );
    cardStyleSectionTitle.className = 'pwpl-wizard-section-title';
    cardStyleSectionTitle.textContent = ( config.i18n && config.i18n.cardStyle ) || 'Card style';
    var cardStyleList = document.createElement( 'div' );
    cardStyleList.className = 'pwpl-card-style-list';

    var planColumnsSectionTitle = document.createElement( 'div' );
    planColumnsSectionTitle.className = 'pwpl-wizard-section-title';
    planColumnsSectionTitle.textContent = ( config.i18n && config.i18n.columns ) || 'Plan columns';

    var planListWrap = document.createElement( 'div' );
    planListWrap.className = 'pwpl-plan-list-wrap';

    var planColumnsList = document.createElement( 'div' );
    planColumnsList.className = 'pwpl-plan-columns-list';

    var addColumnBtn = document.createElement( 'button' );
    addColumnBtn.type = 'button';
    addColumnBtn.className = 'button pwpl-add-column';
    addColumnBtn.textContent = ( config.i18n && config.i18n.addColumn ) || 'Add column';

    planListWrap.appendChild( planColumnsList );
    planListWrap.appendChild( addColumnBtn );

    var planEditWrap = document.createElement( 'div' );
    planEditWrap.className = 'pwpl-plan-edit-wrap';

    var step2Actions = document.createElement( 'div' );
    step2Actions.className = 'pwpl-step-actions';
    var step2Back = document.createElement( 'button' );
    step2Back.type = 'button';
    step2Back.className = 'button pwpl-step-back';
    step2Back.textContent = ( config.i18n && config.i18n.back ) || 'Back';
    var step2Next = document.createElement( 'button' );
    step2Next.type = 'button';
    step2Next.className = 'button button-primary pwpl-step-next';
    step2Next.textContent = ( config.i18n && config.i18n.continueLayout ) || 'Continue';
    step2Actions.appendChild( step2Back );
    step2Actions.appendChild( step2Next );

    step2Wrap.appendChild( layoutSectionTitle );
    step2Wrap.appendChild( layoutTypeList );
    step2Wrap.appendChild( dimsSectionTitle );
    step2Wrap.appendChild( dimGroup );
    step2Wrap.appendChild( cardStyleSectionTitle );
    step2Wrap.appendChild( cardStyleList );
    step2Wrap.appendChild( planColumnsSectionTitle );
    step2Wrap.appendChild( planListWrap );
    step2Wrap.appendChild( planEditWrap );
    step2Wrap.appendChild( step2Actions );

    // Step 3 content
    var summaryTitle = document.createElement( 'div' );
    summaryTitle.className = 'pwpl-wizard-section-title';
    summaryTitle.textContent = ( config.i18n && config.i18n.summaryTitle ) || 'Summary';

    var summaryList = document.createElement( 'div' );
    summaryList.className = 'pwpl-summary';

    var summaryForm = document.createElement( 'div' );
    summaryForm.className = 'pwpl-summary-form';

    var nameLabel = document.createElement( 'label' );
    nameLabel.className = 'pwpl-details__label';
    nameLabel.setAttribute( 'for', 'pwpl-table-wizard-title' );
    nameLabel.textContent = ( config.i18n && config.i18n.tableName ) || 'Table name';
    nameInput = document.createElement( 'input' );
    nameInput.type = 'text';
    nameInput.className = 'pwpl-details__input';
    nameInput.id = 'pwpl-table-wizard-title';
    nameLabel.appendChild( nameInput );

    var themeLabel = document.createElement( 'label' );
    themeLabel.className = 'pwpl-details__label';
    themeLabel.setAttribute( 'for', 'pwpl-table-wizard-theme' );
    themeLabel.textContent = ( config.i18n && config.i18n.theme ) || 'Theme';
    themeSelect = document.createElement( 'select' );
    themeSelect.className = 'pwpl-details__select';
    themeSelect.id = 'pwpl-table-wizard-theme';
    [
        { value: '', label: ( config.i18n && config.i18n.themeInherit ) || 'Default' },
        { value: 'firevps', label: 'FireVPS' },
        { value: 'warm', label: 'Warm' },
        { value: 'classic', label: 'Classic' },
    ].forEach( function( opt ) {
        var o = document.createElement( 'option' );
        o.value = opt.value;
        o.textContent = opt.label;
        themeSelect.appendChild( o );
    } );
    themeLabel.appendChild( themeSelect );

    summaryForm.appendChild( nameLabel );
    summaryForm.appendChild( themeLabel );

    var summaryActions = document.createElement( 'div' );
    summaryActions.className = 'pwpl-step-actions';

    var step3Back = document.createElement( 'button' );
    step3Back.type = 'button';
    step3Back.className = 'button pwpl-step-back';
    step3Back.textContent = ( config.i18n && config.i18n.back ) || 'Back';

    var createAndOpenBtn = document.createElement( 'button' );
    createAndOpenBtn.type = 'button';
    createAndOpenBtn.className = 'button button-primary pwpl-table-wizard__submit';
    createAndOpenBtn.textContent = ( config.i18n && config.i18n.createLabel ) || 'Create table and open editor';

    var createAndCopyBtn = document.createElement( 'button' );
    createAndCopyBtn.type = 'button';
    createAndCopyBtn.className = 'button pwpl-table-wizard__submit-secondary';
    createAndCopyBtn.textContent = ( config.i18n && config.i18n.createCopyLabel ) || 'Create and copy shortcode';

    summaryActions.appendChild( step3Back );
    summaryActions.appendChild( createAndOpenBtn );
    summaryActions.appendChild( createAndCopyBtn );

    step3Wrap.appendChild( summaryTitle );
    step3Wrap.appendChild( summaryList );
    step3Wrap.appendChild( summaryForm );
    step3Wrap.appendChild( summaryActions );

    stepContainer.appendChild( step1Wrap );
    stepContainer.appendChild( step2Wrap );
    stepContainer.appendChild( step3Wrap );
    sidebarEl.appendChild( stepContainer );

    // Utility helpers
    function deepClone( obj ) {
        return JSON.parse( JSON.stringify( obj || null ) );
    }

    function getTemplateById( id ) {
        for ( var i = 0; i < templates.length; i++ ) {
            if ( templates[ i ].id === id ) {
                return templates[ i ];
            }
        }
        return null;
    }

    function getDefaultLayoutId( tpl ) {
        if ( tpl && tpl.layouts ) {
            if ( tpl.layouts.default ) {
                return 'default';
            }
            var ids = Object.keys( tpl.layouts );
            if ( ids.length ) {
                return ids[0];
            }
        }
        return null;
    }

    function getDefaultCardStyleId( tpl ) {
        if ( tpl && tpl.card_styles ) {
            if ( tpl.card_styles.default ) {
                return 'default';
            }
            var ids = Object.keys( tpl.card_styles );
            if ( ids.length ) {
                return ids[0];
            }
        }
        return null;
    }

    function initialPlansForTemplate( tpl ) {
        var plans = tpl && tpl.defaults && Array.isArray( tpl.defaults.plans ) ? deepClone( tpl.defaults.plans ) : [];
        if ( ! plans.length ) {
            plans = [ defaultPlan() ];
        }
        return plans.map( ensurePlanStructure );
    }

    function defaultPlan( index ) {
        var idx = index || 1;
        return {
            post_title: 'Plan ' + idx,
            post_excerpt: '',
            meta: {
                [ META_KEYS.FEATURED ]: false,
                [ META_KEYS.SPECS ]: [],
                [ META_KEYS.VARIANTS ]: [
                    {
                        period: 'monthly',
                        price: '',
                        sale_price: '',
                        cta_label: '',
                        cta_url: '',
                        target: '_self',
                        unit: '',
                    },
                ],
                [ META_KEYS.BADGES ]: [],
                _pwpl_badge_shadow: 8,
            },
        };
    }

    function ensurePlanStructure( plan, index ) {
        var p = plan || {};
        p.post_title = p.post_title || ( 'Plan ' + ( ( index || 0 ) + 1 ) );
        p.post_excerpt = p.post_excerpt || '';
        p.meta = p.meta && typeof p.meta === 'object' ? p.meta : {};
        if ( ! Array.isArray( p.meta[ META_KEYS.VARIANTS ] ) ) {
            p.meta[ META_KEYS.VARIANTS ] = [ defaultVariant() ];
        }
        if ( ! Array.isArray( p.meta[ META_KEYS.SPECS ] ) ) {
            p.meta[ META_KEYS.SPECS ] = [];
        }
        if ( typeof p.meta[ META_KEYS.FEATURED ] === 'undefined' ) {
            p.meta[ META_KEYS.FEATURED ] = false;
        }
        if ( ! Array.isArray( p.meta[ META_KEYS.BADGES ] ) ) {
            p.meta[ META_KEYS.BADGES ] = [];
        }
        p.hidden = !! p.hidden;
        return p;
    }

    function defaultVariant() {
        return {
            period: 'monthly',
            price: '',
            sale_price: '',
            cta_label: '',
            cta_url: '',
            target: '_self',
            unit: '',
        };
    }

    function getVisiblePlans() {
        return ( state.plans || [] ).filter( function( p ) { return ! p.hidden; } );
    }

    function getCategoryLabel( slug ) {
        if ( ! slug ) {
            return '';
        }
        var key = String( slug ).toLowerCase();
        if ( CATEGORY_LABELS[ key ] ) {
            return CATEGORY_LABELS[ key ];
        }
        return slug.charAt( 0 ).toUpperCase() + slug.slice( 1 );
    }

    function formatTagLabel( slug ) {
        if ( ! slug ) {
            return '';
        }
        return slug.split( '-' ).map( function( part ) {
            if ( ! part.length ) {
                return part;
            }
            return part.charAt( 0 ).toUpperCase() + part.slice( 1 );
        } ).join( ' ' );
    }

    function getTemplateMetadata( tpl ) {
        if ( tpl && tpl.metadata && typeof tpl.metadata === 'object' ) {
            return tpl.metadata;
        }
        return {};
    }

    function getSampleSetsForTemplate() {
        var tpl = getTemplateById( state.selectedTemplateId );
        var meta = getTemplateMetadata( tpl );
        var sets = Array.isArray( meta.sample_sets ) ? meta.sample_sets.slice() : [];
        if ( ! sets.length && meta.sample_specs && meta.sample_specs.length ) {
            sets.push( {
                id: 'default',
                label: ( config.i18n && config.i18n.defaultSampleSet ) || 'Sample features',
                specs: meta.sample_specs,
            } );
        }
        return sets;
    }

    function canResetPlan( index ) {
        return Array.isArray( state.templatePlanDefaults ) && state.templatePlanDefaults[ index ];
    }

    function resetPlanToTemplate( index ) {
        if ( ! canResetPlan( index ) ) {
            return;
        }
        var original = deepClone( state.templatePlanDefaults[ index ] );
        var current = state.plans[ index ];
        if ( ! current || ! original ) {
            return;
        }
        state.plans[ index ] = ensurePlanStructure( original, index );
        openFeatureMenuIndex = null;
        state.editingFeatureIndex = null;
        syncPlanChange();
        renderPlanList();
        renderPlanEditor();
    }

    function applySampleSpecsToPlan( plan, specs ) {
        if ( ! plan || ! plan.meta ) {
            return;
        }
        var sampleSpecs = specs;
        if ( ! Array.isArray( sampleSpecs ) || ! sampleSpecs.length ) {
            var tpl = getTemplateById( state.selectedTemplateId );
            var meta = getTemplateMetadata( tpl );
            sampleSpecs = meta.sample_specs || [];
        }
        if ( ! sampleSpecs.length ) {
            return;
        }
        plan.meta[ META_KEYS.SPECS ] = sampleSpecs.map( function( spec ) {
            var entry = {
                label: spec.label || '',
            };
            if ( spec.value ) {
                entry.value = spec.value;
            }
            if ( spec.icon ) {
                entry.icon = spec.icon;
            }
            return entry;
        } );
        syncPlanChange();
        state.editingFeatureIndex = null;
        openFeatureMenuIndex = null;
        renderPlanEditor();
    }

    function getLayoutTypeLabel( type ) {
        return LAYOUT_TYPE_LABELS[ type ] || type;
    }

    function clearPreviewError() {
        if ( ! previewErrorEl ) {
            return;
        }
        previewErrorEl.textContent = '';
        previewErrorEl.classList.remove( 'is-visible' );
    }

    function showPreviewError( message ) {
        if ( ! previewErrorEl ) {
            return;
        }
        var msg = message || ( config.i18n && config.i18n.previewError ) || 'Unable to load preview. Please try again.';
        previewErrorEl.textContent = msg;
        previewErrorEl.classList.add( 'is-visible' );
    }

    function mapLayoutTypeToLayoutId( tpl, layoutType ) {
        if ( ! tpl || ! tpl.layouts ) {
            return null;
        }
        var layoutIds = Object.keys( tpl.layouts );
        if ( layoutType === 'comparison' ) {
            for ( var i = 0; i < layoutIds.length; i++ ) {
                if ( layoutIds[ i ] === 'comparison' ) {
                    return layoutIds[ i ];
                }
                if ( tpl.layouts[ layoutIds[ i ] ].label && tpl.layouts[ layoutIds[ i ] ].label.toLowerCase().indexOf( 'comparison' ) !== -1 ) {
                    return layoutIds[ i ];
                }
            }
        }
        if ( layoutType === 'grid' || layoutType === 'classic' || layoutType === 'carousel' ) {
            if ( tpl.layouts.default ) {
                return 'default';
            }
        }
        return getDefaultLayoutId( tpl );
    }

    // Rendering
    function renderTemplates() {
        var categoryOrder = [];
        var grouped = {};
        templates.forEach( function( tpl ) {
            var categoryKey = tpl.category || 'uncategorized';
            if ( ! grouped[ categoryKey ] ) {
                grouped[ categoryKey ] = [];
                categoryOrder.push( categoryKey );
            }
            grouped[ categoryKey ].push( tpl );
        } );
        renderCategoryFilters( categoryOrder );

        templatesWrap.innerHTML = '';
        categoryOrder.forEach( function( categoryKey ) {
            var groupWrap = document.createElement( 'div' );
            groupWrap.className = 'pwpl-template-group';
            groupWrap.dataset.category = categoryKey;

            var heading = document.createElement( 'div' );
            heading.className = 'pwpl-template-group__heading';
            heading.textContent = getCategoryLabel( categoryKey );
            groupWrap.appendChild( heading );

            var gridWrap = document.createElement( 'div' );
            gridWrap.className = 'pwpl-template-group__grid';

            grouped[ categoryKey ].forEach( function( tpl ) {
                var btn = document.createElement( 'button' );
                btn.type = 'button';
                btn.className = 'pwpl-template-card';
                btn.dataset.templateId = tpl.id;
                btn.dataset.category = tpl.category || 'uncategorized';

                var check = document.createElement( 'span' );
                check.className = 'pwpl-template-card__check';
                check.setAttribute( 'aria-hidden', 'true' );
                check.innerHTML = '&#10003;';
                btn.appendChild( check );

                var media = document.createElement( 'div' );
                media.className = 'pwpl-template-card__media';
                if ( tpl.thumbnail ) {
                    var img = document.createElement( 'img' );
                    img.src = tpl.thumbnail;
                    img.loading = 'lazy';
                    img.decoding = 'async';
                    img.alt = ( tpl.label || tpl.id ) + ' preview';
                    img.className = 'pwpl-template-card__img';
                    media.appendChild( img );
                } else {
                    var fallbackThumb = buildTemplateThumb( tpl.id );
                    fallbackThumb.classList.add( 'pwpl-template-card__svg' );
                    media.appendChild( fallbackThumb );
                }

                if ( tpl.premium ) {
                    var badge = document.createElement( 'span' );
                    badge.className = 'pwpl-template-card__badge';
                    badge.textContent = ( config.i18n && config.i18n.proLabel ) || 'Pro';
                    media.appendChild( badge );
                }

                btn.appendChild( media );

                var title = document.createElement( 'div' );
                title.className = 'pwpl-template-card__title';
                title.textContent = tpl.label || tpl.id;
                btn.appendChild( title );

                var detailsToggle = document.createElement( 'button' );
                detailsToggle.type = 'button';
                detailsToggle.className = 'pwpl-template-card__details-toggle';
                var toggleLabel = document.createElement( 'span' );
                toggleLabel.className = 'pwpl-template-card__details-label';
                toggleLabel.textContent = ( config.i18n && config.i18n.templateDetailsLink ) || 'Details';
                var toggleIcon = document.createElement( 'span' );
                toggleIcon.className = 'pwpl-template-card__details-icon';
                toggleIcon.setAttribute( 'aria-hidden', 'true' );
                toggleIcon.innerHTML = '&#9662;';
                detailsToggle.appendChild( toggleLabel );
                detailsToggle.appendChild( toggleIcon );
                detailsToggle.addEventListener( 'click', function( event ) {
                    event.stopPropagation();
                    if ( state.selectedTemplateId !== tpl.id ) {
                        selectTemplate( tpl.id );
                    }
                    toggleTemplateDetails( tpl.id );
                } );
                btn.appendChild( detailsToggle );

                var detailsSlot = document.createElement( 'div' );
                detailsSlot.className = 'pwpl-template-card__details-slot';
                btn.appendChild( detailsSlot );

                btn.addEventListener( 'click', function() {
                    selectTemplate( tpl.id );
                } );

                gridWrap.appendChild( btn );
            } );

            groupWrap.appendChild( gridWrap );
            templatesWrap.appendChild( groupWrap );
        } );
        syncTemplateDetailsPanel();
        applyTemplateFilters();
    }

    function renderCategoryFilters( categories ) {
        if ( ! categoryFilter ) {
            return;
        }
        categoryFilter.innerHTML = '';
        if ( ! categories.length ) {
            return;
        }
        if ( state.selectedCategoryFilter && categories.indexOf( state.selectedCategoryFilter ) === -1 ) {
            state.selectedCategoryFilter = '';
        }

        var allBtn = document.createElement( 'button' );
        allBtn.type = 'button';
        allBtn.className = 'pwpl-template-category-btn' + ( state.selectedCategoryFilter ? '' : ' is-active' );
        allBtn.textContent = ( config.i18n && config.i18n.allTemplatesLabel ) || 'All';
        allBtn.addEventListener( 'click', function() {
            state.selectedCategoryFilter = '';
            renderCategoryFilters( categories );
            applyTemplateFilters();
        } );
        categoryFilter.appendChild( allBtn );

        categories.forEach( function( categoryKey ) {
            var btn = document.createElement( 'button' );
            btn.type = 'button';
            btn.className = 'pwpl-template-category-btn';
            if ( state.selectedCategoryFilter === categoryKey ) {
                btn.classList.add( 'is-active' );
            }
            btn.dataset.category = categoryKey;
            btn.textContent = getCategoryLabel( categoryKey );
            btn.addEventListener( 'click', function() {
                if ( state.selectedCategoryFilter === categoryKey ) {
                    state.selectedCategoryFilter = '';
                } else {
                    state.selectedCategoryFilter = categoryKey;
                }
                renderCategoryFilters( categories );
                applyTemplateFilters();
            } );
            categoryFilter.appendChild( btn );
        } );
    }

    function applyTemplateFilters() {
        var cards = templatesWrap.querySelectorAll( '.pwpl-template-card' );
        cards.forEach( function( card ) {
            var matches = true;
            if ( state.selectedCategoryFilter ) {
                var cardCategory = card.dataset.category || 'uncategorized';
                matches = ( cardCategory === state.selectedCategoryFilter );
            }
            card.style.display = matches ? '' : 'none';
        } );
        if ( state.openTemplateDetailsId ) {
            var openCard = templatesWrap.querySelector( '.pwpl-template-card[data-template-id="' + state.openTemplateDetailsId + '"]' );
            if ( ! openCard || openCard.style.display === 'none' ) {
                state.openTemplateDetailsId = null;
                syncTemplateDetailsPanel();
            }
        }
        updateTemplateGroupVisibility();
    }

    function updateTemplateGroupVisibility() {
        var groups = templatesWrap.querySelectorAll( '.pwpl-template-group' );
        groups.forEach( function( group ) {
            var visible = false;
            group.querySelectorAll( '.pwpl-template-card' ).forEach( function( card ) {
                if ( card.style.display !== 'none' ) {
                    visible = true;
                }
            } );
            group.style.display = visible ? '' : 'none';
        } );
    }

    function renderTemplateDetails( tpl ) {
        if ( ! templateDetailsPane ) {
            return;
        }
        templateDetailsPane.innerHTML = '';

        if ( ! tpl ) {
            var placeholder = document.createElement( 'p' );
            placeholder.className = 'pwpl-template-details__placeholder';
            placeholder.textContent = ( config.i18n && config.i18n.templateDetailsHint ) || 'Select a template to see highlights.';
            templateDetailsPane.appendChild( placeholder );
            return;
        }

        var meta = getTemplateMetadata( tpl );

        var heading = document.createElement( 'div' );
        heading.className = 'pwpl-template-details__title';
        heading.textContent = tpl.label || tpl.id;
        templateDetailsPane.appendChild( heading );

        if ( meta.best_for ) {
            var bestFor = document.createElement( 'p' );
            bestFor.className = 'pwpl-template-details__best';
            bestFor.textContent = meta.best_for;
            templateDetailsPane.appendChild( bestFor );
        }

        var facts = document.createElement( 'div' );
        facts.className = 'pwpl-template-details__facts';

        if ( tpl.category ) {
            var catFact = document.createElement( 'span' );
            catFact.className = 'pwpl-template-details__fact';
            catFact.textContent = getCategoryLabel( tpl.category );
            facts.appendChild( catFact );
        }

        var planFact = document.createElement( 'span' );
        planFact.className = 'pwpl-template-details__fact';
        planFact.textContent = ( meta.plan_count || 3 ) + ' ' + ( meta.plan_count === 1 ? 'plan' : 'plans' );
        facts.appendChild( planFact );

        if ( tpl.layout_type ) {
            var layoutFact = document.createElement( 'span' );
            layoutFact.className = 'pwpl-template-details__fact';
            layoutFact.textContent = getLayoutTypeLabel( tpl.layout_type );
            facts.appendChild( layoutFact );
        }

        if ( meta.supports_hero ) {
            var heroFact = document.createElement( 'span' );
            heroFact.className = 'pwpl-template-details__fact';
            heroFact.textContent = 'Hero images';
            facts.appendChild( heroFact );
        }

        templateDetailsPane.appendChild( facts );

        if ( meta.highlights && meta.highlights.length ) {
            var highlightSection = document.createElement( 'div' );
            highlightSection.className = 'pwpl-template-details__section';
            var highlightTitle = document.createElement( 'div' );
            highlightTitle.className = 'pwpl-template-details__section-title';
            highlightTitle.textContent = 'Highlights';
            highlightSection.appendChild( highlightTitle );
            var highlightList = document.createElement( 'ul' );
            highlightList.className = 'pwpl-template-details__list';
            meta.highlights.forEach( function( text ) {
                var item = document.createElement( 'li' );
                item.textContent = text;
                highlightList.appendChild( item );
            } );
            highlightSection.appendChild( highlightList );
            templateDetailsPane.appendChild( highlightSection );
        }

        if ( meta.sample_specs && meta.sample_specs.length ) {
            var sampleSection = document.createElement( 'div' );
            sampleSection.className = 'pwpl-template-details__section';
            var sampleTitle = document.createElement( 'div' );
            sampleTitle.className = 'pwpl-template-details__section-title';
            sampleTitle.textContent = 'Sample features';
            sampleSection.appendChild( sampleTitle );
            var sampleList = document.createElement( 'ul' );
            sampleList.className = 'pwpl-template-details__list';
            meta.sample_specs.forEach( function( spec ) {
                var item = document.createElement( 'li' );
                if ( spec.label && spec.value ) {
                    item.textContent = spec.label + ': ' + spec.value;
                } else {
                    item.textContent = spec.label || spec.value || '';
                }
                sampleList.appendChild( item );
            } );
            sampleSection.appendChild( sampleList );
            templateDetailsPane.appendChild( sampleSection );
        }
    }

    function syncTemplateDetailsPanel() {
        if ( ! templateDetailsPane ) {
            return;
        }
        templatesWrap.querySelectorAll( '.pwpl-template-card.is-details-open' ).forEach( function( card ) {
            card.classList.remove( 'is-details-open' );
        } );
        if ( templateDetailsPane.parentNode ) {
            templateDetailsPane.parentNode.classList.remove( 'is-active' );
            templateDetailsPane.parentNode.removeChild( templateDetailsPane );
        }

        var activeId = state.openTemplateDetailsId;
        if ( ! activeId ) {
            renderTemplateDetails( null );
            return;
        }

        var selectedCard = templatesWrap.querySelector( '.pwpl-template-card[data-template-id="' + activeId + '"]' );
        if ( ! selectedCard ) {
            state.openTemplateDetailsId = null;
            renderTemplateDetails( null );
            return;
        }

        var slot = selectedCard.querySelector( '.pwpl-template-card__details-slot' );
        if ( ! slot ) {
            state.openTemplateDetailsId = null;
            renderTemplateDetails( null );
            return;
        }
        selectedCard.classList.add( 'is-details-open' );
        slot.classList.add( 'is-active' );
        slot.appendChild( templateDetailsPane );
        renderTemplateDetails( getTemplateById( activeId ) );
    }

    function toggleTemplateDetails( templateId ) {
        if ( state.openTemplateDetailsId === templateId ) {
            state.openTemplateDetailsId = null;
        } else {
            state.openTemplateDetailsId = templateId;
        }
        syncTemplateDetailsPanel();
    }

    function renderLayoutTypes() {
        layoutTypeList.innerHTML = '';
        Object.keys( LAYOUT_TYPE_LABELS ).forEach( function( typeKey ) {
            var btn = document.createElement( 'button' );
            btn.type = 'button';
            btn.className = 'pwpl-layout-type';
            btn.dataset.layoutType = typeKey;
            btn.textContent = getLayoutTypeLabel( typeKey );
            if ( state.layoutType === typeKey ) {
                btn.classList.add( 'is-selected' );
            }
            btn.addEventListener( 'click', function() {
                state.layoutType = typeKey;
                var tpl = getTemplateById( state.selectedTemplateId );
                state.selectedLayoutId = mapLayoutTypeToLayoutId( tpl, typeKey );
                renderLayoutTypes();
                renderCardStyles();
                loadPreview();
                renderSummary();
            } );
            layoutTypeList.appendChild( btn );
        } );
    }

    function renderCardStyles() {
        cardStyleList.innerHTML = '';
        var tpl = getTemplateById( state.selectedTemplateId );
        if ( ! tpl ) {
            return;
        }

        var styles = tpl.card_styles || {};
        var styleIds = Object.keys( styles );
        if ( styleIds.length ) {
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
    }

    function renderPlanList() {
        planColumnsList.innerHTML = '';
        ( state.plans || [] ).forEach( function( plan, index ) {
            var row = document.createElement( 'div' );
            row.className = 'pwpl-plan-row';
            if ( plan.hidden ) {
                row.classList.add( 'is-hidden' );
            }
            if ( state.activePlanIndex === index && state.editingPlanIndex === null ) {
                row.classList.add( 'is-active' );
            }

            var textWrap = document.createElement( 'div' );
            textWrap.className = 'pwpl-plan-row__text';

            var title = document.createElement( 'div' );
            title.className = 'pwpl-plan-row__title';
            title.textContent = plan.post_title || ( 'Plan ' + ( index + 1 ) );
            textWrap.appendChild( title );

            if ( plan.post_excerpt ) {
                var subtitle = document.createElement( 'div' );
                subtitle.className = 'pwpl-plan-row__subtitle';
                subtitle.textContent = plan.post_excerpt;
                textWrap.appendChild( subtitle );
            }

            row.appendChild( textWrap );

            var menuWrap = document.createElement( 'div' );
            menuWrap.className = 'pwpl-plan-row__menu-wrap';

            var menuToggle = document.createElement( 'button' );
            menuToggle.type = 'button';
            menuToggle.className = 'pwpl-plan-row__menu-toggle';
            menuToggle.textContent = '⋯';
            menuToggle.addEventListener( 'click', function( evt ) {
                evt.stopPropagation();
                openMenuIndex = openMenuIndex === index ? null : index;
                renderPlanList();
            } );
            menuWrap.appendChild( menuToggle );

            if ( openMenuIndex === index ) {
                var menu = document.createElement( 'div' );
                menu.className = 'pwpl-plan-row__menu';

                var editBtn = document.createElement( 'button' );
                editBtn.type = 'button';
                editBtn.textContent = ( config.i18n && config.i18n.edit ) || 'Edit';
                editBtn.addEventListener( 'click', function( evt ) {
                    evt.stopPropagation();
                    openPlanEditor( index );
                } );
                menu.appendChild( editBtn );

                var dupBtn = document.createElement( 'button' );
                dupBtn.type = 'button';
                dupBtn.textContent = ( config.i18n && config.i18n.duplicate ) || 'Duplicate';
                dupBtn.addEventListener( 'click', function( evt ) {
                    evt.stopPropagation();
                    duplicatePlan( index );
                } );
                menu.appendChild( dupBtn );

                var hideBtn = document.createElement( 'button' );
                hideBtn.type = 'button';
                hideBtn.textContent = plan.hidden ? ( ( config.i18n && config.i18n.unhide ) || 'Unhide' ) : ( ( config.i18n && config.i18n.hide ) || 'Hide' );
                hideBtn.addEventListener( 'click', function( evt ) {
                    evt.stopPropagation();
                    toggleHidePlan( index );
                } );
                menu.appendChild( hideBtn );

                var delBtn = document.createElement( 'button' );
                delBtn.type = 'button';
                delBtn.textContent = ( config.i18n && config.i18n.deleteLabel ) || 'Delete';
                delBtn.className = 'is-danger';
                delBtn.addEventListener( 'click', function( evt ) {
                    evt.stopPropagation();
                    deletePlan( index );
                } );
                menu.appendChild( delBtn );

                menuWrap.appendChild( menu );
            }

            row.appendChild( menuWrap );

            row.addEventListener( 'click', function() {
                state.activePlanIndex = index;
                state.editingPlanIndex = null;
                renderPlanList();
                renderPlanEditor();
            } );

            planColumnsList.appendChild( row );
        } );

        addColumnBtn.disabled = ( state.plans || [] ).length >= MAX_PLAN_COUNT;
    }

    function renderPlanEditor() {
        planEditWrap.innerHTML = '';
        if ( state.editingPlanIndex === null ) {
            planEditWrap.classList.remove( 'is-visible' );
            planListWrap.classList.add( 'is-visible' );
            return;
        }
        planListWrap.classList.remove( 'is-visible' );
        planEditWrap.classList.add( 'is-visible' );

        var plan = state.plans[ state.editingPlanIndex ];
        if ( ! plan ) {
            return;
        }

        if ( state.editingFeatureIndex !== null ) {
            renderFeatureEditor( plan );
            return;
        }

        var header = document.createElement( 'div' );
        header.className = 'pwpl-plan-edit__header';

        var backBtn = document.createElement( 'button' );
        backBtn.type = 'button';
        backBtn.className = 'pwpl-plan-edit__back';
        backBtn.textContent = ( config.i18n && config.i18n.back ) || 'Back';
        backBtn.addEventListener( 'click', function() {
            state.editingPlanIndex = null;
            renderPlanEditor();
        } );
        var title = document.createElement( 'div' );
        title.className = 'pwpl-plan-edit__title';
        title.textContent = ( config.i18n && config.i18n.editColumn ) || 'Edit Column';

        var resetBtn = document.createElement( 'button' );
        resetBtn.type = 'button';
        resetBtn.className = 'pwpl-plan-reset';
        resetBtn.textContent = ( config.i18n && config.i18n.resetPlan ) || 'Reset to template';
        resetBtn.addEventListener( 'click', function() {
            resetPlanToTemplate( state.editingPlanIndex );
        } );

        header.appendChild( backBtn );
        header.appendChild( title );
        if ( canResetPlan( state.editingPlanIndex ) ) {
            header.appendChild( resetBtn );
        }
        planEditWrap.appendChild( header );

        // Basics
        var basics = document.createElement( 'div' );
        basics.className = 'pwpl-plan-edit__section';
        var basicsTitle = document.createElement( 'div' );
        basicsTitle.className = 'pwpl-plan-edit__section-title';
        basicsTitle.textContent = ( config.i18n && config.i18n.basics ) || 'Basics';
        basics.appendChild( basicsTitle );

        basics.appendChild( labeledInput( ( config.i18n && config.i18n.planTitle ) || 'Title', plan.post_title, function( val ) {
            plan.post_title = val;
            syncPlanChange();
        } ) );
        basics.appendChild( labeledInput( ( config.i18n && config.i18n.planSubtitle ) || 'Caption', plan.post_excerpt, function( val ) {
            plan.post_excerpt = val;
            syncPlanChange();
        } ) );
        basics.appendChild( labeledInput( ( config.i18n && config.i18n.highlightLabel ) || 'Highlight label', getHighlightLabel( plan ), function( val ) {
            setHighlightLabel( plan, val );
            syncPlanChange();
        } ) );

        var featToggle = labeledToggle( ( config.i18n && config.i18n.featured ) || 'Featured', !! plan.meta[ META_KEYS.FEATURED ] );
        featToggle.querySelector( 'input' ).addEventListener( 'change', function( evt ) {
            plan.meta[ META_KEYS.FEATURED ] = evt.target.checked;
            syncPlanChange();
        } );
        basics.appendChild( featToggle );

        planEditWrap.appendChild( basics );

        // Features
        var features = document.createElement( 'div' );
        features.className = 'pwpl-plan-edit__section';
        var featuresHeader = document.createElement( 'div' );
        featuresHeader.className = 'pwpl-plan-edit__section-header';
        var featuresTitle = document.createElement( 'div' );
        featuresTitle.className = 'pwpl-plan-edit__section-title';
        featuresTitle.textContent = ( config.i18n && config.i18n.features ) || 'Features';
        featuresHeader.appendChild( featuresTitle );

        var sampleSets = getSampleSetsForTemplate();
        if ( sampleSets.length ) {
            var sampleSelect = document.createElement( 'select' );
            sampleSelect.className = 'pwpl-sample-select';
            var defaultOption = document.createElement( 'option' );
            defaultOption.value = '';
            defaultOption.textContent = ( config.i18n && config.i18n.useSampleFeatures ) || 'Apply sample data';
            sampleSelect.appendChild( defaultOption );
            sampleSets.forEach( function( set ) {
                var opt = document.createElement( 'option' );
                opt.value = set.id;
                opt.textContent = set.label || set.id;
                sampleSelect.appendChild( opt );
            } );
            sampleSelect.addEventListener( 'change', function() {
                var selected = sampleSets.find( function( set ) { return set.id === sampleSelect.value; } );
                if ( selected ) {
                    applySampleSpecsToPlan( plan, selected.specs );
                    applySampleExtrasToPlan( plan, selected );
                }
                sampleSelect.value = '';
            } );
            featuresHeader.appendChild( sampleSelect );
        }

        features.appendChild( featuresHeader );

        var specs = Array.isArray( plan.meta[ META_KEYS.SPECS ] ) ? plan.meta[ META_KEYS.SPECS ] : [];
        specs.forEach( function( spec, idx ) {
            var row = document.createElement( 'div' );
            row.className = 'pwpl-feature-row';

            row.draggable = true;
            row.dataset.featureIndex = idx;
            row.addEventListener( 'dragstart', function( evt ) {
                draggingFeatureIndex = idx;
                evt.dataTransfer.effectAllowed = 'move';
            } );
            row.addEventListener( 'dragend', function() {
                draggingFeatureIndex = null;
                row.classList.remove( 'is-drag-over' );
            } );
            row.addEventListener( 'dragover', function( evt ) {
                evt.preventDefault();
                evt.dataTransfer.dropEffect = 'move';
                row.classList.add( 'is-drag-over' );
            } );
            row.addEventListener( 'dragleave', function() {
                row.classList.remove( 'is-drag-over' );
            } );
            row.addEventListener( 'drop', function( evt ) {
                evt.preventDefault();
                row.classList.remove( 'is-drag-over' );
                if ( draggingFeatureIndex === null ) {
                    return;
                }
                moveFeature( plan, draggingFeatureIndex, idx );
                draggingFeatureIndex = null;
            } );

            var textWrap = document.createElement( 'div' );
            textWrap.className = 'pwpl-feature-row__text';

            var dragHandle = document.createElement( 'span' );
            dragHandle.className = 'pwpl-feature-row__drag';
            dragHandle.textContent = '≡';
            textWrap.appendChild( dragHandle );

            var labelEl = document.createElement( 'div' );
            labelEl.className = 'pwpl-feature-row__label';
            labelEl.textContent = spec.label || ( ( config.i18n && config.i18n.featurePlaceholder ) || 'Feature' );
            textWrap.appendChild( labelEl );
            var valueEl = document.createElement( 'div' );
            valueEl.className = 'pwpl-feature-row__value';
            valueEl.textContent = spec.value || '';
            textWrap.appendChild( valueEl );
            row.appendChild( textWrap );

            var actions = document.createElement( 'div' );
            actions.className = 'pwpl-feature-row__actions';

            var menuToggle = document.createElement( 'button' );
            menuToggle.type = 'button';
            menuToggle.className = 'pwpl-feature-row__menu-toggle';
            menuToggle.textContent = '⋯';
            menuToggle.addEventListener( 'click', function( evt ) {
                evt.stopPropagation();
                openFeatureMenuIndex = openFeatureMenuIndex === idx ? null : idx;
                renderPlanEditor();
            } );
            actions.appendChild( menuToggle );

            if ( openFeatureMenuIndex === idx ) {
                var menu = document.createElement( 'div' );
                menu.className = 'pwpl-feature-row__menu';

                var editBtn = document.createElement( 'button' );
                editBtn.type = 'button';
                editBtn.textContent = ( config.i18n && config.i18n.edit ) || 'Edit';
                editBtn.addEventListener( 'click', function( evt ) {
                    evt.stopPropagation();
                    state.editingFeatureIndex = idx;
                    openFeatureMenuIndex = null;
                    renderPlanEditor();
                } );
                menu.appendChild( editBtn );

                var dupBtn = document.createElement( 'button' );
                dupBtn.type = 'button';
                dupBtn.textContent = ( config.i18n && config.i18n.duplicate ) || 'Duplicate';
                dupBtn.addEventListener( 'click', function( evt ) {
                    evt.stopPropagation();
                    plan.meta[ META_KEYS.SPECS ].splice( idx + 1, 0, { label: spec.label || '', value: spec.value || '' } );
                    syncPlanChange();
                    openFeatureMenuIndex = null;
                    state.editingFeatureIndex = null;
                    renderPlanEditor();
                } );
                menu.appendChild( dupBtn );

                var moveUpBtn = document.createElement( 'button' );
                moveUpBtn.type = 'button';
                moveUpBtn.textContent = ( config.i18n && config.i18n.moveUp ) || 'Move up';
                moveUpBtn.addEventListener( 'click', function( evt ) {
                    evt.stopPropagation();
                    moveFeature( plan, idx, idx - 1 );
                    openFeatureMenuIndex = null;
                } );
                menu.appendChild( moveUpBtn );

                var moveDownBtn = document.createElement( 'button' );
                moveDownBtn.type = 'button';
                moveDownBtn.textContent = ( config.i18n && config.i18n.moveDown ) || 'Move down';
                moveDownBtn.addEventListener( 'click', function( evt ) {
                    evt.stopPropagation();
                    moveFeature( plan, idx, idx + 1 );
                    openFeatureMenuIndex = null;
                } );
                menu.appendChild( moveDownBtn );

                var delBtn = document.createElement( 'button' );
                delBtn.type = 'button';
                delBtn.textContent = ( config.i18n && config.i18n.deleteLabel ) || 'Delete';
                delBtn.className = 'is-danger';
                delBtn.addEventListener( 'click', function( evt ) {
                    evt.stopPropagation();
                    plan.meta[ META_KEYS.SPECS ].splice( idx, 1 );
                    syncPlanChange();
                    openFeatureMenuIndex = null;
                    renderPlanEditor();
                } );
                menu.appendChild( delBtn );

                actions.appendChild( menu );
            }

            row.appendChild( actions );
            features.appendChild( row );
        } );

        var addFeature = document.createElement( 'button' );
        addFeature.type = 'button';
        addFeature.className = 'button pwpl-add-feature';
        addFeature.textContent = ( config.i18n && config.i18n.addFeature ) || 'Add Feature';
        addFeature.addEventListener( 'click', function() {
            var newIndex = plan.meta[ META_KEYS.SPECS ].length;
            plan.meta[ META_KEYS.SPECS ].push( { label: '', value: '' } );
            syncPlanChange();
            openFeatureMenuIndex = null;
            state.editingFeatureIndex = newIndex;
            renderPlanEditor();
        } );
        features.appendChild( addFeature );

        planEditWrap.appendChild( features );

        // Price
        var price = document.createElement( 'div' );
        price.className = 'pwpl-plan-edit__section';
        var priceTitle = document.createElement( 'div' );
        priceTitle.className = 'pwpl-plan-edit__section-title';
        priceTitle.textContent = ( config.i18n && config.i18n.price ) || 'Price';
        price.appendChild( priceTitle );

        var variant = firstVariant( plan );

        price.appendChild( labeledInput( ( config.i18n && config.i18n.priceLabel ) || 'Price', variant.price || '', function( val ) {
            variant.price = val;
            syncPlanChange();
        }, 'number' ) );
        price.appendChild( labeledInput( ( config.i18n && config.i18n.salePriceLabel ) || 'Old Price / Sale Price', variant.sale_price || '', function( val ) {
            variant.sale_price = val;
            syncPlanChange();
        }, 'number' ) );

        planEditWrap.appendChild( price );

        // Button
        var buttonSection = document.createElement( 'div' );
        buttonSection.className = 'pwpl-plan-edit__section';
        var buttonTitle = document.createElement( 'div' );
        buttonTitle.className = 'pwpl-plan-edit__section-title';
        buttonTitle.textContent = ( config.i18n && config.i18n.button ) || 'Button';
        buttonSection.appendChild( buttonTitle );

        buttonSection.appendChild( labeledInput( ( config.i18n && config.i18n.buttonText ) || 'Text', variant.cta_label || '', function( val ) {
            variant.cta_label = val;
            syncPlanChange();
        } ) );
        buttonSection.appendChild( labeledInput( ( config.i18n && config.i18n.buttonUrl ) || 'Link', variant.cta_url || '', function( val ) {
            variant.cta_url = val;
            syncPlanChange();
        }, 'url' ) );

        planEditWrap.appendChild( buttonSection );
    }

    function labeledInput( label, value, onChange, type ) {
        var wrap = document.createElement( 'label' );
        wrap.className = 'pwpl-labeled-input';
        var lbl = document.createElement( 'div' );
        lbl.className = 'pwpl-labeled-input__label';
        lbl.textContent = label;
        var input = document.createElement( 'input' );
        input.type = type || 'text';
        input.className = 'pwpl-labeled-input__input';
        input.value = value || '';
        input.addEventListener( 'input', function( evt ) {
            onChange( evt.target.value );
        } );
        wrap.appendChild( lbl );
        wrap.appendChild( input );
        return wrap;
    }

    function labeledToggle( label, checked ) {
        var wrap = document.createElement( 'label' );
        wrap.className = 'pwpl-labeled-toggle';
        var text = document.createElement( 'span' );
        text.textContent = label;
        var input = document.createElement( 'input' );
        input.type = 'checkbox';
        input.checked = checked;
        wrap.appendChild( text );
        wrap.appendChild( input );
        return wrap;
    }

    function getHighlightLabel( plan ) {
        var badges = plan.meta[ META_KEYS.BADGES ] || [];
        if ( Array.isArray( badges ) && badges[0] && badges[0].label ) {
            return badges[0].label;
        }
        return '';
    }

    function setHighlightLabel( plan, label ) {
        var badges = plan.meta[ META_KEYS.BADGES ] || [];
        if ( ! Array.isArray( badges ) ) {
            badges = [];
        }
        if ( ! badges[0] ) {
            badges[0] = {};
        }
        badges[0].label = label;
        badges[0].slug = badges[0].slug || 'highlight';
        badges[0].color = badges[0].color || '#2563eb';
        plan.meta[ META_KEYS.BADGES ] = badges;
    }

    function firstVariant( plan ) {
        if ( ! plan.meta[ META_KEYS.VARIANTS ] || ! Array.isArray( plan.meta[ META_KEYS.VARIANTS ] ) || ! plan.meta[ META_KEYS.VARIANTS ].length ) {
            plan.meta[ META_KEYS.VARIANTS ] = [ defaultVariant() ];
        }
        return plan.meta[ META_KEYS.VARIANTS ][0];
    }

    function syncPlanChange() {
        renderPlanList();
        renderSummary();
        loadPreview();
    }

    function renderFeatureEditor( plan ) {
        planEditWrap.innerHTML = '';
        var featureIdx = state.editingFeatureIndex;
        var spec = plan.meta[ META_KEYS.SPECS ][ featureIdx ] || { label: '', value: '' };

        var header = document.createElement( 'div' );
        header.className = 'pwpl-plan-edit__header';

        var backBtn = document.createElement( 'button' );
        backBtn.type = 'button';
        backBtn.className = 'pwpl-plan-edit__back';
        backBtn.textContent = ( config.i18n && config.i18n.back ) || 'Back';
        backBtn.addEventListener( 'click', function() {
            state.editingFeatureIndex = null;
            renderPlanEditor();
        } );
        var title = document.createElement( 'div' );
        title.className = 'pwpl-plan-edit__title';
        title.textContent = ( config.i18n && config.i18n.editFeature ) || 'Edit Feature';

        header.appendChild( backBtn );
        header.appendChild( title );
        planEditWrap.appendChild( header );

        var section = document.createElement( 'div' );
        section.className = 'pwpl-plan-edit__section';

        var textLabel = document.createElement( 'div' );
        textLabel.className = 'pwpl-plan-edit__section-title';
        textLabel.textContent = ( config.i18n && config.i18n.featureLabel ) || 'Text';
        section.appendChild( textLabel );

        section.appendChild( labeledInput( '', spec.label || '', function( val ) {
            plan.meta[ META_KEYS.SPECS ][ featureIdx ].label = val;
            syncPlanChange();
        } ) );

        var valueLabel = document.createElement( 'div' );
        valueLabel.className = 'pwpl-plan-edit__section-title';
        valueLabel.textContent = ( config.i18n && config.i18n.featureValue ) || 'Value';
        section.appendChild( valueLabel );

        section.appendChild( labeledInput( '', spec.value || '', function( val ) {
            plan.meta[ META_KEYS.SPECS ][ featureIdx ].value = val;
            syncPlanChange();
        } ) );

        planEditWrap.appendChild( section );

        var actions = document.createElement( 'div' );
        actions.className = 'pwpl-step-actions';
        var saveBtn = document.createElement( 'button' );
        saveBtn.type = 'button';
        saveBtn.className = 'button button-primary';
        saveBtn.textContent = ( config.i18n && config.i18n.saveFeature ) || 'Save feature';
        saveBtn.addEventListener( 'click', function() {
            state.editingFeatureIndex = null;
            renderPlanEditor();
        } );
        actions.appendChild( saveBtn );
        planEditWrap.appendChild( actions );
    }

    // Selection handlers
    function selectTemplate( templateId ) {
        if ( ! templateId ) {
            return;
        }
        var tpl = getTemplateById( templateId );
        if ( ! tpl ) {
            return;
        }
        state.selectedTemplateId = templateId;
        state.selectedLayoutId = getDefaultLayoutId( tpl );
        state.selectedCardStyleId = getDefaultCardStyleId( tpl );
        state.layoutType = 'grid';
        state.plans = initialPlansForTemplate( tpl );
        state.templatePlanDefaults = deepClone( state.plans );
        state.activePlanIndex = 0;
        state.editingPlanIndex = null;
        state.dimensions = {
            platform: true,
            period: true,
            location: false,
        };
        if ( nameInput ) {
            nameInput.value = tpl.label || templateId;
        }

        Array.prototype.forEach.call( templatesWrap.querySelectorAll( '.pwpl-template-card' ), function( card ) {
            card.classList.toggle( 'is-selected', card.dataset.templateId === templateId );
        } );
        syncTemplateDetailsPanel();

        step1Next.disabled = false;
        renderLayoutTypes();
        renderCardStyles();
        renderPlanList();
        renderPlanEditor();
        updateDimToggles();
        renderSummary();
        loadPreview();
    }

    function selectCardStyle( styleId ) {
        state.selectedCardStyleId = styleId || null;
        Array.prototype.forEach.call( cardStyleList.querySelectorAll( '.pwpl-card-style-tile' ), function( tile ) {
            tile.classList.toggle( 'is-selected', tile.dataset.cardStyleId === styleId );
        } );
        loadPreview();
    }

    function addPlan( options ) {
        var nextIndex = ( state.plans || [] ).length + 1;
        if ( nextIndex > MAX_PLAN_COUNT ) {
            return;
        }
        var source = state.plans.length ? state.plans[ state.plans.length - 1 ] : defaultPlan( nextIndex );
        var clone = deepClone( source );
        clone.post_title = clone.post_title ? clone.post_title + ' ' + nextIndex : 'Plan ' + nextIndex;
        clone.hidden = false;
        clone = ensurePlanStructure( clone, nextIndex - 1 );
        state.plans.push( clone );
        state.templatePlanDefaults.push( deepClone( clone ) );
        state.activePlanIndex = state.plans.length - 1;
        state.editingPlanIndex = options && options.openEditor ? state.activePlanIndex : null;
        openMenuIndex = null;
        syncPlanChange();
        if ( options && options.openEditor ) {
            renderPlanEditor();
        }
    }

    function duplicatePlan( index ) {
        if ( state.plans.length >= MAX_PLAN_COUNT ) {
            return;
        }
        var base = state.plans[ index ];
        if ( ! base ) {
            return;
        }
        var clone = ensurePlanStructure( deepClone( base ), state.plans.length );
        clone.post_title = clone.post_title ? clone.post_title + ' copy' : 'Plan ' + ( state.plans.length + 1 );
        state.plans.splice( index + 1, 0, clone );
        state.templatePlanDefaults.splice( index + 1, 0, deepClone( clone ) );
        state.activePlanIndex = index + 1;
        state.editingPlanIndex = null;
        openMenuIndex = null;
        syncPlanChange();
    }

    function toggleHidePlan( index ) {
        var plan = state.plans[ index ];
        if ( ! plan ) {
            return;
        }
        plan.hidden = ! plan.hidden;
        openMenuIndex = null;
        syncPlanChange();
    }

    function deletePlan( index ) {
        if ( state.plans.length <= MIN_PLAN_COUNT ) {
            return;
        }
        state.plans.splice( index, 1 );
        state.templatePlanDefaults.splice( index, 1 );
        if ( state.activePlanIndex >= state.plans.length ) {
            state.activePlanIndex = state.plans.length - 1;
        }
        state.editingPlanIndex = null;
        openMenuIndex = null;
        syncPlanChange();
    }

    function openPlanEditor( index ) {
        state.activePlanIndex = index;
        state.editingPlanIndex = index;
        renderPlanList();
        renderPlanEditor();
    }

    // Preview + REST
    function buildPlansPayload() {
        var visible = getVisiblePlans();
        if ( ! visible.length && state.plans.length ) {
            visible = [ state.plans[0] ];
        }
        return visible.map( function( plan ) {
            var p = {
                post_title: plan.post_title || '',
                post_excerpt: plan.post_excerpt || '',
                meta: {},
            };
            p.meta[ META_KEYS.FEATURED ] = !! plan.meta[ META_KEYS.FEATURED ];
            p.meta[ META_KEYS.SPECS ] = Array.isArray( plan.meta[ META_KEYS.SPECS ] ) ? plan.meta[ META_KEYS.SPECS ].map( function( spec ) {
                return { label: spec.label || '', value: spec.value || '' };
            } ) : [];
            var variants = Array.isArray( plan.meta[ META_KEYS.VARIANTS ] ) ? deepClone( plan.meta[ META_KEYS.VARIANTS ] ) : [];
            if ( ! variants.length ) {
                variants = [ defaultVariant() ];
            }
            variants[0] = Object.assign( {}, defaultVariant(), variants[0] );
            p.meta[ META_KEYS.VARIANTS ] = variants;
            if ( Array.isArray( plan.meta[ META_KEYS.BADGES ] ) ) {
                p.meta[ META_KEYS.BADGES ] = plan.meta[ META_KEYS.BADGES ].map( function( badge ) {
                    return {
                        label: badge.label || '',
                        slug: badge.slug || '',
                        color: badge.color || '',
                    };
                } );
            }
            return p;
        } );
    }

    function loadPreview() {
        if ( ! state.selectedTemplateId ) {
            return;
        }

        clearPreviewError();
        previewEl.classList.add( 'is-loading' );
        sidebarEl.classList.add( 'is-loading' );

        var apiFetch = window.wp && wp.apiFetch ? wp.apiFetch : null;
        var plansPayload = buildPlansPayload();
        if ( apiFetch && config.rest && config.rest.previewUrl ) {
            apiFetch( {
                path: config.rest.previewUrl.replace( restUrlRoot(), '' ),
                method: 'POST',
                headers: { 'X-WP-Nonce': config.rest.nonce },
                data: {
                    template_id: state.selectedTemplateId,
                    layout_id: state.selectedLayoutId || '',
                    card_style_id: state.selectedCardStyleId || '',
                    plan_count: plansPayload.length || '',
                    plans_override: plansPayload,
                },
            } ).catch( function( err ) {
                // eslint-disable-next-line no-console
                console.error( 'Preview fetch failed', err );
                showPreviewError( ( config.i18n && config.i18n.previewError ) || 'Unable to load preview. Please try again.' );
            } );
        }

        var frameUrl = buildPreviewFrameUrl( state.selectedTemplateId, state.selectedLayoutId, state.selectedCardStyleId, plansPayload );
        iframeEl.src = frameUrl;
        iframeEl.onload = function() {
            previewEl.classList.remove( 'is-loading' );
            sidebarEl.classList.remove( 'is-loading' );
            clearPreviewError();
        };
    }

    function restUrlRoot() {
        if ( config.rest && config.rest.root ) {
            return config.rest.root.replace( /\/+$/, '' ) + '/';
        }
        return '/wp-json/';
    }

    function buildPreviewFrameUrl( templateId, layoutId, cardStyleId, plansPayload ) {
        var base = config.previewFrame && config.previewFrame.url ? config.previewFrame.url : '';
        var plansJson = '';
        try {
            plansJson = JSON.stringify( plansPayload );
        } catch (e) {
            plansJson = '';
        }
        try {
            var url = new URL( base, window.location.origin );
            url.searchParams.set( 'template_id', templateId );
            if ( layoutId ) {
                url.searchParams.set( 'layout_id', layoutId );
            }
            if ( cardStyleId ) {
                url.searchParams.set( 'card_style_id', cardStyleId );
            }
            if ( plansJson ) {
                url.searchParams.set( 'plans_override', plansJson );
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
            if ( plansJson ) {
                qs += '&plans_override=' + encodeURIComponent( plansJson );
            }
            return base + ( base.indexOf( '?' ) === -1 ? '?' : '&' ) + qs;
        }
    }

    // Step management
    function goToStep( step ) {
        state.step = step;
        updateSteps();
    }

    function updateSteps() {
        stepTemplate.classList.toggle( 'is-active', state.step >= 1 );
        stepLayout.classList.toggle( 'is-active', state.step >= 2 );
        stepCardStyle.classList.toggle( 'is-active', state.step >= 3 );

        step1Wrap.style.display = state.step === 1 ? 'block' : 'none';
        step2Wrap.style.display = state.step === 2 ? 'block' : 'none';
        step3Wrap.style.display = state.step === 3 ? 'block' : 'none';
    }

    function updateDimToggles() {
        var toggles = dimGroup ? dimGroup.querySelectorAll( '.pwpl-dim-toggle' ) : [];
        Array.prototype.forEach.call( toggles, function( btn ) {
            var dim = btn.dataset.dim;
            var isOn = !! state.dimensions[ dim ];
            btn.classList.toggle( 'is-on', isOn );
            btn.setAttribute( 'aria-pressed', isOn ? 'true' : 'false' );
        } );
    }

    // Summary
    function renderSummary() {
        var tpl = getTemplateById( state.selectedTemplateId );
        summaryList.innerHTML = '';
        var columnsCount = getVisiblePlans().length || 1;
        var items = [
            { label: ( config.i18n && config.i18n.summaryTemplate ) || 'Template', value: tpl ? tpl.label : '' },
            { label: ( config.i18n && config.i18n.summaryLayout ) || 'Layout', value: getLayoutTypeLabel( state.layoutType ) },
            { label: ( config.i18n && config.i18n.summaryColumns ) || 'Plan columns', value: columnsCount.toString() },
            { label: ( config.i18n && config.i18n.summaryDimensions ) || 'Dimensions', value: summaryDimensionsText() },
        ];
        items.forEach( function( item ) {
            var row = document.createElement( 'div' );
            row.className = 'pwpl-summary__row';
            var lbl = document.createElement( 'span' );
            lbl.className = 'pwpl-summary__label';
            lbl.textContent = item.label;
            var val = document.createElement( 'span' );
            val.className = 'pwpl-summary__value';
            val.textContent = item.value;
            row.appendChild( lbl );
            row.appendChild( val );
            summaryList.appendChild( row );
        } );
    }

    function summaryDimensionsText() {
        var enabled = [];
        Object.keys( state.dimensions ).forEach( function( dimKey ) {
            if ( state.dimensions[ dimKey ] ) {
                enabled.push( dimKey.charAt( 0 ).toUpperCase() + dimKey.slice( 1 ) );
            }
        } );
        return enabled.length ? enabled.join( ', ' ) : ( config.i18n && config.i18n.noneLabel ) || 'None';
    }

    // Events
    step1Next.addEventListener( 'click', function() {
        if ( ! state.selectedTemplateId ) {
            return;
        }
        goToStep( 2 );
    } );

    step2Back.addEventListener( 'click', function() {
        goToStep( 1 );
    } );

    step2Next.addEventListener( 'click', function() {
        renderSummary();
        goToStep( 3 );
    } );

    step3Back.addEventListener( 'click', function() {
        goToStep( 2 );
    } );

    addColumnBtn.addEventListener( 'click', function() {
        addPlan( { openEditor: true } );
    } );

    document.addEventListener( 'click', function( evt ) {
        if ( ! evt.target.closest( '.pwpl-plan-row__menu-wrap' ) ) {
            if ( openMenuIndex !== null ) {
                openMenuIndex = null;
                renderPlanList();
            }
        }
    } );

    function createTable( options ) {
        if ( ! state.selectedTemplateId ) {
            return;
        }
        var apiFetch = window.wp && wp.apiFetch ? wp.apiFetch : null;
        if ( ! apiFetch || ! config.rest || ! config.rest.createUrl ) {
            return;
        }

        createAndOpenBtn.disabled = true;
        createAndCopyBtn.disabled = true;
        createAndOpenBtn.classList.add( 'is-busy' );
        createAndCopyBtn.classList.add( 'is-busy' );

        var plansPayload = buildPlansPayload();

        apiFetch( {
            path: config.rest.createUrl.replace( restUrlRoot(), '' ),
            method: 'POST',
            headers: { 'X-WP-Nonce': config.rest.nonce },
            data: {
                template_id:   state.selectedTemplateId,
                layout_id:     state.selectedLayoutId || '',
                card_style_id: state.selectedCardStyleId || '',
                title:         nameInput ? nameInput.value : '',
                theme:         themeSelect ? themeSelect.value : '',
                dimensions:    state.dimensions,
                plan_count:    plansPayload.length || '',
                plans_override: plansPayload,
            },
        } ).then( function( response ) {
            createAndOpenBtn.disabled = false;
            createAndCopyBtn.disabled = false;
            createAndOpenBtn.classList.remove( 'is-busy' );
            createAndCopyBtn.classList.remove( 'is-busy' );
            if ( ! response ) {
                // eslint-disable-next-line no-console
                console.error( 'Unexpected create-table response', response );
                return;
            }
            if ( options && options.copyOnly && response.table_id ) {
                var shortcode = '[pwpl_table id="' + response.table_id + '"]';
                showShortcodeModal( shortcode );
                return;
            }
            if ( response.edit_url ) {
                window.location = response.edit_url;
            }
        } ).catch( function( err ) {
            createAndOpenBtn.disabled = false;
            createAndCopyBtn.disabled = false;
            createAndOpenBtn.classList.remove( 'is-busy' );
            createAndCopyBtn.classList.remove( 'is-busy' );
            // eslint-disable-next-line no-console
            console.error( 'Create-table failed', err );
            alert( ( config.i18n && config.i18n.createError ) || 'Unable to create table. Please try again.' );
        } );
    }

    createAndOpenBtn.addEventListener( 'click', function() {
        createTable( { copyOnly: false } );
    } );

    createAndCopyBtn.addEventListener( 'click', function() {
        createTable( { copyOnly: true } );
    } );

    // Initialize
    renderTemplates();
    if ( templates[0] ) {
        selectTemplate( templates[0].id );
    }

    function showShortcodeModal( shortcode ) {
        var existing = document.querySelector( '.pwpl-modal' );
        if ( existing ) {
            existing.remove();
        }
        var modal = document.createElement( 'div' );
        modal.className = 'pwpl-modal';

        var backdrop = document.createElement( 'div' );
        backdrop.className = 'pwpl-modal__backdrop';
        backdrop.addEventListener( 'click', closeModal );
        modal.appendChild( backdrop );

        var dialog = document.createElement( 'div' );
        dialog.className = 'pwpl-modal__dialog';

        var title = document.createElement( 'h3' );
        title.className = 'pwpl-modal__title';
        title.textContent = ( config.i18n && config.i18n.shortcodeTitle ) || 'Table created';
        dialog.appendChild( title );

        var desc = document.createElement( 'p' );
        desc.className = 'pwpl-modal__desc';
        desc.textContent = ( config.i18n && config.i18n.shortcodeDesc ) || 'Copy this shortcode to embed your table:';
        dialog.appendChild( desc );

        var fieldWrap = document.createElement( 'div' );
        fieldWrap.className = 'pwpl-modal__field';
        var input = document.createElement( 'input' );
        input.type = 'text';
        input.readOnly = true;
        input.value = shortcode;
        input.className = 'pwpl-modal__input';
        input.addEventListener( 'focus', function() { input.select(); } );
        fieldWrap.appendChild( input );

        var copyBtn = document.createElement( 'button' );
        copyBtn.type = 'button';
        copyBtn.className = 'button button-primary pwpl-modal__copy';
        copyBtn.textContent = ( config.i18n && config.i18n.shortcodeCopy ) || 'Copy shortcode';
        copyBtn.addEventListener( 'click', function() {
            if ( navigator.clipboard && navigator.clipboard.writeText ) {
                navigator.clipboard.writeText( shortcode );
                copyBtn.textContent = ( config.i18n && config.i18n.shortcodeCopied ) || 'Copied!';
                setTimeout( function() {
                    copyBtn.textContent = ( config.i18n && config.i18n.shortcodeCopy ) || 'Copy shortcode';
                }, 1800 );
            } else {
                input.select();
                document.execCommand( 'copy' );
            }
        } );
        fieldWrap.appendChild( copyBtn );
        dialog.appendChild( fieldWrap );

        var actions = document.createElement( 'div' );
        actions.className = 'pwpl-modal__actions';
        var closeBtn = document.createElement( 'button' );
        closeBtn.type = 'button';
        closeBtn.className = 'button';
        closeBtn.textContent = ( config.i18n && config.i18n.closeModal ) || 'Close';
        closeBtn.addEventListener( 'click', closeModal );
        actions.appendChild( closeBtn );
        dialog.appendChild( actions );

        modal.appendChild( dialog );
        document.body.appendChild( modal );

        function closeModal() {
            if ( modal && modal.parentNode ) {
                modal.parentNode.removeChild( modal );
            }
        }
    }
    renderLayoutTypes();
    renderCardStyles();
    renderPlanList();
    renderPlanEditor();
    renderSummary();
    updateSteps();
    updateDimToggles();
}() );
