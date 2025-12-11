/*global AWPCP*/
AWPCP.define( 'awpcp/multiple-region-selector-validator', [ 'jquery' ],
function( $ ) {
    var MultipleRegionSelectorValidator = function() {};

    $.extend( MultipleRegionSelectorValidator.prototype, {
        showErrorsIfUserSelectedDuplicatedRegions: function( form ) {
            var self = this,

                $form = $( form ),

                multipleRegionSelector = self.getMultipleRegionSelectorInstance( $form ),
                userSelectedDuplicatedRegions = false;

            if ( typeof multipleRegionSelector === 'undefined' ) {
                return false;
            }

            $form.find( '.multiple-region:visible' ).each( function() {
                if ( multipleRegionSelector.checkDuplicatedRegionsForField( $( this ).attr( 'id' ), true ) ) {
                    userSelectedDuplicatedRegions = true;
                }
            } );

            return userSelectedDuplicatedRegions;
        },

        getMultipleRegionSelectorInstance: function( $form ) {
            return $form.find( '.awpcp-multiple-region-selector' ).data( 'RegionSelector' );
        },

        showErrorsIfRequiredFieldsAreEmpty: function( form ) {
            var $form = $( form ),
                multipleRegionSelector = this.getMultipleRegionSelectorInstance( $form ),
                emptyRequiredField = null,
                requiredFieldsAreEmpty = false;

            if ( ! multipleRegionSelector ) {
                return requiredFieldsAreEmpty;
            }

            $.each( multipleRegionSelector.regions(), function( index, region ) {
                $.each( region.partials(), function( index, partial ) {
                    if ( ! partial.config.required ) {
                        return;
                    }

                    var selected = partial.selected();

                    // True if selected is null, undefined, 0, an empty string
                    // or any other value with false boolean value.
                    if ( ! selected || ! selected.length ) {
                        region.error( AWPCP.l10n( 'multiple-region-selector', 'missing-' + partial.type ) );
                        emptyRequiredField = emptyRequiredField ? emptyRequiredField : partial;
                        requiredFieldsAreEmpty = true;
                    }
                } );
            } );

            if ( emptyRequiredField ) {
                $form.find( '[id="' + emptyRequiredField.id + '"]' )
                    .closest('.awpcp-region-selector-partials')
                        .find(':input:visible')
                            .last()
                                .focus();
            }

            return requiredFieldsAreEmpty;
        }
    } );

    return new MultipleRegionSelectorValidator();
} );

