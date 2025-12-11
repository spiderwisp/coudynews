/*global AWPCP*/
AWPCP.run( 'awpcp/restricted-length-field', [
    'jquery'
], function( $ ) {
    var RestrictedLengthField = function(element) {
        var self = this;

        self.element = $(element);
        self.container = self.element.parent().find( '.characters-left' );
        self.placeholder = self.container.find('.characters-left-placeholder');

        self.allowed = parseInt(self.element.attr('data-max-characters'), 10);
        self.remaining = parseInt(self.element.attr('data-remaining-characters'), 10);

        self.element.bind('keyup keydown', function() {
            var text = self.element.val(), charactersLeft;

            if (self.allowed <= 0 ) {
                return;
            }

            if ( text.length > self.allowed ) {
                text = text.substring( 0, self.allowed );
                self.element.val( text );
            }

            charactersLeft = self.allowed - text.length;

            self.container.removeClass( 'awpcp-almost-no-characters-left' );
            self.container.removeClass( 'awpcp-no-characters-left' );

            if ( charactersLeft > 0 && charactersLeft <= 20 ) {
                self.container.addClass( 'awpcp-almost-no-characters-left' );
            }

            if ( charactersLeft <= 0 ) {
                self.container.addClass( 'awpcp-no-characters-left' );
            }

            self.placeholder.text( charactersLeft );
        }).trigger('keyup');
    };

    return RestrictedLengthField;
} );
