/*global AWPCP*/
AWPCP.define( 'awpcp/frontend/upload-media-section-controller', [
    'jquery',
    'awpcp/media-center',
    'awpcp/settings',
    'awpcp/jquery-collapsible'
], function( $, MediaCenter, settings ) {
    var UploadMediaSectionController = function( section, store ) {
        var self = this;

        self.id       = section.id;
        self.template = section.template;
        self.store    = store;

        self.selectedPaymentTerm = null;
        self.listing             = null;
    };

    $.extend( UploadMediaSectionController.prototype, {
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

            var selectedPaymentTerm = self.store.getSelectedPaymentTermId();

            // See https://github.com/drodenbaugh/awpcp/commit/e59ccd2
            var orderModifiedDate = self.store.getOrderModifiedDate();

            if ( selectedPaymentTerm === null ) {
                return false;
            }

            if ( selectedPaymentTerm !== self.selectedPaymentTerm ) {
                return true;
            }

            if ( orderModifiedDate !== self.orderModifiedDate ) {
                return true;
            }

            return false;
        },

        updateSelectedValues: function() {
            var self = this;

            self.selectedPaymentTerm = self.store.getSelectedPaymentTermId();
            self.listing             = self.store.getListingId();
            self.orderModifiedDate   = self.store.getOrderModifiedDate();
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
            var self = this;

            self.$element = $( self.template ).replaceAll( self.$element ).collapsible();

            self.$element.find( '.awpcp-media-center' ).StartMediaCenter( {
                mediaManagerOptions: settings.get( 'media-manager-data' ),
                mediaUploaderOptions: settings.get( 'media-uploader-data' )
            } );

            self.$element.addClass( 'rendered' );
        },

        shouldHideTemplate: function() {
            var self = this;

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

            self.$element.find( '.awpcp-upload-media-listing-section__loading_mode' ).show();
            self.$element.find( '.awpcp-upload-media-listing-section__edit_mode' ).hide();

            self.$element.show();
        },

        showEditMode: function() {
            var self = this;

            self.$element.find( '.awpcp-upload-media-listing-section__loading_mode' ).hide();
            self.$element.find( '.awpcp-upload-media-listing-section__edit_mode' ).show();

            self.$element.show();
        },

        reload: function( data ) {
            var self = this;

            self.template = data.template;

            self.$element.removeClass( 'rendered' );
            self.prepareTemplate();
        },

        clear: function() {
        }
    } );

    return UploadMediaSectionController;
} );
