/*global AWPCP*/
AWPCP.define( 'awpcp/credit-plans-list', [
    'jquery'
],
function( $ ) {
    var CreditPlansList = function( container, options ) {
        var self = this;

        self.$container = $( container );
        self.options    = options;

        self.setup();
    };

    $.extend( CreditPlansList.prototype, {
        setup: function() {
            var self = this;

            self.$container.on( 'change', '[name="credit_plan"]', function() {
                self.onChange();
            } );
        },

        onChange: function() {
            var self = this;

            if ( $.isFunction( self.options.onChange ) ) {
                self.options.onChange( self.getSelectedCreditPlan() );
            }
        },

        getSelectedCreditPlan: function() {
            var self = this;

            var $radio = self.$container.find( '[name="credit_plan"]:checked' );

            return {
                id: $radio.data( 'credit-plan-id' ),
                name: $radio.data( 'credit-plan-name' ),
                price: $radio.data( 'credit-plan-price' ),
                summary: $radio.data( 'credit-plan-summary' )
            };
        },

        clearSelectedCreditPlan: function() {
            var self = this;

            self.$container.find( '[name="credit_plan"]' ).prop( 'checked', false );
        }
    } );

    return CreditPlansList;
} );
