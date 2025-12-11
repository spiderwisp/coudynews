<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_users_autocomplete_ajax_handler() {
    return new AWPCP_UsersAutocompleteAjaxHandler( awpcp_users_collection(), '', awpcp_ajax_response() );
}

class AWPCP_UsersAutocompleteAjaxHandler extends AWPCP_AjaxHandler {

    private $users;

    public function __construct( $users, $null, $response ) {
        parent::__construct( $response );

        $this->users = $users;
    }

    public function ajax() {
        $users = $this->users->find( array(
            'fields' => array( 'ID', 'public_name' ),
            'like'   => awpcp_get_var( array( 'param' => 'term' ) ),
            'limit' => 100,
        ) );

        return $this->success( array( 'items' => array_values( $users ) ) );
    }
}
