<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_listings_meta() {
    return new AWPCP_Posts_Meta(
        new AWPCP_Listing_Meta_Configuration(),
        $GLOBALS['wpdb']
    );
}

class AWPCP_Listing_Meta_Configuration implements AWPCP_Posts_Meta_Configuration {

    public function get_post_type() {
        return AWPCP_LISTING_POST_TYPE;
    }

    public function prepare_meta_key( $meta_key ) {
        return "_awpcp_$meta_key";
    }
}
