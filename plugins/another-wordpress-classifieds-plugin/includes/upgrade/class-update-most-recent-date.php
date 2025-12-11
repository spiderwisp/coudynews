<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Upgrade routine to update `_awpcp_most_recent_start_date` to match renewal date.
 */
class AWPCP_UpdateMostRecentDate implements AWPCP_Upgrade_Task_Runner {

    /**
     * @var object
     */
    private $db;

    /**
     * Constructor.
     */
    public function __construct( $db ) {
        $this->db = $db;
    }

    /**
     * @since 4.0.5
     */
    public function count_pending_items( $last_item_id ) {
        $sql    = $this->db->prepare(
            "SELECT COUNT(p.ID) AS COUNT
            FROM %i AS p
            INNER JOIN %i AS pm ON p.ID = pm.post_id
            INNER JOIN %i AS pm2 ON p.ID = pm2.post_id
            WHERE pm.meta_key = %s
            AND pm2.meta_key = %s
            AND CAST(pm2.meta_value AS DATETIME) < CAST(pm.meta_value AS DATETIME)",
            $this->db->posts,
            $this->db->postmeta,
            $this->db->postmeta,
            '_awpcp_renewed_date',
            '_awpcp_most_recent_start_date'
        );
        $result = $this->db->get_results( $sql );

        return (int) $result[0]->COUNT;
    }

    /**
     * @since 4.0.5
     */
    public function get_pending_items( $last_item_id ) {
        $sql    = $this->db->prepare(
            "SELECT p.ID, pm.meta_value AS renewed, pm2.meta_value AS start
            FROM %i AS p
            INNER JOIN %i AS pm ON p.ID = pm.post_id
            INNER JOIN %i AS pm2 ON p.ID = pm2.post_id
            WHERE pm.meta_key = %s
            AND pm2.meta_key = %s
            AND CAST(pm2.meta_value AS DATETIME) < CAST(pm.meta_value AS DATETIME)
            LIMIT 50",
            $this->db->posts,
            $this->db->postmeta,
            $this->db->postmeta,
            '_awpcp_renewed_date',
            '_awpcp_most_recent_start_date'
        );
        $result = $this->db->get_results( $sql );

        return $result;
    }

    /**
     * @since 4.0.5
     */
    public function process_item( $item, $last_item_id ) {
        update_post_meta( absint( $item->ID ), '_awpcp_most_recent_start_date', $item->renewed );

        return $item->ID;
    }
}
