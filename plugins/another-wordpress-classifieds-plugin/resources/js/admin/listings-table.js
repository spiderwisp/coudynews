/*global AWPCP */
AWPCP.run( 'awpcp/admin/listings-table', [
    'jquery',
    'moment',
    'awpcp/categories-selector'
], function( $, moment ) {
    $( function() {
        $( '.awpcp-search-mode-dropdown-container' ).insertBefore( '#post-search-input' ).removeClass( 'awpcp-hidden' );
    } );

    $( function() {
        if ( typeof moment === 'undefined' || typeof $.fn.daterangepicker === 'undefined' ) {
            return;
        }

        var $dataFilterField      = $( '[name="awpcp_date_filter"]' );
        var $dateRangePlaceholder = $( '[name="awpcp_date_range_placeholder"]' );
        var $dateRangeStart       = $( '[name="awpcp_date_range_start"]' );
        var $dateRangeEnd         = $( '[name="awpcp_date_range_end"]' );

        var formatSelectedDate = function( startDate, endDate ) {
            $dateRangePlaceholder.val( startDate.format( 'MMMM D, YYYY' ) + ' - ' + endDate.format( 'MMMM D, YYYY' ) );
        };

        moment.locale( $dateRangePlaceholder.data( 'locale' ) );

        var startDate = moment( $dateRangeStart.val(), 'YYYY-MM-DD' );
        var endDate   = moment( $dateRangeEnd.val(), 'YYYY-MM-DD' );

        $dataFilterField.on( 'change', function() {
            if ( $( this ).val() ) {
                $dateRangePlaceholder.removeClass( 'awpcp-hidden' );
            } else {
                $dateRangePlaceholder.addClass( 'awpcp-hidden' );
            }
        } );

        $dateRangePlaceholder.daterangepicker( {
            autoUpdateInput: false,
            ranges: {
               'Today': [moment(), moment()],
               'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
               'Last 7 Days': [moment().subtract(6, 'days'), moment()],
               'Last 30 Days': [moment().subtract(29, 'days'), moment()],
               'This Month': [moment().startOf('month'), moment().endOf('month')],
               'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
           }
        } );

        $dateRangePlaceholder.on('apply.daterangepicker', function( event, picker ) {
            $dateRangeStart.val( picker.startDate.format( 'YYYY-MM-DD' ) );
            $dateRangeEnd.val( picker.endDate.format( 'YYYY-MM-DD' ) );

            formatSelectedDate( picker.startDate, picker.endDate );
        } );

        if ( startDate.isValid() && endDate.isValid() ) {
            formatSelectedDate( startDate, endDate );
        }

        $dataFilterField.trigger( 'change' );
    } );
} );
