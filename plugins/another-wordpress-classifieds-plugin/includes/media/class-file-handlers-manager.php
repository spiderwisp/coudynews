<?php
/**
 * @package AWPCP\Media
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Allows the plugin and premium modules to register File Handlers for the types
 * of files currently supported and provide methods to retrieve the appropriate
 * handler for a given file.
 */
class AWPCP_File_Handlers_Manager {

    private $handlers;

    /**
     * @var object AWPCP_Container
     */
    private $container;

    public function __construct( $container ) {
        $this->container = $container;
    }

    /**
     * Loops through the array of file handlers definitions an attempts to
     * return an instance by calling a constructor function or loading it from
     * the plugin's container.
     *
     * @param AWPCP_UploadedFileLogic $uploaded_file     The uploaded file.
     * @throws AWPCP_Exception  When no handler can be found.
     */
    public function get_handler_for_file( $uploaded_file ) {
        foreach ( $this->get_file_handlers() as $handler ) {
            if ( ! in_array( $uploaded_file->get_mime_type(), $handler['mime_types'], true ) ) {
                continue;
            }

            $instance = null;

            if ( is_callable( $handler['constructor'] ) ) {
                $instance = call_user_func( $handler['constructor'] );
            }

            if ( isset( $this->container[ $handler['constructor'] ] ) ) {
                $instance = $this->container[ $handler['constructor'] ];
            }

            if ( ! is_object( $instance ) ) {
                continue;
            }

            return $instance;
        }

        $message = _x( 'There is no file handler for this kind of file (<mime-type>). Aborting.', 'file uploads', 'another-wordpress-classifieds-plugin' );
        $message = str_replace( '<mime-type>', $uploaded_file->get_mime_type(), $message );

        throw new AWPCP_Exception( esc_html( $message ) );
    }

    public function get_file_handlers() {
        if ( is_null( $this->handlers ) ) {
            $this->handlers = apply_filters( 'awpcp-file-handlers', array() );
        }

        return $this->handlers;
    }
}
