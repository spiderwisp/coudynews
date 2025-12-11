/*global AWPCP*/
AWPCP.define( 'awpcp/settings-validator', [
    'jquery',
    'awpcp/jquery-validate-methods'
], function( $ ) {
    var SettingsValidator = function() {
    };

    $.extend( SettingsValidator.prototype, {
        setup: function( form ) {
            var self = this, options = { rules: {}, messages: {} };

            form.find( '[awpcp-setting]' ).each( function() {
                var field = $(this),
                    fieldName = field.attr( 'name' ),
                    setting = JSON.parse( field.attr( 'awpcp-setting' ) );

                if ( setting && setting.validation && setting.validation.messages ) {
                    options.messages[ fieldName ] = setting.validation.messages;
                }

                if ( setting && setting.validation && setting.validation.rules ) {
                    options.rules[ fieldName ] = self.getSettingRules( setting );
                }

                self.setupSettingBehavior( field, setting );
            } );

            form.validate( $.extend( options, {
                errorClass: 'invalid',
                errorPlacement: function ( error, element ) {
                    error.addClass( 'awpcp-message awpcp-error' ).css( 'display', 'block' );
                    element.closest( 'td' ).append( error );
                }
            } ) );
        },

        getSettingRules: function( setting ) {
            var self = this, rules = {};

            for ( var validator in setting.validation.rules ) {
                if ( setting.validation.rules.hasOwnProperty( validator ) ) {
                    rules[ validator ] = self.getRuleForValidator( validator, setting );
                }
            }

            return rules;
        },

        getRuleForValidator: function( validator, setting ) {
            var rule = setting.validation.rules[ validator ],
                selector = this.getEscapedSelector( rule.depends );

            if ( $( selector ).length === 0 ) {
                return rule;
            } else {
                return $.extend( {}, rule, {
                    depends: function() {
                        return $( selector ).is(':checked');
                    }
                } );
            }
        },

        getEscapedSelector: function ( selector ) {
            var self = this;

            if ( typeof selector === 'undefined' ) {
                return false;
            }

            var escapedSelectorParts = $.map( selector.split( ',' ), function( part ) {
                return '#' + self.escapeSelector( part );
            } );

            return escapedSelectorParts.join( ',' );
        },

        escapeSelector: function( selector ) {
            return selector.trim().replace( /(:|\.|\[|\]|,)/g, '\\$1' );
        },

        getEscapedNameSelector: function( selector ) {
            var self = this;

            var escapedSelectorParts = $.map( selector.split( ',' ), function( part ) {
                return '[name="awpcp-options[' + self.escapeSelector( part ) + ']"]';
            } );

            return escapedSelectorParts.join( ',' );
        },

        setupSettingBehavior: function( field, setting ) {
            var self = this;

            for ( var behavior in setting.behavior ) {
                if ( setting.behavior.hasOwnProperty( behavior ) ) {
                    if ( $.isFunction( self[ behavior ] ) ) {
                        self[ behavior ].apply( self, [ field, setting.behavior[ behavior ] ] );
                    }
                }
            }
        },

        enabledIf: function( field, element ) {
            var dependencies = $( this.getEscapedSelector( element ) );

            dependencies.change( function() {
                if ( dependencies.is(':checked') ) {
                    field.prop( 'disabled', false );
                } else {
                    field.prop( 'disabled', true );
                }
            } );

            dependencies.change();
        },

        shownUnless: function( field, element ) {
            var dependencies = $( this.getEscapedSelector( element ) );

            dependencies.change( function() {
                if ( dependencies.is( ':checked' ) ) {
                    field.closest('tr').fadeOut( 400 );
                } else {
                    field.closest('tr').fadeIn( 400 );
                }
            } );

            dependencies.change();
        },

        enabledIfMatches: function( field, rule ) {
            var parts = rule.split( '=' ),
                element = parts[0],
                expectedValue = parts[1],
                dependencies = $( this.getEscapedNameSelector( element ) );

            dependencies.change( function() {
                if ( dependencies.filter( ':checked' ).val() === expectedValue ) {
                    field.prop( 'disabled', false );
                } else {
                    field.prop( 'disabled', true );
                }
            } );

            dependencies.change();
        }
    } );

    return new SettingsValidator();
} );
