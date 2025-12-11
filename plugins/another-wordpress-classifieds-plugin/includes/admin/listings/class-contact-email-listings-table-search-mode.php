<?php
/**
 * @package AWPCP\Admin\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Search listings by the contact email.
 */
class AWPCP_ContactEmailListingsTableSearchMode {

    /**
     * @since 4.0.0
     */
    public function get_name() {
        return _x( 'Contact Email', 'listings table search', 'another-wordpress-classifieds-plugin' );
    }

    /**
     * @since 4.0.0
     */
    public function pre_get_posts( $query ) {
        if ( empty( $query->query_vars['s'] ) ) {
            return;
        }

        $query->query_vars['classifieds_query']['contact_email'] = $query->query_vars['s'];

        unset( $query->query_vars['s'] );
    }
}
