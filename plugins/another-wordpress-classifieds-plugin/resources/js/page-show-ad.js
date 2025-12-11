/*global confirm, alert*/
(function($, undefined) {

    var AWPCP = jQuery.AWPCP = jQuery.extend({}, jQuery.AWPCP, AWPCP);

    $.AWPCP.FlagLink = function(link) {
        var self = this;

        self.id = parseInt($(link).attr('data-ad'), 10);

        self.link = link.click(function(event) {
            event.preventDefault();
            var proceed = confirm($.AWPCP.l10n('page-show-ad', 'flag-confirmation-message'));
            if (proceed) {
                self.flag_ad();
            }
        });
    };

    $.extend($.AWPCP.FlagLink.prototype, {
        flag_ad: function() {
            var self = this;

            $.ajax({
                url: $.AWPCP.get('ajaxurl'),
                data: {
                    'action': 'awpcp-flag-ad',
                    'ad': self.id,
                    'nonce': $.AWPCP.get('page-show-ad-flag-ad-nonce')
                },
                success: $.proxy(self.callback, self),
                error: $.proxy(self.callback, self)
            });
        },

        callback: function(data) {
            if (parseInt(data, 10) === 1) {
                alert($.AWPCP.l10n('page-show-ad', 'flag-success-message'));
            } else {
                alert($.AWPCP.l10n('page-show-ad', 'flag-error-message'));
            }
        }
    });

    $(function() {
        $.noop( new $.AWPCP.FlagLink( $( '.awpcp-flag-listing-link' ) ) );
    });

    $(function() {
        if ( typeof $.fn.lightGallery === 'undefined' ) {
            return;
        }

        var getGalleryItems = function() {
            var items = null;
            items = $( '#classiwrapper [data-awpcp-gallery]' ).map( function( index, element ) {
                var $link = $( element ), $img = $link.find( 'img' );

                if ( $img.length === 0 ) {
                    return undefined;
                }

                return {
                    src: $link.attr( 'href' ),
                    thumb: $img.attr( 'src' )
                };
            } ).get();

            return items;
        }

        $( '#classiwrapper' ).on( 'click', '.awpcp-listing-primary-image-thickbox-link, .thickbox', function( event ) {
            event.preventDefault();

            var $link = $( this ),
                galleryItems = getGalleryItems(),
                currentGalleryItem = 0;

            for ( var i = galleryItems.length - 1; i >= 0; i = i - 1 ) {
                if ( galleryItems[ i ].src === $link.attr( 'href' ) ) {
                    currentGalleryItem = i;
                }
            }

            $link.lightGallery({
                download: false,
                dynamic: true,
                dynamicEl: galleryItems,
                index: currentGalleryItem
            });
        } );

    });
})(jQuery);
