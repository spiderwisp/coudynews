<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



interface AWPCP_HTML_Element {

    public function build( $params = array() );
}
