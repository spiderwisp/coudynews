/*global AWPCP, grecaptcha*/
AWPCP.run('awpcp/init-recaptcha', ['jquery'],
function($) {
    var maxAttempts = 45,
        attempts = 0,
        maxDelay = 1500,
        timeout = false;

     function initReCaptcha() {
        if ( window['AWPCPGetReCaptchaResponse'] ) {
            return;
        }

        var $widgets = $( '.awpcp-recaptcha' );

        if ( $widgets.length ) {
            renderReCaptchaWidgets( $widgets );
        }

        var $actions = $( '.awpcp-recaptcha-action' );

        if ( $actions.length ) {
            executeReCaptchaAction( $actions );
        }
    }

    function renderReCaptchaWidgets( $widgets ) {
        $widgets.each( function() {
            var element = $( this ), widgetId;

            if ( ! element.data( 'awpcp-recaptcha' ) ) {
                widgetId = grecaptcha.render( this, {
                  'sitekey' : element.attr( 'data-sitekey' ),
                  'theme' : 'light'
                } );

                element.data( 'awpcp-recaptcha', true );
                element.attr( 'data-recaptcha-widget-id', widgetId );
            }
        } );

        window['AWPCPGetReCaptchaResponse'] = function( callback ) {
            callback();
        };
    }

     function executeReCaptchaAction( $actions ) {
        var $action = $actions.eq( 0 );

        window['AWPCPGetReCaptchaResponse'] = function( callback ) {
            grecaptcha.execute( $action.data( 'sitekey' ), { action: $action.data( 'name' ) } ).then( function( token ) {
                $action.find( ':hidden' ).val( token );
                callback();
            } );
        };
    }

    var waitForReCaptchaToBeReady = function() {
        attempts = attempts + 1;

        if ( typeof grecaptcha !== 'undefined' && typeof grecaptcha.render !== 'undefined' ) {
            initReCaptcha();
        } else if ( attempts <= maxAttempts ) {
            timeout = setTimeout( waitForReCaptchaToBeReady, maxDelay * Math.pow( attempts / maxAttempts, 2 ) );
        }
    };

    window['AWPCPreCAPTCHAonLoadCallback'] = function() {
        if ( timeout ) {
            clearTimeout( timeout );
        }

        initReCaptcha();
    };

    $( waitForReCaptchaToBeReady );
});
