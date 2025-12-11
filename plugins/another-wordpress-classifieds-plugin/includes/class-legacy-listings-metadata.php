<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_legacy_listings_metadata() {
    static $instance = null;

    if ( is_null( $instance ) ) {
        $instance = new AWPCP_Legacy_Listings_Metadata( $GLOBALS['wpdb'] );
    }

    return $instance;
}

class AWPCP_Legacy_Listings_Metadata {

    private $db;

    public function __construct( $db ) {
        $this->db = $db;
    }

    public function get( $listing_id, $meta_key ) {
        $query = 'SELECT meta_value FROM ' . AWPCP_TABLE_AD_META . ' WHERE awpcp_ad_id = %d AND meta_key = %s';
        return $this->db->get_var( $this->db->prepare( $query, $listing_id, $meta_key ) );
    }
}
