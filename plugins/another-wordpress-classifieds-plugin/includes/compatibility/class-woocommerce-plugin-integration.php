<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_woocommerce_plugin_integration() {
    return new AWPCP_WooCommercePluginIntegration();
}

class AWPCP_WooCommercePluginIntegration {

    /**
     * @var AWPCP_Query
     */
    private $query;

    public function __construct() {
        $this->query = awpcp_query();
    }

    public function filter_prevent_admin_access( $prevent_access ) {
        return $prevent_access;
    }

    public function filter_unforce_ssl_checkout( $value ) {
        if ( $value && $this->query->is_page_that_accepts_payments() ) {
            return false;
        } else {
            return $value;
        }
    }
}
