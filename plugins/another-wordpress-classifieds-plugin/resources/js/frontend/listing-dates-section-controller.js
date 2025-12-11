/*global AWPCP*/
AWPCP.define( 'awpcp/frontend/listing-dates-section-controller', [
    'jquery',
    'awpcp/datepicker-field'
], function( $, DatepickerField ) {
    var ListingDatesSectionController = function( section, store ) {
        var self = this;

        self.id       = section.id;
        self.template = section.template;
        self.store    = store;
    };

    $.extend( ListingDatesSectionController.prototype, {
        render: function( $container ) {
            var self = this;

            if ( ! self.$element ) {
                self.$element = $( '<div></div>' ).appendTo( $container );
            }

            if ( self.shouldUpdateTemplate() ) {
                self.updateSelectedValues();

                return self.prepareToUpdateTemplate();
            }

            self.updateSelectedValues();
            self.prepareTemplate();
        },

        shouldUpdateTemplate: function() {
            var self    = this,
                listing = self.store.getListingId();

            if ( listing !== null && listing !== self.listing ) {
                return true;
            }

            return false;
        },

        prepareToUpdateTemplate: function() {
            var self = this,
                state = self.store.getSectionState( self.id );

            if ( 'disabled' !== state ) {
                self.store.setSectionStateToLoading( self.id );
            }

            self.store.requestSectionUpdate( self.id );
        },

        updateSelectedValues: function() {
            var self = this;

            self.listing = self.store.getListingId();
        },

        prepareTemplate: function() {
            var self = this;

            if ( ! self.$element.hasClass( 'rendered' ) ) {
                self.renderTemplate();
            }

            self.updateTemplate();
        },

        renderTemplate: function() {
            var self = this;

            self.$element = $( self.template ).replaceAll( self.$element ).collapsible();


            self.$startDate = self.$element.find( '[name="start_date"]' );
            self.$endDate   = self.$element.find( '[name="end_date"]' );

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

            self.$element.addClass( 'rendered' );
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

            self.$element.find( '.awpcp-listing-dates-submit-listing-section__loading_mode' ).show();
            self.$element.find( '.awpcp-listing-dates-submit-listing-section__edit_mode' ).hide();

            self.$element.show();
        },

        showEditMode: function() {
            var self = this;

            self.$element.find( '.awpcp-listing-dates-submit-listing-section__loading_mode' ).hide();
            self.$element.find( '.awpcp-listing-dates-submit-listing-section__edit_mode' ).show();

            self.$element.show();
        },

        reload: function( data ) {
            var self = this;

            self.template = data.template;

            self.$element.removeClass( 'rendered' );
            self.prepareTemplate();
        },

        clear: function() {
            var self = this;

            self.$element.find( '[datepicker-placeholder]' ).val( null ).trigger( 'change' );
        }
    } );

    return ListingDatesSectionController;
} );
