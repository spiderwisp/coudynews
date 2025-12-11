<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_filesystem() {
    return new AWPCP_Filesystem( awpcp()->settings );
}

class AWPCP_Filesystem {

    private $settings;
    private $wp_filesystem;

    public function __construct( $settings ) {
        $this->settings      = $settings;
        $this->wp_filesystem = awpcp_get_wp_filesystem();

        if ( ! $this->wp_filesystem ) {
            throw new AWPCP_Exception( esc_html__( 'Unable to initialize WordPress file system.', 'another-wordpress-classifieds-plugin' ) );
        }
    }

    public function get_uploads_dir() {
        $path = $this->settings->get_runtime_option( 'awpcp-uploads-dir' );
        return $this->prepare_directory( $path );
    }

    private function prepare_directory( $path ) {
        if ( ! $this->wp_filesystem->is_dir( $path ) ) {
            return $this->create_directory( $path );
        } elseif ( ! $this->wp_filesystem->is_writable( $path ) ) {
            return $this->make_directory_writable( $path );
        } else {
            return $path;
        }
    }

    private function create_directory( $path ) {
        $previous_umask = umask( 0 );

        if ( ! $this->wp_filesystem->mkdir( $path, awpcp_get_dir_chmod(), true ) ) {
            $message = __( 'There was a problem trying to create directory <directory-name>.', 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '<directory-name>', awpcp_utf8_basename( $path ), $message );
            throw new AWPCP_Exception( esc_html( $message ) );
        }

        umask( $previous_umask );

        return $path;
    }

    private function get_default_directory_mode() {
        return intval( $this->settings->get_option( 'upload-directory-permissions', '0755' ), 8 );
    }

    private function make_directory_writable( $path ) {
        $previous_umask = umask( 0 );

        if ( ! $this->wp_filesystem->chmod( $path, $this->get_default_directory_mode() ) ) {
            $message = __( 'There was a problem trying to make directory <directory-name> writable.', 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '<directory-name>', awpcp_utf8_basename( $path ), $message );
            throw new AWPCP_Exception( esc_html( $message ) );
        }

        umask( $previous_umask );

        return $path;
    }

    public function get_thumbnails_dir() {
        $path = implode( DIRECTORY_SEPARATOR, array( $this->settings->get_runtime_option( 'awpcp-uploads-dir' ), 'thumbs' ) );
        return $this->prepare_directory( $path );
    }
}
