<?php
/**
 * @since 3.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function awpcp_pointers_manager() {
    return new AWPCP_PointersManager();
}

/**
 * @since 3.4
 */
class AWPCP_PointersManager {

    private $javascript;
    private $settings;

    public function __construct() {
        $this->javascript = awpcp()->js;
        $this->settings   = awpcp()->settings;
    }

    public function register_pointers() {
        if ( $this->settings->get_option( 'show-drip-autoresponder' ) ) {
            $drip_autoresponder = awpcp_drip_autoresponder();
            add_filter( 'awpcp-admin-pointers', array( $drip_autoresponder, 'register_pointer' ) );
        }
    }

    public function setup_pointers() {
        $pointers = apply_filters( 'awpcp-admin-pointers', array() );

        if ( empty( $pointers ) ) {
            return;
        }

        wp_enqueue_script( 'awpcp-admin-pointers' );
        wp_enqueue_style( 'awpcp-admin-style' );
        wp_enqueue_style( 'wp-pointer' );

        $this->javascript->set( 'pointers', $pointers );
    }
}
