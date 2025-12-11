<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_ajax_request_handler( $routes ) {
    return new AWPCP_Ajax_Request_Handler( $routes );
}

class AWPCP_Ajax_Request_Handler {

    protected $routes;

    public function __construct( $routes ) {
        $this->routes = $routes;
    }

    public function handle_anonymous_ajax_request() {
        return $this->handle_ajax_request( $this->routes->get_anonymous_ajax_actions() );
    }

    private function handle_ajax_request( $ajax_actions ) {
        $action_name = awpcp_get_var( array( 'param' => 'action' ) );
        $action_name = str_replace( 'awpcp-', '', $action_name );

        if ( ! isset( $ajax_actions[ $action_name ] ) ) {
            return;
        }

        $current_action = $ajax_actions[ $action_name ];

        if ( is_null( $current_action->handler ) || ! is_callable( $current_action->handler ) ) {
            return;
        }

        $request_handler = call_user_func( $current_action->handler );

        if ( is_null( $request_handler ) ) {
            return;
        }

        $request_handler->ajax();
    }

    public function handle_private_ajax_request() {
        return $this->handle_ajax_request( $this->routes->get_private_ajax_actions() );
    }
}
