<?php
/**
 * @package AWPCP\Admin\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Search listings by the contact phone.
 */
class AWPCP_ContactPhoneListingsTableSearchMode {

    /**
     * @since 4.0.0
     */
    public function get_name() {
        return _x( 'Contact Phone', 'listings table search', 'another-wordpress-classifieds-plugin' );
    }

    /**
     * @param object $query     An instance of WP_Query.
     * @since 4.0.0
     */
    public function pre_get_posts( $query ) {
        if ( empty( $query->query_vars['s'] ) ) {
            return;
        }

        $query->query_vars['classifieds_query']['contact_phone'] = $query->query_vars['s'];

        unset( $query->query_vars['s'] );
    }
}
