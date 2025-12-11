/*global AWPCP*/

AWPCP.define('awpcp/collapsible', ['jquery'], function($) {
    function Collapsible(element) {
        this.element = element;
        this.handler = this.element.find('.js-handler').eq(0);
        this.subject = this.element.find('[data-collapsible]').eq(0);
    }

    $.extend(Collapsible.prototype, {
        setup: function() {
            var self = this;

            if ( 0 === self.subject.length ) {
                self.handler.hide();
                return;
            }

            self.handler.click( function( event ) {
                self.toggle.apply( self, [ event, this ] );
            } );

            if ( self.subject.is( '[awpcp-keep-open]' ) ) {
                self.showExpanded();
                return;
            }

            self.showCollapsed();
        },

        showExpanded: function() {
            var self = this;

            self.subject.show();
            self.setHandlerClass( 'close' );
        },

        showCollapsed: function() {
            var self = this;

            self.subject.hide();
            self.setHandlerClass( 'open' );
        },

        setHandlerClass: function( className ) {
            this.handler.find( 'span' ).removeClass( 'open close' ).addClass( className );
        },

        toggleHandlerClass: function() {
            if (this.subject.is(':visible')) {
                this.setHandlerClass( 'close' );
            } else {
                this.setHandlerClass( 'open' );
            }
        },

        toggle: function(event) {
            var self = this;

            event.preventDefault();

            self.subject.stop().slideToggle(function() { self.toggleHandlerClass(); });
        }
    });

    return Collapsible;
});
