<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



class AWPCP_TableEntryActionAjaxHandler extends AWPCP_AjaxHandler {

    private $action_handler;

    public function __construct( $action_handler, $response ) {
        parent::__construct( $response );

        $this->action_handler = $action_handler;
    }

    public function ajax() {
        check_ajax_referer( 'awpcp_ajax', 'nonce' );
        if ( ! awpcp_current_user_is_admin() ) {
            return $this->error_response( __( 'You are not authorized to perform this action.', 'another-wordpress-classifieds-plugin' ) );
        }

        return $this->action_handler->process_entry_action( $this );
    }
}
