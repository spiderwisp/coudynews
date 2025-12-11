<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Task runner used update object counts for categories terms.
 *
 * @since 4.0.0
 */
class AWPCP_Update_Categories_Term_Count_Upgrade_Task_Runner implements AWPCP_Upgrade_Task_Runner {

    /**
     * @var string
     */
    private $listing_category_taxonomy;

    /**
     * @var object wpdb
     */
    private $db;

    /**
     * @since 4.0.0
     */
    public function __construct( $listing_category_taxonomy, $db ) {
        $this->listing_category_taxonomy = $listing_category_taxonomy;
        $this->db                        = $db;
    }

    /**
     * @since 4.0.0
     */
    public function count_pending_items( $last_item_id ) {
        $sql = "SELECT COUNT(term_taxonomy_id) FROM {$this->db->term_taxonomy} WHERE taxonomy = %s AND term_taxonomy_id > %d";
        $sql = $this->db->prepare( $sql, $this->listing_category_taxonomy, $last_item_id );

        return intval( $this->db->get_var( $sql ) );
    }

    /**
     * @since 4.0.0
     */
    public function get_pending_items( $last_item_id ) {
        $sql = "SELECT term_taxonomy_id FROM {$this->db->term_taxonomy} WHERE taxonomy = %s AND term_taxonomy_id > %d ORDER BY term_taxonomy_id ASC LIMIT 25";
        $sql = $this->db->prepare( $sql, $this->listing_category_taxonomy, $last_item_id );

        return $this->db->get_results( $sql );
    }

    /**
     * @since 4.0.0
     */
    public function process_item( $item, $last_item_id ) {
        wp_update_term_count_now( [ $item->term_taxonomy_id ], $this->listing_category_taxonomy );

        return $item->term_taxonomy_id;
    }
}
