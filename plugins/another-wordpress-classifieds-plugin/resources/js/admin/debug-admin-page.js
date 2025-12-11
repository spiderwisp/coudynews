/* globals ajaxurl, AWPCPAjaxOptions */
if ( typeof jQuery !== 'undefined' ) {
    (function( $ ) {
        $( '.awpcp-test-ssl-client-button' ).click( function( event ) {
            event.preventDefault();

            var $textarea = $( '.awpcp-test-ssl-client-results' ), options;

            options = {
                data: {
                    action: 'awpcp-test-ssl-client',
                    nonce: AWPCPAjaxOptions.nonce
                }
            };

            $textarea.html( '&hellip;' ).removeClass( 'awpcp-hidden' );

            $.ajax( ajaxurl, options )
                .then( function( response ) {
                    $textarea.html( response );
                } );
        } );
    })( jQuery );
}
