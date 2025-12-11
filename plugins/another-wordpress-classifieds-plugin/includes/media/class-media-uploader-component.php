<?php
/**
 * @package AWPCP\UI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function for Media Uploader Component
 */
function awpcp_media_uploader_component() {
    return new AWPCP_MediaUploaderComponent( awpcp()->js );
}

/**
 * Generic UI component used to upload media.
 */
class AWPCP_MediaUploaderComponent {

    /**
     * @var AWPCP_JavaScript
     */
    private $javascript;

    /**
     * @var bool
     */
    private $echo = false;

    /**
     * @param AWPCP_JavaScript $javascript    An instance of JavaScript.
     */
    public function __construct( $javascript ) {
        $this->javascript = $javascript;
    }

    /**
     * @param array $configuration  An array of configuration options.
     */
    public function render( $configuration ) {
        $configuration = wp_parse_args( $configuration, array(
            'flash_swf_url'       => includes_url( 'js/plupload/plupload.flash.swf' ),
            'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
        ) );

        $this->javascript->set( 'media-uploader-data', $configuration );

        return $this->render_component( $configuration );
    }

    /**
     * @since 4.3.3
     *
     * @param array $configuration  An array of configuration options.
     *
     * @return void
     */
    public function show( $configuration ) {
        $this->echo = true;
        $this->render( $configuration );
        $this->echo = false;
    }

    /**
     * @param array $configuration  An array of configuration options.
     *
     * @return string
     */
    private function render_component( $configuration ) {
        $file = AWPCP_DIR . '/templates/components/media-uploader.tpl.php';
        if ( $this->echo ) {
            include $file;
            return '';
        }

        ob_start();
        include $file;
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }
}
