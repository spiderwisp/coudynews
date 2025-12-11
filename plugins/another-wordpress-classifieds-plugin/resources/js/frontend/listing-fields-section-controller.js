/* globals AWPCP, _ */
AWPCP.define( 'awpcp/frontend/listing-fields-section-controller', [
    'jquery',
    'awpcp/settings',
    'awpcp/restricted-length-field',
    'awpcp/multiple-region-selector-validator',
    'awpcp/datepicker-field',
    'awpcp/jquery-collapsible',
    'awpcp/jquery-validate-methods'
], function( $, settings, RestrictedLengthField, MultipleRegionsSelectorValidator, DatepickerField ) {
    var ListingFieldsSectionController = function( section, store ) {
        var self = this;

        self.id       = section.id;
        self.template = section.template;
        self.store    = store;

        self.listing             = null;
        self.selectedCategories  = [];
        self.selectedPaymentTerm = null;
        self.selectedUserId      = null;

        self.updater = null;
    };

    $.extend( ListingFieldsSectionController.prototype, {
        render: function( $container ) {
            var self = this;

            if ( ! self.$element ) {
                self.$element = $( '<div></div>' ).appendTo( $container );
            }

            if ( self.shouldUpdateTemplate() ) {
                self.updateSelectedValues();

                self.store.setSectionStateToLoading( self.id );

                return self.store.requestSectionUpdate( self.id );
            }

            self.updateSelectedValues();
            self.prepareTemplate();
        },

        shouldUpdateTemplate: function() {
            var self = this;

            var listing = self.store.getListingId();

            if ( null === listing ) {
                return false;
            }

            if ( listing !== self.listing ) {
                return true;
            }

            var selectedCategories  = self.store.getSelectedCategoriesIds();
            var selectedPaymentTerm = self.store.getSelectedPaymentTermId();
            var selectedUserId      = self.store.getSelectedUserId();

            // The modified order date changes everytime the Order section
            // successfully stores the payment information for the listing.
            //
            // We use it here to update the template again after the Payment
            // Transaction has been updated to use the newly selected user.
            var orderModifiedDate   = self.store.getOrderModifiedDate();

            if ( 0 === selectedCategories.length || null === selectedPaymentTerm ) {
                return false;
            }

            if ( ! _.isEqual( selectedCategories, self.selectedCategories ) ) {
                return true;
            }

            if ( selectedPaymentTerm !== self.selectedPaymentTerm ) {
                return true;
            }

            if ( selectedUserId !== self.selectedUserId ) {
                return true;
            }

            if ( orderModifiedDate !== self.orderModifiedDate ) {
                return true;
            }

            return false;
        },

        prepareTemplate: function() {
            var self = this;

            if ( ! self.$element.hasClass( 'rendered' ) ) {
                self.renderTemplate();
            }

            if ( self.shouldHideTemplate() ) {
                self.showDisabledMode();
            } else {
                self.updateTemplate();
            }
        },

        renderTemplate: function() {
            var self = this,
                data = self.store.getListingFields();

            // We use slice() because prior to jQuery 1.9, replaceAll() returned
            // the aggregate set of all elements appended to the target elements.
            //
            // https://jquery.com/upgrade-guide/1.9/#appendto-insertbefore-insertafter-and-replaceall
            self.$element = $( self.template ).replaceAll( self.$element ).slice( 0, self.$element.length );

            self.$element.collapsible();

            // Mark element as rendered earlier to prevent renderTemplate from being called
            // again if the data store is refreshed as the result of one of the actions
            // below.
            //
            // This line shouldn't be moved to the bottom of the function.
            self.$element.addClass( 'rendered' );

            // References to necessary elements.
            self.$regionsSelector = self.$element.find( '.awpcp-multiple-region-selector' );

            /* jshint ignore:start */
            if ( settings.get( 'overwrite-contact-information-on-user-change' ) ) {
                // self.updater = new UserInformationUpdater( self.$element );
                // self.updater.watch();
            }
            /* jshint ignore:end */

            // display and control characters allowed for the Ad title
            $.noop( new RestrictedLengthField( self.$element.find( '[name="ad_title"]' ) ) );

            // display and control characters allowed for the Ad details
            $.noop( new RestrictedLengthField( self.$element.find( '[name="ad_details"]' ) ) );

            // Instantiate Multiple Region Selector using the currently selected regions,
            // if any.
            if ( self.$regionsSelector.length ) {
                self.$regionsSelector.MultipleRegionSelector( data.regions );
            }

            // XXX: Adds support for Extra Fields DatePicker fields.
            self.$element.find( '[datepicker-placeholder]' ).each( function() {
                $.noop( new DatepickerField( $( this ).siblings( '[name]:hidden' ), {
                    datepicker: {
                        onSelect: function( dateText, instance ) {
                            var data = {};

                            data[ instance.id ] = instance.settings.altField.val();

                            self.store.updateListingFields( data );
                        }
                    }
                } ) );
            } );

            // TODO: Should we route this through the store?
            $.publish( '/awpcp/post-listing-page/details-step/ready', [ self.$element, self.store ] );

            self.$element.find( 'form' ).validate({
                messages: $.AWPCP.l10n( 'submit-listing-form-fields' ),
                onfocusout: false
            });

            self.$element.on( 'change', '.awpcp-has-value', function() {
                self.onContinueButtonClicked();
            } );

            // Load values already present in the form when the template was loaded.
            self.onContinueButtonClicked();
        },

        onContinueButtonClicked: function() {
            var self = this,
                data = {};

            self.$element.find( '.awpcp-has-value' ).each( function( index, element ) {
                var $field = $( element ),
                    type   = $field.attr( 'type' ),
                    name   = $field.attr( 'name' );

                if ( ( 'radio' === type || 'checkbox' === type ) && ! $field.is(':checked') ) {
                    return;
                }

                if ( typeof data[ name ] !== 'undefined' && $.isArray( data[ name ] ) ) {
                    data[ name ].push( $field.val() );
                } else if ( typeof data[ name ] !== 'undefined' ) {
                    data[ name ] = [ data[ name ], $field.val() ];
                } else {
                    data[ $field.attr( 'name' ) ] = $field.val();
                }
            } );

            if ( self.$regionsSelector.length ) {
                data.regions = self.$regionsSelector.data( 'RegionSelector' ).getSelectedRegions();
            }

            self.store.updateListingFields( data );
        },

        shouldHideTemplate: function() {
            var self = this;

            if ( self.selectedCategories.length === 0 ) {
                return true;
            }

            if ( self.selectedUserId === null ) {
                return true;
            }

            if ( self.selectedPaymentTerm === null ) {
                return true;
            }

            return false;
        },

        updateTemplate: function() {
            var self  = this,
                state = self.store.getSectionState( self.id );

            if ( 'disabled' === state ) {
                return self.showDisabledMode();
            }

            if ( 'loading' === state ) {
                return self.showLoadingMode();
            }

            return self.showEditMode();
        },

        showDisabledMode: function() {
            var self = this;

            self.$element.hide();
        },

        showLoadingMode: function() {
            var self = this;

            self.$element.find( '.awpcp-listing-fields-submit-listing-section__loading_mode' ).show();
            self.$element.find( '.awpcp-listing-fields-submit-listing-section__edit_mode' ).hide();

            self.$element.show();
        },

        showEditMode: function() {
            var self = this, data;

            self.$element.find( '.awpcp-listing-fields-submit-listing-section__loading_mode' ).hide();
            self.$element.find( '.awpcp-listing-fields-submit-listing-section__edit_mode' ).show();

            data = self.store.getListingFields();

            $.each( data, function( name, value ) {
                $( '[name="' + name + '"]').each( function() {
                    var $field       = $( this ),
                        type         = $field.attr( 'type' ),
                        isArrayValue = $.isArray( value );

                    if ( 'checkbox' === type || 'radio' === type ) {
                        if ( isArrayValue && $.inArray( $field.val(), value ) !== -1 ) {
                            $field.prop( 'checked', true );
                        } else if ( ! isArrayValue && value === $field.val() ) {
                            $field.prop( 'checked', true );
                        } else {
                            $field.prop( 'checked', false );
                        }
                    } else if ( 'hidden' === type ) {
                        return;
                    } else {
                        $field.val( value );
                    }
                } );
            } );

            self.$element.show();
        },

        updateSelectedValues: function() {
            var self = this;

            self.listing             = self.store.getListingId();
            self.selectedCategories  = self.store.getSelectedCategoriesIds();
            self.selectedPaymentTerm = self.store.getSelectedPaymentTermId();
            self.selectedUserId      = self.store.getSelectedUserId();
            self.orderModifiedDate   = self.store.getOrderModifiedDate();
        },

        reload: function( data ) {
            var self = this;

            self.template = data.template;

            self.$element.removeClass( 'rendered' );
            self.prepareTemplate();
        },

        clear: function() {
            var self = this;

            self.$element.find( '.awpcp-has-value' ).not( '[readonly]' ).val( null ).trigger( 'change' );

            if ( self.$regionsSelector.length ) {
                self.$regionsSelector.data( 'RegionSelector' ).clearSelectedRegions();
            }
        },

        /**
         * @return non-empty array if the form has errors.
         */
        validate: function( action ) {
            var self = this;

            // The current state of form fields is irrelevant if we are about
            // to change the category, payment term or ad owner. New fields may
            // become available and other become unnecessary after that action.
            if ( action === 'update-listing-order' ) {
                return [];
            }

            var $form = self.$element.find( 'form' ),
                formHasDuplicatedRegions = false,
                formHasEmptyRegionFields = false;

            formHasDuplicatedRegions = MultipleRegionsSelectorValidator
                .showErrorsIfUserSelectedDuplicatedRegions( $form );

            formHasEmptyRegionFields = MultipleRegionsSelectorValidator
                .showErrorsIfRequiredFieldsAreEmpty( $form );

            if ( ! $form.valid() || formHasDuplicatedRegions || formHasEmptyRegionFields ) {
                return [ true ];
            }

            return [];
        },

        showErrors: function() {
            // jQuery validate takes care of that.
        }
    } );

    return ListingFieldsSectionController;
} );
