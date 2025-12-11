( function( $ ) {
    $( function() {
        // Increments view counter.
        $.post( $.AWPCP.options[ 'ajaxurl' ], {
            action: 'awpcp-ad-count-view',
            listing_id: $.AWPCP.get( 'ad-id' )
        }, function( data ) {
            if ( data.status == 'ok' ) {
                $( '.adviewed' ).replaceWith( data.placeholder );
            }
        } );
    } );
} )( jQuery );


