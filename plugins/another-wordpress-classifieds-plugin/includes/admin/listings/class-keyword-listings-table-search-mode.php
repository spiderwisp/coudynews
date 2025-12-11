<?php
/**
 * @package AWPCP\Admin\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handler for the default search mode on the listings table: search by keyword.
 */
class AWPCP_KeywordListingsTableSearchMode {

    /**
     * @since 4.0.0
     */
    public function get_name() {
        return _x( 'Keyword', 'listings search mode', 'another-wordpress-classifieds-plugin' );
    }

    /**
     * @since 4.0.0
     */
    public function pre_get_posts() {
        // Keyword search is WordPress defult. Do nothing.
    }
}
