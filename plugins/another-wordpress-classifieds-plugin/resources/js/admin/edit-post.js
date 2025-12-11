/*global AWPCP */
AWPCP.run( 'awpcp/edit-post', [
    'jquery',
    'awpcp/datepicker-field',
    'awpcp/multiple-region-selector-validator',
    'awpcp/media-center',
    'awpcp/settings',
    'awpcp/user-selector',
    'awpcp/jquery-validate-methods'
], function(
    $,
    DatepickerField,
    MultipleRegionsSelectorValidator,
    MediaCenter,
    settings,
    UserSelector
) {
    $( function() {
        $( '[datepicker-placeholder]' ).each( function() {
            $.noop( new DatepickerField( $( this ).siblings( '[name]:hidden' ) ) );
        } );

        $( '.awpcp-listing-fields-metabox, .awpcp-listing-information-metabox' ).on( 'click', '.awpcp-tab a', function( event ) {
            event.preventDefault();

            var $link = $( this ),
                $tab = $link.closest( '.awpcp-tab' ),
                $container = $tab.closest( '.awpcp-tabs' ).parent();

            $container.find( '.awpcp-tab, .awpcp-tab-panel' ).removeClass( 'awpcp-tab-active awpcp-tab-panel-active' );
            $container.find( $link.attr( 'href' ) ).addClass( 'awpcp-tab-panel-active' );

            $tab.addClass( 'awpcp-tab-active' );
        } );

        $( 'form#post' ).validate({
            messages: $.AWPCP.l10n( 'edit-post-form-fields' ),
            onfocusout: false,
            submitHandler: function( form, event ) {
                if ( event.isDefaultPrevented() ) {
                    return;
                }

                if ( MultipleRegionsSelectorValidator.showErrorsIfUserSelectedDuplicatedRegions( form ) ) {
                    return false;
                }

                if ( MultipleRegionsSelectorValidator.showErrorsIfRequiredFieldsAreEmpty( form ) ) {
                    return false;
                }

                $( form ).trigger( 'submit.edit-post' );
            }
        });

        $( '.awpcp-media-center' ).StartMediaCenter( {
            mediaManagerOptions: settings.get( 'media-manager-data' ),
            mediaUploaderOptions: settings.get( 'media-uploader-data' )
        } );
    } );

    $( function() {
        var $listingInformationMetabox = $( '.awpcp-listing-information-metabox' );
        var $changePaymentTermLink = $listingInformationMetabox.find( '.awpcp-payment-term-name .edit-link' );
        var $changePaymentTermForm = $( '.awpcp-change-payment-term-form' );

        $changePaymentTermLink.click( function( event ) {
            event.preventDefault();

            $changePaymentTermLink.toggleClass( 'awpcp-hidden' );
            $changePaymentTermForm.toggleClass( 'awpcp-hidden' );
        } );

        $changePaymentTermForm.on( 'click', '[type="button"]', function() {
            var paymentTermId = $( '[name="payment_term"]' ).val(),
                $selectedOption = $changePaymentTermForm.find( '[value="' + paymentTermId + '"]' ),
                properties = $selectedOption.data( 'properties' ),
                $paymentTermName = $listingInformationMetabox.find( '.awpcp-payment-term-name .awpcp-value-display' );

            if ( properties ) {
                $paymentTermName.html( '<a href="' + properties.url + '">' + properties.name + '</a>' );

                $listingInformationMetabox.find( '.awpcp-payment-term-number-of-images .awpcp-value-display' ).text( properties.number_of_images );
                $listingInformationMetabox.find( '.awpcp-payment-term-number-of-regions .awpcp-value-display' ).text( properties.number_of_regions );
                $listingInformationMetabox.find( '.awpcp-payment-term-characters-in-title .awpcp-value-display' ).text( properties.characters_in_title );
                $listingInformationMetabox.find( '.awpcp-payment-term-characters-in-description .awpcp-value-display' ).text( properties.characters_in_description );

                var mediaUploaderOptions = settings.get( 'media-uploader-data' );
                mediaUploaderOptions['allowed_files']['images']['allowed_file_count'] = properties.number_of_images ;
                $.post( settings.get( 'ajaxurl' ), {
                    nonce: mediaUploaderOptions.nonce,
                    action: 'awpcp-update-payment-term',
                    payment_term: paymentTermId,
                    listing: mediaUploaderOptions.listing_id,
                    context: 'admin-place-ad'
                }, function() {
                } );

            }

            $changePaymentTermLink.toggleClass( 'awpcp-hidden' );
            $changePaymentTermForm.toggleClass( 'awpcp-hidden' );
        } );

        $changePaymentTermForm.on( 'click', '.cancel-link', function( event ) {
            event.preventDefault();

            $changePaymentTermLink.toggleClass( 'awpcp-hidden' );
            $changePaymentTermForm.toggleClass( 'awpcp-hidden' );
        } );


        // Classifieds Owner Dropdown
        $userSelect = $('#awpcp-classifieds-owner-metabox').find( '.awpcp-user-selector' );
        function getUserInformation( userId ) {
            $user = $userSelect.find( 'option[value="' + userId + '"]' );

            return $user.length ? $user.data( 'user-information' ) : null;
        }
        userData = $userSelect.data( 'configuration' );

        $userSelector = new UserSelector( $userSelect, $userSelect.data( 'configuration' ) );

    } );
} );
