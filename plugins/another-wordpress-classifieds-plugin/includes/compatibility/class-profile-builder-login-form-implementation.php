<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_profile_builder_login_form_implementation() {
    return new AWPCP_Profile_Builder_Login_Form_Implementation();
}

class AWPCP_Profile_Builder_Login_Form_Implementation {

    public function render( $redirect_url, $message = null ) {
        _deprecated_function( __METHOD__, '4.2' );
    }
}
