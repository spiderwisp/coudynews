/*global AWPCP, _*/
AWPCP.define( 'awpcp/payment-terms-list', [ 'jquery', 'awpcp/settings' ],
function( $, settings ) {
    var PaymentTermsList = function( container, options ) {
        var self = this;

        self.$container = container;
        self.options    = options;

        this.container = container;
        this.state = {
            allPaymentTerms: this.container.find( '.awpcp-payment-term' )
        };

        $.subscribe( '/categories/change', _.bind( this.onCategoriesUpdated, this ) );
        $.subscribe( '/user/updated', _.bind( this.onUserUpdated, this ) );

        self.$container.on( 'change', '[name="payment_term"]', function() {
            self.onChange();
        } );

        this.update();
    };

    $.extend( PaymentTermsList.prototype, {
        onCategoriesUpdated: function( event, source, categories ) {
            if ( ! $.contains( source.closest( 'form' ).get( 0 ), this.container.get( 0 ) ) ) {
                return;
            }

            if ( categories === null && ! settings.get( 'hide-all-payment-terms-if-no-category-is-selected' ) ) {
                return;
            }

            if ( $.isArray( categories ) ) {
                this.state.selectedCategories = categories;
            } else {
                this.state.selectedCategories = [ categories ];
            }

            this.update();
        },

        onUserUpdated: function( event, user ) {
            if ( user && user.payment_terms ) {
                this.state.userPaymentTerms = user.payment_terms;
                this.update();
            }
        },

        onChange: function() {
            var self = this;

            if ( $.isFunction( self.options.onChange ) ) {
                self.options.onChange( self.getSelectedPaymentTerm() );
            }

            // self.isMaxCategories();
        },

        getSelectedPaymentTerm: function() {
            var self = this;

            var $radio = self.$container.find( '[name="payment_term"]:checked' );

            if ( $radio.length ) {
                return {
                    id: $radio.data( 'payment-term-id' ),
                    type: $radio.data( 'payment-term-type' ),
                    mode: $radio.data( 'payment-term-mode' ),
                    summary: $radio.data( 'payment-term-summary' ),
                    maxCategories: $radio.parents('li').data('number-of-categories-allowed')
                };
            }

            return null;
        },

        clearSelectedPaymentTerm: function() {
            var self = this;

            self.$container.find( '[name="payment_term"]' ).prop( 'checked', false );
            self.update();
        },

        includesFreePaymentTermOnly: function() {
            var length = this.state.allPaymentTerms.length;

            if ( length !== 1 ) {
                return false;
            }

            for ( var i = length - 1; i >= 0; i = i - 1 ) {
                if ( this.state.allPaymentTerms.eq( i ).data( 'id' ) !== 'fee-0' ) {
                    return false;
                }
            }

            return true;
        },

        update: function() {
            var disabledPaymentTerms = this._getDisabledPaymentTerms();
            var enabledPaymentTerms = this.state.allPaymentTerms.not( disabledPaymentTerms.get() );

            if ( enabledPaymentTerms.find( ':radio:checked' ).length === 0 ) {
                var radio = enabledPaymentTerms.eq( 0 ).find( ':radio:first' );

                if ( radio.prop ) {
                    radio.prop( 'checked', true );
                } else {
                    radio.attr( 'checked', 'checked' );
                }

                radio.trigger( 'change' );
            }

            // this.isMaxCategories();
            enabledPaymentTerms.fadeIn();
            disabledPaymentTerms.fadeOut();
        },

        isMaxCategories: function() {
            $( ".awpcp-submit-listing-page-form select[name='category[]']" )
            .parent( '.awpcp-form-spacer' ).find( '.awpcp-message-error' ).remove();
            var message = $.AWPCP.get( 'default-validation-messages' ).maxCategories;
            var cats = this.state.selectedCategories;
            var singleSelect = $( '.awpcp-submit-listing-page-form .select2-selection--multiple' ).length > 0;
            if ( typeof ( cats ) != 'undefined' && cats !== null && cats.length > 0 && !isNaN( cats[ 0 ] ) && singleSelect ) {
                if ( this.state.selectedCategories.length >= this.getSelectedPaymentTerm().maxCategories ) {
                    $( ".awpcp-submit-listing-page-form select[name='category[]']" )
                    .parent( '.awpcp-form-spacer' )
                    .append( '<p class="awpcp-message awpcp-message-error">' + message + '</p>' );
                }
            }
        },

        _getDisabledPaymentTerms: function _getDisabledPaymentTerms() {
            var self = this;
            var selectedCats = self.state.selectedCategories;
            if ( (selectedCats === null || typeof selectedCats === 'undefined' || selectedCats.length === 0) && settings.get( 'hide-all-payment-terms-if-no-category-is-selected' ) || AWPCPSubmitListingPageData.payments_enabled == "0" ) {
                return self.state.allPaymentTerms;
            }

            return self.state.allPaymentTerms.filter(function() {
                var paymentTerm = $( this );

                // filter by user
                if ( self.state.userPaymentTerms && ! self._isUserPaymentTerm( paymentTerm ) ) {
                    return true;
                }

                // filter by category
                if ( self.state.selectedCategories && ! self._isPaymentTermAvailableForCategories( paymentTerm ) ) {
                    return true;
                }

                return false;
            });
        },

        _isUserPaymentTerm: function( paymentTerm ) {
            return $.inArray( paymentTerm.attr( 'data-id' ), this.state.userPaymentTerms ) !== -1;
        },

        _isPaymentTermAvailableForCategories: function( paymentTerm ) {
            var paymentTermCategories = JSON.parse( paymentTerm.attr( 'data-categories' ) );
            var numberOfCategoriesAllowed = parseInt( paymentTerm.attr( 'data-number-of-categories-allowed' ), 10 );

            if ( this.state.selectedCategories.length > numberOfCategoriesAllowed ) {
                return false;
            }

            // payment terms with no list of associated categories are now assumed to be
            // available for all categories.
            if ( ! $.isArray( paymentTermCategories ) || paymentTermCategories.length === 0 ) {
                return true;
            }

            paymentTermCategories = $.map( paymentTermCategories, function( category ) {
                return parseInt( category, 10 );
            } );

            return _.difference( this.state.selectedCategories, paymentTermCategories ).length === 0;
        }
    } );

    return PaymentTermsList;
} );
