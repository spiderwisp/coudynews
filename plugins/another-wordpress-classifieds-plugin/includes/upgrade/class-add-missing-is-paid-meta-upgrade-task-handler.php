<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Upgrade routine to add the `_awpcp_is_paid` meta to ads that don't have it.
 */
class AWPCP_AddMissingIsPaidMetaUpgradeTaskHandler implements AWPCP_Upgrade_Task_Runner {

    /**
     * @var AWPCP_ListingRenderer
     */
    private $listing_renderer;

    /**
     * @var AWPCP_ListingsCollection
     */
    private $listings;

    /**
     * @since 4.0.0
     */
    public function __construct( $listing_renderer, $listings ) {
        $this->listing_renderer = $listing_renderer;
        $this->listings         = $listings;
    }

    /**
     * @since 4.0.0
     */
    public function count_pending_items( $last_item_id ) {
        return $this->listings->count_listings( $this->prepare_query_vars( [] ) );
    }

    /**
     * Add common query vars for counting and finding items that need to be
     * processed by this routine.
     *
     * @since 4.0.0
     */
    private function prepare_query_vars( $query_vars ) {
        /*
         * I used 'any' somewhere else and found out too late that post
         * status with 'exclude_from_search' set to true are not considered.
         * As a result, some upgrade routines failed to process disabled
         * ads in some cases.
         */
        $query_vars['post_status'] = [ 'disabled', 'draft', 'pending', 'publish', 'trash', 'auto-draft', 'future', 'private' ];

        $query_vars['meta_query'][] = [
            'key'     => '_awpcp_is_paid',
            'compare' => 'NOT EXISTS',
        ];

        $query_vars['update_post_meta_cache'] = false;
        $query_vars['update_post_term_cache'] = false;

        return $query_vars;
    }

    /**
     * @since 4.0.0
     */
    public function get_pending_items( $last_item_id ) {
        $query_vars = $this->prepare_query_vars(
            [
                'posts_per_page' => 50,
                'no_found_rows'  => true,
            ]
        );

        return $this->listings->find_listings( $query_vars );
    }

    /**
     * @since 4.0.0
     */
    public function process_item( $item, $last_item_id ) {
        $payment_term = $this->listing_renderer->get_payment_term( $item );
        $is_paid      = false;

        if ( $payment_term ) {
            $is_paid = $payment_term->is_paid();
        }

        add_post_meta( $item->ID, '_awpcp_is_paid', $is_paid );

        return $item;
    }
}
