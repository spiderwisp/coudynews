<?php
/**
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 4.1.0
 */
function awpcp_default_layout_ajax_handler() {
    return new AWPCP_Default_Layout_Ajax_Handler(
        awpcp_request(),
        awpcp_ajax_response(),
        awpcp()->container['SettingsManager']
    );
}

class AWPCP_Default_Layout_Ajax_Handler extends AWPCP_AjaxHandler {

    private $request;
    private $settings_manager;

    public function __construct( $request, $ajax_response, $settings_manager ) {
        parent::__construct( $ajax_response );

        $this->request          = $request;
        $this->settings_manager = $settings_manager;
    }

    public function ajax() {
        $nonce = $this->request->post( 'security' );

        if ( ! wp_verify_nonce( $nonce, 'reset-default' ) ) {
            return $this->error();
        }

        $displayadlyoutcode   = $this->settings_manager->get_setting( 'displayadlayoutcode' );
        $awpcpshowtheadlayout = $this->settings_manager->get_setting( 'awpcpshowtheadlayout' );
        $id                   = $this->request->post( 'id' );

        if ( $id === 'displayadlayoutcode-default' ) {
            return $this->success( $displayadlyoutcode );
        }

        if ( $id === 'awpcpshowtheadlayout-default' ) {
            return $this->success( $awpcpshowtheadlayout );
        }

        return $this->error();
    }
}
