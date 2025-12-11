<?php
/**
 * @package AWPCP\Admin\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Table view for complete listings.
 */
class AWPCP_CompleteListingTableView {

    /**
     * @var object
     */
    private $listings_collection;

    /**
     * @param object $listings_collection  An instance of Listings Collection.
     * @since 4.0.0
     */
    public function __construct( $listings_collection ) {
        $this->listings_collection = $listings_collection;
    }

    /**
     * @since 4.0.0
     */
    public function get_label() {
        return _x( 'Complete', 'listing view', 'another-wordpress-classifieds-plugin' );
    }

    /**
     * @param string $current_url   The URL of the current admin page.
     * @since 4.0.0
     */
    public function get_url( $current_url ) {
        return add_query_arg( 'awpcp_filter', 'complete', $current_url );
    }

    /**
     * @since 4.0.0
     */
    public function get_count() {
        return $this->listings_collection->count_valid_listings();
    }

    /**
     * @param object $query     An instance of WP_Query.
     * @since 4.0.0
     */
    public function pre_get_posts( $query ) {
        $query->query_vars['classifieds_query']['is_valid'] = true;
    }
}
