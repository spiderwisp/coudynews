/*global AWPCP*/
AWPCP.run( 'awpcp/page-search-listings', [
    'jquery',
    'awpcp/datepicker-field',
    'awpcp/categories-selector',
    'awpcp/jquery-validate-methods'
], function( $, DatepickerField, CategoriesSelector ) {
    var AWPCP = $.AWPCP = $.extend({}, $.AWPCP, AWPCP);

    $(function() {
        $.AWPCP.validate();

        var container = $('.awpcp-search-ads'), form, fields;

        /* Search Ads Form */

        form = container.find('.awpcp-search-ads-form');

        if (form.length) {
            // create and store jQuery objects for all form fields
            fields = form.find(':input').filter(':not(:button,:submit)').filter('[type!="hidden"]');

            form.find( '.awpcp-category-dropdown' ).each( function() {
                var $dropdown = $( this ),
                    selector  = new CategoriesSelector( $dropdown );

                setTimeout( function() {
                    $.publish( '/categories/change', [ $dropdown, selector.getSelectedCategoriesIds() ] );
                }, 10 );
            } );

            $( '[datepicker-placeholder]' ).each( function() {
                $.noop( new DatepickerField( $(this).siblings('[name]:hidden') ) );
            } );

            // Check if selectWoo is defined.
            if ( typeof $.fn.selectWoo !== 'undefined' ) {
                $( '[name="searchname"]' ).selectWoo();
            }
        }
    });
} );
