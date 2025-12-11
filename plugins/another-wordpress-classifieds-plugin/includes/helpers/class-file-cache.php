<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_file_cache() {
    return new AWPCP_FileCache( WP_CONTENT_DIR . '/uploads/awpcp/cache/' );
}

class AWPCP_FileCache {

    private $location;
    private $wp_filesystem;

    public function __construct( $location ) {
        $this->location      = $location;
        $this->wp_filesystem = awpcp_get_wp_filesystem();

        if ( ! $this->wp_filesystem ) {
            throw new AWPCP_Exception( esc_html__( 'Unable to initialize WordPress file system.', 'another-wordpress-classifieds-plugin' ) );
        }

        if ( ! $this->wp_filesystem->is_dir( $this->location ) ) {
            if ( ! $this->wp_filesystem->mkdir( $this->location, awpcp_get_dir_chmod() ) ) {
                throw new AWPCP_IOError( esc_html( sprintf( "Can't create cache directory: %s", $this->location ) ) );
            }
        }
    }

    public function set( $name, $value ) {
        $filename = $this->path( $name );

        if ( ! $this->wp_filesystem->put_contents( $filename, $value, awpcp_get_file_chmod() ) ) {
            throw new AWPCP_IOError( esc_html( sprintf( "Can't write to file %s for cache entry '%s'.", $filename, $name ) ) );
        }
    }

    public function path( $name ) {
        return trailingslashit( $this->location ) . $name . '.json';
    }

    public function get( $name ) {
        $filename = $this->path( $name );

        if ( ! $this->wp_filesystem->exists( $filename ) ) {
            throw new AWPCP_Exception( esc_html( sprintf( "No cache entry found with name '%s'.", $name ) ) );
        }

        $content = $this->wp_filesystem->get_contents( $filename );

        if ( false === $content ) {
            throw new AWPCP_IOError( esc_html( sprintf( "Can't read cache entry '%s' from file %s.", $name, $filename ) ) );
        }

        return $content;
    }

    public function url( $name ) {
        return str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $this->path( $name ) );
    }

    public function remove( $name ) {
        $filename = $this->path( $name );

        if ( $this->wp_filesystem->exists( $filename ) && ! $this->wp_filesystem->delete( $filename ) ) {
            throw new AWPCP_IOError( esc_html( sprintf( "Can't remove %s associated with entry '%s'.", $filename, $name ) ) );
        }
    }
}
