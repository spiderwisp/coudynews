<?php
/**
 * @package AWPCP\Admin\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Search classifieds by title on the Listings admin page.
 */
class AWPCP_TitleListingsTableSearchMode {

    /**
     * @since 4.0.0
     */
    public function get_name() {
        return _x( 'Title', 'listings search mode', 'another-wordpress-classifieds-plugin' );
    }

    /**
     * @param object $query     An instance of WP_Query.
     * @since 4.0.0
     */
    public function pre_get_posts( $query ) {
        if ( empty( $query->query_vars['s'] ) ) {
            return;
        }

        $query->query_vars['classifieds_query']['title'] = $query->query_vars['s'];

        unset( $query->query_vars['s'] );
    }
}
