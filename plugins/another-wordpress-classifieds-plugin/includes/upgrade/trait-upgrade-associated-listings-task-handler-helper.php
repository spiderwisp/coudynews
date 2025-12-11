<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Common function for upgrade tasks handlers that update the ID of associated
 * listings.
 */
trait AWPCP_UpgradeAssociatedListingsTaskHandlerHelper {

    /**
     * @param object $old_listing_id  The ID of a listing in the old database tables.
     */
    protected function get_id_of_associated_listing( $old_listing_id ) {
        $query = $this->get_query_for_associated_listing( $old_listing_id, [ 'fields' => 'ids' ] );

        if ( ! is_array( $query->posts ) || empty( $query->posts ) ) {
            return 0;
        }

        return intval( $query->posts[0] );
    }

    protected function get_query_for_associated_listing( $old_listing_id, $query_vars = [] ) {
        $query_vars = array_merge(
            [
                'post_type'              => 'awpcp_listing',

                /*
                 * I used 'any' before and found out too late that post status
                 * with 'exclude_from_search' set to true are not considered.
                 * As a result, some upgrade routines failed to process disabled
                 * ads in some cases.
                 */
                'post_status'            => [ 'disabled', 'draft', 'pending', 'publish', 'trash', 'auto-draft', 'future', 'private' ],
                'meta_query'             => [
                    [
                        'key'     => "_awpcp_old_id_{$old_listing_id}",
                        'compare' => 'EXISTS',
                    ],
                ],
                // See https://10up.github.io/Engineering-Best-Practices/php/#performance.
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            ],
            $query_vars
        );

        return new WP_Query( $query_vars );
    }

    /**
     * @param object $old_listing_id  The ID of a listing in the old database tables.
     */
    protected function get_associated_listing( $old_listing_id ) {
        $query = $this->get_query_for_associated_listing( $old_listing_id );

        if ( ! is_array( $query->posts ) || empty( $query->posts ) ) {
            return null;
        }

        return $query->posts[0];
    }
}
