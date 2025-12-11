/*global AWPCP*/
AWPCP.define( 'awpcp/jquery-validate-methods', [ 'jquery' ],
function( $ ) {
    $( function() {
        if ( typeof $.validator === 'undefined' ) {
            return;
        }

        $.extend( $.validator.messages, $.AWPCP.get( 'default-validation-messages' ) );

        $.validator.addMethod( 'oneof', function( value, element, params ) {
            if ( this.optional( element ) ) {
                return true;
            }

            if ( $.inArray( value, params ) !== -1 ) {
                return true;
            }

            return false;
        } );

        $.validator.addMethod( 'classifiedsurl', function( value, element ) {
            // Allows URLs without protocol. Based on the url method in http://jqueryvalidation.org/
            return this.optional( element ) || /^(?:(https?|s?ftp):\/\/)?(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test( value );
        } );

        $.validator.addMethod('money', (function() {
            var decimal = $.AWPCP.get('decimal-separator'),
                thousands = $.AWPCP.get('thousands-separator'),
                pattern = new RegExp('^-?(?:\\d+|\\d{1,3}(?:\\' + thousands + '\\d{3})+)?(?:\\' + decimal + '\\d+)?$');

            return function(value, element) {
                return this.optional(element) || pattern.test(value);
            };
        })()/*, validation message provided as a default validation message in awpcp.php */);

        $.validator.addClassRules('integer', {
            integer: true
        });

        $.validator.setDefaults( {
            errorClass: 'error invalid',
            errorElement: 'span',
            errorPlacement: function ( error, element ) {
                error.addClass('awpcp-error');

                var tables = ['payment_term', 'credit_plan', 'payment_method'];

                if ( $.inArray( element.attr( 'name' ), tables ) !== -1) {
                    error.insertBefore( element.closest( 'table' ) );
                } else if ( element.closest( '.awpcp-form-spacer' ).length ) {
                    error.appendTo( element.closest( '.awpcp-form-spacer' ) );
                }
            }
        } );

    } );

    // To prevent this function being called multiple times. See _instantiate() in awpcp.js.
    return true;
} );

