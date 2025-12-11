/*global AWPCP*/
AWPCP.define( 'awpcp/util/number-format', [ 'awpcp/settings' ], function( settings ) {
    var NumberFormatter = {
        /**
         * Taken and modified from http://stackoverflow.com/a/9318724
         */
        format: function( value, decimalPlaces, thousandsSeparator, decimalSeparator ) {
            decimalPlaces = isNaN( decimalPlaces = Math.abs( decimalPlaces ) ) ? 2 : decimalPlaces;
            decimalSeparator = decimalSeparator === undefined ? '.' : settings.get( 'decimal-separator' );
            thousandsSeparator = thousandsSeparator === undefined ? ',' : settings.get( 'thousands-separator' );

            var absolute = Math.abs( +value || 0 ).toFixed( decimalPlaces );
            var rounded = parseInt( absolute, 10 ).toString();
            var firstThousandsGroupPosition = rounded.length > 3 ? rounded.length % 3 : 0;
            var formatted;

            if ( firstThousandsGroupPosition ) {
                formatted = rounded.substr( 0, firstThousandsGroupPosition );
            } else {
                formatted = '';
            }

            formatted += rounded.substr( firstThousandsGroupPosition ).replace( /(\d{3})(?=\d)/g, '$1' + thousandsSeparator );

            if ( decimalPlaces ) {
                formatted += decimalSeparator + Math.abs( absolute - rounded ).toFixed( decimalPlaces ).slice(2);
            }

            return value >= 0 ? formatted : '(' + formatted + ')';
        }
    };

    return NumberFormatter;
} );
