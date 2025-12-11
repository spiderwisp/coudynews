/*global AWPCP*/
AWPCP.run('awpcp/init-collapsible-elements', ['jquery', 'awpcp/jquery-collapsible'],
function($) {
    $(function(){
        $('.awpcp-categories-list .top-level-category').closest('li').collapsible();
    });
});

AWPCP.run( 'awpcp/init-categories-dropdown', [ 'jquery', 'awpcp/categories-selector' ],
function( $, CategoriesSelector ) {
    $( function() {
        $( '.awpcp-category-dropdown[data-auto]' ).each( function() {
            $.noop( new CategoriesSelector( $( this ) ) );
        } );
    } );
} );

AWPCP.run( 'awpcp/init-category-selector', [ 'jquery' ],
function( $ ) {
    $( function() {
        var $selectors = $( '.awpcp-category-switcher .awpcp-category-dropdown' );

        $.subscribe( '/categories/change', function( event, $dropdown, category_id ) {
            if ( -1 === $selectors.index( $dropdown ) ) {
                return;
            }

            if ( category_id ) {
                $dropdown.closest( 'form' ).submit();
            }
        } );
    } );
} );

AWPCP.run( 'awpcp/reload-payment-completed-page', ['jquery'],
function( $ ) {
    $( function() {
        var $form = $( '#awpcp-payment-completed-form' );

        if ( 0 === $form.length ) {
            return;
        }

        var payment_status = $form.find( ':hidden[name="payment_status"]' ).val();

        if ( 'Not Verified' !== payment_status ) {
            return;
        }

        setTimeout( function() {
            location.reload();
        }, 5000 );
    } );
} );
