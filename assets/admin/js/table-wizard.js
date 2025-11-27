( function() {
    if ( typeof window.PWPL_TableWizard === 'undefined' ) {
        return;
    }
    var root = document.getElementById( 'pwpl-table-wizard-root' );
    if ( ! root ) {
        return;
    }
    // Placeholder: React app will mount here in a later step.
    // eslint-disable-next-line no-console
    console.log( 'PWPL_TableWizard config', window.PWPL_TableWizard );
}() );
