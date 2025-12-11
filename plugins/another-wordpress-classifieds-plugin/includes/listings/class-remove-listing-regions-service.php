<?php
/**
 * @package AWPCP\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Delete region information associated with a listing that is about to be
 * deleted.
 *
 * @since 4.0.0
 */
class AWPCP_RemoveListingRegionsService {

    /**
     * @var wpdb
     */
    private $db;

    /**
     * @since 4.0.0
     */
    public function __construct( $db ) {
        $this->db = $db;
    }

    /**
     * @since 4.0.0
     */
    public function register() {
        add_action( 'awpcp_before_delete_ad', [ $this, 'before_delete_listing' ] );
    }

    /**
     * Delete region data associated with the given listing.
     *
     * @since 4.0.0
     */
    public function before_delete_listing( $listing ) {
        $sql = 'DELETE FROM ' . AWPCP_TABLE_AD_REGIONS . ' WHERE ad_id = %d';

        $this->db->query( $this->db->prepare( $sql, $listing->ID ) );
    }
}
