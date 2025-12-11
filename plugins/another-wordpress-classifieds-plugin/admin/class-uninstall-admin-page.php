<?php
/**
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function.
 */
function awpcp_uninstall_admin_page() {
    return new AWPCP_UninstallAdminPage(
        awpcp()->container['Uninstaller'],
        awpcp()->container['Settings']
    );
}

/**
 * Uninstall admin page.
 */
class AWPCP_UninstallAdminPage {

    /**
     * @var object
     */
    private $uninstaller;

    /**
     * @var AWPCP_Settings_API
     */
    private $settings;

    /**
     * @param object $uninstaller   An installer of Uninstaller.
     * @param object $settings      An instance of Settings.
     */
    public function __construct( $uninstaller, $settings ) {
        $this->uninstaller = $uninstaller;
        $this->settings    = $settings;
    }

    /**
     * Renders the page.
     */
    public function dispatch() {
        $action = awpcp_get_var( array( 'param' => 'action', 'default' => 'confirm' ) );
        $nonce  = awpcp_get_var( array( 'param' => '_wpnonce' ), 'get' );

        if ( 0 === strcmp( $action, 'uninstall' ) && ( ! wp_verify_nonce( $nonce, 'awpcp-uninstall' ) || ! awpcp_current_user_is_admin() ) ) {
            wp_die( esc_html__( 'You are not authorized to perform this action.', 'another-wordpress-classifieds-plugin' ) );
        }

        if ( 0 !== strcmp( $action, 'uninstall' ) ) {
            $url      = awpcp_current_url();
            $dirname  = $this->settings->get_runtime_option( 'awpcp-uploads-dir' );
            $template = AWPCP_DIR . '/admin/templates/admin-panel-uninstall.tpl.php';

            return awpcp_render_template( $template, compact( 'action', 'url', 'dirname' ) );
        }

        $this->uninstaller->uninstall();
    }
}
