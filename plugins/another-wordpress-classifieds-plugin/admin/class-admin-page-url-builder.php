<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_admin_page_url_builder() {
    return new AWPCP_Admin_Page_URL_Builder();
}

class AWPCP_Admin_Page_URL_Builder {

    private $blacklisted_params = array(
        'action2', 'action', // action and bulk actions
        'selected', // selected rows for bulk actions
        '_wpnonce',
        '_wp_http_referer',
    );

    public function set_blacklisted_params( $blacklisted_params ) {
        $this->blacklisted_params = $blacklisted_params;
    }

    public function get_current_url_with_params( $params = array() ) {
        return $this->get_url_with_params( awpcp_current_url(), $params );
    }

    public function get_url_with_params( $url, $params = array() ) {
        $url = remove_query_arg( $this->blacklisted_params, $url );
        $url = add_query_arg( urlencode_deep( $params ), $url );

        return $url;
    }
}
