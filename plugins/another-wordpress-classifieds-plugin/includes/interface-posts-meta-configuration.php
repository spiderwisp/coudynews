<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



interface AWPCP_Posts_Meta_Configuration {

    public function get_post_type();
    public function prepare_meta_key( $meta_key );
}
