<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AWPCP_UploadsManager {

    private $settings;

    public function __construct( $settings ) {
        $this->settings = $settings;
    }

    public function get_url_for_relative_path( $relative_path ) {
        $uploads_dir = $this->settings->get_runtime_option( 'awpcp-uploads-url' );
        return implode( DIRECTORY_SEPARATOR, array( $uploads_dir, $relative_path ) );
    }

    public function get_path_for_relative_path( $relative_path ) {
        $uploads_dir = $this->settings->get_runtime_option( 'awpcp-uploads-dir' );
        return implode( DIRECTORY_SEPARATOR, array( $uploads_dir, $relative_path ) );
    }

    public function move_file_to( $file, $relative_path, $related_directories = array() ) {
        $destination_dir = $this->get_path_for_relative_path( $relative_path );
        $wp_filesystem = awpcp_get_wp_filesystem();

        if ( ! $wp_filesystem ) {
            throw new AWPCP_Exception( esc_html__( 'Unable to initialize WordPress file system.', 'another-wordpress-classifieds-plugin' ) );
        }

        if ( ! is_dir( $destination_dir ) && ! wp_mkdir_p( $destination_dir ) ) {
            throw new AWPCP_Exception( esc_html__( "Destination directory doesn't exists and couldn't be created.", 'another-wordpress-classifieds-plugin' ) );
        }

        $target_directories = array_merge( array( $destination_dir ), $related_directories );
        $unique_filename    = awpcp_unique_filename( $file->get_path(), $file->get_real_name(), $target_directories );
        $destination_path   = implode( DIRECTORY_SEPARATOR, array( $destination_dir, $unique_filename ) );

        if ( ! $wp_filesystem->move( $file->get_path(), $destination_path ) ) {
            $wp_filesystem->delete( $file->get_path() );

            /* translators: %s is the name of the uploaded file. */
            $message = _x( 'The file %s could not be copied to the destination directory.', 'upload files', 'another-wordpress-classifieds-plugin' );
            $message = sprintf( $message, $file->get_real_name() );

            throw new AWPCP_Exception( esc_html( $message ) );
        }

        $file->set_path( $destination_path );
        $wp_filesystem->chmod( $destination_path, 0644 );

        return $file;
    }

    public function move_file_with_thumbnail_to( $file, $relative_path ) {
        $thumbnails_dir = $this->get_path_for_relative_path( 'thumbs' );
        return $this->move_file_to( $file, $relative_path, array( $thumbnails_dir ) );
    }
}
