/*global AWPCP*/
AWPCP.define( 'awpcp/frontend/actions-section-controller', [
    'jquery',
    'awpcp/jquery-collapsible'
], function( $ ) {
    var ActionsSectionController = function( section, store ) {
        var self = this;

        self.template = section.template;
        self.store    = store;
        self.error    = '';
        self.message  = '';
    };

    $.extend( ActionsSectionController.prototype, {
        render: function( $container ) {
            var self = this;

            if ( ! self.$element ) {
                self.$element = $( '<div></div>' ).appendTo( $container );
            }

            if ( ! self.$element.hasClass( 'rendered' ) ) {
                self.renderTemplate();
            }
        },

        renderTemplate: function() {
            var self = this;

            self.$element = $( self.template ).replaceAll( self.$element ).collapsible();

            self.$content = self.$element.find( '.awpcp-submit-listing-section-content .awpcp-actions-submit-listing-section__edit_mode' );
            self.$actions = self.$element.find( '.awpcp-listing-actions-component' );

            self.$actions.on( 'click', '[data-action]', function( event ) {
                event.preventDefault();

                self.onTriggerAction( $( this ) );
            } );

            if ( self.message ) {
                self.showSuccessMessage( self.message );
            }

            if ( self.error ) {
                self.showErrors( [ self.error ] );
            }

            self.$element.addClass( 'rendered' );
        },

        onTriggerAction: function( $action ) {
            var self = this;

            self.$element.find( '.awpcp-message' ).remove();

            if ( $action.data( 'confirmation-message' ) ) {
                self.showConfirmationMessage( $action );
                return;
            }

            self.execute( $action );
        },

        showConfirmationMessage: function( $action ) {
            var self = this, $confirmation , $confirm, $cancel;

            $confirmation = $( '<div class="awpcp-listing-action-confirmation"></div>' );
            $cancel       = $( '<button class="awpcp-cancel-listing-action-button">' + $action.data( 'cancel-button' ) + '</button>' );
            $confirm      = $( '<button class="awpcp-confirm-listing-action-button">' + $action.text() + '</button>' );

            $confirmation.append( $( '<span>' + $action.data( 'confirmation-message' ) + '</span>' ) );
            $confirmation.append( $cancel );
            $confirmation.append( $confirm );

            $cancel.click( function( event ) {
                event.preventDefault();

                $confirmation.remove();

                self.$actions.show();
            } );

            $confirmation.click( function( event ) {
                event.preventDefault();

                $confirmation.remove();

                self.execute( $action );
            } );

            self.$content.prepend( $confirmation );
            self.$actions.hide();
        },

        execute: function( $action ) {
            var self = this, options;

            /**
             * The 'method' option was added in jQuery 1.9, we also include
             * 'type' for websites using jQuery 1.8.x or older.
             */
            options = {
                url:       $.AWPCP.get( 'ajaxurl' ),
                data:      {
                    action: 'awpcp_execute_listing_action',
                    listing_action: $action.data( 'action' ),
                    listing_id: self.store.getListingId(),
                    nonce: $action.data( 'nonce' )
                },
                dataType: 'json',
                method:   'POST',
                type:     'POST',
            };

            $.ajax( options ).done( function( response ) {
                if ( 'error' === response.status && response.error ) {
                    self.error = response.error;
                    self.showErrors( [ response.error ] );
                }

                if ( 'ok' === response.status && response.message ) {
                    self.message = response.message;
                    self.showSuccessMessage( response.message );
                }

                if ( 'ok' === response.status && response.redirect_url ) {
                    // Wait a litle after the success message is shown.
                    setTimeout( function() {
                        document.location.href = response.redirect_url;
                    }, 1000 );

                    return;
                }

                if ( 'ok' === response.status && response.reload ) {
                    self.store.requestSectionUpdate( self.id );
                    self.store.refresh();
                }

                self.$actions.show();
            } );
        },

        showSuccessMessage: function( message ) {
            var self = this, $content = self.$element.find( '.awpcp-submit-listing-section-content' );

            $content.prepend( '<div class="awpcp-message awpcp-message-success notice notice-success success"><p>' + message + '</p></div>' );
        },

        showErrors: function( errors ) {
            var self = this;

            $.each( errors, function( index, error ) {
                self.$content.prepend( '<div class="awpcp-message awpcp-error notice notice-error error"><p>' + error + '</p></div>' );
            } );
        },

        reload: function( data ) {
            var self = this;

            self.template = data.template;

            self.renderTemplate();
        },

        clear: function() {
        }
    } );

    return ActionsSectionController;
} );
