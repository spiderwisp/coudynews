/*global AWPCP*/
/*global AWPCPSubmitListingPageData*/
AWPCP.run( 'awpcp/frontend/submit-listing-page', [
    'jquery',
    'awpcp/frontend/submit-listing-data-store',
    'awpcp/frontend/actions-section-controller',
    'awpcp/frontend/order-section-controller',
    'awpcp/frontend/listing-fields-section-controller',
    'awpcp/frontend/listing-dates-section-controller',
    'awpcp/frontend/upload-media-section-controller',
    'awpcp/frontend/save-section-controller'
], function(
    $,
    Store,
    ActionsSectionController,
    OrderSectionController,
    ListingFieldsSectionController,
    ListingDatesSectionController,
    UploadMediaSectionController,
    SaveSectionController
) {
    var Page = function( store, sections, $container ) {
        var self = this;

        self.store      = store;
        self.sections   = sections;
        self.$container = $container;
    };

    $.extend( Page.prototype, {
        render: function() {
            var self = this;

            $.each( self.sections, function( index, section ) {
                section.render( self.$container );
            } );
        },

        reload: function( sections ) {
            var self = this;

            $.each( sections, function( index, data ) {
                if ( typeof self.sections[ data.id ] === 'undefined' ) {
                    return;
                }

                self.store.setSectionStateWithoutRefreshing( data.id, data.state );

                self.sections[ data.id ].reload( data, self.$container );
            } );
        },

        clear: function() {
            var self = this;

            $.each( self.sections, function( index, section ) {
                section.clear();
            } );
        },

        validate: function( action ) {
            var self = this,
                errors = {},
                errorsCount = 0;

            $.each( self.sections, function( index, section ) {
                if ( ! section.validate ) {
                    return;
                }

                errors[ section.id ] = section.validate( action );
                errorsCount = errorsCount + errors[ section.id ].length;
            } );

            if ( 0 === errorsCount ) {
                return true;
            }

            $.each( errors, function( sectionId, sectionErrors ) {
                self.sections[ sectionId ].showErrors( sectionErrors );
            } );

            $( 'html, body' ).animate( {
                scrollTop: self.$container.find( '.awpcp-error:visible' ).eq( 0 ).offset().top - 200
            }, 'fast' );
        }
    } );

    $( function() {
        var store = new Store(),
            sections = {},
            controllers;

        controllers = {
            'actions':        ActionsSectionController,
            'order':          OrderSectionController,
            'listing-dates':  ListingDatesSectionController,
            'listing-fields': ListingFieldsSectionController,
            'upload-media':   UploadMediaSectionController,
            'save':           SaveSectionController
        };

        /**
         * Use $.subscribe( 'awpcp/register-submit-listing-section-controllers', function( event, controllers ) {} )
         * to register additional sections controllers.
         */
        $.publish( 'awpcp/register-submit-listing-section-controllers', [ controllers ] );

        $.each( AWPCPSubmitListingPageData.sections, function( index, section ) {
            sections[ index ] = new controllers[ index ]( section, store );

            store.setSectionStateWithoutRefreshing( section.id, section.state );
        } );

        store.listener = new Page( store, sections, $( '.awpcp-submit-listing-page-form' ) );
        store.mode     = AWPCPSubmitListingPageData.mode;

        store.refresh();
    } );
} );
