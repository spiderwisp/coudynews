<?php
/**
 * @package AWPCP\Admin\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handler for the default search mode on the listings table: search by ID.
 */
class AWPCP_IDListingsTableSearchMode {

    /**
     * @since 4.0.0
     */
    public function get_name() {
        return _x( 'ID', 'listings search mode', 'another-wordpress-classifieds-plugin' );
    }

    /**
     * @since 4.0.0
     */
    public function pre_get_posts( $query ) {
        if ( empty( $query->query_vars['s'] ) ) {
            return;
        }

        $query->query_vars['p'] = absint( $query->query_vars['s'] );

        unset( $query->query_vars['s'] );
    }
}
