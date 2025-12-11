<?php
/**
 * @package AWPCP\Admin\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Search listings by their location.
 */
class AWPCP_LocationListingsTableSearchMode {

    /**
     * @since 4.0.0
     */
    public function get_name() {
        return _x( 'Location', 'listings table search', 'another-wordpress-classifieds-plugin' );
    }

    /**
     * @param object $query     An instance of WP_Query.
     * @since 4.0.0
     */
    public function pre_get_posts( $query ) {
        if ( empty( $query->query_vars['s'] ) ) {
            return;
        }

        $query->query_vars['classifieds_query']['region'] = $query->query_vars['s'];

        unset( $query->query_vars['s'] );
    }
}
