<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor for Browse Categories Page Redirection Handler.
 */
function awpcp_browse_categories_page_redirection_handler() {
    return new AWPCP_Browse_Categories_Page_Redirection_Handler(
        awpcp_request()
    );
}

class AWPCP_Browse_Categories_Page_Redirection_Handler {

    private $request;

    public function __construct( $request ) {
        $this->request = $request;
    }

    public function maybe_redirect() {
        $custom = $this->request->get_query_var( 'awpcp-custom' );

        if ( strcmp( $custom, 'redirect-browse-listings' ) === 0 ) {
            return $this->redirect();
        }

        $browse_categories_page_info = get_option( 'awpcp-browse-categories-page-information', array() );

        if ( empty( $browse_categories_page_info['page_id'] ) ) {
            return;
        }

        $browse_categores_page_id = $browse_categories_page_info['page_id'];
        $current_page_id          = $this->request->get_query_var( 'page_id' );

        if ( $current_page_id && $browse_categores_page_id !== $current_page_id ) {
            return;
        }

        if ( $browse_categores_page_id !== $current_page_id ) {
            return;
        }

        return $this->redirect();
    }

    private function redirect() {
        $category_id = $this->request->get_category_id();
        $params      = wp_parse_args( wp_parse_url( awpcp_current_url(), PHP_URL_QUERY ) );

        unset( $params['page_id'] );
        unset( $params['category_id'] );
        unset( $params['awpcp_category_id'] );

        if ( $category_id ) {
            $url = awpcp_get_browse_category_url_from_id( $category_id );
        } else {
            $url = awpcp_get_browse_categories_page_url();
        }

        wp_safe_redirect( add_query_arg( urlencode_deep( $params ), $url ), 301 );
        exit();
    }
}
