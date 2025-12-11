/*global AWPCP */

AWPCP.run( 'awpcp/admin-export', [
    'jquery',
    'awpcp/settings'
  ],
  function( $, settings ) {
    $( function() {
      let progress = new ProgressBar( $( '.awpcp-step-2 .export-progress' ) );

      let exportInProgress = false;
      let cancelExport = false;
      let lastState = null;
      let nonce = null;

      function ProgressBar( $item, settings ) {
        $item.empty();
        $item.html(
          '<div class="awpcp-progress-bar"><span class="progress-text">0%</span><div class="progress-bar"><div class="progress-bar-outer"><div class="progress-bar-inner" style="width: 0%;"></div></div></div>' );

        this.$item = $item;
        this.$text = $item.find( '.progress-text' );
        this.$bar = $item.find( '.progress-bar' );

        this.set = function( completed, total ) {
          var pcg = Math.round( 100 * parseInt( completed ) / parseInt( total ) );
          this.$text.text( pcg + '%' );
          this.$bar.find( '.progress-bar-inner' ).attr( 'style', 'width: ' + pcg + '%;' );
        };
      }

      function handleError( msg, res ) {
        if ( msg ) {
          $( ' div.error p' ).text( msg );
        }

        if ( res && res.state ) {
          $.ajax( ajaxurl, {
            data: {
              action: 'awpcp-csv-export',
              state: state,
              cleanup: 1,
              _wpnonce: nonce
            },
            type: 'POST'
          } );
        }

        cancelExport = true;
        exportInProgress = false;

        $( '.awpcp-step-1,  .awpcp-step-2,  .awpcp-step-3' ).hide();
        $( 'div.error' ).show();
        $( '.canceled-export' ).show();

        $( 'html, body' ).animate( { scrollTop: 0 }, 'medium' );
      }

      function advanceExport( state ) {
        if ( !exportInProgress ) {
          return;
        }

        lastState = state;

        if ( cancelExport ) {
          exportInProgress = false;
          cancelExport = false;

          $( '.awpcp-step-2' ).fadeOut( function() {
            $( '.canceled-export' ).fadeIn();
          } );

          $.ajax( ajaxurl, {
            data: {
              'action': 'awpcp-csv-export', 'state': state,
              'cleanup': 1, '_wpnonce': nonce
            },
            type: 'POST',
            dataType: 'json',
            success: function( res ) {
            }
          } );
          return;
        }

        $.ajax( ajaxurl, {
          data: { 'action': 'awpcp-csv-export', 'state': state, '_wpnonce': nonce },
          type: 'POST',
          dataType: 'json',
          success: function( res ) {
            if ( !res || res.error ) {
              exportInProgress = false;
              handleError( ( res && res.error ) ? res.error : null, res );
              return;
            }

            $( '.awpcp-step-2 .listings' ).text( res.exported + ' / ' + res.count );
            $( '.awpcp-step-2 .size' ).text( res.filesize );
            progress.set( res.exported, res.count );

            if ( res.isDone ) {
              exportInProgress = false;

              $( '.awpcp-step-2' ).fadeOut( function() {
                $( '.awpcp-step-3 .download-link a' ).attr( 'href', res.fileurl );
                $( '.awpcp-step-3 .download-link a .filename' ).text( res.filename );
                $( '.awpcp-step-3 .download-link a .filesize' ).text( res.filesize );

                $( '.awpcp-step-3' ).fadeIn( function() {
                  $( '.awpcp-step-3 .cleanup-link' ).hide();
                } );
              } );

            } else {
              advanceExport( res.state );
            }
          },
          error: function() { handleError(); }
        } );
      }

      $( 'form#awpcp-csv-export-form' ).submit( function( e ) {
        e.preventDefault();
        nonce = $('#_wpnonce').val();
        let data = $( this ).serialize() + '&action=awpcp-csv-export';
        $.ajax( settings.get( 'ajaxurl' ), {
          data: data,
          type: 'POST',
          dataType: 'json',
          success: function( res ) {
            if ( !res || res.error ) {
              exportInProgress = false;
              handleError( ( res && res.error ) ? res.error : null, res );
              return;
            }

            $( '.awpcp-step-1' ).fadeOut( function() {
              exportInProgress = true;
              $( '.awpcp-step-2 .listings' ).text( '0 / ' + res.count );
              $( '.awpcp-step-2 .size' ).text( '0 KB' );

              $( '.awpcp-step-2' ).fadeIn( function() {
                advanceExport( res.state );
              } );
            } );
          },
          error: function() { handleError(); }
        } );
      } );

      $( 'a.awpcp-cancel-export' ).click( function( e ) {
        e.preventDefault();
        cancelExport = true;
      } );

      $( '.awpcp-step-3 .download-link a' ).click( function( e ) {
        $( '.awpcp-step-3 .cleanup-link' ).fadeIn();
      } );

      $( '.awpcp-step-3 .cleanup-link a' ).click( function( e ) {
        e.preventDefault();
        $.ajax( settings.get( 'ajaxurl' ), {
          data: { 'action': 'awpcp-csv-export', 'state': lastState, 'cleanup': 1, '_wpnonce': nonce },
          type: 'POST',
          dataType: 'json',
          success: function( res ) {
            location.href = '';
          }
        } );
      } );

    } );
  } );
