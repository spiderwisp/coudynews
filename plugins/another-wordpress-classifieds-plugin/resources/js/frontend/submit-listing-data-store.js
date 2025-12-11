/*global AWPCP*/
AWPCP.define( 'awpcp/frontend/submit-listing-data-store', [
    'jquery'
], function( $ ) {
    var Store = function ( data ) {
        this.data         = data || {};
        this.listener     = null;
        this.mode         = '';
        this.refreshCalls = 0;
    };

    $.extend( Store.prototype, {
        setSectionStateToPreview: function( sectionId ) {
            var self = this;

            self.setSectionState( sectionId, 'preview' );
        },

        setSectionStateToRead: function( sectionId ) {
            var self = this;

            self.setSectionState( sectionId, 'read' );
        },

        setSectionState: function( sectionId, state ) {
            var self = this;

            self.setSectionStateWithoutRefreshing( sectionId, state );
            self.refresh();
        },

        setSectionStateWithoutRefreshing: function( sectionId, state ) {
            var self = this;

            if ( typeof self.data.sections === 'undefined' ) {
                self.data.sections = {};
            }

            if ( typeof self.data.sections[ sectionId ] === 'undefined' ) {
                self.data.sections[ sectionId ] = {};
            }

            self.data.sections[ sectionId ].state = state;
        },

        setSectionStateToEdit: function( sectionId ) {
            var self = this;

            self.setSectionState( sectionId, 'edit' );
        },

        setSectionStateToLoading: function( sectionId ) {
            var self = this;

            self.setSectionState( sectionId, 'loading' );
        },

        refresh: function() {
            var self = this;

            self.refreshCalls = self.refreshCalls + 1;

            if ( typeof self.data.sectionsToUpdate === 'undefined' ) {
                self.data.sectionsToUpdate = [];
            }

            self.listener.render();

            self.refreshCalls = self.refreshCalls - 1;

            if ( self.refreshCalls <= 0 && self.data.sectionsToUpdate.length ) {
                self.updateSections();
            }
        },

        getSectionState: function( sectionId ) {
            var self = this;

            if ( self.data.sections && self.data.sections[ sectionId ] && self.data.sections[ sectionId ].state ) {
                return self.data.sections[ sectionId ].state;
            }

            return 'edit';
        },

        requestSectionUpdate: function( sectionId ) {
            var self = this;

            self.data.sectionsToUpdate.push( sectionId );
        },

        updateSelectedCategories: function( categories ) {
            var self = this;

            self.data.categories = categories;

            self.refresh();
        },

        getSelectedCategoriesIds: function() {
            var self = this;

            return $.map( self.data.categories || [], function( category ) {
                return category.id;
            } );
        },

        getSelectedCategoriesNames: function() {
            var self = this;

            return $.map( self.data.categories || [], function( category ) {
                return category.name;
            } );
        },

        updateSelectedUser: function( user ) {
            var self = this;

            self.data.user = user;

            self.refresh();
        },

        getSelectedUserId: function() {
            var self = this;

            if ( self.data.user ) {
                return self.data.user.id;
            }

            return null;
        },

        getSelectedUserName: function() {
            var self = this;

            if ( self.data.user ) {
                return self.data.user.name;
            }

            return '';
        },

        updateSelectedPaymentTerm: function( paymentTerm ) {
            var self = this;

            self.data.paymentTerm = paymentTerm;

            self.refresh();
        },

        getSelectedPaymentTerm: function() {
            var self = this;

            if ( self.data.paymentTerm ) {
                return self.data.paymentTerm;
            }

            return null;
        },

        getSelectedPaymentTermId: function() {
            var self = this;

            if ( self.data.paymentTerm ) {
                return self.data.paymentTerm.id;
            }

            return null;
        },

        getSelectedPaymentTermSummary: function() {
            var self = this;

            if ( self.data.paymentTerm ) {
                return self.data.paymentTerm.summary;
            }

            return '';
        },

        updateSelectedCreditPlan: function( creditPlan ) {
            var self = this;

            self.data.creditPlan = creditPlan;

            self.refresh();
        },

        getSelectedCreditPlan: function() {
            var self = this;

            if ( self.data.creditPlan ) {
                return self.data.creditPlan;
            }

            return null;
        },

        getSelectedCreditPlanId: function() {
            var self = this;

            if ( self.data.creditPlan ) {
                return self.data.creditPlan.id;
            }

            return null;
        },

        getSelectedCreditPlanSummary: function() {
            var self = this;

            if ( self.data.creditPlan ) {
                return self.data.creditPlan.summary;
            }

            return '';
        },

        setCAPTCHAAnswer: function( captcha ) {
            var self = this;

            self.data.captcha = captcha;
        },

        getCAPTCHAAnswer: function() {
            var self = this;

            if ( self.data.captcha ) {
                return self.data.captcha;
            }

            return {};
        },

        setTransactionId: function( transactionId ) {
            var self = this;

            self.data.transaction = transactionId;
        },

        getTransactionId: function() {
            var self = this;

            if ( self.data.transaction ) {
                return self.data.transaction;
            }

            return null;
        },

        setOrderModifiedDate: function( date ) {
            var self = this;

            self.data.order = { modifiedDate: date };
        },

        getOrderModifiedDate: function() {
            var self = this;

            if ( typeof self.data.order === 'undefined' ) {
                return null;
            }

            if ( typeof self.data.order.modifiedDate === 'undefined' ) {
                return null;
            }

            return self.data.order.modifiedDate;
        },

        updateListingFields: function( fields ) {
            var self = this;

            self.data.fields = $.extend( self.data.fields || {}, fields );

            self.refresh();
        },

        setListingId: function( listingId ) {
            var self = this;

            if ( ! listingId ) {
                return;
            }

            self.data.listing = {
                ID: listingId
            };

            self.refresh();
        },

        getListingId: function() {
            var self = this;

            if ( self.data.listing ) {
                return self.data.listing.ID;
            }

            return null;
        },

        getListingFields: function() {
            var self = this;

            return self.data.fields || {};
        },

        updateCustomData: function( data ) {
            var self = this;

            self.data.custom = $.extend( self.data.custom || {}, data );

            self.refresh();
        },

        getCustomData: function() {
            var self = this;

            return self.data.custom || {};
        },

        updateSections: function() {
            var self = this,
                data, options, request;

            if ( self.updateSectionsTimeout ) {
                clearTimeout( self.updateSectionsTimeout );
            }

            data = {
                action: 'awpcp_update_submit_listing_sections',
                sections: self.data.sectionsToUpdate,
                mode:           self.mode,
                // TODO: create, validate and pass this nonce around.
                nonce: $.AWPCP.get( 'update_submit_listing_sections_nonce' ),
                listing: self.getListingId(),
                transaction_id: self.getTransactionId()
            };

            /**
             * The 'method' option was added in jQuery 1.9, we also include
             * 'type' for websites using jQuery 1.8.x or older.
             */
            options = {
                url: $.AWPCP.get( 'ajaxurl' ),
                data: data,
                dataType: 'json',
                method: 'POST',
                type:     'POST'
            };

            self.updateSectionsTimeout = setTimeout( function() {
                request = $.ajax( options ).done( function( data ) {
                    if ( 'ok' === data.status ) {
                        self.listener.reload( data.sections );
                    }

                    self.data.sectionsToUpdate = [];
                } );
            }, 250 );
        },

        clearSections: function() {
            var self = this;

            self.listener.clear();
        },

        isValid: function( action ) {
            return this.listener.validate( action );
        }
    } );

    return Store;
} );
