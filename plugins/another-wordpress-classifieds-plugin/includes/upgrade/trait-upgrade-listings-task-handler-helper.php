<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Common functions for upgrade task handlers that migrate listings.
 */
trait AWPCP_UpgradeListingsTaskHandlerHelper {

    /**
     * @since 4.0.0
     */
    private function get_max_legacy_post_id() {
        $table = AWPCP_TABLE_ADS;

        return intval( $this->db->get_var( "SELECT MAX(ad_id) FROM $table" ) );
    }

    /**
     * @since 4.0.0
     */
    private function maybe_insert_post_with_id( $post_id, $post_data ) {
        if ( $this->get_max_post_id() < ( $post_id - 1 ) ) {
            return $this->insert_post_with_id( $post_id, $post_data );
        }

        return $this->wordpress->insert_post( $post_data, true );
    }

    /**
     * @since 4.0.0
     */
    private function get_max_post_id() {
        return intval( $this->db->get_var( "SELECT MAX(ID) FROM {$this->db->posts}" ) );
    }

    /**
     * @since 4.0.0
     */
    private function insert_post_with_id( $post_id, $post_data ) {
        $post_data['import_id'] = $post_id;

        return $this->wordpress->insert_post( $post_data, true );
    }
}
