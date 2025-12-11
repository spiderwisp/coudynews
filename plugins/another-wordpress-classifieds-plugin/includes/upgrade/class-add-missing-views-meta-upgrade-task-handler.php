<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Upgrade routine to add the `_awpcp_views` meta to ads that don't have it.
 */
class AWPCP_AddMissingViewsMetaUpgradeTaskHandler implements AWPCP_Upgrade_Task_Runner {

    /**
     * @var AWPCP_ListingsCollection
     */
    private $listings;

    /**
     * @since 4.0.0
     */
    public function __construct( $listings ) {
        $this->listings = $listings;
    }

    /**
     * @since 4.0.0
     */
    public function count_pending_items( $last_item_id ) {
        return $this->listings->count_listings(
            [

                /*
                 * I used 'any' somewhere else and found out too late that post
                 * status with 'exclude_from_search' set to true are not considered.
                 * As a result, some upgrade routines failed to process disabled
                 * ads in some cases.
                 */
                'post_status'            => [ 'disabled', 'draft', 'pending', 'publish', 'trash', 'auto-draft', 'future', 'private' ],
                'meta_query'             => [
                    [
                        'key'     => '_awpcp_views',
                        'compare' => 'NOT EXISTS',
                    ],
                ],
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            ]
        );
    }

    /**
     * @since 4.0.0
     */
    public function get_pending_items( $last_item_id ) {
        return $this->listings->find_listings(
            [
                // Check the post_status query var in count_pending_items().
                'post_status'            => [ 'disabled', 'draft', 'pending', 'publish', 'trash', 'auto-draft', 'future', 'private' ],
                'meta_query'             => [
                    [
                        'key'     => '_awpcp_views',
                        'compare' => 'NOT EXISTS',
                    ],
                ],
                'posts_per_page'         => 50,
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
                'fields'                 => 'ids',
            ]
        );
    }

    /**
     * @since 4.0.0
     */
    public function process_item( $item, $last_item_id ) {
        add_post_meta( absint( $item ), '_awpcp_views', 0 );

        return $item;
    }
}
