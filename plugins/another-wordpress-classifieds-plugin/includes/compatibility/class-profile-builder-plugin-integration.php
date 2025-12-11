<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_profile_builder_plugin_integration() {
    _deprecated_function( __FUNCTION__, '4.2' );
    return new AWPCP_Profile_Builder_Plugin_Integration();
}

class AWPCP_Profile_Builder_Plugin_Integration {

    public function get_login_form_implementation( $implementation ) {
        _deprecated_function( __METHOD__, '4.2' );
        return $implementation;
    }
}
